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

	$user_name = '';
	$user_mail = '';
	$user_role = ''; // Initialize $user_mail as well

	if ($user_isnew === false) {
		
		if (!isset($_GET['u'])) {
			header("Location: users.php");
			die();
		}
		$user_id = $_GET['u']; // Target user ID from URL

		$db_conn = new DBConn(); // Create a new DB connection instance
		$stmt_fetch_user = $db_conn->conn->prepare("SELECT user_uid, user_mail, user_role FROM users WHERE user_id = ?");
		if ($stmt_fetch_user === false) {
			error_log("Prepare user fetch in edit-user failed: (" . $db_conn->conn->errno . ") " . $db_conn->conn->error);
			header("Location: users.php?msg=db_error_edit");
			die();
		}
		$stmt_fetch_user->bind_param("i", $user_id);
		$stmt_fetch_user->execute();
		$result_fetch_user = $stmt_fetch_user->get_result();

		if ($result_fetch_user->num_rows === 1) {
			$user_data = $result_fetch_user->fetch_assoc();
			$user_name = $user_data['user_uid'];
			$user_mail = $user_data['user_mail'];
			$user_role = $user_data['user_role'];
		} else {
			header("Location: users.php?msg=user_not_found");
			die();
		}
		$stmt_fetch_user->close();
	}

    // Determine if the currently logged-in user is editing their own profile
    $is_self_edit = (isset($user_id) && isset($_SESSION['UserID']) && $user_id == $_SESSION['UserID']);
?>

	<main id="page-default">

		<section>
			<?php if (!$user_isnew && $userdata->row['user_role'] === 'admin' && $user_id !== $_SESSION['UserID']) { ?>
			<a class="btn btn-red btn-r" href="process-user.php?a=del&u=<?php echo htmlspecialchars($user_id); ?>" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fa-solid fa-trash-alt" data-fa-transform="up-1"></i>Delete User</a>
			<?php } ?>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-user-gear"></i><?php echo $site_title; ?></h1>
			<p>Here you can manage a user account, or add a new one.</p>

			<h2 class="h2_underscore">User details</h2>
			<p>Below you can find and modify the details of the user.</p>

			<form action="process-user.php" method="POST" class="form-default" autocomplete="off">

				<?php if (isset($msgtxt) && $msgorigin == "edituser") { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<input name="user_name" class="form-field" type="text" minlength="3" maxlength="30" placeholder="Username" autocomplete="off" value="<?php if (!$user_isnew) echo htmlspecialchars($user_name); ?>" required />
				<div class="form-field-container">
					<input id="user_pass_field" name="user_pass" class="form-field" type="password" minlength="8" maxlength="25" placeholder="Password" autocomplete="new-password" value="" <?php if ($user_isnew) echo 'required '; ?> />
					<button id="generate_pass_btn" class="form-inlinebtn" type="button" onclick="this.blur();"><i class="fa-solid fa-key"></i>Generate</button>
				</div>

				<input name="user_mail" class="form-field" type="text" minlength="6" maxlength="30" placeholder="E-mail" autocomplete="off" value="<?php if (!$user_isnew) echo htmlspecialchars($user_mail); ?>" required />
				
				<?php if ($userdata->row['user_role'] === 'admin') { ?>
				<select name="user_role" class="form-dropdown form-dropdown-medium" <?php if ($is_self_edit) echo 'disabled'; ?>>
						<option value="user" <?php if ($user_isnew || $user_role === 'user') echo 'selected'; ?>>User</option>
						<option value="moderator" <?php if ($user_role === 'moderator') echo 'selected'; ?>>Moderator</option>
						<option value="admin" <?php if ($user_role === 'admin') echo 'selected'; ?>>Administrator</option>
				</select>
				<?php } ?>
				<?php if (!$user_isnew) { ?><input name="user_id" type="hidden" value="<?php echo htmlspecialchars($user_id); ?>" /><?php } ?>
				<input name="action" value="<?php echo htmlspecialchars(($user_isnew) ? "newuser" : "edituser"); ?>" type="hidden" />
				<button class="form-submit btn-green" type="submit"><i class="fa-solid fa-<?php echo ($user_isnew) ? "plus" : "edit"; ?>" data-fa-transform="up-1"></i><?php echo ($user_isnew) ? "Create User" : "Save Changes"; ?></button>

			</form>

		</section>

	</main>

<?php require "inc-adm-foot.php" ?>