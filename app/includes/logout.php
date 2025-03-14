<?php
// app/includes/logout.php

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
	session_start();
}

require_once(__DIR__ . '/../config/database.php');

// Check if "logout" parameter is in the URL or if script is 'logout.php'
if (isset($_GET['logout']) || basename($_SERVER['PHP_SELF']) == 'logout.php') {
	global $conn;

	// Save user ID before destroying session
	$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

	// Handle "remember me" feature if cookie exists
	if (isset($_COOKIE['remember_me'])) {
		// Extract values from the cookie
		list($cookie_user_id, $token, $hash) = explode(':', $_COOKIE['remember_me']);

		// Remove token from database
		$stmt = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = :user_id AND token = :token");
		$stmt->bindParam(':user_id', $cookie_user_id, PDO::PARAM_INT);
		$stmt->bindParam(':token', $token);
		$stmt->execute();

		// Delete "remember_me" cookie
		setcookie('remember_me', '', time() - 3600, '/', '', isset($_SERVER['HTTPS']), true);
	}

	// Reset session variables and destroy session
	$_SESSION = array();
	session_destroy();

	header("Location: /");
	exit;
}