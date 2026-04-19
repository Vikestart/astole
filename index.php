<?php 
require_once "PageManager.php";

$route = isset($_GET['route']) && !empty($_GET['route']) ? rtrim($_GET['route'], '/') : 'home';
$pageData = PageManager::getPageBySlug($route);

if (!$pageData) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Page Not Found";
    $page_contents = "<p style='text-align: center; color: var(--text-muted);'>The content you are looking for does not exist or has been moved.</p>";
} else {
    $page_title = $pageData['page_title'];
    $page_contents = $pageData['page_contents'];
    
    // Only set the page description if the author actually typed one!
    if (!empty($pageData['page_desc'])) {
        $page_desc = $pageData['page_desc'];
    }
}

// Output the Smart Header
require_once "inc-head.php"; 
?>

<main class="page-container">
    <div class="hero-section">
        <div class="hero-badge">
            <i class="fa-solid fa-chart-line"></i> Technical Consultant & Developer
        </div>
        <h1 class="hero-title">Bridging Business Strategy<br>with <span>Modern Technology</span>.</h1>
        <p class="hero-subtitle">Specializing in ERP solutions, business controlling, and scalable web experiences.</p>
    </div>

    <section class="glass-panel">
        <div class="panel-header">
            <h2 class="panel-title"><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>
        <div class="panel-body">
            <?php echo $page_contents; ?>
        </div>
    </section>
</main>

<?php require_once "inc-end.php"; ?>