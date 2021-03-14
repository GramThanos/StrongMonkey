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

// Create QR code entry
function auth_qrcode_create_entry ($username, $seconds=180) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	$expiration = date('Y-m-d H:i:s', (time() + $seconds));
	// Client IP
	$device_id = $_SERVER['REMOTE_ADDR'];
	$device_name = $_SERVER['HTTP_USER_AGENT']; // This is dirty & can be manipulated. It is better in production to parse it and identify the browser, maybe using the Browser.php project
	// Generate Code
	$code = sha1($username . '-UNIPI-' . date('Y-m-d H:i:s'));
	$status = 'pending';
	$stmt = $conn->prepare('INSERT INTO `qrcode_sessions` (`code`, `username`, `expiration`, `status`, `device_id`, `device_name`) VALUE (?, ?, ?, ?, ?, ?)');
	$stmt->bind_param('ssssss', $code, $username, $expiration, $status, $device_id, $device_name);
	if (!$stmt->execute()) {
		return null;
	}
	return $code;
}

// Get QR code entry
function auth_qrcode_get_entry ($code, $status=null) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	// Delete expired sessions
	$stmt = $conn->prepare('DELETE FROM qrcode_sessions WHERE expiration < ?');
	$date = date('Y-m-d H:i:s', time());
	$stmt->bind_param('s', $date);
	$stmt->execute();
	// Get this session
	if (!$status) {
		$stmt = $conn->prepare('SELECT code, username, expiration, status, device_id, device_name FROM qrcode_sessions WHERE code=?');
		$stmt->bind_param('s', $code);
	} else {
		$stmt = $conn->prepare('SELECT code, username, expiration, status, device_id, device_name FROM qrcode_sessions WHERE code=? AND status=?');
		$stmt->bind_param('ss', $code, $status);
	}
	$stmt->execute();
	$result = $stmt->get_result();
	if (!$result) return null;
	return $result->fetch_assoc();
}

// Update QR code entry
function auth_qrcode_update_entry ($code, $status) {
	$conn = auth_getDbConn();
	if (!$conn) return null;
	// Get this session
	$stmt = $conn->prepare('UPDATE qrcode_sessions SET status=? WHERE code=?');
	$stmt->bind_param('ss', $status, $code);
	$stmt->execute();
}
