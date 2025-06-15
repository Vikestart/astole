<?php
	$site_title = "Home";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	// Fetch list of recently updated pages
	$updatedpages = new DBConn();
	$updatedpages->result = $updatedpages->conn->query("SELECT * FROM pages ORDER BY page_updated DESC LIMIT 5");
?>

	<main id="page-default">

		<section>
			<a class="btn btn-red btn-r btn-desktop" href="logout.php"><i class="fa-solid fa-sign-out-alt" data-fa-transform="up-1"></i>Sign out</a>
			<a class="btn btn-r btn-desktop" href="/" target="_blank"><i class="fa-solid fa-eye"></i>Go to website</a>
			<h1 class="h1_underscore"><i class="fa-solid fa-shield-check"></i>Welcome, <?php echo $userdata->row['user_uid']; ?></h1>
			<p>Your last login was <strong><?php echo $userdata->row['user_lastseen']; /*format("F j, o \a\\t H:i T")*/ ?></strong>.</p>
			<p>Your current role is: <strong><?php echo ucfirst(htmlspecialchars($userdata->row['role_name'])); ?></strong>.</p>
			<p>Your IP is: <strong><?php echo $userdata->row['user_ip']; ?></strong>.</p>
			</div>
		</section>

		<section>
			<h1 class="h1_underscore"><i class="fas fa-bell"></i>Notifications</h1>
			<p>No new notifications. Check back later.</p>
			</div>
		</section>

		<section>
			<a class="btn btn-green btn-r" href="edit-page.php?t=new"><i class="fa-solid fa-plus" data-fa-transform="up-1"></i>New Page</a>
			<h1 class="h1_underscore"><i class="fas fa-clock" data-fa-transform="down-1"></i>Recently updated</h1>
			<?php
				if ($updatedpages->result->num_rows > 0) {
						echo '<ul>';
				    // output data of each row
				    while($updatedpages->row = $updatedpages->result->fetch_assoc()) {
				        echo "<li><a href='edit-page.php?t=edit&p=" . $updatedpages->row['page_id'] . "'><i class='fas fa-file'></i>" . $updatedpages->row['page_title']. "</a></li>";
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
