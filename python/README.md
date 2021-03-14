# StrongMonkey

Python SDK for interacting with FIDO2 Server API v3.0.0

![strongmonkey-banner](../strongmonkey.png)

---

## Example usage

Download the [StrongMonkey](StrongMonkey.py) Python SDK library and start by checking the status of your FIDO2 server.

```python
# Include Library
import StrongMonkey
# Don't validate SSL certificate
StrongMonkey.STRONGMONKEY_DEBUG = True # Only for development
# Specify the FIDO server's URL and the authentication method to be used
monkey = StrongMonkey.StrongMonkey('https://localhost:8181', 1, 'REST', 'HMAC', '162a5684336fa6e7', '7edd81de1baab6ebcc76ebe3e38f41f4')
# Send a ping request to the server
result = monkey.ping()
# If there is an error print it
error = monkey.getError(result)
if (error):
    print(error)
else:
    # Print server info
    print(result)
```

A simple example with the first relying party calls can be found at [example.py](example.py)

---

## Python SDK API

 - [StrongMonkey Object](#strongmonkey-object) - `monkey = StrongMonkey.StrongMonkey( ... )`;
 - [PreRegister Method](#preregister) - `monkey.preregister( ... )`;
 - [Register Method](#preregister) - `monkey.register( ... )`;
 - [PreAuthenticate Method](#preauthenticate) - `monkey.preauthenticate( ... )`;
 - [Authenticate Method](#authenticate) - `monkey.authenticate( ... )`;
 - [Update Key Info Method](#update-key-info) - `monkey.updatekeyinfo( ... )`;
 - [GetKeyInfo Method](#get-key-info) - `monkey.getkeysinfo( ... )`;
 - [DeRegister Method](#deregister) - `monkey.deregister( ... )`;
 - [Ping Method](#ping) - `.monkey.ping()`;
 - [JavaScript functions](#javascript-functions)


### StrongMonkey Object
Create a StrongMonkey object which can later be used to communicate with the FIDO2 Server

```python
StrongMonkey.StrongMonkey(str hostport, int did, str protocol, str authtype, str keyid, str keysecret) : StrongMonkey.StrongMonkey
```

- hostport
	- Host and port to access the FIDO SOAP and REST formats
		- `'http://<FQDN>:<non-ssl-portnumber>'` or
		- `'https://<FQDN>:<ssl-portnumber>'`
- did
	- Domain ID e.g. `1`
- protocol
	- Web socket protocol; `'REST'` or `'SOAP'` (only REST is supported)
- authtype
	- Authorization type; `'HMAC'` or `'PASSWORD'`
- keyid
	- PublicKey or Username (keys should be in hex)
- keysecret
	- SecretKey or Password (keys should be in hex)

Example call using HMAC authentication:
```python
# Prepare Object
monkey = StrongMonkey.StrongMonkey(
    'https://fido2.strongkey.unipi.gr:8181', # URL of your FIDO2 server
    1,                                       # Domain ID (usually 1)
    'REST',                                  # Protocol ti use (currently always REST)
    'HMAC',                                  # Authentication method to use
    '162a5684336fa6e7',                      # Default key ID (for HMAC)
    '7edd81de1baab6ebcc76ebe3e38f41f4'       # Default secret key (for HMAC)
)
```

Example call using PASSWORD authentication:
```python
# Prepare Object
monkey = StrongMonkey.StrongMonkey(
    'https://fido2.strongkey.unipi.gr:8181', # URL of your FIDO2 server
    1,                                       # Domain ID (usually 1)
    'REST',                                  # Protocol ti use (currently always REST)
    'PASSWORD',                              # Authentication method to use
    'svcfidouser',                           # Default username (for PASSWORD)
    'Abcd1234!'                              # Default password (for PASSWORD)
)
```

### Preregister
Initialize a key registration challenge with the FIDO server.

```python
monkey.preregister(str username[, str displayname = None[, dict|str options=None[, dict|str extensions=None]]]) : int|dict
```

- username
	- Username of the user
	- Based on the WebAuthn standard:
		- Human-palatable identifier for the user account, intended only for display, helping distinguish form other user
		- The relay party MAY let the user choose this value
		- ex. `john.smith@email.com` (email) or `+306901234567` (telephone)
- displayname
	- Display name for the user
	- Based on the WebAuthn standard:
		- Human-palatable name for the user account, intended only for display
		- The relay party SHOULD let the user choose this value
		- ex. `J. Smith` (full name)
- options
	- Object of options
	- *Options support depend on your FIDO2 server*
- extensions
	- Object of extensions
	- *Extensions support depend on your FIDO2 server*

Example call and response forward to front-end
```python
# Request to start a key registration
response = monkey.preregister(username)
# Check for errors
if monkey.getError(response):
    print('Failed to start pre-registeration with the FIDO2 server.')
    sys.exit(0)
# Maybe save the challenge on the session so that you can match it when you receive the reply
SESSION['challenge'] = response.Response.challenge
# Prepare object for WebAuthn
webauthn = response.Response
# Set your Replay Party info
webauthn['rp']['id'] = RelayingPartyID # Relaying Party ID, a valid domain string that identifies the WebAuthn Relying Party (It should be the webpage domain or a subset of the domain)
webauthn['rp']['name'] = 'StrongMonkey' # Human-palatable identifier for the Relying Party, intended only for display
# Reply as JSON (assuming that the JavaScript will handle the request through ajax)
print(json.dumps(webauthn))
```

Example returned object:
```javascript
{
    "Response" : {
        "rp": {
            "name": "demo.strongauth.com:8181" // Human-palatable identifier for the Relying Party
            // Note that the required "id" attribute is not here, thus you will have to insert it
        },
        "user": {
            "name": "gramthanos",                                // Human-palatable identifier for the user account
            "id": "cp0VxDLKLIBHFNOFnvD8Nmw8ZeOXa6cGDolVtGQk_oE", // User id that maps credentials to the rp user entity
            "displayName": "gramthanos"                          // Human-palatable name for the user account
        },
        "challenge": "PO3B670hqw0ACFRwVTKzGQ", // Randomized challenge generated by the relay party server
        "pubKeyCredParams" : [ // Information about the properties of the credentials to be created
            {"type": "public-key", "alg": -7},     // ES256 : ECDSA w/ SHA-256
            {"type": "public-key", "alg": -35},    // ES384 : ECDSA w/ SHA-384
            {"type": "public-key", "alg": -36},    // ES512 : CDSA w/ SHA-512
            {"type": "public-key", "alg": -8},     // EdDSA
            {"type": "public-key", "alg": -43},    // SHA-384 (TEMPORARY - registered 2019-08-13, extension registered 2020-06-19, expires 2021-08-13) : SHA-2 384-bit Hash
            {"type": "public-key", "alg": -65535}, // RS1 : RSASSA-PKCS1-v1_5 using SHA-1
            {"type": "public-key", "alg": -257},   // RS256 : RSASSA-PKCS1-v1_5 using SHA-256
            {"type": "public-key", "alg": -258},   // RS384 : RSASSA-PKCS1-v1_5 using SHA-384
            {"type": "public-key", "alg": -259},   // RS512 : RSASSA-PKCS1-v1_5 using SHA-512
            {"type": "public-key", "alg": -37},    // PS256 : RSASSA-PSS w/ SHA-256
            {"type": "public-key", "alg": -38},    // PS384 : RSASSA-PSS w/ SHA-384
            {"type": "public-key", "alg": -39}     // PS512 : RSASSA-PSS w/ SHA-512
            // Alg values can be found at https://www.iana.org/assignments/cose/cose.xhtml#algorithms
        ],
        "excludeCredentials" : [ // Authenticators that also contains one of the credentials enumerated in this parameter will not be accepted
            {
                "type" : "public-key",
                "id" : "bEheZZuBISZo8COfypI5hnFExJUQU4qDfLuEESNFonw",
                "alg" : -7
            }
        ]
    }
}
```

On the client front-end side, if no library for WebAuthn is used, JavaScript will have to convert the `user.id`, the `challenge` and any `excludeCredentials[i].id` from `base64` to `Uint8Array`. For example, assuming the the JavaScript retrieved the above JSON object on the `options` variable:
```javascript
// Convert base64 values to Uint8Array
options.challenge = tools.base64urlToUint8Array(options.challenge);
options.user.id = tools.base64urlToUint8Array(options.user.id);
if (options.excludeCredentials) {
    for (var i = options.excludeCredentials.length - 1; i >= 0; i--) {
        options.excludeCredentials[i].id = tools.base64urlToUint8Array(options.excludeCredentials[i].id);
    }
}
// Call WebAuthn credentials create
window.navigator.credentials.create({
    publicKey: options
}).then((credentials) => {
    console.log('[Credentials]', credentials);
});
```

The `tools.base64urlToUint8Array` function can be found at the bottom of this section.



### Register
Send register response to the FIDO server.

```python
monkey.register(dict|str response[, dict|str metadata=None]) : int|dict
```

- response
	- Response data from the authenticator
- metadata
	- Additional meta data
	- *Meta data needed depend on your FIDO2 server*
	- *e.g. StrongKey FIDO2 Server requires an object as shown on the example bellow*

Example call
```python
# Assuming that the reply from the client is at the variable authenticator_response
# Before forwarding the request to the server, you may want to check the challenge
clientDataJSON = json.loads(base64Decode(authenticator_response['response']['clientDataJSON']))
if not clientDataJSON or SESSION['challenge'] != clientDataJSON['challenge']:
    print('Authentication failed due to challenge mismatch.')
    sys.exit(0)
# Request to register Key
response = monkey.register(authenticator_response, {
    'version' : '1.0',
    'create_location' : 'webapp',
    'username' : username,
    'origin' : 'https://' + RelayingPartyID
})
# Check for errors
if monkey.getError(response):
    print('Failed to register key to the FIDO2 server.')
    sys.exit(0)
# Print response message
print(response.Response)
```

Example returned object:
```javascript
{
    "Response" : "Successfully processed registration response"
}
```

On the client front-end side, if no library for WebAuthn is used, JavaScript will have to convert the `credential.rawId`, the `response.attestationObject` and the `response.clientDataJSON` from `Uint8Array` to `base64` before sending them to the server. For example, here is an example code of how to prepare the authenticator's response:
```javascript
// Call WebAuthn credentials create
window.navigator.credentials.create({
    ... // Credentials create options (explained on the preregister phase)
}).then((credentials) => {
    // Create credentials object
    let publicKeyCredential = {
        id : credential.id,
        type : credential.type,
        rawId : tools.uint8ArrayToBase64url(credential.rawId),
        response : {
            attestationObject : tools.uint8ArrayToBase64url(credential.response.attestationObject),
            clientDataJSON : tools.uint8ArrayToBase64url(credential.response.clientDataJSON)
        }
    };

    // The `publicKeyCredential` is now ready to be send to the server
    ... 
});
```

The `tools.uint8ArrayToBase64url` function can be found at the bottom of this section.

### Preauthenticate
Initialize a key authentication challenge with the FIDO server.

```python
monkey.preauthenticate(str username[, dict|str options=null[, dict|str extensions=null]]) : int|dict
```

- username
	- Username of the user
- options
	- Object of options
	- *Options support depend on your FIDO2 server*
- extensions
	- Object of extensions
	- *Extensions support depend on your FIDO2 server*

Example call
```python
# Request to start an user authentication
response = monkey.preauthenticate(username)
# Check for errors
if monkey.getError(response):
    print('Failed to start pre-authenticate with the FIDO2 server.')
    sys.exit(0)
# Maybe save the challenge on the session so that you can match it when you receive the reply
SESSION['challenge'] = response['Response']['challenge']
# Prepare object for WebAuthn
webauthn = response['Response']
print(json.dumps(webauthn))
```

Example returned object:
```javascript
{
    "Response" : {
        "challenge" : "CjoVc9BYbvihiaDw9C66mw"
        "allowCredentials" : [
            {
                "type" : "public-key",
                "id" : "-B8CY_OHv6ccJVWTmH-THUAEbmadKtSDLw-jG3eP33A",
                "alg" : -7
            }
        ]
    }
}
```

On the client front-end side, if no library for WebAuthn is used, JavaScript will have to convert the the `challenge` and any `allowCredentials[i].id` from `base64` to `Uint8Array`. For example, assuming the the JavaScript retrieved the above JSON object on the `options` variable:
```javascript
// Convert base64 values to Uint8Array
options.challenge = tools.base64urlToUint8Array(options.challenge);
if (options.allowCredentials) {
    for (var i = options.allowCredentials.length - 1; i >= 0; i--) {
        options.allowCredentials[i].id = tools.base64urlToUint8Array(options.allowCredentials[i].id);
    }
}
// Get credentials
window.navigator.credentials.get({
    publicKey: options
})
.then((assertion) => {
    console.log(assertion);
})
```

The `tools.base64urlToUint8Array` function can be found at the bottom of this section.

### Authenticate
Send authenticate response to the FIDO server.

```python
monkey.authenticate(dict|str response[, dict|str metadata=null]) : int|dict
```

- response
	- Response data from the authenticator
- metadata
	- Additional meta data
	- *Meta data needed depend on your FIDO2 server*
	- *e.g. StrongKey FIDO2 Server requires an object as shown on the example bellow*

Example call
```python
# Assuming that the reply from the client is at the variable authenticator_response
# Before forwarding the request to the server, you may want to check the challenge
clientDataJSON = json.loads(base64Decode(authenticator_response['response']['clientDataJSON']))
if not clientDataJSON or SESSION['challenge'] != clientDataJSON.challenge:
    print('Authentication failed due to challenge mismatch.')
    sys.exit(0)
# Here you may also want to check if the username provided exists as a user
# Request to authenticate user
response = monkey.authenticate(authenticator_response, {
    'version' : '1.0',
    'last_used_location' : 'webapp',
    'username' : username,
    'origin' : 'https://' + _SERVER['HTTP_HOST']
})
# Check for errors
if monkey.getError(response):
    print('Failed to authenticate user with the FIDO2 server.')
    sys.exit(0)
# Print response message
print(response['Response'])
```

Example returned object:
```javascript
{
    "Response" : ""
}
```

On the client front-end side, if no library for WebAuthn is used, JavaScript will have to convert the `credential.rawId`, the `response.attestationObject` and the `response.clientDataJSON` from `Uint8Array` to `base64` before sending them to the server. For example, here is an example code of how to prepare the authenticator's response:
```javascript
// Call WebAuthn credentials get
window.navigator.credentials.get({
    ... // Credentials get options (explained on the preauthenticate phase)
}).then((credentials) => {
    // Get credentials object
    let publicKeyCredential = {
        id : credentials.id,
        type : credentials.type,
        rawId : tools.uint8ArrayToBase64url(credentials.rawId),
        response : {
            authenticatorData : tools.uint8ArrayToBase64url(credentials.response.authenticatorData),
            signature : tools.uint8ArrayToBase64url(credentials.response.signature),
            userHandle : credentials.response.userHandle,
            clientDataJSON : tools.uint8ArrayToBase64url(credentials.response.clientDataJSON)
        }
    };

    // The `publicKeyCredential` is now ready to be send to the server
    ... 
});
```

The `tools.uint8ArrayToBase64url` function can be found at the bottom of this section.

### Update key info
Update key information.

```python
monkey.updatekeyinfo(str status, str modify_location, str displayname, str keyid) : int|dict
```

- status
	- The status of the key (Active, Inactive)
- modify_location
	- Modify location
- displayname
	- Display name of the key
- keyid
	- Id of the key to change

Example call
```python
# Request to update Key
response = monkey.updatekeyinfo('Inactive', 'webapp', 'Text Display Name', keyid)
# Check for errors
if monkey.getError(response):
    print('Failed to update key to the FIDO2 server.')
    sys.exit(0)
# Print response message
keys = response['Response']
```

Example returned object:
```javascript
{
    "Response" : "Successfully updated user registered security key"
}
```

### Get key info
Get user's keys information from the FIDO server.

```python
monkey.getkeysinfo(str username) : int|dict
```

- username
	- Username of the user

Example call
```python
# Request to get Keys from user
response = monkey.getkeysinfo(username)
# Check for errors
if monkey.getError(response):
    print('Failed to get keys from the FIDO2 server.')
    sys.exit(0)
# Retrieve keys from the response
keys = response['Response']['keys']
```

Example returned object:
```javascript
{
    "Response" : {
        "keys" : [
            {
                "randomid" : "1-1-gramthanos-9",     // The key ID
                "randomid_ttl_seconds" : "300",      // Time To Live for the random ID (in our tests the random id does not change after this time)
                "fidoProtocol" : "FIDO2_0",          // Unknown
                "fidoVersion" : "FIDO2_0",           // Unknown
                "createLocation" : "webapp",         // Location passed durring creation of key
                "createDate" : 1601485206000,        // Creation date as a Timestamp in milliseconds
                "lastusedLocation" : "Not used yet", // Unknown (needs testing)
                "modifyDate" : 0,                    // Unknown (needs testing)
                "status" : "Active",                 // Key status Active/Inactive
                "displayName" : "gramthanos"         // Display name of the user that owns the key
            }
        ]
    }
}
```

### Deregister
Delete user's key information from the FIDO server.

```python
monkey.deregister(str keyid) : int|dict
```

- keyid
	- Id of the key to deregister

Example call
```python
# Request to delete key
response = monkey.deregister(keyid)
# Check for errors
if monkey.getError(response):
    print('Failed to deregister key on the FIDO2 server.')
    sys.exit(0)
# Print response message
print(response['Response'])
```

Example returned object:
```javascript
{
    "Response" : "Successfully deleted user registered security key"
}
```

### Ping
Send a ping to the FIDO server to check if up.

```python
monkey.ping() : bool|str
```

Example call
```python
# Ping request
response = monkey.ping()
# Check for errors
if monkey.getError(response):
    print('Failed to ping FIDO2 server.')
    sys.exit(0)
# Print response
print(response)
```

Example return string:
```python
"""StrongKey, Inc. FIDO Server 4.3.0
Hostname: localhost (ServerID: 1)
Current time: Mon Oct 12 07:10:19 UTC 2020
Up since: Mon Oct 12 06:22:35 UTC 2020
FIDO Server Domain 1 is alive!"""
```


### Front-end JavaScript functions

The following functions can be used for the conversion between `base64` and `Uint8Array` on the front-end
```javascript
// Tools
const tools = {
    // Base64 to Uint8Array
    base64urlToUint8Array : function(base64url) {
        return this.base64ToUint8Array(this.base64urlToBase64(base64url));
    },
    base64ToUint8Array : function(base64) {
        var raw = window.atob(base64);
        var rawLength = raw.length;
        var array = new Uint8Array(new ArrayBuffer(rawLength));

        for(i = 0; i < rawLength; i++) {
            array[i] = raw.charCodeAt(i);
        }
        return array;
    },
    base64urlToBase64 : function(base64url) {
        var base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
        while(base64.length % 4 != 0){
            base64 += '=';
        }
        return base64;
    },
    // Uint8Array to Base64
    uint8ArrayToBase64url : function(array) {
        return this.base64ToBase64url(this.uint8ArrayToBase64(array));
    },
    uint8ArrayToBase64 : function(array) {
        var str = String.fromCharCode.apply(null, new Uint8Array(array));
        return window.btoa(str);
    },
    base64ToBase64url : function(base64) {
        var base64url = base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=*$/, '');
        return base64url;
    },
}
```

### Back-end Python functions

The following functions can be used for the conversion of `base64`
```python
import base64

def base64Encode(b):
	return base64.urlsafe_b64encode(b).decode().rstrip('=').encode()

def base64Decode(b):
	return base64.urlsafe_b64decode((b.decode() + ('=' * (4 - (len(b) % 4)))).encode())
```
