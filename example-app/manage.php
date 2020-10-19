<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Manage
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');
// Session
include(dirname(__FILE__) . '/includes/session.php');
// Auth
include(dirname(__FILE__) . '/includes/auth.php');
// StrongMonkey
include(STRONGMONKEY_LIB);
// If not logged in
if (!session_isLoggedIn()) {
	header('Location: login.php');
	exit();
}

$message_success = false;
$message_error = false;

// Init Connection with FIDO server
$smk = new StrongMonkey(APP_FIDO_URL, APP_FIDO_DID, APP_FIDO_PROTOCOL, APP_FIDO_AUTH, APP_FIDO_KEYID, APP_FIDO_KEYSECRET);

// If this was a post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	if (isset($_POST['randomid']) && isset($_POST['delete-btn'])) {
		$keys = $smk->getkeysinfo(session_get_userUsername());
		if ($smk->getError($keys) || !$keys) {
			$message_error = 'Failed to delete the key.';
		}
		else {
			$del = $smk->deregister($_POST['randomid']);
			if (!$smk->getError($del) || $del) {
				$message_success = 'Key was deleted!';
			}
			else {
				$message_error = 'Failed to delete the key.';
			}
		}
	}
}

// Get keys
$keys = $smk->getkeysinfo(session_get_userUsername());
if ($smk->getError($keys)) {
	$keys = array();
	if (!$message_error) $message_error = '';
	else $message_error .= ' ';
	$message_error .= 'Failed to contact FIDO2 server.';
}
else {
	$keys = $keys->Response->keys;
}

?>
<?php include(dirname(__FILE__) . '/includes/page_header.php'); ?>

		<!-- Manage Authenticators Page -->
		<div class="container">
			<div class="row">
				<div class="col-12" style="padding-top: 25px;">
					<h4>Manage Keys</h4>
				</div>
				<div class="col-12">
					<div class="table-responsive">
						<table class="table table-bordered">
							<thead>
								<tr>
									<th scope="col">#</th>
									<th scope="col">Key <small>(Status, RandomID, Create date, Last used location)</small></th>
									<th scope="col"></th>
								</tr>
							</thead>
							<tbody>
								<?php if (count($keys) == 0) { ?>
									<td colspan="3">You have no keys</td>
								<?php } else { ?>
									<?php foreach ($keys as $index => $key) { ?>
									<tr>
										<th scope="row"><?=($index + 1);?></th>
										<td>
											<?=htmlspecialchars($key->displayName)?><br>
											<small>
												<span class="badge badge-<?=$key->status=='Active'?'success':'secondary';?>">Status: <?=htmlspecialchars($key->status);?></span>
												<span class="badge badge-light">id: <?=htmlspecialchars($key->randomid);?></span>
												<span class="badge badge-info">Created on: <?=htmlspecialchars(date('D, j M Y H:i:s e', $key->createDate/1000));?></span>
												<span class="badge badge-dark">Last used at: <?=htmlspecialchars($key->lastusedLocation);?></span>
											</small>
										</td>
										<td>
											<form method="POST">
												<!--<%= hidden_field_tag :authenticity_token, form_authenticity_token %>-->
												<input type="hidden" name="randomid" value="<?=htmlspecialchars($key->randomid)?>"/>
												<input type="submit" class="btn btn-danger btn-sm" name="delete-btn" value="&times;"/>
											</form>
										</td>
									</tr>
									<?php } ?>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Messages -->
				<div class="col-12" style="padding-bottom: 25px;">
					<?php if ($message_error) { ?>
						<div class="alert alert-danger" role="alert">
							<?=$message_error;?>
						</div>
					<?php } ?>					
					<?php if ($message_success) { ?>
						<div class="alert alert-success" role="alert">
							<?=$message_success;?>
						</div>
					<?php } ?>
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

				<div class="col-12" style="padding-bottom: 25px;">
					<button type="button" class="btn btn-primary" id="add">Add key</button>
				</div>
			</div>
		</div>

		<script type="text/javascript">
			if (typeof window.navigator.credentials == 'undefined') {
				document.getElementById('webauthn-support').style.display = 'block';
				document.getElementById('add').setAttribute('disabled', 'disabled');
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
			const csrf_tag = 123;//document.head.querySelector("meta[name~=csrf-token][content]").content;

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
			var lock_add = false;

			// Start Challenge
			var fido_request_challenge = function() {
				log.success.hide();
				log.error.hide();
				log.warning.show('Starting a challenge with the server ...');

				// Ask server for a challenge
				jQuery.post(FidoAPI.set, {authenticity_token : csrf_tag})
				.done(function(json) {
					console.log('[Request Challenge Success]', json);
					log.warning.show('Creating authenticator credentials ...');
					// Parse options
					let options = json.options;
					options.challenge = tools.base64urlToUint8Array(options.challenge);
					options.user.id = tools.base64urlToUint8Array(options.user.id);
					if (options.excludeCredentials) {
						for (var i = options.excludeCredentials.length - 1; i >= 0; i--) {
							options.excludeCredentials[i].id = tools.base64urlToUint8Array(options.excludeCredentials[i].id);
						}
					}
					// Create credentials
					window.navigator.credentials.create({
						publicKey: options
					})
					.then((credentials) => {
						console.log('[Credentials Create Success]', credentials);
						log.warning.show('Credentials were created ...');
						setTimeout(() => {fido_respond_challenge(credentials);}, 0);
					})
					.catch((error) => {
						lock_add = false;
						log.success.hide();
						log.warning.hide();
						console.log('[Credentials Create Error]', error);
						log.error.show('[Credentials Create Error] Failed to create authenticator credentials.');
						return;
					});
				})
				.fail(function(error) {
					lock_add = false;
					console.log('[Request Challenge Error]', error);
					log.success.hide();
					log.warning.hide();
					log.error.show('[Request Challenge Error] Failed to start a challenge with the server.');
				})
			}
			// Reply to challenge
			var fido_respond_challenge = function(credential) {
				// Parse data
				//credential.rawId = tools.uint8ArrayToBase64url(credential.rawId);
				//credential.response.attestationObject = tools.uint8ArrayToBase64url(credential.response.attestationObject);
				//credential.response.clientDataJSON = tools.uint8ArrayToBase64url(credential.response.clientDataJSON);

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
				// Send challenge response to server
				jQuery.post(FidoAPI.set, {publicKeyCredential: publicKeyCredential, authenticity_token : csrf_tag})
				.done(function(json) {
					console.log('[Challenge Completed]', json);
					log.warning.hide();
					log.success.show('Authenticator credentials were added! ... Refreshing the page ...');
					lock_add = false;
					setTimeout(() => {window.location.href = window.location.href;}, 500);
				})
				.fail(function(error) {
					lock_add = false;
					console.log('[Challenge Response Error]', error);
					log.success.hide();
					log.warning.hide();
					log.error.show('Server verification of the challenge failed.');
				})
			}

			// Wait page to load
			window.addEventListener('load', () => {
				// Handle ADD click
				document.getElementById('add').addEventListener('click', () => {
					if (lock_add) return;
					lock_add = true;
					log.success.hide();
					log.warning.hide();
					log.error.hide();
					fido_request_challenge();
				});
			});
		</script>

<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
