#!/usr/bin/python3
#
# StrongMonkey
# https://github.com/GramThanos/StrongMonkey
#
# Python Example
#

import sys
import json

# Include Library
import StrongMonkey

# Don't validate SSL certificate
StrongMonkey.STRONGMONKEY_DEBUG = True

# Print library info
print("Using StrongMonkey " + StrongMonkey.STRONGMONKEY_VESION);

# StrongKey FIDO info
FS_URL = 'https://192.168.56.102:8181'
FS_DID = 1
FS_PROTOCOL = 'REST' # Only REST is currently supported
# Authentication using HMAC
FS_AUTH = 'HMAC'
FS_KEYID = '162a5684336fa6e7'
FS_KEYSECRET = '7edd81de1baab6ebcc76ebe3e38f41f4'
# or Authentication using Password
FS_AUTH = 'PASSWORD'
FS_KEYID = 'svcfidouser'
FS_KEYSECRET = 'Abcd1234!'

# Initialize
monkey = StrongMonkey.StrongMonkey(FS_URL, FS_DID, FS_PROTOCOL, FS_AUTH, FS_KEYID, FS_KEYSECRET)

# Create a ping request
print("-----------------------------------")
print("Ping request ... ", end='')
result = monkey.ping()
error = monkey.getError(result)
if (error):
    print("failed")
    print("\t" + error)
    sys.exit(0)
print("ok")
# Print server info
print(result)

# Create a preregister request
print("-----------------------------------")
print("Pre-register request ... ", end='')
result = monkey.preregister('gramthanos')
error = monkey.getError(result)
if error:
    print("failed")
    print("\t" + error)
    sys.exit(0)
print("ok")
print(json.dumps(result))

# Create a preauthenticate request
print("-----------------------------------")
print("Pre-authenticate request ... ", end='')
result = monkey.preauthenticate('gramthanos', {
    "authenticatorSelection" : {
        "requireResidentKey" : True
    }
})
error = monkey.getError(result)
if (error):
    print("failed")
    print("\t" + error)
    sys.exit(0)
print("ok")
print(json.dumps(result))


# Create a getkeysinfo request
print("-----------------------------------")
print("Get keys info request ... ", end='')
result = monkey.getkeysinfo('gramthanos')
error = monkey.getError(result)
if (error):
    print("failed")
    print("\t" + error)
    sys.exit(0)
print("ok")
print(json.dumps(result))
