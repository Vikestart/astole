<?php
  // Start session and get mySQL database parameters
  session_start();
  require "../db.php";

  // Set full page title
  $site_title = "AdminCP - " . $site_title;

  // Function for defining user - (this class is defined but not currently instantiated if commented out below)
  class ActiveUser {
    public $username;
    public $usermail;
    public $userrole;
    public $lastseen;
    public $timezone;
    public $ipaddress;

    public function __construct($username, $usermail, $userrole, $lastseen, // Corrected timezone parameter for DateTime constructor
                                $timezone, $ipaddress) {
      $this->username = $username;
      $this->usermail = $usermail;
      $this->userrole = $userrole;
      $this->lastseen = new DateTime($lastseen, new DateTimeZone('UTC'));
      $this->lastseen->setTimezone(new DateTimeZone($timezone));
      $this->timezone = $timezone;
      $this->ipaddress = $ipaddress;
    }
  }

  // Determine if we need to log in
  if (!isset($_SESSION['UserID']) && !isset($isLoginPage)) {
    header("Location: login.php");
    die();
  } else if (isset($_SESSION['UserID']) && isset($isLoginPage)) {
		header("Location: index.php");
		die();
	} else if (isset($_SESSION['UserID']) && !isset($isLoginPage)) {
    // Fetch user data
    /*
    // If ActiveUser class is desired:
    $user_timezone = isset($_SESSION['UserTimezone']) ? $_SESSION['UserTimezone'] : 'UTC'; // Fallback timezone
    $user = new ActiveUser(
      $_SESSION['User'],
      $_SESSION['UserMail'],
      $_SESSION['UserRole'],
      $_SESSION['LastSeen'],
      $user_timezone, // Pass the correct timezone
      $_SESSION['UserIP']
    );
    */

    // If ActiveUser class is not used, fetch user data into $userdata->row directly
    $userdata = new DBConn(); // Reuse DBConn object for user data fetch
    $active_user_ID = (int)$_SESSION['UserID']; // Ensure ID is integer
    $stmt_userdata = $userdata->conn->prepare("SELECT user_uid, user_mail, user_role, user_lastseen, user_timezone, user_ip FROM users WHERE user_id = ?");
    if ($stmt_userdata === false) {
        error_log("inc-adm-head.php: User data prepare failed: (" . $userdata->conn->errno . ") " . $userdata->conn->error);
        session_destroy();
        session_write_close();
        header("Location: login.php?msg=db_error_user_data");
        die();
    }
    $stmt_userdata->bind_param("i", $active_user_ID);
    $stmt_userdata->execute();
    $result_userdata = $stmt_userdata->get_result();

    if ($result_userdata->num_rows === 1) {
        $userdata->row = $result_userdata->fetch_assoc();
        // Set session variables that were previously used by ActiveUser if it's not instantiated
        $_SESSION['User'] = $userdata->row['user_uid'];
        $_SESSION['UserMail'] = $userdata->row['user_mail'];
        $_SESSION['UserRole'] = $userdata->row['user_role'];
        $_SESSION['LastSeen'] = $userdata->row['user_lastseen'];
        $_SESSION['UserTimezone'] = $userdata->row['user_timezone'];
        $_SESSION['UserIP'] = $userdata->row['user_ip'];

    } else {
        // If user ID from session doesn't match a user in DB, invalidate session.
        session_destroy();
        session_write_close();
        header("Location: login.php?msg=user_data_mismatch");
        die();
    }
    $stmt_userdata->close();
  }

  // Error handler - REVISED TO USE ASSOCIATIVE KEYS AND INCLUDE 'origin'
	if (isset($_SESSION['Sessionmsg'])) {
        $msgorigin = $_SESSION['Sessionmsg']['origin']; // Access 'origin' key
        $msgtype = $_SESSION['Sessionmsg']['type'];
        $msgicon = $_SESSION['Sessionmsg']['icon'];
        $msgexpire = $_SESSION['Sessionmsg']['expire'];
        if ($msgexpire == 0) { $msgexpire = 4500; } //Standard value
        $msgtxt = $_SESSION['Sessionmsg']['message']; // Access 'message' key
		unset($_SESSION['Sessionmsg']);
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="description" content="Aleksander Støle">
<meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
<meta charset="UTF-8">
<title><?php echo $site_title; ?></title>
<link rel="shortcut icon" href="../favicon.ico" type="image/x-icon">
<link rel="stylesheet" href="assets/adm.css?v=<?php echo date("mdHis") ?>">
<script defer src="../assets/font-awesome/fontawesome.min.js"></script>
<script defer src="../assets/font-awesome/solid.min.js"></script>
<script defer src="../assets/font-awesome/brands.min.js"></script>
</head>
<body>