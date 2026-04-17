<?php

	if (isset($_GET['t'])) {
		$page_type = $_GET['t'];
		if ($page_type == "new") {
			$site_title = "New Page";
			$page_isnew = true;
            // Initialize variables for new page to prevent undefined variable notices
            $page_title = '';
            $page_contents = '';
		} else if ($page_type == "edit") {
			$site_title = "Edit Page";
			$page_isnew = false;
		} else {
			header("Location: pages.php");
			die();
		}
	} else {
		header("Location: pages.php");
		die();
	}

	require "inc-adm-head.php";
	require "inc-adm-nav.php";

	if ($page_isnew === false) {
		// Ensure $page_id is an integer. Casting to int is the first line of defense.
		$page_id = (int)$_GET['p'];
		$_SESSION['acp_page_id'] = $page_id; // Store the safely cast integer in session

		$mysqli = new DBConn();

		// Use Prepared Statements to prevent SQL Injection
		$stmt = $mysqli->conn->prepare("SELECT page_title, page_contents FROM pages WHERE page_id = ?");
		if ($stmt === false) {
			// Log the actual database error for debugging, but don't show it to the user
			error_log("edit-page.php: Prepare failed: (" . $mysqli->conn->errno . ") " . $mysqli->conn->error);
            // Set a generic, user-friendly error message in session
			$_SESSION['Sessionmsg'] = array("error", "times-circle", 0, "A database error occurred while fetching page data. Please try again.", "pages.php");
			header("Location: pages.php");
			die();
		}

		// Bind parameters: 'i' for integer type
		$stmt->bind_param("i", $page_id);

		// Execute the statement
		$stmt->execute();

		// Get the result
		$mysqli->result = $stmt->get_result();

		if ($mysqli->result->num_rows === 1) {
			$mysqli->row = $mysqli->result->fetch_assoc();
			$page_title = $mysqli->row['page_title'];
			$page_contents = $mysqli->row['page_contents'];
		} else {
			// Page not found or invalid ID provided.
            // Redirect with an appropriate message.
			$_SESSION['Sessionmsg'] = array("error", "times-circle", 0, "The requested page was not found or the ID is invalid.", "pages.php");
			header("Location: pages.php");
			die();
		}

		// Close the statement
		$stmt->close();
	}
?>

	<main id="page-default">

		<section>
			<?php if (!$page_isnew) { ?>
				<a id="deletePageBtn" class="btn btn-red btn-r btn-desktop" data-page-id="<?php echo htmlspecialchars($page_id); ?>" href="#"><i class="fa-solid fa-trash-alt" data-fa-transform="up-1"></i>Delete Page</a>
			<?php } ?>
            <h1 class="h1_underscore"><i class="fa-solid fa-<?php echo ($page_isnew) ? "file-plus" : "file-edit"; ?>"></i><?php echo ($page_isnew) ? "New Page" : "Edit Page: " . htmlspecialchars($page_title); ?></h1>

			<form action="process-page.php?a=edit" method="POST" class="form-default" autocomplete="off">

				<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

				<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fa-solid fa-$msgicon'></i><span>" . htmlspecialchars($msgtxt) . "</span></div>"; } ?>

                <input name="pagetitle" class="form-field" type="text" minlength="3" maxlength="30" placeholder="Page title" autocomplete="off" value="<?php if (!$page_isnew) echo htmlspecialchars($page_title); ?>" required />
                <textarea name="pagecontents" class="form-textbox" placeholder="What do you have on your mind?" required><?php if (!$page_isnew) echo htmlspecialchars($page_contents); ?></textarea>
				<input name="action" value="<?php echo ($page_isnew) ? "newpage" : "editpage"; ?>" type="hidden" />
				<button class="form-submit" type="submit"><i class="fa-solid fa-<?php echo ($page_isnew) ? "paper-plane" : "edit"; ?>"></i><?php echo ($page_isnew) ? "Submit" : "Save changes"; ?></button>

			</form>
		</section>

	</main>

	<?php require "inc-adm-foot.php"; ?>