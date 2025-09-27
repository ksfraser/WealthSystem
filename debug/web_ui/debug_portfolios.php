<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== PORTFOLIOS DEBUG ===\n";
echo "Current directory: " . getcwd() . "\n";
echo "File exists: " . (file_exists('portfolios.php') ? 'YES' : 'NO') . "\n";

echo "\n=== TESTING DEPENDENCIES ===\n";

// Test 1: auth_check
echo "1. Testing auth_check.php... ";
if (file_exists('auth_check.php')) {
    try {
        ob_start();
        include 'auth_check.php';
        $output = ob_get_clean();
        echo "✅ LOADED\n";
        if ($output) echo "Output: $output\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ FILE NOT FOUND\n";
}

// Test 2: UI Components
echo "2. Testing UI autoload... ";
if (file_exists('../src/Ksfraser/UIRenderer/autoload.php')) {
    try {
        require_once '../src/Ksfraser/UIRenderer/autoload.php';
        echo "✅ LOADED\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ AUTOLOAD FILE NOT FOUND\n";
}

// Test 3: Try to load portfolios
echo "\n=== LOADING PORTFOLIOS.PHP ===\n";
try {
    ob_start();
    include 'portfolios.php';
    $portfolios_output = ob_get_clean();
    echo "✅ PORTFOLIOS LOADED SUCCESSFULLY\n";
    echo "Output length: " . strlen($portfolios_output) . " characters\n";
    if (strlen($portfolios_output) > 0) {
        echo "First 200 characters:\n" . substr($portfolios_output, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ PORTFOLIOS ERROR: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ PORTFOLIOS FATAL: " . $e->getMessage() . "\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
