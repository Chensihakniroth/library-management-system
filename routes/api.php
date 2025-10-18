<?php
// Turn off error reporting for production but log errors
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ===============================
//  ROUTE FILE: routes/api.php
// ===============================

// Add CORS headers
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Simple response helper
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

try {
    // Define the project root path
    $projectRoot = dirname(__DIR__);
    
    // Include database configuration
    require_once $projectRoot . '/config/database.php';
    
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify connection is MySQLi object
    if (!($db instanceof mysqli)) {
        throw new Exception('Database connection is not a MySQLi instance');
    }
    
    // Get the URL path
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Adjust base path
    $base_path = '/library-management-system/api';
    $path = str_replace($base_path, '', $uri);
    
    // ---- AUTH ROUTES ----
    if ($path === '/login' && $method === 'POST') {
        handleLogin($db);
        exit;
    }
    
    if ($path === '/register' && $method === 'POST') {
        handleRegister($db);
        exit;
    }
    
    // ---- BOOK ROUTES ----
    if ($path === '/books' && $method === 'GET') {
        // Simple direct database query using MySQLi
        $query = "SELECT * FROM books";
        $result = $db->query($query);
        
        if ($result) {
            $books = [];
            while ($row = $result->fetch_assoc()) {
                $books[] = $row;
            }
            $result->free();
            
            json_response([
                'success' => true,
                'data' => $books,
                'count' => count($books)
            ]);
        } else {
            throw new Exception('Failed to fetch books: ' . $db->error);
        }
    }
    
    // Handle file upload for book creation
    if ($path === '/books' && $method === 'POST' && isset($_FILES['bookImage'])) {
        handleBookUpload($db);
    }
    
    // Handle regular book creation
    if ($path === '/books' && $method === 'POST') {
        $input = json_decode(file_get_contents("php://input"), true);
        
        if (empty($input['title']) || empty($input['author'])) {
            json_response(['success' => false, 'message' => 'Title and Author are required'], 400);
        }
        
        $query = "INSERT INTO books (title, author, publishYear, ISBN, page, img) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param(
            'ssisis', 
            $input['title'],
            $input['author'],
            $input['publishYear'] ?? date('Y'),
            $input['ISBN'] ?? '',
            $input['page'] ?? null,
            $input['img'] ?? '/images/default-book.jpg'
        );
        
        if ($stmt->execute()) {
            json_response([
                'success' => true,
                'message' => 'Book created successfully',
                'bookId' => $stmt->insert_id
            ]);
        } else {
            throw new Exception('Failed to create book: ' . $stmt->error);
        }
    }
    
    // Default 404
    json_response(['success' => false, 'message' => 'Endpoint not found: ' . $path], 404);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ], 500);
}

// ---- AUTH HANDLERS ----
function handleLogin($db) {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['email']) || empty($input['password'])) {
        json_response(['success' => false, 'message' => 'Email and password are required'], 400);
    }
    
    $email = $input['email'];
    $password = $input['password'];
    
    // Query the librarians table
    $query = "SELECT * FROM librarians WHERE email = ? AND password = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $email, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    $librarian = $result->fetch_assoc();
    
    if ($librarian) {
        json_response([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $librarian['id'],
                'name' => $librarian['name'],
                'email' => $librarian['email']
            ]
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Invalid email or password'], 401);
    }
}

function handleRegister($db) {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if (empty($input['name']) || empty($input['password']) || empty($input['email'])) {
        json_response(['success' => false, 'message' => 'Name, password, and email are required'], 400);
    }
    
    $name = $input['name'];
    $password = $input['password'];
    $email = $input['email'];
    
    // Check if librarian already exists
    $query = "SELECT id FROM librarians WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        json_response(['success' => false, 'message' => 'Email already exists'], 400);
    }
    
    // Insert new librarian
    $query = "INSERT INTO librarians (name, password, email) VALUES (?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->bind_param('sss', $name, $password, $email);
    
    if ($stmt->execute()) {
        json_response([
            'success' => true,
            'message' => 'Registration successful',
            'userId' => $stmt->insert_id
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Registration failed'], 500);
    }
}

function handleBookUpload($db) {
    $uploadDir = 'C:/laragon/www/images/';
    
    try {
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Get book data from form
        $bookData = json_decode($_POST['bookData'] ?? '{}', true);
        
        // Validate required fields
        if (empty($bookData['title']) || empty($bookData['author']) || empty($bookData['publishYear'])) {
            json_response([
                'success' => false,
                'message' => 'Title, Author, and Publish Year are required fields.'
            ], 400);
        }

        $imagePath = '/images/default-book.jpg';

        // Handle file upload
        if (isset($_FILES['bookImage']) && $_FILES['bookImage']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['bookImage'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes)) {
                json_response([
                    'success' => false,
                    'message' => 'Only JPG, JPEG, and PNG files are allowed.'
                ], 400);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $imagePath = '/images/' . $fileName;
            } else {
                json_response([
                    'success' => false,
                    'message' => 'Failed to upload image.'
                ], 500);
            }
        }

        // Insert into database using MySQLi
        $query = "INSERT INTO books (title, publishYear, author, ISBN, page, img) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $db->prepare($query);
        $stmt->bind_param(
            'ssisis', 
            $bookData['title'],
            $bookData['publishYear'],
            $bookData['author'],
            $bookData['ISBN'] ?? '',
            $bookData['page'] ?? null,
            $imagePath
        );

        if ($stmt->execute()) {
            json_response([
                'success' => true,
                'message' => 'Book added successfully!',
                'bookId' => $stmt->insert_id,
                'imagePath' => $imagePath
            ]);
        } else {
            throw new Exception('Failed to insert book: ' . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        json_response([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}
?>