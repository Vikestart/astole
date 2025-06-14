<?php
	// Start session and get database configuration
	session_start();
	$page_id = $_SESSION['acp_page_id'];
	require "../db.php";

	// Determine which action is to be performed on the page
	$action = ($_GET['a'] == 'del') ? 'delpage' : $_POST['action'];

	// Function for returning back to page with an error message
	function returnWithMsg($type, $icon, $expire, $message, $redirect) {
		global $action;
		global $page_id;
		$_SESSION['Sessionmsg'] = array($action, $type, $icon, $expire, $message);
		if ($redirect === false) {
			header("Location: edit-page.php?t=edit&p=$page_id");
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
	if ($action === "delpage") {

		$mysqli = new DBConn();
		$mysqli->conn->query("DELETE FROM pages WHERE page_id = '$page_id'");

		if ($mysqli->conn->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The page has been deleted as requested.", "pages.php");
		} else {
			returnWithMsg("error", "times-circle", 0, "Page deletion failed!", false);
		}

	}	else if (count($_POST) > 0) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		foreach($_POST as $key => $value) {
		  ${$key} = validate($value);
			if (empty(${$key})) {
				returnWithMsg("error", "times-circle", 0, "Please fill out all the fields.", false);
			}
		}

		if (isset($action) && $action === "newpage") {

			$currtime = gmdate("Y-m-d H:i:s");
			// Query the database
			$mysqli = new DBConn();
			$mysqli->sql = sprintf("INSERT INTO pages (page_title, page_author, page_created, page_updated, page_contents) VALUES ('%s', '$user', '$currtime', '$currtime', '%s')", $mysqli->conn->real_escape_string($pagetitle), $mysqli->conn->real_escape_string($pagecontents));
			$mysqli->conn->query($mysqli->sql);

			if ($mysqli->conn->affected_rows === 1) {
				$page_id = $mysqli->conn->insert_id;
				returnWithMsg("success", "check-circle", 0, "The page was successfully created and saved.", false);
			} else {
				returnWithMsg("error", "times-circle", 0, "Please input something into all the fields.", false);
			}

		} else if (isset($action) && $action === "editpage") {

			// Query the database
			$currtime = gmdate("Y-m-d H:i:s");
			$mysqli = new DBConn();
			$mysqli->result = $mysqli->conn->query("UPDATE pages SET page_title = '$pagetitle', page_contents = '$pagecontents', page_updated = '$currtime' WHERE page_id = '$page_id'");

			if ($mysqli->conn->affected_rows === 1) {
				returnWithMsg("success", "check-circle", 0, "The page was successfully updated with the new content.", false);
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
