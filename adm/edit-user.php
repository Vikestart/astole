<?php
	if (isset($_GET['t'])) {
		$page_type = $_GET['t'];
		if ($page_type == "new") {
			$site_title = "New User";
			$section_title = "New user";
			$user_isnew = true;
		} else if ($page_type == "edit") {
			$site_title = "Edit User";
			$section_title = "Edit user";
			$user_isnew = false;
		} else {
			header("Location: users.php");
			die();
		}
	} else {
		header("Location: users.php");
		die();
	}
	require "inc-adm-head.php"; // This sets $userdata->row for the current logged-in user
	require "inc-adm-nav.php";

	// Define role constants (matching process-user.php)
	const ROLE_ADMIN = 1;
	const ROLE_MODERATOR = 2;
	const ROLE_USER = 3;

	$user_name = '';
	$user_mail = '';
	$user_role_int = ROLE_USER; // Initialize with default integer role for new users
	$user_id = null; // Initialize user_id

	if ($user_isnew === false) {
		
		if (!isset($_GET['u'])) {
			header("Location: users.php");
			die();
		}
		$user_id = (int)$_GET['u']; // Target user ID from URL

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
			$row_user = $result_fetch_user->fetch_assoc();
			$user_name = $row_user['user_uid'];
			$user_mail = $row_user['user_mail'];
			// Directly assign fetched integer role ID
			$user_role_int = (int)$row_user['user_role'];
		} else {
			header("Location: users.php?msg=user_not_found");
			die();
		}
		$stmt_fetch_user->close();
	}

	// Determine if the role dropdown should be disabled
	$disable_role_dropdown = false;
	$current_user_id = (int)$_SESSION['UserID']; // Get current logged-in user's ID
	$current_user_role = (int)$userdata->row['user_role']; // Current logged-in user's role (from inc-adm-head.php)

	if (!$user_isnew) { // Only apply disable logic if editing an existing user
		// Condition 1: Editing own account and current user is Admin
		// An admin cannot demote their own account.
		if ($user_id === $current_user_id && $current_user_role === ROLE_ADMIN) {
			$disable_role_dropdown = true;
		}
		// Condition 2: Current user is not Admin AND current user has lower privilege than target user
		// This applies to moderators trying to edit admins, or regular users trying to edit anyone.
		// Lower integer value means higher privilege (Admin=1, Moderator=2, User=3)
		if ($current_user_role !== ROLE_ADMIN && $current_user_role > $user_role_int) {
			$disable_role_dropdown = true;
		}
	}
?>

	<main id="page-default">

		<section>
			<?php
			// Display delete button only if editing an existing user, current user is admin, and NOT editing self
			if (!$user_isnew && $current_user_role === ROLE_ADMIN && $user_id !== $current_user_id) { ?>
				<a id="deleteUserBtn" class="btn btn-red btn-r btn-desktop" data-user-id="<?php echo htmlspecialchars($user_id); ?>" href="#"><i class="fa-solid fa-trash-alt" data-fa-transform="up-1"></i>Delete User</a>
			<?php } ?>
			<h1 class="h1_underscore h1_lessmargin"><i class="fa-solid fa-user-cog"></i><?php echo $section_title; ?></h1>
			<p>Here you can <?php echo ($user_isnew) ? "create a new" : "edit an existing"; ?> user account.</p>

			<h2 class="h2_underscore">Account details</h2>
			<p>Modify the user's details. Leave password empty to keep current password.</p>

			<form action="process-user.php" method="POST" class="form-default" autocomplete="off">

				<?php if (isset($msgtxt) && ($msgorigin == "newuser" || $msgorigin == "edituser")) { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . htmlspecialchars($msgtxt) . "</span></div>"; } ?>

				<input name="user_name" class="form-field" type="text" minlength="3" maxlength="40" placeholder="Username" autocomplete="off" value="<?php if (!$user_isnew) echo htmlspecialchars($user_name); ?>" required />
				
				<div class="form-inline">
					<input id="user_pass_field" name="user_pass" id="user_pass" class="form-field form-field-inline" type="password" minlength="8" maxlength="25" placeholder="Password" autocomplete="new-password" value="" <?php if ($user_isnew) echo 'required '; ?> />
					<button id="generate_pass_btn" class="form-inlinebtn" type="button" onclick="this.blur();"><i class="fa-solid fa-key"></i>Generate</button>
				</div>

				<input name="user_mail" class="form-field" type="text" minlength="6" maxlength="30" placeholder="E-mail" autocomplete="off" value="<?php if (!$user_isnew) echo htmlspecialchars($user_mail); ?>" required />
				
				<?php
				// Only display the role dropdown if the current user is an admin
				// Otherwise, display the role as text and use a hidden input
				if ($current_user_role === ROLE_ADMIN) { ?>
				<select name="user_role" class="form-dropdown form-dropdown-medium" <?php if ($disable_role_dropdown) echo 'disabled'; ?>>
						<option value="<?php echo ROLE_USER; ?>" <?php if ($user_isnew || $user_role_int === ROLE_USER) echo 'selected'; ?>>User</option>
						<option value="<?php echo ROLE_MODERATOR; ?>" <?php if ($user_role_int === ROLE_MODERATOR) echo 'selected'; ?>>Moderator</option>
						<option value="<?php echo ROLE_ADMIN; ?>" <?php if ($user_role_int === ROLE_ADMIN) echo 'selected'; ?>>Administrator</option>
				</select>
				<?php } else {
					// If not an admin, display the user's role as plain text or hidden input
					// This ensures the form still submits the user's current role even if the dropdown is not shown
					// and prevents non-admins from tampering with this value.
					$display_role_name = '';
					switch ($user_role_int) {
						case ROLE_ADMIN:
							$display_role_name = 'Administrator';
							break;
						case ROLE_MODERATOR:
							$display_role_name = 'Moderator';
							break;
						case ROLE_USER:
						default:
							$display_role_name = 'User';
							break;
					}
					echo '<p>User Role: <strong>' . htmlspecialchars($display_role_name) . '</strong></p>';
					echo '<input type="hidden" name="user_role" value="' . htmlspecialchars($user_role_int) . '">'; // Hidden field to pass role
				}
				?>
				<?php if (!$user_isnew) { ?><input name="user_id" type="hidden" value="<?php echo htmlspecialchars($user_id); ?>" /><?php } ?>
				<input name="action" value="<?php echo htmlspecialchars(($user_isnew) ? "newuser" : "edituser"); ?>" type="hidden" />
				<button class="form-submit btn-green" type="submit"><i class="fa-solid fa-<?php echo ($user_isnew) ? "plus" : "edit"; ?>"></i><?php echo ($user_isnew) ? "Create User" : "Save Changes"; ?></button>
				
			</form>

		</section>

	</main>

<?php require "inc-adm-foot.php"; ?>