<?php
	$site_title = "System Settings";
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
		die();
	}

	if (isset($_SESSION['Sessionmsg'])) {
		$msgorigin = $_SESSION['Sessionmsg']['origin']; $msgtype = $_SESSION['Sessionmsg']['type']; $msgicon = $_SESSION['Sessionmsg']['icon']; $msgtxt = $_SESSION['Sessionmsg']['message'];
		unset($_SESSION['Sessionmsg']);
	}

	$db = new DBConn();
	$res = $db->conn->query("SELECT setting_key, setting_value FROM settings");
	$config = [];
	if ($res) {
		while ($row = $res->fetch_assoc()) { $config[$row['setting_key']] = $row['setting_value']; }
	}

	// Variables
	$s_name = $config['site_name'] ?? '';
	$s_desc = $config['seo_description'] ?? '';
	$s_ga = $config['ga_id'] ?? '';
	$s_maint = (int)($config['maintenance_mode'] ?? 0);
	$s_rc_site = $config['recaptcha_site'] ?? '';
	$s_rc_sec = $config['recaptcha_secret'] ?? '';
	$s_tkt_sys = (int)($config['ticket_system_enabled'] ?? 1);
	$s_tkt_new = (int)($config['ticket_creation_enabled'] ?? 1);
	$s_tkt_close = (int)($config['ticket_autoclose_hours'] ?? 72);
	$s_tkt_retention = (int)($config['attachment_retention_days'] ?? 365);
	$s_site_email = $config['site_email'] ?? '';
	$s_tkt_notif_new = (int)($config['ticket_notify_admin_new'] ?? 1);
	$s_tkt_notif_rep = (int)($config['ticket_notify_admin_reply'] ?? 1);
	$s_tkt_msg_rec = $config['ticket_msg_received'] ?? '';
	$s_tkt_msg_rep = $config['ticket_msg_reply'] ?? '';
	$s_tkt_msg_ca = $config['ticket_msg_closed_admin'] ?? '';
	$s_tkt_msg_cu = $config['ticket_msg_closed_auto'] ?? '';
?>

<section>
	<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
		<h1 style="margin: 0; font-size: 24px; color: var(--color-heading);"><i class="fa-solid fa-sliders"></i> System Settings</h1>
	</div>

	<?php if (isset($msgtxt) && $msgorigin == 'settings') {
		echo "<div class='msgbox msgbox-$msgtype'><i class='fa-solid fa-$msgicon'></i> " . htmlspecialchars($msgtxt) . "</div>";
	} ?>

	<div class="card">
		<form action="process-settings.php" method="POST">
			<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

			<div class="settings-layout">
				<div class="settings-sidebar">
					<div class="admin-tabs-nav">
						<button class="admin-tab-btn active" data-tab="tab-general"><i class="fa-solid fa-globe" style="width: 20px;"></i> General & SEO</button>
						<button class="admin-tab-btn" data-tab="tab-security"><i class="fa-solid fa-shield-halved" style="width: 20px;"></i> Security & APIs</button>
						<button class="admin-tab-btn" data-tab="tab-tickets"><i class="fa-solid fa-ticket-alt" style="width: 20px;"></i> Ticket System</button>
					</div>
				</div>

				<div class="settings-content">

					<div class="admin-tab-pane active" id="tab-general">
						<h3 style="margin-top: 0; color: var(--color-heading); margin-bottom: 5px; font-size: 18px;">Site Identity</h3>
						<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Manage your website's public name, SEO meta tags, and analytics.</p>
						
						<div class="settings-form-row">
							<div class="form-group" style="margin-bottom: 0;">
								<label>Website Name <span class="tooltip-icon" data-tooltip="The main title of your website, used in the header and emails."><i class="fa-solid fa-question"></i></span></label>
								<input type="text" name="site_name" class="form-input" value="<?php echo htmlspecialchars($s_name); ?>" required>
							</div>
							<div class="form-group" style="margin-bottom: 0;">
								<label>Google Analytics ID <span class="tooltip-icon" data-tooltip="Your GA4 Measurement ID (e.g., G-XXXXXXXXXX)."><i class="fa-solid fa-question"></i></span></label>
								<input type="text" name="ga_id" class="form-input" value="<?php echo htmlspecialchars($s_ga); ?>">
							</div>
						</div>
						
						<div class="form-group" style="margin-bottom: 0;">
							<label>SEO Meta Description <span class="tooltip-icon" data-tooltip="The description that appears in Google search results."><i class="fa-solid fa-question"></i></span></label>
							<textarea name="seo_description" class="form-input" style="height: 80px; resize: vertical;"><?php echo htmlspecialchars($s_desc); ?></textarea>
						</div>
						
						<div class="form-group" style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border); margin-bottom: 0;">
							<label style="display: flex; align-items: center; cursor: pointer;">
								<input type="checkbox" name="maintenance_mode" value="1" <?php if($s_maint === 1) echo 'checked'; ?> style="margin-right: 10px; width: 18px; height: 18px;">
								<strong>Enable Maintenance Mode</strong>
							</label>
							<div style="color: var(--text-muted); font-size: 13px; margin-top: 5px; margin-left: 28px;">If checked, only logged-in administrators can view the front-end website.</div>
						</div>
					</div>

					<div class="admin-tab-pane" id="tab-security">
						<h3 style="margin-top: 0; color: var(--color-heading); margin-bottom: 5px; font-size: 18px;">Google reCAPTCHA v3</h3>
						<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Protect your ticket forms and login pages from spam bots. Leave blank to disable.</p>
						
						<div class="settings-form-row" style="margin-bottom: 0;">
							<div class="form-group" style="margin-bottom: 0;">
								<label>Site Key</label>
								<input type="text" name="recaptcha_site" class="form-input" value="<?php echo htmlspecialchars($s_rc_site); ?>">
							</div>
							<div class="form-group" style="margin-bottom: 0;">
								<label>Secret Key</label>
								<input type="text" name="recaptcha_secret" class="form-input" value="<?php echo htmlspecialchars($s_rc_sec); ?>">
							</div>
						</div>
					</div>

					<div class="admin-tab-pane" id="tab-tickets">
						<h3 style="margin-top: 0; color: var(--color-heading); margin-bottom: 5px; font-size: 18px;">Ticket System Configuration</h3>
						<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Configure support portal behavior and automated cleanup processes.</p>

						<div class="settings-form-row" style="margin-bottom: 30px;">
							<div style="background: var(--bg-surface); padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
								<h4 style="margin: 0 0 15px 0; color: var(--color-heading);">Module Status</h4>
								<label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 15px;">
									<input type="checkbox" name="ticket_system_enabled" value="1" <?php if($s_tkt_sys === 1) echo 'checked'; ?> style="margin-right: 10px; width: 16px; height: 16px;">
									<span style="font-weight: 500;">Enable Support Portal</span>
								</label>
								<label style="display: flex; align-items: center; cursor: pointer;">
									<input type="checkbox" name="ticket_creation_enabled" value="1" <?php if($s_tkt_new === 1) echo 'checked'; ?> style="margin-right: 10px; width: 16px; height: 16px;">
									<span style="font-weight: 500;">Allow New Tickets</span>
								</label>
							</div>

							<div style="background: var(--bg-surface); padding: 20px; border-radius: 8px; border: 1px solid var(--border);">
								<h4 style="margin: 0 0 15px 0; color: var(--color-heading);">Automation & Cleanup</h4>
								<div class="form-group" style="margin-bottom: 15px;">
									<label>Auto-Close 'Answered' Tickets</label>
									<div style="display: flex; align-items: center;">
										<input type="number" name="ticket_autoclose_hours" class="form-input" value="<?php echo htmlspecialchars($s_tkt_close); ?>" min="0" style="width: 100px; margin-right: 10px;">
										<span style="color: var(--text-muted); font-size: 14px;">Hours</span>
									</div>
								</div>
								<div class="form-group" style="margin-bottom: 0;">
									<label>Attachment Retention</label>
									<div style="display: flex; align-items: center;">
										<input type="number" name="attachment_retention_days" class="form-input" value="<?php echo htmlspecialchars($s_tkt_retention); ?>" min="0" style="width: 100px; margin-right: 10px;">
										<span style="color: var(--text-muted); font-size: 14px;">Days</span>
									</div>
								</div>
							</div>
						</div>

						<h3 style="color: var(--color-heading); margin-bottom: 5px; border-top: 1px solid var(--border); padding-top: 30px; font-size: 18px;">Email Routing</h3>
						<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Define where system notifications are sent and what triggers them.</p>

						<div class="form-group">
							<label>Admin Notification Email</label>
							<input type="email" name="site_email" class="form-input" value="<?php echo htmlspecialchars($s_site_email); ?>">
						</div>
						<div style="display: flex; gap: 20px; margin-top: 15px; margin-bottom: 40px;">
							<label style="display: flex; align-items: center; cursor: pointer;">
								<input type="checkbox" name="ticket_notify_admin_new" value="1" <?php if($s_tkt_notif_new === 1) echo 'checked'; ?> style="margin-right: 10px;"> Notify Admin on New Tickets
							</label>
							<label style="display: flex; align-items: center; cursor: pointer;">
								<input type="checkbox" name="ticket_notify_admin_reply" value="1" <?php if($s_tkt_notif_rep === 1) echo 'checked'; ?> style="margin-right: 10px;"> Notify Admin on Client Replies
							</label>
						</div>

						<h3 style="color: var(--color-heading); margin-bottom: 5px; border-top: 1px solid var(--border); padding-top: 30px; font-size: 18px;">Automated Client Messages</h3>
						<p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">Customize the automated email responses sent to clients.</p>

						<div class="settings-form-row">
							<div class="form-group" style="margin-bottom: 0;">
								<label>Message: New Ticket Received (To Client)</label>
								<textarea name="ticket_msg_received" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_rec); ?></textarea>
							</div>
							<div class="form-group" style="margin-bottom: 0;">
								<label>Message: Admin Replied (To Client)</label>
								<textarea name="ticket_msg_reply" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_rep); ?></textarea>
							</div>
						</div>
						<div class="settings-form-row" style="margin-bottom: 0;">
							<div class="form-group" style="margin-bottom: 0;">
								<label>Message: Admin Closed Ticket (To Client)</label>
								<textarea name="ticket_msg_closed_admin" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_ca); ?></textarea>
							</div>
							<div class="form-group" style="margin-bottom: 0;">
								<label>Message: Auto-Closed by System (To Client)</label>
								<textarea name="ticket_msg_closed_auto" class="form-input" style="height: 60px; resize: vertical;"><?php echo htmlspecialchars($s_tkt_msg_cu); ?></textarea>
							</div>
						</div>
					</div>

				</div>
			</div>

			<div class="settings-save-bar" style="margin-top: 0;">
                <div class="save-info-text" style="color: var(--text-muted); font-size: 14px;">
                    <i class="fa-solid fa-circle-info"></i> Changes across all tabs will be saved simultaneously.
                </div>
                <button type="submit" class="btn btn-primary" id="save-settings-btn" disabled>
                    <i class="fa-solid fa-save"></i> <span>Save changes</span>
                </button>
            </div>

		</form>
	</div>
</section>

<?php require "inc-adm-foot.php"; ?>