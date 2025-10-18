<?php
// TEMPORARY DEBUG - TURN ON ALL ERRORS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

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
    error_log("=== API START ===");
    error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request URI: " . $_SERVER['REQUEST_URI']);

    // Define the project root path
    $projectRoot = dirname(__DIR__);
    error_log("Project root: " . $projectRoot);
    
    // Include database configuration
    $dbConfigPath = $projectRoot . '/config/database.php';
    error_log("Database config path: " . $dbConfigPath);
    
    if (!file_exists($dbConfigPath)) {
        throw new Exception('Database config file not found: ' . $dbConfigPath);
    }
    
    require_once $dbConfigPath;
    error_log("Database config loaded");
    
    // Check if Database class exists
    if (!class_exists('Database')) {
        throw new Exception('Database class not found after including config');
    }
    
    error_log("Creating Database instance...");
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify connection is MySQLi object
    if (!($db instanceof mysqli)) {
        throw new Exception('Database connection is not a MySQLi instance. Got: ' . gettype($db));
    }
    
    error_log("Database connected successfully");
    
    // Get the URL path
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];
    
    error_log("URI: " . $uri);
    error_log("Method: " . $method);
    
    // Adjust base path
    $base_path = '/library-management-system/api';
    $path = str_replace($base_path, '', $uri);
    
    error_log("Base path: " . $base_path);
    error_log("Path after base: " . $path);
    
    // ---- AUTH ROUTES ----
    if ($path === '/login' && $method === 'POST') {
        error_log("Routing to login");
        handleLogin($db);
        exit;
    }
    
    if ($path === '/register' && $method === 'POST') {
        error_log("Routing to register");
        handleRegister($db);
        exit;
    }
    
    // ---- BOOK ROUTES ----
    if ($path === '/books' && $method === 'GET') {
        error_log("Routing to get all books");
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
    
    // Handle file upload for book creation - THIS MUST COME FIRST
    if ($path === '/books' && $method === 'POST' && isset($_FILES['bookImage'])) {
        error_log("Routing to book upload with file");
        handleBookUpload($db);
        exit; // Stop further processing
    }
    
    // Handle regular book creation (without file)
    if ($path === '/books' && $method === 'POST') {
        error_log("Routing to regular book creation");
        
        // Get raw input
        $rawInput = file_get_contents("php://input");
        error_log("Raw input: " . $rawInput);
        
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON: ' . json_last_error_msg());
        }
        
        error_log("Decoded input: " . print_r($input, true));
        
        if (empty($input['title']) || empty($input['author'])) {
            json_response(['success' => false, 'message' => 'Title and Author are required'], 400);
        }
        
        $query = "INSERT INTO books (title, author, publishYear, ISBN, pages, img) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        error_log("SQL: " . $query);
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $db->error);
        }
        
        $title = $input['title'];
        $author = $input['author'];
        $publishYear = $input['publishYear'] ?? date('Y');
        $ISBN = $input['ISBN'] ?? '';
        $pages = $input['pages'] ?? null;
        $img = $input['img'] ?? '/images/default-book.jpg';
        
        error_log("Binding params: title=$title, author=$author, publishYear=$publishYear, ISBN=$ISBN, pages=$pages, img=$img");
        
        $stmt->bind_param(
            'ssisis', 
            $title,
            $author,
            $publishYear,
            $ISBN,
            $pages,
            $img
        );
        
        if ($stmt->execute()) {
            error_log("Book created successfully, ID: " . $stmt->insert_id);
            json_response([
                'success' => true,
                'message' => 'Book created successfully',
                'bookId' => $stmt->insert_id
            ]);
        } else {
            throw new Exception('Failed to create book: ' . $stmt->error);
        }
    }
    
    error_log("No route matched");
    // Default 404
    json_response(['success' => false, 'message' => 'Endpoint not found: ' . $path], 404);
    
} catch (Exception $e) {
    error_log("=== API ERROR ===");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile());
    error_log("Line: " . $e->getLine());
    error_log("Trace: " . $e->getTraceAsString());
    
    json_response([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
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
        error_log("=== FILE UPLOAD START ===");
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
            error_log("Created upload directory: " . $uploadDir);
        }

        // Get book data from form
        $bookDataJson = $_POST['bookData'] ?? '{}';
        error_log("Received bookData: " . $bookDataJson);
        
        $bookData = json_decode($bookDataJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid bookData JSON: ' . json_last_error_msg());
        }
        
        error_log("Parsed book data: " . print_r($bookData, true));
        
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
            error_log("File uploaded: " . $file['name'] . " (" . $file['size'] . " bytes)");
            
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
            
            error_log("Moving file to: " . $filePath);
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                $imagePath = '/images/' . $fileName;
                error_log("File moved successfully. Image path: " . $imagePath);
            } else {
                error_log("Failed to move uploaded file");
                json_response([
                    'success' => false,
                    'message' => 'Failed to upload image.'
                ], 500);
            }
        } else {
            $uploadError = $_FILES['bookImage']['error'] ?? 'No file';
            error_log("No file uploaded or upload error: " . $uploadError);
        }

        // Insert into database using MySQLi
        $query = "INSERT INTO books (title, publishYear, author, ISBN, pages, img) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        error_log("Executing SQL: " . $query);
        
        $stmt = $db->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $db->error);
        }
        
        $title = $bookData['title'];
        $author = $bookData['author'];
        $publishYear = intval($bookData['publishYear']);
        $ISBN = $bookData['ISBN'] ?? '';
        $pages = isset($bookData['pages']) ? intval($bookData['pages']) : null;
        
        error_log("Binding params: title=$title, author=$author, publishYear=$publishYear, ISBN=$ISBN, pages=$pages, img=$imagePath");
        
        $stmt->bind_param(
            'ssisis', 
            $title,
            $publishYear,
            $author,
            $ISBN,
            $pages,
            $imagePath
        );

        if ($stmt->execute()) {
            $bookId = $stmt->insert_id;
            error_log("Book inserted successfully. ID: " . $bookId);
            
            json_response([
                'success' => true,
                'message' => 'Book added successfully!',
                'bookId' => $bookId,
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