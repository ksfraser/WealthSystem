<?php
// Simple web context test
header('Content-Type: text/plain');
echo "Testing web context...\n";

// Test auth_check include
echo "Loading auth_check.php...\n";
try {
    require_once 'auth_check.php';
    echo "Auth check loaded successfully\n";
    echo "Current user: " . (getCurrentUser()['username'] ?? 'No user') . "\n";
} catch (Exception $e) {
    echo "Auth check error: " . $e->getMessage() . "\n";
    exit;
}

echo "Test completed successfully!\n";
?>
