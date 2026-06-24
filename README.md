# Rapido Clone — Rider Backend Module

## Module Purpose
The Rider Backend Module serves as the centralized orchestration engine managing the lifecycle, authentication, and state synchronization of service providers (drivers/riders) within the Rapido Clone ecosystem. Built as a decoupled, zero-framework REST API platform in pure PHP 8, this module implements a strict Model-View-Controller (MVC) separation to process driver operational states, profile verifications, and relational database records safely via MySQL 8.

## Prerequisites
- **PHP Runtime Engine:** Version 8.0 or higher
- **Relational Database:** MySQL 8.0 / MariaDB
- **Web Server Layer:** Apache Server (deployed via XAMPP, WAMP, or local compilation stacks)

## Setup & Deployment Steps
1. **Repository Deployment:** Clone this codebase into your web server’s primary deployment path (e.g., `C:\xampp\htdocs\rapido-rider-backend`).
2. **Database Engine Initialization:** Open phpMyAdmin, create a target schema named `rapido`, and ensure the relational tables (`drivers`, `users`, `rides`, `payments`) are active.
3. **Environment Security Configuration:** Initialize a local environment file named `.env` in the root directory. Using the structure provided in `env.sample`, fill in your local system parameters.
4. **Isolated Test Execution:** Verify database connectivity and autoload configurations from your terminal before exposing endpoints to the web server:
   C:\xampp\php\php.exe Test/test_login_flow.php

## Core API Specification Matrix

| Method | Target Routing Endpoint | Functional Overview | Access Control |
|---|---|---|---|
| POST | /api/user/login | Validates driver identity via unique mobile tracking and passwords. | Public |

*Note: All data-mutating endpoints expect a transmission header containing Content-Type: application/json.*

## Standardized JSON Exception Framework
All runtime execution failures, verification anomalies, or structural payload errors intercept the response stack to return appropriate HTTP Status Codes (e.g., 400, 401, 500) alongside a uniform, machine-readable JSON structure:

{
  "status": "error",
  "message": "A precise string describing the runtime exception context."
}

## Security & Architecture Protocols
- **Parameterized Queries:** To completely neutralize SQL Injection (SQLi) attack vectors, all database communication layers run exclusively via PDO Prepared Statements utilizing explicit parameter binding.
- **Environment Isolation:** To prevent credential leakage across open-source code hosting infrastructure, all cryptographic keys, database secrets, and structural environment settings are stored locally in the git-ignored .env file.
- **Case-Sensitive Autoloading:** The execution engine conforms to rigid Unix-style directory mapping rules. Directories (Src, Controllers, Core) must preserve exact case-matching rules across all namespace routing targets to prevent deployment crashes.

----------------------------------------------------------------------------------------------------

# Rapido Clone - Rider Management System

An isolated, zero-dependency service provider management engine built natively to handle high-concurrency ride allocation tracking tasks.

## Technical Architecture Boundaries
- **Language Runtime:** PHP 8.x (Strict typing enabled, native OOP, PDO Data Abstraction Layer)
- **Storage Engine:** MySQL 8.x (Transactional InnoDB engine, strict utf8mb4_unicode_ci charsets)
- **Design Pattern Constraints:** Pure vanilla implementation. Framework abstraction layers (Laravel, Symfony), external Object-Relational Mappers (ORMs), or node-package dependencies are strictly prohibited.

## System Engineering Progress Checkpoints
- [x] Milestone 1: Relational Database Normalization Schema Setup (rapido)
- [x] Milestone 2: Singleton Architecture Implementation for Centralized Database Access Control
- [x] Milestone 3: Isolated CLI Verification Engine & Continuous Local Test Framework
- [x] Milestone 4: Parameterized Rider Authentication Controller Logic (mobile/password validation)
- [x] Milestone 5: Live Ride-Request Polling Engine Logic (Fetching available trips)
- [x] Milestone 6: Atomic Ride Acceptance & Driver Allocation State Matrix
- [x] Milestone 7: Sequential Ride Lifecycle State Transitions (Arrived, Started, Completed)
- [x] Milestone 8: Ride Earnings Realization & Payment Settlement Engine
- [x] Milestone 9: Cross-Table Driver Earnings & Completed Trips Analytics Engine
- [x] Milestone 10: Live Geospatial Coordinate Refresh Tracking Engine

## 🚀 Production Optimizations & Security Enhancements
- **ACID-Compliant Transactions:** Integrated atomic PDO database transactions (`beginTransaction`, `commit`, `rollBack`) with row-level concurrency locking (`FOR UPDATE`) on critical states like ride acceptance to completely eliminate multi-driver race conditions.
- **High-Frequency Query Indexing:** Applied database optimization indexes (`idx_ride_status`, `idx_driver_status`) in MySQL to scale up live pooling performance and prevent performance-heavy Full Table Scans.
- **Cryptographic Authentication:** Swapped out insecure plain-text database comparisons for secure, industry-standard **Bcrypt cryptographic hashing** validation via native PHP security frameworks.
