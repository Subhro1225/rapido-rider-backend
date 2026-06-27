# Core Engineering Rules & Architecture Standards

To keep the codebase reliable, clean, and bug-free, everyone working on this project must strictly follow these rules.

---

## Method Reference Index (RiderController)

| Function Name | Description | HTTP Status Outputs |
| :--- | :--- | :--- |
| `signup($data)` | Registers a new driver with Bcrypt password, vehicle type, and plate number. | `201 Created` / `409 Conflict` |
| `login($data)` | Validates mobile + OTP, returns `driver.id` for frontend session. | `200 OK` / `401 Unauthorized` |
| `toggleAvailability($data)` | Switches driver online/offline. Requires verified driver ID. | `200 OK` / `403 Forbidden` |
| `getAvailableRides()` | Returns all rides with `waiting` status (unallocated global feed). | `200 OK` |
| `getNearbyRides($data)` | Returns rides within 5KM of driver's GPS using Haversine formula. | `200 OK` / `403 Forbidden` |
| `acceptRide($data)` | Atomically claims a ride with row-level lock. Sets driver `is_online = 0`. | `200 OK` / `409 Conflict` |
| `startRideWithOtp($data)` | Validates passenger OTP hash and transitions ride to `started`. | `200 OK` / `401 Unauthorized` |
| `completeRide($data)` | Finalizes ride to `completed`. Resets driver `is_online = 1`. | `200 OK` / `400 Bad Request` |
| `settleRidePayment($data)` | Writes payment record to ledger. Guards against missing `ride_id` and `payment_method`. | `200 OK` / `409 Conflict` |
| `getDriverProfile($data)` | Returns driver name, `vehicle_type`, `plate_number`, and rating. | `200 OK` / `404 Not Found` |
| `getDriverRideHistory($data)` | Returns paginated list of completed rides for history panel. | `200 OK` |
| `getDriverEarnings($data)` | Returns today's earnings, total rides, wallet balance, and Mon–Sun weekly breakdown. | `200 OK` |
| `submitRideRating($data)` | Saves passenger rating for a completed ride. | `200 OK` / `400 Bad Request` |
| `getRiderAnalytics($data)` | Lifetime trip counters and performance metrics. | `200 OK` |
| `updateLocation($data)` | Updates driver's GPS latitude/longitude in real time. | `200 OK` |

---

## 🛡️ Rule 1: Rigid State Machine Sequence

Ride status must transition strictly in this order. Any attempt to skip a state returns `HTTP 400`:

```
[waiting] → [accepted] → [driver_arrived] → [started] → [completed]
```

- A ride cannot be marked `completed` unless its current status is `started`.
- Payment cannot be settled until the ride status is `completed`.

---

## 🔒 Rule 2: Atomic Concurrency Isolation

- All status mutations (especially `acceptRide`) must use `$db->beginTransaction()`, `$db->commit()`, and `$db->rollBack()`.
- Any SELECT before a status UPDATE must append `FOR UPDATE` to lock the row and prevent two drivers claiming the same ride simultaneously.

---

## 🔑 Rule 3: Zero Plaintext Passwords

- Passwords are never stored or compared in plain text.
- Registration uses `password_hash($password, PASSWORD_BCRYPT)`.
- Login uses `password_verify($input, $storedHash)`.
- The `password` column in `drivers` must be `VARCHAR(255)` minimum to store Bcrypt hashes.

---

## 📂 Rule 4: Portable Path Configuration

Every script must use dynamic root resolution — no hardcoded absolute paths:

```php
define('ROOT_DIR', dirname(__DIR__));
$envPath = ROOT_DIR . '/.env';
```

This keeps path mapping compatible across Windows (XAMPP) and Unix environments.

---

## 💬 Rule 5: Normalized JSON API Output

Every endpoint must return a response through `Response::json()`. The payload must always include a `status` key:

```json
{
    "status": "success",
    "message": "Descriptive result message.",
    "data": {}
}
```

Never `echo` raw JSON or return plain strings from a controller method.

---

## 🔒 Rule 6: Automated Availability Lifecycle

- `acceptRide()` must set `is_online = 0` inside the same transaction — immediately removing the driver from the available pool.
- `completeRide()` must set `is_online = 1` — returning the driver to the pool automatically.
- `toggleAvailability()` is only permitted for drivers with a verified registration row in the database.

---

## 🗺️ Rule 7: Geospatial Coordinate Standards

- All ride records must store `pickup_latitude` and `pickup_longitude` as `DECIMAL(10,8)` / `DECIMAL(11,8)` types to avoid rounding errors.
- Proximity filtering uses the Haversine formula with Earth radius = 6371 km and a strict 5.0 km threshold.
- Changes to the radius constant or distance threshold require architectural approval.

---

## 🖥️ Rule 8: Frontend ↔ Backend Contract

- The frontend (`app.js`) must always save `driver_id` to `localStorage` after login or registration before navigating to the dashboard.
- Every API call that requires driver identity must read `localStorage.getItem("current_driver_id")` — never rely on session state alone.
- `API_BASE_URL` must be declared as a top-level constant in `app.js` and used consistently across all fetch calls.
- The fare collection widget must be populated with live ride data before being shown — never rely on HTML default values.
