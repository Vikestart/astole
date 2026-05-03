<?php
// /core/lib/activitylogger.php
namespace Core\Lib;

class ActivityLogger {
    private $db;   // Add this!
    private $conn;

    public function __construct() {
        $this->db = new Database(); // Save it to the class property
        $this->conn = $this->db->getConnection();
    }

    public function logAdminActivity(int $user_id, string $type, string $desc) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $time = gmdate("Y-m-d H:i:s");
        
        $stmt = $this->conn->prepare("INSERT INTO activity_log (user_id, action_type, action_desc, ip_address, created_at) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("issss", $user_id, $type, $desc, $ip, $time);
            $stmt->execute();
            $stmt->close();
        }
    }
}