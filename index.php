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

// --- 2. TICKET SYSTEM PORTAL [TICKET_PORTAL] ---

// Fetch the Ticket Settings & reCAPTCHA
$db = new DBConn();
$res_tkt = $db->conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('recaptcha_site', 'ticket_system_enabled', 'ticket_creation_enabled', 'ticket_autoclose_hours')");
$tkt_settings = ['ticket_system_enabled' => 1, 'ticket_creation_enabled' => 1, 'ticket_autoclose_hours' => 72, 'recaptcha_site' => '', 'attachment_retention_days' => 365];
if ($res_tkt) {
    while($r = $res_tkt->fetch_assoc()) { $tkt_settings[$r['setting_key']] = $r['setting_value']; }
}

// AUTO-DELETE ATTACHMENTS CRON
$retention_days = (int)($tkt_settings['attachment_retention_days'] ?? 365);
if ($retention_days > 0) {
    // Find replies with attachments older than X days
    $q_att = $db->conn->query("SELECT id, attachment FROM ticket_replies WHERE attachment IS NOT NULL AND created_at < DATE_SUB(NOW(), INTERVAL $retention_days DAY)");
    if ($q_att && $q_att->num_rows > 0) {
        while($r = $q_att->fetch_assoc()) {
            $files = json_decode($r['attachment'], true);
            if (is_array($files)) {
                foreach($files as $f) { @unlink(__DIR__ . '/uploads/tickets/' . $f); }
            } else {
                @unlink(__DIR__ . '/uploads/tickets/' . $r['attachment']);
            }
            // Clear the database record
            $db->conn->query("UPDATE ticket_replies SET attachment = NULL WHERE id = " . $r['id']);
        }
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

// RENDER LOGIC
if ((int)$tkt_settings['ticket_system_enabled'] === 0) {
    // SYSTEM IS DISABLED
    $ticket_portal_html = '<div style="margin-top: 30px; background: #fff; padding: 40px 20px; text-align: center; border-radius: 12px; border: 1px solid #e2e8f0;"><i class="fa-solid fa-wrench" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px;"></i><h3 style="margin: 0 0 10px 0; color: #0f172a;">Support Portal Offline</h3><p style="color: #64748b; margin: 0;">Our ticketing system is currently down for maintenance. Please check back later.</p></div>';
} else {
    // SYSTEM IS ACTIVE
    $rc_site = $tkt_settings['recaptcha_site'];
    $rc_html = '';
    if (!empty($rc_site)) {
        $rc_html = '<script src="https://www.google.com/recaptcha/api.js" async defer></script><div class="g-recaptcha" data-sitekey="' . htmlspecialchars($rc_site) . '" data-action="submit_ticket" style="margin-bottom: 15px;"></div>';
    }

    $allow_new = ((int)$tkt_settings['ticket_creation_enabled'] === 1);

    // Build the Open Ticket Card dynamically based on status
    $open_ticket_card = '';
    if ($allow_new) {
        $open_ticket_card = '
        <div onclick="switchTicketView(\'submit\')" style="background: #fff; border: 2px solid #e2e8f0; padding: 30px 20px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#2563eb\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.borderColor=\'#e2e8f0\'; this.style.transform=\'translateY(0)\';">
            <i class="fa-solid fa-envelope-open-text" style="font-size: 36px; color: #2563eb; margin-bottom: 15px;"></i>
            <h4 style="margin: 0 0 10px 0; color: #0f172a; font-size: 18px;">Open a New Ticket</h4>
            <p style="margin: 0; color: #64748b; font-size: 14px;">Submit a new request or inquiry to our technical support team.</p>
        </div>';
    } else {
        $open_ticket_card = '
        <div style="background: #f8fafc; border: 2px dashed #cbd5e1; padding: 30px 20px; text-align: center; border-radius: 8px; opacity: 0.6; cursor: not-allowed;">
            <i class="fa-solid fa-envelope-open-text" style="font-size: 36px; color: #94a3b8; margin-bottom: 15px;"></i>
            <h4 style="margin: 0 0 10px 0; color: #64748b; font-size: 18px;">Open a New Ticket</h4>
            <p style="margin: 0; color: #94a3b8; font-size: 14px;">The creation of new tickets is currently disabled. Please check back later.</p>
        </div>';
    }

    $ticket_portal_html = '
    <div id="ticket-portal-wrapper" style="margin-top: 30px; background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        
        <div id="tp-selection">
            <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 25px; text-align: center; font-size: 22px;">What can I help you with?</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                
                ' . $open_ticket_card . '

                <div onclick="switchTicketView(\'track\')" style="background: #fff; border: 2px solid #e2e8f0; padding: 30px 20px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.2s;" onmouseover="this.style.borderColor=\'#475569\'; this.style.transform=\'translateY(-2px)\';" onmouseout="this.style.borderColor=\'#e2e8f0\'; this.style.transform=\'translateY(0)\';">
                    <i class="fa-solid fa-magnifying-glass" style="font-size: 36px; color: #475569; margin-bottom: 15px;"></i>
                    <h4 style="margin: 0 0 10px 0; color: #0f172a; font-size: 18px;">Check Ticket Status</h4>
                    <p style="margin: 0; color: #64748b; font-size: 14px;">View ongoing replies and status updates for an existing ticket.</p>
                </div>
            </div>
        </div>

        ' . ($allow_new ? '
        <div id="tp-submit" style="display: none;">
            <button onclick="switchTicketView(\'selection\')" type="button" style="background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 20px; font-size: 15px; display: flex; align-items: center;"><i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Back to Options</button>
            <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">Open a Support Ticket</h3>
            <form action="process-ticket.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">
                <input type="hidden" name="action" value="new_ticket">
                <input type="hidden" name="return_url" value="' . htmlspecialchars($route) . '">
                <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="favorite_color" tabindex="-1" value="" autocomplete="off"></div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Your Name <span style="color: #dc2626;">*</span></label><input type="text" name="client_name" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; background: #fff;" required></div>
                    <div><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Email Address <span style="color: #dc2626;">*</span></label><input type="email" name="client_email" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; background: #fff;" required></div>
                </div>
                <div style="margin-bottom: 15px;"><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Subject <span style="color: #dc2626;">*</span></label><input type="text" name="subject" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; background: #fff;" required></div>
                <div style="margin-bottom: 15px;"><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Message <span style="color: #dc2626;">*</span></label><textarea name="message" style="width: 100%; height: 150px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; resize: vertical; background: #fff;" required></textarea></div>
                <div style="margin-bottom: 20px;"><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;"><i class="fa-solid fa-paperclip"></i> Attach File (Optional)</label><input type="file" name="attachment[]" multiple accept=".jpg,.jpeg,.png,.webp,.pdf,.txt" class="multi-file-input" style="width: 100%; padding: 8px; border: 1px dashed var(--border); border-radius: 6px; font-size: 13px;"><div class="file-list-preview" style="margin-top: 10px; display: flex; flex-direction: column; gap: 5px;"></div></div>
                ' . $rc_html . '
                <button type="submit" style="background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">Submit Ticket</button>
            </form>
        </div>' : '') . '

        <div id="tp-track" style="display: none;">
            <button onclick="switchTicketView(\'selection\')" type="button" style="background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; padding: 0; margin-bottom: 20px; font-size: 15px; display: flex; align-items: center;"><i class="fa-solid fa-arrow-left" style="margin-right: 8px;"></i> Back to Options</button>
            <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;">Check Ticket Status</h3>
            <form action="view-ticket.php" method="GET">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Tracking ID <span style="color: #dc2626;">*</span></label><input type="text" name="id" placeholder="e.g. TKT-ABC123" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; background: #fff;" required></div>
                    <div><label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Email Address <span style="color: #dc2626;">*</span></label><input type="email" name="email" placeholder="Used when opening ticket" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; background: #fff;" required></div>
                </div>
                <button type="submit" style="background: #475569; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">View Thread</button>
            </form>
        </div>

    </div>

    <script>
    function switchTicketView(view) {
        document.getElementById("tp-selection").style.display = (view === "selection") ? "block" : "none";
        ' . ($allow_new ? 'document.getElementById("tp-submit").style.display = (view === "submit") ? "block" : "none";' : '') . '
        document.getElementById("tp-track").style.display = (view === "track") ? "block" : "none";
    }
    </script>
    ';
}

$page_contents = str_replace('[TICKET_PORTAL]', $ticket_portal_html, $page_contents);

// --- 3. FRONTEND NOTIFICATIONS ---
$frontend_msg = '';
if (isset($_SESSION['Frontmsg'])) {
    $msgType = $_SESSION['Frontmsg']['type']; 
    $msgTxt = $_SESSION['Frontmsg']['message'];
    $bgColor = ($msgType === 'success') ? '#dcfce7' : '#fee2e2';
    $textColor = ($msgType === 'success') ? '#166534' : '#991b1b';
    
    $frontend_msg = "<div style='background: $bgColor; color: $textColor; padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; border: 1px solid " . (($msgType === 'success') ? '#bbf7d0' : '#fecaca') . ";'>$msgTxt</div>";
    unset($_SESSION['Frontmsg']);
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
        <div class="panel-body">
            <?php echo $frontend_msg; ?>
            <?php echo $page_contents; ?>
        </div>
    </section>
</main>

<?php require_once "inc-end.php"; ?>