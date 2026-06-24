# Core Engineering Rules & Architecture Standards

To keep the codebase reliable, clean, and bug-free, everyone working on this project must strictly adhere to these core development rules.

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