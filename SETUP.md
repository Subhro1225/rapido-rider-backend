# Local Environment Setup & Usage Guide

Follow these instructions to configure and run the localized backend testing framework on your machine.

## 📋 Prerequisites
Ensure you have the following installed locally:
* **XAMPP** (with PHP 8.0+ and MySQL running)
* **VS Code** (or any preferred text editor)
* **Git**

---

## ⚙️ Initial Project Configuration

### 1. Position the Repository
Ensure the project folder resides directly inside your XAMPP local web root directory:

C:\xampp\htdocs\rapido-rider-backend\

### 2. Configure Environment Variables
1. Look at the root of the project and verify your .env file exists.
2. If it is missing, create a new file named exactly .env at the root and fill it with your local credentials based on env.sample:

DB_HOST=localhost
DB_NAME=rapido
DB_USER=root
DB_PASS=

## 💻 Local Terminal Test Runner Execution
You can simulate the entire functional ride application directly from your terminal panel using your local PHP engine path without a web server:

# 1. Verify Database Connectivity Basics
C:\xampp\php\php.exe Test/test_db.php

# 2. Test Driver Authentication Engine
C:\xampp\php\php.exe Test/test_login_flow.php

# 3. Toggle Driver Online/Offline Availability
C:\xampp\php\php.exe Test/test_availability_flow.php

# 4. Poll Live Available Ride-Requests
C:\xampp\php\php.exe Test/test_polling_flow.php

# 5. Test Atomic Concurrent Ride Acceptance
C:\xampp\php\php.exe Test/test_accept_flow.php

# 6. Progress Active Ride States (Arrived, Started, Completed)
C:\xampp\php\php.exe Test/test_lifecycle_flow.php

# 7. Settle and Process Payout Ledger Entries
C:\xampp\php\php.exe Test/test_payment_flow.php

# 8. View Aggregated Driver Earnings & Lifetime Analytics
C:\xampp\php\php.exe Test/test_driver_analytics_flow.php

# 9. Stream Live Coordinate Refresh Telemetry
C:\xampp\php\php.exe Test/test_location_flow.php

# 10. Test Routing and Core API Mechanics
C:\xampp\php\php.exe Test/test_api_flow.php