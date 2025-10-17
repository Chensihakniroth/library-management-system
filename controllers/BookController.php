<?php
require_once __DIR__.'/../models/Book.php';

class BookController {
    private $book;
    public function __construct($db) { $this->book = new Book($db); }

    public function create($data){
        $this->book->title = $data['title'];
        $this->book->author = $data['author'];
        $this->book->status = $data['status'] ?? 'on_shelf';
        $this->book->img = $data['img'] ?? '';
        $this->book->publishYear = $data['publishYear'] ?? null;
        $this->book->ISBN = $data['isbn'] ?? '';
        $this->book->pages = $data['pages'] ?? null;
        
        if($this->book->create()) {
            http_response_code(201);
            echo json_encode(['success' => true, 'message' => 'Book added']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to add book']);
        }
    }

    public function uploadWithImage() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['success' => false, 'message' => 'Method not allowed']);
                return;
            }

            // Get form data
            $title = $_POST['title'] ?? '';
            $author = $_POST['author'] ?? '';
            $publishYear = $_POST['publishYear'] ?? null;
            $bookCreateAt = $_POST['bookCreateAt'] ?? null;
            $isbn = $_POST['isbn'] ?? '';
            $pages = $_POST['pages'] ?? null;

            // Validate required fields
            if (empty($title) || empty($author)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Title and Author are required']);
                return;
            }

            // Handle file upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/library-management-system/uploads/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $file = $_FILES['image'];
                $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9\.]/", "_", $file['name']);
                $targetPath = $uploadDir . $fileName;

                $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $fileType = mime_content_type($file['tmp_name']);
                
                if (!in_array($fileType, $allowedTypes)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Only JPG, JPEG, PNG & GIF files are allowed']);
                    return;
                }

                if ($file['size'] > 5 * 1024 * 1024) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                    return;
                }

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $imagePath = '/uploads/' . $fileName;
                } else {
                    http_response_code(500);
                    echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
                    return;
                }
            }

            // Create book
            $this->book->title = $title;
            $this->book->author = $author;
            $this->book->status = 'on_shelf';
            $this->book->img = $imagePath;
            $this->book->publishYear = $publishYear;
            $this->book->ISBN = $isbn;
            $this->book->pages = $pages;

            if ($this->book->create()) {
                http_response_code(201);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Book added successfully',
                    'data' => [
                        'title' => $title,
                        'author' => $author,
                        'imagePath' => $imagePath
                    ]
                ]);
            } else {
                if ($imagePath && file_exists($_SERVER['DOCUMENT_ROOT'] . $imagePath)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $imagePath);
                }
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to add book to database']);
            }

        } catch (Exception $e) {
            error_log('Upload error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    }

    public function getAll(){ 
        $books = $this->book->getAll();
        echo json_encode(['success' => true, 'data' => $books]);
    }
    
    public function getById($id){ 
        $book = $this->book->getById($id);
        if ($book) {
            echo json_encode(['success' => true, 'data' => $book]);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Book not found']);
        }
    }
    
    public function update($data){
        $this->book->id = $data['id'];
        $this->book->title = $data['title'];
        $this->book->author = $data['author'];
        $this->book->status = $data['status'];
        $this->book->img = $data['img'] ?? '';
        $this->book->publishYear = $data['publishYear'] ?? null;
        $this->book->ISBN = $data['isbn'] ?? '';
        $this->book->pages = $data['pages'] ?? null;
        
        if($this->book->update()) {
            echo json_encode(['success' => true, 'message' => 'Book updated']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to update book']);
        }
    }
    
    public function delete($id){
        if($this->book->delete($id)) {
            echo json_encode(['success' => true, 'message' => 'Book deleted']);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Failed to delete book']);
        }
    }

    public function filterByStatus($status){ 
        $books = $this->book->filterByStatus($status);
        echo json_encode(['success' => true, 'data' => $books]);
    }
    
    public function searchByTitle($title){ 
        $books = $this->book->searchByTitle($title);
        echo json_encode(['success' => true, 'data' => $books]);
    }
}
?>