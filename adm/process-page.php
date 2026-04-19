<?php
	session_start();

	define('MSG_TYPE_SUCCESS', 'success');
	define('MSG_TYPE_ERROR', 'error');
	define('MSG_TYPE_WARNING', 'warning');
	define('MSG_ICON_SUCCESS', 'check-circle');
	define('MSG_ICON_ERROR', 'times-circle');
	define('MSG_ICON_WARNING', 'exclamation-triangle');
	define('MSG_DEFAULT_EXPIRE', 4500);

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, 5000, "Security validation failed (CSRF).", false);
        }
    } else {
        header("Location: pages.php"); die();
    }
	
	require "../db.php"; 
	$db_connection = new DBConn();
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;
	$action = isset($_POST['action']) ? $_POST['action'] : '';

	function returnWithMsg($type, $icon, $expire, $message, $redirect_url) {
		global $action, $page_id;
		$_SESSION['Sessionmsg'] = array('origin' => $action, 'type' => $type, 'icon' => $icon, 'expire' => $expire, 'message' => $message);
		if ($redirect_url === false) {
			header("Location: " . (($page_id === null || $page_id === 0) ? "pages.php" : "edit-page.php?t=edit&p=" . $page_id));
		} else {
			header("Location: " . $redirect_url);
		}
		die();
	}

	if (!isset($_SESSION['UserID'])) { returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Invalid user session.", "login.php"); }

	// --- FETCH ACTIVE USER UID & ROLE ---
	$stmt_active = $db_connection->conn->prepare("SELECT user_role, user_uid FROM users WHERE user_id = ?");
	$stmt_active->bind_param("i", $_SESSION['UserID']);
	$stmt_active->execute();
	$res_active = $stmt_active->get_result()->fetch_assoc();
	$active_user_role = (int)$res_active['user_role'];
	$active_user_uid = $res_active['user_uid'];
	$stmt_active->close();

	// --- VERIFY PAGE OWNERSHIP FOR EDITS & DELETES ---
	if ($action === "delpage" || $action === "editpage") {
		$check_id = ($action === "delpage") ? (int)($_POST['p'] ?? 0) : $page_id;
		
		$stmt_auth = $db_connection->conn->prepare("SELECT page_author FROM pages WHERE page_id = ?");
		$stmt_auth->bind_param("i", $check_id);
		$stmt_auth->execute();
		$res_auth = $stmt_auth->get_result();
		
		if ($res_auth->num_rows === 0) { returnWithMsg(MSG_TYPE_WARNING, MSG_ICON_WARNING, MSG_DEFAULT_EXPIRE, "Page not found.", "pages.php"); }
		$page_author = $res_auth->fetch_assoc()['page_author'];
		$stmt_auth->close();

		if ($active_user_role == 3 && $page_author !== $active_user_uid) {
			returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, 5000, "Permission denied. You can only modify your own pages.", "pages.php");
		}
	}

	// --- ACTIONS ---
	if ($action === "delpage") {
		$target_id = (int)$_POST['p'];
		$stmt = $db_connection->conn->prepare("DELETE FROM pages WHERE page_id = ?");
		$stmt->bind_param("i", $target_id); 
		$stmt->execute();
		returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page has been deleted.", "pages.php");

	} else { 
		$pagetitle = trim($_POST['pagetitle'] ?? '');
        $pageslug = trim($_POST['pageslug'] ?? ''); 
		$pagecontents = trim($_POST['pagecontents'] ?? '');
		$currtime = gmdate("Y-m-d H:i:s"); 

		if (empty($pagetitle) || empty($pagecontents)) { returnWithMsg(MSG_TYPE_ERROR, MSG_ICON_ERROR, MSG_DEFAULT_EXPIRE, "Page title and contents are required.", false); }

		if ($action === "newpage") {
			$stmt = $db_connection->conn->prepare("INSERT INTO pages (page_title, page_slug, page_author, page_created, page_updated, page_contents) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssss", $pagetitle, $pageslug, $active_user_uid, $currtime, $currtime, $pagecontents); 
			$stmt->execute();
			$page_id = $db_connection->conn->insert_id; 
			returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully created.", false); 

		} else if ($action === "editpage") {
			$stmt = $db_connection->conn->prepare("UPDATE pages SET page_title = ?, page_slug = ?, page_contents = ?, page_updated = ? WHERE page_id = ?");
			$stmt->bind_param("ssssi", $pagetitle, $pageslug, $pagecontents, $currtime, $page_id); 
			$stmt->execute();
			returnWithMsg(MSG_TYPE_SUCCESS, MSG_ICON_SUCCESS, MSG_DEFAULT_EXPIRE, "The page was successfully updated.", false); 
		}
	}
?>