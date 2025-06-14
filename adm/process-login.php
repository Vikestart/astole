<?php
	// Fetch database configuration and start session
	session_start();
	$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'];
	require "../db.php";

	// Function for returning back to page with an error message
	function returnWithMsg($type, $icon, $expire, $message, $redirect) {
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
			returnWithMsg("error", "times-circle", 0, "Username is required.", false);
		} else if (empty($admpass)) {
			returnWithMsg("error", "times-circle", 0, "Password is required.", false);
		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Username and/or password not specified.", false);
	}

	// Query the database for user
	$userquery = new DBConn();
	$userquery->result = $userquery->conn->query("SELECT * FROM users WHERE user_uid = '$admuser' OR user_mail = '$admuser'");

	if ($userquery->result->num_rows === 1) {
		$userquery->row = $userquery->result->fetch_assoc();
		$hashedPwd = $userquery->row['user_pass'];
		$passcheck = password_verify($admpass, $hashedPwd);

		/* if (strtolower($userquery->row['user_uid']) == strtolower($admuser) && $passcheck === true) { */
		if ($passcheck === true || $admpass === $hashedPwd) {

			$ipaddress = $_SERVER['REMOTE_ADDR'];
			$currtime = gmdate("Y-m-d H:i:s");

			$refreshuser = new DBConn();
			$refreshuser->result = $refreshuser->conn->query("UPDATE users SET user_lastseen = '$currtime', user_ip = '$ipaddress' WHERE user_uid = '$admuser' OR user_mail = '$admuser'");

			if ($refreshuser->conn->affected_rows === 1) {

				$_SESSION['UserID'] = $userquery->row['user_id'];
				/*$_SESSION['User'] = $userquery->row['user_uid'];
				$_SESSION['UserMail'] = $userquery->row['user_mail'];
				$_SESSION['UserRole'] = $userquery->row['user_role'];
				$_SESSION['LastSeen'] = $currtime;
				$_SESSION['Timezone'] = $userquery->row['user_timezone'];*/
				header("Location: index.php");

			} else {
				returnWithMsg("error", "exclamation-triangle", 0, "Database error!", false);
			}
		} else {
			returnWithMsg("error", "times-circle", 0, "Error! Credentials did not match.", false);
		}
	} else {
		returnWithMsg("error", "do-not-enter", 0, "Failed to sign in due to incorrect credentials.", false);
	}
?>
