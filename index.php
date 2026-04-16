<?php require "inc-head.php";

// Assuming $page_id is set here, or via $_GET['p'] in a page.php script
$page_id = 3; 
$mysqli = new DBConn();

// SECURITY FIX: Use prepared statements
$stmt = $mysqli->conn->prepare("SELECT page_title, page_contents FROM pages WHERE page_id = ?");
$stmt->bind_param("i", $page_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
	$row = $result->fetch_assoc();
	$page_title = $row['page_title'];
	$page_contents = $row['page_contents'];
} else {
	$page_title = "Page Not Found";
	$page_contents = "The content you are looking for does not exist or has been moved.";
}
$stmt->close();
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

<?php require "inc-end.php"; ?>