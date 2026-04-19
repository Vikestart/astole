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
    $status_update = $_POST['status_update'] ?? 'Answered'; // Default to Answered when replying

    if (empty($message) && $status_update !== 'Closed') {
        returnWithMsg("error", "times-circle", 4500, "Message cannot be empty.", "view-ticket.php?id=" . $ticket_id);
    }

    if (!empty($message)) {
        $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'Admin', ?, ?)");
        $stmt_msg->bind_param("iss", $ticket_id, $message, $currtime);
        $stmt_msg->execute();
        $stmt_msg->close();
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
        $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_msg_reply', 'ticket_msg_closed_admin')");
        $settings = [];
        while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; }

        $headers = "From: " . $settings['site_name'] . " <noreply@" . $_SERVER['SERVER_NAME'] . ">\r\nReply-To: " . $settings['site_email'] . "\r\nX-Mailer: PHP/" . phpversion();
        
        $body_text = "Hello " . $t_data['client_name'] . ",\n\n";
        $send_email = false;

        if (!empty($message) && !empty($settings['ticket_msg_reply'])) {
            $body_text .= $settings['ticket_msg_reply'] . "\n\n";
            $send_email = true;
        }
        if ($status_update === 'Closed' && !empty($settings['ticket_msg_closed_admin'])) {
            $body_text .= $settings['ticket_msg_closed_admin'] . "\n\n";
            $send_email = true;
        }

        if ($send_email) {
            $body_text .= "Tracking ID: " . $t_data['tracking_id'] . "\nYou can view the full thread on our support portal.";
            @mail($t_data['client_email'], "Ticket Update: " . $t_data['tracking_id'], $body_text, $headers);
        }
    }

    returnWithMsg("success", "check-circle", 4500, "Ticket updated successfully.", "view-ticket.php?id=" . $ticket_id);
}
?>