<?php
	session_start();
	require "admin-functions.php";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            returnWithMsg('page', 'error', 'times-circle', 5000, "Security validation failed (CSRF).", "pages.php");
        }
    } else {
        header("Location: pages.php"); die();
    }
	
	require "../db.php";
	$db_connection = new DBConn();
	$page_id = isset($_SESSION['acp_page_id']) ? (int)$_SESSION['acp_page_id'] : 0;
	$action = isset($_POST['action']) ? $_POST['action'] : '';
	$active_user_uid = $_SESSION['UserUID'] ?? 'Unknown';

    // FIX 1: Look for the exact action string 'delpage' and grab ID from 'p'
	if ($action === "delpage") {
		$del_id = (int)($_POST['p'] ?? 0);
		$stmt = $db_connection->conn->prepare("DELETE FROM pages WHERE page_id = ?");
		$stmt->bind_param("i", $del_id);
		$stmt->execute();
		$stmt->close();
		returnWithMsg($action, "success", "check-circle", 4500, "The page has been deleted.", "pages.php", $db_connection->conn, 'Page', "Deleted page ID: " . $del_id);

	} else { 
		$pagetitle = trim($_POST['pagetitle'] ?? '');
        $pageslug = trim($_POST['pageslug'] ?? ''); 
        $pagedesc = trim($_POST['pagedesc'] ?? '');
		$pagecontents = trim($_POST['pagecontents'] ?? '');
		$currtime = gmdate("Y-m-d H:i:s"); 

		if (empty($pagetitle) || empty($pagecontents)) { returnWithMsg($action, "error", "times-circle", 4500, "Page title and contents are required.", "edit-page.php?t=" . ($action === 'newpage' ? 'new' : 'edit&id='.$page_id)); }

		if ($action === "newpage") {
			$stmt = $db_connection->conn->prepare("INSERT INTO pages (page_title, page_slug, page_desc, page_author, page_created, page_updated, page_contents) VALUES (?, ?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("sssssss", $pagetitle, $pageslug, $pagedesc, $active_user_uid, $currtime, $currtime, $pagecontents); 
			$stmt->execute();
			$page_id = $db_connection->conn->insert_id; 
			returnWithMsg($action, "success", "check-circle", 4500, "The page was successfully created.", "pages.php", $db_connection->conn, 'Page', "Created the page: " . $pagetitle); 

		} else if ($action === "editpage") {
			$stmt = $db_connection->conn->prepare("UPDATE pages SET page_title = ?, page_slug = ?, page_desc = ?, page_updated = ?, page_contents = ? WHERE page_id = ?");
			$stmt->bind_param("sssssi", $pagetitle, $pageslug, $pagedesc, $currtime, $pagecontents, $page_id); 
			$stmt->execute();
			returnWithMsg($action, "success", "check-circle", 4500, "The page was successfully updated.", "edit-page.php?t=edit&id=" . $page_id, $db_connection->conn, 'Page', "Updated the page: " . $pagetitle); 
		}
	}
?>