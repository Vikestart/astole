<?php
	// Fetch database configuration and start session
	session_start();
	require "../db.php";

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

		if (isset($_SESSION['User'])) {
			$user = $_SESSION['User'];
		} else {
			returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", false);
		}

		foreach($_POST as $key => $value) {
		  ${$key} = validate($value);
			if (empty(${$key})) {
				returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
			}
		}

		if (isset($origin) && $origin === "changebasics") {

			// Query the database
			$mysqli = new DBConn();
			$mysqli->sql = sprintf("UPDATE users SET user_uid = '%s', user_mail = '%s' WHERE user_uid = '$user'", $mysqli->conn->real_escape_string($user_name), $mysqli->conn->real_escape_string($user_mail));
			$mysqli->conn->query($mysqli->sql);

			if ($mysqli->conn->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "Your profile has been updated.", false);
			} else {
				returnWithMsg("error", "exclamation-triangle", 0, "Database error!", false);
			}

		} else if (isset($origin) && $origin === "changepassword") {

			if ($newpass != $passconfirm) {
				returnWithMsg("error", "times-circle", 10000, "There was a mismatch in the two instances of the new password. Make sure to input the new password identically in both fields.", false);
			}

			// Query the database about password
			$userquery = new DBConn();
			$userquery->result = $userquery->conn->query("SELECT * FROM users WHERE user_uid = '$user'");
			$userquery->row = $userquery->result->fetch_assoc();
			$hashedPwd = $userquery->row['user_pass'];
			$passcheck = password_verify($oldpass, $hashedPwd);

			if ($passcheck === true) {

				$newpass = password_hash($newpass, PASSWORD_DEFAULT);
				$mysqli = new DBConn();
				$mysqli->sql = sprintf("UPDATE users SET user_pass = '%s' WHERE user_uid = '$user'", $mysqli->conn->real_escape_string($newpass));
				$mysqli->conn->query($mysqli->sql);
	
				if ($mysqli->conn->affected_rows === 1) {
					returnWithMsg("success", "check-circle", 0, "Your password has been updated.", false);
				} else {
					returnWithMsg("error", "exclamation-triangle", 0, "Database error!", false);
				}

			} else {

				returnWithMsg("error", "exclamation-triangle", 0, "Your current password is not correct!", false);
			}

		} else if (isset($origin) && $origin === "changetimezone") {

			// Query the database about timezone
			$mysqli = new DBConn();
			$mysqli->sql = sprintf("UPDATE users SET user_timezone = '%s' WHERE user_uid = '$user'", $mysqli->conn->real_escape_string($timezone));
			$mysqli->conn->query($mysqli->sql);

			if ($mysqli->conn->affected_rows === 1) {
				$_SESSION['Timezone'] = $timezone;
				returnWithMsg("success", "check-circle", 0, "Your timezone has been updated.", false);
			} else {
				returnWithMsg("error", "times-circle", 0, "This is already set as your timezone!", false);
			}

		} else {

			returnWithMsg("error", "times-circle", 0, "Invalid form!", false);

		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
	}

?>
