<?php
// Setup autoloader mapping for testing
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        require_once __DIR__ . '/../src/' . str_replace('App\\', '', $class) . '.php';
    }
});

use App\Core\Response;

echo "=== Running Request & Response API Engine Test ===\n";

$mockData = [
    "status" => "success",
    "message" => "Request and Response modules are working seamlessly!"
];

// Output clean JSON
Response::json($mockData);