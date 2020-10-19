<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Login FIDO
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');
// Session
include(dirname(__FILE__) . '/includes/session.php');
// If logged in
if (session_isLoggedIn()) {
	header('Location: dashboard.php');
	exit();
}

?>
<?php include(dirname(__FILE__) . '/includes/page_header.php'); ?>

		<!-- Login with FIDO -->
		<div class="container">
			<div class="row">
				<div class="col-12" style="padding-top: 25px;padding-bottom: 25px;">
					<h4>FIDO Login Form</h4>
					<form id="fido-form">
						<div class="form-group">
							<label for="username-or-email">Username or Email</label>
							<input type="text" class="form-control" id="username-or-email" name="username-or-email">
						</div>
						<!-- TODO csrf token -->
						<button type="submit" class="btn btn-primary" id="login-with-fido">Login with FIDO</button>
					</form>
				</div>

				<!-- Messages -->
				<div class="col-12" style="padding-bottom: 25px;">
					<div id="webauthn-support" class="alert alert-danger" role="alert" style="display: none;">
						WebAuthn is not supported by your browser.
					</div>

					<!-- Error Message -->
					<div class="alert alert-danger alert-dismissible" role="alert" id="error" style="display: none;">
						<span id="error-msg"></span>
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<!-- Warning Message -->
					<div class="alert alert-warning alert-dismissible" role="alert" id="warning" style="display: none;">
						<span id="warning-msg"></span>
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<!-- Success Message -->
					<div class="alert alert-success alert-dismissible" role="alert" id="success" style="display: none;">
						<span id="success-msg"></span>
						<button type="button" class="close" data-dismiss="alert" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			if (typeof window.navigator.credentials == 'undefined') {
				document.getElementById('webauthn-support').style.display = 'block';
				document.getElementById('login-with-fido').setAttribute('disabled', 'disabled');
			}
		</script>

		<script type="text/javascript">
			// Request URLs
			const FidoAPI = {
				'auth' : 'fido.php?action=authenticate',
				'set' : 'fido.php?action=register',
				'get' : 'fido.php?action=info'
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

			// Wait page to load
			window.addEventListener('load', () => {
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
			});
		</script>

<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
