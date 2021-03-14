// Request URLs
const FidoAPI = {
	'auth' : 'fido.php?action=authenticate',
	'set' : 'fido.php?action=register',
	'get' : 'fido.php?action=info',
	'qrcode' : 'fido.php?action=qrcode'
};

// Get CSRF tag
const csrf_tag = 1234;//document.head.querySelector("meta[name~=csrf-token][content]").content;

// Log
const log = {
	error : {
		div : document.getElementById('error'),
		span : document.getElementById('error-msg'),
		show : function(error) {
			this.span.textContent = error;
			this.div.style.display = 'block';
		},
		showHTML : function(error) {
			this.span.innerHTML = error;
			this.div.style.display = 'block';
		},
		hide : function() {
			this.div.style.display = 'none';
			this.span.textContent = '';
		}
	},
	warning : {
		div : document.getElementById('warning'),
		span : document.getElementById('warning-msg'),
		show : function(warning) {
			this.span.textContent = warning;
			this.div.style.display = 'block';
		},
		showHTML : function(warning) {
			this.span.innerHTML = warning;
			this.div.style.display = 'block';
		},
		hide : function() {
			this.div.style.display = 'none';
			this.span.textContent = '';
		}
	},
	success : {
		div : document.getElementById('success'),
		span : document.getElementById('success-msg'),
		show : function(success) {
			this.span.textContent = success;
			this.div.style.display = 'block';
		},
		showHTML : function(success) {
			this.span.innerHTML = success;
			this.div.style.display = 'block';
		},
		hide : function() {
			this.div.style.display = 'none';
			this.span.textContent = '';
		}
	}
};

// Tools
const tools = {
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

// Mutex to avoid double clicks
var lock_login = false;

// Start Challenge
var fido_authn_challenge = function() {
	log.success.hide();
	log.error.hide();
	log.warning.show('Starting a challenge with the server ...');

	// Remember username
	localStorage.setItem("identifier", jQuery('#username-or-email').val());

	// Ask server for a challenge
	jQuery.post(FidoAPI.auth, {'userid' : jQuery('#username-or-email').val(), authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[Request Challenge Success]', json);
		log.warning.show('Creating authenticator credentials ...');
		// Parse options
		let options = json.options;
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
			console.log('[Assertion Create Success]', assertion);
			log.warning.show('Assertion was created... Please wait...');
			setTimeout(() => {fido_reply_challenge(assertion);}, 0);
		})
		.catch((error) => {
			lock_login = false;
			log.success.hide();
			log.warning.hide();
			console.log('[Assertion Create Error]', error);
			log.error.show('[Assertion Create Error] Failed to create authenticator assertion.');
			return;
		});
	})
	.fail(function(error) {
		lock_login = false;
		console.log('[Request Challenge Error]', error);
		log.success.hide();
		log.warning.hide();
		if (error && error.responseJSON && error.responseJSON.error) {
			log.error.show(error.responseJSON.error);
		}
		else {
			log.error.show('[Request Challenge Error] Failed to start a challenge with the server.');
		}
	})
}

// Reply to challenge
var fido_reply_challenge = function(assertion) {
	// Create assertion object
	let publicKeyCredential = {
		id : assertion.id,
		type : assertion.type,
		rawId : tools.uint8ArrayToBase64url(assertion.rawId),
		response : {
			authenticatorData : tools.uint8ArrayToBase64url(assertion.response.authenticatorData),
			signature : tools.uint8ArrayToBase64url(assertion.response.signature),
			userHandle : assertion.response.userHandle,
			clientDataJSON : tools.uint8ArrayToBase64url(assertion.response.clientDataJSON)
		}
	};
	// Send challenge response to server
	jQuery.post(FidoAPI.auth, {publicKeyCredential: publicKeyCredential, authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[Challenge Completed]', json);
		log.warning.hide();
		log.success.show('Authentication was successful! ... redirecting ...');
		setTimeout(() => {document.location.href = document.location.href;}, 500);
	})
	.fail(function(error) {
		lock_login = false;
		console.log('[Challenge Response Error]', error);
		log.success.hide();
		log.warning.hide();
		log.error.show('Server verification of the challenge failed.');
	})
}

// QR Code Start Challenge
var fido_qrcode_authn_init = function() {
	window.qrcode_options = null;
	log.success.hide();
	log.error.hide();
	log.warning.show('Loading server info ...');
	document.getElementById('authenticate-auth').setAttribute('disabled', 'disabled');
	
	// Ask server for a challenge
	jQuery.post(FidoAPI.qrcode + '&code=' + window.qrcode, {authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[QR Code Request Challenge Success]', json);
		log.warning.hide();
		// Show device info
		jQuery('#authenticate-client-id').text('IP: ' + json['client']['id']);
		jQuery('#authenticate-client-name').text('Agent: ' + json['client']['name']);
		document.getElementById('authenticate-auth').removeAttribute('disabled');
		// Parse options
		let options = json.options;
		options.challenge = tools.base64urlToUint8Array(options.challenge);
		if (options.allowCredentials) {
			for (var i = options.allowCredentials.length - 1; i >= 0; i--) {
				options.allowCredentials[i].id = tools.base64urlToUint8Array(options.allowCredentials[i].id);
			}
		}
		window.qrcode_options = options;
	})
	.fail(function(error) {
		lock_login = false;
		console.log('[QR Code Request Challenge Error]', error);
		document.getElementById('authenticate-auth').setAttribute('disabled', 'disabled');
		log.success.hide();
		log.warning.hide();
		if (error && error.responseJSON && error.responseJSON.error) {
			log.error.show(error.responseJSON.error);
		}
		else {
			log.error.show('[Request Challenge Error] Failed to start a challenge with the server.');
		}
	})
}
// QR Code Start Challenge
var fido_qrcode_authn_challenge = function() {
	jQuery('#authenticate-auth').attr('disabled', 'disabled');
	if (!window.qrcode_options) {
		setTimeout(function() {
			fido_qrcode_authn_challenge();
		}, 200);
		return;
	}
	// Get credentials
	window.navigator.credentials.get({
		publicKey: window.qrcode_options
	})
	.then((assertion) => {
		console.log('[QR Code Assertion Create Success]', assertion);
		log.warning.show('Assertion was created... Please wait...');
		setTimeout(() => {fido_qrcode_reply_challenge(assertion);}, 0);
	})
	.catch((error) => {
		lock_login = false;
		console.log('[QR Code Assertion Create Error]', error);
		jQuery('#authenticate-auth').attr('disabled', 'disabled');
		log.success.hide();
		log.warning.hide();
		log.error.show('[Assertion Create Error] Failed to create authenticator assertion.');
		return;
	});
}

// QR Code Reply to challenge
var fido_qrcode_reply_challenge = function(assertion) {
	// Create assertion object
	let publicKeyCredential = {
		id : assertion.id,
		type : assertion.type,
		rawId : tools.uint8ArrayToBase64url(assertion.rawId),
		response : {
			authenticatorData : tools.uint8ArrayToBase64url(assertion.response.authenticatorData),
			signature : tools.uint8ArrayToBase64url(assertion.response.signature),
			userHandle : assertion.response.userHandle,
			clientDataJSON : tools.uint8ArrayToBase64url(assertion.response.clientDataJSON)
		}
	};
	// Send challenge response to server
	jQuery.post(FidoAPI.qrcode + '&code=' + window.qrcode, {publicKeyCredential: publicKeyCredential, authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[QR Code Challenge Completed]', json);
		log.warning.hide();
		log.success.show('Authentication was successful!');
	})
	.fail(function(error) {
		jQuery('#authenticate-auth').attr('disabled', 'disabled');
		lock_login = false;
		console.log('[QR Code Challenge Response Error]', error);
		log.success.hide();
		log.warning.hide();
		log.error.show('Server verification of the challenge failed.');
	})
}

// Generate QR code
var fido_qrcode_authn_generate = function() {
	log.error.hide();
	log.success.hide();
	log.warning.show('Loading ...');
	// Get code from the server
	jQuery.post(FidoAPI.qrcode, {userid: jQuery('#username-or-email').val(), authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[QR Code Generated]', json);
		log.success.show('QR code was generated!');
		log.warning.hide();
		// Save info
		window.qrcode = json.code;
		var qrcode = document.getElementById('qrcode-generate');
		qrcode.innerHTML = '';
		qrcode.style.display = 'block';
		new window.QRCode(qrcode, window.location.href.replace(/\?.+/i,'') + '?code=' + json.code);
		document.getElementById('qrcode-loading').style.display = 'none';
		$('#qrcodeModal').modal('show');
		setTimeout(function() {fido_qrcode_authn_check();}, 10 * 1000);
	})
	.fail(function(error) {
		lock_login = false;
		console.log('[QR Code Generated Error]', error);
		log.success.hide();
		log.warning.hide();
		log.error.show('QR code generation failed.');
		$('#qrcodeModal').modal('hide');
	})
}

// Check QR code status
var fido_qrcode_authn_check = function() {
	if (!lock_login) return;
	// Send challenge response to server
	jQuery.post(FidoAPI.qrcode + '&code=' + window.qrcode + '&status=get', {authenticity_token : csrf_tag})
	.done(function(json) {
		console.log('[QR Code Check]', json);
		if (json.status == 'challenging') {
			document.getElementById('qrcode-generate').style.display = 'none';
			document.getElementById('qrcode-loading').style.display = 'block';
		}
		else if (json.status == 'authenticated') {
			window.location.href = window.location.href;
			return;
		}
		setTimeout(function() {fido_qrcode_authn_check();}, 5 * 1000);
	})
	.fail(function(error) {
		lock_login = false;
		console.log('[QR Code Status Check Error]', error);
		log.success.hide();
		log.warning.hide();
		log.error.show('QR code login failed.');
		$('#qrcodeModal').modal('hide');
	})
}

// Wait page to load
window.addEventListener('load', () => {
	// Check if there is a qrcode
	var url = new URL(window.location.href);
	var code = url.searchParams.get('code');
	if (code) {
		window.qrcode = code;
		jQuery('#authenticate-close').click(function() {
			if (lock_login) return false;
			lock_login = true;
			window.location.href = '/';
		});
		jQuery('#authenticate-auth').click(function() {
			if (lock_login) return false;
			lock_login = true;
			fido_qrcode_authn_challenge();	
		});
		fido_qrcode_authn_init();
		return false;
	}

	// Remember username
	var identifier = localStorage.getItem("identifier");
	if (identifier) jQuery('#username-or-email').val(identifier);
	// Handle ADD click
	document.getElementById('fido-form').addEventListener('submit', (e) => {
		e.preventDefault();
		if (lock_login) return false;
		lock_login = true;
		log.success.hide();
		log.warning.hide();
		log.error.hide();
		fido_authn_challenge();
		return false;
	});
	// Handle QR code generation
	document.getElementById('login-with-fido-qrcode').addEventListener('click', (e) => {
		e.preventDefault();
		if (lock_login) return false;
		lock_login = true;
		log.success.hide();
		log.warning.hide();
		log.error.hide();
		fido_qrcode_authn_generate();
		return false;
	});
});
