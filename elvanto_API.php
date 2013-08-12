<?php

/**
* Super-simple elvanto API v1 wrapper
*
* Requires curl
*
* @version 1.0
*/
class elvanto_API {

	private $api_key;
	private $api_endpoint = 'https://api.elvanto.com/v1/';

	/**
	* Create a new instance
	* @param string $api_key Your elvanto API key
	*/
	function __construct($api_key) {
		$this->api_key = $api_key;
	}

	/**
	* Call an API method. Every request needs the API key, so that is added automatically.
	* @param string $method The API method to call.
	* @param array $args An array of parameters to pass to the method.
	* @return array Associative array of unserialized API response.
	*/
	public function call($method, $args = array()) {

		$args['apikey'] = $this->api_key;

		$url = $this->api_endpoint . '?output=php&method=' . $method;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, count($args));
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($args));
		$result = curl_exec($ch);
		curl_close($ch);

		if (ini_get('magic_quotes_runtime'))
			$result = stripslashes($result);

		return $result ? unserialize($result) : false;

	}

}

?>