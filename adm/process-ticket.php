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
    
    // Also delete all replies associated with it
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

    // 1. Strictly require a message, no matter what status is chosen!
    if (empty($message)) {
        returnWithMsg("error", "times-circle", 4500, "You must provide a reply comment.", "view-ticket.php?id=" . $ticket_id);
    }

    // 2. Fetch current ticket data (including 'status' so we can check if it changes)
    $stmt_tkt = $db->conn->prepare("SELECT client_name, client_email, tracking_id, status FROM tickets WHERE id = ?");
    $stmt_tkt->bind_param("i", $ticket_id);
    $stmt_tkt->execute();
    $t_data = $stmt_tkt->get_result()->fetch_assoc();
    $stmt_tkt->close();

    if ($t_data) {
        
        // 3. Status History Log: Grab Admin Name & Log Change
        if ($t_data['status'] !== $status_update) {
            // Fetch Admin Name
            $stmt_admin = $db->conn->prepare("SELECT user_uid FROM users WHERE user_id = ?");
            $stmt_admin->bind_param("i", $_SESSION['UserID']);
            $stmt_admin->execute();
            $admin_res = $stmt_admin->get_result()->fetch_assoc();
            $admin_name = $admin_res ? $admin_res['user_uid'] : 'an Administrator';
            $stmt_admin->close();

            // Store message with safe [b] brackets for bolding
            $sys_msg = "Status changed from [b]{$t_data['status']}[/b] to [b]{$status_update}[/b] by {$admin_name}.";
            $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
            $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
            $stmt_sys->execute();
            $stmt_sys->close();
        }

        // Process Attachment (Note the path goes up one level `..`)
        $attachment = null;
        if (!empty($_FILES['attachment']['name'])) {
            $upload_res = processTicketAttachment($_FILES['attachment'], __DIR__ . '/../uploads/tickets');
            if (is_array($upload_res) && isset($upload_res['error'])) {
                returnWithMsg("error", "times-circle", 4500, $upload_res['error'], "view-ticket.php?id=" . $ticket_id);
            }
            $attachment = $upload_res;
        }

        $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, attachment, created_at) VALUES (?, 'Admin', ?, ?, ?)");
        $stmt_msg->bind_param("issss", $ticket_id, $message, $attachment, $currtime);
        $stmt_msg->execute();
        $stmt_msg->close();

        // 5. Update the actual ticket status and timestamp
        $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = ?, updated_at = ? WHERE id = ?");
        $stmt_upd->bind_param("ssi", $status_update, $currtime, $ticket_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // 6. Send the Anti-Spam Email Notification
        $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_msg_reply', 'ticket_msg_closed_admin')");
        $settings = [];
        if ($res_stg) { while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; } }

        $site_name = $settings['site_name'] ?? 'Support';
        $safe_noreply = "noreply@" . $_SERVER['HTTP_HOST'];
        
        // FIX: Safely Base64 encode the site name to support Norwegian characters (ø, æ, å)
        $encoded_site_name = '=?UTF-8?B?' . base64_encode($site_name) . '?=';
        
        $headers = "From: " . $encoded_site_name . " <" . $safe_noreply . ">\r\n" .
                   "Reply-To: " . $safe_noreply . "\r\n" .
                   "MIME-Version: 1.0\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();
        
        $body_text = "Hello " . trim($t_data['client_name']) . ",\n\n";
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
            // FIX: Generate the exact direct link to the ticket!
            $portal_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/view-ticket.php?id=" . urlencode($t_data['tracking_id']) . "&email=" . urlencode($t_data['client_email']);
            
            $body_text .= "Tracking ID: " . $t_data['tracking_id'] . "\n\n";
            $body_text .= "--- Please do not reply directly to this email ---\n";
            $body_text .= "You can view the full thread and respond securely via this direct link: \n" . $portal_url;
            
            $mail_sent = mail(trim($t_data['client_email']), "Ticket Update: " . $t_data['tracking_id'], $body_text, $headers, "-f" . $safe_noreply);
            if (!$mail_sent) { error_log("TICKET MAIL FAILED: PHP mail() returned false. To: " . $t_data['client_email']); }
        }
    }

    returnWithMsg("success", "check-circle", 4500, "Ticket updated successfully.", "view-ticket.php?id=" . $ticket_id);
}
?>