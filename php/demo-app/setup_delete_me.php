<?php
/*
 * StrongMonkey
 * https://github.com/GramThanos/StrongMonkey
 *
 * Setup database
 */

// Info
include(dirname(__FILE__) . '/includes/config.php');

// -------------------------------------------------------
// Change these credentials for your database
define('ADMIN_DATABASE_USER', 'admin');
define('ADMIN_DATABASE_PASSWORD', '123!@#qweQWE');
// -------------------------------------------------------

// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connect to databasse with admin
$conn = new mysqli(APP_DATABASE_HOST, ADMIN_DATABASE_USER, ADMIN_DATABASE_PASSWORD);
if ($conn->connect_errno) {
	die('Failed to connect to the database as admin');
}

// Create database
$result = $conn->query('CREATE DATABASE IF NOT EXISTS `' . APP_DATABASE_NAME . '`;');
if (!$result) {
	die('Failed to create database.');
}

// Create user only for that database
$result = $conn->query('CREATE USER IF NOT EXISTS `' . APP_DATABASE_USER . '`@`' . APP_DATABASE_HOST . '` IDENTIFIED BY \'' . APP_DATABASE_PASSWORD . '\';');
if (!$result) {
	die('Failed to create database user.');
}
$result = $conn->query('GRANT ALL ON `' . APP_DATABASE_NAME . '`.* TO `' . APP_DATABASE_USER . '`@`' . APP_DATABASE_HOST . '`;');
if (!$result) {
	die('Failed to give user permisions.');
}

$conn2 = new mysqli(APP_DATABASE_HOST, APP_DATABASE_USER, APP_DATABASE_PASSWORD, APP_DATABASE_NAME);
if ($conn->connect_errno) {
	die('Failed to connect to the database as app user');
}

// Create users table
$result = $conn2->query('
	CREATE TABLE IF NOT EXISTS `users` (
		`id` int NOT NULL AUTO_INCREMENT,
		`username` varchar(32) NOT NULL,
		`email` varchar(255) NOT NULL,
		`password_hash` varchar(255) NOT NULL,
		PRIMARY KEY (`id`)
	) DEFAULT CHARSET=utf8;
');
if (!$result) {
	die('Failed to create users table.');
}

// Create qrcode_sessions table
$result = $conn2->query('DELETE FROM `qrcode_sessions`;');
$result = $conn2->query('DROP TABLE `qrcode_sessions`;');
$result = $conn2->query('
	CREATE TABLE IF NOT EXISTS `qrcode_sessions` (
		`code` varchar(64) NOT NULL,
		`username` varchar(32) NOT NULL,
		`expiration` datetime NOT NULL,
		`status` varchar(32) NOT NULL,
		`device_id` varchar(64) NOT NULL,
		`device_name` varchar(256) NOT NULL,
		PRIMARY KEY (`code`)
	) DEFAULT CHARSET=utf8;
');
if (!$result) {
	die('Failed to create users table.');
}

echo 'DONE';
