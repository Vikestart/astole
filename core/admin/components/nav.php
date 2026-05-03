<?php
// /core/admin/components/nav.php

$sidebar_class = "";
if (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true') {
    $sidebar_class = "collapsed";
}

// Clean the active page URL based on the Admin Router
global $urlSegments;
$current_page = $urlSegments[1] ?? 'dashboard';
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
            <a href="/adm" class="menu-item <?php if($current_page == 'dashboard') echo 'active'; ?>">
                <i class="fa-solid fa-gauge"></i> <span>Dashboard</span>
            </a>
            
            <span class="menu-label">Content</span>
            <a href="/adm/pages" class="menu-item <?php if($current_page == 'pages') echo 'active'; ?>">
                <i class="fa-solid fa-file-alt"></i> <span>Pages</span>
            </a>
            
            <a href="/adm/menus" class="menu-item <?php if($current_page == 'menus') echo 'active'; ?>">
                <i class="fa-solid fa-list-ul"></i> <span>Menus</span>
            </a>
            
            <span class="menu-label">Support</span>
            <a href="/adm/tickets" class="menu-item <?php if($current_page == 'tickets') echo 'active'; ?>">
                <i class="fa-solid fa-headset"></i> <span>Tickets</span>
            </a>
            
            <span class="menu-label">Administration</span>
            <a href="/adm/users" class="menu-item <?php if($current_page == 'users') echo 'active'; ?>">
                <i class="fa-solid fa-users"></i> <span>Users & Roles</span>
            </a>
            
            <a href="/adm/activity" class="menu-item <?php if($current_page == 'activity') echo 'active'; ?>">
                <i class="fa-solid fa-clipboard-list"></i> <span>Activity Log</span>
            </a>
            
            <?php if (isset($userdata->row['user_role']) && $userdata->row['user_role'] == 1): ?>
            <a href="/adm/settings" class="menu-item <?php if($current_page == 'settings') echo 'active'; ?>">
                <i class="fa-solid fa-sliders"></i> <span>Global Settings</span>
            </a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left d-flex align-center">
                <button class="sidebar-toggle" id="sidebar-toggle"><i class="fa-solid fa-bars-staggered"></i></button>
                
                <div class="topbar-search">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" placeholder="Search pages, users, or tickets... (Press '/')">
                </div>
            </div>
            
            <div class="topbar-right d-flex align-center gap-10">
                <a href="/" target="_blank" class="topbar-btn" title="View Website"><i class="fa-solid fa-external-link-alt"></i></a>
                
                <div class="user-pill">
                    <a href="/adm/profile" class="profile-link">
                        <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
                        <span class="user-name"><?php echo htmlspecialchars($userdata->row['user_uid'] ?? 'Admin'); ?></span>
                    </a>
                    <div class="pill-divider"></div>
                    <a href="/core/actions/adm/logout.php" class="logout-link" title="Sign Out">
                        <i class="fa-solid fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </header>

        <main class="admin-content">
            <?php
            // Lightweight check for Maintenance Mode to display the global banner
            $nav_db = new \Core\Lib\Database();
            $res_maint = $nav_db->getConnection()->query("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
            if ($res_maint && $res_maint->num_rows > 0) {
                if ($res_maint->fetch_assoc()['setting_value'] == '1') {
                    echo '<div class="admin-maint-banner">
                            <i class="fa-solid fa-triangle-exclamation admin-maint-icon"></i>
                            Maintenance Mode is currently active. The front-end website is hidden from public view.
                          </div>';
                }
            }
            ?>