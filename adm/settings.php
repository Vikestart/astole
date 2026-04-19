<?php
	$site_title = "Global Settings";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

    // --- SECURITY VERIFICATION ---
	if ($userdata->row['user_role'] != 1) {
		echo '<section>
				<div style="text-align: center; padding: 60px 20px;">
					<i class="fa-solid fa-shield-lock" style="font-size: 48px; color: var(--danger); margin-bottom: 20px;"></i>
					<h2 style="font-size: 24px; color: var(--text-main); margin-bottom: 10px;">Access Restricted</h2>
					<p style="color: var(--text-muted); font-size: 16px;">You must be an Administrator to access global system configuration.</p>
					<a href="index.php" class="btn btn-primary" style="margin-top: 20px;"><i class="fa-solid fa-arrow-left"></i> Return to Dashboard</a>
				</div>
			  </section>';
		require "inc-adm-foot.php";
		die(); // Stop drawing the rest of the page!
	}

	// Handle Session Messages
	if (isset($_SESSION['Sessionmsg'])) {
		$msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
		unset($_SESSION['Sessionmsg']);
	}

	// Fetch all settings into a convenient associative array
	$db = new DBConn();
	$res = $db->conn->query("SELECT setting_key, setting_value FROM settings");
	$config = [];
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			$config[$row['setting_key']] = $row['setting_value'];
		}
	}

	// Assign variables with fallbacks just in case a row is missing
	$s_name = $config['site_name'] ?? '';
	$s_email = $config['site_email'] ?? '';
	$s_maint = (int)($config['maintenance_mode'] ?? 0);
	$s_desc = $config['seo_description'] ?? '';
	$s_ga = $config['ga_id'] ?? '';
	$s_rc_site = $config['recaptcha_site'] ?? '';
	$s_rc_sec = $config['recaptcha_secret'] ?? '';
    $s_tkt_sys = (int)($config['ticket_system_enabled'] ?? 1);
	$s_tkt_new = (int)($config['ticket_creation_enabled'] ?? 1);
	$s_tkt_close = (int)($config['ticket_autoclose_hours'] ?? 72);
    $s_tkt_notif_new = (int)($config['ticket_notify_admin_new'] ?? 1);
	$s_tkt_notif_rep = (int)($config['ticket_notify_admin_reply'] ?? 1);
	$s_tkt_msg_rec = $config['ticket_msg_received'] ?? '';
	$s_tkt_msg_rep = $config['ticket_msg_reply'] ?? '';
	$s_tkt_msg_ca = $config['ticket_msg_closed_admin'] ?? '';
	$s_tkt_msg_cu = $config['ticket_msg_closed_auto'] ?? '';
?>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-cog"></i> Global Configuration</h1>
    </div>

	<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>"; } ?>

    <form action="process-settings.php" method="POST" autocomplete="off" class="track-changes">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            
            <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
                <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fa-solid fa-globe" style="margin-right: 10px; color: var(--text-muted);"></i> General Information
                </h2>
                
                <div class="form-group">
                    <label>Website Name <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="The global brand name displayed in the browser tab and search results."><i class="fa-solid fa-question"></i></span></label>
                    <input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($s_name); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Administrator Contact Email <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="The primary email address used for system alerts and contact forms."><i class="fa-solid fa-question"></i></span></label>
                    <input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars($s_email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Maintenance Mode <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="When turned on, the public will see a maintenance screen. Admins can still browse normally."><i class="fa-solid fa-question"></i></span></label>
                    <select name="maintenance_mode" class="form-input" style="cursor: pointer;">
                        <option value="0" <?php echo ($s_maint === 0) ? 'selected' : ''; ?>>Off - Website is Live</option>
                        <option value="1" <?php echo ($s_maint === 1) ? 'selected' : ''; ?>>On - Hide from Public</option>
                    </select>
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
                <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fa-solid fa-search" style="margin-right: 10px; color: var(--text-muted);"></i> Global SEO Defaults
                </h2>
                
                <div class="form-group">
                    <label>Default Meta Description <span class="req-ast">*</span> <span class="tooltip-icon" data-tooltip="The global fallback description for search engines when a specific page lacks custom metadata."><i class="fa-solid fa-question"></i></span></label>
                    <textarea name="seo_description" class="form-input" style="height: 100px; resize: none; font-family: inherit;" required><?php echo htmlspecialchars($s_desc); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Google Analytics ID <span class="tooltip-icon" data-tooltip="Your tracking ID (e.g., G-XXXXXXXXXX). Leave blank to disable tracking."><i class="fa-solid fa-question"></i></span></label>
                    <input type="text" name="ga_id" class="form-input" value="<?php echo htmlspecialchars($s_ga); ?>">
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
                <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fa-solid fa-shield-halved" style="margin-right: 10px; color: var(--text-muted);"></i> Security & Anti-Spam
                </h2>
                
                <div class="form-group">
                    <label>Google reCAPTCHA v2 Site Key <span class="tooltip-icon" data-tooltip="The public key for the v2 Checkbox widget. Leave blank to disable CAPTCHA."><i class="fa-solid fa-question"></i></span></label>
                    <input type="text" name="recaptcha_site" class="form-input" value="<?php echo htmlspecialchars($s_rc_site); ?>">
                </div>
                
                <div class="form-group">
                    <label>Google reCAPTCHA v2 Secret Key <span class="tooltip-icon" data-tooltip="The private secret key used by the server to verify the user's response."><i class="fa-solid fa-question"></i></span></label>
                    <input type="password" name="recaptcha_secret" class="form-input" value="<?php echo htmlspecialchars($s_rc_sec); ?>">
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
                <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fa-solid fa-ticket-alt" style="margin-right: 10px; color: var(--text-muted);"></i> Tickets system
                </h2>
                
                <div class="form-group">
                    <label>Master Toggle (Front-End) <span class="tooltip-icon" data-tooltip="Turns the entire support portal on or off."><i class="fa-solid fa-question"></i></span></label>
                    <select name="ticket_system_enabled" class="form-input" style="cursor: pointer;">
                        <option value="1" <?php echo ($s_tkt_sys === 1) ? 'selected' : ''; ?>>Enabled - Portal is Active</option>
                        <option value="0" <?php echo ($s_tkt_sys === 0) ? 'selected' : ''; ?>>Disabled - Portal is Offline</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Allow New Tickets <span class="tooltip-icon" data-tooltip="If disabled, clients can reply to existing tickets, but cannot open new ones."><i class="fa-solid fa-question"></i></span></label>
                    <select name="ticket_creation_enabled" class="form-input" style="cursor: pointer;">
                        <option value="1" <?php echo ($s_tkt_new === 1) ? 'selected' : ''; ?>>Yes - Accept New Tickets</option>
                        <option value="0" <?php echo ($s_tkt_new === 0) ? 'selected' : ''; ?>>No - Pause New Inquiries</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Auto-Close 'Answered' Tickets <span class="tooltip-icon" data-tooltip="Automatically closes tickets awaiting a client response after X hours. Set to 0 to disable."><i class="fa-solid fa-question"></i></span></label>
                    <div style="display: flex; align-items: center;">
                        <input type="number" name="ticket_autoclose_hours" class="form-input" value="<?php echo htmlspecialchars($s_tkt_close); ?>" min="0" style="width: 100px; margin-right: 10px;">
                        <span style="color: var(--text-muted); font-size: 14px;">Hours</span>
                    </div>
                </div>
            </div>

            <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
                <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                    <i class="fa-solid fa-envelope" style="margin-right: 10px; color: var(--text-muted);"></i> Ticket Email Notifications
                </h2>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Notify Admin on New Ticket</label>
                        <select name="ticket_notify_admin_new" class="form-input">
                            <option value="1" <?php echo ($s_tkt_notif_new === 1) ? 'selected' : ''; ?>>Yes - Send Email</option>
                            <option value="0" <?php echo ($s_tkt_notif_new === 0) ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notify Admin on Client Reply</label>
                        <select name="ticket_notify_admin_reply" class="form-input">
                            <option value="1" <?php echo ($s_tkt_notif_rep === 1) ? 'selected' : ''; ?>>Yes - Send Email</option>
                            <option value="0" <?php echo ($s_tkt_notif_rep === 0) ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Message: Ticket Received (To Client)</label>
                    <textarea name="ticket_msg_received" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_rec); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Message: Admin Replied (To Client)</label>
                    <textarea name="ticket_msg_reply" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_rep); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Message: Admin Closed Ticket (To Client)</label>
                    <textarea name="ticket_msg_closed_admin" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_ca); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Message: Auto-Closed by System (To Client)</label>
                    <textarea name="ticket_msg_closed_auto" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_cu); ?></textarea>
                </div>
            </div>

        </div>
        
        <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Global Settings</button>
        </div>
    </form>
</section>

<?php require "inc-adm-foot.php"; ?>