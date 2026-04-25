<?php
class DBConn {
    public $conn;

    public function __construct() {
        $envPath = __DIR__ . '/.env';

        if (!file_exists($envPath)) {
            die("Critical Error: Database configuration (.env) is missing on the server.");
        }

        $env = [];
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Strip any hidden Windows carriage returns first!
            $line = str_replace(array("\r", "\n"), '', $line);
            $line = trim($line);
            
            if (empty($line) || strpos($line, '#') === 0) continue; 
            
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Safely strip outer quotes ONLY if they exist on both sides
                if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                    $value = $matches[2];
                }
                
                $env[$key] = $value;
            }
        }

        if (empty($env['DB_HOST'])) {
            die("Critical Error: Database variables not found or formatted incorrectly in .env file.");
        }

        // Suppress default warnings so we can handle them manually
        mysqli_report(MYSQLI_REPORT_OFF);

        // Connect using the parsed variables
        $this->conn = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'], $env['DB_NAME']);

        // --- DIAGNOSTIC ERROR HANDLER ---
        if ($this->conn->connect_error) {
            // This will print the EXACT reason MySQL is rejecting the connection
            die("<div style='font-family: sans-serif; padding: 20px; background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 8px; max-width: 600px; margin: 40px auto;'>
                    <h2 style='margin-top:0;'>Diagnostic DB Error</h2>
                    <p><strong>MySQL said:</strong> " . $this->conn->connect_error . "</p>
                    <p><strong>Error Code:</strong> " . $this->conn->connect_errno . "</p>
                    <hr style='border: 0; border-top: 1px solid #fca5a5; margin: 15px 0;'>
                    <p style='font-size: 13px;'>If the error says 'Access Denied', your password or username has a typo. If it says 'Connection Refused' or 'Network is unreachable', your Host IP is blocking you.</p>
                 </div>");
        }
        
        $this->conn->set_charset("utf8mb4");
    }
}