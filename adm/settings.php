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

        </div>
        
        <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Global Settings</button>
        </div>
    </form>
</section>

<?php require "inc-adm-foot.php"; ?>