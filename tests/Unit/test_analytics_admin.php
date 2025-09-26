<?php
// Test analytics.php with admin user to verify admin color coding
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set ADMIN session data
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_user';
$_SESSION['is_admin'] = true;

echo "Testing analytics.php with ADMIN user...\n";
echo "Session data: user_id=" . $_SESSION['user_id'] . ", username=" . $_SESSION['username'] . ", is_admin=" . ($_SESSION['is_admin'] ? 'true' : 'false') . "\n";

try {
    echo "About to include analytics.php...\n";
    ob_start();
    include 'analytics.php';
    $output = ob_get_clean();
    echo "Include completed.\n";
    
    echo "✅ Analytics page loaded successfully!\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    // Check for admin-specific elements
    if (strpos($output, 'nav-header admin') !== false) {
        echo "✅ Contains admin CSS class\n";
    } else {
        echo "❌ Missing admin CSS class\n";
    }
    
    if (strpos($output, 'admin-badge') !== false) {
        echo "✅ Contains admin badge\n";
    } else {
        echo "❌ Missing admin badge\n";
    }
    
    if (strpos($output, 'dc3545') !== false || strpos($output, 'c82333') !== false) {
        echo "✅ Contains admin red color scheme\n";
    } else {
        echo "❌ Missing admin red color scheme\n";
    }
    
    echo "First 500 characters:\n";
    echo substr($output, 0, 500) . "...\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}
?>
