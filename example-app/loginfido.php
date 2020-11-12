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
				<?php if (isset($_GET['code'])) { ?>
				<div class="col-12" style="padding-top: 25px;padding-bottom: 25px;min-height: 250px;">
					<h4>FIDO Login with device</h4>
					<p>Are you sure you want to authenticate client?</p>
					<div style="padding-bottom: 15px;font-style: italic;">
						<div id="authenticate-client-id">
							<div class="loading black"><div></div><div></div><div></div></div>
						</div>
						<div id="authenticate-client-name">
							<div class="loading black"><div></div><div></div><div></div></div>
						</div>
					</div>
					<button type="button" class="btn btn-secondary" id="authenticate-close">Close</button>
					<button type="button" class="btn btn-primary" id="authenticate-auth">Authenticate</button>
				</div>
				<?php } else { ?>
				<div class="col-12" style="padding-top: 25px;padding-bottom: 25px;">
					<h4>FIDO Login Form</h4>
					<form id="fido-form">
						<div class="form-group">
							<label for="username-or-email">Username or Email</label>
							<input type="text" class="form-control" id="username-or-email" name="username-or-email">
						</div>
						<!-- TODO csrf token -->
						<button type="submit" class="btn btn-primary" id="login-with-fido">Login with FIDO</button>
						<button type="button" class="btn btn-secondary" id="login-with-fido-qrcode">Login with FIDO QR Code</button>
					</form>
				</div>
				<?php } ?>

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

		<!-- QR Code -->
		<div class="modal fade" id="qrcodeModal" tabindex="-1" aria-labelledby="qrcodeModalLabel" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="qrcodeModalLabel">Scan QR Code</h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<p>Use your device to scan the QR code and authenticate with it.</p>
						<div id="qrcode-generate" style="width: 306px;height: 306px;border-radius: 8px;padding: 25px;margin: 25px auto;border: 1px solid #aaaaaa;"></div>
						<div id="qrcode-loading" class="loading black" style="margin: 0 auto;"><div></div><div></div><div></div></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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

<?php
	$footer_scripts = array(
		'js/qrcode.min.js',
		'js/login-fido.js'
	);
?>
<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
