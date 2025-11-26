<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ANALYTICS DEBUG TEST ===\n";

try {
    echo "1. Testing auth_check.php...\n";
    // Mock session for testing
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        }
    }
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'test';
    
    // Test requiring auth_check
    ob_start();
    require_once 'auth_check.php';
    $auth_output = ob_get_clean();
    echo "✅ auth_check.php loaded successfully\n";
    if ($auth_output) echo "Auth output: " . $auth_output . "\n";
    
} catch (Exception $e) {
    echo "❌ auth_check.php error: " . $e->getMessage() . "\n";
}

try {
    echo "2. Testing NavigationManager.php...\n";
    require_once 'NavigationManager.php';
    $navManager = new NavigationManager();
    echo "✅ NavigationManager loaded successfully\n";
    
    echo "3. Testing getNavigationCSS()...\n";
    $css = $navManager->getNavigationCSS();
    echo "✅ CSS method works, length: " . strlen($css) . " chars\n";
    
} catch (Exception $e) {
    echo "❌ NavigationManager error: " . $e->getMessage() . "\n";
}

try {
    echo "4. Testing UiStyles.php...\n";
    require_once 'UiStyles.php';
    ob_start();
    UiStyles::render();
    $styles = ob_get_clean();
    echo "✅ UiStyles loaded, length: " . strlen($styles) . " chars\n";
    
} catch (Exception $e) {
    echo "❌ UiStyles error: " . $e->getMessage() . "\n";
}

try {
    echo "5. Testing QuickActions.php...\n";
    require_once 'QuickActions.php';
    ob_start();
    QuickActions::render();
    $actions = ob_get_clean();
    echo "✅ QuickActions loaded, length: " . strlen($actions) . " chars\n";
    
} catch (Exception $e) {
    echo "❌ QuickActions error: " . $e->getMessage() . "\n";
}

echo "6. Testing full analytics page include...\n";
try {
    ob_start();
    include 'analytics.php';
    $full_page = ob_get_clean();
    echo "✅ Full analytics page loads successfully!\n";
    echo "Page length: " . strlen($full_page) . " characters\n";
    echo "First 200 chars:\n" . substr($full_page, 0, 200) . "...\n";
} catch (Exception $e) {
    echo "❌ Analytics page error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Analytics page fatal error: " . $e->getMessage() . "\n";
}

echo "=== TEST COMPLETE ===\n";
?>
