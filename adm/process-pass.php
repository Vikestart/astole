<?php
	// Fetch database configuration and start session
	require "../db.php";

	// Function for returning back to page with an error message
	function returnWithErr($message) {
		$_SESSION['Errormsg'] = $message;
		header("Location: user-settings.php");
		die();
	}

	if (isset($_POST["password"]) && isset($_POST["newpassword"]) && isset($_POST["passconfirm"])) {

		function validate($data) {
			$data = trim($data);
			$data = stripslashes($data);
			$data = htmlspecialchars($data);
			return $data;
		}

		if (isset($_SESSION['User'])) {
			$user = $_SESSION['User'];
		} else {
			returnWithErr("Invalid user. Try signing out and back in.");
		}

		$oldpass = validate($_POST["password"]);
		$newpass = validate($_POST["newpassword"]);
		$passconfirm = validate($_POST["passconfirm"]);

		if (empty($oldpass)) {
			returnWithErr("The current password is required.");
		} else if (empty($newpass) || empty($passconfirm)) {
			returnWithErr("You need to specify the new password twice.");
		} else if ( (empty($newpass) && empty($passconfirm)) || $newpass != $passconfirm ) {
			returnWithErr("There was a mismatch in the two instances of the new password. Make sure to input the new password identically in both fields.");
		}

	} else {
		returnWithErr("Please fill out all the fields.");
	}

	// Connect to the DB
	// $conn = mysqli_connect($db_host . ":" . $db_port, $db_user, $db_pass, $db_tbl);
	// if (!$conn) {
	// 	returnWithErr("Database connection failed!");
	// }

	// Query the database for user
	$sql = "UPDATE users SET password = '$newpass' WHERE username = '$user' AND password = '$oldpass'";
	$result = mysqli_query($conn, $sql);

	if (mysqli_affected_rows($conn) === 1) {
		$_SESSION['Successmsg'] = "Your password has been updated.";
		header("Location: user-settings.php");
	} else {
		returnWithErr("The current password was incorrect. Please make sure to enter the correct current password.");
	}
?>
