<?php
// /core/lib/database.php
namespace Core\Lib;

use mysqli;
use Exception;

class Database {
    private $mysqli;

    public function __construct() {
        // Enable strict MySQLi exceptions for better error catching (PHP 8.1+ default, but good to enforce)
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            // Check if our configuration constants exist before trying to connect
            if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS') || !defined('DB_NAME') || !defined('DB_PORT')) {
                throw new Exception("Database Configuration Error: Credentials are not defined.");
            }

            // Initialize the OOP mysqli connection using constants from /site/config/config.php
            $this->mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            
            // Set the charset to strictly utf8mb4 for full Unicode/Emoji support and security
            $this->mysqli->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            // Log the exact error to the server's error_log, but hide sensitive details from the UI
            error_log("Database Connection Failed: " . $e->getMessage());
            die("System Error: Unable to establish a database connection. Please check your configuration.");
        }
    }

    /**
     * Returns the raw OOP mysqli connection object.
     * Use this for your existing $stmt = $conn->prepare(...) calls.
     *
     * @return mysqli
     */
    public function getConnection(): mysqli {
        return $this->mysqli;
    }

    /**
     * Safely closes the database connection.
     */
    public function closeConnection(): void {
        if ($this->mysqli instanceof mysqli) {
            $this->mysqli->close();
        }
    }
    
    /**
     * Magic Method: Automatically close the connection when the Database object is destroyed
     */
    public function __destruct() {
        $this->closeConnection();
    }
}