<?php
/**
 * Test just the includes that system_status.php uses
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing includes step by step...\n";

try {
    echo "1. Testing auth_check.php...\n";
    ob_start();
    require_once __DIR__ . '/auth_check.php';
    $authOutput = ob_get_contents();
    ob_end_clean();
    echo "Auth output length: " . strlen($authOutput) . "\n";
    if ($authOutput) {
        echo "Auth output: " . substr($authOutput, 0, 100) . "\n";
    }
    
    echo "2. Testing NavigationManager.php...\n";
    ob_start();
    require_once __DIR__ . '/NavigationManager.php';
    $navOutput = ob_get_contents();
    ob_end_clean();
    echo "Nav output length: " . strlen($navOutput) . "\n";
    if ($navOutput) {
        echo "Nav output: " . substr($navOutput, 0, 100) . "\n";
    }
    
    echo "All includes tested successfully\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
}
?>
