<?php
	// 1. Access the current active session
	session_start();

	// 2. Unset all session variables
	$_SESSION = array();

	// 3. Force the user's browser to delete the session cookie
	if (ini_get("session.use_cookies")) {
	    $params = session_get_cookie_params();
	    setcookie(session_name(), '', time() - 42000,
	        $params["path"], $params["domain"],
	        $params["secure"], $params["httponly"]
	    );
	}

	// 4. Destroy the session on the server
	session_destroy();
	session_write_close();

	// 5. Start a BRAND NEW session just for the flash message
	session_start(); 
	session_regenerate_id(true);

	// 6. Set the success message
	$_SESSION['Sessionmsg'] = array(
        'origin' => "logout",
        'type' => "success",
        'icon' => "check-circle",
        'expire' => 4500, // I changed this to 4500ms so it smoothly fades out!
        'message' => "You have been signed out safely."
    );

	// 7. Redirect
	header("Location: login.php");
	die();
?>