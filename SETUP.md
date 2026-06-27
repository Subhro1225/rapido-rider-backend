# Local Environment Setup & Usage Guide

Follow these steps to configure and run the backend locally using XAMPP.

---

## 📋 Prerequisites

- **XAMPP** (PHP 8.0+ and MySQL running)
- **VS Code** or any text editor
- **Git**

---

## ⚙️ Initial Configuration

### 1. Place the Repository
Put the project folder directly inside your XAMPP web root:
```
C:\xampp\htdocs\rapido-rider-backend\
```

### 2. Configure Environment Variables
Create a `.env` file at the project root (copy from `env.sample`):
```
DB_HOST=localhost
DB_NAME=rapido
DB_USER=root
DB_PASS=
```

### 3. Import the Database Schema
Open **phpMyAdmin**, create a database named `rapido`, then import `Sql/rapido.sql`.

### 4. Apply Required Schema Migrations
If you are upgrading from an earlier version of the schema, run this once in phpMyAdmin's SQL tab:
```sql
ALTER TABLE drivers
  ADD COLUMN vehicle_type VARCHAR(30) DEFAULT 'bike',
  ADD COLUMN plate_number VARCHAR(20) DEFAULT '',
  MODIFY COLUMN password VARCHAR(255) NOT NULL;
```

### 5. Open the Frontend
Visit in your browser:
```
http://localhost/rapido-rider-backend/index.html
```

---

## 💻 CLI Test Runner

You can test every backend method directly from the terminal without a browser:

| # | Test Script | What It Tests |
|---|-------------|---------------|
| 1 | `Test/test_signup_flow.php` | Driver registration with Bcrypt hashing & vehicle info |
| 2 | `Test/test_db.php` | Raw PDO database connectivity |
| 3 | `Test/test_login_flow.php` | OTP authentication & `driver_id` return |
| 4 | `Test/test_availability_flow.php` | Online/offline toggle with auth guard |
| 5 | `Test/test_polling_flow.php` | Available ride queue fetch |
| 6 | `Test/test_accept_flow.php` | Atomic concurrent ride acceptance (race condition test) |
| 7 | `Test/test_lifecycle_flow.php` | Full ride state chain: arrived → started → completed |
| 8 | `Test/test_payment_flow.php` | Payment ledger settlement |
| 9 | `Test/test_driver_analytics_flow.php` | Earnings analytics (today, weekly, wallet) |
| 10 | `Test/test_location_flow.php` | Live GPS coordinate update |
| 11 | `Test/test_api_flow.php` | API routing & entry point mechanics |
| 12 | `Test/test_range_flow.php` | 5KM Haversine geospatial proximity filter |
| 13 | `Test/test_otp_flow.php` | Hashed OTP generation and verification |

**Run any test like this:**
```bash
C:\xampp\php\php.exe Test/test_signup_flow.php
```

---

## 🔑 Frontend Session Notes

- After login or registration, `driver_id` is saved to `localStorage` as `current_driver_id`.
- On page reload, the app reads `localStorage` to restore the session — no re-login needed.
- To force a fresh login (e.g. during testing), run this in the browser console:
```js
localStorage.clear();
```
Then reload the page.

---

## ⚠️ Common Issues

| Problem | Cause | Fix |
|---------|-------|-----|
| `500 — Column not found: vehicle_type` | Schema not migrated | Run the ALTER TABLE SQL above |
| `500 — password hash mismatch` | `password` column too short (`varchar(8)`) | Run `MODIFY COLUMN password VARCHAR(255)` |
| Dashboard opens without login | Old session in `localStorage` | Run `localStorage.clear()` in browser console |
| `API_BASE_URL is not defined` | Old version of `app.js` | Replace with the latest fixed `app.js` |
| Earnings panel shows ₹0 | `api/driver/earnings` was routed to wrong method | Replace with the latest fixed `index.php` |
