<?php
/**
 * @package   yii2-sms
 * @author    Yuri Shekhovtsov <shekhovtsovy@yandex.ru>
 * @copyright Copyright &copy; Yuri Shekhovtsov, lowbase.ru, 2016
 * @version   1.0.0
 */

namespace lowbase\sms;

use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\Object;
use lowbase\sms\models\Sms as smsModel;
use yii\helpers\ArrayHelper;

/**
 * Class Sms
 * @package lowbase\sms
 */
class Sms extends Object
{
	/**
	 * Sms services and their settings
	 *
	 * 'services' => [
	 *      ['providerName_1'] => [
	 *             'class' => '',
	 *             'login' => '',
	 *             'password' => '',
	 *             'order' => '',
	 *              ],
	 *      ['providerName_2'] => [
	 *             'class' => '',
	 *             'login' => '',
	 *             'password' => ''
	 *              'order' => '',
	 *              ],
	 *  ]
	 *
	 * @var array
	 */
	protected $services = [];
	protected $availableServices = [];

	/** @var  AbstractService $currentService */
	protected $currentService;
	protected $currentServiceName;

	protected $cascade = false;

	/**
	 * Initialize the component.
	 */
	public function init()
	{
		parent::init();
		$this->registerTranslations();
		if (!$this->services) {
			throw new ErrorException(\Yii::t('sms', 'Services are not configured.'));
		}
		foreach ($this->services as $service) {
			if (!key_exists('class', $service)) {
				throw new ErrorException(\Yii::t('sms', 'Class Unknown.'));
			}
			if (!key_exists('login', $service)) {
				throw new ErrorException(\Yii::t('sms', 'Login Unknown.'));
			}
			if (!key_exists('password', $service)) {
				throw new ErrorException(\Yii::t('sms', 'Password Unknown.'));
			}
			if (!key_exists('order', $service)) {
				throw new ErrorException(\Yii::t('sms', 'Order Unknown.'));
			}
			if (!class_exists($service['class'])) {
				throw new ErrorException(\Yii::t('sms', 'Service not found.'));
			}
			if ($service['class'] instanceof AbstractService) {
				throw new ErrorException(\Yii::t('sms', 'Class must be inherited from the AbstractService.'));
			}
		}
		ArrayHelper::multisort($this->services, 'order');
		$this->availableServices = $this->services;
		$this->currentServiceName = array_keys($this->availableServices)[0];
		$this->currentService = $this->getServiceByName($this->currentServiceName);

	}

	/**
	 * Include translation messages for component
	 */
	public static function registerTranslations()
	{
		if (!isset(\Yii::$app->i18n->translations['sms']) && !isset(\Yii::$app->i18n->translations['sms/*'])) {
			\Yii::$app->i18n->translations['sms'] = [
				'class' => 'yii\i18n\PhpMessageSource',
				'basePath' => '/lowbase/components/sms/messages',
				'forceTranslation' => true,
				'fileMap' => [
					'sms' => 'sms.php'
				]
			];
		}
	}

	/**
	 * Send Sms and put info into database
	 *
	 * @param $phone
	 * @param $text
	 * @param bool $saveInfo
	 * @param null $type
	 * @param null $forUserId
	 * @param null $mustSentAt
	 * @param array $options
	 * @return bool
	 */
	public function sendSms($phone, $text, $saveInfo = true, $type = null, $forUserId = null, $mustSentAt = null, $options = [])
	{
		$model = new smsModel([
			'phone' => $phone,
			'text' => $text,
			'provider' => $this->currentServiceName,
			'type' => $type,
			'for_user_id' => $forUserId,
			'must_sent_at' => $mustSentAt
		]);

		if ($model->validate()) {
			$result = $this->currentService->sendSms($phone, $text, $mustSentAt, $options);

			if ($saveInfo) {
				$model->status = $result['status'];
				$model->provider_answer = $result['answer'];
				if (isset($result['id'])) {
					$model->provider_sms_id = $result['id'];
				}
				$model->save();
			}

			unset($this->availableServices[$this->currentServiceName]);

			while ($result['status'] === smsModel::STATUS_FAILED && $this->cascade && count($this->availableServices)) {
				// retry send sms with new Service
				$currentServiceName = array_keys($this->availableServices)[0];
				$this->useService($currentServiceName);
				$result['status'] = $this->sendSms($phone, $text, $saveInfo, $type, $forUserId, $mustSentAt, $options);
			}

			return $result['status'];
		}

		return false;
	}

	/**
	 * Get sms status by my id (from database)
	 *
	 * @param $id
	 * @param bool $saveInfo
	 * @param array $options
	 * @return bool|integer
	 * @throws ErrorException
	 */
	public function getSmsStatusById($id, $saveInfo = true, $options = [])
	{
		$model = smsModel::findOne($id);

		if ($model === null || $model->provider_sms_id === null) {

			return false;
		}
		$this->currentService = $this->getServiceByName($model->provider);

		$result = $this->currentService->getSmsStatus($model->provider_sms_id, array_merge(['phone' => $model->phone], $options));
		if ($saveInfo) {
			$model->status = $result['status'];
			$model->provider_answer = $result['answer'];
			$model->save();
		}

		return $result['status'];
	}

	/**
	 * Get sms status by provider id
	 *
	 * Some service need phone in options for status!!!
	 *
	 * @param $providerSmsId
	 * @param array $options
	 * @return integer
	 */
	public function getSmsStatusByProviderId($providerSmsId, $options = [])
	{

		$result = $this->currentService->getSmsStatus($providerSmsId, $options);

		return $result['status'];
	}

	/**
	 * Get account status
	 *
	 * @param array $options
	 * @return bool|float
	 */
	public function getBalance($options = [])
	{
		return $this->currentService->getBalance($options);
	}

	/**
	 * Get service by name
	 *
	 * @param $serviceName
	 * @return object
	 * @throws ErrorException
	 */
	public function getServiceByName($serviceName)
	{

		if (in_array($serviceName, array_keys($this->services))) {
			$service = $this->services[$serviceName];

			return \Yii::createObject([
				'class' => $service['class'],
				'login' => $service['login'],
				'password' => $service['password'],
			]);

		} else {
			throw new ErrorException(\Yii::t('sms', 'Service not found.'));
		}
	}

	/**
	 * Use specific service
	 *
	 * @param $serviceName
	 * @return $this
	 * @throws ErrorException
	 */
	public function useService($serviceName)
	{
		$this->currentService = $this->getServiceByName($serviceName);
		$this->currentServiceName = $serviceName;

		return $this;
	}

	/**
	 * Set services
	 *
	 * @param $services
	 */
	public function setServices($services)
	{
		$this->services = $services;
	}

	/**
	 * Set cascade
	 *
	 * @param $cascade
	 */
	public function setCascade($cascade)
	{
		$this->cascade = $cascade;
	}

}
