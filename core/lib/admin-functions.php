<?php
// /core/lib/admin-functions.php

function returnWithMsg($origin, $type, $icon, $expire, $message, $redirect, $conn = null, $log_type = null, $log_desc = null) {
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    
    $_SESSION['Sessionmsg'] = array(
        'origin' => $origin,
        'type' => $type,
        'icon' => $icon,
        'expire' => $expire,
        'message' => $message
    );

    // If a log type and description were passed, log the activity
    if ($log_type && $log_desc && isset($_SESSION['UserID'])) {
        // We use the new OOP class. No require_once needed thanks to the autoloader!
        $logger = new \Core\Lib\ActivityLogger();
        $logger->logAdminActivity($_SESSION['UserID'], $log_type, $log_desc);
    }

    header("Location: " . $redirect);
    exit();
}