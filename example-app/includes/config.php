<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Config
 */

// App Info
define('APP_NAME', 'StrongMonkey');
define('APP_DESCRIPTION', 'PHP library for interacting with StrongKey FIDO Server');
define('APP_VERSION', 'v0.0.4-beta');
define('APP_WEBSITE', 'https://github.com/GramThanos/StrongMonkey');
define('APP_DEBUG', true);
define('APP_ROOT_PATH', dirname(__DIR__));

// Session Info
define('APP_SESSION_NAME', 'STRONGMONKEY_SESSION');
define('APP_SESSION_LIFETIME', 6 * 60 * 60);
define('APP_SESSION_LIFETIME_REMEMBER', 30 * 24 * 60 * 60);
define('APP_SESSION_AUTO_UPDATE', true);
define('APP_SESSION_AUTO_UPDATE_INTERVAL', 60);
define('APP_SESSION_AUTO_REGENERATE', true);
define('APP_SESSION_AUTO_REGENERATE_INTERVAL', 60);

// Database
define('APP_DATABASE_HOST', 'localhost');
define('APP_DATABASE_USER', 'app_user');
define('APP_DATABASE_PASSWORD', 'app_user_pass');
define('APP_DATABASE_NAME', 'strongmonkey_app_db');

// StrongKey FIDO info
define('APP_FIDO_URL', 'https://192.168.56.102:8181');
define('APP_FIDO_DID', 1);
define('APP_FIDO_PROTOCOL', 'REST');

define('APP_FIDO_AUTH', 'HMAC');
define('APP_FIDO_KEYID', '162a5684336fa6e7');
define('APP_FIDO_KEYSECRET', '7edd81de1baab6ebcc76ebe3e38f41f4');
//define('APP_FIDO_AUTH', 'PASSWORD');
//define('APP_FIDO_KEYID', 'svcfidouser');
//define('APP_FIDO_KEYSECRET', 'Abcd1234!');

// Comment this if your StrongMonkey server have a valid SSL certificate
define('STRONGMONKEY_DEBUG', true); // In debug mode the SSL certificate are not validated
// Path for StrongMonkey library
define('STRONGMONKEY_LIB', dirname(__FILE__) . '/../../' . 'StrongMonkey.php');

// For debugging
if (APP_DEBUG) {
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);
}
