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
│   └── test_polling_flow.php       # CLI ride availability queue scanner
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

---

## 📄 Documentation Matrix
For deeper operational details, refer to these standalone modules:

1. **[Local Environment Setup & Installation Guide](SETUP.md)**
2. **[Engineering Rules & Contribution Standards](CONTRIBUTION.md)**


