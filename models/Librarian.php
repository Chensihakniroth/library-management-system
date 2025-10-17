<?php
class Librarian {
    private $conn;
    private $table = "librarians";
    public $id;
    public $name;
    public $email;
    public $password;
    public $profile_image;

    public function __construct($db) { $this->conn = $db; }

    public function create() {
        $stmt_check = $this->conn->prepare("SELECT id FROM {$this->table} WHERE email=:email");
        $stmt_check->execute([':email'=>$this->email]);
        if($stmt_check->rowCount() > 0) return false;

        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (name,email,password) VALUES (:name,:email,:password)");
        return $stmt->execute([
            ':name'=>$this->name,
            ':email'=>$this->email,
            ':password'=>$this->password
        ]);
    }

    public function getByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE email=:email");
        $stmt->execute([':email'=>$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateProfile($id, $data) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET name=:name, profile_image=:profile_image WHERE id=:id");
        return $stmt->execute([
            ':name'=>$data['name'],
            ':profile_image'=>$data['profile_image'],
            ':id'=>$id
        ]);
    }
    public function getById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id=:id");
    $stmt->execute([':id'=>$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

}
?>
