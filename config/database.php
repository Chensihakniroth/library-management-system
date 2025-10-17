<?php
class Database {
    private $host = "localhost";
    private $db_name = "library_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        // Use MySQLi instead of PDO
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->db_name);
        
        // Check connection
        if ($this->conn->connect_error) {
            die("❌ MySQLi Connection failed: " . $this->conn->connect_error);
        }
        
        // Set charset
        $this->conn->set_charset("utf8mb4");
        
        return $this->conn;
    }
}