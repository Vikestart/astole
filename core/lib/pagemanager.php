<?php
// /core/lib/pagemanager.php
namespace Core\Lib;

class PageManager {
    private $db;   // We add this to hold the object and keep it alive!
    private $conn;

    public function __construct() {
        // Save the instance to the class property
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }

    public function getPageBySlug(string $slug) {
        $stmt = $this->conn->prepare("SELECT page_title, page_desc, page_type, page_contents, page_updated FROM pages WHERE page_slug = ? LIMIT 1");
        
        if (!$stmt) {
            // Just in case the query fails to prepare, prevent a fatal error
            return false; 
        }

        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $data = $result->fetch_assoc();
            $stmt->close();
            return $data;
        }
        
        $stmt->close();
        return false; // Page not found
    }
}