<?php

/**
 * FindMyiPhone - A PHP class for interacting with iCloud's Find My iPhone.
 * Copyright (c) 2013 Neal <neal@ineal.me>
 */

class FindMyiPhone {

	private $username;
	private $password;

	public $devices = array();

	private $email_updates = true;

	private $url_host = 'fmipmobile.icloud.com';
	private $scope;

	private $client_context = array(
		'appName' => 'FindMyiPhone',
		'appVersion' => '2.0.2',
		'buildVersion' => '294',
		'clientTimestamp' => 0,
		'deviceUDID' => null,
		'inactiveTime' => 1,
		'osVersion' => '6.1.4',
		'productType' => 'iPhone5,1'
	);

	private $server_context = array(
		'callbackIntervalInMS' => 10000,
		'classicUser' => false,
		'clientId' => null,
		'cloudUser' => true,
		'deviceLoadStatus' => '200',
		'enableMapStats' => false,
		'isHSA' => false,
		'lastSessionExtensionTime' => null,
		'macCount' => 0,
		'maxDeviceLoadTime' => 60000,
		'maxLocatingTime' => 90000,
		'preferredLanguage' => 'en-us',
		'prefsUpdateTime' => 0,
		'sessionLifespan' => 900000,
		'timezone' => null,
		'trackInfoCacheDurationInSecs' => 86400,
		'validRegion' => true
	);


	/**
	 * Constructor
	 * Checks requred extensions, sets username/password and gets url host for the user.
	 * @param $username - iCloud Apple ID
	 * @param $password - iCloud Password
	 */
	public function __construct($username, $password) {
		if (!extension_loaded('curl')) {
			throw new FindMyiPhoneException('PHP extension cURL is not loaded.');
		}

		$this->username = $username;
		$this->password = $password;

		$this->init_client();
	}


	/**
	 * Set email updates
	 * If false, requests will request to not send email to the user. (doesn't work on all requests)
	 * True by default. (optional to set)
	 * $param $email_updates - bool
	 */
	public function set_email_updates($email_updates) {
		$this->email_updates = (bool) $email_updates;
	}


	/**
	 * Init Client
	 * 
	 */
	private function init_client() {
		$post_data = json_encode(array(
			'clientContext' => $this->client_context
		));

		$headers = $this->parse_curl_headers($this->make_request("/fmipservice/device/{$this->username}/initClient", $post_data, array(), true));

		$this->url_host = $headers['X-Apple-MMe-Host'];
		$this->scope = $headers['X-Apple-MMe-Scope'];

		$this->refresh_client();
	}


	/**
	 * Refresh Client
	 * 
	 */
	public function refresh_client() {
		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context
		));

		foreach (json_decode($this->make_request("/fmipservice/device/{$this->scope}/refreshClient", $post_data))->content as $id => $device) {
			$this->devices[$id] = $device;
		}
	}


	/**
	 * Play Sound
	 * 
	 */
	public function play_sound($device_id, $subject = 'Find My iPhone Alert') {
		if(!is_string($device_id)) throw new FindMyiPhoneException('Expected $device_id to be a string');
		if(!is_string($subject)) throw new FindMyiPhoneException('Expected $subject to be a string');

		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context,
			'device' => $device_id,
			'subject' => $subject
		));

		return json_decode($this->make_request("/fmipservice/device/{$this->scope}/playSound", $post_data))->content[0]->msg;
	}


	/**
	 * Send Message
	 * 
	 */
	public function send_message($device_id, $text, $sound = false, $subject = 'Important Message') {
		if(!is_string($device_id)) throw new FindMyiPhoneException('Expected $device_id to be a string');
		if(!is_string($text)) throw new FindMyiPhoneException('Expected $text to be a string');
		if(!is_bool($sound)) throw new FindMyiPhoneException('Expected $sound to be a bool');
		if(!is_string($subject)) throw new FindMyiPhoneException('Expected $subject to be a string');

		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context,
			'device' => $device_id,
			'emailUpdates' => $this->email_updates,
			'sound' => $sound,
			'subject' => $subject,
			'text' => $text,
			'userText' => true
		));

		return json_decode($this->make_request("/fmipservice/device/{$this->scope}/sendMessage", $post_data))->content[0]->msg;
	}


	/**
	 * Lock Device
	 * 
	 */
	public function lost_device($device_id, $passcode, $owner_phone_number = '911', $sound = false, $text = 'This iPhone has been lost. Please call me.') {
		if(!is_string($device_id)) throw new FindMyiPhoneException('Expected $device_id to be a string');
		if(!is_string($passcode)) throw new FindMyiPhoneException('Expected $passcode to be a string');
		if(!is_string($owner_phone_number)) throw new FindMyiPhoneException('Expected $owner_phone_number to be a string');
		if(!is_bool($sound)) throw new FindMyiPhoneException('Expected $sound to be a bool');
		if(!is_string($text)) throw new FindMyiPhoneException('Expected $text to be a string');

		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context,
			'device' => $device_id,
			'emailUpdates' => $this->email_updates,
			'lostModeEnabled' => true,
			'ownerNbr' => $owner_phone_number,
			'passcode' => $passcode,
			'sound' => $sound,
			'text' => $text,
			'trackingEnabled' => true,
			'userText' => true
		));

		return json_decode($this->make_request("/fmipservice/device/{$this->scope}/lostDevice", $post_data))->content[0]->lostDevice;
	}


	/**
	 * Remote Lock
	 * 
	 */
	public function remote_lock($device_id, $passcode) {
		if(!is_string($device_id)) throw new FindMyiPhoneException('Expected $device_id to be a string');
		if(!is_string($passcode)) throw new FindMyiPhoneException('Expected $passcode to be a string');

		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context,
			'device' => $device_id,
			'emailUpdates' => $this->email_updates,
			'passcode' => $passcode
		));

		return json_decode($this->make_request("/fmipservice/device/{$this->scope}/remoteLock", $post_data))->content[0]->remoteLock;
	}


	/**
	 * Remote Wipe
	 * 
	 */
	public function remote_wipe($device_id) {
		if(!is_string($device_id)) throw new FindMyiPhoneException('Expected $device_id to be a string');

		$post_data = json_encode(array(
			'clientContext' => $this->client_context,
			'serverContext' => $this->server_context,
			'device' => $device_id,
			'emailUpdates' => $this->email_updates
		));

		return json_decode($this->make_request("/fmipservice/device/{$this->scope}/remoteWipe", $post_data))->content[0]->remoteWipe;
	}


	/**
	 * Locate Device
	 * 
	 */
	public function locate_device($device, $timeout = 120) {
		if(!is_integer($device)) throw new FindMyiPhoneException('Expected $device to be an integer');

		$start = time();
		while (!$this->devices[$device]->location->locationFinished) {
			if ((time() - $start) > intval($timeout)) {
				throw new FindMyiPhoneException('Failed to locate device! Request timed out.');
			}
			sleep(5);
			$this->refresh_client();
		}

		return $this->devices[$device]->location;
	}


	/**
	 * Make request to the Find My iPhone server.
	 * @param $url - the url endpoint
	 * @param $post_data - the POST data
	 * @param $headers - optional headers to send
	 * @param $return_headers - also return headers when true
	 * @return HTTP response
	 */
	private function make_request($url, $post_data, $headers = array(), $return_headers = false) {
		if(!is_string($url)) throw new FindMyiPhoneException('Expected $url to be a string');
		if(!$this->is_json($post_data)) throw new FindMyiPhoneException('Expected $post_data to be json');
		if(!is_array($headers)) throw new FindMyiPhoneException('Expected $headers to be an array');
		if(!is_bool($return_headers)) throw new FindMyiPhoneException('Expected $return_headers to be a bool');

		array_push($headers, 'Accept-Language: en-us');
		array_push($headers, 'Content-Type: application/json; charset=utf-8');
		array_push($headers, 'X-Apple-Realm-Support: 1.0');
		array_push($headers, 'X-Apple-Find-Api-Ver: 3.0');
		array_push($headers, 'X-Apple-Authscheme: UserIdGuest');

		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_TIMEOUT => 9,
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_AUTOREFERER => true,
			CURLOPT_VERBOSE => false,
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HEADER => $return_headers,
			CURLOPT_URL => 'https://' . $this->url_host . $url,
			CURLOPT_USERPWD => $this->username . ':' . $this->password,
			CURLOPT_USERAGENT => 'FindMyiPhone/294 CFNetwork/655 Darwin/14.0.0'
		));

		$http_result = curl_exec($curl);
		curl_close($curl);

		return $http_result;
	}


	/**
	 * Parse cURL headers
	 * @param $response - cURL response including the headers
	 * @return array of headers
	 */
	private function parse_curl_headers($response) {
		$headers = array();
		foreach (explode("\r\n", substr($response, 0, strpos($response, "\r\n\r\n"))) as $i => $line) {
			if ($i === 0) {
				$headers['http_code'] = $line;
			} else {
				list($key, $value) = explode(': ', $line);
				$headers[$key] = $value;
			}
		}
		return $headers;
	}

	/**
	 * Finds whether a variable is json.
	 */
	private function is_json($var) {
		json_decode($var);
		return (json_last_error() == JSON_ERROR_NONE);
	}
}

class FindMyiPhoneException extends Exception {}

?>
