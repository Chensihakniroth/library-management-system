<?php
class Librarian {
    private $conn;
    private $table = "librarians";
    public $id;
    public $name;
    public $email;
    public $password;
    public $profile_image;

    public function __construct($db) { 
        $this->conn = $db; 
    }

    public function create() {
        // Check if email already exists
        $existingLibrarian = $this->getByEmail($this->email);
        if ($existingLibrarian) {
            return false;
        }

        // Insert new librarian using MySQLi
        $query = "INSERT INTO " . $this->table . " (name, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters for MySQLi
        $stmt->bind_param("sss", $this->name, $this->email, $this->password);

        // Execute query
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getByEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateProfile($id, $data) {
        $query = "UPDATE " . $this->table . " SET name = ?, profile_image = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bind_param("ssi", $data['name'], $data['profile_image'], $id);

        return $stmt->execute();
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}
?>