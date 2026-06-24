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

    public function getAvailableRides() {
        try {
            $db = Database::getInstance();

            // Query to find all rides currently waiting for a driver allocation
            $query = "SELECT id, user_id, pickup_location, destination, fare, ride_status 
                      FROM rides 
                      WHERE ride_status = 'waiting' 
                      ORDER BY id DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $rides = $stmt->fetchAll();

            if (!empty($rides)) {
                Response::json([
                    "status" => "success",
                    "count" => count($rides),
                    "rides" => $rides
                ]);
            } else {
                Response::json([
                    "status" => "success",
                    "count" => 0,
                    "message" => "No available ride requests at the moment.",
                    "rides" => []
                ]);
            }

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database fetch failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function acceptRide($data) {
        $rideId = $data['ride_id'] ?? '';
        $driverId = $data['driver_id'] ?? '';

        if (empty($rideId) || empty($driverId)) {
            Response::json([
                "status" => "error",
                "message" => "Ride ID and Driver ID parameters are required."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // First, verify the ride is still 'waiting' so two drivers can't accept the same ride
            $checkQuery = "SELECT ride_status FROM rides WHERE id = :ride_id LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':ride_id' => $rideId]);
            $ride = $checkStmt->fetch();

            if (!$ride || $ride['ride_status'] !== 'waiting') {
                Response::json([
                    "status" => "error",
                    "message" => "This ride request is no longer available or has already been accepted."
                ], 409); // 409 Conflict
                return;
            }

            // Update the ride status and assign the driver_id
            $query = "UPDATE rides 
                      SET ride_status = 'accepted', driver_id = :driver_id 
                      WHERE id = :ride_id AND ride_status = 'waiting'";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':driver_id' => $driverId,
                ':ride_id' => $rideId
            ]);

            Response::json([
                "status" => "success",
                "message" => "Ride successfully accepted and assigned to driver.",
                "ride_id" => $rideId,
                "driver_id" => $driverId
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database update failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function updateRideStatus($data) {
        $rideId = $data['ride_id'] ?? '';
        $nextStatus = $data['next_status'] ?? ''; // Expecting: 'driver_arrived', 'started', or 'completed'

        $allowedStatuses = ['driver_arrived', 'started', 'completed'];

        if (empty($rideId) || !in_array($nextStatus, $allowedStatuses)) {
            Response::json([
                "status" => "error",
                "message" => "Invalid target status or missing Ride ID."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // Fetch the current status to validate state machine rules
            $checkQuery = "SELECT ride_status FROM rides WHERE id = :ride_id LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':ride_id' => $rideId]);
            $ride = $checkStmt->fetch();

            if (!$ride) {
                Response::json([
                    "status" => "error",
                    "message" => "Ride transaction records not found."
                ], 404);
                return;
            }

            $currentStatus = $ride['ride_status'];

            // Enforce sequential business rules
            if ($nextStatus === 'driver_arrived' && $currentStatus !== 'accepted') {
                Response::json(["status" => "error", "message" => "Cannot mark arrived unless trip is accepted."], 400);
                return;
            }
            if ($nextStatus === 'started' && $currentStatus !== 'driver_arrived') {
                Response::json(["status" => "error", "message" => "Cannot start ride before driver arrives."], 400);
                return;
            }
            if ($nextStatus === 'completed' && $currentStatus !== 'started') {
                Response::json(["status" => "error", "message" => "Cannot complete a ride that hasn't started."], 400);
                return;
            }

            // Execute the valid state update
            $query = "UPDATE rides SET ride_status = :next_status WHERE id = :ride_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':next_status' => $nextStatus,
                ':ride_id' => $rideId
            ]);

            Response::json([
                "status" => "success",
                "message" => "Ride state advanced to tracking phase: " . $nextStatus,
                "ride_id" => $rideId,
                "ride_status" => $nextStatus
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Lifecycle mutation failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function settleRidePayment($data) {
        $rideId = $data['ride_id'] ?? '';
        $paymentMethod = $data['payment_method'] ?? 'cash'; // Default to cash if not specified

        if (empty($rideId)) {
            Response::json([
                "status" => "error",
                "message" => "Ride ID parameter is required for payment processing."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // 1. Verify the ride exists and is fully completed
            $rideQuery = "SELECT id, fare, ride_status FROM rides WHERE id = :ride_id LIMIT 1";
            $rideStmt = $db->prepare($rideQuery);
            $rideStmt->execute([':ride_id' => $rideId]);
            $ride = $rideStmt->fetch();

            if (!$ride) {
                Response::json(["status" => "error", "message" => "Ride record not found."], 404);
                return;
            }

            if ($ride['ride_status'] !== 'completed') {
                Response::json([
                    "status" => "error", 
                    "message" => "Payment processing rejected. Ride state must be 'completed' first."
                ], 400);
                return;
            }

            // 2. Check if a payment transaction has already been logged for this ride
            $payCheckQuery = "SELECT id FROM payments WHERE ride_id = :ride_id LIMIT 1";
            $payCheckStmt = $db->prepare($payCheckQuery);
            $payCheckStmt->execute([':ride_id' => $rideId]);
            
            if ($payCheckStmt->fetch()) {
                Response::json(["status" => "error", "message" => "Payment for this ride has already been settled."], 409);
                return;
            }

            // 3. Process the payout injection into the payments ledger
            $insertQuery = "INSERT INTO payments (ride_id, amount, payment_status, payment_method, created_at) 
                            VALUES (:ride_id, :amount, 'success', :payment_method, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                ':ride_id' => $rideId,
                ':amount' => $ride['fare'],
                ':payment_method' => $paymentMethod
            ]);

            Response::json([
                "status" => "success",
                "message" => "Payment transaction settled successfully.",
                "ride_id" => $rideId,
                "earnings_realized" => $ride['fare'],
                "payment_status" => "success"
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Payment transaction execution failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function getDriverAnalytics($data) {
        $driverId = $data['driver_id'] ?? '';

        if (empty($driverId)) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID parameter is required for analytics generation."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // Run an optimized aggregate join query to pull total trips and financial metrics
            $query = "SELECT 
                        COUNT(r.id) as total_completed_trips,
                        IFNULL(SUM(p.amount), 0.00) as total_lifetime_earnings
                      FROM rides r
                      INNER JOIN payments p ON r.id = p.ride_id
                      WHERE r.driver_id = :driver_id AND r.ride_status = 'completed'";
            
            $stmt = $db->prepare($query);
            $stmt->execute([':driver_id' => $driverId]);
            $analytics = $stmt->fetch();

            Response::json([
                "status" => "success",
                "driver_id" => $driverId,
                "performance_metrics" => [
                    "completed_trips" => (int)$analytics['total_completed_trips'],
                    "lifetime_earnings" => (float)$analytics['total_lifetime_earnings'],
                    "currency" => "INR"
                ]
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Analytics aggregation failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function updateLocation($data) {
        $driverId = $data['driver_id'] ?? '';
        $latitude = $data['latitude'] ?? '';
        $longitude = $data['longitude'] ?? '';

        if (empty($driverId) || empty($latitude) || empty($longitude)) {
            Response::json([
                "status" => "error",
                "message" => "Missing parameters. Driver ID, latitude, and longitude are required."
            ], 400);
        }

        try {
            $db = Database::getInstance();

            // Update the geospatial data for the target driver
            $query = "UPDATE drivers 
                      SET latitude = :latitude, longitude = :longitude, updated_at = NOW() 
                      WHERE id = :driver_id";
            
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':latitude' => $latitude,
                ':longitude' => $longitude,
                ':driver_id' => $driverId
            ]);

            Response::json([
                "status" => "success",
                "message" => "Geospatial telemetry updated successfully.",
                "driver_id" => $driverId,
                "coordinates" => [
                    "latitude" => $latitude,
                    "longitude" => $longitude
                ]
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Telemetry write failed: " . $e->getMessage()
            ], 500);
        }
    }
    
}