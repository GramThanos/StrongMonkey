<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Login
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');
// Session
include(dirname(__FILE__) . '/includes/session.php');
// Auth
include(dirname(__FILE__) . '/includes/auth.php');
// If logged in
if (session_isLoggedIn()) {
	header('Location: dashboard.php');
	exit();
}

$message_error = false;

// If this was a post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Try to find the user and authenticate him
	$user = null;
	if (strpos($_POST['username-or-email'], '@') !== false) {
		$user = auth_getUserByEmail($_POST['username-or-email']);
	}
	else {
		$user = auth_getUserByUsername($_POST['username-or-email']);
	}
	if (!auth_authenticate($user, $_POST['password'])) {
		$user = null;
	}
	// If user was found and authentication was successful
	if ($user) {
		// Session info
		session_logIn($user);
		// Go to dashboard
		header('Location: dashboard.php');
		exit();
	}
	// User was not found or Authentication failed
	else {
		// Show error message
		$message_error = "Invalid credentials";
	}
}

?>
<?php include(dirname(__FILE__) . '/includes/page_header.php'); ?>

		<!-- Login Page -->
		<div class="container">
			<div class="row">
				<!-- Login -->
				<div class="col-12" style="padding-top: 25px;padding-bottom: 25px;">
					<h4>Login Form</h4>
					<form method="POST">
						<div class="form-group">
							<label for="username-or-email">Username or Email</label>
							<input type="text" class="form-control" id="username-or-email" name="username-or-email">
						</div>
						<div class="form-group">
							<label for="password">Password</label>
							<input type="password" class="form-control" id="password" name="password">
						</div>
						<!-- TODO csrf token -->
						<?php if ($message_error) { ?>
							<div class="alert alert-danger" role="alert">
								<?=$message_error;?>
							</div>
						<?php } ?>
						<button type="submit" class="btn btn-primary" id="login-btn">Login</button>
						<a href="loginfido.php" class="btn btn-light">Login with FIDO</a>
					</form>
				</div>
			</div>
		</div>

<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
