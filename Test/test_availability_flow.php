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

$controller = new RiderController();

echo "=== Case 1: Testing Valid Logged-In Driver (Mark Online) ===\n";
// NOTE: Make sure 'driver_id' matches an actual ID present in your drivers table
$validTestData = [
    "driver_id" => 1, 
    "is_available" => 0
];
$controller->toggleAvailability($validTestData);

echo "\n\n=== Case 2: Testing Non-Existent/Logged-Out Driver Guard ===\n";
// Testing with a fake ID to verify the system forces it offline
$invalidTestData = [
    "driver_id" => 99999, 
    "is_available" => 1
];
$controller->toggleAvailability($invalidTestData);
echo "\n";