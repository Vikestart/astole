<?php
	session_start();
	require "admin-functions.php";

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
			returnWithMsg('settings', 'error', 'times-circle', 5000, "Security validation failed (CSRF).", "settings.php");
		}
	} else {
		header("Location: settings.php"); die();
	}

	require "../db.php";
	$db_connection = new DBConn();

	if (!isset($_SESSION['UserID'])) { header("Location: login.php"); die(); }

	$active_user_id = (int)$_SESSION['UserID'];
	$stmt_auth = $db_connection->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
	$stmt_auth->bind_param("i", $active_user_id);
	$stmt_auth->execute();
	$user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
	$stmt_auth->close();

	if ($user_role !== 1) {
		returnWithMsg('settings', 'error', 'times-circle', 5000, "Only Administrators can modify system settings.", "settings.php");
	}

	$updates = [
		'site_name' => trim($_POST['site_name'] ?? ''),
		'site_email' => trim($_POST['site_email'] ?? ''),
		'maintenance_mode' => (int)($_POST['maintenance_mode'] ?? 0),
		'seo_description' => trim($_POST['seo_description'] ?? ''),
		'ga_id' => trim($_POST['ga_id'] ?? ''),
		'recaptcha_site' => trim($_POST['recaptcha_site'] ?? ''),
		'recaptcha_secret' => trim($_POST['recaptcha_secret'] ?? ''),
		'ticket_system_enabled' => (int)($_POST['ticket_system_enabled'] ?? 0),
		'ticket_creation_enabled' => (int)($_POST['ticket_creation_enabled'] ?? 0),
		'ticket_notify_admin_new' => (int)($_POST['ticket_notify_admin_new'] ?? 0),
		'ticket_notify_admin_reply' => (int)($_POST['ticket_notify_admin_reply'] ?? 0),
		'ticket_autoclose_hours' => (int)($_POST['ticket_autoclose_hours'] ?? 72),
		'ticket_msg_received' => trim($_POST['ticket_msg_received'] ?? ''),
		'ticket_msg_reply' => trim($_POST['ticket_msg_reply'] ?? ''),
		'ticket_msg_closed_admin' => trim($_POST['ticket_msg_closed_admin'] ?? ''),
		'ticket_msg_closed_auto' => trim($_POST['ticket_msg_closed_auto'] ?? ''),
		'attachment_retention_days' => (int)($_POST['attachment_retention_days'] ?? 365)
	];

	$stmt = $db_connection->conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
	$changes_made = 0;
	foreach ($updates as $key => $value) {
		$stmt->bind_param("ss", $value, $key);
		$stmt->execute();
		if ($stmt->affected_rows > 0) { $changes_made++; }
	}
	$stmt->close();

	if ($changes_made > 0) {
        // Boom! Look how clean the logging is here!
		returnWithMsg("settings", "success", "check-circle", 4500, "System settings have been successfully updated.", "settings.php", $db_connection->conn, 'Settings', 'Updated system settings.');
	} else {
		returnWithMsg("settings", "success", "check-circle", 4500, "No changes were made to the settings.", "settings.php");
	}
?>