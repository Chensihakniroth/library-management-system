<?php
require_once __DIR__ . '/../models/Librarian.php';
require_once __DIR__ . '/../helpers/Response.php';

class LibrarianController {
    private $librarian;

    public function __construct($db) {
        $this->librarian = new Librarian($db);
    }

    public function get($id) {
        if (empty($id)) {
            Response::json(['message' => 'Librarian ID is required'], 400);
            return;
        }

        $info = $this->librarian->getById($id);
        if ($info) {
            // Remove password from response
            unset($info['password']);
            Response::json($info, 200);
        } else {
            Response::json(['message' => 'Librarian not found'], 404);
        }
    }

    public function update($id, $data) {
        if (empty($id)) {
            Response::json(['message' => 'Librarian ID is required'], 400);
            return;
        }

        // Simple update without file upload for now
        if ($this->librarian->updateProfile($id, $data)) {
            Response::json(['message' => 'Profile updated successfully'], 200);
        } else {
            Response::json(['message' => 'Update failed'], 400);
        }
    }
}
?>