<?php
	// Start session and get database configuration
	session_start();
	$user_id = $_SESSION['acp_page_id'];
	require "../db.php";

	// Determine which action is to be performed on the page
	$action = ($_GET['a'] == 'del') ? 'deluser' : $_POST['action'];

	// Function for returning back to page with an error message
	function returnWithMsg($type, $icon, $expire, $message, $redirect) {
		global $action;
		global $user_id;
		$_SESSION['Sessionmsg'] = array($action, $type, $icon, $expire, $message);
		if ($redirect === false) {
			header("Location: edit-user.php?t=edit&u=$user_id");
		} else {
			header("Location: $redirect");
		}
		die();
	}

	// Control user account
	if (isset($_SESSION['User'])) {
		$user = $_SESSION['User'];
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", false);
	}

	// Require $_POST array to be populated
	if ($action === "deluser") {

		$mysqli = new DBConn();
		$mysqli->result = $mysqli->conn->query("SELECT * FROM users WHERE user_id = '$user_id'");
		if ($mysqli->result->num_rows === 1) {
			$mysqli->row = $mysqli->result->fetch_assoc();
			$user_role = $mysqli->row['user_role'];
		}

		if ($user_role !== 'admin') {

			$mysqli->conn->query("DELETE FROM users WHERE user_id = '$user_id'");

			if ($mysqli->conn->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "The user has been deleted as requested.", "users.php");
			} else {
				returnWithMsg("error", "times-circle", 0, "Page deletion failed!", false);
			}

		} else {

			returnWithMsg("error", "times-circle", 0, "Cannot delete Admin users!", false);

		}

	}	else if (count($_POST) > 0) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		$_POST['user_pass'] = password_hash($_POST['user_pass'], PASSWORD_DEFAULT);
		foreach($_POST as $key => $value) {
			${$key} = validate($value);
			if (empty(${$key}) && $key !== 'user_pass') {
				returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
			}
		}

		if (isset($action) && $action === "newuser") {

			$currtime = gmdate("Y-m-d H:i:s");
			// Query the database
			$mysqli = new DBConn();
			$mysqli->sql = sprintf("INSERT INTO users (user_uid, user_pass, user_mail, user_role) VALUES ('%s', '%s', '%s', '%s')", $mysqli->conn->real_escape_string($user_name), $mysqli->conn->real_escape_string($user_pass), $mysqli->conn->real_escape_string($user_mail), $mysqli->conn->real_escape_string(strtolower($user_role)));
			$mysqli->conn->query($mysqli->sql);

			if ($mysqli->conn->affected_rows === 1) {
				$user_id = $mysqli->conn->insert_id;
				returnWithMsg("success", "check-circle", 0, "The user was successfully added.", false);
			} else {
				returnWithMsg("error", "times-circle", 0, "Please input something into all the fields.", false);
			}

		} else if (isset($action) && $action === "edituser") {

			// Query the database
			$currtime = gmdate("Y-m-d H:i:s");
			$mysqli = new DBConn();
			$user_role = strtolower($user_role);

			if (empty($user_pass)) {
				$mysqli->result = $mysqli->conn->query("UPDATE users SET user_uid = '$user_name', user_mail = '$user_mail', user_role = '$user_role' WHERE user_id = '$user_id'");
			} else {
				$mysqli->result = $mysqli->conn->query("UPDATE users SET user_uid = '$user_name', user_pass = '$user_pass', user_mail = '$user_mail', user_role = '$user_role' WHERE user_id = '$user_id'");
			}

			if ($mysqli->conn->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "The user was successfully updated with the new details.", false);
			} else {
				returnWithMsg("error", "times-circle", 0, "Could not save since no changes were made.", false);
			}

		} else {

			returnWithMsg("error", "times-circle", 0, "Invalid form!", false);

		}

	} else {
		returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
	}

?>
