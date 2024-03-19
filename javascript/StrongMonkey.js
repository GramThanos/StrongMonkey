//
// StrongMonkey v0.0.5-beta
// Javascript SDK for interacting with FIDO2 Server API v3.0.0
// Copyright (c) 2022 Grammatopoulos Athanasios-Vasileios
//

global.STRONGMONKEY_VESION = 'v0.0.5-beta';
if (global.STRONGMONKEY_DEBUG === undefined) global.STRONGMONKEY_DEBUG = false;
if (global.STRONGMONKEY_CONNECTTIMEOUT === undefined) global.STRONGMONKEY_CONNECTTIMEOUT = 10;
if (global.STRONGMONKEY_TIMEOUT === undefined) global.STRONGMONKEY_TIMEOUT = 30;
if (global.STRONGMONKEY_USERAGENT === undefined) global.STRONGMONKEY_USERAGENT = 'StrongMonkey-Agent' + '/' + global.STRONGMONKEY_VESION;

var StrongMonkey = (function () {
    /**
     * Constructor initialize object
     * @constructor
     */
    var StrongMonkey = function (hostport, did, protocol, authtype, keyid, keysecret) {
        // TODO: Test inputs? No?
        // Save information
        this.hostport = hostport;
        this.did = did;
        this.protocol = protocol;
        this.authtype = authtype;
        this.keyid = keyid;
        this.keysecret = keysecret;

        // Check if not supported
        if (authtype != StrongMonkey.AUTHORIZATION_HMAC and authtype != StrongMonkey.AUTHORIZATION_PASSWORD):
            console.log('The provided authorization method is not supported');
        if (protocol != StrongMonkey.PROTOCOL_REST):
            console.log('The provided protocol is not supported');
    };

    // Static variables
    StrongMonkey.api_protocol = 'FIDO2_0';
    StrongMonkey.api_version = 'SK3_0';
    StrongMonkey.api_url_base = '/skfs/rest';
    StrongMonkey.version = global.STRONGMONKEY_VESION;
    StrongMonkey.useragent = global.STRONGMONKEY_USERAGENT;

    // ERRORS
    StrongMonkey.PARSE_ERROR = 1001;
    StrongMonkey.SUBMIT_ERROR = 1002;
    StrongMonkey.AUTHENTICATION_FAILED = 1003;
    StrongMonkey.RESOURCE_UNAVAILABLE = 1004;
    StrongMonkey.UNEXPECTED_ERROR = 1005;
    StrongMonkey.UNUSED_ROUTES = 1006;
    StrongMonkey.UNKNOWN_ERROR = 1007;

    // Authorization Methods
    StrongMonkey.AUTHORIZATION_HMAC = 'HMAC';
    StrongMonkey.AUTHORIZATION_PASSWORD = 'PASSWORD';
    // Protocol Methods
    StrongMonkey.PROTOCOL_REST = 'REST';

    StrongMonkey.prototype.preregister = function (username, displayname=null, options=null, extensions=null) {
        // Init parameters
        if (displayname === null)
            displayname = username;
        options = this.jsonStringPrepare(options, {});
        extensions = this.jsonStringPrepare(extensions, {});

        // Create data
        var payload = {
            'username' : username,
            'displayname' : displayname,
            'options' : options,
            'extensions' : extensions
        };

        // Make preregister request
        return this.request(payload, '/preregister');
    };

    StrongMonkey.prototype.register = function (response, metadata=null) {
        // Init empty parameters
        response = this.jsonStringPrepare(response);
        metadata = this.jsonStringPrepare(metadata, {});

        // Create data
        var payload = {
            'response' : response,
            'metadata' : metadata
        };

        // Make register request
        return this.request(payload, '/register');
    };

    StrongMonkey.prototype.preauthenticate = function (response, username=null, options=null, extensions=null) {
        // Init empty parameters
        options = this.jsonStringPrepare(options, {});
        extensions = this.jsonStringPrepare(extensions, {});

        // Create data
        var payload = {
            'username' : username,
            'options' : options,
            'extensions' : extensions
        };

        // Make preauthenticate request
        return this.request(payload, '/preauthenticate');
    };

    StrongMonkey.prototype.authenticate = function (response, metadata=null) {
        // Init empty parameters
        response = this.jsonStringPrepare(response);
        metadata = this.jsonStringPrepare(metadata, {});

        // Create data
        var payload = {
            'response' : response,
            'metadata' : metadata
        };

        // Make authenticate request
        return this.request(payload, '/authenticate');
    };

    StrongMonkey.prototype.updatekeyinfo = function (status, modify_location, displayname, keyid) {
        // Create data
        var payload = {
            "status" : status,
            "modify_location" : modify_location,
            "displayname" : displayname,
            "keyid" : keyid
        };

        // Make updatekeyinfo request
        return this.request(payload, '/updatekeyinfo');
    };

    StrongMonkey.prototype.getkeysinfo = function (username) {
        // Create data
        var payload = {
            "username" : username
        };

        // Make getkeysinfo request
        return this.request(payload, '/getkeysinfo');
    };

    StrongMonkey.prototype.deregister = function (keyid) {
        // Create data
        var payload = {
            "keyid" : keyid
        };

        // Make deregister request
        return this.request(payload, '/deregister');
    };

    StrongMonkey.prototype.ping = function () {
        // Make ping request
        var response = this.request(None, '/ping', false);
        // If no error
        if (response['code'] == 200)
            return response['body'];

        // Return error code
        return this.parseResponse(response['code'], response['body']);
    };

    StrongMonkey.prototype.request = function (payload, action_path, parse=true) {
        // Create data
        var body = {
            "svcinfo" : {
                "did" : this.did,
                "protocol" : StrongMonkey.api_protocol,
                "authtype" : this.authtype
            }
        };
        // Prepare payload
        if (payload !== null)
            body['payload'] = payload;

        // Generate path
        var path = StrongMonkey.api_url_base + action_path;

        // Prepare Request Headers
        var headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'User-Agent': StrongMonkey.useragent
        };

        // HMAC
        if (this.authtype == StrongMonkey.AUTHORIZATION_HMAC) {
            // Get date
            var data = new Date().toUTCString();

            // Prepare hashes
            var payload_hash = '';
            var mimetype = '';
            if (payload !== null) {
                var payload_string = json.dumps(body['payload'],separators=(',', ':'));
                payload_hash = hashlib.sha256(payload_string.encode()).digest();
                payload_hash = base64.b64encode(payload_hash).decode();
                mimetype = 'application/json';
            }

            // Generate HMAC authentication
            var authentication_hash = this.generateHMAC('POST', payload_hash, mimetype, date, path);
            
            // Add authorization Headers
            headers['strongkey-content-sha256'] = payload_hash;
            headers['Date'] = date;
            headers['strongkey-api-version'] = StrongMonkey.api_version;
            headers['Authorization'] = authentication_hash;
        }
        // Credentials
        else {
            body['svcinfo']['svcusername'] = this.keyid;
            body['svcinfo']['svcpassword'] = this.keysecret;
        }

        // Create request
        var reqOptions = {
            'url' : this.hostport + path,
            'verify' : true,
            'data' : json.dumps(body),
            'headers' : headers,
            'timeout' : global.STRONGMONKEY_TIMEOUT
        };
        if (global.STRONGMONKEY_DEBUG) {
            requests.packages.urllib3.disable_warnings();
            reqOptions['verify'] = false;
        }
        var ch = requests.post(
            reqOptions['url'],
            verify = reqOptions['verify'],
            data = reqOptions['data'],
            headers = reqOptions['headers'],
            timeout = reqOptions['timeout']
        );
        var response = ch.text;
        var response_code = ch.status_code;

        if (parse) {
            return this.parseResponse(response_code, response);
        }
        else {
            return {
                'code' : response_code,
                'body' : response
            };
        }
    };

    StrongMonkey.prototype.parseResponse = function (code, response) {
        // 200: Success
        if (code == 200) {
            try {
                response = json.loads(response);
                return response;
            } catch(e) {
                return StrongMonkey.PARSE_ERROR;
            }
        }
        // 400: There was an error in the submitted input.
        if (code == 400)
            return StrongMonkey.SUBMIT_ERROR;
        // 401: The authentication failed.
        if (code == 401)
            return StrongMonkey.AUTHENTICATION_FAILED;
        // 404: The requested resource is unavailable.
        if (code == 404)
            return StrongMonkey.RESOURCE_UNAVAILABLE;
        // 500: The server ran into an unexpected exception.
        if (code == 500)
            return StrongMonkey.UNEXPECTED_ERROR;
        // 501: Unused routes return a 501 exception with an error message.
        if (code == 501)
            return StrongMonkey.UNUSED_ROUTES;
        return StrongMonkey.UNKNOWN_ERROR;
    };

    StrongMonkey.prototype.getError = function (error) {
        // If not error
        if (typeof error === 'number')
            return false;
        // Resolve error code
        if (error == StrongMonkey.PARSE_ERROR)
            return 'StrongMonkey: Response parse error.';
        if (error == StrongMonkey.SUBMIT_ERROR)
            return 'StrongMonkey: There was an error in the submitted input.';
        if (error == StrongMonkey.AUTHENTICATION_FAILED)
            return 'StrongMonkey: The authentication failed.';
        if (error == StrongMonkey.RESOURCE_UNAVAILABLE)
            return 'StrongMonkey: The requested resource is unavailable.';
        if (error == StrongMonkey.UNEXPECTED_ERROR)
            return 'StrongMonkey: The server ran into an unexpected exception.';
        if (error == StrongMonkey.UNKNOWN_ERROR)
            return 'StrongMonkey: Unused routes return a 501 exception with an error message.';
        return 'StrongMonkey: Unknown error code.';
    };

    StrongMonkey.prototype.generateHMAC = function (method, payload, mimetype, datestr, path) {
        // Assembly hash message
        var message = [
            method,
            payload,
            mimetype,
            datestr,
            StrongMonkey.api_version,
            path
        ];
        message = "\n".join(message);
        // Generate HMAC
        var digest = hmac.new(bytes.fromhex(this.keysecret), msg = bytes(message , 'latin-1'), digestmod = hashlib.sha256).digest();
        // Return header
        return 'HMAC ' + this.keyid + ':' + base64.b64encode(digest).decode();
    };

    StrongMonkey.prototype.jsonStringPrepare = function (vjson, ifnull=null) {
        if ((vjson === null) && (ifnull !== null))
            vjson = ifnull;
        if (typeof vjson === 'string')
            return vjson;
        return json.dumps(vjson);
    };

    return StrongMonkey;
}());

module.exports = StrongMonkey;
