<?php
/**
 * StrongMonkey v0.0.3-beta
 * PHP library for interacting with StrongKey FIDO Server
 * Copyright (c) 2020 Grammatopoulos Athanasios-Vasileios
 */

define('STRONGMONKEY_VESION', 'v0.0.3-beta');
if (!defined('STRONGMONKEY_DEBUG')) define('STRONGMONKEY_DEBUG', false);
if (!defined('STRONGMONKEY_CONNECTTIMEOUT')) define('STRONGMONKEY_CONNECTTIMEOUT', 10);
if (!defined('STRONGMONKEY_TIMEOUT')) define('STRONGMONKEY_TIMEOUT', 30);
if (!defined('STRONGMONKEY_USERAGENT')) define('STRONGMONKEY_USERAGENT', 'StrongMonkey-Agent' . '/' . STRONGMONKEY_VESION);

class StrongMonkey {

	// Static variables
	private static $api_protocol = 'FIDO2_0';
	private static $api_version = 'SK3_0';
	private static $api_url_base = '/skfs/rest';
	private static $version = STRONGMONKEY_VESION;
	private static $useragent = STRONGMONKEY_USERAGENT;

	// ERRORS
	private static $PARSE_ERROR = 1001;
	private static $SUBMIT_ERROR = 1002;
	private static $AUTHENTICATION_FAILED = 1003;
	private static $RESOURCE_UNAVAILABLE = 1004;
	private static $UNEXPECTED_ERROR = 1005;
	private static $UNUSED_ROUTES = 1006;
	private static $UNKNOWN_ERROR = 1007;

	// Authorization Methods
	private static $AUTHORIZATION_HMAC = 'HMAC';
	private static $AUTHORIZATION_PASSWORD = 'PASSWORD';
	// Protocol Methods
	private static $PROTOCOL_REST = 'REST';

	// Private variables
	private $hostport;
	private $did;
	private $wsprotocol;
	private $authtype;
	private $keyid;
	private $keysecret;

	/**
	 * Create a StrongMonkey object which can later be used to communicate with the StrongKey FIDO2 Server
	 * @param string  $hostport   Host and port to access the FIDO SOAP and REST formats
	 *                               http://<FQDN>:<non-ssl-portnumber> or
	 *                               https://<FQDN>:<ssl-portnumber>
	 * @param integer $did        Domain ID
	 * @param string  $wsprotocol Web socket protocol; REST or SOAP
	 * @param string  $authtype   Authorization type; HMAC or PASSWORD
	 * @param string  $id         PublicKey or Username (Keys should be in hex)
	 * @param string  $secret     SecretKey or Password (Keys should be in hex)
	 */
	function __construct ($hostport, $did, $protocol, $authtype, $keyid, $keysecret) {
		// TODO: Test inputs? No?
		// Save information
		$this->hostport = $hostport;
		$this->did = $did;
		$this->protocol = $protocol;
		$this->authtype = $authtype;
		$this->keyid = $keyid;
		$this->keysecret = $keysecret;

		// Check if not supported
		if ($authtype != StrongMonkey::$AUTHORIZATION_HMAC && $authtype != StrongMonkey::$AUTHORIZATION_PASSWORD) {
			die('The provided authorization method is not supported');
		}
		if ($protocol != StrongMonkey::$PROTOCOL_REST) {
			die('The provided protocol is not supported');
		}
	}

	/**
	 * Initialize a key registration challenge with the FIDO server
	 * @param  string       $username    Username of the user
	 * @param  string       $displayname Display name for the user
	 * @param  array|string $options     Object of options
	 * @param  array|string $extensions  Object of extensions
	 * @return integer|array
	 */
	public function preregister ($username, $displayname=null, $options=null, $extensions=null) {
		// Init paramters
		if (is_null($displayname)) $displayname = $username;
		$options = $this->jsonStringPrepare($options, new stdClass);
		$extensions = $this->jsonStringPrepare($extensions, new stdClass);

		// Create data
		$payload = array(
			'username' => $username,
			'displayname' => $displayname,
			'options' => $options,
			'extensions' => $extensions
		);

		// Make preregister request
		return $this->request($payload, '/preregister');
	}

	/**
	 * Send register response to the FIDO server
	 * @param  array|string $response Response data from the authenticator
	 * @param  array|string $metadata Additional meta data
	 * @return integer|array
	 */
	public function register ($response, $metadata=null) {
		// Init empty paramters
		$response = $this->jsonStringPrepare($response);
		$metadata = $this->jsonStringPrepare($metadata, new stdClass);

		// Create data
		$payload = array(
			'response' => $response,
			'metadata' => $metadata
		);

		// Make register request
		return $this->request($payload, '/register');
	}

	/**
	 * Initialize a key authentication challenge with the FIDO server
	 * @param  string       $username    Username of the user
	 * @param  array|string $options     Object of options
	 * @param  array|string $extensions  Object of extensions
	 * @return integer|array
	 */
	public function preauthenticate ($username=null, $options=null, $extensions=null) {
		// Init empty paramters
		//if (is_null($username)) {
		//	if (is_null($options)) $options = array();
		//	$options['Residentkey'] = 'req';
		//}
		$options = $this->jsonStringPrepare($options, new stdClass);
		$extensions = $this->jsonStringPrepare($extensions, new stdClass);

		// Create data
		$payload = array(
			'username' => $username,
			'options' => $options,
			'extensions' => $extensions
		);

		// Make preauthenticate request
		return $this->request($payload, '/preauthenticate');
	}

	/**
	 * Send authenticate response to the FIDO server
	 * @param  array|string $response Response data from the authenticator
	 * @param  array|string $metadata Additional meta data
	 * @return integer|array
	 */
	public function authenticate ($response, $metadata=null) {
		// Init empty paramters
		$response = $this->jsonStringPrepare($response);
		$metadata = $this->jsonStringPrepare($metadata, new stdClass);

		// Create data
		$payload = array(
			'response' => $response,
			'metadata' => $metadata
		);

		// Make authenticate request
		return $this->request($payload, '/authenticate');
	}

	/**
	 * Update key information
	 * @param  string $status          The status of the key (Active, Inactive)
	 * @param  string $modify_location Modify location
	 * @param  string $displayname     Display name of the key
	 * @param  string $keyid           Id of the key to change
	 * @return integer|array
	 */
	public function updatekeyinfo ($status, $modify_location, $displayname, $keyid) {
		// Create data
		$payload = array(
			"status" => $status,
			"modify_location" => $modify_location,
			"displayname" => $displayname,
			"keyid" => $keyid
		);

		// Make updatekeyinfo request
		return $this->request($payload, '/updatekeyinfo');
	}

	/**
	 * Get user's keys information from the FIDO server
	 * @param  string $username Username of the user
	 * @return integer|array
	 */
	public function getkeysinfo ($username) {
		// Create data
		$payload = array(
			"username" => $username
		);

		// Make getkeysinfo request
		return $this->request($payload, '/getkeysinfo');
	}

	/**
	 * Delete user's key information from the FIDO server
	 * @param  string $keyid Id of the key to deregister
	 * @return integer|array
	 */
	public function deregister ($keyid) {
		// Create data
		$payload = array(
			"keyid" => $keyid
		);

		// Make deregister request
		return $this->request($payload, '/deregister');
	}

	/**
	 * Send a ping to the FIDO server
	 * @return boolean|string
	 */
	public function ping () {
		// Make ping request
		$response = $this->request(null, '/ping', false);
		// If no error
		if ($response['code'] === 200) {
			return $response['body'];
		}
		// Return error code
		return $this->parseResponse($response['code'], $response['body']);
	}

	/**
	 * Create a request to the FIDO server
	 * @param  array   $payload     Payload to send
	 * @param  string  $action_path API path for the action
	 * @param  boolean $parse       Automatically parse response
	 * @return integer|array
	 */
	public function request ($payload, $action_path, $parse=true) {
		// Create data
		$body = array(
			"svcinfo" => array(
				"did" => $this->did,
				"protocol" => StrongMonkey::$api_protocol,
				"authtype" => $this->authtype
			)
		);
		// Prepare payload
		if (!is_null($payload)) {
			$body['payload'] = $payload;
		}

		// Generate path
		$path = StrongMonkey::$api_url_base . $action_path;

		// Prepare Request Headers
		$headers = array(
			'Accept: application/json',
			'Content-Type: application/json',
			'User-Agent: ' . StrongMonkey::$useragent
		);

		// HMAC
		if ($this->authtype === StrongMonkey::$AUTHORIZATION_HMAC) {
			// Get date
			$date = date('D, j M Y H:i:s e');

			// Prepare hashes
			$payload_hash = '';
			$mimetype = '';
			if (!is_null($payload)) {
				$payload_string = json_encode($body['payload'], JSON_UNESCAPED_SLASHES);
				$payload_hash = base64_encode(hex2bin(hash('sha256', $payload_string)));
				$mimetype = 'application/json';
			}
			
			// Generate HMAC authentication
			$authentication_hash = $this->generateHMAC('POST', $payload_hash, $mimetype, $date, $path);
			
			// Add authorization Headers
			$headers[] = 'strongkey-content-sha256: ' . $payload_hash;
			$headers[] = 'date: ' . $date;
			$headers[] = 'strongkey-api-version: ' . StrongMonkey::$api_version;
			$headers[] = 'Authorization: ' . $authentication_hash;
		}
		// Credentials
		else {
			$body['svcinfo']['svcusername'] = $this->keyid;
			$body['svcinfo']['svcpassword'] = $this->keysecret;
		}

		// Create request
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->hostport . $path);
		if (STRONGMONKEY_DEBUG) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		} else {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		}
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, STRONGMONKEY_CONNECTTIMEOUT); 
		curl_setopt($ch, CURLOPT_TIMEOUT, STRONGMONKEY_TIMEOUT);
		$response = curl_exec($ch);
		$response_code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		curl_close($ch);

		if ($parse) {
			return $this->parseResponse($response_code, $response);
		}
		else {
			return array(
				'code' => $response_code,
				'body' => $response
			);
		}
	}

	/**
	 * Parse response from the FIDO server
	 * @param  integer $code     HTTP code returned
	 * @param  string  $response Response body
	 * @return integer|array
	 */
	private function parseResponse ($code, $response) {
		// 200: Success
		if ($code === 200) {
			$response = json_decode($response);
			if ($response) {
				return $response;
			}
			return StrongMonkey::$PARSE_ERROR;
		}
		// 400: There was an error in the submitted input.
		if ($code === 400) {
			return StrongMonkey::$SUBMIT_ERROR;
		}
		// 401: The authentication failed.
		if ($code === 401) {
			return StrongMonkey::$AUTHENTICATION_FAILED;
		}
		// 404: The requested resource is unavailable.
		if ($code === 404) {
			return StrongMonkey::$RESOURCE_UNAVAILABLE;
		}
		// 500: The server ran into an unexpected exception.
		if ($code === 500) {
			return StrongMonkey::$UNEXPECTED_ERROR;
		}
		// 501: Unused routes return a 501 exception with an error message.
		if ($code === 501) {
			return StrongMonkey::$UNUSED_ROUTES;
		}
		return StrongMonkey::$UNKNOWN_ERROR;
	}

	/**
	 * Check and get error string if any
	 * @param  mixed $error Response returned from an action
	 * @return boolean|string
	 */
	public function getError ($error) {
		// If not error
		if (!is_numeric($error)) {
			return false;
		}
		// Resolve error code
		switch ($error) {
			case StrongMonkey::$PARSE_ERROR:
				return 'StrongMonkey: Response parse error.';
			case StrongMonkey::$SUBMIT_ERROR:
				return 'StrongMonkey: There was an error in the submitted input.';
			case StrongMonkey::$AUTHENTICATION_FAILED:
				return 'StrongMonkey: The authentication failed.';
			case StrongMonkey::$RESOURCE_UNAVAILABLE:
				return 'StrongMonkey: The requested resource is unavailable.';
			case StrongMonkey::$UNEXPECTED_ERROR:
				return 'StrongMonkey: The server ran into an unexpected exception.';
			case StrongMonkey::$UNKNOWN_ERROR:
				return 'StrongMonkey: Unused routes return a 501 exception with an error message.';
			default:
				return 'StrongMonkey: Unknown error code.';
		}
	}

	/**
	 * Generate HMAC authentication header value
	 * @param  string $method   Request method to be used
	 * @param  string $payload  Payload Hash to be used
	 * @param  string $mimetype Mime-type to be used
	 * @param  string $datestr  Date string to be used
	 * @param  string $path     Path to be used
	 * @return string
	 */
	private function generateHMAC ($method, $payload, $mimetype, $datestr, $path) {
		// Assembly hash message
		$message = array(
			$method,
			$payload,
			$mimetype,
			$datestr,
			StrongMonkey::$api_version,
			$path
		);
		// Generate HMAC
		$digest = hash_hmac('sha256', implode("\n", $message), pack('H*', $this->keysecret));
		// Return header
		return 'HMAC ' . $this->keyid . ':' . base64_encode(pack('H*', $digest));
	}

	/**
	 * Convert JSON to string
	 * @param  array|string $json   JSON value to be converted
	 * @param  array|string $ifnull Default falue if value is null
	 * @return string
	 */
	private function jsonStringPrepare ($json, $ifnull=null) {
		if ($json === null && $ifnull !== null) {
			$json = $ifnull;
		}
		if (is_string($json)) {
			return $json;
		}
		return json_encode($json, JSON_UNESCAPED_SLASHES);
	}

}