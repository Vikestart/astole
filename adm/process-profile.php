<?php
	// Fetch database configuration and start session
	session_start();
	// Prevent unauthorized remote submissions
	if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
		returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, 5000, "Security validation failed (CSRF).", false);
	}
	require "../db.php";
	$db_connection = new DBConn();

	// Function for returning back to page with an error message
	$origin = $_POST['action'];
	function returnWithMsg($type, $icon, $expire, $message, $redirect) {
		global $origin;
		$_SESSION['Sessionmsg'] = array($origin, $type, $icon, $expire, $message);
		header("Location: profile.php");
		die();
	}

	if (count($_POST) > 0) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		if (isset($_SESSION['UserID'])) {
            $active_user_ID = $_SESSION['UserID'];

            $stmt_fetch_user_uid = $db_connection->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
            if ($stmt_fetch_user_uid === false) {
                error_log("Prepare user_uid fetch failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "User ID query preparation failed!", false);
            }
            $stmt_fetch_user_uid->bind_param("i", $active_user_ID);
            $stmt_fetch_user_uid->execute();

            if ($stmt_fetch_user_uid->errno) {
                error_log("Execute user_uid fetch failed: (" . $stmt_fetch_user_uid->errno . ") " . $stmt_fetch_user_uid->error);
                returnWithMsg("error", "exclamation-triangle", 0, "User ID query execution failed!", false);
            }

            $result = $stmt_fetch_user_uid->get_result();
            $user_row = $result->fetch_assoc();
            $stmt_fetch_user_uid->close();

            if ($user_row) {
                $user = $user_row['user_uid'];
            } else {
                returnWithMsg("error", "times-circle", 0, "Could not fetch user data. Try signing out and back in.", false);
            }
        } else {
            returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", false);
        }

		if (isset($origin) && $origin === "changebasics") {
            
			$user_name = validate($_POST['user_name'] ?? '');
            $user_mail = validate($_POST['user_mail'] ?? '');

            if (empty($user_name) || empty($user_mail)) {
                returnWithMsg("error", "times-circle", 0, "Please fill out all fields for profile details.", false);
            }
            
			$stmt = $db_connection->conn->prepare("UPDATE users SET user_uid = ?, user_mail = ? WHERE user_uid = ?");

            if ($stmt === false) {
                error_log("Prepare failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Database query preparation failed!", false);
            }
			
            $stmt->bind_param("sss", $user_name, $user_mail, $user);
            $stmt->execute();

            if ($stmt->errno) {
                error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Database query execution failed!", false);
            }

			if ($stmt->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "Your profile has been updated.", false);
			} else {
                if ($stmt->affected_rows === 0) {
                    returnWithMsg("success", "check-circle", 0, "No changes detected or profile already up-to-date.", false);
                } else {
				    returnWithMsg("error", "exclamation-triangle", 0, "Database error! (Affected rows: " . $stmt->affected_rows . ")", false);
                }
			}

            $stmt->close();

		} else if (isset($origin) && $origin === "changepassword") {
            
			$oldpass = validate($_POST['oldpass'] ?? '');
            $newpass = validate($_POST['newpass'] ?? '');
            $passconfirm = validate($_POST['passconfirm'] ?? '');

            if (empty($oldpass) || empty($newpass) || empty($passconfirm)) {
                returnWithMsg("error", "times-circle", 0, "Please fill out all fields for password change.", false);
            }

            if ($newpass != $passconfirm) {
                returnWithMsg("error", "times-circle", 10000, "There was a mismatch in the two instances of the new password. Make sure to input the new password identically in both fields.", false);
            }

			$stmt_select = $db_connection->conn->prepare("SELECT user_pass FROM users WHERE user_uid = ?");
            if ($stmt_select === false) {
                error_log("Prepare select failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Password query preparation failed!", false);
            }

            $stmt_select->bind_param("s", $user);
			$stmt_select->execute();

            if ($stmt_select->errno) {
                error_log("Execute select failed: (" . $stmt_select->errno . ") " . $stmt_select->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Password query execution failed!", false);
            }

            $result = $stmt_select->get_result();
            $user_row = $result->fetch_assoc();

            $stmt_select->close();

			if ($user_row) {
                $hashedPwd = $user_row['user_pass'];
                $passcheck = password_verify($oldpass, $hashedPwd);
            } else {
                returnWithMsg("error", "exclamation-triangle", 0, "User not found!", false);
            }

            if ($passcheck === true) {
                $newpass = password_hash($newpass, PASSWORD_DEFAULT);

                $stmt_update_pass = $db_connection->conn->prepare("UPDATE users SET user_pass = ? WHERE user_uid = ?");
                if ($stmt_update_pass === false) {
                    error_log("Prepare update pass failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                    returnWithMsg("error", "exclamation-triangle", 0, "Password update preparation failed!", false);
                }

                $stmt_update_pass->bind_param("ss", $newpass, $user);

                $stmt_update_pass->execute();

                if ($stmt_update_pass->errno) {
                    error_log("Execute update pass failed: (" . $stmt_update_pass->errno . ") " . $stmt_update_pass->error);
                    returnWithMsg("error", "exclamation-triangle", 0, "Password update execution failed!", false);
                }
	
				if ($stmt_update_pass->affected_rows === 1) {
					returnWithMsg("success", "check-circle", 0, "Your password has been updated.", false);
				} else {
                    if ($stmt_update_pass->affected_rows === 0) {
                        returnWithMsg("success", "check-circle", 0, "No changes detected or password already up-to-date.", false);
                    } else {
					    returnWithMsg("error", "exclamation-triangle", 0, "Database error!", false);
                    }
				}

                $stmt_update_pass->close();

            } else {
                returnWithMsg("error", "exclamation-triangle", 0, "Your current password is not correct!", false);
            }

		} else if (isset($origin) && $origin === "changetimezone") {
            
			$timezone = validate($_POST['timezone'] ?? '');

            if (empty($timezone)) {
                returnWithMsg("error", "times-circle", 0, "Please select a timezone.", false);
            }

			$stmt_timezone = $db_connection->conn->prepare("UPDATE users SET user_timezone = ? WHERE user_uid = ?");
            if ($stmt_timezone === false) {
                error_log("Prepare timezone failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Timezone update preparation failed!", false);
            }

            $stmt_timezone->bind_param("ss", $timezone, $user);
			$stmt_timezone->execute();

            if ($stmt_timezone->errno) {
                error_log("Execute timezone failed: (" . $stmt_timezone->errno . ") " . $stmt_timezone->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Timezone update execution failed!", false);
            }

			if ($stmt_timezone->affected_rows === 1) {
				$_SESSION['Timezone'] = $timezone;
				returnWithMsg("success", "check-circle", 0, "Your timezone has been updated.", false);
			} else {
                if ($stmt_timezone->affected_rows === 0) {
                    returnWithMsg("success", "check-circle", 0, "Timezone is already set to this value.", false);
                } else {
				    returnWithMsg("error", "times-circle", 0, "Database error or multiple rows affected!", false);
                }
			}
			
            $stmt_timezone->close();

		} else {

			returnWithMsg("error", "times-circle", 0, "Invalid form!", false);

		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
	}

?>
