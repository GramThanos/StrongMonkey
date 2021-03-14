<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Page Index
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
else {
	header('Location: login.php');
	exit();
}
