<?php
	$site_title = "Dashboard";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

    // 1. Map the integer role to a human-readable string
    $role_name = 'User';
    if (isset($userdata->row['user_role'])) {
        if ($userdata->row['user_role'] == 1) {
            $role_name = 'Administrator';
        } elseif ($userdata->row['user_role'] == 2) {
            $role_name = 'Moderator';
        }
    }
?>

    <section>
        <h1 class="h1_underscore"><i class="fa-solid fa-gauge"></i> System Dashboard</h1>
        <p style="font-size: 16px;">
            Welcome back, <strong><?php echo htmlspecialchars($userdata->row['user_uid']); ?></strong>! 
            You are currently logged in with <strong><?php echo $role_name; ?></strong> privileges.
        </p>
    </section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <section style="margin-bottom: 0;">
            <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 15px;">
                <i class="fa-solid fa-shield-halved"></i> Security & Session
            </h2>
            <ul style="list-style: none; padding: 0; line-height: 2;">
                <li><i class="fa-solid fa-envelope" style="color: var(--text-muted); width: 20px;"></i> <strong>Email:</strong> <?php echo htmlspecialchars($userdata->row['user_mail']); ?></li>
                <li><i class="fa-solid fa-clock" style="color: var(--text-muted); width: 20px;"></i> <strong>Last Seen:</strong> <?php echo htmlspecialchars($userdata->row['user_lastseen']); ?></li>
                <li><i class="fa-solid fa-network-wired" style="color: var(--text-muted); width: 20px;"></i> <strong>Current IP:</strong> <?php echo htmlspecialchars($userdata->row['user_ip']); ?></li>
            </ul>
        </section>

        <section style="margin-bottom: 0;">
            <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 15px;">
                <i class="fa-solid fa-bolt"></i> Quick Actions
            </h2>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="edit-page.php?t=new" class="btn btn-primary">
                    <i class="fa-solid fa-plus"></i> Create New Page
                </a>
                <a href="pages.php" class="btn" style="background: var(--bg-body); border: 1px solid var(--border); color: var(--text-main);">
                    <i class="fa-solid fa-file-lines"></i> Manage Content
                </a>
                <a href="users.php" class="btn" style="background: var(--bg-body); border: 1px solid var(--border); color: var(--text-main);">
                    <i class="fa-solid fa-users"></i> User Administration
                </a>
            </div>
        </section>

    </div>

<?php require "inc-adm-foot.php"; ?>