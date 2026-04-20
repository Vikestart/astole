<?php
session_start();
require "db.php";

$return_url = $_POST['return_url'] ?? 'home';

function returnWithMsg($type, $message) {
    global $return_url;
    $_SESSION['Frontmsg'] = array('type' => $type, 'message' => $message);
    header("Location: " . $return_url);
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    returnWithMsg("error", "Security validation failed. Please refresh the page and try again.");
}

if (!empty($_POST['favorite_color'])) {
    returnWithMsg("success", "Operation successful!"); // Honeypot trap
}

$db = new DBConn();

// --- RECAPTCHA v3 VERIFICATION ---
$res_sec = $db->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'recaptcha_secret'");
$rc_secret = ($res_sec && $res_sec->num_rows === 1) ? trim($res_sec->fetch_assoc()['setting_value']) : '';

$action = $_POST['action'] ?? ''; // Grab action early!
$currtime = gmdate("Y-m-d H:i:s");

// Only enforce reCAPTCHA for new tickets and replies!
if (!empty($rc_secret) && in_array($action, ['new_ticket', 'reply_ticket'])) {
    $rc_response = $_POST['g-recaptcha-response'] ?? '';
    if (empty($rc_response)) { returnWithMsg("error", "Anti-spam validation missing. Please try again."); }
    
    $verify_url = "https://www.google.com/recaptcha/api/siteverify?secret={$rc_secret}&response={$rc_response}";
    $verify_data = json_decode(file_get_contents($verify_url));
    
    if (!$verify_data->success || (isset($verify_data->score) && $verify_data->score < 0.5)) { 
        returnWithMsg("error", "Anti-spam verification failed. Our system thinks you might be a bot."); 
    }
}

$action = $_POST['action'] ?? '';
$currtime = gmdate("Y-m-d H:i:s");

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

    $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'Client', ?, ?)");
    $stmt_msg->bind_param("iss", $ticket_id, $message, $currtime);
    $stmt_msg->execute();
    $stmt_msg->close();

    // --- EMAIL NOTIFICATIONS (NEW TICKET) ---
    $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_notify_admin_new', 'ticket_msg_received')");
    $settings = [];
    while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; }
    
    $site_name = $settings['site_name'] ?? 'Support';
    $safe_noreply = "noreply@" . $_SERVER['SERVER_NAME'];
    
    // Force both 'From' and 'Reply-To' to be the system's noreply address
    $headers = "From: " . $site_name . " <" . $safe_noreply . ">\r\n" .
                "Reply-To: " . $safe_noreply . "\r\n" .
                "MIME-Version: 1.0\r\n" .
                "Content-Type: text/plain; charset=UTF-8\r\n" .
                "X-Mailer: PHP/" . phpversion();

    // 1. Email Client
    if (!empty($settings['ticket_msg_received'])) {
        $body = "Hello " . $name . ",\n\n" . $settings['ticket_msg_received'] . "\n\nTracking ID: " . $tracking_id . "\nSubject: " . $subject;
        mail($email, "Ticket Received: " . $tracking_id, $body, $headers, "-f noreply@" . $_SERVER['SERVER_NAME']);
    }
    // 2. Email Admin
    if ($settings['ticket_notify_admin_new'] == '1' && !empty($settings['site_email'])) {
        $admin_body = "A new support ticket has been opened.\n\nTracking ID: $tracking_id\nClient: $name ($email)\nSubject: $subject\n\nMessage:\n$message";
        mail($settings['site_email'], "New Ticket: " . $tracking_id, $admin_body, $headers, "-f noreply@" . $_SERVER['SERVER_NAME']);
    }

    returnWithMsg("success", "Your ticket has been opened! Your Tracking ID is: <strong>" . $tracking_id . "</strong><br><br>Please save this ID and use the tracking form to view replies.");
}

// --- ACTION: REPLY TO TICKET ---
if ($action === 'reply_ticket') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $auth_email = filter_var($_POST['auth_email'] ?? '', FILTER_SANITIZE_EMAIL);
    $message = trim(htmlspecialchars($_POST['message'] ?? ''));
    
    // Fallback if return URL isn't set properly
    if ($return_url == 'home') { $return_url = "view-ticket.php?id=" . urlencode($_POST['tracking_id'] ?? '') . "&email=" . urlencode($auth_email); }

    if (empty($message) || empty($ticket_id)) { returnWithMsg("error", "Message cannot be empty."); }

    // Security Check: Ensure the email matches the ticket ID before allowing a reply!
    $stmt_chk = $db->conn->prepare("SELECT id FROM tickets WHERE id = ? AND client_email = ?");
    $stmt_chk->bind_param("is", $ticket_id, $auth_email);
    $stmt_chk->execute();
    if ($stmt_chk->get_result()->num_rows === 0) { returnWithMsg("error", "Authorization failed. Invalid ticket or email."); }
    $stmt_chk->close();

    $stmt_msg = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'Client', ?, ?)");
    $stmt_msg->bind_param("iss", $ticket_id, $message, $currtime);
    $stmt_msg->execute();
    $stmt_msg->close();

    // Reopen the ticket so the Admin knows there is a new message
    $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = 'Open', updated_at = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $currtime, $ticket_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    // --- EMAIL NOTIFICATION (CLIENT REPLY) ---
    $res_stg = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_notify_admin_reply')");
    $settings = [];
    while($r = $res_stg->fetch_assoc()) { $settings[$r['setting_key']] = $r['setting_value']; }

    if ($settings['ticket_notify_admin_reply'] == '1' && !empty($settings['site_email'])) {
        $headers = "From: " . $settings['site_name'] . " <noreply@" . $_SERVER['SERVER_NAME'] . ">\r\nReply-To: " . $auth_email . "\r\nX-Mailer: PHP/" . phpversion();
        $admin_body = "A client has replied to their ticket.\n\nTracking ID: " . ($_POST['tracking_id'] ?? 'Unknown') . "\nClient Email: $auth_email\n\nMessage:\n$message";
        mail($settings['site_email'], "Ticket Reply: " . ($_POST['tracking_id'] ?? 'Unknown'), $admin_body, $headers, "-f noreply@" . $_SERVER['SERVER_NAME']);
    }

    returnWithMsg("success", "Your reply has been added to the ticket.");
}

// Add System History Log
    $sys_msg = "Ticket was marked as Resolved and Closed by the Client.";
    $stmt_sys = $db->conn->prepare("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES (?, 'System', ?, ?)");
    $stmt_sys->bind_param("iss", $ticket_id, $sys_msg, $currtime);
    $stmt_sys->execute();
    $stmt_sys->close();

// --- ACTION: CLIENT CLOSE TICKET ---
if ($action === 'client_close') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $auth_email = filter_var($_POST['auth_email'] ?? '', FILTER_SANITIZE_EMAIL);

    if (empty($ticket_id)) { returnWithMsg("error", "Invalid request."); }

    // Security Check: Ensure the email matches the ticket ID!
    $stmt_chk = $db->conn->prepare("SELECT id FROM tickets WHERE id = ? AND client_email = ? AND status != 'Closed'");
    $stmt_chk->bind_param("is", $ticket_id, $auth_email);
    $stmt_chk->execute();
    if ($stmt_chk->get_result()->num_rows === 0) { 
        returnWithMsg("error", "Authorization failed, or ticket is already closed."); 
    }
    $stmt_chk->close();

    $stmt_upd = $db->conn->prepare("UPDATE tickets SET status = 'Closed', updated_at = ? WHERE id = ?");
    $stmt_upd->bind_param("si", $currtime, $ticket_id);
    $stmt_upd->execute();
    $stmt_upd->close();

    returnWithMsg("success", "Ticket has been successfully closed. Thank you!");
}
?>