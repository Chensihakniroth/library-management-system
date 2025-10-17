<?php
class Borrow {
    private $conn;
    private $table = "borrows";

    public $id;
    public $book_id;
    public $librarian_id;
    public $borrow_date;
    public $return_date;
    public $status;

    public function __construct($db) { $this->conn = $db; }

    public function borrowBook() {
        $stmt = $this->conn->prepare("INSERT INTO {$this->table} (book_id,librarian_id,borrow_date,status) VALUES (:book_id,:librarian_id,:borrow_date,:status)");
        return $stmt->execute([
            ':book_id'=>$this->book_id,
            ':librarian_id'=>$this->librarian_id,
            ':borrow_date'=>$this->borrow_date,
            ':status'=>'borrowed'
        ]);
    }

    public function returnBook($id, $return_date) {
        $stmt = $this->conn->prepare("UPDATE {$this->table} SET status='returned', return_date=:return_date WHERE id=:id");
        return $stmt->execute([':id'=>$id,':return_date'=>$return_date]);
    }
}
?>
