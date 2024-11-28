<?php

class DatabaseConnection {
    private $host = 'localhost';     
    private $dbname = 'elearning_platform';  
    private $username = 'root';     
    private $password = '';     
    
    public $pdo; 

    public function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4", 
                $this->username, 
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}


try {
    $database = new DatabaseConnection();
    $pdo = $database->pdo;
} catch (Exception $e) {
    
    die("Database connection error: " . $e->getMessage());
}
?>