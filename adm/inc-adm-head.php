<?php
  // Force cookies to be accessible only via HTTP protocol (prevents XSS theft)
  ini_set('session.cookie_httponly', 1);
  // Optional but recommended: Uncomment the next line if your site uses SSL/HTTPS!
  ini_set('session.cookie_secure', 1); 
  // Prevents session fixation by refusing uninitialized session IDs
  ini_set('session.use_strict_mode', 1);
  
  session_start();

  if (empty($_SESSION['csrf_token'])) {
      // Generate a cryptographically secure random token
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  
  require "../db.php";

  // Set full page title
  $site_title = "AdminCP - " . $site_title;

  // Function for defining user - (this class is defined but not currently instantiated if commented out below)
  class ActiveUser {
    public $username;
    public $usermail;
    public $userrole; // numeric role ID
    public $rolename; // string role name - ADDED
    public $lastseen;
    public $timezone;
    public $ipaddress;

    public function __construct($username, $usermail, $userrole, $rolename, $lastseen, $timezone, $ipaddress) { // ADDED $rolename
      $this->username = $username;
      $this->usermail = $usermail;
      $this->userrole = $userrole;
      $this->rolename = $rolename; // Store role name - ADDED
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
    // Fetch user data along with their role name
    $user_ID = (int)$_SESSION['UserID'];
    $db_conn = new DBConn(); // Establish DB connection for this script

    // Prepared statement to fetch user details and their role name using LEFT JOIN.
    // Assuming 'roles' table has 'role_id' and 'role_name' columns.
    // LEFT JOIN ensures user data is fetched even if a role_id doesn't have a matching role_name.
    $stmt_userdata = $db_conn->conn->prepare("SELECT u.user_uid, u.user_mail, u.user_role, u.user_lastseen, u.user_timezone, u.user_ip, r.role_name FROM users u LEFT JOIN roles r ON u.user_role = r.role_id WHERE u.user_id = ?");
    
    if ($stmt_userdata === false) {
        error_log("inc-adm-head.php: Prepare statement failed: (" . $db_conn->conn->errno . ") " . $db_conn->conn->error);
        // In case of a critical database issue with the prepare statement, invalidate session and redirect.
        session_destroy();
        session_write_close();
        header("Location: login.php?msg=db_error"); // Redirect to login with a generic error
        die();
    }
    
    $stmt_userdata->bind_param("i", $user_ID); // "i" for integer
    $stmt_userdata->execute();
    $result_userdata = $stmt_userdata->get_result();

    if ($result_userdata->num_rows === 1) {
        $userdata = (object)['row' => $result_userdata->fetch_assoc()]; // Populate $userdata->row
        // The role_name will now be available in $userdata->row['role_name']
        // The numeric role ID is still in $userdata->row['user_role']
    } else {
        // If user ID from session doesn't match a user in DB, invalidate session.
        session_destroy();
        session_write_close();
        header("Location: login.php?msg=user_data_mismatch");
        die();
    }
    $stmt_userdata->close(); // Close the prepared statement
  }

  // Error handler (existing code for displaying session messages)
	if (isset($_SESSION['Sessionmsg'])) {
    $msgorigin = $_SESSION['Sessionmsg']['origin'];
    $msgtype = $_SESSION['Sessionmsg']['type'];
    $msgicon = $_SESSION['Sessionmsg']['icon'];
    $msgexpire = $_SESSION['Sessionmsg']['expire'];
    if ($msgexpire == 0) { $msgexpire = 4500; } //Standard value
    $msgtxt = $_SESSION['Sessionmsg']['message'];
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