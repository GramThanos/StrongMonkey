<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Header File
 */
?><!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<title><?=APP_NAME;?></title>
		<meta name="description" content="<?=APP_DESCRIPTION;?>">
		<meta name="author" content="GramThanos">
		<link rel="shortcut icon" href="favicon.ico">

		<!-- Bootstrap CSS -->
		<link rel="stylesheet" href="css/bootstrap.min.css">
		<link rel="stylesheet" href="css/app.css">
	</head>

	<body>
		<nav class="navbar navbar-expand-lg navbar-light bg-light">
			<div class="container">
				<a class="navbar-brand" href="index.php"><?=APP_NAME;?> <small><?=APP_VERSION;?></small></a>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon"></span>
				</button>
				<div class="collapse navbar-collapse" id="navbarSupportedContent">
					<?php if (!session_isLoggedIn()) { ?>
					<ul class="navbar-nav mr-auto">
						<li class="nav-item">
							<a class="nav-link" href="login.php">Login</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="register.php">Register</a>
						</li>
					</ul>
					<?php } else { ?>
					<ul class="navbar-nav mr-auto">
						<li class="nav-item">
							<a class="nav-link" href="dashboard.php">Dashboard</a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="manage.php">Manage Keys</a>
						</li>
					</ul>
					<ul class="navbar-nav ml-auto">
						<li class="nav-item">
							<a class="nav-link"><?=htmlspecialchars(session_get_userUsername());?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" href="logout.php">Logout</a>
						</li>
					</ul>
					<?php } ?>
				</div>
			</div>
		</nav>
