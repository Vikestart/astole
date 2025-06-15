<?php
	session_start();
	session_unset();
	session_destroy();
	session_write_close();
	session_start(); 
	$_SESSION['Sessionmsg'] = array(
        'origin' => "logout",
        'type' => "success",
        'icon' => "check-circle",
        'expire' => 0,
        'message' => "You have been signed out."
    );
	header("Location: login.php");
	die();
?>