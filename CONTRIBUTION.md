# Core Engineering Rules & Architecture Standards

To keep the codebase reliable, clean, and bug-free, everyone working on this project must strictly adhere to these core development rules.

---
# Method Reference Index (RiderController)

| Function Name | Description | Status Code Outputs |
| :--- | :--- | :--- |
| `signup($data)` | Registers a new driver profile with secure password encryption. | `201 Created` / `409 Conflict` |
| `login($data)` | Validates mobile credentials using secure hash matching. | `200 OK` / `401 Unauthorized` |
| `toggleAvailability($data)` | Switches driver status to online/offline queues. | `200 OK` / `401 Unauthorized` |
| `getAvailableRides()` | Fetches global feed of all unallocated ride bookings. | `200 OK` |
| `getNearbyRides($data)` | Feeds rides strictly within a 5KM sphere of driver GPS. | `200 OK` / `403 Forbidden` |
| `acceptRide($data)` | Secures a ride request using atomic transaction rows. | `200 OK` / `409 Conflict` |
| `startRideWithOtp($data)` | Unlocks ride state to `started` via cryptographic matches. | `200 OK` / `401 Unauthorized` |
| `completeRide($data)` | Finalizes journey status and toggles driver to available. | `200 OK` / `400 Bad Request` |
| `settleRidePayment($data)` | Processes fares securely into ledger tables. | `200 OK` / `409 Conflict` |
| `getDriverAnalytics($data)` | Generates trip counters and lifetime performance records. | `200 OK` |

---

## 🛡️ Rule 1: Rigid State Machine Sequence
The ride_status must transition strictly in chronological order. No exceptions. Any request attempting to skip a state must instantly return an HTTP 400 Bad Request or code validation failure:

[waiting] ➔ [accepted] ➔ [driver_arrived] ➔ [started] ➔ [completed]

* Guard Rule: A ride can never be marked as completed unless its current state is explicitly set to started.
* Payment Gate Rule: A passenger cannot pay until the driver has completed the ride from their side, which shifts the transaction state to completed.

---

## 🔒 Rule 2: Complete Concurrency Isolation
* All status mutation actions (especially Ride Acceptance) must be wrapped inside a secure, atomic database transaction context using $db->beginTransaction(), $db->commit(), and $db->rollBack().
* When querying a row to check its current status before altering it, you must append FOR UPDATE to the SQL string to securely lock that row in memory. This completely prevents race conditions where two drivers accept the same ride at the exact same microsecond.

---

## 🔑 Rule 3: Zero Plaintext Password Entry
* Plain-text passwords must never be matched or written directly to the database. 
* All authentication flows must use PHP’s native password_verify() framework checking against an encrypted Bcrypt hash database string.

---

## 📂 Rule 4: Isolated CLI Execution Support
* Every newly introduced controller method or config script must support terminal testing execution paths.
* Avoid absolute path configurations. Always utilize the dynamic ROOT_DIR environment calculations to keep path mapping compatible across both Unix and Windows setups:

define('ROOT_DIR', dirname(__DIR__));
$envPath = ROOT_DIR . '/.env';

---

## 💬 Rule 5: Normalized JSON API Schema Output
All server outputs must route through the central Response::json() helper to guarantee cross-system uniformity. Every single endpoint payload must contain an explicit tracking string status:

```json
{
    "status": "success/error",
    "message": "Explicit description text detailing exact execution result details."
}
```
---

## 🔒 Rule 6: Automated Availability Concurrency Lifecycle
* When a driver invokes `acceptRide()`, the system must instantly set `is_online = 0` inside the same database transaction block. This ensures they are immediately removed from open pooling visibility.
* When a driver invokes `completeRide()`, the system must automatically flip `is_online = 1` back to true, returning them safely to the available pooling queue. 
* Manual overrides to availability via `toggleAvailability()` are strictly forbidden unless the driver holds a verified active registration row in the database.

---

## 🗺️ Rule 7: Geospatial Coordinate Calculations
* Every ride request record must explicitly include valid `pickup_latitude` and `pickup_longitude` values utilizing decimal data types to prevent structural rounding errors.
* The matching pooling engine utilizes a standard spherical Haversine formula calculation. Changes to the core $6371\text{ km}$ Earth radius constant or the strict $5.0\text{ km}$ search threshold must pass architectural approval before deployment.