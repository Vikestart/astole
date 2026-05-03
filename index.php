<?php
// /index.php (Master Front Controller)

// 1. Bootstrap the Engine
// This loads the config, instantiates the autoloader, and secures the DB connection.
require_once __DIR__ . '/core/init.php';

// 2. Parse the URL
// The .htaccess file passes the path as a GET variable named 'url'.
$requestUrl = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';

// Split the URL into segments for easy routing (e.g., ['adm', 'tickets', 'view'])
$urlSegments = explode('/', $requestUrl);

// 3. Master Routing Logic

if ($urlSegments[0] === 'adm') {
    // --- ADMIN PANEL ROUTING ---
    // If the URL starts with /adm, we pass control to the Admin Router.
    $adminRouter = __DIR__ . '/core/admin/index.php';
    
    if (file_exists($adminRouter)) {
        require_once $adminRouter;
    } else {
        die("System Error: Admin core missing.");
    }
    exit;
}

// --- FRONTEND ROUTING (Theming Engine) ---

$template = new \Core\Lib\Template();

// Parse the slug (Empty URL defaults to 'home')
$pageSlug = $urlSegments[0] ?: 'home';
$rendered = false;

// 1. Hardcoded System Routes (Apps/Tools that don't live in the CMS pages table)
if ($pageSlug === 'ticket') {
    // This handles domain.com/ticket (viewing an existing ticket)
    $rendered = $template->render('view-ticket');
} 

// 2. Dynamic CMS Routes (Everything else falls back to the Database)
else {
    $pageManager = new \Core\Lib\PageManager();
    $pageData = $pageManager->getPageBySlug($pageSlug);
    
    if ($pageData) {
        // Pass the $pageSlug to the view so we can check if it's the home page
        $rendered = $template->render('dynamic-page', [
            'page' => $pageData, 
            'pageSlug' => $pageSlug
        ]);
    }
}

// --- INTEGRATED 404 HANDLER ---
if (!$rendered) {
    http_response_code(404);
    $template->component('header', ['page_title' => '404 - Page Not Found']);
    ?>
    <main class="page-container">
        <div class="hero-section ticket-hero">
            <h1 class="hero-title">404</h1>
            <p class="hero-subtitle">Oops! The page you are looking for doesn't exist or has been moved.</p>
        </div>
        <section class="glass-panel ticket-main-panel text-center">
            <i class="fa-solid fa-compass empty-state-icon"></i>
            <h3 class="empty-state-title">Let's get you back on track</h3>
            <a href="/" class="ticket-btn-primary no-underline d-inline-block">Return to Homepage</a>
        </section>
    </main>
    <?php
    $template->component('footer');
}