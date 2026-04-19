<?php
	session_start();

	// --- STRICT POST CSRF CHECK ---
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			$_SESSION['Sessionmsg'] = array('origin' => 'settings', 'type' => 'error', 'icon' => 'times-circle', 'expire' => 5000, 'message' => "Security validation failed (CSRF).");
			header("Location: settings.php"); die();
		}
	} else {
		header("Location: settings.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();

	if (!isset($_SESSION['UserID'])) {
		header("Location: login.php"); die();
	}

	// Security: Only Admins (Role 1) can change global settings
	$active_user_id = (int)$_SESSION['UserID'];
	$stmt_auth = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
	$stmt_auth->bind_param("i", $active_user_id);
	$stmt_auth->execute();
	$user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
	$stmt_auth->close();

	if ($user_role !== 1) {
		$_SESSION['Sessionmsg'] = array('origin' => 'settings', 'type' => 'error', 'icon' => 'times-circle', 'expire' => 5000, 'message' => "Only Administrators can modify global settings.");
		header("Location: settings.php"); die();
	}

	// --- PROCESS UPDATES ---
    // Map the POST data to the exact database keys
	$updates = [
		'site_name' => trim($_POST['site_name'] ?? ''),
		'site_email' => trim($_POST['site_email'] ?? ''),
		'maintenance_mode' => (int)($_POST['maintenance_mode'] ?? 0),
		'seo_description' => trim($_POST['seo_description'] ?? ''),
		'ga_id' => trim($_POST['ga_id'] ?? '')
	];

	// Prepare a single update statement that we will loop through
	$stmt = $db_connection->conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
	
	$changes_made = 0;
	foreach ($updates as $key => $value) {
		$stmt->bind_param("ss", $value, $key);
		$stmt->execute();
		if ($stmt->affected_rows > 0) {
			$changes_made++;
		}
	}
	$stmt->close();

	if ($changes_made > 0) {
		$_SESSION['Sessionmsg'] = array('origin' => 'settings', 'type' => 'success', 'icon' => 'check-circle', 'expire' => 4500, 'message' => "Global settings have been successfully updated.");
	} else {
		$_SESSION['Sessionmsg'] = array('origin' => 'settings', 'type' => 'success', 'icon' => 'check-circle', 'expire' => 4500, 'message' => "No changes were made to the settings.");
	}

	header("Location: settings.php");
	die();
?>