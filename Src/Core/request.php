<?php
namespace App\Core;

class Request {
    // Get the clean URL path (e.g., /api/user/login)
    public static function getUri() {
        return explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    // Get the HTTP method (GET, POST, etc.)
    public static function getMethod() {
        return $_SERVER['REQUEST_METHOD'];
    }

    // Parse incoming JSON body sent by the frontend teammate
    public static function getJsonData() {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        return $data ?? [];
    }
}