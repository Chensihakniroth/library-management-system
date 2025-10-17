<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/Librarian.php';

class AuthController {
    private $db;
    private $librarian;

    public function __construct() {
        $this->db = (new Database())->getConnection();
        $this->librarian = new Librarian($this->db);
    }

    // Register
    public function register($data) {
        // Validate required fields
        if (empty($data['name']) || empty($data['email']) || empty($data['password'])) {
            Response::json(['success' => false, 'message' => 'All fields are required'], 400);
            return;
        }

        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::json(['success' => false, 'message' => 'Invalid email format'], 400);
            return;
        }

        // Check if email already exists
        if ($this->librarian->getByEmail($data['email'])) {
            Response::json(['success' => false, 'message' => 'Email already exists'], 400);
            return;
        }

        // Set librarian data
        $this->librarian->name = $data['name'];
        $this->librarian->email = $data['email'];
        $this->librarian->password = password_hash($data['password'], PASSWORD_BCRYPT);

        // Create new librarian
        if ($this->librarian->create()) {
            Response::json([
                'success' => true, 
                'message' => 'Registration successful!'
            ], 201);
        } else {
            Response::json([
                'success' => false, 
                'message' => 'Registration failed. Please try again.'
            ], 500);
        }
    }

    // Login
    public function login($data) {
        // Validate required fields
        if (empty($data['email']) || empty($data['password'])) {
            Response::json(['success' => false, 'message' => 'Email and password are required'], 400);
            return;
        }

        $librarian = $this->librarian->getByEmail($data['email']);
        
        if ($librarian && password_verify($data['password'], $librarian['password'])) {
            // Generate simple token (you can implement JWT later)
            $token = bin2hex(random_bytes(16));
            Response::json([
                'success' => true,
                'token' => $token,
                'user' => [
                    'id' => $librarian['id'],
                    'name' => $librarian['name'],
                    'email' => $librarian['email'],
                    'profile_image' => $librarian['profile_image']
                ]
            ], 200);
        } else {
            Response::json([
                'success' => false, 
                'message' => 'Invalid email or password'
            ], 401);
        }
    }
}