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
 * Class IqmscRuService
 * @link http://iqsms.ru/api/api_rest/
 * @package lowbase\sms
 */
class IqmscRuService extends AbstractService
{
	const SEND_SMS_URL = 'http://gate.iqsms.ru/send/';
	const GET_SMS_STATUS_URL = 'http://gate.iqsms.ru/status/';
	const GET_BALANCE_URL = 'http://api.iqsms.ru/messages/v2/balance/';

	public $statusMap = [
		'queued' => Sms::STATUS_QUEUED,
		'smsc submit' => Sms::STATUS_QUEUED,
		'delivered' => Sms::STATUS_DELIVERED,
		'delivery error' => Sms::STATUS_FAILED,
		'smsc reject' => Sms::STATUS_FAILED,
		'incorrect id' => Sms::STATUS_UNKNOWN,
	];

	/**
	 * Send sms
	 *
	 * @param $phone
	 * @param $text
	 * @param null $must_sent_at
	 * @param array $options
	 * @return array
	 */
	public function sendSms($phone, $text, $must_sent_at = null, $options = [])
	{
		if ($must_sent_at) {
			$options['scheduleTime'] = $must_sent_at;
		}
		$result = $this->sendRequest(self::SEND_SMS_URL, array_merge($options, [
			'login' => $this->login,
			'password' => $this->password,
			'phone' => $phone,
			'text' => $text
		]));

		if (substr_count($result, '=accepted')) {
			//success
			return [
				'status' => Sms::STATUS_SENT,
				'id' => explode('=', $result)[0],
				'answer' => $result
			];

		} else {

			return [
				'status' => Sms::STATUS_FAILED,
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
			'password' => $this->password,
			'id' => $provider_sms_id,
		]));

		if (substr_count($result, '=')) {
			$status = explode('=', $result)[1];
			$status = in_array($status, array_keys($this->statusMap)) ? $this->statusMap[$status] : Sms::STATUS_UNKNOWN;

			return [
				'status' => $status,
				'answer' => $result
			];

		} else {

			return [
				'status' => Sms::STATUS_UNKNOWN,
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
			'password' => $this->password,
		]));

		if (substr_count($result, 'RUB')) {
			$balance = explode(';', $result);
			if (isset($balance[1])) {
				return (float)$balance[1];
			}
		}

		return false;
	}

}
