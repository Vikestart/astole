<?php
session_start();
require_once "../upload-helper.php";
require_once "../db.php";
require_once "admin-functions.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    returnWithMsg("tickets", "error", "times-circle", 5000, "Security validation failed.", "tickets.php");
}

if (!isset($_SESSION['UserID'])) { header("Location: login.php"); die(); }

$db = new DBConn();

$stmt_auth = $db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
$stmt_auth->close();

if ($user_role == 3) { returnWithMsg("tickets", "error", "times-circle", 5000, "Permission denied.", "index.php"); }

$action = $_POST['action'] ?? '';
$ticket_id = (int)($_POST['ticket_id'] ?? 0);
$currtime = gmdate("Y-m-d H:i:s");

if ($action === 'delete_ticket') {
    // Fetch tracking_id before deleting for the Activity Log
    $stmt_trk = $db->conn->prepare("SELECT tracking_id FROM tickets WHERE id = ?");
    $stmt_trk->bind_param("i", $ticket_id);
    $stmt_trk->execute();
    $trk_res = $stmt_trk->get_result()->fetch_assoc();
    $tracking_id_del = $trk_res ? $trk_res['tracking_id'] : 'Unknown';
    $stmt_trk->close();

    $stmt_att = $db->conn->prepare("SELECT attachment FROM ticket_replies WHERE ticket_id = ? AND attachment IS NOT NULL");
    $stmt_att->bind_param("i", $ticket_id);
    $stmt_att->execute();
    $res_att = $stmt_att->get_result();
    while($r = $res_att->fetch_assoc()) {
        $files = json_decode($r['attachment'], true);
        if(is_array($files)) { foreach($files as $f) { @unlink(__DIR__ . '/../uploads/tickets/' . $f); } } 
        else { @unlink(__DIR__ . '/../uploads/tickets/' . $r['attachment']); }
    }
    $stmt_att->close();

    $stmt = $db->conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $stmt->close();
    
    $stmt_rep = $db->conn->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?");
    $stmt_rep->bind_param("i", $ticket_id);
    $stmt_rep->execute();
    $stmt_rep->close();

    // Updated log message to use Tracking ID
    returnWithMsg("tickets", "success", "check-circle", 4500, "Ticket and all replies deleted.", "tickets.php", $db->conn, 'Ticket', "Deleted Tracking ID: {$tracking_id_del} and all associated attachments.");
}

if ($action === 'admin_reply') {
    $message = trim($_POST['message'] ?? '');
    $status_update = $_POST['status_update'] ?? 'Answered';

    if (empty($message)) { returnWithMsg("tickets", "error", "times-circle", 4500, "You must provide a reply comment.", "view-ticket.php?id=" . $ticket_id); }

    $stmt_tkt = $db->conn->prepare("SELECT client_name, client_email, tracking_id, status FROM tickets WHERE id = ?");
    $stmt_tkt->bind_param("i", $ticket_id);
    $stmt_tkt->execute();
    $t_data = $stmt_tkt->get_result()->fetch_assoc();
    $stmt_tkt->close();

    if ($t_data) {
        if ($t_data['status'] !== $status_update) {
            $stmt_admin = $db->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
            $stmt_admin->bind_param("i", $_SESSION['UserID']);
            $stmt_admin->execute();
            $admin_res = $stmt_admin->get_result()->fetch_assoc();
            $admin_name = $admin_res ? $admin_res['user_uid'] : 'an Administrator';
            $stmt_admin->close();

            $sys_msg = "Status changed from [b]{$t_data['status']}[/b] to [b]{$status_update}[/b] by {$admin_name}.";
            $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
            $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
            $stmt_sys->execute();
            $stmt_sys->close();
        }

        $attachment = null;
        if (!empty($_FILES['attachment']['name'][0])) {
            $upload_res = processMultipleAttachments($_FILES['attachment'], __DIR__ . '/../uploads/tickets');
            if (is_array($upload_res) && isset($upload_res['error'])) { returnWithMsg("tickets", "error", "times-circle", 4500, $upload_res['error'], "view-ticket.php?id=" . $ticket_id); }
            $attachment = $upload_res;
        }

        $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, attachment, created_at) VALUES (?, 'Admin', ?, ?, ?)");
        $stmt_msg->bind_param("isss", $ticket_id, $message, $attachment, $currtime);
        $stmt_msg->execute();
        $stmt_msg->close();

        $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?");
        $stmt_upd->bind_param("ssi", $status_update, $currtime, $ticket_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_msg_reply', 'ticket_msg_closed_admin')");
        $settings = [];
        if ($res_stg) { while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; } }

        $site_name = $settings['site_name'] ?? 'Support';
        $safe_noreply = "noreply@" . $_SERVER['HTTP_HOST'];
        $encoded_site_name = '=?UTF-8?B?' . base64_encode($site_name) . '?=';
        $headers = "From: " . $encoded_site_name . " <" . $safe_noreply . ">\r\nReply-To: " . $safe_noreply . "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
        
        $body_text = "Hello " . trim($t_data['client_name']) . ",\n\n";
        $send_email = false;

        if (!empty($message)) {
            $body_text .= (!empty($settings['ticket_msg_reply']) ? $settings['ticket_msg_reply'] : "An administrator has replied to your ticket.") . "\n\n";
            $send_email = true;
        }
        
        if ($status_update === 'Closed') {
            $body_text .= (!empty($settings['ticket_msg_closed_admin']) ? $settings['ticket_msg_closed_admin'] : "Your ticket has been marked as resolved and closed.") . "\n\n";
            $send_email = true;
        }

        if ($send_email) {
            $portal_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/view-ticket.php?id=" . urlencode($t_data['tracking_id']) . "&email=" . urlencode($t_data['client_email']);
            $body_text .= "Tracking ID: " . $t_data['tracking_id'] . "\n\n--- Please do not reply directly to this email ---\nYou can view the full thread and respond securely via this direct link: \n" . $portal_url;
            mail(trim($t_data['client_email']), "Ticket Update: " . $t_data['tracking_id'], $body_text, $headers, "-f" . $safe_noreply);
        }
    }

    // Updated log message to use Tracking ID
    returnWithMsg("tickets", "success", "check-circle", 4500, "Ticket updated successfully.", "view-ticket.php?id=" . $ticket_id, $db->conn, 'Ticket', "Replied to Tracking ID: {$t_data['tracking_id']} and set status to: {$status_update}");
}

if ($action === 'assign_ticket') {
    $new_assignee = (int)($_POST['assigned_to'] ?? 0);
    $assignee_val = ($new_assignee === 0) ? null : $new_assignee;

    // Modified to also fetch the tracking_id for the log
    $stmt_check = $db->conn->prepare("SELECT assigned_to, tracking_id FROM tickets WHERE id = ?");
    $stmt_check->bind_param("i", $ticket_id);
    $stmt_check->execute();
    $t_info = $stmt_check->get_result()->fetch_assoc();
    $curr_assigned = $t_info['assigned_to'] ?? null;
    $tracking_id = $t_info['tracking_id'] ?? 'Unknown';
    $stmt_check->close();

    if ($curr_assigned !== $assignee_val) {
        // Update ticket
        $stmt_upd = $db->conn->prepare("UPDATE tickets SET assigned_to = ?, updated_at = ? WHERE id = ?");
        $stmt_upd->bind_param("isi", $assignee_val, $currtime, $ticket_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // Get names for the logging/messages
        $staff_name = "Unassigned";
        if ($assignee_val) {
            $stmt_u = $db->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
            $stmt_u->bind_param("i", $assignee_val);
            $stmt_u->execute();
            $staff_name = $stmt_u->get_result()->fetch_assoc()['user_uid'] ?? 'Unknown';
            $stmt_u->close();
        }

        $stmt_admin = $db->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
        $stmt_admin->bind_param("i", $_SESSION['UserID']);
        $stmt_admin->execute();
        $admin_name = $stmt_admin->get_result()->fetch_assoc()['user_uid'] ?? 'Admin';
        $stmt_admin->close();

        // Add System Message to Ticket Thread
        $sys_msg = "Ticket assigned to [b]{$staff_name}[/b] by {$admin_name}.";
        $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
        $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
        $stmt_sys->execute();
        $stmt_sys->close();

        // Updated log message to use Tracking ID
        returnWithMsg("tickets", "success", "user-check", 3000, "Ticket assignment updated to: $staff_name", "view-ticket.php?id=" . $ticket_id, $db->conn, 'Ticket', "Assigned ticket {$tracking_id} to {$staff_name}");
    }

    header("Location: view-ticket.php?id=" . $ticket_id);
    exit();
}
?>