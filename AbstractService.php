<?php
/**
 * @package   yii2-sms
 * @author    Yuri Shekhovtsov <shekhovtsovy@yandex.ru>
 * @copyright Copyright &copy; Yuri Shekhovtsov, lowbase.ru, 2016
 * @version   1.0.0
 */

namespace lowbase\sms;

use yii\base\Object;

/**
 * Class AbstractService
 * @package lowbase\sms
 */
abstract class AbstractService extends Object
{
	public $login;
	public $password;
	
	/**
	 * Send Request
	 * 
	 * @param $request
	 * @param null $options
	 * @return mixed
	 */
	static public function sendRequest($request, $options = null) {
		mb_internal_encoding("UTF-8");
		$ch = curl_init();
		$string = $request;
		curl_setopt($ch, CURLOPT_URL, $string);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $options);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	/**
	 * Send sms
	 *
	 * @param $phone
	 * @param $text
	 * @param null $must_sent_at
	 * @param array $options
	 * @return mixed
	 */
	abstract public function sendSms($phone, $text, $must_sent_at = null, $options = []);

	/**
	 * Get sms status by id
	 *
	 * @param $provider_sms_id
	 * @param array $options
	 * @return mixed
	 */
	abstract public function getSmsStatus($provider_sms_id, $options = []);


	/**
	 * Get account status
	 *
	 * @param array $options
	 * @return mixed
	 */
	abstract public function getBalance($options = []);
}