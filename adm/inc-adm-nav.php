<?php
    // Read the cookie securely before drawing the layout
    $sidebar_class = "";
    if (isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true') {
        $sidebar_class = "collapsed";
    }
?>
<div class="admin-wrapper <?php echo $sidebar_class; ?>" id="admin-layout">

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
                <i class="fa-solid fa-file-lines"></i> <span>Pages</span>
            </a>
            
            <span class="menu-label">Administration</span>
            <a href="users.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit-user.php') echo 'active'; ?>">
                <i class="fa-solid fa-users"></i> <span>Users</span>
            </a>
            <a href="settings.php" class="menu-item <?php if(basename($_SERVER['PHP_SELF']) == 'settings.php') echo 'active'; ?>">
                <i class="fa-solid fa-cog"></i> <span>Settings</span>
            </a>
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
                    <div class="user-avatar">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?php echo htmlspecialchars($userdata->row['user_uid']); ?></span>
                    </div>
                    <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fa-solid fa-sign-out-alt"></i></a>
                </div>
            </div>
        </header>

        <main class="admin-content">