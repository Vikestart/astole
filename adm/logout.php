<?php
	session_start();
	session_destroy();
	session_write_close();
	session_start();
	$_SESSION['Sessionmsg'] = array("logout", "success", "check-circle", 0, "You have been signed out.");
	header("Location: login.php");
	die();
?>
