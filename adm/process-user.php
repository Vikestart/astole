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
                } else { // If it's a success for a new user
                    header("Location: users.php");
                }
            } elseif ($action === "deluser") {
                // For delete user: always redirect to the users list
                header("Location: users.php");
            } else {
                // Fallback for any unhandled actions or general errors
                header("Location: users.php");
            }
		}
		die();
	}

	// Control user account - Check for logged-in admin using UserID
	if (isset($_SESSION['UserID'])) {
		$current_admin_id = $_SESSION['UserID'];
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", "login.php");
	}

    // Fetch current admin's role to check permissions
    $stmt_admin_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
    if ($stmt_admin_role === false) {
        error_log("Prepare admin role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
        returnWithMsg("error", "exclamation-triangle", 0, "Failed to retrieve admin role.", "users.php");
    }
    $stmt_admin_role->bind_param("i", $current_admin_id);
    $stmt_admin_role->execute();
    $result_admin_role = $stmt_admin_role->get_result();
    if ($result_admin_role->num_rows === 1) {
        $admin_data = $result_admin_role->fetch_assoc();
        $admin_role = $admin_data['user_role'];
    } else {
        // Admin user not found, something is seriously wrong with session/db.
        session_destroy();
        session_write_close();
        returnWithMsg("error", "times-circle", 0, "Admin account not found. Please log in again.", "login.php");
    }
    $stmt_admin_role->close();


	// Handle Delete User action
	if ($action === "deluser") {
        if (!isset($_GET['u'])) {
            returnWithMsg("error", "times-circle", 0, "No user specified for deletion.", "users.php");
        }
        $target_user_id = $_GET['u'];

        // Prevent admin from deleting themselves
        if ($target_user_id == $current_admin_id) {
            returnWithMsg("error", "times-circle", 0, "You cannot delete your own account.", "users.php");
        }

        // Get target user's role to check if current admin has permission
        $stmt_target_role = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
        if ($stmt_target_role === false) {
            error_log("Prepare target user role fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
            returnWithMsg("error", "exclamation-triangle", 0, "Failed to retrieve target user role.", "users.php");
        }
        $stmt_target_role->bind_param("i", $target_user_id);
        $stmt_target_role->execute();
        $result_target_role = $stmt_target_role->get_result();
        if ($result_target_role->num_rows === 1) {
            $target_user_data = $result_target_role->fetch_assoc();
            $target_user_role = $target_user_data['user_role'];

            // Only admin can delete other admins or moderators
            if ($admin_role !== 'admin' && ($target_user_role === 'admin' || $target_user_role === 'moderator')) {
                returnWithMsg("error", "times-circle", 0, "You do not have permission to delete this user.", "users.php");
            }
        } else {
            returnWithMsg("error", "times-circle", 0, "User to delete not found.", "users.php");
        }
        $stmt_target_role->close();

        // Delete user using prepared statement
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
            error_log("Failed to delete user ID: " . $target_user_id . ". Affected rows: " . $stmt_delete->affected_rows);
            returnWithMsg("error", "times-circle", 0, "Failed to delete the user. User might not exist or database error.", "users.php");
        }
        $stmt_delete->close();
	}

	// Handle New User / Edit User actions (requires POST data)
	if (count($_POST) > 0) {
		// Re-define validate() for safety, if not already available
        if (!function_exists('validate')) {
            function validate($data) {
                $data = trim($data);
                $data = stripslashes($data);
                $data = htmlspecialchars($data);
                return $data;
            }
        }
        
		foreach($_POST as $key => $value) {
		  ${$key} = validate($value);
		}

		// Validate common fields for new and edit user
		if (empty($user_name) || empty($user_mail) || empty($user_role)) {
			returnWithMsg("error", "times-circle", 0, "Please fill out all the required fields.", false, isset($user_id) ? $user_id : null);
		}
        
        // Backend username pattern validation
        if (!preg_match("/^[a-zA-Z0-9_.-]+$/", $user_name)) {
            returnWithMsg("error", "times-circle", 0, "Username can only contain letters, numbers, underscores, hyphens, and periods.", false, isset($user_id) ? $user_id : null);
        }

        // Basic email validation
        if (!filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
            returnWithMsg("error", "times-circle", 0, "Invalid email address format.", false, isset($user_id) ? $user_id : null);
        }

        // Sanitize role input strictly
        $allowed_roles = ['user', 'moderator', 'admin'];
        if (!in_array($user_role, $allowed_roles)) {
            returnWithMsg("error", "times-circle", 0, "Invalid user role specified.", false, isset($user_id) ? $user_id : null);
        }
        // Admin role permission check for creation/assignment
        if ($user_role === 'admin' && $admin_role !== 'admin') {
            returnWithMsg("error", "times-circle", 0, "You do not have permission to create/assign an administrator role.", false, isset($user_id) ? $user_id : null);
        }
        // Moderator role permission check (admins can assign, regular users cannot assign moderator)
        if ($user_role === 'moderator' && $admin_role === 'user') { // Assuming regular users cannot assign moderator
            returnWithMsg("error", "times-circle", 0, "You do not have permission to create/assign a moderator role.", false, isset($user_id) ? $user_id : null);
        }

		if ($action === "newuser") {
            if (empty($user_pass)) {
                returnWithMsg("error", "times-circle", 0, "Password is required for new users.", false);
            }
            $hashed_pass = password_hash($user_pass, PASSWORD_DEFAULT);

            // Check if username or email already exists (excluding current user for edit)
            $stmt_check_exist = $db_connection->conn->prepare("SELECT user_id FROM users WHERE user_uid = ? OR user_mail = ?");
            if ($stmt_check_exist === false) {
                error_log("Prepare check existing user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "User check preparation failed!", "users.php");
            }
            $stmt_check_exist->bind_param("ss", $user_name, $user_mail);
            $stmt_check_exist->execute();
            $result_check_exist = $stmt_check_exist->get_result();
            if ($result_check_exist->num_rows > 0) {
                returnWithMsg("error", "times-circle", 0, "Username or email already exists.", false); // Changed redirect to `false` for new user errors
            }
            $stmt_check_exist->close();

            // Insert new user using prepared statement
            $stmt_insert = $db_connection->conn->prepare("INSERT INTO users (user_uid, user_pass, user_mail, user_role, user_registered, user_lastseen, user_ip, user_timezone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_insert === false) {
                error_log("Prepare insert user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "New user insertion preparation failed!", false); // Changed redirect to `false` for new user errors
            }
            $currtime = gmdate("Y-m-d H:i:s");
            $user_ip = $_SERVER['REMOTE_ADDR'];
            $default_timezone = 'Europe/Madrid'; // Or fetch from site settings if available
            $stmt_insert->bind_param("ssssssss", $user_name, $hashed_pass, $user_mail, $user_role, $currtime, $currtime, $user_ip, $default_timezone);
            $stmt_insert->execute();

            if ($stmt_insert->affected_rows === 1) {
                returnWithMsg("success", "check-circle", 0, "The user was successfully added.", false); // Changed redirect to `false` for new user success (will go to users.php by returnWithMsg's logic)
            } else {
                error_log("Failed to add new user. Affected rows: " . $stmt_insert->affected_rows . ". Error: " . $stmt_insert->error);
                returnWithMsg("error", "times-circle", 0, "Failed to add the new user. Database error or duplicate entry.", false); // Changed redirect to `false` for new user errors
            }
            $stmt_insert->close();

		} else if ($action === "edituser") {
            if (!isset($_POST['user_id'])) {
                returnWithMsg("error", "times-circle", 0, "User ID not specified for editing.", "users.php");
            }
            $target_user_id = $_POST['user_id'];

            // Fetch original user data for permission checks
            $stmt_original_user_data = $db_connection->conn->prepare("SELECT user_role, user_uid, user_mail FROM users WHERE user_id = ?");
            if ($stmt_original_user_data === false) {
                 error_log("Prepare original user data fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                 returnWithMsg("error", "exclamation-triangle", 0, "Failed to retrieve original user data.", false, $target_user_id);
            }
            $stmt_original_user_data->bind_param("i", $target_user_id);
            $stmt_original_user_data->execute();
            $result_original_user_data = $stmt_original_user_data->get_result();
            if ($result_original_user_data->num_rows === 1) {
                $original_user_data = $result_original_user_data->fetch_assoc();
                $original_user_role = $original_user_data['user_role'];
                $original_user_uid = $original_user_data['user_uid'];
                $original_user_mail = $original_user_data['user_mail'];
            } else {
                returnWithMsg("error", "times-circle", 0, "User to edit not found.", "users.php");
            }
            $stmt_original_user_data->close();

            // Check if username or email already exists for another user
            $stmt_check_exist = $db_connection->conn->prepare("SELECT user_id FROM users WHERE (user_uid = ? OR user_mail = ?) AND user_id != ?");
            if ($stmt_check_exist === false) {
                error_log("Prepare check existing user (edit) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "User check preparation failed!", false, $target_user_id);
            }
            $stmt_check_exist->bind_param("ssi", $user_name, $user_mail, $target_user_id);
            $stmt_check_exist->execute();
            $result_check_exist = $stmt_check_exist->get_result();
            if ($result_check_exist->num_rows > 0) {
                returnWithMsg("error", "times-circle", 0, "Username or email already exists for another user.", false, $target_user_id);
            }
            $stmt_check_exist->close();


            // Permission checks for editing:
            // 1. A non-admin cannot change an admin's or moderator's role or edit their profile (unless it's their own, and they aren't changing their role to admin/mod).
            // 2. A non-admin cannot assign admin/moderator roles to others.
            // 3. A user cannot change their own role to admin/moderator if they are not already.

            // If current admin is NOT an admin:
            if ($admin_role !== 'admin') {
                // If target user is an admin or moderator, and current user is trying to edit someone else
                if (($original_user_role === 'admin' || $original_user_role === 'moderator') && ($target_user_id != $current_admin_id)) {
                    returnWithMsg("error", "times-circle", 0, "You do not have permission to edit this user's profile.", "users.php");
                }
                // If the target role is being changed to admin/moderator, and current user is not admin
                if (($user_role === 'admin' || $user_role === 'moderator') && ($user_role !== $original_user_role) && ($target_user_id != $current_admin_id)) {
                    returnWithMsg("error", "times-circle", 0, "You do not have permission to assign this role to other users.", false, $target_user_id);
                }
                // If current admin is trying to change their own role to admin/moderator from a lower role
                if (($target_user_id == $current_admin_id) && ($user_role !== $original_user_role) && ($user_role === 'admin' || $user_role === 'moderator')) {
                    returnWithMsg("error", "times-circle", 0, "You cannot change your own role to administrator or moderator.", false, $target_user_id);
                }
            }


            $currtime = gmdate("Y-m-d H:i:s"); // Update timestamp

            if (empty($user_pass)) {
                // Update user without changing password
                $stmt_update = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ?, user_role = ? WHERE user_id = ?");
                if ($stmt_update === false) {
                    error_log("Prepare update user (no pass) failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                    returnWithMsg("error", "exclamation-triangle", 0, "User update preparation failed!", false, $target_user_id);
                }
                $stmt_update->bind_param("sssi", $user_name, $user_mail, $user_role, $target_user_id);
            } else {
                // Update user including new password
                $hashed_pass = password_hash($user_pass, PASSWORD_DEFAULT);
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
                returnWithMsg("success", "check-circle", 0, "No changes were made to the user.", false, $target_user_id);
            } else {
                error_log("Failed to update user ID: " . $target_user_id . ". Affected rows: " . $stmt_update->affected_rows . ". Error: " . $stmt_update->error);
                returnWithMsg("error", "times-circle", 0, "Failed to update the user. Database error or multiple rows affected.", false, $target_user_id);
            }
            $stmt_update->close();
		}
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid form submission!", "users.php");
	}

    // Close the main database connection object
    $db_connection->conn->close();

?>