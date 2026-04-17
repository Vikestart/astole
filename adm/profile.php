<?php
	$site_title = "Profile";
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	// Fetch list of pages authored by current user
	$active_user_uid = $userdata->row['user_uid'];
	$userpages = new DBConn();
	$userpages->result = $userpages->conn->query("SELECT * FROM pages WHERE page_author = '$active_user_uid' ORDER BY page_title");
?>

	<main id="page-default">

		<section>
			<a class="btn btn-red btn-r btn-desktop" href="logout.php"><i class="fa-solid fa-sign-out-alt" data-fa-transform="up-1"></i>Sign out</a>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-user-cog"></i>User settings</h1>
			<p>You can manage your user settings on this page, such as changing your password.</p>

			<h2 class="h2_underscore">Profile details</h2>
			<p>Your username and email address can be changed here.</p>

			<form action="process-profile.php" method="POST" class="form-default" autocomplete="off">

				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

				<?php if (isset($msgtxt) && $msgorigin == "changebasics") { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<input name="user_name" class="form-field" type="text" minlength="3" maxlength="20" placeholder="Username" value="<?php echo $userdata->row['user_uid']; ?>" autocomplete="off" required />
				<input name="user_mail" class="form-field" type="text" minlength="6" maxlength="30" placeholder="E-mail" value="<?php echo $userdata->row['user_mail']; ?>" autocomplete="off" required />
				<input name="action" value="changebasics" type="hidden" />
				<button class="form-submit" type="submit"><i class="fa-solid fa-edit"></i>Save</button>

			</form>
			
			<h2 class="h2_underscore">Change password</h2>
			<p>Type in the current password below, then the new password two times to confirm.</p>

			<form action="process-profile.php" method="POST" class="form-default" autocomplete="off">

				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

				<?php if (isset($msgtxt) && $msgorigin == "changepassword") { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<input name="oldpass" class="form-field" type="password" minlength="8" maxlength="20" placeholder="Current password" autocomplete="off" required />
				<input name="newpass" class="form-field" type="password" minlength="8" maxlength="20" placeholder="New password" autocomplete="new-password" required />
				<input name="passconfirm" class="form-field" type="password" minlength="8" maxlength="20" placeholder="Confirm password" autocomplete="new-password" required />
				<input name="action" value="changepassword" type="hidden" />
				<button class="form-submit" type="submit"><i class="fa-solid fa-edit"></i>Save</button>

			</form>

			<h2 class="h2_underscore">Timezone</h2>
			<p>Set the timezone to your current local timezone to get the correct dates and timestamps.</p>

			<form action="process-profile.php" method="POST" class="form-default">

				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

				<?php if (isset($msgtxt) && $msgorigin == "changetimezone") { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<?php function formatOffset($offset) {
	        $hours = $offset / 3600;
	        $remainder = $offset % 3600;
	        $sign = $hours > 0 ? '+' : '-';
	        $hour = (int) abs($hours);
	        $minutes = (int) abs($remainder / 60);

	        if ($hour == 0 AND $minutes == 0) {
	            $sign = ' ';
	        }
	        return $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) .':'. str_pad($minutes,2, '0');
				}
				$utc = new DateTimeZone('UTC');
				$dt = new DateTime('now', $utc);
				echo '<select name="timezone" class="form-dropdown form-dropdown-wide">';
				foreach(DateTimeZone::listIdentifiers() as $tz) {
				    $current_tz = new DateTimeZone($tz);
				    $offset =  $current_tz->getOffset($dt);
				    $transition =  $current_tz->getTransitions($dt->getTimestamp(), $dt->getTimestamp());
				    $abbr = $transition[0]['abbr'];
						if ($tz == $userdata->row['user_timezone']) {
							echo '<option value="' .$tz. '" selected>' .$tz. ' [' .$abbr. ' '. formatOffset($offset). ']</option>';
						} else {
							echo '<option value="' .$tz. '">' .$tz. ' [' .$abbr. ' '. formatOffset($offset). ']</option>';
						}
				}
				echo '</select>'; ?>
				<input name="action" value="changetimezone" type="hidden" />
				<button class="form-submit" type="submit"><i class="fa-solid fa-edit"></i>Save</button>

			</form>

		</section>

		<section>
			<a class="btn btn-green btn-r" href="edit-page.php?t=new"><i class="fa-solid fa-plus" data-fa-transform="up-1"></i>New Page</a>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-layer-group" data-fa-transform="down-1"></i>Your pages</h1>
			<p>Below you will find a list of pages that you have authored.</p>

			<h2 class="h2_underscore">List of your pages</h2>
			<?php
				if ($userpages->result->num_rows > 0) {
						echo '<ul>';
				    // output data of each row
				    while($userpages->row = $userpages->result->fetch_assoc()) {
				        echo "<li><a href='editpage.php?t=edit&p=" . $userpages->row['page_id'] . "'><i class='fa-solid fa-file'></i>" . $userpages->row['page_title']. "</a></li>";
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
