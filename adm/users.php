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
			<a class="btn btn-green btn-r" href="edit-user.php?t=new"><i class="fa-solid fa-plus" data-fa-transform="up-1"></i>New User</a>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-users" data-fa-transform="down-1"></i>User accounts</h1>
			<p>Here you can manage the user accounts for this website. Click on a user to edit that user.</p>

			<?php 
				// Display session messages, if any
				if (isset($msgtxt)) { 
					echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; 
				} 
			?>

			<h2 class="h2_underscore">List of user accounts</h2>
			<?php
				if ($userlist->result->num_rows > 0) {
					echo '<ul>';
				    // output data of each row
				    while($userlist->row = $userlist->result->fetch_assoc()) {
				        echo "<li><a href='edit-user.php?t=edit&u=" . $userlist->row['user_id'] . "'><i class='fa-solid fa-user'></i>" . htmlspecialchars($userlist->row['user_uid']). "</a></li>";
				    }
					echo '</ul>';
				} else {
				    echo "<p>No users found.</p>";
				}
			?>
		</section>

	</main>

<?php require "inc-adm-end.php" ?>