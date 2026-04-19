<?php
	session_start();

	// --- STRICT POST CSRF CHECK ---
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			$_SESSION['Sessionmsg'] = array('origin' => 'profile', 'type' => 'error', 'icon' => 'times-circle', 'expire' => 5000, 'message' => "Security validation failed (CSRF).");
			header("Location: profile.php"); die();
		}
	} else {
		header("Location: profile.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();

	$origin = isset($_POST['action']) ? $_POST['action'] : 'profile';

	function returnWithMsg($type, $icon, $expire, $message) {
		global $origin;
		$_SESSION['Sessionmsg'] = array(
			'origin' => $origin,
			'type' => $type,
			'icon' => $icon,
			'expire' => $expire,
			'message' => $message
		);
		header("Location: profile.php");
		die();
	}

	if (!isset($_SESSION['UserID'])) { returnWithMsg("error", "times-circle", 4500, "Invalid session. Please log in again."); }
	$active_user_ID = (int)$_SESSION['UserID'];

	// --- ACTIONS ---
	if ($origin === "changebasics") {
		$user_name = trim($_POST['user_name']);
		$user_mail = trim($_POST['user_mail']);
		
		if (empty($user_name) || empty($user_mail)) { returnWithMsg("error", "times-circle", 4500, "Username and Email are required."); }

		$stmt = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ? WHERE user_id = ?");
		$stmt->bind_param("ssi", $user_name, $user_mail, $active_user_ID);
		$stmt->execute();

		if ($stmt->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 4500, "Your profile details have been updated.");
		} else {
			returnWithMsg("success", "check-circle", 4500, "No changes were made.");
		}
		$stmt->close();

} elseif ($origin === "changepass") {
		$curr_pass = $_POST['user_currpass'];
		$new_pass = $_POST['user_newpass'];
		$confirm_pass = $_POST['user_confirmpass'] ?? '';

		if (empty($curr_pass) || empty($new_pass)) { returnWithMsg("error", "times-circle", 4500, "Both current and new passwords are required."); }
		
		// 1. Check if Confirm matches
		if ($new_pass !== $confirm_pass) { returnWithMsg("error", "times-circle", 4500, "Your new passwords did not match."); }

		// 2. Strict Strength Enforcement (Server-Side)
		if (strlen($new_pass) < 8 || !preg_match('/[A-Z]/', $new_pass) || !preg_match('/[a-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass) || !preg_match('/[\W_]/', $new_pass)) {
			returnWithMsg("error", "times-circle", 6000, "Password must be 8+ chars and contain at least one uppercase, lowercase, number, and symbol.");
		}

		// Verify current password
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
			
			returnWithMsg("success", "check-circle", 4500, "Your password has been changed successfully.");
		} else {
			returnWithMsg("error", "times-circle", 4500, "The current password you entered is incorrect.");
		}

	} elseif ($origin === "changetimezone") { // ... the rest of the file stays the same
		$timezone = $_POST['user_timezone'];
		
		$stmt = $db_connection->conn->prepare("UPDATE users SET user_timezone = ? WHERE user_id = ?");
		$stmt->bind_param("si", $timezone, $active_user_ID);
		$stmt->execute();
		
		if ($stmt->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 4500, "Your timezone preference has been saved.");
		} else {
			returnWithMsg("success", "check-circle", 4500, "No changes were made.");
		}
		$stmt->close();
	}
?>