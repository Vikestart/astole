<?php
function logAdminActivity($db_conn, $user_id, $type, $desc) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $time = gmdate("Y-m-d H:i:s");
    
    $stmt = $db_conn->prepare("INSERT INTO activity_log (user_id, action_type, action_desc, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("issss", $user_id, $type, $desc, $ip, $time);
        $stmt->execute();
        $stmt->close();
    }
}
?>