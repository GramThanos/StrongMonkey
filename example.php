<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Example
 */

// Don't validate SSL certificate
define('STRONGMONKEY_DEBUG', true);
// Include Library
include('StrongMonkey.php');

// Print library info
print("Using StrongMonkey " . STRONGMONKEY_VESION . "\n");

// StrongKey FIDO info
define('FS_URL', 'https://192.168.56.102:8181');
define('FS_DID', 1);
define('FS_PROTOCOL', 'REST'); // Only REST is currently supported
// Authentication using HMAC
define('FS_AUTH', 'HMAC');
define('FS_KEYID', '162a5684336fa6e7');
define('FS_KEYSECRET', '7edd81de1baab6ebcc76ebe3e38f41f4');
// or Authentication using Password
//define('FS_AUTH', 'PASSWORD');
//define('FS_KEYID', 'svcfidouser');
//define('FS_KEYSECRET', 'Abcd1234!');

// Initialize
$monkey = new StrongMonkey(FS_URL, FS_DID, FS_PROTOCOL, FS_AUTH, FS_KEYID, FS_KEYSECRET);

// Create a ping request
print("-----------------------------------\n");
print("Ping request ... ");
$result = $monkey->ping();
if ($error = $monkey->getError($result)) {
	print("failed\n");
	die("\t" . $error . "\n");
}
print("ok\n");
// Print server info
print($result);

// Create a preregister request
print("-----------------------------------\n");
print("Pre-register request ... ");
$result = $monkey->preregister('gramthanos');
if ($error = $monkey->getError($result)) {
	print("failed\n");
	die("\t" . $error . "\n");
}
print("ok\n");
print(json_encode($result, JSON_UNESCAPED_SLASHES) . "\n");


// Create a preauthenticate request
print("-----------------------------------\n");
print("Pre-authenticate request ... ");
$result = $monkey->preauthenticate('gramthanos', array(
	"authenticatorSelection" => array(
		"requireResidentKey" => true
	)
));
if ($error = $monkey->getError($result)) {
	print("failed\n");
	die("\t" . $error . "\n");
}
print("ok\n");
print(json_encode($result, JSON_UNESCAPED_SLASHES) . "\n");


// Create a getkeysinfo request
print("-----------------------------------\n");
print("Get keys info request ... ");
$result = $monkey->getkeysinfo('gramthanos');
if ($error = $monkey->getError($result)) {
	print("failed\n");
	die("\t" . $error . "\n");
}
print("ok\n");
print(json_encode($result, JSON_UNESCAPED_SLASHES) . "\n");


