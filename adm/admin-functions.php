<?php
// Centralized function to handle session messages, redirects, and automated activity logging
function returnWithMsg($origin, $type, $icon, $expire, $message, $redirect_url, $db_conn = null, $log_type = null, $log_desc = null) {
    
    // Auto-Log Activity: Only triggers if it's a 'success', logging params are provided, and a user is logged in
    if ($type === 'success' && $db_conn !== null && $log_type !== null && $log_desc !== null) {
        require_once "activity-logger.php";
        $user_id = $_SESSION['UserID'] ?? 0;
        if ($user_id > 0) {
            logAdminActivity($db_conn, $user_id, $log_type, $log_desc);
        }
    }

    // Set the Session Message
    $_SESSION['Sessionmsg'] = array(
        'origin' => $origin,
        'type' => $type,
        'icon' => $icon,
        'expire' => $expire,
        'message' => $message
    );

    // Redirect
    if ($redirect_url) {
        header("Location: " . $redirect_url);
    } else {
        header("Location: index.php"); // Safety fallback
    }
    die();
}
?>