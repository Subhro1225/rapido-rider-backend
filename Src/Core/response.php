<?php
namespace App\Core;

class Response {
    public static function json($data, $statusCode = 200) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($statusCode);
        }
        echo json_encode($data);
        exit; // Stop execution immediately after sending data
    }
}