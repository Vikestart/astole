<?php
// /core/admin/views/dashboard.php
$site_title = "Dashboard";

// Load the Admin Header and Navigation
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/nav.php';

// Map the integer role to a human-readable string
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
        Welcome back, <strong><?php echo htmlspecialchars($userdata->row['user_uid'] ?? 'Admin'); ?></strong>! 
        You are currently logged in with <strong><?php echo $role_name; ?></strong> privileges.
    </p>
</section>

<div class="action-grid">
    
    <section class="mb-0">
        <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 15px;">
            <i class="fa-solid fa-shield-halved"></i> Security & Session
        </h2>
        <ul class="clean-list">
            <li><i class="fa-solid fa-envelope icon-muted"></i> <strong>Email:</strong> <?php echo htmlspecialchars($userdata->row['user_mail'] ?? 'N/A'); ?></li>
            <li><i class="fa-solid fa-clock icon-muted"></i> <strong>Last Seen:</strong> <?php echo htmlspecialchars($userdata->row['user_lastseen'] ?? 'N/A'); ?></li>
            <li><i class="fa-solid fa-network-wired icon-muted"></i> <strong>Current IP:</strong> <?php echo htmlspecialchars($userdata->row['user_ip'] ?? 'N/A'); ?></li>
        </ul>
    </section>

    <section class="mb-0">
        <h2 class="h1_underscore" style="font-size: 18px; margin-bottom: 15px;">
            <i class="fa-solid fa-bolt"></i> Quick Actions
        </h2>
        <div class="d-flex flex-col gap-10">
            <a href="/adm/pages?action=new" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Create New Page
            </a>
            <a href="/adm/pages" class="btn btn-outline">
                <i class="fa-solid fa-file-lines"></i> Manage Content
            </a>
            <a href="/adm/users" class="btn btn-outline">
                <i class="fa-solid fa-users"></i> User Management
            </a>
            
            <?php if (isset($userdata->row['user_role']) && $userdata->row['user_role'] == 1): ?>
            <a href="/adm/settings" class="btn btn-outline">
                <i class="fa-solid fa-sliders"></i> Global Settings
            </a>
            <?php endif; ?>
        </div>
    </section>

</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>