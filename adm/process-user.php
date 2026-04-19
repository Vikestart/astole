<?php
	session_start();

	// --- STRICT POST CSRF CHECK ---
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			returnWithMsg("error", "times-circle", 5000, "Security validation failed (CSRF).", false);
		}
	} else {
		header("Location: users.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();

	$action = isset($_POST['action']) ? $_POST['action'] : '';

	function returnWithMsg($type, $icon, $expire, $message, $redirect_url = false, $target_user_id = null) {
		global $action;
		$_SESSION['Sessionmsg'] = array('origin' => $action, 'type' => $type, 'icon' => $icon, 'expire' => $expire, 'message' => $message);
		if ($redirect_url === false) {
			header("Location: " . ($target_user_id ? "edit-user.php?t=edit&u=$target_user_id" : "users.php"));
		} else {
			header("Location: " . $redirect_url);
		}
		die();
	}

	if (!isset($_SESSION['UserID'])) { returnWithMsg("error", "times-circle", 4500, "Invalid session.", "login.php"); }

	// --- FETCH ACTIVE USER ROLE FOR PERMISSION ENFORCEMENT ---
	$active_user_id = (int)$_SESSION['UserID'];
	$stmt_active = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
	$stmt_active->bind_param("i", $active_user_id);
	$stmt_active->execute();
	$active_user_role = (int)$stmt_active->get_result()->fetch_assoc()['user_role'];
	$stmt_active->close();

	// NEW: Instantly reject any processing if the user is a Standard User (Role 3)
	if ($active_user_role == 3) {
		returnWithMsg("error", "times-circle", 5000, "Permission denied. You can only view users.", "users.php");
	}

	// --- ACTIONS ---
	if ($action === "deluser") {
		$target_user_id = (isset($_POST['u'])) ? (int)$_POST['u'] : 0;
		if ($target_user_id === 0) { returnWithMsg("error", "times-circle", 4500, "No user specified.", "users.php"); }
		
		// Anti Self-Lockout
		if ($target_user_id === $active_user_id) { returnWithMsg("error", "times-circle", 5000, "You cannot delete your own account.", "users.php"); }

		// Fetch target user's role to prevent Mod deleting Admin
		$stmt_tgt = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
		$stmt_tgt->bind_param("i", $target_user_id);
		$stmt_tgt->execute();
		$target_role = (int)$stmt_tgt->get_result()->fetch_assoc()['user_role'];
		$stmt_tgt->close();

		if ($active_user_role == 2 && $target_role == 1) { returnWithMsg("error", "times-circle", 5000, "Moderators cannot delete Administrators.", "users.php"); }
		if ($active_user_role == 3) { returnWithMsg("error", "times-circle", 5000, "Permission denied.", "users.php"); }

		$stmt = $db_connection->conn->prepare("DELETE FROM users WHERE user_id = ?");
		$stmt->bind_param("i", $target_user_id);
		$stmt->execute();
		returnWithMsg("success", "check-circle", 4500, "User successfully deleted.", "users.php");

	} else {
		// New User / Edit User
		$user_name = trim($_POST['user_name']);
		$user_mail = trim($_POST['user_mail']);
		$submitted_role = (int)$_POST['user_role'];

		if (empty($user_name) || empty($user_mail)) { returnWithMsg("error", "times-circle", 4500, "Name and Email are required.", false, $_POST['user_id'] ?? null); }

		if ($action === "newuser") {
			// Hierarchy Enforcement for New Users
			$final_role = 3; // Default
			if ($active_user_role == 1) { $final_role = $submitted_role; } 
			elseif ($active_user_role == 2 && in_array($submitted_role, [2, 3])) { $final_role = $submitted_role; } 
			else { returnWithMsg("error", "times-circle", 5000, "You do not have permission to create users.", "users.php"); }

			$user_pass = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);
			$stmt = $db_connection->conn->prepare("INSERT INTO users (user_uid, user_mail, user_pass, user_role) VALUES (?, ?, ?, ?)");
			$stmt->bind_param("sssi", $user_name, $user_mail, $user_pass, $final_role);
			$stmt->execute();
			returnWithMsg("success", "check-circle", 4500, "User created.", "users.php");

		} elseif ($action === "edituser") {
			$target_user_id = (int)$_POST['user_id'];

			// Fetch target user's original role
			$stmt_tgt = $db_connection->conn->prepare("SELECT user_role, user_pass FROM users WHERE user_id = ?");
			$stmt_tgt->bind_param("i", $target_user_id);
			$stmt_tgt->execute();
			$tgt_data = $stmt_tgt->get_result()->fetch_assoc();
			$target_current_role = (int)$tgt_data['user_role'];
			$stmt_tgt->close();

			// Hierarchy Enforcement for Editing
			$final_role = $target_current_role; // Default: No change
			
			if ($target_user_id !== $active_user_id) { // If NOT editing self...
				if ($active_user_role == 1) { 
					$final_role = $submitted_role; // Admins can change anyone
				} elseif ($active_user_role == 2) {
					if ($target_current_role == 1) { returnWithMsg("error", "times-circle", 5000, "Moderators cannot edit Administrators.", "users.php"); }
					if (in_array($submitted_role, [2, 3])) { $final_role = $submitted_role; } // Mods can set to 2 or 3
				} else {
					returnWithMsg("error", "times-circle", 5000, "Permission denied.", "users.php");
				}
			} // Note: If target IS self, it ignores submitted_role and keeps $final_role as $target_current_role.

			// Handle Password
			$user_pass = (!empty($_POST['user_pass'])) ? password_hash($_POST['user_pass'], PASSWORD_DEFAULT) : $tgt_data['user_pass'];

			$stmt = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ?, user_pass = ?, user_role = ? WHERE user_id = ?");
			$stmt->bind_param("sssii", $user_name, $user_mail, $user_pass, $final_role, $target_user_id);
			$stmt->execute();
			
			returnWithMsg("success", "check-circle", 4500, "User updated successfully.", false, $target_user_id);
		}
	}
?>