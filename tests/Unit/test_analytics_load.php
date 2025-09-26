<?php
// Simple test for analytics.php without causing redirects

// Mock session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session data AFTER session_start
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'test_user';
$_SESSION['is_admin'] = false;

echo "Testing analytics.php load...\n";
echo "Session data: user_id=" . $_SESSION['user_id'] . ", username=" . $_SESSION['username'] . "\n";

try {
    echo "About to include analytics.php...\n";
    ob_start();
    include 'analytics.php';
    $output = ob_get_clean();
    echo "Include completed.\n";
    
    echo "✅ Analytics page loaded successfully!\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    // Check for common HTML elements
    if (strpos($output, '<!DOCTYPE html') !== false) {
        echo "✅ Contains DOCTYPE declaration\n";
    }
    if (strpos($output, 'Analytics Dashboard') !== false) {
        echo "✅ Contains Analytics Dashboard title\n";
    }
    if (strpos($output, '</html>') !== false) {
        echo "✅ Properly closed HTML\n";
    }
    
    echo "First 200 characters:\n";
    echo substr($output, 0, 200) . "...\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}
?>
