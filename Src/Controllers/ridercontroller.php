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

            // 3. Query your real 'users' table
            $query = "SELECT * FROM users WHERE mobile = :mobile LIMIT 1";
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
}