<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once "PageManager.php";

$route = isset($_GET['route']) && !empty($_GET['route']) ? rtrim($_GET['route'], '/') : 'home';
$pageData = PageManager::getPageBySlug($route);

if (!$pageData) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Page Not Found";
    $page_contents = "<p style='text-align: center; color: var(--text-muted);'>The content you are looking for does not exist or has been moved.</p>";
} else {
    $page_title = $pageData['page_title'];
    $page_contents = $pageData['page_contents'];
    if (!empty($pageData['page_desc'])) {
        $page_desc = $pageData['page_desc'];
    }
}

// --- TICKET SYSTEM BACKEND LOGIC ---
$db = new DBConn();
$res_tkt = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('recaptcha_site', 'ticket_system_enabled', 'ticket_creation_enabled', 'ticket_autoclose_hours', 'attachment_retention_days')");
$tkt_settings = ['ticket_system_enabled' => 1, 'ticket_creation_enabled' => 1, 'ticket_autoclose_hours' => 72, 'attachment_retention_days' => 365];
$rc_site = '';

if ($res_tkt) {
    while($r = $res_tkt->fetch_assoc()) {
        $tkt_settings[$r['setting_key']] = $r['setting_value'];
        if ($r['setting_key'] === 'recaptcha_site') { $rc_site = trim($r['setting_value']); }
    }
}

// AUTO-CLOSE CRON FUNCTION
$auto_close_hours = (int)$tkt_settings['ticket_autoclose_hours'];
if ($auto_close_hours > 0) {
    $q_close = $db->conn->query("SELECT id, client_email, client_name, tracking_id FROM tickets WHERE status = 'Answered' AND updated_at < DATE_SUB(NOW(), INTERVAL $auto_close_hours HOUR)");
    if ($q_close && $q_close->num_rows > 0) {
        $res_mail = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_email', 'site_name', 'ticket_msg_closed_auto')");
        $m_stg = [];
        while($r = $res_mail->fetch_assoc()) { $m_stg[$r['setting_key']] = $r['setting_value']; }
        
        $site_name = $m_stg['site_name'] ?? 'Support';
        $safe_noreply = "noreply@" . $_SERVER['HTTP_HOST'];
        $encoded_site_name = '=?UTF-8?B?' . base64_encode($site_name) . '?=';
        
        $headers = "From: " . $encoded_site_name . " <" . $safe_noreply . ">\r\n" .
                   "Reply-To: " . $safe_noreply . "\r\n" .
                   "MIME-Version: 1.0\r\n" .
                   "Content-Type: text/plain; charset=UTF-8\r\n" .
                   "X-Mailer: PHP/" . phpversion();
                   
        $msg_auto = $m_stg['ticket_msg_closed_auto'] ?? 'Your ticket was auto-closed due to inactivity.';
        $ids_to_close = [];
        
        while ($t = $q_close->fetch_assoc()) {
            $ids_to_close[] = $t['id'];
            $portal_url = "http" . (isset($_SERVER['HTTPS']) ? "s" : "") . "://" . $_SERVER['HTTP_HOST'] . "/view-ticket.php?id=" . urlencode($t['tracking_id']) . "&email=" . urlencode($t['client_email']);
            $body = "Hello " . trim($t['client_name']) . ",\n\n" . $msg_auto . "\n\nTracking ID: " . $t['tracking_id'] . "\n\nYou can view the ticket thread here: \n" . $portal_url;
            mail($t['client_email'], "Ticket Auto-Closed: " . $t['tracking_id'], $body, $headers, "-f" . $safe_noreply);
            
            $sys_msg = "Ticket auto-closed by system due to " . $auto_close_hours . " hours of inactivity.";
            $db->conn->query("INSERT INTO ticket_replies (ticket_id, sender_type, message, created_at) VALUES ({$t['id']}, 'System', '{$sys_msg}', NOW())");
        }
        $id_list = implode(',', $ids_to_close);
        $db->conn->query("UPDATE tickets SET status = 'Closed' WHERE id IN ($id_list)");
    }
}

// AUTO-DELETE ATTACHMENTS CRON
$retention_days = (int)($tkt_settings['attachment_retention_days'] ?? 365);
if ($retention_days > 0) {
    $q_att = $db->conn->query("SELECT id, attachment FROM ticket_replies WHERE attachment IS NOT NULL AND created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
    if ($q_att && $q_att->num_rows > 0) {
        while($r = $q_att->fetch_assoc()) {
            $files = json_decode($r['attachment'], true);
            if (is_array($files)) { foreach($files as $f) { @unlink(__DIR__ . '/uploads/tickets/' . $f); } } 
            else { @unlink(__DIR__ . '/uploads/tickets/' . $r['attachment']); }
            $db->conn->query("UPDATE ticket_replies SET attachment = NULL WHERE id = " . $r['id']);
        }
    }
}

// --- SESSION MESSAGES ---
if (isset($_SESSION['Frontmsg'])) {
    $msgType = $_SESSION['Frontmsg']['type']; 
    $msgTxt = $_SESSION['Frontmsg']['message'];
    $msgClass = ($msgType === 'success') ? 'front-msgbox-success' : 'front-msgbox-error';
    $frontend_msg = "<div class='front-msgbox $msgClass'>$msgTxt</div>";
    unset($_SESSION['Frontmsg']);
}

// --- TEMPLATE / PAGE TYPE ROUTER ---
if (isset($pageData['page_type']) && $pageData['page_type'] === 'Ticket portal') {
    ob_start();
    require_once "inc-ticket-form.php";
    $page_contents = ob_get_clean(); // Instantly replaces all content with the portal
} 
// Legacy fallback (just in case)
else if (strpos($page_contents, "[TICKET_PORTAL]") !== false) {
    ob_start();
    require_once "inc-ticket-form.php";
    $page_contents = str_replace("[TICKET_PORTAL]", ob_get_clean(), $page_contents);
}

require_once "inc-head.php";
?>

<main class="page-container">
    <?php if ($route === 'home') { ?>
        <div class="hero-section">
            <div class="hero-badge">
                <i class="fa-solid fa-chart-line"></i> Technical Consultant & Developer
            </div>
            <h1 class="hero-title">Bridging Business Strategy<br>with <span>Modern Technology</span>.</h1>
            <p class="hero-subtitle">Specializing in ERP solutions, business controlling, and scalable web experiences.</p>
        </div>
    <?php } else { ?>
        <div class="hero-section" style="min-height: auto;">
            <div class="hero-badge" style="margin-bottom: 0;">
                <i class="fa-solid fa-chart-line"></i> Technical Consultant & Developer
            </div>
        </div>
    <?php } ?>

    <section class="glass-panel">
        <div class="panel-header">
            <h2 class="panel-title"><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        
        <div class="page-content" style="line-height: 1.8;">
            <?php 
                if (isset($frontend_msg)) { echo $frontend_msg; }
                echo $page_contents; 
            ?>
        </div>
    </section>
</main>

<?php require_once "inc-end.php"; ?>