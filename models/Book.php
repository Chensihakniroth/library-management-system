<?php
class Book {
    private $conn;
    private $table = "books";

    public $id;
    public $title;
    public $author;
    public $status;
    public $img;
    public $created_at;
    public $publishYear;
    public $ISBN;
    public $pages;

    public function __construct($db) { 
        $this->conn = $db; 
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                 (title, author, status, img, publishYear, ISBN, pages) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssi", 
            $this->title, 
            $this->author, 
            $this->status, 
            $this->img, 
            $this->publishYear,
            $this->ISBN,
            $this->pages
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function getAll() {
        $query = "SELECT 
                    MIN(id) as id, 
                    title, 
                    author,
                    MAX(CASE WHEN img IS NOT NULL AND img != '' THEN img ELSE NULL END) as img,
                    publishYear,
                    ISBN,
                    pages,
                    COUNT(*) as total_copies,
                    SUM(CASE WHEN status = 'on_shelf' THEN 1 ELSE 0 END) as available_copies
                  FROM " . $this->table . " 
                  GROUP BY title, author, publishYear, ISBN, pages
                  ORDER BY title ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        $stmt->close();
        return $books;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $book = $result->fetch_assoc();
        $stmt->close();
        return $book;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                 SET title = ?, author = ?, status = ?, img = ?, 
                     publishYear = ?, ISBN = ?, pages = ? 
                 WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssssii", 
            $this->title, 
            $this->author, 
            $this->status, 
            $this->img, 
            $this->publishYear,
            $this->ISBN,
            $this->pages,
            $this->id
        );
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            return false;
        }
    }

    public function filterByStatus($status) {
        if ($status === 'on_shelf') {
            $query = "SELECT 
                        MIN(id) as id, 
                        title, 
                        author, 
                        img,
                        publishYear,
                        ISBN,
                        pages,
                        COUNT(*) as total_copies,
                        SUM(CASE WHEN status = 'on_shelf' THEN 1 ELSE 0 END) as available_copies
                      FROM " . $this->table . " 
                      GROUP BY title, author, img, publishYear, ISBN, pages
                      HAVING available_copies > 0
                      ORDER BY title ASC";
        } else {
            $query = "SELECT 
                        MIN(id) as id, 
                        title, 
                        author, 
                        img,
                        publishYear,
                        ISBN,
                        pages,
                        COUNT(*) as total_copies,
                        SUM(CASE WHEN status = 'on_shelf' THEN 1 ELSE 0 END) as available_copies
                      FROM " . $this->table . " 
                      GROUP BY title, author, img, publishYear, ISBN, pages
                      ORDER BY title ASC";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        $stmt->close();
        return $books;
    }

    public function searchByTitle($title) {
        $query = "SELECT 
                    MIN(id) as id, 
                    title, 
                    author, 
                    img,
                    publishYear,
                    ISBN,
                    pages,
                    COUNT(*) as total_copies,
                    SUM(CASE WHEN status = 'on_shelf' THEN 1 ELSE 0 END) as available_copies
                  FROM " . $this->table . " 
                  WHERE title LIKE ? 
                  GROUP BY title, author, img, publishYear, ISBN, pages
                  ORDER BY title ASC";
        
        $stmt = $this->conn->prepare($query);
        $searchTerm = "%" . $title . "%";
        $stmt->bind_param("s", $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        $stmt->close();
        return $books;
    }
}
?>