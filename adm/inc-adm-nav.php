<?php
    // Read the cookie securely before drawing the layout
    $sidebar_class = "";
    if (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true') {
        $sidebar_class = "collapsed";
    }
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
            <a href="index.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'index.php') echo 'active'; ?>">
                <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
            </a>
            
            <span class="menu-label">Content</span>
            <a href="pages.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'pages.php' || basename($_SERVER['PHP_SELF']) == 'edit-page.php') echo 'active'; ?>">
                <i class="fa-solid fa-file-alt"></i> <span>Pages</span>
            </a>
            
            <a href="tickets.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'tickets.php' || basename($_SERVER['PHP_SELF']) == 'view-ticket.php') echo 'active'; ?>">
                <i class="fa-solid fa-ticket-alt"></i> <span>Tickets</span>
            </a>
            
            <span class="menu-label">Administration</span>
            <a href="users.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit-user.php') echo 'active'; ?>">
                <i class="fa-solid fa-users"></i> <span>Users</span>
            </a>
            
            <?php if ($userdata->row['user_role'] == 1) { ?>
                <a href="settings.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'settings.php') echo 'active'; ?>">
                    <i class="fa-solid fa-cog"></i> <span>Settings</span>
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