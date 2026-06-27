# Rapido Rider Backend — Architecture Overview

A transaction-safe, state-machine-driven PHP backend that manages the full real-time lifecycle of a ride-hailing driver application. Built on vanilla PHP with PDO, it exposes a clean REST-style API consumed by a vanilla JS frontend.

---

## 🛠️ Core Architecture & Design Patterns

- **Singleton Database Provider:** A single centralized PDO instance is shared across all controller calls via `Database::getInstance()`, preventing redundant connections and memory leaks.
- **ACID-Compliant State Machine:** Every ride status transition (`waiting → accepted → driver_arrived → started → completed`) is enforced with atomic database transactions (`beginTransaction`, `commit`, `rollBack`) and row-level locking (`FOR UPDATE`) to eliminate race conditions when two drivers attempt to claim the same ride simultaneously.
- **Centralized JSON Response Layer:** All API outputs are routed through `Response::json()` to guarantee a uniform `{ status, message, data }` envelope across every endpoint.
- **CLI Test Suite:** Every controller method has a corresponding PHP test script runnable directly from the terminal without needing a browser or web server.

---

## 📂 Repository Directory Layout

```text
rapido-rider-backend/
├── Config/
│   └── database.php                    # Singleton PDO connector & .env reader
├── Src/
│   ├── Controllers/
│   │   └── ridercontroller.php         # All driver operational & transactional methods
│   └── Core/
│       ├── request.php                 # HTTP request capturing utility
│       ├── response.php                # Centralized JSON response normalizer
│       └── router.php                  # Route gateway
├── Test/
│   ├── test_signup_flow.php            # Bcrypt registration simulation
│   ├── test_login_flow.php             # OTP authentication engine test
│   ├── test_accept_flow.php            # Atomic concurrent ride acceptance test
│   ├── test_api_flow.php               # API routing execution tester
│   ├── test_range_flow.php             # 5KM Haversine proximity filter test
│   ├── test_availability_flow.php      # Driver online/offline toggle test
│   ├── test_db.php                     # PDO connectivity check
│   ├── test_driver_analytics_flow.php  # Cross-table metrics generation test
│   ├── test_lifecycle_flow.php         # Ride state step validator
│   ├── test_location_flow.php          # GPS coordinate update test
│   ├── test_payment_flow.php           # Payment settlement injection test
│   ├── test_polling_flow.php           # Ride queue scanner test
│   └── test_otp_flow.php               # Hashed OTP verification test
├── Sql/
│   └── rapido.sql                      # Database schema & structural backups
├── public/
│   ├── assets/                         # Static frontend assets
│   └── .htaccess                       # Apache rewrite rules
├── index.php                           # API entry point & route dispatcher
├── app.js                              # Frontend JS — all API calls & UI logic
├── index.html                          # Frontend HTML shell
├── .env                                # Local environment credentials (not committed)
├── env.sample                          # Credential template
├── .gitignore
├── SETUP.md                            # Environment setup guide
└── CONTRIBUTING.md                     # Engineering rules & coding standards
```

---

## 📊 Database Schema

| Table | Purpose |
| :--- | :--- |
| `drivers` | Stores driver credentials, vehicle info (`vehicle_type`, `plate_number`), real-time GPS coordinates, availability state (`is_available`, `is_online`), and average rating |
| `rides` | Tracks full ride lifecycle — passenger details, pickup/dropoff coordinates, fare, assigned driver, and sequential status (`waiting → accepted → driver_arrived → started → completed`) |
| `payments` | Immutable financial ledger — records payment method, amount, and settlement status per completed ride |

### Required Schema Columns (run once if upgrading from an older schema)
```sql
ALTER TABLE drivers
  ADD COLUMN vehicle_type VARCHAR(30) DEFAULT 'bike',
  ADD COLUMN plate_number VARCHAR(20) DEFAULT '',
  MODIFY COLUMN password VARCHAR(255) NOT NULL;
```

---

## 🌐 API Route Reference

All requests go to `index.php?route=<endpoint>`

| Method | Route | Controller Method | Description |
| :--- | :--- | :--- | :--- |
| POST | `api/driver/signup` | `signup()` | Register new driver with vehicle info & Bcrypt password |
| POST | `api/driver/login` | `login()` | OTP-based login, returns `driver.id` |
| POST | `api/driver/status` | `toggleAvailability()` | Toggle driver online/offline |
| POST | `api/driver/earnings` | `getDriverEarnings()` | Today's fare, ride count, wallet total, weekly chart |
| GET  | `api/rides/available` | `getAvailableRides()` | Fetch all unallocated rides |
| POST | `api/rides/accept` | `acceptRide()` | Atomic ride claim with row-level lock |
| POST | `api/rides/start` | `startRideWithOtp()` | Validate passenger OTP and start ride |
| POST | `api/rides/complete` | `completeRide()` | Mark ride completed, flip driver back to available |
| POST | `api/rides/settle` | `settleRidePayment()` | Write payment record to ledger |
| POST | `get_driver_profile` | `getDriverProfile()` | Fetch driver name, vehicle, rating |
| POST | `get_driver_history` | `getDriverRideHistory()` | Fetch completed ride history list |
| POST | `submit_rating` | `submitRideRating()` | Submit passenger rating for a ride |
| POST | `get_rider_analytics` | `getRiderAnalytics()` | Lifetime trip counters and performance stats |

---

## 🚀 Engineering Progress

### Phase 1 — Core Architecture
- [x] Relational database schema setup
- [x] Singleton PDO database provider
- [x] CLI test framework

### Phase 2 — Transactional Ride Lifecycle
- [x] Bcrypt driver authentication
- [x] Live ride polling engine
- [x] Atomic ride acceptance with race-condition protection
- [x] Sequential ride state transitions (arrived → started → completed)

### Phase 3 — Financial & Analytics
- [x] Payment settlement ledger engine
- [x] Driver earnings analytics (today, weekly, wallet)
- [x] Live GPS coordinate refresh tracking

### Phase 4 — Production Hardening & Frontend Integration
- [x] `vehicle_type` and `plate_number` added to signup and driver profile
- [x] `getDriverEarnings()` method returning today/weekly/wallet breakdown
- [x] `driver_id` correctly persisted to `localStorage` after registration (race condition fixed)
- [x] `API_BASE_URL` constant defined — fixes broken rating and analytics calls
- [x] Duplicate `fetchDriverProfile` function removed from frontend
- [x] Fare collection widget populated with real ride payout on completion
- [x] All hardcoded dummy values removed from HTML (420 rides, ₹1240 wallet, ₹154 fare)
- [x] Earnings and ride history loaded on login, registration, and session restore
- [x] `password` column widened to `VARCHAR(255)` to fit Bcrypt hashes
- [x] `settleRidePayment()` null-guarded for missing `ride_id` and `payment_method`

---

## 📄 Documentation
1. [Local Environment Setup](SETUP.md)
2. [Engineering Rules & Contribution Standards](CONTRIBUTING.md)
