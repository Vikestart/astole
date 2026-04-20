<?php
session_start();
require_once "upload-helper.php";
require_once "db.php";

$return_url = $_POST['return_url'] ?? 'home';

function returnWithMsg($type, $message) {
    global $return_url;
    $_SESSION['Frontmsg'] = array('type' => $type, 'message' => $message);
    header("Location: " . $return_url);
    die();
}

$action = $_POST['action'] ?? '';
$currtime = gmdate("Y-m-d H:i:s");
$db = new DBConn();

// --- RECAPTCHA v3 VERIFICATION ---
$res_sec = $db->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'recaptcha_secret'");
$rc_secret = ($res_sec && $res_sec->num_rows === 1) ? trim($res_sec->fetch_assoc()['setting_value']) : '';

if (!empty($rc_secret) && in_array($action, ['new_ticket', 'reply_ticket'])) {
    $rc_response = $_POST['g-recaptcha-response'] ?? '';
    if (empty($rc_response)) { returnWithMsg("error", "Anti-spam validation missing. Please try again."); }
    
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$rc_secret}&response={$rc_response}";
    $verify_data = json_decode(file_get_contents($verify_url));
    
    if (!$verify_data->success || (isset($verify_data->score) && $verify_data->score < 0.5)) { 
        returnWithMsg("error", "Anti-spam verification failed. Our system thinks you might be a bot."); 
    }
}

// --- ACTION: NEW TICKET ---
if ($action === 'new_ticket') {
    $name = trim(strip_tags($_POST['client_name'] ?? ''));
    $email = filter_var($_POST['client_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));

    if (empty($name) || empty($email) || empty($subject) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        returnWithMsg("error", "Please fill out all fields with a valid email address.");
    }

    $tracking_id = 'TKT-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

    $stmt = $db->conn->prepare("INSERT INTO tickets (tracking_id, client_name, client_email, subject, status, created_at, updated_at) VALUES (?, ?, ?, ?, 'Open', ?, ?)");
    $stmt->bind_param("ssssss", $tracking_id, $name, $email, $subject, $currtime, $currtime);
    $stmt->execute();
    $ticket_id = $db->conn->insert_id;
    $stmt->close();

    // Process Attachment
    $attachment = null;
    if (!empty($_FILES['attachment']['name'])) {
        $upload_res = processMultipleAttachments($_FILES['attachment'], __DIR__ . '/../uploads/tickets');
        if (is_array($upload_res) && isset($upload_res['error'])) { returnWithMsg("error", $upload_res['error']); }
        $attachment = $upload_res;
    }

    $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, attachment, created_at) VALUES (?, 'Client', ?, ?, ?)");
    $stmt_msg->bind_param("isss", $ticket_id, $message, $attachment, $currtime);
    $stmt_msg->execute();
    $stmt_msg->close();

    // --- EMAIL NOTIFICATIONS ---
    $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_notify_admin_new', 'ticket_msg_received')");
    $settings = [];
    while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; }
    
    $site_name = $settings['site_name'] ?? 'Support';
    $safe_noreply = "noreply@" . $_SERVER['HTTP_HOST'];
    $encoded_site_name = '=?UTF-8?B?' . base64_encode($site_name) . '?=';

    $headers = "From: " . $encoded_site_name . " <" . $safe_noreply . ">\r\n" .
               "Reply-To: " . ($settings['site_email'] ?? $safe_noreply) . "\r\n" .
               "MIME-Version: 1.0\r\n" .
               "Content-Type: text/plain; charset=UTF-8\r\n" .
               "X-Mailer: PHP/" . phpversion();

    $portal_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/view-ticket.php?id=" . urlencode($tracking_id) . "&email=" . urlencode($email);

    if (!empty($settings['ticket_msg_received'])) {
        $body = "Hello " . $name . ",\n\n" . $settings['ticket_msg_received'] . "\n\nTracking ID: " . $tracking_id . "\nSubject: " . $subject . "\n\nYou can view your ticket and reply via this direct link: \n" . $portal_url;
        mail($email, "Ticket Received: " . $tracking_id, $body, $headers, "-f" . $safe_noreply);
    }
    if ($settings['ticket_notify_admin_new'] == '1' && !empty($settings['site_email'])) {
        $admin_body = "A new support ticket has been opened.\n\nTracking ID: $tracking_id\nClient: $name ($email)\nSubject: $subject\n\nMessage:\n$message";
        mail($settings['site_email'], "New Ticket: " . $tracking_id, $admin_body, $headers, "-f" . $safe_noreply);
    }

    returnWithMsg("success", "Your ticket has been opened! Your Tracking ID is: <strong>" . $tracking_id . "</strong><br><br>An email has been sent to you with a direct link to view this thread.");
}

// --- ACTION: REPLY TO TICKET ---
if ($action === 'reply_ticket') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $auth_email = filter_var($_POST['auth_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));
    $tracking_id = $_POST['tracking_id'] ?? 'Unknown';
    
    if ($return_url == 'home') { $return_url = "view-ticket.php?id=" . urlencode($tracking_id) . "&email=" . urlencode($auth_email); }

    if (empty($message) || empty($ticket_id)) { returnWithMsg("error", "Message cannot be empty."); }

    $stmt_chk = $db->conn->prepare("SELECT id, status FROM tickets WHERE id = ? AND client_email = ?");
    $stmt_chk->bind_param("is", $ticket_id, $auth_email);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result();
    if ($res_chk->num_rows === 0) { returnWithMsg("error", "Authorization failed. Invalid ticket or email."); }
    $t_data = $res_chk->fetch_assoc();
    $stmt_chk->close();

    // --- SYSTEM LOG: Wake up ticket ---
    if ($t_data['status'] !== 'Open') {
        $sys_msg = "Status changed from [b]{$t_data['status']}[/b] to [b]Open[/b] by the Client.";
        $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
        $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
        $stmt_sys->execute();
        $stmt_sys->close();
    }

    // Process Attachment
    $attachment = null;
    if (!empty($_FILES['attachment']['name'])) {
        $upload_res = processMultipleAttachments($_FILES['attachment'], __DIR__ . '/../uploads/tickets');
        if (is_array($upload_res) && isset($upload_res['error'])) { returnWithMsg("error", $upload_res['error']); }
        $attachment = $upload_res;
    }

    $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, attachment, created_at) VALUES (?, 'Client', ?, ?, ?)");
    $stmt_msg->bind_param("isss", $ticket_id, $message, $attachment, $currtime);
    $stmt_msg->execute();
    $stmt_msg->close();

    $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = 'Open', updated_at = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $currtime, $ticket_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    // --- EMAIL NOTIFICATION ---
    $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_notify_admin_reply')");
    $settings = [];
    while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; }

    if ($settings['ticket_notify_admin_reply'] == '1' && !empty($settings['site_email'])) {
        $site_name = $settings['site_name'] ?? 'Support';
        $safe_noreply = "noreply@" . $_SERVER['HTTP_HOST'];
        $encoded_site_name = '=?UTF-8?B?' . base64_encode($site_name) . '?=';

        $headers = "From: " . $encoded_site_name . " <" . $safe_noreply . ">\r\n" .
                   "Reply-To: " . $auth_email . "\r\n" .
                   "MIME-Version: 1.0\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();
                   
        $admin_body = "A client has replied to their ticket.\n\nTracking ID: " . $tracking_id . "\nClient Email: $auth_email\n\nMessage:\n$message";
        mail($settings['site_email'], "Ticket Reply: " . $tracking_id, $admin_body, $headers, "-f" . $safe_noreply);
    }

    returnWithMsg("success", "Your reply has been added to the ticket.");
}

// --- ACTION: CLIENT CLOSE TICKET ---
if ($action === 'client_close') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $auth_email = filter_var($_POST['auth_email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($ticket_id)) { returnWithMsg("error", "Invalid request."); }

    $stmt_chk = $db->conn->prepare("SELECT id FROM tickets WHERE id = ? AND client_email = ? AND status != 'Closed'");
    $stmt_chk->bind_param("is", $ticket_id, $auth_email);
    $stmt_chk->execute();
    if ($stmt_chk->get_result()->num_rows === 0) { returnWithMsg("error", "Authorization failed, or ticket is already closed."); }
    $stmt_chk->close();

    $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = 'Closed', updated_at = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $currtime, $ticket_id);
    $stmt_upd->execute();
    $stmt_upd->close();
    
    // Add System History Log
    $sys_msg = "Ticket was marked as [b]Resolved and Closed[/b] by the Client.";
    $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
    $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
    $stmt_sys->execute();
    $stmt_sys->close();

    returnWithMsg("success", "Ticket has been successfully closed. Thank you!");
}
?>