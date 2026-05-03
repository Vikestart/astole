<?php
// /core/admin/index.php
if (!defined('DB_HOST')) { die('Direct access forbidden.'); }

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Authentication Gateway
// If the user is NOT logged in, force them to the login view.
if (empty($_SESSION['UserID'])) {
    $adminView = __DIR__ . '/views/login.php';
    if (file_exists($adminView)) {
        require_once $adminView;
    } else {
        die("System Error: Admin login view missing.");
    }
    exit;
}

// 2. Optional: Role-Based Access Control (RBAC) Check
// If you want to strictly limit this area to Role 1 (Admin) or 2 (Moderator)
if (isset($_SESSION['UserRole']) && $_SESSION['UserRole'] > 2) {
    die("Access Denied: You do not have permission to view the admin panel.");
}

// 3. Admin Routing Logic
// Our master index.php passes URL segments. $urlSegments[0] is 'adm'. 
// We look at $urlSegments[1] to figure out which admin page to load.
$adminRoute = $urlSegments[1] ?? 'dashboard';

// Define allowed admin routes to prevent directory traversal attacks
$allowedRoutes = [
    'dashboard' => 'dashboard.php',
    'pages' => 'pages.php',
    'users' => 'users.php',
    'tickets' => 'tickets.php',
    'menus' => 'menus.php',
    'settings' => 'settings.php',
    'activity' => 'activity-log.php',
    'profile' => 'profile.php'
];

// 4. Render the Admin View
if (array_key_exists($adminRoute, $allowedRoutes)) {
    $viewFile = __DIR__ . '/views/' . $allowedRoutes[$adminRoute];
    
    if (file_exists($viewFile)) {
        require_once $viewFile;
    } else {
        echo "<h1>Admin View Not Found</h1><p>The file {$allowedRoutes[$adminRoute]} has not been migrated yet.</p>";
    }
} else {
    // Admin 404
    echo "<h1>404 - Admin Route Not Found</h1>";
}