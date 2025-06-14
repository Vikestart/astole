<?php
	if (isset($_GET['t'])) {
		$page_type = $_GET['t'];
		if ($page_type == "new") {
			$site_title = "New User";
			$user_isnew = true;
		} else if ($page_type == "edit") {
			$site_title = "Edit User";
			$user_isnew = false;
		} else {
			header("Location: users.php");
			die();
		}
	} else {
		header("Location: users.php");
		die();
	}
	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	$user_name = ''; $user_role = '';
	if ($user_isnew === false) {
		$user_id = $_GET['u'];
		$_SESSION['acp_page_id'] = $_GET['u'];
		$mysqli = new DBConn();
		$mysqli->result = $mysqli->conn->query("SELECT * FROM users WHERE user_id = '$user_id'");
		if ($mysqli->result->num_rows === 1) {
			$mysqli->row = $mysqli->result->fetch_assoc();
			$user_name = $mysqli->row['user_uid'];
			$user_mail = $mysqli->row['user_mail'];
			$user_role = $mysqli->row['user_role'];
		}
	}
?>

	<main id="page-default">

		<section>
			<?php if (!$user_isnew && $userdata->row['user_role'] === 'admin' && $user_role !== 'admin') { echo "<a class='btn btn-red btn-r' href='process-user.php?a=del&u=$user_id'><i class='fa-solid fa-user-slash' data-fa-transform='up-1'></i>Delete User</a>"; } ?>
			<h1 class="h1_underscore"><i class="fa-solid fa-<?php echo ($user_isnew) ? "user-plus" : "user-edit"; ?>"></i><?php echo ($user_isnew) ? "New User" : "Edit User: " . $user_name; ?></h1>

			<?php if ($userdata->row['user_role'] === 'admin') { ?>
			<form action="process-user.php?a=edit" method="POST" class="form-default" autocomplete="off">

				<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<input name="user_name" class="form-field" type="text" minlength="3" maxlength="30" placeholder="Username" autocomplete="off" value="<?php if (!$user_isnew) echo $user_name; ?>" required />
				<input name="user_pass" class="form-field" type="password" minlength="8" maxlength="25" placeholder="Password" autocomplete="off" value="" <?php if ($user_isnew) echo 'required '; ?> />
				<input name="user_mail" class="form-field" type="text" minlength="6" maxlength="30" placeholder="E-mail" autocomplete="off" value="<?php if (!$user_isnew) echo $user_mail; ?>" required />
				<?php if ($userdata->row['user_role'] === 'admin') { ?>
				<select name="user_role" class="form-dropdown form-dropdown-medium">
						<option value="user" <?php if ($user_isnew || $user_role === 'user') echo 'selected'; ?>>User</option>
						<option value="moderator" <?php if ($user_role === 'moderator') echo 'selected'; ?>>Moderator</option>
						<option value="admin" <?php if ($user_role === 'admin') echo 'selected'; ?>>Administrator</option>
				</select>
			<?php } ?>
				<input name="action" value="<?php echo ($user_isnew) ? "newuser" : "edituser"; ?>" type="hidden" />
				<button class="form-submit" type="submit"><i class="fa-solid fa-<?php echo ($user_isnew) ? "paper-plane" : "edit"; ?>"></i><?php echo ($user_isnew) ? "Submit" : "Save changes"; ?></button>

			</form>
		<?php } else { ?>
			<p>No settings available to your user role.</p>
		<?php } ?>

		</section>

	</main>

<?php require "inc-adm-end.php" ?>
