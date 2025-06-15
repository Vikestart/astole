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

    public function __construct($username, $usermail, $userrole, $lastseen, $timezone, $ipaddress) {
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
    /*$user = new ActiveUser($_SESSION['User'], $_SESSION['UserMail'], $_SESSION['UserRole'], $_SESSION['LastSeen'], $_SESSION['Timezone'], $_SERVER['REMOTE_ADDR']);*/
    $active_user_ID = $_SESSION['UserID'];
    
    // Fetch user data of current user using prepared statement
    $userdata = new DBConn(); // Reusing the $userdata variable name for consistency
    $stmt_userdata = $userdata->conn->prepare("SELECT * FROM users WHERE user_id = ?");
    if ($stmt_userdata === false) {
        error_log("Prepare SELECT user in inc-adm-head failed: (" . $userdata->conn->errno . ") " . $userdata->conn->error);
        // If there's a database error here, it's critical. Redirect to login.
        header("Location: login.php?msg=db_error_head");
        die();
    }
    $stmt_userdata->bind_param("i", $active_user_ID);
    $stmt_userdata->execute();
    $result_userdata = $stmt_userdata->get_result();

    if ($result_userdata->num_rows === 1) {
        $userdata->row = $result_userdata->fetch_assoc();
    } else {
        // If user ID from session doesn't match a user in DB, invalidate session.
        session_destroy();
        session_write_close();
        header("Location: login.php?msg=user_data_mismatch");
        die();
    }
    $stmt_userdata->close();
  }

  // Error handler
	if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg'][0];
    $msgtype = $_SESSION['Sessionmsg'][1];
    $msgicon = $_SESSION['Sessionmsg'][2];
    $msgexpire = $_SESSION['Sessionmsg'][3];
    if ($msgexpire == 0) { $msgexpire = 4500; } //Standard value
    $msgtxt = $_SESSION['Sessionmsg'][4];
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
