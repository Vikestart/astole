<?php
    // --- 1. TEMPORARY DEBUGGING ---
    // Forces PHP to print Fatal Errors to the screen instead of a blank 500 page.
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // --- 2. MATCH STRICT SESSION RULES ---
    // This MUST match the settings in inc-adm-head.php so Ubuntu doesn't drop the session!
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
	session_start();

	$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'] ?? '';
	require "../db.php";

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

    // --- 3. BULLETPROOF CSRF CHECK ---
    // Using ?? '' prevents PHP 8 TypeErrors if the session token is ever missing
    $session_token = $_SESSION['csrf_token'] ?? '';
    $post_token = $_POST['csrf_token'] ?? '';

    if (empty($session_token) || empty($post_token) || !hash_equals($session_token, $post_token)) {
        returnWithMsg("error", "times-circle", 5000, "Security validation failed (Session timeout). Please refresh and try again.");
    }

	if (isset($_POST["username"]) && isset($_POST["password"])) {
        // Clean up the inner function and sanitize directly
		$admuser = htmlspecialchars(stripslashes(trim($_POST["username"])));
        
        // BUG FIX: Never sanitize passwords! Take the raw input.
		$admpass = $_POST["password"]; 

		if (empty($admuser)) { returnWithMsg("error", "times-circle", 0, "Username is required."); }
        if (empty($admpass)) { returnWithMsg("error", "times-circle", 0, "Password is required."); }
	} else {
		returnWithMsg("error", "times-circle", 0, "Username and/or password not specified.");
	}

	$db_connection = new DBConn();
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // --- RATE LIMITING CHECK ---
    $time_limit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
    $stmt_limit = $db_connection->conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > ?");
    
    if ($stmt_limit === false) {
        error_log("Missing table 'login_attempts': " . $db_connection->conn->error);
        returnWithMsg("error", "times-circle", 0, "Database setup incomplete. Check logs.");
    }

    $stmt_limit->bind_param("ss", $ip_address, $time_limit);
    $stmt_limit->execute();
    $stmt_limit->bind_result($attempt_count);
    $stmt_limit->fetch();
    $stmt_limit->close();

    if ($attempt_count >= 5) {
        returnWithMsg("error", "times-circle", 5000, "Too many failed attempts. Try again in 15 minutes.");
    }

	// --- AUTHENTICATE USER ---
	$stmt_userquery = $db_connection->conn->prepare("SELECT user_id, user_pass, user_uid FROM users WHERE user_uid = ? OR user_mail = ? LIMIT 1");
    if ($stmt_userquery === false) {
        returnWithMsg("error", "times-circle", 0, "Database query error occurred.");
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
            $db_connection->conn->close();
            
            header("Location: index.php");
            die();
		}
	}

    // --- LOGIN FAILED - LOG ATTEMPT ---
    $stmt_log = $db_connection->conn->prepare("INSERT INTO login_attempts (ip_address, attempt_time) VALUES (?, NOW())");
    $stmt_log->bind_param("s", $ip_address);
    $stmt_log->execute();
    $stmt_log->close();

    returnWithMsg("error", "times-circle", 4500, "Incorrect username or password.");
?>