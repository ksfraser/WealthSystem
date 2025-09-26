<?php
// Minimal database test to isolate HTTP 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting database test...\n";

// Test 1: Basic PHP
echo "1. PHP working: OK\n";

// Test 2: Session
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "2. Session: OK\n";
} catch (Exception $e) {
    echo "2. Session ERROR: " . $e->getMessage() . "\n";
}

// Test 3: Auth check (this might be the problem)
try {
    require_once 'auth_check.php';
    echo "3. Auth check loaded: OK\n";
} catch (Exception $e) {
    echo "3. Auth check ERROR: " . $e->getMessage() . "\n";
    exit("Auth check failed");
}

// Test 4: Try to call requireLogin
try {
    requireLogin();
    echo "4. RequireLogin called: OK\n";
} catch (Exception $e) {
    echo "4. RequireLogin ERROR: " . $e->getMessage() . "\n";
    exit("RequireLogin failed");
}

// Test 5: Navigation header
try {
    require_once 'nav_header.php';
    echo "5. Nav header loaded: OK\n";
} catch (Exception $e) {
    echo "5. Nav header ERROR: " . $e->getMessage() . "\n";
    exit("Nav header failed");
}

// Test 6: Database loader
try {
    require_once 'database_loader.php';
    echo "6. Database loader: OK\n";
} catch (Exception $e) {
    echo "6. Database loader ERROR: " . $e->getMessage() . "\n";
    exit("Database loader failed");
}

// Test 7: Database connection
try {
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    echo "7. Database connection: " . ($connection ? "OK" : "FAILED") . "\n";
} catch (Exception $e) {
    echo "7. Database connection ERROR: " . $e->getMessage() . "\n";
}

echo "All tests completed successfully!\n";
?>
