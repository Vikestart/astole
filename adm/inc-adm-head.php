<?php
// Force secure cookies and strict sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Generate CSRF Token if missing
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require "../db.php";
$mysqli = new DBConn();

// Check Login Status & Fetch User Data
if (isset($_SESSION['UserID'])) {
    $userdata = new stdClass();
    $active_user_ID = $_SESSION['UserID'];
    $stmt_userdata = $mysqli->conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt_userdata->bind_param("i", $active_user_ID);
    $stmt_userdata->execute();
    $result_userdata = $stmt_userdata->get_result();
    
    if ($result_userdata->num_rows === 1) {
        $userdata->row = $result_userdata->fetch_assoc();
    } else {
        header("Location: logout.php");
        die();
    }
    $stmt_userdata->close();
} else {
    // Exclude login page from redirecting to itself
    if (basename($_SERVER['PHP_SELF']) != 'login.php') {
        header("Location: login.php");
        die();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
    <meta name="robots" content="noindex, nofollow">
    <title>ACP | <?php echo isset($site_title) ? htmlspecialchars($site_title) : 'Dashboard'; ?></title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="assets/adm.css?v=<?php echo date('mdHis'); ?>">
    
    <script defer src="../assets/font-awesome/fontawesome.min.js"></script>
    <script defer src="../assets/font-awesome/solid.min.js"></script>
</head>
<body class="preload <?php echo (basename($_SERVER['PHP_SELF']) == 'login.php') ? 'login-page' : 'admin-page'; ?>">