<?php
require_once __DIR__ . '/../config/database.php'; // go up from public/ to root folder

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✅ Database connected successfully!";
} else {
    echo "❌ Failed to connect to database!";
}
