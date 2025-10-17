<?php
// helpers/AuthMiddleware.php
require_once __DIR__ . '/Response.php';

class AuthMiddleware {
    public static function check() {
        $headers = getallheaders();
        if(!isset($headers['Authorization'])) {
            Response::json(['message'=>'Unauthorized'], 401);
        }

        $token = str_replace('Bearer ', '', $headers['Authorization']);
        // Normally verify JWT here. For demo, simple check:
        if(empty($token)) {
            Response::json(['message'=>'Invalid token'], 401);
        }
        return true;
    }
}
?>
