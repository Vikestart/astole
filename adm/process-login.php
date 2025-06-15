<?php
	// Fetch database configuration and start session
	session_start();
	$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'];
	require "../db.php";

	// Function for returning back to page with an error message
	function returnWithMsg($type, $icon, $expire, $message) { // Removed $redirect, as it always redirects to login.php
		$_SESSION['Sessionmsg'] = array("login", $type, $icon, $expire, $message);
		header("Location: login.php");
		die();
	}

	if (isset($_POST["username"]) && isset($_POST["password"])) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		$admuser = validate($_POST["username"]);
		$admpass = validate($_POST["password"]);

		if (empty($admuser)) {
			returnWithMsg("error", "times-circle", 0, "Username is required.");
		} else if (empty($admpass)) {
			returnWithMsg("error", "times-circle", 0, "Password is required.");
		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Username and/or password not specified.");
	}

	// Create a single DBConn instance for this script
	$db_connection = new DBConn();

	// Query the database for user using prepared statement
	$stmt_userquery = $db_connection->conn->prepare("SELECT user_id, user_pass, user_uid FROM users WHERE user_uid = ? OR user_mail = ?");
    if ($stmt_userquery === false) {
        error_log("Prepare user query failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
        returnWithMsg("error", "exclamation-triangle", 0, "Database query preparation failed!");
    }
    $stmt_userquery->bind_param("ss", $admuser, $admuser);
    $stmt_userquery->execute();
    $result_userquery = $stmt_userquery->get_result();

	if ($result_userquery->num_rows === 1) {
		$userquery_row = $result_userquery->fetch_assoc();
        $stmt_userquery->close(); // Close statement after fetching result

		$hashedPwd = $userquery_row['user_pass'];
		$passcheck = password_verify($admpass, $hashedPwd);

		// ONLY use password_verify for password check
		if ($passcheck === true) {

			$ipaddress = $_SERVER['REMOTE_ADDR'];
			$currtime = gmdate("Y-m-d H:i:s");

			// Update user last seen and IP using prepared statement
			$stmt_refreshuser = $db_connection->conn->prepare("UPDATE users SET user_lastseen = ?, user_ip = ? WHERE user_id = ?");
            if ($stmt_refreshuser === false) {
                error_log("Prepare refresh user failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Database update preparation failed!");
            }
            $stmt_refreshuser->bind_param("ssi", $currtime, $ipaddress, $userquery_row['user_id']); // Bind by user_id
            $stmt_refreshuser->execute();

            if ($stmt_refreshuser->errno) {
                error_log("Execute refresh user failed: (" . $stmt_refreshuser->errno . ") " . $stmt_refreshuser->error);
                returnWithMsg("error", "exclamation-triangle", 0, "Database update execution failed!");
            }

            $rows_affected = $stmt_refreshuser->affected_rows;
            $stmt_refreshuser->close();

			if ($rows_affected === 1) {
				$_SESSION['UserID'] = $userquery_row['user_id'];
				// The commented lines below should remain commented if you are solely relying on UserID for session.
				/*$_SESSION['User'] = $userquery_row['user_uid'];
				$_SESSION['UserMail'] = $userquery_row['user_mail'];
				$_SESSION['UserRole'] = $userquery_row['user_role'];
				$_SESSION['LastSeen'] = $currtime;
				$_SESSION['Timezone'] = $userquery_row['user_timezone'];*/
				
				// Close the DB connection before redirect
				$db_connection->conn->close();
				header("Location: index.php");
				die();

			} else {
                error_log("Failed to update user last seen/IP for user ID: " . $userquery_row['user_id'] . ". Affected rows: " . $rows_affected);
				returnWithMsg("error", "exclamation-triangle", 0, "Error updating user last seen/IP.");
			}

		} else {
			returnWithMsg("error", "times-circle", 0, "Incorrect username or password.");
		}

	} else {
		
		returnWithMsg("error", "times-circle", 0, "Incorrect username or password.");
	}

    $db_connection->conn->close();

?>