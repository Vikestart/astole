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

// --- 2. TICKET SYSTEM SHORTCODES ---

// A. Submit Ticket Form [TICKET_SUBMIT]
$rc_site = $global_settings['recaptcha_site'] ?? '';
$rc_html = '';
if (!empty($rc_site)) {
    $rc_html = '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($rc_site) . '" style="margin-bottom: 15px;"></div><script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

$ticket_submit_html = '
<form action="process-ticket.php" method="POST" style="margin-top: 20px; background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0;">
    <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 20px;">Open a Support Ticket</h3>
    <input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">
    <input type="hidden" name="action" value="new_ticket">
    <input type="hidden" name="return_url" value="' . htmlspecialchars($route) . '">

    <div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="favorite_color" tabindex="-1" value="" autocomplete="off"></div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Your Name <span style="color: #dc2626;">*</span></label>
            <input type="text" name="client_name" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" required>
        </div>
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Email Address <span style="color: #dc2626;">*</span></label>
            <input type="email" name="client_email" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" required>
        </div>
    </div>
    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Subject <span style="color: #dc2626;">*</span></label>
        <input type="text" name="subject" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" required>
    </div>
    <div style="margin-bottom: 15px;">
        <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Message <span style="color: #dc2626;">*</span></label>
        <textarea name="message" style="width: 100%; height: 150px; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; resize: vertical;" required></textarea>
    </div>
    
    ' . $rc_html . '

    <button type="submit" style="background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">Submit Ticket</button>
</form>
';

// B. Track Ticket Form [TICKET_TRACK]
$ticket_track_html = '
<form action="view-ticket.php" method="GET" style="margin-top: 20px; background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
    <h3 style="margin-top: 0; color: #0f172a; margin-bottom: 20px;">Check Ticket Status</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Tracking ID</label>
            <input type="text" name="id" placeholder="e.g. TKT-ABC123" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" required>
        </div>
        <div>
            <label style="display: block; font-weight: 600; margin-bottom: 5px; color: #334155; font-size: 14px;">Email Address</label>
            <input type="email" name="email" placeholder="Used when opening ticket" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;" required>
        </div>
    </div>
    <button type="submit" style="background: #475569; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: opacity 0.2s;">View Thread</button>
</form>
';

// Inject the forms
$page_contents = str_replace('[TICKET_SUBMIT]', $ticket_submit_html, $page_contents);
$page_contents = str_replace('[TICKET_TRACK]', $ticket_track_html, $page_contents);

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
    <div class="hero-section">
        <div class="hero-badge">
            <i class="fa-solid fa-chart-line"></i> Technical Consultant & Developer
        </div>
        <h1 class="hero-title">Bridging Business Strategy<br>with <span>Modern Technology</span>.</h1>
        <p class="hero-subtitle">Specializing in ERP solutions, business controlling, and scalable web experiences.</p>
    </div>

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