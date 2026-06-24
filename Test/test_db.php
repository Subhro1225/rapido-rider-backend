<?php
// 1. Setup path mapping so tests can load your project classes
spl_autoload_register(function ($class) {
    if (strpos($class, 'Config\\') === 0) {
        require_once __DIR__ . '/../config/' . str_replace('Config\\', '', $class) . '.php';
    }
    if (strpos($class, 'App\\') === 0) {
        require_once __DIR__ . '/../src/' . str_replace('App\\', '', $class) . '.php';
    }
});

use Config\Database;

echo "=== Running Database Connection Test ===\n";

try {
    $db = Database::getInstance();
    if ($db) {
        echo "✅ TEST PASSED: Successfully connected to rapido_rider_db!\n";
    }
} catch (\Exception $e) {
    echo "❌ TEST FAILED: " . $e->getMessage() . "\n";
}
