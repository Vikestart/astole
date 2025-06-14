<?php require "inc-head.php";

$page_id = 3;
$mysqli = new DBConn();
$mysqli->result = $mysqli->conn->query("SELECT * FROM pages WHERE page_id = '$page_id'");
if ($mysqli->result->num_rows === 1) {
	$mysqli->row = $mysqli->result->fetch_assoc();
	$page_title = $mysqli->row['page_title'];
	$page_contents = $mysqli->row['page_contents'];
}	?>

		<section>

			<h1 class="h1_underscore"><?php echo $page_title; ?></h1>
			<p><?php echo nl2br($page_contents); ?></p>

		</section>

	</main>

<?php require "inc-end.php"; ?>
