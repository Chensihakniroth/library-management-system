<?php
require_once __DIR__.'/../models/Borrow.php';
require_once __DIR__.'/../helpers/Response.php';

class BorrowController {
    private $borrow;
    public function __construct($db){ $this->borrow = new Borrow($db); }

    public function borrowBook($data){
        $this->borrow->book_id = $data['book_id'];
        $this->borrow->librarian_id = $data['librarian_id'];
        $this->borrow->borrow_date = $data['borrow_date'];
        if($this->borrow->borrowBook()) Response::json(['message'=>'Borrowed'],201);
        else Response::json(['message'=>'Failed'],400);
    }

    public function returnBook($data){
        if($this->borrow->returnBook($data['id'],return_date: $data['return_date'])) Response::json(['message'=>'Returned'],200);
        else Response::json(['message'=>'Failed'],400);
    }
}
?>
