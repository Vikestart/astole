<?php
	session_start();
	require "admin-functions.php";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			returnWithMsg('user', 'error', 'times-circle', 5000, "Security validation failed (CSRF).", "users.php");
		}
	} else {
		header("Location: users.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	if (!isset($_SESSION['UserID'])) { returnWithMsg('user', "error", "times-circle", 4500, "Invalid session.", "login.php"); }

	$active_user_id = (int)$_SESSION['UserID'];
	$stmt_auth = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
	$stmt_auth->bind_param("i", $active_user_id);
	$stmt_auth->execute();
	$active_user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
	$stmt_auth->close();

	if ($active_user_role == 3) { returnWithMsg($action, "error", "times-circle", 5000, "Permission denied.", "index.php"); }

	if ($action === "deleteuser" || $action === "deluser") {
		$del_user_id = (int)($_POST['u'] ?? $_POST['user_id'] ?? 0);
		
		if ($del_user_id === $active_user_id) { returnWithMsg($action, "error", "times-circle", 5000, "You cannot delete your own account.", "users.php"); }
		
		$stmt_tgt = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
		$stmt_tgt->bind_param("i", $del_user_id);
		$stmt_tgt->execute();
		$tgt_res = $stmt_tgt->get_result();
		if ($tgt_res->num_rows === 0) { returnWithMsg($action, "error", "times-circle", 5000, "User not found.", "users.php"); }
		$target_role = (int)$tgt_res->fetch_assoc()['user_role'];
		$stmt_tgt->close();

		if ($active_user_role == 2 && $target_role == 1) { returnWithMsg($action, "error", "times-circle", 5000, "Moderators cannot delete Administrators.", "users.php"); }

		$stmt = $db_connection->conn->prepare("DELETE FROM users WHERE user_id = ?");
		$stmt->bind_param("i", $del_user_id);
		$stmt->execute();
		$stmt->close();

		returnWithMsg($action, "success", "check-circle", 4500, "User has been permanently deleted.", "users.php", $db_connection->conn, 'User', "Deleted user ID: " . $del_user_id);

	} elseif ($action === "newuser") {
        // FIX 2: Look for 'user_name' instead of 'user_uid'
		$user_uid = trim($_POST['user_name'] ?? '');
		$user_mail = trim($_POST['user_mail'] ?? '');
		$submitted_role = (int)($_POST['user_role'] ?? 3);

		if (empty($user_uid) || empty($user_mail)) { returnWithMsg($action, "error", "times-circle", 5000, "Name and Email are required.", "users.php"); }
		if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) { returnWithMsg($action, "error", "times-circle", 5000, "Invalid email format.", "users.php"); }
        if ($active_user_role == 2 && $submitted_role == 1) { returnWithMsg($action, "error", "times-circle", 5000, "Moderators cannot create Administrators.", "users.php"); }
        if (empty($_POST['user_pass'])) { returnWithMsg($action, "error", "times-circle", 5000, "A password is required for new users.", "users.php"); }

        $stmt_chk = $db_connection->conn->prepare("SELECT user_id FROM users WHERE user_mail = ? OR user_uid = ?");
        $stmt_chk->bind_param("ss", $user_mail, $user_uid);
        $stmt_chk->execute();
        if ($stmt_chk->get_result()->num_rows > 0) { returnWithMsg($action, "error", "times-circle", 5000, "That Email or Username is already taken.", "users.php"); }
        $stmt_chk->close();

        $user_pass = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);
        $currtime = gmdate("Y-m-d H:i:s");

        $stmt = $db_connection->conn->prepare("INSERT INTO users (user_uid, user_mail, user_pass, user_role, user_registered) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $user_uid, $user_mail, $user_pass, $submitted_role, $currtime);
        $stmt->execute();
        $stmt->close();

        returnWithMsg($action, "success", "check-circle", 4500, "The user was successfully created.", "users.php", $db_connection->conn, 'User', "Created new user profile: " . $user_uid);

	} elseif ($action === "edituser") {
        $target_user_id = (int)($_POST['user_id'] ?? 0);
        $redirect_url = $target_user_id ? "edit-user.php?t=edit&u=$target_user_id" : "users.php";
        $submitted_role = (int)($_POST['user_role'] ?? 3);

        $stmt_tgt = $db_connection->conn->prepare("SELECT user_uid, user_mail, user_role, user_pass FROM users WHERE user_id = ?");
        $stmt_tgt->bind_param("i", $target_user_id);
        $stmt_tgt->execute();
        $tgt_data = $stmt_tgt->get_result()->fetch_assoc();
        $target_current_role = (int)$tgt_data['user_role'];
        $stmt_tgt->close();

        // FIX 2: Look for 'user_name' instead of 'user_uid'
        $user_uid = !empty($_POST['user_name']) ? trim($_POST['user_name']) : $tgt_data['user_uid'];
        $user_mail = !empty($_POST['user_mail']) ? trim($_POST['user_mail']) : $tgt_data['user_mail'];

        if (empty($user_uid) || empty($user_mail)) { returnWithMsg($action, "error", "times-circle", 5000, "Name and Email are required.", $redirect_url); }

        $stmt_chk = $db_connection->conn->prepare("SELECT user_id FROM users WHERE (user_mail = ? OR user_uid = ?) AND user_id != ?");
        $stmt_chk->bind_param("ssi", $user_mail, $user_uid, $target_user_id);
        $stmt_chk->execute();
        if ($stmt_chk->get_result()->num_rows > 0) { returnWithMsg($action, "error", "times-circle", 5000, "Email or Username already taken by another user.", $redirect_url); }
        $stmt_chk->close();

        $final_role = $target_current_role; 
        if ($target_user_id !== $active_user_id) { 
            if ($active_user_role == 1) { 
                $final_role = $submitted_role; 
            } elseif ($active_user_role == 2) {
                if ($target_current_role == 1) { returnWithMsg($action, "error", "times-circle", 5000, "Moderators cannot edit Administrators.", "users.php"); }
                if (in_array($submitted_role, [2, 3])) { $final_role = $submitted_role; }
            } else {
                returnWithMsg($action, "error", "times-circle", 5000, "Permission denied.", "users.php");
            }
        } 

        $user_pass = (!empty($_POST['user_pass'])) ? password_hash($_POST['user_pass'], PASSWORD_DEFAULT) : $tgt_data['user_pass'];

        $stmt = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ?, user_pass = ?, user_role = ? WHERE user_id = ?");
        $stmt->bind_param("sssii", $user_uid, $user_mail, $user_pass, $final_role, $target_user_id);
        $stmt->execute();
        $stmt->close();

        returnWithMsg($action, "success", "check-circle", 4500, "User profile updated successfully.", $redirect_url, $db_connection->conn, 'User', "Updated user profile for: " . $user_uid);
	}
?>