<?php
define('ROOT_DIR', dirname(__DIR__));

require_once ROOT_DIR . '/config/database.php';
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

echo "=== Running Rider Availability Toggle TestFlow ===\n";

$controller = new RiderController();

// Simulate shifting a driver online (ID: 1, Availability: 1)
$mockInputData = [
    "driver_id" => 1,
    "is_available" => 1
];

$controller->toggleAvailability($mockInputData);