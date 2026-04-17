<?php
	// Start session and get database configuration
	session_start();
	// Prevent unauthorized remote submissions
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, 5000, "Security validation failed (CSRF).", false);
	}
	require "../db.php";

	// Define role constants
	const ROLE_ADMIN = 1;
	const ROLE_MODERATOR = 2;
	const ROLE_USER = 3;

	// Determine which action is to be performed on the page
	$action = ($_GET['a'] == 'del') ? 'deluser' : (isset($_POST['action']) ? $_POST['action'] : '');

	// Global DB connection for this script
	$db_connection = new DBConn();

	// Function for returning back to page with an error message
	// The $redirect_url parameter allows explicit redirection, otherwise it constructs based on action.
	// $target_user_id is used for redirects back to edit-user.php
	function returnWithMsg($type, $icon, $expire, $message, $redirect_url = false, $target_user_id = null) {
		global $action; // Use the global $action for the 'origin'
		
		// Set $_SESSION['Sessionmsg'] as an ASSOCIATIVE ARRAY
		$_SESSION['Sessionmsg'] = array(
			'origin' => $action, // Use $action as the origin for filtering messages
			'type' => $type,
			'icon' => $icon,
			'expire' => $expire,
			'message' => $message
		);
		
		if ($redirect_url !== false) { // Explicit redirect URL provided (overrides default)
			header("Location: " . $redirect_url);
		} else { // Determine redirect based on action and type
            if ($action === "edituser") {
                // For edit user, always redirect back to the specific edit page
                header("Location: edit-user.php?t=edit&u=" . ($target_user_id !== null ? $target_user_id : ''));
            } elseif ($action === "newuser") {
                // For new user: on error, stay on new user page; on success, go to users list
                if ($type === "error") { // If it's an error for a new user
                    header("Location: edit-user.php?t=new"); // Stay on new user page to fix
                } else { // If new user success
                    header("Location: users.php"); // Redirect to users list
                }
            } else { // Fallback for other actions like delete
                header("Location: users.php"); // Redirect to users list
            }
		}
		die();
	}

	// Control user account - Check for logged-in admin using UserID
	if (isset($_SESSION['UserID'])) {
		$current_user_id = (int)$_SESSION['UserID'];
		
		// Fetch current user's role and data for authorization
		$stmt_current_user = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
		if ($stmt_current_user === false) {
			error_log("Prepare current user role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg("error", "exclamation-triangle", 0, "Authorization check failed!", "index.php"); // Redirect to index or login
		}
		$stmt_current_user->bind_param("i", $current_user_id);
		$stmt_current_user->execute();
		$result_current_user = $stmt_current_user->get_result();
		if ($result_current_user->num_rows === 1) {
			$current_user_data = $result_current_user->fetch_assoc();
			$current_user_role = (int)$current_user_data['user_role'];
		} else {
			// User not found in DB, session invalid
			session_destroy();
			session_write_close();
			returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", "login.php");
		}
		$stmt_current_user->close();

		// Check if current user is admin or moderator for most actions
		if ($current_user_role !== ROLE_ADMIN && $current_user_role !== ROLE_MODERATOR) {
			returnWithMsg("error", "times-circle", 0, "You do not have permission to perform this action.", "index.php");
		}
	} else {
		// Not logged in or session expired
		returnWithMsg("error", "times-circle", 0, "You are not logged in or your session has expired.", "login.php");
	}

	// Require $_POST array to be populated for newuser/edituser actions
	if ($action === "newuser" || $action === "edituser") {
		if (!isset($_POST['user_name']) || !isset($_POST['user_mail']) || !isset($_POST['user_role'])) {
			returnWithMsg("error", "times-circle", 0, "Please input something into all the fields.", false, isset($_POST['user_id']) ? (int)$_POST['user_id'] : null);
		}
		$user_name = trim($_POST['user_name']);
		$user_mail = trim($_POST['user_mail']);
		$user_role = (int)$_POST['user_role']; // Get as integer
		$user_pass = isset($_POST['user_pass']) ? trim($_POST['user_pass']) : '';
		$hashed_pass = '';
		if (!empty($user_pass)) {
			$hashed_pass = password_hash($user_pass, PASSWORD_DEFAULT);
		}

		// Validation: Basic checks (more comprehensive validation can be added)
		if (strlen($user_name) < 3 || strlen($user_name) > 40 || !filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
			returnWithMsg("error", "times-circle", 0, "Invalid username or e-mail format.", false, isset($_POST['user_id']) ? (int)$_POST['user_id'] : null);
		}
	}

	// Process actions
	if ($action === "deluser") {
        $target_user_id = (int)$_GET['u'];
		
		// Prevent deleting self
		if ($target_user_id === $current_user_id) {
			returnWithMsg("error", "times-circle", 0, "You cannot delete your own account!", "users.php");
		}

		// Check if current user is admin and target user is not admin for deletion
		// Only an admin can delete another admin. A moderator cannot delete an admin.
		if ($current_user_role !== ROLE_ADMIN) {
			// Check target user's role
			$stmt_target_user_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
			if ($stmt_target_user_role === false) {
				error_log("Prepare target user role fetch failed for delete: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "Deletion authorization check failed!", "users.php");
			}
			$stmt_target_user_role->bind_param("i", $target_user_id);
			$stmt_target_user_role->execute();
			$result_target_user_role = $stmt_target_user_role->get_result();
			if ($result_target_user_role->num_rows === 1) {
				$target_user_data = $result_target_user_role->fetch_assoc();
				$target_user_role = (int)$target_user_data['user_role'];

				if ($target_user_role === ROLE_ADMIN || $current_user_role > $target_user_role) { // If target is admin OR current user has lower privilege (e.g. moderator deleting moderator)
					returnWithMsg("error", "times-circle", 0, "You do not have permission to delete this user.", "users.php");
				}
			} else {
				returnWithMsg("error", "times-circle", 0, "User to delete not found.", "users.php");
			}
			$stmt_target_user_role->close();
		}

		$stmt_delete = $db_connection->conn->prepare("DELETE FROM users WHERE user_id = ?");
        if ($stmt_delete === false) {
            error_log("Prepare delete user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
            returnWithMsg("error", "exclamation-triangle", 0, "User deletion preparation failed!", "users.php");
        }
		$stmt_delete->bind_param("i", $target_user_id);
		$stmt_delete->execute();

		if ($stmt_delete->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The user was successfully deleted.", "users.php");
		} else {
            error_log("Failed to delete user ID: " . $target_user_id . ". Affected rows: " . $stmt_delete->affected_rows . ". Error: " . $stmt_delete->error);
			returnWithMsg("error", "times-circle", 0, "Failed to delete the user or user not found.", "users.php");
		}
        $stmt_delete->close();

	} else if ($action === "newuser") {
		// New user creation
		// Check if current user is allowed to create users with the specified role
		// Only admins can create admins or moderators. Moderators can only create users.
		if ($current_user_role === ROLE_MODERATOR && $user_role !== ROLE_USER) {
			returnWithMsg("error", "times-circle", 0, "As a moderator, you can only create 'User' accounts.", false);
		}

		// Check for existing username or email before inserting
		$stmt_check_exist = $db_connection->conn->prepare("SELECT user_id FROM users WHERE user_uid = ? OR user_mail = ?");
		if ($stmt_check_exist === false) {
			error_log("Prepare check user existence failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg("error", "exclamation-triangle", 0, "User existence check failed!", false);
		}
		$stmt_check_exist->bind_param("ss", $user_name, $user_mail);
		$stmt_check_exist->execute();
		$result_check_exist = $stmt_check_exist->get_result();
		if ($result_check_exist->num_rows > 0) {
			returnWithMsg("error", "times-circle", 0, "Username or e-mail already exists.", false);
		}
		$stmt_check_exist->close();

		// Insert new user
		$stmt_insert = $db_connection->conn->prepare("INSERT INTO users (user_uid, user_pass, user_mail, user_role) VALUES (?, ?, ?, ?)");
		if ($stmt_insert === false) {
			error_log("Prepare insert user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg("error", "exclamation-triangle", 0, "User creation preparation failed!", false);
		}
		// Corrected: user_role is integer (i)
		$stmt_insert->bind_param("sssi", $user_name, $hashed_pass, $user_mail, $user_role); // 'i' for user_role

		$stmt_insert->execute();

		if ($stmt_insert->affected_rows === 1) {
			$new_user_id = $db_connection->conn->insert_id; // Get the ID of the newly inserted user
			returnWithMsg("success", "check-circle", 0, "The user was successfully added.", false, $new_user_id); // Redirect to edit page of new user
		} else {
            error_log("Failed to insert new user. Affected rows: " . $stmt_insert->affected_rows . ". Error: " . $stmt_insert->error);
			returnWithMsg("error", "times-circle", 0, "Failed to create user. Database error.", false);
		}
		$stmt_insert->close();

	} else if ($action === "edituser") {
		$target_user_id = (int)$_POST['user_id'];
		
		// Prevent self-demotion or editing higher roles if not admin
		if ($target_user_id === $current_user_id && $user_role !== ROLE_ADMIN && $current_user_role === ROLE_ADMIN) {
			returnWithMsg("error", "times-circle", 0, "You cannot change your own role from Administrator to a lower role.", false, $target_user_id);
		}
		// If current user is not admin, prevent them from changing roles or editing higher roles
		if ($current_user_role !== ROLE_ADMIN) {
			// Check target user's current role before update for authorization
			$stmt_target_user_curr_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
			if ($stmt_target_user_curr_role === false) {
				error_log("Prepare target user current role fetch failed for edit: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "User edit authorization check failed!", false, $target_user_id);
			}
			$stmt_target_user_curr_role->bind_param("i", $target_user_id);
			$stmt_target_user_curr_role->execute();
			$result_target_user_curr_role = $stmt_target_user_curr_role->get_result();
			if ($result_target_user_curr_role->num_rows === 1) {
				$target_user_curr_role = (int)$result_target_user_curr_role->fetch_assoc()['user_role'];

				// Prevent changing role if not admin
				if ($user_role !== $target_user_curr_role) {
					returnWithMsg("error", "times-circle", 0, "You do not have permission to change user roles.", false, $target_user_id);
				}
				// Prevent editing higher roles or same level if moderator trying to edit another moderator/admin
				if ($current_user_role > $target_user_curr_role) { // e.g. Moderator (2) cannot edit Admin (1)
					returnWithMsg("error", "times-circle", 0, "You do not have permission to edit this user's details.", false, $target_user_id);
				}
				// Additionally, a moderator (ROLE_MODERATOR) should not be able to edit other moderator accounts.
				if ($current_user_role === ROLE_MODERATOR && $target_user_curr_role === ROLE_MODERATOR && $target_user_id !== $current_user_id) {
					returnWithMsg("error", "times-circle", 0, "Moderators cannot edit other moderator accounts.", false, $target_user_id);
				}

			} else {
				returnWithMsg("error", "times-circle", 0, "User to edit not found.", "users.php");
			}
			$stmt_target_user_curr_role->close();
		}


		// Update user details
		$stmt_update = null;
		if (empty($hashed_pass)) {
			// Update without password change
			$stmt_update = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ?, user_role = ? WHERE user_id = ?");
			if ($stmt_update === false) {
				error_log("Prepare update user (no pass) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "User update preparation failed!", false, $target_user_id);
			}
			// Corrected: user_role is integer (i)
			$stmt_update->bind_param("ssii", $user_name, $user_mail, $user_role, $target_user_id); // 'i' for user_role
		} else {
			// Update with password change
			$stmt_update = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_pass = ?, user_mail = ?, user_role = ? WHERE user_id = ?");
			if ($stmt_update === false) {
				error_log("Prepare update user (with pass) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "User update preparation failed!", false, $target_user_id);
			}
			// Corrected: user_role is integer (i)
			$stmt_update->bind_param("sssii", $user_name, $hashed_pass, $user_mail, $user_role, $target_user_id); // 'i' for user_role
		}
		
		$stmt_update->execute();

		if ($stmt_update->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The user was successfully updated with the new details.", false, $target_user_id);
		} else if ($stmt_update->affected_rows === 0) {
			returnWithMsg("success", "check-circle", 0, "No changes were made to the user.", false, $target_user_id);
		} else {
			error_log("Failed to update user ID: " . $target_user_id . ". Affected rows: " . $stmt_update->affected_rows . ". Error: " . $stmt_update->error);
			returnWithMsg("error", "times-circle", 0, "Failed to update the user. Database error or multiple rows affected.", false, $target_user_id);
		}
		$stmt_update->close();
	} else {
		// No valid action provided or not allowed
		returnWithMsg("error", "times-circle", 0, "Invalid action.", "users.php");
	}

	// Close DB connection
	$db_connection->conn->close();
?>