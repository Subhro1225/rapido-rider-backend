<?php
namespace App\Controllers;

use Config\Database;
use App\Core\Response;

class RiderController {
    
    public function login($data) {
        $mobile = $data['mobile'] ?? '';
        $password = $data['password'] ?? '';

        // 1. Core Validation
        if (empty($mobile) || empty($password)) {
            Response::json([
                "status" => "error",
                "message" => "Mobile and password fields are required."
            ], 400);
        }

        try {
            // 2. Fetch the active database connection instance
            $db = Database::getInstance();

            // 3. Query your real 'drivers' table
            $query = "SELECT * FROM drivers WHERE mobile = :mobile LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([':mobile' => $mobile]);
            $user = $stmt->fetch();

            // 4. Verify user credentials match
            // 4. Verify user credentials match safely
        if ($user && isset($user['password']) && $password === $user['password']) {
            Response::json([
                "status" => "success",
                "message" => "Authentication successful!",
                "user" => [
                    "id" => $user['id'],
                    "mobile" => $user['mobile']
                ]
            ]);
        } else {
                Response::json([
                    "status" => "error",
                    "message" => "Invalid mobile or password credentials."
                ], 401); // 401 means Unauthorized
            }

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database operation failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function toggleAvailability($data) {
        $driverId = $data['driver_id'] ?? '';
        // Expecting 1 for online, 0 for offline
        $isAvailable = isset($data['is_available']) ? (int)$data['is_available'] : null; 

        if (empty($driverId) || $isAvailable === null) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID and availability status are required."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // Update the specific driver's availability state
            $query = "UPDATE drivers SET is_available = :is_available WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':is_available' => $isAvailable,
                ':id' => $driverId
            ]);

            Response::json([
                "status" => "success",
                "message" => "Rider availability status updated successfully.",
                "current_status" => $isAvailable
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database update failed: " . $e->getMessage()
            ], 500);
        }
    }
}