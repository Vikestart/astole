<?php 
require_once "PageManager.php";

// 1. Get the requested route from the URL. Default to 'home' if empty.
$route = isset($_GET['route']) && !empty($_GET['route']) ? rtrim($_GET['route'], '/') : 'home';

// 2. Fetch the data using our new PageManager
$pageData = PageManager::getPageBySlug($route);

// 3. Handle 404 Not Found if the slug doesn't exist in the database
if (!$pageData) {
    header("HTTP/1.0 404 Not Found");
    $page_title = "Page Not Found";
    $page_contents = "The content you are looking for does not exist or has been moved.";
} else {
    $page_title = $pageData['page_title'];
    $page_contents = $pageData['page_contents'];
}

// 4. Output the View (HTML)
require_once "inc-head.php"; 
?>

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
			<p><?php echo nl2br(htmlspecialchars($page_contents, ENT_QUOTES, 'UTF-8')); ?></p>
		</div>
	</section>

<?php require_once "inc-end.php"; ?>