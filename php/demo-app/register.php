<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Register
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

$message_success = false;
$message_error = false;

// If this was a post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// Check data input data (TODO: this needs better testing)
	if (!isset($_POST['password']) || strlen($_POST['password']) < 3) {
		// Show error message
		$message_error = 'Invalid password';
	}
	else if ($_POST['password'] !== $_POST['password_confirmation']) {
		// Show error message
		$message_error = 'Passwords does not match';
	}
	else if (!isset($_POST['name']) || strlen($_POST['name']) < 3) {
		// Show error message
		$message_error = 'Invalid username';
	}
	else if (!isset($_POST['email']) || strlen($_POST['email']) < 3) {
		// Show error message
		$message_error = 'Invalid email';
	}
	else {
		// Try to create user
		$user = auth_register($_POST['name'], $_POST['email'], $_POST['password']);
		// If user was created
		if ($user) {
			// Session info
			$message_success = 'Your account was created';
		}
		// Failed to create user
		else {
			// Show error message
			$message_error = 'Failed to create account';
		}
	}
}

?>
<?php include(dirname(__FILE__) . '/includes/page_header.php'); ?>

		<!-- Register Page -->
		<div class="container">
			<div class="row">
				<div class="col-12" style="padding-top: 25px;padding-bottom: 25px;">
					<h4>Register Form</h4>
					<form method="POST">
						<div class="form-group">
							<label for="name">Username</label>
							<input type="text" class="form-control" id="name" name="name">
						</div>
						<div class="form-group">
							<label for="email">Email</label>
							<input type="text" class="form-control" id="email" name="email">
						</div>
						<div class="form-group">
							<label for="password">Password</label>
							<input type="password" class="form-control" id="password" name="password">
						</div>
						<div class="form-group">
							<label for="password">Password Confirm</label>
							<input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
						</div>
						<!-- TODO csrf token -->
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
						<button type="submit" class="btn btn-primary" id="login-btn">Register</button>
					</form>
				</div>
			</div>
		</div>

<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
