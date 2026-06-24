# Rapido Rider Backend Architecture

A high-performance, transaction-safe, and decoupled vanilla PHP backend core designed to manage the real-time lifecycle of a ride-hailing system. This module isolates the entire transactional matrix of a driver application using a lightweight command-line verification framework.

---

## 🛠️ Core Architecture & Design Patterns
* **Singleton Database Provider:** Enforces a single, centralized database instance using a PDO wrapper to optimize connection reusability and completely prevent memory leaks.
* **ACID-Compliant State Machine:** Leverages atomic database transactions (`beginTransaction`, `commit`, `rollBack`) paired with row-level concurrency locking (`FOR UPDATE`) to eliminate multi-driver race conditions during ride acceptance.
* **Decoupled CLI Engine:** Designed with a zero-dependency environment context, allowing pure functional execution and unit tracking entirely through local terminal scripts.

## 📂 Repository Directory Layout
```text
rapido-rider-backend/
├── Config/
│   └── database.php                # Singleton PDO DB connector & .env reader
├── public/
│   ├── assets/                     # Static frontend/public assets
│   └── .htaccess                   # Apache server rewrite & configuration rules
├── Sql/
│   └── rapido.sql                  # Database schema exports & structural backups
├── Src/
│   ├── Controllers/
│   │   └── ridercontroller.php     # Core driver operational & transactional methods
│   └── Core/
│       ├── request.php             # Dynamic HTTP request capturing utility
│       ├── response.php            # Centralized JSON response normalization tool
│       └── router.php              # Centralized route handling and core gateway
├── Test/
│   ├── test_signup_flow.php        # Simulates secure Bcrypt new account registration
│   ├── test_accept_flow.php        # CLI atomic transaction ride claim script
│   ├── test_api_flow.php           # Local API routing execution tester
│   ├── test_range_flow.php         # Evaluates 5KM geospatial proximity range filters using Haversine
│   ├── test_availability_flow.php  # CLI driver availability toggle test script
│   ├── test_db.php                 # Direct PDO connectivity validation check
│   ├── test_driver_analytics_flow.php # Cross-table metrics generation script
│   ├── test_lifecycle_flow.php     # CLI active ride state step validator
│   ├── test_location_flow.php      # CLI geospatial update telemetry tester
│   ├── test_login_flow.php         # CLI authentication testing engine
│   ├── test_payment_flow.php       # CLI transaction settlement injection tool
│   ├── test_polling_flow.php       # CLI ride availability queue scanner
│   └── test_otp_flow.php           # verifies the enctypted otp 
├── .env                            # Active local environmental credentials
├── .gitignore                      # Git path tracking exclusions
├── env.sample                      # Template configuration example
├── CONTRIBUTING.md                 # Strict engineering guidelines & coding rules
└── SETUP.md                        # Environment initialization instructions
```
---

## 📊 Database Schema Blueprint
The underlying persistence layer relies on optimized MySQL tables with structural relationships and indexing:

* **`drivers`**: Manages authentication tokens, real-time online availability states, and live geospatial coordinate streaming.
* **`rides`**: Tracks ride pricing, active driver assignment matrix, and the sequential lifecycle states (`waiting`, `accepted`, `driver_arrived`, `started`, `completed`).
* **`payments`**: Acts as a financial ledger capturing immutable records linked strictly to finalized rides.

---

## 🚀 Production Optimizations & Security
* **Cryptographic Authentication:** Replaced insecure plain-text database comparisons with industry-standard **Bcrypt cryptographic hashing** via native PHP validation.
* **High-Frequency Query Indexing:** Applied composite B-Tree database indexes (`idx_ride_status`, `idx_driver_status`) in MySQL to scale up live ride polling queues and eliminate heavy full-table scans.

---

## 📌 System Engineering Progress Checkpoints

### Phase 1: Core Architecture Foundations
- [x] Milestone 1: Relational Database Normalization Schema Setup
- [x] Milestone 2: Singleton Architecture Implementation for Centralized Database Access Control
- [x] Milestone 3: Isolated CLI Verification Engine & Continuous Local Test Framework

### Phase 2: Transactional & Lifecycle Logic
- [x] Milestone 4: Parameterized Rider Authentication Controller Logic (Bcrypt Validation)
- [x] Milestone 5: Live Ride-Request Polling Engine Logic (Fetching available trips)
- [x] Milestone 6: Atomic Ride Acceptance & Driver Allocation State Matrix (Race-Condition Proof)
- [x] Milestone 7: Sequential Ride Lifecycle State Transitions (Arrived, Started, Completed)

### Phase 3: Financial Settlement & Analytics
- [x] Milestone 8: Ride Earnings Realization & Payment Settlement Engine (State-Locked)
- [x] Milestone 9: Cross-Table Driver Earnings & Completed Trips Analytics Engine
- [x] Milestone 10: Live Geospatial Coordinate Refresh Tracking Engine

## 🚀 Phase 4: Production Core Enhancements & Geospatial Engine

In this development cycle, the backend architecture was upgraded to transition from a basic structural prototype to a production-grade, state-synchronized ride-hailing core. This phase introduced strict state machine rules, identity validation guards, and coordinate-based proximity math.

### 📊 Engineering Change Matrix

| Feature Module | Database Mutations | Core Engine Architecture Changes | Validation Test Suite Component |
| :--- | :--- | :--- | :--- |
| **1. Secure Driver Account Registration** | Synced standard entity tracking attributes to the `drivers` schema interface. | Implemented `signup()` inside `RiderController` using secure hashing filters for credential encryption. | `Test/test_signup_flow.php` |
| **2. Active Availability Verification Guards** | `ALTER TABLE drivers ADD COLUMN is_online TINYINT(1) NOT NULL DEFAULT 0 AFTER password;` | Updated `toggleAvailability()` to enforce authentication checks, restricting status mutations to verified driver accounts. | `Test/test_availability_flow.php` |
| **3. Automated Ride Lifecycle Sync** | Native state tracking modifications applied dynamically to active `rides` state machines. | Injected automated transactional logic into `acceptRide()` (sets driver `is_online = 0`) and `completeRide()` (resets driver `is_online = 1`). | `Test/test_lifecycle_flow.php` |
| **4. 5KM Geospatial Proximity Filtering** | `ALTER TABLE rides ADD COLUMN pickup_latitude DECIMAL(10, 8) NULL AFTER destination, ADD COLUMN pickup_longitude DECIMAL(11, 8) NULL AFTER pickup_latitude;` | Built `getNearbyRides()` utilizing a spherical geometry Haversine formula calculation query restricted to a strict 5.0km search threshold. | `Test/test_range_flow.php` |

### 🛠️ Key Architectural Resolutions

* **Automated State Synchronization:** Implemented an automated state transition cycle based on the ride lifecycle. The second a driver accepts a ride, they are marked busy (`is_online = 0`) to decouple them from open pooling queues. Once the trip transitions to completed, the system auto-resets their availability state back to open (`is_online = 1`).
* **Geospatial Range Calculations:** Integrated the mathematical **Haversine Formula** within the native PDO layer to evaluate distances dynamically on a spherical Earth surface using a fixed radius constant of $6371\text{ km}$. This optimizes matching logic by strictly filtering incoming requests to a $5.0\text{ km}$ bubble around the driver's streaming telemetry point.
* **Namespace Memory Integration:** Resolved decoupling errors between the global runtime environment and the test runner scripts by explicitly locating and instantiating the controller memory layer via its declared namespace string: `\App\Controllers\RiderController()`.
---

## 📄 Documentation Matrix
For deeper operational details, refer to these standalone modules:

1. **[Local Environment Setup & Installation Guide](SETUP.md)**
2. **[Engineering Rules & Contribution Standards](CONTRIBUTION.md)**


