<?php
class DatabaseConnection {
    private $host = 'localhost';
    private $dbname = 'elearning_platform';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function __construct() {
        // Create connection
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        // Check connection
        if ($this->conn->connect_error) {
            error_log("Database Connection Error: " . $this->conn->connect_error);
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
    }

    // Sanitization method
    public function sanitizeInput($input) {
        // Trim whitespace
        $input = trim($input);
        
        // Remove backslashes
        $input = stripslashes($input);
        
        // Escape special characters
        $input = $this->conn->real_escape_string($input);
        
        // Convert special characters to HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }

    public function getConnection() {
        return $this->conn;
    }

    public function closeConnection() {
        $this->conn->close();
    }
}

try {
    $database = new DatabaseConnection();
    $conn = $database->getConnection();
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>