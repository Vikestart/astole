<?php
	// Start session and get database configuration
	session_start();
	require "../db.php"; // Include DB connection class

	// --- CONSTANTS ---
	// Message Types for $_SESSION['Sessionmsg']
	define('MSG_TYPE_SUCCESS', 'success');
	define('MSG_TYPE_ERROR', 'error');
	define('MSG_TYPE_WARNING', 'warning'); // Added for general use

	// Message Icons for $_SESSION['Sessionmsg']
	define('MSG_ICON_SUCCESS', 'check-circle');
	define('MSG_ICON_ERROR', 'times-circle');
	define('MSG_ICON_WARNING', 'exclamation-triangle');

	// Default Message Expiry (milliseconds)
	define('MSG_DEFAULT_EXPIRE', 4500); // Standard value if 0 is passed to expire

	// --- GLOBAL DB CONNECTION ---
	// Create a single DB connection instance that will be reused throughout this script.
	$db_connection = new DBConn();

	// Initialize $page_id. This variable will hold the ID of the page being processed.
	// It will be updated based on GET parameters (for existing pages) or insert_id (for new pages).
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;

	// Determine which action is to be performed on the page (e.g., 'delpage', 'newpage', 'editpage').
	$action = ($_GET['a'] == 'del') ? 'delpage' : (isset($_POST['action']) ? $_POST['action'] : '');

	// Function for returning back to a page with a session message.
	// $redirect_url: If false, redirects to the page's edit screen. Otherwise, redirects to the specified URL.
	function returnWithMsg($type, $icon, $expire, $message, $redirect_url) {
		global $action;
		global $page_id; // Access the global $page_id for redirection
		global $db_connection; // Access the global DB connection for error logging if needed

		// Set the session message using associative keys.
		$_SESSION['Sessionmsg'] = array(
			'origin' => $action,
			'type' => $type,
			'icon' => $icon,
			'expire' => $expire,
			'message' => $message
		);

		// Determine the redirection target.
		if ($redirect_url === false) {
			// Special case: Redirect to the newly created/edited page.
			// Ensure $page_id is valid for redirection to an edit page.
			if ($page_id === null || $page_id === 0) {
				// Fallback to the main pages list if page_id is not properly set
				// (e.g., in case of an error during new page creation before ID is known).
				header("Location: pages.php");
			} else {
				header("Location: edit-page.php?t=edit&p=" . $page_id);
			}
		} else {
			// Redirect to the explicitly provided URL.
			header("Location: " . $redirect_url);
		}
		// Always terminate script execution after a header redirect.
		die();
	}

	// --- SECURITY CHECK ---
	// Ensure a user is logged in before proceeding with any actions.
	if (!isset($_SESSION['User'])) {
		returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Invalid user session. Try signing out and back in.", "login.php");
	}

	// --- ACTIONS ---

	// Handle "Delete Page" Action
	if ($action === "delpage") {
		// Get page ID from GET parameter for deletion.
		if (isset($_GET['p'])) {
			$page_id = (int)$_GET['p'];
		} else {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "No page specified for deletion.", "pages.php");
		}

		// Use a prepared statement for secure deletion.
		$stmt = $db_connection->conn->prepare("DELETE FROM pages WHERE page_id = ?");
		if ($stmt === false) {
			error_log("process-page.php: Prepare delete page failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to prepare delete query.", "pages.php");
		}
		$stmt->bind_param("i", $page_id); // "i" for integer
		$stmt->execute();

		if ($stmt->affected_rows === 1) {
			returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page has been deleted as requested.", "pages.php");
		} else if ($stmt->affected_rows === 0) {
			// If 0 rows affected, page might not exist or was already deleted.
			returnWithMsg(MSG_TYPE_WARNING, MSG_ICON_WARNING, MSG_DEFAULT_EXPIRE, "Page not found or already deleted.", "pages.php");
		} else {
			// Log unexpected number of affected rows.
			error_log("process-page.php: Failed to delete page ID: " . $page_id . ". Affected rows: " . $stmt->affected_rows . ". Error: " . $stmt->error);
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Error deleting page.", "pages.php");
		}
		$stmt->close(); // Close the prepared statement

	} else { // Handle actions that require POST data: "newpage" or "editpage"
		// Validate required POST fields.
		if (!isset($_POST['pagetitle']) || trim($_POST['pagetitle']) === '' || !isset($_POST['pagecontents']) || trim($_POST['pagecontents']) === '') {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Page title and contents are required.", false);
		}

		$pagetitle = $_POST['pagetitle'];
		$pagecontents = $_POST['pagecontents'];
		$currtime = gmdate("Y-m-d H:i:s"); // Current GMT time for created/updated fields

		if ($action === "newpage") {
			$user = $_SESSION['User']; // Author for the new page

			// Use a prepared statement for secure insertion of new page data.
			$stmt = $db_connection->conn->prepare("INSERT INTO pages (page_title, page_author, page_created, page_updated, page_contents) VALUES (?, ?, ?, ?, ?)");
			if ($stmt === false) {
				error_log("process-page.php: Prepare insert page failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to prepare insert query.", false);
			}
			$stmt->bind_param("sssss", $pagetitle, $user, $currtime, $currtime, $pagecontents); // "sssss" for five strings
			$stmt->execute();

			if ($stmt->affected_rows === 1) {
				$page_id = $db_connection->conn->insert_id; // Update global $page_id with the new ID for redirection
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully created and saved.", false); // Redirect to new page's edit screen
			} else {
				error_log("process-page.php: Failed to insert new page. Affected rows: " . $stmt->affected_rows . ". Error: " . $stmt->error);
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to create page. Please try again.", false);
			}
			$stmt->close(); // Close the prepared statement

		} else if ($action === "editpage") {
			// Ensure $page_id is available and valid for updating an existing page.
			if (!isset($page_id) || $page_id == 0) {
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "No valid page ID specified for editing.", "pages.php");
			}

			// Use a prepared statement for secure update of page data.
			$stmt = $db_connection->conn->prepare("UPDATE pages SET page_title = ?, page_contents = ?, page_updated = ? WHERE page_id = ?");
			if ($stmt === false) {
				error_log("process-page.php: Prepare update page failed: (" . $db_connection->conn->errno . ") " . $db_connection->conn->error);
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to prepare update query.", false);
			}
			$stmt->bind_param("sssi", $pagetitle, $pagecontents, $currtime, $page_id); // "sssi" for two strings, one string, one integer
			$stmt->execute();

			if ($stmt->affected_rows === 1) {
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully updated with the new content.", false); // Redirect to current page's edit screen
			} else if ($stmt->affected_rows === 0) {
				// If 0 rows affected, it means no changes were made to the existing data.
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "No changes were made to the page content.", false);
			} else {
				// Log unexpected number of affected rows.
				error_log("process-page.php: Failed to update page ID: " . $page_id . ". Affected rows: " . $stmt->affected_rows . ". Error: " . $stmt->error);
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to update page. Please try again.", false);
			}
			$stmt->close(); // Close the prepared statement
		}
	}
?>