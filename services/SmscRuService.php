<?php
/**
 * @package   yii2-sms
 * @author    Yuri Shekhovtsov <shekhovtsovy@yandex.ru>
 * @copyright Copyright &copy; Yuri Shekhovtsov, lowbase.ru, 2016
 * @version   1.0.0
 */

namespace lowbase\sms\services;

use lowbase\sms\AbstractService;
use lowbase\sms\models\Sms;

/**
 * Class SmscRuService
 * @link http://smsc.ru/api/
 * @package lowbase\sms
 */
class SmscRuService extends AbstractService
{
	const SEND_SMS_URL = 'http://smsc.ru/sys/send.php';
	const GET_SMS_STATUS_URL = 'http://smsc.ru/sys/status.php';
	const GET_BALANCE_URL = 'http://smsc.ru/sys/balance.php';

	public $statusMap = [
		-3 => Sms::STATUS_UNKNOWN,
		-1 => Sms::STATUS_QUEUED,
		0 => Sms::STATUS_QUEUED,
		1 => Sms::STATUS_DELIVERED,
		3 => Sms::STATUS_FAILED,
		20 => Sms::STATUS_FAILED,
		22 => Sms::STATUS_FAILED,
		23 => Sms::STATUS_FAILED,
		24 => Sms::STATUS_FAILED,
		25 => Sms::STATUS_FAILED
	];

	/**
	 * Send sms
	 *
	 * @param $phone
	 * @param $text
	 * @param int $must_sent_at
	 * @param array $options
	 * @return array
	 */
	public function sendSms($phone, $text, $must_sent_at = null, $options = [])
	{
		$result = $this->sendRequest(self::SEND_SMS_URL, array_merge($options, [
			'login' => $this->login,
			'psw' => $this->password,
			'phones' => $phone,
			'mes' => $text,
			'time' => $must_sent_at === null ? 0 : $must_sent_at
		]));

		if (substr_count($result, 'ERROR')) {
			// fail
			return [
				'status' => Sms::STATUS_FAILED,
				'answer' => $result
			];
		} else {
			// success
			$pos = strripos($result, 'ID -');
			$providerSmsId = (int)substr($result, $pos + 5);

			return [
				'status' => Sms::STATUS_SENT,
				'id' => $providerSmsId,
				'answer' => $result
			];
		}
	}

	/**
	 * Get sms status by id
	 *
	 * @param $provider_sms_id
	 * @param array $options
	 * @return array
	 */
	public function getSmsStatus($provider_sms_id, $options = [])
	{
		$result = $this->sendRequest(self::GET_SMS_STATUS_URL, array_merge($options, [
			'login' => $this->login,
			'psw' => $this->password,
			'id' => $provider_sms_id,
		]));

		if (substr_count($result, 'ERROR')) {

			return [
				'status' => Sms::STATUS_UNKNOWN,
				'answer' => $result
			];
		} else {

			$posEnd = strrpos($result, ', check_time =');
			$status = (int)substr($result, 9, $posEnd - 9);
			$status = in_array($status, array_keys($this->statusMap)) ? $this->statusMap[$status] : Sms::STATUS_UNKNOWN;

			return [
				'status' => $status,
				'answer' => $result
			];
		}
	}


	/**
	 * Get account status
	 *
	 * @param array $options
	 * @return bool|float
	 */
	public function getBalance($options = [])
	{
		$result = $this->sendRequest(self::GET_BALANCE_URL, array_merge($options, [
			'login' => $this->login,
			'psw' => $this->password,
		]));

		if (substr_count($result, 'ERROR')) {

			return false;
		}

		return (float)$result;
	}


}
