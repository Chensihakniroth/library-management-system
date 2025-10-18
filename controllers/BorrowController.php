<?php
class BookController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($data) {
        // Validate required fields
        if (empty($data['title']) || empty($data['author']) || empty($data['publishYear'])) {
            Response::json([
                'success' => false, 
                'message' => 'Title, Author, and Publish Year are required'
            ], 400);
            return;
        }

        // Sanitize and validate inputs
        $title = trim($data['title']);
        $author = trim($data['author']);
        $publishYear = intval($data['publishYear']);
        $ISBN = isset($data['ISBN']) ? trim($data['ISBN']) : '';
        $pages = isset($data['pages']) ? intval($data['pages']) : null;
        $img = isset($data['img']) ? trim($data['img']) : '/images/default-book.jpg';

        // Additional validation
        if (strlen($title) > 255) {
            Response::json(['success' => false, 'message' => 'Title too long'], 400);
            return;
        }

        if (strlen($author) > 100) {
            Response::json(['success' => false, 'message' => 'Author name too long'], 400);
            return;
        }

        $currentYear = date('Y');
        if ($publishYear < 1000 || $publishYear > $currentYear) {
            Response::json(['success' => false, 'message' => 'Invalid publish year'], 400);
            return;
        }

        if ($pages !== null && $pages < 1) {
            Response::json(['success' => false, 'message' => 'Invalid page count'], 400);
            return;
        }

        try {
            // Use parameterized query to prevent SQL injection
            $query = "INSERT INTO books (title, author, publishYear, ISBN, pages, img) 
                      VALUES (:title, :author, :publishYear, :ISBN, :pages, :img)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':author', $author);
            $stmt->bindParam(':publishYear', $publishYear, PDO::PARAM_INT);
            $stmt->bindParam(':ISBN', $ISBN);
            $stmt->bindParam(':pages', $pages, PDO::PARAM_INT);
            $stmt->bindParam(':img', $img);
            
            if ($stmt->execute()) {
                Response::json([
                    'success' => true,
                    'message' => 'Book added successfully',
                    'bookId' => $this->db->lastInsertId()
                ], 201);
            } else {
                Response::json([
                    'success' => false, 
                    'message' => 'Failed to add book to database'
                ], 500);
            }
        } catch (PDOException $e) {
            // Log the error (don't expose database details to client)
            error_log("Database error in BookController::create: " . $e->getMessage());
            
            Response::json([
                'success' => false, 
                'message' => 'Database error occurred'
            ], 500);
        }
    }

    public function getAll() {
    try {
        $query = "SELECT * FROM books ORDER BY id DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Check what's actually in the database
        error_log("Books found: " . count($books));
        foreach ($books as $book) {
            error_log("Book: " . print_r($book, true));
        }
        
        Response::json([
            'success' => true, 
            'data' => $books,
            'count' => count($books) // Add count for debugging
        ]);
    } catch (PDOException $e) {
        error_log("Database error in BookController::getAll: " . $e->getMessage());
        Response::json([
            'success' => false, 
            'message' => 'Failed to retrieve books: ' . $e->getMessage()
        ], 500);
    }
}

    public function getById($id) {
        try {
            $query = "SELECT * FROM books WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $book = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($book) {
                Response::json(['success' => true, 'data' => $book]);
            } else {
                Response::json(['success' => false, 'message' => 'Book not found'], 404);
            }
        } catch (PDOException $e) {
            error_log("Database error in BookController::getById: " . $e->getMessage());
            Response::json(['success' => false, 'message' => 'Failed to retrieve book'], 500);
        }
    }

    public function update($data) {
        // Similar validation as create method
        if (empty($data['id']) || empty($data['title']) || empty($data['author']) || empty($data['publishYear'])) {
            Response::json(['success' => false, 'message' => 'ID, Title, Author, and Publish Year are required'], 400);
            return;
        }

        // Sanitize inputs (similar to create method)
        $id = intval($data['id']);
        $title = trim($data['title']);
        $author = trim($data['author']);
        $publishYear = intval($data['publishYear']);
        $ISBN = isset($data['ISBN']) ? trim($data['ISBN']) : '';
        $pages = isset($data['pages']) ? intval($data['pages']) : null;
        $img = isset($data['img']) ? trim($data['img']) : '/images/default-book.jpg';

        try {
            $query = "UPDATE books SET title = :title, author = :author, publishYear = :publishYear, 
                      ISBN = :ISBN, pages = :pages, img = :img WHERE id = :id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':author', $author);
            $stmt->bindParam(':publishYear', $publishYear, PDO::PARAM_INT);
            $stmt->bindParam(':ISBN', $ISBN);
            $stmt->bindParam(':pages', $pages, PDO::PARAM_INT);
            $stmt->bindParam(':img', $img);
            
            if ($stmt->execute()) {
                Response::json(['success' => true, 'message' => 'Book updated successfully']);
            } else {
                Response::json(['success' => false, 'message' => 'Failed to update book'], 500);
            }
        } catch (PDOException $e) {
            error_log("Database error in BookController::update: " . $e->getMessage());
            Response::json(['success' => false, 'message' => 'Database error occurred'], 500);
        }
    }

    public function delete($id) {
        try {
            $query = "DELETE FROM books WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                Response::json(['success' => true, 'message' => 'Book deleted successfully']);
            } else {
                Response::json(['success' => false, 'message' => 'Failed to delete book'], 500);
            }
        } catch (PDOException $e) {
            error_log("Database error in BookController::delete: " . $e->getMessage());
            Response::json(['success' => false, 'message' => 'Database error occurred'], 500);
        }
    }

    public function filterByStatus($status) {
        try {
            $query = "SELECT * FROM books WHERE status = :status ORDER BY id DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'data' => $books]);
        } catch (PDOException $e) {
            error_log("Database error in BookController::filterByStatus: " . $e->getMessage());
            Response::json(['success' => false, 'message' => 'Failed to filter books'], 500);
        }
    }

    public function searchByTitle($title) {
        try {
            $searchTerm = '%' . $title . '%';
            $query = "SELECT * FROM books WHERE title LIKE :title ORDER BY id DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':title', $searchTerm);
            $stmt->execute();
            $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            Response::json(['success' => true, 'data' => $books]);
        } catch (PDOException $e) {
            error_log("Database error in BookController::searchByTitle: " . $e->getMessage());
            Response::json(['success' => false, 'message' => 'Failed to search books'], 500);
        }
    }
}
?>