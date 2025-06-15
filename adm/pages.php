<?php
	$site_title = "Pages";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	// Fetch the list of pages
	$pageslist = new DBConn();
	$pageslist->result = $pageslist->conn->query("SELECT * FROM pages ORDER BY page_title");
?>

	<main id="page-default">

		<section>
			<a class="btn btn-green btn-r" href="edit-page.php?t=new"><i class="fa-solid fa-plus" data-fa-transform="up-1"></i>New Page</a>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-layer-group" data-fa-transform="down-1"></i>Pages management</h1>
			<p>Here you can manage the pages of the website. Click on a page in the list below to start editing.</p>

			<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

			<h2 class="h2_underscore">List of pages</h2>
			<?php
				if ($pageslist->result->num_rows > 0) {
						echo '<ul>';
				    // output data of each row
				    while($pageslist->row = $pageslist->result->fetch_assoc()) {
				        echo "<li><a href='edit-page.php?t=edit&p=" . $pageslist->row['page_id'] . "'><i class='fa-solid fa-file'></i>" . $pageslist->row['page_title']. "</a></li>";
				    }
						echo '</ul>';
				} else {
				    echo "<p>No pages found.</p>";
				}
			?>

			</div>
		</section>

	</main>

<?php require "inc-adm-foot.php" ?>
