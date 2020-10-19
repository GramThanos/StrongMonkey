<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Dashboard
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');
// Session
include(dirname(__FILE__) . '/includes/session.php');
// If not logged in
if (!session_isLoggedIn()) {
	header('Location: login.php');
	exit();
}

$username = htmlspecialchars(session_get_userUsername());
$email = htmlspecialchars(session_get_userEmail());

?>
<?php include(dirname(__FILE__) . '/includes/page_header.php'); ?>

		<!-- Dashboard Page -->
		<div class="container">
			<div class="row">
				<div class="col-sm" style="padding-top: 25px;padding-bottom: 25px;">
					<h4>Dashboard</h4>
					<h6>Welcome <?=$username;?>!</h6>
					<table class="table table-bordered">
						<tbody>
							<tr>
								<th scope="row">Username</th>
								<td><?=$username;?></td>
							</tr>
							<tr>
								<th scope="row">Email</th>
								<td><?=$email;?></td>
							</tr>
						</tbody>
					</table>
					<a href="manage.php" class="btn btn-primary">Manage your keys</a>
				</div>
			</div>
		</div>

<?php include(dirname(__FILE__) . '/includes/page_footer.php'); ?>
