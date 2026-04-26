<?php
    // Read the cookie securely before drawing the layout
    $sidebar_class = "";
    if (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true') {
        $sidebar_class = "collapsed";
    }
    
    // Clean the active page URL for the menu highlight logic
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<div class="admin-wrapper <?php echo $sidebar_class; ?>" id="admin-layout">

    <div class="mobile-overlay" id="mobile-overlay"></div>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon">A.S</div>
            <div class="brand-text">Control Panel</div>
        </div>
        
        <div class="sidebar-menu">
            <span class="menu-label">System</span>
            <a href="/adm" class="menu-item <?php if($current_page == 'index') echo 'active'; ?>">
                <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
            </a>
            
            <span class="menu-label">Content</span>
            <a href="pages" class="menu-item <?php if($current_page == 'pages') echo 'active'; ?>">
                <i class="fa-solid fa-file-alt"></i> <span>Pages</span>
            </a>
            
            <a href="menus" class="menu-item <?php if($current_page == 'menus') echo 'active'; ?>">
                <i class="fa-solid fa-list-ul"></i> <span>Menus</span>
            </a>
            
            <a href="tickets" class="menu-item <?php if($current_page == 'tickets' || $current_page == 'view-ticket') echo 'active'; ?>">
                <i class="fa-solid fa-ticket-alt"></i> <span>Tickets</span>
            </a>

            <span class="menu-label">Administration</span>
            <a href="users" class="menu-item <?php if($current_page == 'users') echo 'active'; ?>">
                <i class="fa-solid fa-users"></i> <span>Users</span>
            </a>
            
            <?php if ($userdata->row['user_role'] == 1) { ?>
                <a href="settings" class="menu-item <?php if($current_page == 'settings') echo 'active'; ?>">
                    <i class="fa-solid fa-cogs"></i> <span>Settings</span>
                </a>
                
                <a href="activity-log" class="menu-item <?php if($current_page == 'activity-log') echo 'active'; ?>">
                    <i class="fa-solid fa-list-check"></i> <span>Activity Log</span>
                </a>
            <?php } ?>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn"><i class="fa-solid fa-bars"></i></button>
                
                <button class="desktop-menu-btn" title="Toggle Sidebar"><i class="fa-solid fa-bars-staggered"></i></button>
                
                <div class="topbar-search">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" placeholder="Search the control panel...">
                </div>
            </div>
            
            <div class="topbar-right">
                <a href="../index.php" target="_blank" class="topbar-action tooltip" title="View Website">
                    <i class="fa-solid fa-external-link-alt"></i>
                </a>
                
                <div class="user-pill">
                    <a href="profile.php" class="user-profile-link" title="My Profile">
                        <div class="user-avatar">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($userdata->row['user_uid']); ?></span>
                        </div>
                    </a>
                    
                    <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fa-solid fa-sign-out-alt"></i></a>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <?php
            // Lightweight check for Maintenance Mode to display the global banner
            require_once "../db.php";
            $nav_db = new DBConn();
            $res_maint = $nav_db->conn->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
            if ($res_maint && $res_maint->num_rows > 0) {
                if ($res_maint->fetch_assoc()['setting_value'] == '1') {
                    echo '<div style="background: #fee2e2; color: #991b1b; padding: 14px 20px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 25px; display: flex; align-items: center; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                            <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px; margin-right: 15px;"></i>
                            Maintenance Mode is currently active. The front-end website is hidden from public visitors.
                          </div>';
                }
            }
            ?>