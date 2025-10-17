<?php
class Book {
    private $conn;
    private $table = "books";

    public $id;
    public $title;
    public $author;
    public $status;

    public function __construct($db) { $this->conn = $db; }

    public function create() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (title,author,status) VALUES (:title,:author,:status)");
        return $stmt->execute([':title'=>$this->title,':author'=>$this->author,':status'=>$this->status]);
    }

    public function getAll() {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table}");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET title=:title,author=:author,status=:status WHERE id=:id");
        return $stmt->execute([':title'=>$this->title,':author'=>$this->author,':status'=>$this->status,':id'=>$this->id]);
    }

    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM {$this->table} WHERE id=:id");
        return $stmt->execute([':id'=>$id]);
    }

    public function filterByStatus($status) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE status=:status");
        $stmt->execute([':status'=>$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchByTitle($title) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->table} WHERE title LIKE :title");
        $stmt->execute([':title'=>"%$title%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
