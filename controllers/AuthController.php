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
        $this->librarian->name = $data['name'];
        $this->librarian->email = $data['email'];
        $this->librarian->password = password_hash($data['password'], PASSWORD_BCRYPT);

        if($this->librarian->create()) {
            http_response_code()::json(['message'=>'Register success'], 201);
        } else {
            http_response_code()::json(['message'=>'Email already exists'], 400);
        }
    }

    // Login
    public function login($data) {
        $librarian = $this->librarian->getByEmail($data['email']);
        if($librarian && password_verify($data['password'], $librarian['password'])) {
            // Generate JWT token (example)
            $token = bin2hex(random_bytes(16));
            http_response_code()::json(['token'=>$token], 200);
        } else {
            http_response_code()::json(['message'=>'Invalid credentials'], 401);
        }
    }
}
