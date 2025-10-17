<?php
require_once __DIR__.'/../models/Book.php';
require_once __DIR__.'/../helpers/Response.php';

class BookController {
    private $book;
    public function __construct($db) { $this->book = new Book($db); }

    public function create($data){
        $this->book->title = $data['title'];
        $this->book->author = $data['author'];
        $this->book->status = $data['status'] ?? 'on_shelf';
        if($this->book->create()) Response::json(['message'=>'Book added'],201);
        else Response::json(['message'=>'Failed'],400);
    }

    public function getAll(){ Response::json($this->book->getAll()); }
    public function getById($id){ Response::json($this->book->getById($id)); }
    public function update($data){
        $this->book->id = $data['id'];
        $this->book->title = $data['title'];
        $this->book->author = $data['author'];
        $this->book->status = $data['status'];
        if($this->book->update()) Response::json(['message'=>'Updated'],200);
        else Response::json(['message'=>'Failed'],400);
    }
    public function delete($id){
        if($this->book->delete($id)) Response::json(['message'=>'Deleted'],200);
        else Response::json(['message'=>'Failed'],400);
    }

    public function filterByStatus($status){ Response::json($this->book->filterByStatus($status)); }
    public function searchByTitle($title){ Response::json($this->book->searchByTitle($title)); }
}
?>
