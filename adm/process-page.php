<?php
	// Start session and get database configuration
	session_start();
	// Initialize $page_id; it will be set from $_GET['p'] if editing, or from insert_id if creating new.
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;
	require "../db.php";

	// Determine which action is to be performed on the page
	$action = ($_GET['a'] == 'del') ? 'delpage' : (isset($_POST['action']) ? $_POST['action'] : '');

	// Function for returning back to page with an error message
	// The $redirect_url parameter allows explicit redirection, otherwise it constructs based on action.
	function returnWithMsg($type, $icon, $expire, $message, $redirect_url) {
		global $action;
		global $page_id; // Ensure $page_id is accessible here

		// Set $_SESSION['Sessionmsg'] with associative keys
		$_SESSION['Sessionmsg'] = array(
			'origin' => $action,
			'type' => $type,
			'icon' => $icon,
			'expire' => $expire,
			'message' => $message
		);

		if ($redirect_url === false) {
			// For new pages, $page_id will be the insert_id from the global scope after insertion.
			// For existing pages (edit), $page_id will be set from $_GET['p'] at the top of the script.
			header("Location: edit-page.php?t=edit&p=" . $page_id);
		} else {
			header("Location: " . $redirect_url);
		}
		die();
	}

	// Control user account
	if (isset($_SESSION['User'])) {
		$current_user_id = (int)$_SESSION['UserID'];
	} else {
		returnWithMsg("error", "times-circle", 0, "Invalid user session. Try signing out and back in.", false);
	}

	// Require $_POST array to be populated for actions other than 'delpage'
	if ($action === "delpage") {
		// Ensure $page_id is correctly set from $_GET['p'] for deletion
		if (isset($_GET['p'])) {
			$page_id = (int)$_GET['p'];
		} else {
			returnWithMsg("error", "times-circle", 0, "No page specified for deletion.", "pages.php");
		}

		$mysqli = new DBConn();
		$mysqli->conn->query("DELETE FROM pages WHERE page_id = '$page_id'");

		if ($mysqli->conn->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The page has been deleted as requested.", "pages.php");
		} else {
			returnWithMsg("error", "times-circle", 0, "Error deleting page or page not found.", "pages.php");
		}

	} else if (!isset($_POST['pagetitle']) || !isset($_POST['pagecontents'])) {
		returnWithMsg("error", "times-circle", 0, "All fields are required.", false);

	} else if (isset($action) && $action === "newpage") {
		$pagetitle = $_POST['pagetitle'];
		$pagecontents = $_POST['pagecontents'];
		$user = $_SESSION['User'];
		$currtime = gmdate("Y-m-d H:i:s");

		// Query the database to insert the new page
		$mysqli = new DBConn();
		$mysqli->sql = sprintf("INSERT INTO pages (page_title, page_author, page_created, page_updated, page_contents) VALUES ('%s', '%s', '%s', '%s', '%s')",
			$mysqli->conn->real_escape_string($pagetitle),
			$mysqli->conn->real_escape_string($user),
			$mysqli->conn->real_escape_string($currtime),
			$mysqli->conn->real_escape_string($currtime),
			$mysqli->conn->real_escape_string($pagecontents)
		);
		$mysqli->conn->query($mysqli->sql);

		if ($mysqli->conn->affected_rows === 1) {
			$page_id = $mysqli->conn->insert_id; // Set global $page_id to the new ID
			returnWithMsg("success", "check-circle", 0, "The page was successfully created and saved.", false); // Redirect to new page's edit screen
		} else {
			returnWithMsg("error", "times-circle", 0, "Failed to create page. Please try again.", false);
		}

	} else if (isset($action) && $action === "editpage") {
		$pagetitle = $_POST['pagetitle'];
		$pagecontents = $_POST['pagecontents'];
		$currtime = gmdate("Y-m-d H:i:s");

		// Ensure $page_id is available for update
		if (!isset($page_id) || $page_id == 0) {
			returnWithMsg("error", "times-circle", 0, "No page ID specified for editing.", "pages.php");
		}

		// Query the database to update the page
		$mysqli = new DBConn();
		$mysqli->sql = sprintf("UPDATE pages SET page_title = '%s', page_contents = '%s', page_updated = '%s' WHERE page_id = '%d'",
			$mysqli->conn->real_escape_string($pagetitle),
			$mysqli->conn->real_escape_string($pagecontents),
			$mysqli->conn->real_escape_string($currtime),
			$page_id
		);
		$mysqli->conn->query($mysqli->sql);

		if ($mysqli->conn->affected_rows === 1) {
			returnWithMsg("success", "check-circle", 0, "The page was successfully updated with the new content.", false); // Redirect to current page's edit screen
		} else if ($mysqli->conn->affected_rows === 0) {
			returnWithMsg("success", "check-circle", 0, "No changes were made to the page content.", false);
		} else {
			returnWithMsg("error", "times-circle", 0, "Failed to update page. Please try again.", false);
		}
	}
?>