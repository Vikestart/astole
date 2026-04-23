<?php
	session_start();
	require "admin-functions.php";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			returnWithMsg('profile', 'error', 'times-circle', 5000, "Security validation failed (CSRF).", "profile.php");
		}
	} else {
		header("Location: profile.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();
	$origin = isset($_POST['action']) ? $_POST['action'] : 'profile';

	if (!isset($_SESSION['UserID'])) { returnWithMsg($origin, "error", "times-circle", 4500, "Invalid session. Please log in again.", "login.php"); }
	$active_user_ID = (int)$_SESSION['UserID'];

	if ($origin === "changebasics") {
		$user_mail = filter_var($_POST['user_mail'], FILTER_SANITIZE_EMAIL);
		if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) { returnWithMsg($origin, "error", "times-circle", 4500, "Invalid email address format.", "profile.php"); }

		$stmt_chk = $db_connection->conn->prepare("SELECT user_id FROM users WHERE user_mail = ? AND user_id != ?");
		$stmt_chk->bind_param("si", $user_mail, $active_user_ID);
		$stmt_chk->execute();
		if ($stmt_chk->get_result()->num_rows > 0) { returnWithMsg($origin, "error", "times-circle", 4500, "That email address is already in use.", "profile.php"); }
		$stmt_chk->close();

		$stmt_up = $db_connection->conn->prepare("UPDATE users SET user_mail = ? WHERE user_id = ?");
		$stmt_up->bind_param("si", $user_mail, $active_user_ID);
		$stmt_up->execute();
		
		returnWithMsg($origin, "success", "check-circle", 4500, "Your profile details were updated.", "profile.php", $db_connection->conn, 'Profile', 'Updated their personal profile details.');

	} elseif ($origin === "changepassword") {
		$curr_pass = $_POST['user_pass_curr'];
		$new_pass = $_POST['user_pass_new'];
		$new_pass_conf = $_POST['user_pass_conf'];

		if (empty($curr_pass) || empty($new_pass) || empty($new_pass_conf)) { returnWithMsg($origin, "error", "times-circle", 4500, "All password fields are required.", "profile.php"); }
		if ($new_pass !== $new_pass_conf) { returnWithMsg($origin, "error", "times-circle", 4500, "The new passwords do not match.", "profile.php"); }
		if (strlen($new_pass) < 8 || !preg_match('/[A-Z]/', $new_pass) || !preg_match('/[a-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[\W_]/', $new_pass)) {
			returnWithMsg($origin, "error", "times-circle", 6000, "Password must be 8+ chars and contain at least one uppercase, lowercase, number, and symbol.", "profile.php");
		}

		$stmt = $db_connection->conn->prepare("SELECT user_pass FROM users WHERE user_id = ?");
		$stmt->bind_param("i", $active_user_ID);
		$stmt->execute();
		$db_pass = $stmt->get_result()->fetch_assoc()['user_pass'];
		$stmt->close();

		if (password_verify($curr_pass, $db_pass)) {
			$hashed_new = password_hash($new_pass, PASSWORD_DEFAULT);
			$stmt_up = $db_connection->conn->prepare("UPDATE users SET user_pass = ? WHERE user_id = ?");
			$stmt_up->bind_param("si", $hashed_new, $active_user_ID);
			$stmt_up->execute();
			returnWithMsg($origin, "success", "check-circle", 4500, "Your password has been changed successfully.", "profile.php", $db_connection->conn, 'Profile', 'Changed their account password.');
		} else {
			returnWithMsg($origin, "error", "times-circle", 4500, "The current password you entered is incorrect.", "profile.php");
		}

	} elseif ($origin === "changetimezone") {
		$timezone = $_POST['user_timezone'];
		$stmt = $db_connection->conn->prepare("UPDATE users SET user_timezone = ? WHERE user_id = ?");
		$stmt->bind_param("si", $timezone, $active_user_ID);
		$stmt->execute();
		returnWithMsg($origin, "success", "check-circle", 4500, "Your timezone has been updated.", "profile.php", $db_connection->conn, 'Profile', "Updated their timezone to: {$timezone}");
	}
?>