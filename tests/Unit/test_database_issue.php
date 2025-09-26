<?php
/**
 * Minimal test to isolate the 500 error cause
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing UserAuthDAO database connection issue...\n";

try {
    // Test 1: Include UserAuthDAO
    echo "1. Including UserAuthDAO...\n";
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "✓ UserAuthDAO included\n";
    
    // Test 2: Try to create instance with timeout
    echo "2. Creating UserAuthDAO instance...\n";
    set_time_limit(10); // 10 second timeout
    
    $userAuth = new UserAuthDAO();
    echo "✓ UserAuthDAO created successfully\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
}

echo "Test complete.\n";
?>
