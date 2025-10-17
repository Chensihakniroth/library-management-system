<?php
// ===============================
//  ROUTE FILE: routes/api.php
// ===============================

// Include dependencies
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/BookController.php';
require_once __DIR__ . '/../controllers/BorrowController.php';
require_once __DIR__ . '/../controllers/LibrarianController.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/AuthMiddleware.php';

// Database connection
$db = (new Database())->getConnection();

// Initialize controllers
$auth = new AuthController();
$book = new BookController($db);
$borrow = new BorrowController($db);
$librarian = new LibrarianController($db);

// Get the URL path (e.g., /api/books)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Adjust base path to match your project folder name
// Example: if your project folder in htdocs is "library-management-system"
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
if ($path === '/books' && $method === 'GET') {
    $book->getAll();
    exit;
}

if (preg_match('#^/books/(\d+)$#', $path, $matches)) {
    $id = $matches[1];
    if ($method === 'GET') $book->getById($id);
    elseif ($method === 'PUT') $book->update(array_merge($data, ['id' => $id]));
    elseif ($method === 'DELETE') $book->delete($id);
    exit;
}

if ($path === '/books' && $method === 'POST') {
    $book->create($data);
    exit;
}

if ($path === '/books/filter' && $method === 'GET') {
    $status = $_GET['status'] ?? '';
    $book->filterByStatus($status);
    exit;
}

if ($path === '/books/search' && $method === 'GET') {
    $title = $_GET['title'] ?? '';
    $book->searchByTitle($title);
    exit;
}

// ---- BORROW ROUTES ----
if ($path === '/borrow' && $method === 'POST') {
    $borrow->borrowBook($data);
    exit;
}

if ($path === '/return' && $method === 'POST') {
    $borrow->returnBook($data);
    exit;
}

// ---- LIBRARIAN ROUTES ----
if ($path === '/librarian' && $method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $librarian->get($id);
    } else {
        Response::json(['message' => 'Librarian ID required'], 400);
    }
    exit;
}

if ($path === '/librarian' && $method === 'POST') {
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
