<?php
// Ultra minimal test to isolate the 500 error
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "Starting test...\n";

// Test 1: Basic PHP
echo "✅ PHP working\n";

// Test 2: Try auth_check without output
ob_start();
try {
    require_once 'auth_check.php';
    $auth_output = ob_get_clean();
    echo "✅ auth_check.php loaded\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ auth_check.php failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Test 3: Try NavigationManager
try {
    require_once 'NavigationManager.php';
    echo "✅ NavigationManager.php loaded\n";
} catch (Throwable $e) {
    echo "❌ NavigationManager.php failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

// Test 4: Try creating NavigationManager
try {
    $nav = getNavManager();
    echo "✅ NavigationManager created\n";
} catch (Throwable $e) {
    echo "❌ NavigationManager creation failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "Test complete\n";
?>
