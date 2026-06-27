<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Tell the browser it's okay to share data across different ports (CORS)
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/Config/env.php';
require_once __DIR__ . '/Config/database.php';
require_once __DIR__ . '/Src/Core/response.php';
require_once __DIR__ . '/Src/Controllers/ridercontroller.php';

use App\Controllers\RiderController;

// Handle preflight browser security checks gently
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Read the order ticket parameter coming from app.js (?route=...)
$route = isset($_GET['route']) ? trim($_GET['route'], " /") : '';

// 3. Catch the raw JSON envelope data sent by the frontend
$inputData = json_decode(file_get_contents("php://input"), true) ?? [];

$controller = new RiderController();

$requestData = array_merge($_GET, $inputData);

// 4. The Switch Case: Decide what cooking station handles the request
switch ($route) {

    case 'api/driver/signup':
        $controller->signup($inputData);
        break;

    case 'api/driver/login':
        $controller->login($inputData);
        break;

    case 'api/rides/available':
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $controller = new \App\Controllers\RiderController();
            $controller->getAvailableRides();
            exit;
        }
        break;
    
    case 'api/driver/status':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->toggleAvailability($data);
            exit;
        }
        break;

    case 'api/rides/accept':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->acceptRide($data);
            exit;
        }
        break;

    case 'api/rides/start':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->startRideWithOtp($data);
            exit;
        }
        break;

    case 'api/rides/complete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            // Pointing to your controller's complete method
            $controller->completeRide($data); 
            exit;
        }
        break;

    case 'get_driver_profile':
        $controller->getDriverProfile($inputData);
        break;

    case 'get_driver_history':
        $controller->getDriverRideHistory($inputData);
        break;

    case 'submit_rating':
        $controller->submitRideRating($inputData);
        break;

    case 'get_rider_analytics':
        $controller->getRiderAnalytics($inputData);
        break;

    case 'api/rides/confirm_payment':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->settleRidePayment($data);
            exit;
        }
        break;

    case 'api/driver/earnings':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->getDriverEarnings($data);
            exit;
        }
        break;

    case 'api/rides/settle':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $controller = new \App\Controllers\RiderController();
            $controller->settleRidePayment($data);
            exit;
        }
        break;

    default:
        http_response_code(404);

        echo json_encode([
            "status" => "error",
            "message" => "Route not found."
        ]);
}