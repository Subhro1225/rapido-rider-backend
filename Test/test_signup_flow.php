<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/Config/database.php';
require_once ROOT_DIR . '/Src/Core/response.php';
require_once ROOT_DIR . '/Src/Controllers/ridercontroller.php';

// Load environmental variables safely
$envPath = ROOT_DIR . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Controllers\RiderController;

echo "=== Running Rider Signup Registration Engine TestFlow ===\n";

$controller = new RiderController();

// Simulate registering a brand new driver account
$mockInputData = [
    "name" => "Test Driver",
    "mobile" => "9876543210", // Change this if testing multiple times
    "password" => "securepass123"
];

$controller->signup($mockInputData);