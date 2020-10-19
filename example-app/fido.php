<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page FIDO API
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');
// Session
include(dirname(__FILE__) . '/includes/session.php');
// Auth
include(dirname(__FILE__) . '/includes/auth.php');
// StrongMonkey
include(STRONGMONKEY_LIB);

// Response with JSON
function json_response ($json, $code=200) {
	http_response_code($code);
	header('Content-Type: application/json');
	exit(json_encode($json));
}

// Get action
$action = 'invalid';
if (isset($_GET['action'])) {
	$action = $_GET['action'];
} else if (isset($_GET['authenticate'])) {
	$action = 'authenticate';
} else if (isset($_GET['register'])) {
	$action = 'register';
} else if (isset($_GET['info'])) {
	$action = 'info';
}

/* Authenticate User
 *************************/
if ($action === 'authenticate') {
	// If not logged in
	if (session_isLoggedIn()) {
		json_response(array(
			'error' => 'Already logged in.',
		), 400);
	}

	// If this was a post
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		# If user id, then create a challenge
		if (isset($_POST['userid'])) {
			// Try to find the user and authenticate him
			$user = null;
			// In general, we should be able to do requests with no username
			// but the StrongKey FIDO2 server seems not to support it
			if (strlen($_POST['userid']) === 0) {
				json_response(array(
					'error' => 'No username was given.',
				), 404);
			}
			if (strlen($_POST['userid']) > 0) {
				if (strpos($_POST['userid'], '@') !== false) {
					$user = auth_getUserByEmail($_POST['userid']);
				}
				else {
					$user = auth_getUserByUsername($_POST['userid']);
				}
				// If user was not found
				if (!$user) {
					json_response(array(
						'error' => 'Account was not found.',
					), 404);
				}
			}
			// Credential Authentication - Initiation phase
			$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
			$reply = $user ? $smk->preauthenticate($user['username']) : $smk->preauthenticate(); // Maybe in the future resident keys are supported (this is why we have the second call without a $username)
			if ($smk->getError($reply)) {
				json_response(array(
					'error' => 'Challenge creating failed.',
				), 404);
			}
			session_challenge_set($user, $reply->Response->challenge);
			// Send back challenge
			json_response(array(
				'success' => 'Challenge started.',
				'options' => $reply->Response
			), 200);
		}

		// If challenge
		else if (isset($_POST['publicKeyCredential'])) {
			$authenticator_response = $_POST['publicKeyCredential'];
			$clientDataJSON = (isset($authenticator_response['response']) && isset($authenticator_response['response']['clientDataJSON'])) ? json_decode(base64_decode($authenticator_response['response']['clientDataJSON'])) : false;
			if (!$clientDataJSON || !$user = session_challenge_get($clientDataJSON->challenge)) {
				json_response(array(
					'error' => 'Invalid request.',
				), 404);
			}

			// Get user
			$user = auth_getUserByUsername($user);
			if (!$user) {
				json_response(array(
					'error' => 'Invalid request.',
				), 404);
			}

			// Authenticate user
			$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
			$reply = $smk->authenticate($authenticator_response, array(
				'version' => '1.0',
				'last_used_location' => 'webapp',
				'username' => $user['username'],
				'origin' => 'https://' . $_SERVER['HTTP_HOST']
			));
			if ($smk->getError($reply)) {
				session_logOut();
				json_response(array(
					'error' => 'Authentication failed.',
				), 404);
			}
			// Log user in
			session_logIn($user);
			// Send back challenge
			json_response(array(
				'success' => 'Successful login.'
			), 200);
		}
	}
}

/* Register Authenticators
 *************************/
if ($action === 'register') {
	// If not logged in
	if (!session_isLoggedIn()) {
		json_response(array(
			'error' => 'User is not logged in.',
		), 404);
	}

	// If this was a post
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// If no credentials
		if (!isset($_POST['publicKeyCredential'])) {
			// Credential Registration - Initiation phase
			$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
			$reply = $smk->preregister(session_get_userUsername());
			if ($smk->getError($reply)) {
				json_response(array(
					'error' => 'Failed to create challenge.'
				), 404);
			}
			// Save challenge code to session
			session_challenge_set(false, $reply->Response->challenge);
			// Set rp info
			$reply->Response->rp->id = $_SERVER['HTTP_HOST'];
			$reply->Response->rp->name = 'StrongMonkey';
			// Send back challenge
			json_response(array(
				'success' => 'Challenge started.',
				'options' => $reply->Response
			), 200);
		}
		// If challenge
		else if (isset($_POST['publicKeyCredential'])) {
			// Credential Registration - Verification phase
			$authenticator_response = $_POST['publicKeyCredential'];
			$clientDataJSON = (isset($authenticator_response['response']) && isset($authenticator_response['response']['clientDataJSON'])) ? json_decode(base64_decode($authenticator_response['response']['clientDataJSON'])) : false;
			if (!$clientDataJSON || !session_challenge_get($clientDataJSON->challenge)) {
				json_response(array(
					'error' => 'Authentication error.',
				), 404);
			}
			$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
			$reply = $smk->register($authenticator_response, array(
				'version' => '1.0',
				'create_location' => 'webapp',
				'username' => session_get_userUsername(),
				'origin' => 'https://' . $_SERVER['HTTP_HOST']
			));
			if ($smk->getError($reply)) {
				var_dump($smk->getError($reply));
				json_response(array(
					'error' => 'Failed to register authenticator.',
				), 404);
			}
			// Response message
			json_response(array(
				'success' => 'Successful authenticator registration.'
			), 200);
		}
	}
}

/* Get Authenticators Keys
 *************************/
if ($action === 'info') {
	// If not logged in
	if (!session_isLoggedIn()) {
		json_response(array(
			'error' => 'User is not logged in.',
		), 404);
	}

	// If this was a get
	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		// Get keys
		$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
		$reply = $smk->getkeysinfo(session_get_userUsername());
		if ($smk->getError($reply)) {
			json_response(array(
				'error' => 'Failed to retrieve credentials.',
			), 404);
		}
		// Response message
		json_response(array(
			'success' => 'Keys list',
			'keys' => $reply->Response->keys
		), 200);
	}

	// If this was a post
	else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Check data
		if (isset($_POST['status']) && isset($_POST['location']) && isset($_POST['displayname']) && isset($_POST['keyid'])) {
			// Get keys
			$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);
			$reply = $smk->updatekeyinfo($_POST['status'], $_POST['location'], $_POST['displayname'], $_POST['keyid']);
			if ($smk->getError($reply)) {
				json_response(array(
					'error' => 'Failed to update credentials.',
				), 404);
			}
			// Response message
			json_response(array(
				'success' => 'Credentials were updated.'
			), 200);
		}
	}
}

/* Invalid Request
 *************************/
// Can't handle request
json_response(array(
	'error' => 'Invalid request.',
), 404);
