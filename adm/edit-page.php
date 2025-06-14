<?php
	if (isset($_GET['t'])) {
		$page_type = $_GET['t'];
		if ($page_type == "new") {
			$site_title = "New Page";
			$page_isnew = true;
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
		$page_id = $_GET['p'];
		$_SESSION['acp_page_id'] = $_GET['p'];
		$mysqli = new DBConn();
		$mysqli->result = $mysqli->conn->query("SELECT * FROM pages WHERE page_id = '$page_id'");
		if ($mysqli->result->num_rows === 1) {
			$mysqli->row = $mysqli->result->fetch_assoc();
			$page_title = $mysqli->row['page_title'];
			$page_contents = $mysqli->row['page_contents'];
		}
	}
?>

	<main id="page-default">

		<section>
			<?php if (!$page_isnew) { echo "<a class='btn btn-red btn-r' href='process-page.php?a=del&p=$page_id'><i class='fas fa-trash-alt' data-fa-transform='up-1'></i>Delete Page</a>"; } ?>
			<h1 class="h1_underscore"><i class="fas fa-<?php echo ($page_isnew) ? "file-plus" : "file-edit"; ?>"></i><?php echo ($page_isnew) ? "New Page" : "Edit Page: " . $page_title; ?></h1>

			<form action="process-page.php?a=edit" method="POST" class="form-default" autocomplete="off">

				<?php if (isset($msgtxt)) { echo "<div class='msgbox msgbox-$msgtype' data-expire='$msgexpire'><i class='fas fa-$msgicon'></i><span>" . $msgtxt . "</span></div>"; } ?>

				<input name="pagetitle" class="form-field" type="text" minlength="3" maxlength="30" placeholder="Page title" autocomplete="off" value="<?php if (!$page_isnew) echo $page_title; ?>" required />
				<textarea name="pagecontents" class="form-textbox" placeholder="What do you have on your mind?" required><?php if (!$page_isnew) echo $page_contents; ?></textarea>
				<input name="action" value="<?php echo ($page_isnew) ? "newpage" : "editpage"; ?>" type="hidden" />
				<button class="form-submit" type="submit"><i class="fas fa-<?php echo ($page_isnew) ? "paper-plane" : "edit"; ?>"></i><?php echo ($page_isnew) ? "Submit" : "Save changes"; ?></button>

			</form>
		</section>

	</main>

<?php require "inc-adm-end.php" ?>
