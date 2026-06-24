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

echo "=== Running Sequential Ride Lifecycle Test Suite ===\n\n";

// Mock operational payload configuration
// Ensure ride_id matches an active ride record currently set to 'started' in your database
$mockPayload = [
    "ride_id" => 1,
    "driver_id" => 1
];

echo "--> Executing Trip Finalization State Transition...\n";
// Call your specific completeRide method instead of the generic updateRideStatus
$controller->completeRide($mockPayload);
echo "\n";