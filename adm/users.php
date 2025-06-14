<?php
	$site_title = "Users";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	// Fetch the list of users
	$userlist = new DBConn();
	$userlist->result = $userlist->conn->query("SELECT * FROM users ORDER BY user_uid");
?>

	<main id="page-default">

		<section>
			<a class="btn btn-green btn-r" href="edit-user.php?t=new"><i class="fas fa-plus" data-fa-transform="up-1"></i>New User</a>
			<h1 class="h1_underscore h1_lessmargin"><i class="fas fa-users" data-fa-transform="down-1"></i>User accounts</h1>
			<p>Here you can manage the user accounts for this website. Click on a user to edit that user.</p>

			<h2 class="h2_underscore">List of user accounts</h2>
			<?php
				if ($userlist->result->num_rows > 0) {
						echo '<ul>';
				    // output data of each row
				    while($userlist->row = $userlist->result->fetch_assoc()) {
				        echo "<li><a href='edit-user.php?t=edit&u=" . $userlist->row['user_id'] . "'><i class='fas fa-user'></i>" . $userlist->row['user_uid']. "</a></li>";
				    }
						echo '</ul>';
				} else {
				    echo "<p>No users found.</p>";
				}
			?>

			</div>
		</section>

	</main>

<?php require "inc-adm-end.php" ?>
