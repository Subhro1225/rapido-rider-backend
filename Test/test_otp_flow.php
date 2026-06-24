<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('ROOT_DIR', dirname(__DIR__));

// 1. Environmental Variable Sync Layer
$envPath = ROOT_DIR . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// 2. Dependancy Mapping Vectors
require_once ROOT_DIR . '/Config/database.php';
require_once ROOT_DIR . '/Src/Core/response.php';
require_once ROOT_DIR . '/Src/Controllers/ridercontroller.php';

$controller = new \App\Controllers\RiderController();

echo "=== Running One-Time Password Cryptographic Verification Test ===\n\n";

$mockPayload = [
    "ride_id" => 5, 
    "driver_id" => 1,
    "otp" => "1234" 
];

echo "--> Submitting plaintext '1234' code for decryption match check...\n";
$controller->startRideWithOtp($mockPayload);
echo "\n";