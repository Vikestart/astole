<?php
// /core/actions/adm/process-login.php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_start();

$_SESSION['LastPage'] = $_SERVER['REQUEST_URI'] ?? '';

// Load the core engine & your custom procedural functions
require_once __DIR__ . '/../../init.php'; 
require_once __DIR__ . '/../../lib/admin-functions.php';

$session_token = $_SESSION['csrf_token'] ?? '';
$post_token = $_POST['csrf_token'] ?? '';

// Notice we change the redirect from "login.php" to "/adm" (which routes back securely)
if (empty($session_token) || empty($post_token) || !hash_equals($session_token, $post_token)) {
    returnWithMsg("login", "error", "times-circle", 5000, "Security validation failed. Please refresh and try again.", "/adm");
}

if (isset($_POST["username"]) && isset($_POST["password"])) {
    $admuser = htmlspecialchars(stripslashes(trim($_POST["username"])));
    $admpass = $_POST["password"]; 

    if (empty($admuser)) { returnWithMsg("login", "error", "times-circle", 0, "Username is required.", "/adm"); }
    if (empty($admpass)) { returnWithMsg("login", "error", "times-circle", 0, "Password is required.", "/adm"); }

    // Use the new Autoloaded Database Class
    $db = new \Core\Lib\Database();
    $conn = $db->getConnection();
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Check Brute Force table
    $stmt_check = $conn->prepare("SELECT attempts, last_attempt FROM login_attempts WHERE ip_address = ?");
    $stmt_check->bind_param("s", $ip_address);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    $stmt_check->close();
    
    if ($res_check->num_rows > 0) {
        $row_check = $res_check->fetch_assoc();
        $attempts = $row_check['attempts'];
        $last_attempt = strtotime($row_check['last_attempt']);
        $timeout = 15 * 60; // 15 minutes
        
        if ($attempts >= 5 && (time() - $last_attempt) < $timeout) {
            returnWithMsg("login", "error", "times-circle", 0, "Too many failed attempts. Please try again later.", "/adm");
        }
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE user_uid = ? OR user_mail = ? LIMIT 1");
    $stmt->bind_param("ss", $admuser, $admuser);
    $stmt->execute();
    $userquery = $stmt->get_result();
    $stmt->close();

    if ($userquery->num_rows === 1) {
        $userquery_row = $userquery->fetch_assoc();
        
        if (password_verify($admpass, $userquery_row['user_pass'])) {
            // SUCCESS
            $stmt_clear = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt_clear->bind_param("s", $ip_address);
            $stmt_clear->execute();
            $stmt_clear->close();

            $currtime = gmdate("Y-m-d H:i:s");
            $stmt_refreshuser = $conn->prepare("UPDATE users SET user_lastseen = ?, user_ip = ? WHERE user_id = ?");
            $stmt_refreshuser->bind_param("ssi", $currtime, $ip_address, $userquery_row['user_id']);
            $stmt_refreshuser->execute();
            $stmt_refreshuser->close();

            $_SESSION['UserID'] = $userquery_row['user_id'];
            $_SESSION['UserRole'] = $userquery_row['user_role']; // Required for RBAC routing later!
            
            // MANUALLY LOG THE SUCCESSFUL LOGIN (Fixed the undefined variable bug here)
            $logger = new \Core\Lib\ActivityLogger();
            $logger->logAdminActivity($userquery_row['user_id'], 'Login', 'Admin logged in');

            header("Location: /adm");
            die();
        }
    }

    // LOGIN FAILED - LOG ATTEMPT
    $stmt_log = $conn->prepare("INSERT INTO login_attempts (ip_address, attempts, last_attempt) VALUES (?, 1, NOW()) ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt = NOW()");
    $stmt_log->bind_param("s", $ip_address);
    $stmt_log->execute();
    $stmt_log->close();

    returnWithMsg("login", "error", "times-circle", 0, "Invalid username or password.", "/adm");
} else {
    header("Location: /adm");
    die();
}