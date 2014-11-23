<?php
/**
* Super-simple Elvanto API v1 wrapper
*
* Requires curl
*
* @version 1.0.0
*/

class Elvanto_API {

	const API_ENDPOINT = 'https://api.elvanto.com/v1';
	const OAUTH_URL = 'https://api.elvanto.com/oauth';
	const OAUTH_TOKEN_URL = 'https://api.elvanto.com/oauth/token';

	private $auth_details;

	/**
	* Constructor
	*
	* @param $auth_details array Authentication details to use for API calls.
	*		This array must take one of the following forms:
	*
	*		If using OAuth to authenticate:
	*		array(
	*		 'access_token' => 'your access token',
	*		 'refresh_token' => 'your refresh token')
	*
	*		Or if using an API key:
	*		array('api_key' => 'your api key')
	*
	*		When using authorize_url and exchange_token functions, the
	*		array can be left empty.
	*/
	public function __construct($auth_details = array()) {
		$this->auth_details = $auth_details;
	}

	/**
	* Call a method.
	*
	* @param string $method The name of the method to call.
	* @param array $params The parameters to pass to the method.
	* @return array The response from the API method.
	* @access public
	*/
	public function call($method, $params = array()) {
		return $this->_request($method, $params);
	}

	/**
	* Get the authorization URL for your application.
	*
	* @param $client_id int The Client ID of your registered OAuth application.
	* @param $redirect_uri string The Redirect URI of your registered OAuth application.
	* @param $scope string The comma-separated permission scope your application requires.
	* @param $state string Optional state data to be included in the URL.
	* @return string The authorization URL to which users of your application should be redirected.
	* @access public
	**/
	public function authorize_url($client_id, $redirect_uri, $scope = 'AdministerAccount', $state = false) {
		$qs = 'type=web_server';
		$qs .= '&client_id=' . urlencode($client_id);
		$qs .= '&redirect_uri=' . urlencode($redirect_uri);
		$qs .= '&scope=' . urlencode($scope);
		if ($state)
			$qs .= '&state=' . urlencode($state);
		return self::OAUTH_URL . '?' . $qs;
	}

	/**
	* Exchange a provided OAuth code for an OAuth access token, 'expires in'
	* value and refresh token.
	*
	* @param $client_id int The Client ID of your registered OAuth application.
	* @param $client_secret string The Client Secret of your registered OAuth application.
	* @param $redirect_uri string The Redirect URI of your registered OAuth application.
	* @param $code string The unique OAuth code to be exchanged for an access token.
	* @return A successful response will be an array of the form:
	* 	array(
	*		'access_token' => The access token to use for API calls
	*		'expires_in' => The number of seconds until this access token expires
	*		'refresh_token' => The refresh token to refresh the access token once it expires
	* 	)
	* @access public
	*/
	public function exchange_token($client_id, $client_secret, $redirect_uri, $code = '') {
		$params = array(
			'grant_type' => 'authorization_code',
			'client_id' => $client_id,
			'client_secret' => $client_secret,
			'redirect_uri' => $redirect_uri,
			'code' => $code
		);
		return $this->_request('oauth', $params, self::OAUTH_TOKEN_URL);
	}

	/**
	* Refresh the current OAuth token using the current refresh token.
	*
	* @access public
	*/
	public function refresh_token() {
		if (!isset($this->auth_details['refresh_token']))
			trigger_error('Error refreshing token. There is no refresh token set on this object.', E_USER_ERROR);
		$params = array(
			'grant_type' => 'refresh_token',
			'refresh_token' => $this->auth_details['refresh_token']
		);
		return $this->_request('oauth', $params, self::OAUTH_TOKEN_URL);
	}

	/**
	* Call a method.
	*
	* @param string $method The name of the method to call.
	* @param array $params The parameters to pass to the method.
	* @param string $url The URL to post to.
	* @return array The response from the API method.
	*
	* @access private
	*/
	private function _request($method, $params = array(), $url = self::API_ENDPOINT) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, 'Elvanto_API-PHP/1.0.0');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		// Exchange or refresh a token
		if ($method === 'oauth') {
			$headers = array('Content-Type: application/x-www-form-urlencoded');
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		// Call an API method
		} else {
			$url = self::API_ENDPOINT . '/' . $method . '.json';
			$params['grant_type'] = 'client_credentials';
			$headers = array('Content-Type: application/json; charset=utf-8');
			if (isset($this->auth_details['access_token'])) { // Authenticating using OAuth
				$headers[] = 'Authorization: Bearer ' . $this->auth_details['access_token'];
			} else if (isset($this->auth_details['api_key'])) { // Authenticating using an API key
				curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
				curl_setopt($ch, CURLOPT_USERPWD, $this->auth_details['api_key'] . ':nopass');
			}
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
		}
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($ch);
		if (curl_error($ch)) {
			trigger_error('API call to ' . $url . ' failed: ' . curl_error($ch), E_USER_ERROR);
			return false;
		}
		curl_close($ch);
		return $response ? json_decode($response) : false;
	}

}