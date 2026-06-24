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

// Instantiate using the precise namespace memory address found by our diagnostic
$controller = new \App\Controllers\RiderController();

echo "=== Running 5KM Proximity Geospatial Range Filter Test ===\n\n";

echo "--> Toggling Driver 1 to ONLINE state...\n";
$controller->toggleAvailability([
    "driver_id" => 1,
    "is_online" => 1
]);
echo "\n";

$mockDriverLocation = [
    "driver_id" => 1,
    "latitude" => "30.3165",  
    "longitude" => "78.0322"
];

echo "--> Polling for rides within a 5KM mathematical radius...\n";
$controller->getNearbyRides($mockDriverLocation);
echo "\n";