//
// StrongMonkey
// https://github.com/GramThanos/StrongMonkey
//
// Javascript Example
//

// Don't validate SSL certificate
global.STRONGMONKEY_DEBUG = true

// Include Library
const StrongMonkey = require('./StrongMonkey');

// Print library info
console.log("Using StrongMonkey " + global.STRONGMONKEY_VESION);

// StrongKey FIDO info
let FS_URL = 'https://192.168.56.102:8181';
let FS_DID = 1;
let FS_PROTOCOL = 'REST'; // Only REST is currently supported
// Authentication using HMAC
let FS_AUTH = 'HMAC';
let FS_KEYID = '162a5684336fa6e7';
let FS_KEYSECRET = '7edd81de1baab6ebcc76ebe3e38f41f4';
// or Authentication using Password
//FS_AUTH = 'PASSWORD';
//FS_KEYID = 'svcfidouser';
//FS_KEYSECRET = 'Abcd1234!';

// Initialize
let monkey = new StrongMonkey(FS_URL, FS_DID, FS_PROTOCOL, FS_AUTH, FS_KEYID, FS_KEYSECRET);

// Create a ping request
console.log("-----------------------------------");
process.stdout.write("Ping request ... ");
result = monkey.ping();
error = monkey.getError(result);
if (error) {
    console.log("failed");
    console.log("\t" + error);
    sys.exit(0);
}
console.log("ok");
// Print server info
console.log(result);

// Create a preregister request
console.log("-----------------------------------");
process.stdout.write("Pre-register request ... ");
result = monkey.preregister('gramthanos');
error = monkey.getError(result);
if (error) {
    console.log("failed");
    console.log("\t" + error);
    sys.exit(0);
}
console.log("ok");
console.log(json.dumps(result));

// Create a preauthenticate request
console.log("-----------------------------------");
process.stdout.write("Pre-authenticate request ... ");
result = monkey.preauthenticate('gramthanos');
error = monkey.getError(result);
if (error) {
    console.log("failed");
    console.log("\t" + error);
    sys.exit(0);
}
console.log("ok");
console.log(json.dumps(result));


// Create a getkeysinfo request
console.log("-----------------------------------");
process.stdout.write("Get keys info request ... ");
result = monkey.getkeysinfo('gramthanos');
error = monkey.getError(result);
if (error) {
    console.log("failed");
    console.log("\t" + error);
    sys.exit(0);
}
console.log("ok");
console.log(json.dumps(result));
