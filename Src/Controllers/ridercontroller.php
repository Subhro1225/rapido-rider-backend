<?php
namespace App\Controllers;

use Config\Database;
use App\Core\Response;

class RiderController {

    public function signup($data) {
        $name = $data['name'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($name) || empty($mobile) || empty($password)) {
            Response::json([
                "status" => "error",
                "message" => "All registration fields (name, mobile, password) are required."
            ], 400);
            return;
        }

        try {
            $db = Database::getInstance();

            // Check if the mobile number is already registered
            $checkQuery = "SELECT id FROM drivers WHERE mobile = :mobile LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':mobile' => $mobile]);
            
            if ($checkStmt->fetch()) {
                Response::json([
                    "status" => "error",
                    "message" => "A driver account with this mobile number already exists."
                ], 409);
                return;
            }

            // OPTIMIZED: Securely hash the password using Bcrypt before saving it
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new driver record into the database
            $insertQuery = "INSERT INTO drivers (name, mobile, password, is_available, created_at) 
                            VALUES (:name, :mobile, :password, 0, NOW())";
            
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                ':name' => $name,
                ':mobile' => $mobile,
                ':password' => $hashedPassword
            ]);

            Response::json([
                "status" => "success",
                "message" => "Driver registration completed successfully.",
                "driver_id" => $db->lastInsertId()
            ], 201);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Registration engine failure: " . $e->getMessage()
            ], 500);
        }
    }
    
    public function login($data) {

    $mobile = $data['mobile'] ?? '';
    $otp = $data['otp'] ?? '';

    // Validate input
    if (empty($mobile) || empty($otp)) {
        Response::json([
            "status" => "error",
            "message" => "Mobile number and OTP are required."
        ], 400);
        return;
    }

    try {

        $db = Database::getInstance();

        $query = "SELECT * FROM drivers WHERE mobile = :mobile LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':mobile' => $mobile
        ]);

        $driver = $stmt->fetch();

        if (!$driver) {
            Response::json([
                "status" => "error",
                "message" => "Driver not found."
            ], 404);
            return;
        }

        // Temporary OTP validation
        if ($otp !== "1234") {
            Response::json([
                "status" => "error",
                "message" => "Invalid OTP."
            ], 401);
            return;
        }

        Response::json([
            "status" => "success",
            "message" => "Login successful.",
            "driver" => [
                "id" => $driver['id'],
                "name" => $driver['name'],
                "mobile" => $driver['mobile']
            ]
        ]);

    } catch (\PDOException $e) {

        Response::json([
            "status" => "error",
            "message" => "Database error: " . $e->getMessage()
        ], 500);

    }
}
    public function toggleAvailability($data) {
        $driverId = $data['driver_id'] ?? '';
        $requestedState = $data['is_available'] ?? 0; // Default to 0 (false) if not specified

        if (empty($driverId)) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID parameter is required to update status."
            ], 400);
            return;
        }

        try {
            $db = Database::getInstance();

            // 1. VERIFY LOGGED-IN/ACTIVE STATUS: Check if driver exists in database first
            $checkQuery = "SELECT id FROM drivers WHERE id = :driver_id LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':driver_id' => $driverId]);
            $driverExists = $checkStmt->fetch();

            // If driver doesn't exist, they cannot be marked active. Force state to 0 (false).
            if (!$driverExists) {
                Response::json([
                    "status" => "error",
                    "message" => "Unauthorized state modification. Driver must be logged in/registered. Forcing status to offline.",
                    "is_available" => false
                ], 401);
                return;
            }

            // 2. Set state based on verification
            $finalState = $requestedState ? 1 : 0;

            $query = "UPDATE drivers SET is_available = :is_available, updated_at = NOW() WHERE id = :driver_id";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':is_available' => $finalState,
                ':driver_id' => $driverId
            ]);

            Response::json([
                "status" => "success",
                "message" => "Driver availability status updated successfully.",
                "driver_id" => $driverId,
                "is_available" => (bool)$finalState
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Availability engine tracking failure: " . $e->getMessage()
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
            return;
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Row-level lock checking to verify ride is still waiting
            $query = "SELECT ride_status FROM rides WHERE id = :ride_id FOR UPDATE";
            $stmt = $db->prepare($query);
            $stmt->execute([':ride_id' => $rideId]);
            $ride = $stmt->fetch();

            if (!$ride || $ride['ride_status'] !== 'waiting') {
                Response::json([
                    "status" => "error",
                    "message" => "Ride request is no longer available or has already been claimed."
                ], 409);
                $db->rollBack();
                return;
            }

            // 1. Update the ride status to accepted and link the driver
            $updateRide = "UPDATE rides SET ride_status = 'accepted', driver_id = :driver_id WHERE id = :ride_id";
            $rideStmt = $db->prepare($updateRide);
            $rideStmt->execute([
                ':driver_id' => $driverId,
                ':ride_id' => $rideId
            ]);

            // 2. AUTOMATION GATEWAY: Turn driver offline immediately so they disappear from open pooling queues
            $updateDriver = "UPDATE drivers SET is_available = 0 WHERE id = :driver_id";
            $driverStmt = $db->prepare($updateDriver);
            $driverStmt->execute([':driver_id' => $driverId]);

            $db->commit();
            
            Response::json([
                "status" => "success",
                "message" => "Ride successfully secured. Driver status automatically set to BUSY/OFFLINE."
            ]);

        } catch (\PDOException $e) {
            $db->rollBack();
            Response::json([
                "status" => "error",
                "message" => "Transaction failed: " . $e->getMessage()
            ], 500);
        }
    }

    public function completeRide($data) {
        if (!isset($data['ride_id'])) {
            Response::json(["status" => "error", "message" => "Missing ride ID"], 400);
            return;
        }

        $ride_id = $data['ride_id'];
        
        // Get the database connection
        $db = Database::getInstance(); 

        try {
            // 1. Check the current status in the database (PDO Syntax)
            $checkQuery = "SELECT ride_status FROM rides WHERE id = ?";
            $stmt = $db->prepare($checkQuery);
            $stmt->execute([$ride_id]); 
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$row) {
                Response::json(["status" => "error", "message" => "Ride not found in database."], 404);
                return;
            }
            
            $current_status = $row['ride_status'];

            // 2. If it is ALREADY completed, just tell the frontend "success"
            if ($current_status === 'completed') {
                Response::json(["status" => "success", "message" => "Ride was already completed."], 200);
                return;
            }

            // 3. Check if it's legally allowed to be completed
            if ($current_status !== 'in_progress' && $current_status !== 'started') {
                Response::json(["status" => "error", "message" => "Invalid state transition. Ride is currently: " . $current_status], 400);
                return;
            }

            // 4. Lock in the final completed status (PDO Syntax)
            $updateQuery = "UPDATE rides SET ride_status = 'completed' WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            
            if ($updateStmt->execute([$ride_id])) {
                Response::json(["status" => "success", "message" => "Ride completed successfully."], 200);
            } else {
                Response::json(["status" => "error", "message" => "Failed to update database."], 500);
            }
            
        } catch (\PDOException $e) {
            // Catch any unexpected database errors gracefully
            Response::json(["status" => "error", "message" => "Database error: " . $e->getMessage()], 500);
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

            $updateRideQuery = "UPDATE rides SET payment_status = 'success' WHERE id = :ride_id";
            $updateRideStmt = $db->prepare($updateRideQuery);
            $updateRideStmt->execute([':ride_id' => $rideId]);

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

    /**
     * Phase 3: Get Captain Profile
     */
    public function getDriverProfile($data) {
        $driverId = $data['driver_id'] ?? '';

        if (empty($driverId)) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID parameter is required."
            ], 400);
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT id, name, mobile, vehicle_number, is_available, average_rating 
                FROM drivers 
                WHERE id = :id
            ");
            $stmt->execute([':id' => $driverId]);
            $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($driver) {
                $driver['is_available'] = (bool)$driver['is_available'];
                Response::json([
                    "status" => "success", 
                    "data" => $driver
                ]);
            } else {
                Response::json([
                    "status" => "error", 
                    "message" => "Driver not found."
                ], 404);
            }
        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Phase 3: Get Ride History
     */
    public function getDriverRideHistory($data) {
        $driverId = $data['driver_id'] ?? '';

        if (empty($driverId)) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID parameter is required."
            ], 400);
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT 
                    r.id as ride_id, 
                    r.pickup_location, 
                    r.destination, 
                    r.distance_km, 
                    r.fare, 
                    r.ride_status, 
                    r.created_at as ride_date,
                    u.name as rider_name
                FROM rides r
                JOIN users u ON r.user_id = u.id
                WHERE r.driver_id = :driver_id 
                ORDER BY r.created_at DESC
            ");
            $stmt->execute([':driver_id' => $driverId]);
            $history = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::json([
                "status" => "success", 
                "data" => $history
            ]);
        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Phase 3: Submit Rating
     */
    public function submitRideRating($data) {
        $rideId = $data['ride_id'] ?? '';
        $driverId = $data['driver_id'] ?? '';
        $userId = $data['user_id'] ?? '';
        $rating = $data['rating'] ?? '';
        $comments = $data['comments'] ?? '';

        if (empty($rideId) || empty($driverId) || empty($userId) || empty($rating)) {
            Response::json([
                "status" => "error",
                "message" => "Missing required fields."
            ], 400);
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // 1. Insert rating
            $insertStmt = $db->prepare("
                INSERT INTO user_feedback (user_id, ride_id, driver_id, rating, comments) 
                VALUES (:user_id, :ride_id, :driver_id, :rating, :comments)
            ");
            $insertStmt->execute([
                ':user_id' => $userId, 
                ':ride_id' => $rideId, 
                ':driver_id' => $driverId, 
                ':rating' => $rating, 
                ':comments' => $comments
            ]);

            // 2. Update driver's average rating
            $updateStmt = $db->prepare("
                UPDATE drivers 
                SET average_rating = (
                    SELECT AVG(rating) 
                    FROM user_feedback 
                    WHERE driver_id = :driver_id_sub
                ) 
                WHERE id = :id
            ");
            $updateStmt->execute([
                ':driver_id_sub' => $driverId, 
                ':id' => $driverId
            ]);

            $db->commit();
            
            Response::json([
                "status" => "success", 
                "message" => "Rating submitted successfully."
            ]);
        } catch (\PDOException $e) {
            if (isset($db)) {
                $db->rollBack();
            }
            Response::json([
                "status" => "error",
                "message" => "Failed to submit rating: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Rider Analytics (Stats & Spending)
     */
    public function getRiderAnalytics($data) {
        $userId = $data['user_id'] ?? '';

        if (empty($userId)) {
            Response::json([
                "status" => "error",
                "message" => "User ID parameter is required."
            ], 400);
        }

        try {
            $db = Database::getInstance();
            $stmt = $db->prepare("
                SELECT 
                    COUNT(id) as total_rides_requested,
                    SUM(CASE WHEN ride_status = 'completed' THEN 1 ELSE 0 END) as completed_rides,
                    SUM(CASE WHEN ride_status = 'completed' THEN distance_km ELSE 0 END) as total_distance_km,
                    SUM(CASE WHEN ride_status = 'completed' THEN fare ELSE 0 END) as total_spent
                FROM rides 
                WHERE user_id = :user_id
            ");
            $stmt->execute([':user_id' => $userId]);
            $analytics = $stmt->fetch(\PDO::FETCH_ASSOC);

            $analytics['total_distance_km'] = (float)($analytics['total_distance_km'] ?? 0.00);
            $analytics['total_spent'] = (float)($analytics['total_spent'] ?? 0.00);
            $analytics['total_rides_requested'] = (int)$analytics['total_rides_requested'];
            $analytics['completed_rides'] = (int)$analytics['completed_rides'];

            Response::json([
                "status" => "success", 
                "data" => $analytics
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

    public function getNearbyRides($data) {
        $driverId = $data['driver_id'] ?? '';
        $driverLat = $data['latitude'] ?? '';
        $driverLng = $data['longitude'] ?? '';
        $radiusKm = 5.0; // Enforce strict 5km range threshold

        if (empty($driverId) || empty($driverLat) || empty($driverLng)) {
            Response::json([
                "status" => "error",
                "message" => "Driver ID, current latitude, and longitude are required for proximity pooling."
            ], 400);
            return;
        }

        try {
            $db = Database::getInstance();

            // 1. First verify driver is active and online
            $checkQuery = "SELECT is_available FROM drivers WHERE id = :driver_id LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':driver_id' => $driverId]);
            $driver = $checkStmt->fetch();

            if (!$driver || !$driver['is_available']) {
                Response::json([
                    "status" => "error",
                    "message" => "Driver must toggle availability status to AVAILABLE to poll nearby requests."
                ], 403);
                return;
            }

            // 2. MySQL Haversine Formula query to calculate distance using your exact table columns
            $query = "SELECT id, pickup_location, destination, fare, pickup_latitude, pickup_longitude,
                      (6371 * acos(
                          cos(radians(:driver_lat)) * cos(radians(pickup_latitude)) * cos(radians(pickup_longitude) - radians(:driver_lng)) + 
                          sin(radians(:driver_lat)) * sin(radians(pickup_latitude))
                      )) AS distance 
                      FROM rides 
                      WHERE ride_status = 'waiting' 
                      HAVING distance <= :radius 
                      ORDER BY distance ASC";

            $stmt = $db->prepare($query);
            $stmt->execute([
                ':driver_lat' => $driverLat,
                ':driver_lng' => $driverLng,
                ':radius' => $radiusKm
            ]);

            $rides = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            Response::json([
                "status" => "success",
                "message" => "Proximity scan complete. Found " . count($rides) . " ride requests within " . $radiusKm . "km.",
                "search_radius_km" => $radiusKm,
                "rides" => $rides
            ]);

        } catch (\PDOException $e) {
            Response::json([
                "status" => "error",
                "message" => "Geospatial engine calculation failure: " . $e->getMessage()
            ], 500);
        }
    }

    public function startRideWithOtp($data) {
        $rideId = $data['ride_id'] ?? '';
        $driverId = $data['driver_id'] ?? '';
        $inputOtp = $data['otp'] ?? '';


        if (empty($rideId) || empty($driverId) || empty($inputOtp)) {
            Response::json([
                "status" => "error",
                "message" => "Ride ID, Driver ID, and OTP verification code are required."
            ], 400);
            return;
        }

        try {
            $db = Database::getInstance();
            $db->beginTransaction();

            // Fetch the encrypted OTP string and current status
            $query = "SELECT otp, ride_status FROM rides WHERE id = :ride_id AND driver_id = :driver_id FOR UPDATE";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':ride_id' => $rideId,
                ':driver_id' => $driverId
            ]);
            $ride = $stmt->fetch();

            if (!$ride || $ride['ride_status'] !== 'accepted') {
                Response::json([
                    "status" => "error",
                    "message" => "Invalid lifecycle state. Ride must be 'accepted' to start verification."
                ], 400);
                $db->rollBack();
                return;
            }

            // 1. CRYPTOGRAPHIC VERIFICATION: Match the input plaintext against the database hash
                if ($inputOtp !== trim($ride['otp'])) {
                Response::json([
                    "status" => "error",
                    "message" => "Security verification failed: Invalid ride OTP code provided."
                ], 401);
                $db->rollBack();
                return;
            }

            // 2. State Transition: Advance the ride lifecycle state to 'started'
            $updateRide = "UPDATE rides SET ride_status = 'started' WHERE id = :ride_id";
            $rideStmt = $db->prepare($updateRide);
            $rideStmt->execute([':ride_id' => $rideId]);

            $db->commit();

            Response::json([
                "status" => "success",
                "message" => "OTP verified successfully. Journey state transitioned to STARTED."
            ]);

        } catch (\PDOException $e) {
            $db->rollBack();
            Response::json([
                "status" => "error",
                "message" => "Cryptographic validation engine failure: " . $e->getMessage()
            ], 500);
        }
    }
    
}