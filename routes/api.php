<?php
// ===============================
//  ROUTE FILE: routes/api.php
// ===============================

// Add CORS headers to allow Angular frontend
header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Define the project root path
$projectRoot = dirname(__DIR__);

// Include dependencies with correct paths - FIXED PATHS
require_once $projectRoot . '/config/database.php';
require_once $projectRoot . '/controllers/AuthController.php';

// Check if other controller files exist before including
if (file_exists($projectRoot . '/controllers/BookController.php')) {
    require_once $projectRoot . '/controllers/BookController.php';
}

if (file_exists($projectRoot . '/controllers/BorrowController.php')) {
    require_once $projectRoot . '/controllers/BorrowController.php';
}

require_once $projectRoot . '/controllers/LibrarianController.php';

require_once $projectRoot . '/helpers/Response.php';
require_once $projectRoot . '/helpers/AuthMiddleware.php';

// Database connection
$db = (new Database())->getConnection();

// Initialize controllers - ONLY INITIALIZE IF THEY EXIST
$auth = new AuthController();

// Initialize other controllers only if their classes exist
$book = class_exists('BookController') ? new BookController($db) : null;
$borrow = class_exists('BorrowController') ? new BorrowController($db) : null;
$librarian = new LibrarianController($db);

// Get the URL path (e.g., /api/books)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Adjust base path to match your project folder name
$base_path = '/library-management-system/api';
$path = str_replace($base_path, '', $uri);

// ==================================
//         ROUTE DEFINITIONS
// ==================================

// ---- AUTH ROUTES ----
if ($path === '/register' && $method === 'POST') {
    $auth->register($data);
    exit;
}

if ($path === '/login' && $method === 'POST') {
    $auth->login($data);
    exit;
}

// ---- BOOK ROUTES ----
if ($path === '/books' && $method === 'GET' && $book) {
    $book->getAll();
    exit;
}

if (preg_match('#^/books/(\d+)$#', $path, $matches) && $book) {
    $id = $matches[1];
    if ($method === 'GET') $book->getById($id);
    elseif ($method === 'PUT') $book->update(array_merge($data, ['id' => $id]));
    elseif ($method === 'DELETE') $book->delete($id);
    exit;
}

if ($path === '/books' && $method === 'POST' && $book) {
    $book->create($data);
    exit;
}

if ($path === '/books/filter' && $method === 'GET' && $book) {
    $status = $_GET['status'] ?? '';
    $book->filterByStatus($status);
    exit;
}

if ($path === '/books/search' && $method === 'GET' && $book) {
    $title = $_GET['title'] ?? '';
    $book->searchByTitle($title);
    exit;
}

// ---- BORROW ROUTES ----
if ($path === '/borrow' && $method === 'POST' && $borrow) {
    $borrow->borrowBook($data);
    exit;
}

if ($path === '/return' && $method === 'POST' && $borrow) {
    $borrow->returnBook($data);
    exit;
}

// ---- LIBRARIAN ROUTES ----
if ($path === '/librarian' && $method === 'GET' && $librarian) {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $librarian->get($id);
    } else {
        Response::json(['message' => 'Librarian ID required'], 400);
    }
    exit;
}

if ($path === '/librarian' && $method === 'POST' && $librarian) {
    // For file uploads, use $_POST instead of $data
    $id = $_POST['id'] ?? null;
    if ($id) {
        $librarian->update($id, $_POST);
    } else {
        Response::json(['message' => 'Librarian ID required'], 400);
    }
    exit;
}

// ---- DEFAULT 404 ----
Response::json(['message' => 'Route not found', 'path' => $path], 404);