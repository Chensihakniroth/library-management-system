<?php
require_once __DIR__.'/../models/Borrow.php';
require_once __DIR__.'/../helpers/Response.php';

class BorrowController {
    private $borrow;
    
    public function __construct($db){ 
        $this->borrow = new Borrow($db); 
    }

    public function borrowBook($data){
        // Validate required fields
        if (empty($data['book_id']) || empty($data['librarian_id'])) {
            Response::json(['message' => 'Book ID and Librarian ID are required'], 400);
            return;
        }

        $this->borrow->book_id = $data['book_id'];
        $this->borrow->librarian_id = $data['librarian_id'];
        $this->borrow->borrow_date = $data['borrow_date'] ?? date('Y-m-d H:i:s');
        
        if($this->borrow->borrowBook()) {
            Response::json(['message' => 'Book borrowed successfully'], 201);
        } else {
            Response::json(['message' => 'Failed to borrow book'], 400);
        }
    }

    public function returnBook($data){
        // Validate required fields
        if (empty($data['id'])) {
            Response::json(['message' => 'Borrow record ID is required'], 400);
            return;
        }

        $return_date = $data['return_date'] ?? date('Y-m-d H:i:s');
        
        if($this->borrow->returnBook($data['id'], $return_date)) {
            Response::json(['message' => 'Book returned successfully'], 200);
        } else {
            Response::json(['message' => 'Failed to return book'], 400);
        }
    }
}