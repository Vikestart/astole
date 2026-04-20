<?php
session_start();
require "../db.php";

function returnWithMsg($type, $icon, $expire, $message, $redirect) {
    $_SESSION['Sessionmsg'] = array('origin' => 'tickets', 'type' => $type, 'icon' => $icon, 'expire' => $expire, 'message' => $message);
    header("Location: " . $redirect);
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    returnWithMsg("error", "times-circle", 5000, "Security validation failed.", "tickets.php");
}

if (!isset($_SESSION['UserID'])) { header("Location: login.php"); die(); }

$db = new DBConn();

// Verify Role (Only Admins=1 and Mods=2 can handle tickets)
$stmt_auth = $db->conn->prepare("SELECT user_role FROM users WHERE user_id = ?");
$stmt_auth->bind_param("i", $_SESSION['UserID']);
$stmt_auth->execute();
$user_role = (int)$stmt_auth->get_result()->fetch_assoc()['user_role'];
$stmt_auth->close();

if ($user_role == 3) {
    returnWithMsg("error", "times-circle", 5000, "Permission denied. You cannot manage tickets.", "index.php");
}

$action = $_POST['action'] ?? '';
$ticket_id = (int)($_POST['ticket_id'] ?? 0);
$currtime = gmdate("Y-m-d H:i:s");

// --- ACTION: DELETE TICKET ---
if ($action === 'delete_ticket') {
    $stmt = $db->conn->prepare("DELETE FROM tickets WHERE id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $stmt->close();
    
    // Also delete all replies
    $stmt_rep = $db->conn->prepare("DELETE FROM ticket_replies WHERE ticket_id = ?");
    $stmt_rep->bind_param("i", $ticket_id);
    $stmt_rep->execute();
    $stmt_rep->close();

    returnWithMsg("success", "check-circle", 4500, "Ticket and all replies deleted.", "tickets.php");
}

// --- ACTION: ADMIN REPLY OR CLOSE ---
if ($action === 'admin_reply') {
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));
    $status_update = $_POST['status_update'] ?? 'Answered';

    // Strictly require a message, no matter what status is chosen!
    if (empty($message)) {
        returnWithMsg("error", "times-circle", 4500, "You must provide a reply comment.", "view-ticket.php?id=" . $ticket_id);
    }

    if (!empty($message)) {
        $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'Admin', ?, ?)");
        $stmt_msg->bind_param("iss", $ticket_id, $message, $currtime);
        $stmt_msg->execute();
        $stmt_msg->close();
    }

    // Check if status actually changed
    if ($t_data['status'] !== $status_update) {
        $sys_msg = "Status changed from {$t_data['status']} to {$status_update} by Admin.";
        $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
        $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
        $stmt_sys->execute();
        $stmt_sys->close();
    }

    $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?");
    $stmt_upd->bind_param("ssi", $status_update, $currtime, $ticket_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    // --- EMAIL NOTIFICATIONS (ADMIN ACTION) ---
    $stmt_tkt = $db->conn->prepare("SELECT client_name, client_email, tracking_id FROM tickets WHERE id = ?");
    $stmt_tkt->bind_param("i", $ticket_id);
    $stmt_tkt->execute();
    $t_data = $stmt_tkt->get_result()->fetch_assoc();
    $stmt_tkt->close();

    if ($t_data) {
        // Fetch Settings safely
        $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_msg_reply', 'ticket_msg_closed_admin')");
        $settings = [];
        if ($res_stg) { while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; } }

        $site_name = $settings['site_name'] ?? 'Support';
        $safe_noreply = "noreply@" . $_SERVER['SERVER_NAME'];
        
        // Force both 'From' and 'Reply-To' to be the system's noreply address
        $headers = "From: " . $site_name . " <" . $safe_noreply . ">\r\n" .
                   "Reply-To: " . $safe_noreply . "\r\n" .
                   "MIME-Version: 1.0\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        $body_text = "Hello " . $t_data['client_name'] . ",\n\n";
        $send_email = false;

        if (!empty($message)) {
            $msg_reply = !empty($settings['ticket_msg_reply']) ? $settings['ticket_msg_reply'] : "An administrator has replied to your ticket.";
            $body_text .= $msg_reply . "\n\n";
            $send_email = true;
        }
        
        if ($status_update === 'Closed') {
            $msg_closed = !empty($settings['ticket_msg_closed_admin']) ? $settings['ticket_msg_closed_admin'] : "Your ticket has been marked as resolved and closed.";
            $body_text .= $msg_closed . "\n\n";
            $send_email = true;
        }

        if ($send_email) {
            $portal_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'];
            
            // Add the "Do not reply" warning and the portal link
            $body_text .= "Tracking ID: " . $t_data['tracking_id'] . "\n\n";
            $body_text .= "--- Please do not reply directly to this email ---\n";
            $body_text .= "You can view the full thread and respond on our secure support portal: \n" . $portal_url;
            
            @mail($t_data['client_email'], "Ticket Update: " . $t_data['tracking_id'], $body_text, $headers);
        }
    }

    returnWithMsg("success", "check-circle", 4500, "Ticket updated successfully.", "view-ticket.php?id=" . $ticket_id);
}
?>