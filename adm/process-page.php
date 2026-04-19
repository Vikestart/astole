<?php
	// Start session
	session_start();

	// --- CONSTANTS ---
	// Define constants FIRST so error handlers can use them immediately
	define('MSG_TYPE_SUCCESS', 'success');
	define('MSG_TYPE_ERROR', 'error');
	define('MSG_TYPE_WARNING', 'warning');
	define('MSG_ICON_SUCCESS', 'check-circle');
	define('MSG_ICON_ERROR', 'times-circle');
	define('MSG_ICON_WARNING', 'exclamation-triangle');
	define('MSG_DEFAULT_EXPIRE', 4500);

	// --- STRICT POST CSRF CHECK ---
    // Since we use invisible forms for deletion, NO actions should ever happen via GET.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, 5000, "Security validation failed (CSRF).", false);
        }
    } else {
        // If accessed directly via URL, kick them to the pages list
        header("Location: pages.php");
        die();
    }
	
	require "../db.php"; 

	// --- GLOBAL DB CONNECTION ---
	$db_connection = new DBConn();
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	function returnWithMsg($type, $icon, $expire, $message, $redirect_url) {
		global $action, $page_id, $db_connection;
		$_SESSION['Sessionmsg'] = array(
			'origin' => $action,
			'type' => $type,
			'icon' => $icon,
			'expire' => $expire,
			'message' => $message
		);
		if ($redirect_url === false) {
			if ($page_id === null || $page_id === 0) {
				header("Location: pages.php");
			} else {
				header("Location: edit-page.php?t=edit&p=" . $page_id);
			}
		} else {
			header("Location: " . $redirect_url);
		}
		die();
	}

	// --- SECURITY CHECK ---
	if (!isset($_SESSION['UserID'])) {
		returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Invalid user session. Try signing out and back in.", "login.php");
	}

	// --- ACTIONS ---
	if ($action === "delpage") {
        // ID now comes safely from the invisible POST form
		if (isset($_POST['p'])) {
			$page_id = (int)$_POST['p'];
		} else {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "No page specified for deletion.", "pages.php");
		}

		$stmt = $db_connection->conn->prepare("DELETE FROM pages WHERE page_id = ?");
		$stmt->bind_param("i", $page_id); 
		$stmt->execute();

		if ($stmt->affected_rows === 1) {
			returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page has been deleted.", "pages.php");
		} else if ($stmt->affected_rows === 0) {
			returnWithMsg(MSG_TYPE_WARNING, MSG_ICON_WARNING, MSG_DEFAULT_EXPIRE, "Page not found or already deleted.", "pages.php");
		} else {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Error deleting page.", "pages.php");
		}
		$stmt->close();

	} else { 
		// "newpage" or "editpage" Actions
		if (!isset($_POST['pagetitle']) || trim($_POST['pagetitle']) === '' || !isset($_POST['pagecontents']) || trim($_POST['pagecontents']) === '') {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Page title and contents are required.", false);
		}

		$pagetitle = $_POST['pagetitle'];
        $pageslug = $_POST['pageslug']; 
		$pagecontents = $_POST['pagecontents'];
		$currtime = gmdate("Y-m-d H:i:s"); 

		if ($action === "newpage") {
			$stmt_author = $db_connection->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
			$stmt_author->bind_param("i", $_SESSION['UserID']);
			$stmt_author->execute();
			$stmt_author->bind_result($user);
			$stmt_author->fetch();
			$stmt_author->close();

			$stmt = $db_connection->conn->prepare("INSERT INTO pages (page_title, page_slug, page_author, page_created, page_updated, page_contents) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssss", $pagetitle, $pageslug, $user, $currtime, $currtime, $pagecontents); 
			$stmt->execute();

			if ($stmt->affected_rows === 1) {
				$page_id = $db_connection->conn->insert_id; 
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully created.", false); 
			} else {
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to create page.", false);
			}
			$stmt->close(); 

		} else if ($action === "editpage") {
			if (!isset($page_id) || $page_id == 0) {
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "No valid page ID.", "pages.php");
			}

			$stmt = $db_connection->conn->prepare("UPDATE pages SET page_title = ?, page_slug = ?, page_contents = ?, page_updated = ? WHERE page_id = ?");
			$stmt->bind_param("ssssi", $pagetitle, $pageslug, $pagecontents, $currtime, $page_id); 
			$stmt->execute();

			if ($stmt->affected_rows === 1) {
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully updated.", false); 
			} else if ($stmt->affected_rows === 0) {
				returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "No changes were made.", false);
			} else {
				returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Failed to update page.", false);
			}
			$stmt->close(); 
		}
	}
?>