<?php
	session_start();
	require "admin-functions.php";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            returnWithMsg('page', 'error', 'times-circle', 5000, "Security validation failed (CSRF).", "pages");
        }
    } else {
        header("Location: pages"); die();
    }
	
	require "../db.php";
	$db_connection = new DBConn();
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	
    // FIX 1: Reliably fetch the active user's UID for the Author field
	$active_user_uid = 'Unknown';
    if (isset($_SESSION['UserID'])) {
        $stmt_u = $db_connection->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
        $stmt_u->bind_param("i", $_SESSION['UserID']);
        $stmt_u->execute();
        $active_user_uid = $stmt_u->get_result()->fetch_assoc()['user_uid'] ?? 'Unknown';
        $stmt_u->close();
    }

	if ($action === "delpage") {
		$del_id = (int)($_POST['p'] ?? 0);
        
        // FIX 2: Fetch the page title BEFORE deleting so we can log it properly
        $stmt_t = $db_connection->conn->prepare("SELECT page_title FROM pages WHERE page_id = ?");
        $stmt_t->bind_param("i", $del_id);
        $stmt_t->execute();
        $del_title = $stmt_t->get_result()->fetch_assoc()['page_title'] ?? 'Unknown Page';
        $stmt_t->close();

		$stmt = $db_connection->conn->prepare("DELETE FROM pages WHERE page_id = ?");
		$stmt->bind_param("i", $del_id);
		$stmt->execute();
		$stmt->close();
        
		returnWithMsg($action, "success", "check-circle", 4500, "The page has been deleted.", "pages", $db_connection->conn, 'Page', "Deleted the page: " . $del_title);

	} else { 
		$pagetitle = trim($_POST['pagetitle'] ?? '');
        $pageslug = trim($_POST['pageslug'] ?? ''); 
        $pagedesc = trim($_POST['pagedesc'] ?? '');
        $pagetype = $_POST['pagetype'] ?? 'Standard';
		$pagecontents = trim($_POST['pagecontents'] ?? '');
		$currtime = gmdate("Y-m-d H:i:s"); 

        // Bypass empty content validation if it's a custom template
		if (empty($pagetitle) || ($pagetype === 'Standard' && empty($pagecontents))) { 
            returnWithMsg($action, "error", "times-circle", 4500, "Page title and contents are required.", "pages?action=" . ($action === 'newpage' ? 'new' : "edit&p={$page_id}")); 
        }

		if ($action === "newpage") {
			$stmt = $db_connection->conn->prepare("INSERT INTO pages (page_title, page_slug, page_desc, page_author, page_created, page_updated, page_contents, page_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssssss", $pagetitle, $pageslug, $pagedesc, $active_user_uid, $currtime, $currtime, $pagecontents, $pagetype); 
			$stmt->execute();
			$page_id = $db_connection->conn->insert_id; 
			returnWithMsg($action, "success", "check-circle", 4500, "The page was successfully created.", "pages", $db_connection->conn, 'Page', "Created the page: " . $pagetitle); 

		} else if ($action === "editpage") {
			$stmt = $db_connection->conn->prepare("UPDATE pages SET page_title = ?, page_slug = ?, page_desc = ?, page_updated = ?, page_contents = ?, page_type = ? WHERE page_id = ?");
			$stmt->bind_param("ssssssi", $pagetitle, $pageslug, $pagedesc, $currtime, $pagecontents, $pagetype, $page_id); 
			$stmt->execute();
			returnWithMsg($action, "success", "check-circle", 4500, "The page was successfully updated.", "pages?action=edit&p=" . $page_id, $db_connection->conn, 'Page', "Updated the page: " . $pagetitle); 
		}
	}
?>