<?php
require_once "db.php";

class PageManager {
    public static function getPageBySlug($slug) {
        $db = new DBConn();
        
        // Added page_desc to the SELECT query
        $stmt = $db->conn->prepare("SELECT page_title, page_desc, page_contents, page_updated FROM pages WHERE page_slug = ? LIMIT 1");
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
?>