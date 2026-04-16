<?php require "inc-head.php";

$page_id = $_GET['p'];
$mysqli = new DBConn();

// Use prepared statements for ALL database queries
$stmt = $mysqli->conn->prepare("SELECT page_title, page_contents FROM pages WHERE page_id = ?");
$stmt->bind_param("i", $page_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $page_title = $row['page_title'];
    $page_contents = $row['page_contents'];
} else {
    // Handle 404 Not Found here
    $page_title = "Page Not Found";
    $page_contents = "The requested content could not be found.";
}
$stmt->close();
?>

	<section>
		<h1 class="h1_underscore"><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></h1>
		<p><?php echo nl2br(htmlspecialchars($page_contents, ENT_QUOTES, 'UTF-8')); ?></p>
	</section>

	</main>

<?php require "inc-end.php"; ?>
