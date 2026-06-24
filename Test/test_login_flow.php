<?php
// 1. Explicitly define our absolute project root directory
define('ROOT_DIR', dirname(__DIR__));

// 2. Directly import the exact files we need for this test case
require_once ROOT_DIR . '/config/database.php';
require_once ROOT_DIR . '/Src/core/response.php'; // using lowercase 'core' as per your original file layout
require_once ROOT_DIR . '/Src/Controllers/ridercontroller.php';

// 3. Mini .env loader to securely fetch your database name ('rapido')
$envPath = ROOT_DIR . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
} else {
    die("❌ Error: .env file missing at root directory!\n");
}

// 4. Use the fully qualified class names directly
echo "=== Running Rider Login Controller TestFlow ===\n";

$controller = new \App\Controllers\RiderController();

// Safe dummy data for our terminal run
$mockInputData = [
    "mobile" => "1234567890",
    "password" => "Example1"
];

$controller->login($mockInputData);