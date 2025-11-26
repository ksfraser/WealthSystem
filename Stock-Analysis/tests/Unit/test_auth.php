<?php
/**
 * Test auth_check.php specifically
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing auth_check.php...\n";

try {
    echo "Including auth_check.php...\n";
    require_once __DIR__ . '/auth_check.php';
    echo "✓ auth_check.php included successfully\n";
    
    echo "Testing requireLogin function...\n";
    if (function_exists('requireLogin')) {
        echo "✓ requireLogin function exists\n";
    } else {
        echo "✗ requireLogin function NOT found\n";
    }
    
} catch (Exception $e) {
    echo "ERROR in auth_check: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "FATAL in auth_check: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete.\n";
?>
