<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Authenticate User
 */

// Database Connection
$DB_CONNECTION = false;

// Get Database Connection
function auth_getDbConn () {
	global $DB_CONNECTION;
	if (!$DB_CONNECTION) {
		$DB_CONNECTION = new mysqli(APP_DATABASE_HOST, APP_DATABASE_USER, APP_DATABASE_PASSWORD, APP_DATABASE_NAME);
	}
	if ($DB_CONNECTION->connect_errno) {
		return false;
	}
	return $DB_CONNECTION;
}

// Get user by username
function auth_getUserByUsername ($username) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	$stmt = $conn->prepare('SELECT id, username, email, password_hash FROM users WHERE username=?');
	$stmt->bind_param('s', $username);
	$stmt->execute();
	$result = $stmt->get_result();
	if (!$result) return null;
	return $result->fetch_assoc();
}

// Get user by email
function auth_getUserByEmail ($email) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	$stmt = $conn->prepare('SELECT id, username, email, password_hash FROM users WHERE email=?');
	$stmt->bind_param('s', $email);
	$stmt->execute();
	$result = $stmt->get_result();
	if (!$result) return null;
	return $result->fetch_assoc();
}

// Check user authorization
function auth_authenticate ($user, $password) {
	if (password_verify($password, $user['password_hash'])) {
		return true;
	}
	return false;
}

// Create user
function auth_register ($username, $email, $password) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	$stmt = $conn->prepare('INSERT INTO `users` (`username`, `email`, `password_hash`) VALUE (?, ?, ?)');
	$hash = password_hash($password, PASSWORD_DEFAULT);
	$stmt->bind_param('sss', $username, $email, $hash);
	return $stmt->execute();
}
