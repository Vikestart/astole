<?php
	// Start session and get database configuration
	session_start();
	require "../db.php";

	// Determine which action is to be performed on the page
	$action = ($_GET['a'] == 'del') ? 'deluser' : (isset($_POST['action']) ? $_POST['action'] : '');

	// Global DB connection for this script
	$db_connection = new DBConn();

	// Function for returning back to page with an error message
	// The $redirect_url parameter allows explicit redirection, otherwise it constructs based on action.
	// $target_user_id is used for redirects back to edit-user.php
	function returnWithMsg($type, $icon, $expire, $message, $redirect_url = false, $target_user_id = null) {
		global $action;
		$_SESSION['Sessionmsg'] = array($action, $type, $icon, $expire, $message);
		
		if ($redirect_url !== false) { // Explicit redirect URL provided (overrides default)
			header("Location: " . $redirect_url);
		} else { // Determine redirect based on action and type
            if ($action === "edituser") {
                // For edit user, always redirect back to the specific edit page
                header("Location: edit-user.php?t=edit&u=" . ($target_user_id !== null ? $target_user_id : ''));
            } elseif ($action === "newuser") {
                // For new user: on error, stay on new user page; on success, go to users list
                if ($type === "error") { // If it's an error for a new user
                    header("Location: edit-user.php?t=new");
                } else { // If it's a success for a new user, go to users list
                    header("Location: users.php");
                }
            } elseif ($action === "deluser") { // For delete user, always redirect to users list
                header("Location: users.php");
            } else { // Fallback for unexpected actions
                header("Location: users.php");
            }
		}
		die();
	}

	// Control user account - Check for logged-in admin using UserID
	if (isset($_SESSION['UserID'])) {
		$current_logged_in_user_id = $_SESSION['UserID'];

        // Fetch the role of the currently logged-in user
        $stmt_current_user_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
        if ($stmt_current_user_role === false) {
            error_log("Prepare current user role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
            returnWithMsg("error", "exclamation-triangle", 0, "Database error: Could not verify your role.", false, (isset($_POST['user_id']) ? $_POST['user_id'] : null));
        }
        $stmt_current_user_role->bind_param("i", $current_logged_in_user_id);
        $stmt_current_user_role->execute();
        $result_current_user_role = $stmt_current_user_role->get_result();
        $current_logged_in_user_data = $result_current_user_role->fetch_assoc();
        $current_logged_in_user_role = $current_logged_in_user_data['user_role'];
        $stmt_current_user_role->close();

        // If the action is not 'deluser' and the user is not an admin, they cannot proceed with any user editing
        if ($action !== "deluser" && $current_logged_in_user_role !== 'admin') {
            returnWithMsg("error", "times-circle", 0, "You do not have permission to edit user details.", false, (isset($_POST['user_id']) ? $_POST['user_id'] : null));
        }
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", "login.php");
	}

	// Require $_POST array to be populated for new/edit actions
	if ($action !== "deluser" && count($_POST) === 0) {
		returnWithMsg("error", "times-circle", 0, "Invalid form submission.", false);
	}

	// Validate and sanitize input data
	function validate($data) {
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

	$user_name = isset($_POST['user_name']) ? validate($_POST['user_name']) : '';
	$user_pass = isset($_POST['user_pass']) ? $_POST['user_pass'] : ''; // Password will be hashed, so no htmlspecialchars here
	$user_mail = isset($_POST['user_mail']) ? validate($_POST['user_mail']) : '';
	$user_role = isset($_POST['user_role']) ? validate($_POST['user_role']) : 'user'; // Default to 'user' role

	$target_user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : null;

	if ($action === "deluser") {
		// Only admins can delete users
        if ($current_logged_in_user_role !== 'admin') {
            returnWithMsg("error", "times-circle", 0, "You do not have permission to delete users.", "users.php");
        }

        // The user being deleted is passed via GET parameter 'u'
        $user_id_to_delete = isset($_GET['u']) ? (int)$_GET['u'] : null;
        if ($user_id_to_delete === null) {
            returnWithMsg("error", "times-circle", 0, "No user specified for deletion.", "users.php");
        }

        // Prevent admin from deleting their own account
        if ($user_id_to_delete == $current_logged_in_user_id) {
            returnWithMsg("error", "times-circle", 0, "You cannot delete your own account.", "users.php");
        }

		$stmt_fetch_user_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
		if ($stmt_fetch_user_role === false) {
			error_log("Prepare delete user role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg("error", "exclamation-triangle", 0, "Database error during deletion.", "users.php");
		}
		$stmt_fetch_user_role->bind_param("i", $user_id_to_delete);
		$stmt_fetch_user_role->execute();
		$result_user_role = $stmt_fetch_user_role->get_result();

		if ($result_user_role->num_rows === 1) {
			$user_data = $result_user_role->fetch_assoc();
			$target_user_role = $user_data['user_role'];

			// An admin cannot delete another admin account unless they are the super-admin (or only one admin is left)
			// This logic can be enhanced based on specific requirements for "super-admin"
			// For now, let's assume an admin can delete other admins if there's more than one admin left.
			// Let's add a check that you cannot delete the LAST admin.
			$stmt_count_admins = $db_connection->conn->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE user_role = 'admin'");
			$stmt_count_admins->execute();
			$result_count_admins = $stmt_count_admins->get_result();
			$admin_count_row = $result_count_admins->fetch_assoc();
			$admin_count = $admin_count_row['admin_count'];
			$stmt_count_admins->close();

			if ($target_user_role === 'admin' && $admin_count <= 1) {
				returnWithMsg("error", "times-circle", 0, "Cannot delete the last administrator account.", "users.php");
			}

			$stmt_delete_user = $db_connection->conn->prepare("DELETE FROM users WHERE user_id = ?");
			if ($stmt_delete_user === false) {
				error_log("Prepare delete user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "Database error: Could not delete user.", "users.php");
			}
			$stmt_delete_user->bind_param("i", $user_id_to_delete);
			$stmt_delete_user->execute();

			if ($stmt_delete_user->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "The user was successfully deleted.", "users.php");
			} else {
				error_log("Failed to delete user ID: " . $user_id_to_delete . ". Affected rows: " . $stmt_delete_user->affected_rows . ". Error: " . $stmt_delete_user->error);
				returnWithMsg("error", "times-circle", 0, "Failed to delete the user. Database error or user not found.", "users.php");
			}
			$stmt_delete_user->close();

		} else {
			returnWithMsg("error", "times-circle", 0, "User not found for deletion.", "users.php");
		}
		$stmt_fetch_user_role->close();

	} else if (isset($action) && $action === "newuser") {
		// Only admins can create new users
        if ($current_logged_in_user_role !== 'admin') {
            returnWithMsg("error", "times-circle", 0, "You do not have permission to create new users.", false);
        }

		// Validate all fields for new user creation
		if (empty($user_name) || empty($user_pass) || empty($user_mail) || empty($user_role)) {
			returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
		}

		// Hash the password for new user
		$hashed_pass = password_hash($user_pass, PASSWORD_DEFAULT);

		// Insert new user into database
		$stmt_insert = $db_connection->conn->prepare("INSERT INTO users (user_uid, user_pass, user_mail, user_role) VALUES (?, ?, ?, ?)");
		if ($stmt_insert === false) {
			error_log("Prepare insert user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg("error", "exclamation-triangle", 0, "User creation preparation failed!", false);
		}
		$stmt_insert->bind_param("ssss", $user_name, $hashed_pass, $user_mail, $user_role);
		$stmt_insert->execute();

		if ($stmt_insert->affected_rows === 1) {
			$user_id = $db_connection->conn->insert_id; // Get the ID of the newly inserted user
			returnWithMsg("success", "check-circle", 0, "The user was successfully added.", false, $user_id);
		} else {
            error_log("Failed to insert new user. Error: " . $stmt_insert->error);
			returnWithMsg("error", "times-circle", 0, "Failed to add the user. Please check for duplicate username/email.", false);
		}
		$stmt_insert->close();

	} else if (isset($action) && $action === "edituser") {
		// Only admins can edit users
        if ($current_logged_in_user_role !== 'admin') {
            returnWithMsg("error", "times-circle", 0, "You do not have permission to edit user details.", false, $target_user_id);
        }

		// Check if target user ID is provided
		if ($target_user_id === null) {
			returnWithMsg("error", "times-circle", 0, "No target user specified for editing.", false, $target_user_id);
		}
        
        // Fetch the original role of the target user
        $stmt_target_user_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
        if ($stmt_target_user_role === false) {
            error_log("Prepare target user role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
            returnWithMsg("error", "exclamation-triangle", 0, "Database error: Could not verify target user's role.", false, $target_user_id);
        }
        $stmt_target_user_role->bind_param("i", $target_user_id);
        $stmt_target_user_role->execute();
        $result_target_user_role = $stmt_target_user_role->get_result();
        $target_user_data = $result_target_user_role->fetch_assoc();
        $target_user_original_role = $target_user_data['user_role'];
        $stmt_target_user_role->close();

        // --- Role Change Validation ---
        // 1. Prevent a user from changing their OWN role
        if ($target_user_id == $current_logged_in_user_id) {
            if ($user_role !== $target_user_original_role) {
                returnWithMsg("error", "times-circle", 0, "You cannot change your own role.", false, $target_user_id);
            }
        }
        // 2. Prevent an admin from changing the LAST admin's role
        if ($target_user_original_role === 'admin' && $user_role !== 'admin') { // If trying to change an admin to non-admin
            $stmt_count_admins = $db_connection->conn->prepare("SELECT COUNT(*) AS admin_count FROM users WHERE user_role = 'admin'");
            $stmt_count_admins->execute();
            $result_count_admins = $stmt_count_admins->get_result();
            $admin_count_row = $result_count_admins->fetch_assoc();
            $admin_count = $admin_count_row['admin_count'];
            $stmt_count_admins->close();

            if ($admin_count <= 1 && $target_user_id == $current_logged_in_user_id) { // This means the admin is trying to demote themselves AND they are the last admin
                 returnWithMsg("error", "times-circle", 0, "You cannot demote the last administrator account.", false, $target_user_id);
            } else if ($admin_count <= 1 && $target_user_id != $current_logged_in_user_id && $target_user_original_role === 'admin') { // Admin trying to demote another admin, and that other admin is the last one
                returnWithMsg("error", "times-circle", 0, "Cannot demote the last administrator account.", false, $target_user_id);
            }
        }


		// Hash password only if it's provided (i.e., user wants to change it)
		$hashed_pass = '';
		if (!empty($user_pass)) {
			$hashed_pass = password_hash($user_pass, PASSWORD_DEFAULT);
		}

		// Build the update query based on whether password is being changed
		$sql_update = "";
		if (empty($user_pass)) {
			$stmt_update = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ?, user_role = ? WHERE user_id = ?");
			if ($stmt_update === false) {
				error_log("Prepare update user (no pass) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "User update preparation failed!", false, $target_user_id);
			}
			$stmt_update->bind_param("sssi", $user_name, $user_mail, $user_role, $target_user_id);
		} else {
			$stmt_update = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_pass = ?, user_mail = ?, user_role = ? WHERE user_id = ?");
			if ($stmt_update === false) {
				error_log("Prepare update user (with pass) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg("error", "exclamation-triangle", 0, "User update preparation failed!", false, $target_user_id);
			}
			$stmt_update->bind_param("ssssi", $user_name, $hashed_pass, $user_mail, $user_role, $target_user_id);
		}
		
        $stmt_update->execute();

		if ($stmt_update->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The user was successfully updated with the new details.", false, $target_user_id);
		} else if ($stmt_update->affected_rows === 0) {
            // If affected_rows is 0, it means no actual change was made (e.g., submitted data is identical to current data)
			returnWithMsg("success", "check-circle", 0, "No changes were made to the user.", false, $target_user_id);
		} else {
			error_log("Failed to update user ID: " . $target_user_id . ". Affected rows: " . $stmt_update->affected_rows . ". Error: " . $stmt_update->error);
			returnWithMsg("error", "times-circle", 0, "Failed to update the user. Database error or multiple rows affected.", false, $target_user_id);
		}
		$stmt_update->close();
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid form!", "users.php");
	}
?>