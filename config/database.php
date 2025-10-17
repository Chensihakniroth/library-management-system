<?php
class Database {
    private $host = "localhost";
    private $db_name = "library_db";      // your database name
    private $username = "root";           // default Laragon user
    private $password = "";               // default Laragon password is empty
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "❌ Database connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}
