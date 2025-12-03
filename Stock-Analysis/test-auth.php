<?php
// Test authentication on both pages

echo "Testing Authentication Requirements\n";
echo "====================================\n\n";

// Test 1: Strategy Config - No session (should redirect/error)
echo "Test 1: Strategy Configuration - No active session\n";
ob_start();
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    require __DIR__ . '/web_ui/strategy-config.php';
    $output = ob_get_clean();
    echo "❌ FAIL: Strategy Config loaded without authentication\n";
} catch (Exception $e) {
    ob_end_clean();
    if (strpos($e->getMessage(), 'not logged in') !== false || 
        strpos($e->getMessage(), 'login required') !== false) {
        echo "✅ PASS: Strategy Config correctly requires login\n";
    } else {
        echo "❌ FAIL: Unexpected error: " . $e->getMessage() . "\n";
    }
}

// Test 2: Job Manager - No session (should redirect/error)
echo "\nTest 2: Job Manager - No active session\n";
ob_start();
try {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    // Start a new PHP process to test job_manager.php
    $result = shell_exec('php -r "require \'web_ui/job_manager.php\';" 2>&1');
    ob_end_clean();
    
    if (strpos($result, 'not logged in') !== false || 
        strpos($result, 'login required') !== false ||
        strpos($result, 'LoginRequired') !== false) {
        echo "✅ PASS: Job Manager correctly requires login\n";
    } else {
        echo "❌ FAIL: Job Manager loaded without authentication\n";
        echo "Output: " . substr($result, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ FAIL: Unexpected error: " . $e->getMessage() . "\n";
}

echo "\n✅ All authentication tests complete!\n";
