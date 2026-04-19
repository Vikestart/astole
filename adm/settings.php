<?php
	$site_title = "Global Settings";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";
?>

<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px;">
        <h1 style="margin: 0; font-size: 22px; color: var(--color-heading);"><i class="fa-solid fa-cog"></i> Global Configuration</h1>
    </div>

    <div class="msgbox msgbox-warning" style="margin-bottom: 30px;">
        <i class="fa-solid fa-tools"></i> 
        This module is currently under construction. Future updates will introduce global SEO defaults, maintenance mode toggles, and site-wide branding configurations here.
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; opacity: 0.5; pointer-events: none; user-select: none;">
        
        <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
            <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                <i class="fa-solid fa-globe" style="margin-right: 10px; color: var(--text-muted);"></i> General Information
            </h2>
            
            <div class="form-group">
                <label>Website Name</label>
                <input type="text" class="form-input" value="My Awesome Portfolio" disabled>
            </div>
            
            <div class="form-group">
                <label>Administrator Contact Email</label>
                <input type="text" class="form-input" value="admin@example.com" disabled>
            </div>
            
            <div class="form-group">
                <label>Maintenance Mode</label>
                <select class="form-input" disabled>
                    <option>Off - Website is Live</option>
                    <option>On - Hide from Public</option>
                </select>
            </div>
        </div>

        <div style="background: var(--bg-body); border: 1px solid var(--border); border-radius: 8px; padding: 25px;">
            <h2 style="font-size: 16px; margin-bottom: 15px; display: flex; align-items: center;">
                <i class="fa-solid fa-search" style="margin-right: 10px; color: var(--text-muted);"></i> Global SEO Defaults
            </h2>
            
            <div class="form-group">
                <label>Default Meta Description</label>
                <textarea class="form-input" style="height: 100px; resize: none; font-family: inherit;" disabled>The global fallback description for search engines when a specific page lacks custom metadata.</textarea>
            </div>
            
            <div class="form-group">
                <label>Google Analytics ID</label>
                <input type="text" class="form-input" value="G-XXXXXXXXXX" disabled>
            </div>
        </div>

    </div>
    
    <div style="margin-top: 20px; border-top: 1px solid var(--border); padding-top: 20px;">
        <button class="btn btn-primary" type="button" disabled><i class="fa-solid fa-save"></i> Save Global Settings</button>
    </div>
</section>

<?php require "inc-adm-foot.php"; ?>