<?php
	// Fetch database configuration and start session
	session_start();
	$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'];
	require "../db.php";

	// Function for returning back to page with an error message
	function returnWithMsg($type, $icon, $expire, $message) { 
		$_SESSION['Sessionmsg'] = array(
            'origin' => 'login', 
            'type' => $type, 
            'icon' => $icon, 
            'expire' => $expire, 
            'message' => $message
        );
		header("Location: login.php");
		die();
	}

    // --- 1. CSRF VERIFICATION ---
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        returnWithMsg("error", "times-circle", 5000, "Security validation failed. Please refresh and try again.");
    }

	if (isset($_POST["username"]) && isset($_POST["password"])) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		$admuser = validate($_POST["username"]);
        
        // BUG FIX: Never sanitize passwords! Take the raw input so symbols aren't destroyed.
		$admpass = $_POST["password"]; 

		if (empty($admuser)) {
			returnWithMsg("error", "times-circle", 0, "Username is required.");
		} else if (empty($admpass)) {
			returnWithMsg("error", "times-circle", 0, "Password is required.");
		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Username and/or password not specified.");
	}

	$db_connection = new DBConn();
    $ip_address = $_SERVER['REMOTE_ADDR'];

	// --- 2. RATE LIMITING CHECK ---
    $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt_limit = $db_connection->conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    
    // SAFEGUARD: If the table doesn't exist, tell the user instead of throwing Error 500!
    if ($stmt_limit === false) {
        error_log("Missing table 'login_attempts': " . $db_connection->conn->error);
        returnWithMsg("error", "times-circle", 0, "Database setup incomplete. Check error logs.");
    }

    $stmt_limit->bind_param("ss", $ip_address, $time_limit);
    $stmt_limit->execute();
    $stmt_limit->bind_result($attempt_count);
    $stmt_limit->fetch();
    $stmt_limit->close();

    if ($attempt_count >= 5) {
        returnWithMsg("error", "times-circle", 5000, "Too many failed attempts. Try again in 15 minutes.");
    }

	// --- 3. AUTHENTICATE USER ---
	$stmt_userquery = $db_connection->conn->prepare("SELECT user_id, user_pass, user_uid FROM users WHERE user_uid = ? OR user_mail = ? LIMIT 1");
    if ($stmt_userquery === false) {
        error_log("Prepare user query failed: " . $db_connection->conn->error);
        returnWithMsg("error", "times-circle", 0, "Database error occurred.");
    }

	$stmt_userquery->bind_param("ss", $admuser, $admuser);
	$stmt_userquery->execute();
	$result_userquery = $stmt_userquery->get_result();

	if ($result_userquery->num_rows === 1) {
		$userquery_row = $result_userquery->fetch_assoc();

        // Securely verify password
		if (password_verify($admpass, $userquery_row['user_pass'])) {
            
            // --- 4. LOGIN SUCCESS ---
            session_regenerate_id(true); 
            
            $stmt_clear = $db_connection->conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_clear->bind_param("s", $ip_address);
            $stmt_clear->execute();
            $stmt_clear->close();

			$currtime = gmdate("Y-m-d H:i:s");
			$stmt_refreshuser = $db_connection->conn->prepare("UPDATE users SET user_lastseen = ?, user_ip = ? WHERE user_id = ?");
			$stmt_refreshuser->bind_param("ssi", $currtime, $ip_address, $userquery_row['user_id']);
			$stmt_refreshuser->execute();

			if ($stmt_refreshuser->errno) {
				error_log("Execute refresh user failed: (" . $stmt_refreshuser->errno . ") " . $stmt_refreshuser->error);
				returnWithMsg("error", "exclamation-triangle", 0, "Database update execution failed!");
			}

			$rows_affected = $stmt_refreshuser->affected_rows;
			$stmt_refreshuser->close();

			if ($rows_affected === 1 || $rows_affected === 0) { 
				$_SESSION['UserID'] = $userquery_row['user_id'];
				
				$db_connection->conn->close();
				header("Location: index.php");
				die();

			} else {
				error_log("Failed to update user last seen/IP for user ID: " . $userquery_row['user_id'] . ". Affected rows: " . $rows_affected);
				returnWithMsg("error", "exclamation-triangle", 0, "Error updating user last seen/IP.");
			}
		}
	}

    // --- 5. LOGIN FAILED - LOG ATTEMPT ---
    $stmt_log = $db_connection->conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $stmt_log->bind_param("s", $ip_address);
    $stmt_log->execute();
    $stmt_log->close();

    returnWithMsg("error", "times-circle", 4500, "Incorrect username or password.");
?>