<?php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
	session_start();

	$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'] ?? '';
	require "../db.php";
    require "admin-functions.php";
    require_once "activity-logger.php";

    $session_token = $_SESSION['csrf_token'] ?? '';
    $post_token = $_POST['csrf_token'] ?? '';

    if (empty($session_token) || empty($post_token) || !hash_equals($session_token, $post_token)) {
        returnWithMsg("login", "error", "times-circle", 5000, "Security validation failed. Please refresh and try again.", "login.php");
    }

	if (isset($_POST["username"]) && isset($_POST["password"])) {
		$admuser = htmlspecialchars(stripslashes(trim($_POST["username"])));
		$admpass = $_POST["password"]; 

		if (empty($admuser)) { returnWithMsg("login", "error", "times-circle", 0, "Username is required.", "login.php"); }
        if (empty($admpass)) { returnWithMsg("login", "error", "times-circle", 0, "Password is required.", "login.php"); }

		$db_connection = new DBConn();
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        
        $stmt_check = $db_connection->conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
        $stmt_check->bind_param("s", $ip_address);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();
        
        if ($res_check->num_rows > 0) {
            $row_check = $res_check->fetch_assoc();
            $attempts = $row_check['attempts'];
            $last_attempt = strtotime($row_check['last_attempt']);
            $time_diff = time() - $last_attempt;
            
            if ($attempts >= 5 && $time_diff < 900) { 
                $wait_time = ceil((900 - $time_diff) / 60);
                returnWithMsg("login", "error", "times-circle", 0, "Too many failed attempts. Please try again in {$wait_time} minutes.", "login.php");
            }
            if ($time_diff >= 900) {
                $stmt_reset = $db_connection->conn->prepare("UPDATE login_attempts SET attempts = 0 WHERE ip_address = ?");
                $stmt_reset->bind_param("s", $ip_address);
                $stmt_reset->execute();
                $stmt_reset->close();
            }
        }
        $stmt_check->close();

		$stmt_userquery = $db_connection->conn->prepare("SELECT user_id, user_pass, user_role FROM users WHERE user_uid = ? OR user_mail = ? LIMIT 1");
        if (!$stmt_userquery) {
            returnWithMsg("login", "error", "times-circle", 0, "An internal database error occurred.", "login.php");
        }

		$stmt_userquery->bind_param("ss", $admuser, $admuser);
		$stmt_userquery->execute();
		$result_userquery = $stmt_userquery->get_result();

		if ($result_userquery->num_rows === 1) {
			$userquery_row = $result_userquery->fetch_assoc();

			if (password_verify($admpass, $userquery_row['user_pass'])) {
                
                // LOGIN SUCCESS
                session_regenerate_id(true); 
                
                $stmt_clear = $db_connection->conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                $stmt_clear->bind_param("s", $ip_address);
                $stmt_clear->execute();
                $stmt_clear->close();

				$currtime = gmdate("Y-m-d H:i:s");
				$stmt_refreshuser = $db_connection->conn->prepare("UPDATE users SET user_lastseen = ?, user_ip = ? WHERE user_id = ?");
				$stmt_refreshuser->bind_param("ssi", $currtime, $ip_address, $userquery_row['user_id']);
				$stmt_refreshuser->execute();
				$stmt_refreshuser->close();

                $_SESSION['UserID'] = $userquery_row['user_id'];
                
                // MANUALLY LOG THE SUCCESSFUL LOGIN
                logAdminActivity($db_connection->conn, $userquery_row['user_id'], 'Security', "Logged into the control panel.");

                $db_connection->conn->close();
                header("Location: index.php");
                die();
			}
		}

        // LOGIN FAILED - LOG ATTEMPT
        $stmt_log = $db_connection->conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
        $stmt_log->bind_param("s", $ip_address);
        $stmt_log->execute();
        $stmt_log->close();

		returnWithMsg("login", "error", "times-circle", 0, "Invalid credentials or account does not exist.", "login.php");
	} else {
		returnWithMsg("login", "error", "times-circle", 0, "Please fill in all required fields.", "login.php");
	}
?>