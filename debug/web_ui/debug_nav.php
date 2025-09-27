<?php
/**
 * Debug NavigationManager
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug NavigationManager</h1>";

try {
    echo "<p>1. Testing auth_check.php include...</p>";
    require_once 'auth_check.php';
    echo "<p>✅ auth_check.php loaded successfully</p>";
    
    echo "<p>2. Testing NavigationManager.php include...</p>";
    require_once 'NavigationManager.php';
    echo "<p>✅ NavigationManager.php loaded successfully</p>";
    
    echo "<p>3. Testing \$navManager global variable...</p>";
    if (isset($navManager)) {
        echo "<p>✅ \$navManager is set</p>";
        echo "<p>Type: " . gettype($navManager) . "</p>";
        echo "<p>Class: " . get_class($navManager) . "</p>";
    } else {
        echo "<p>❌ \$navManager is not set</p>";
    }
    
    echo "<p>4. Testing NavigationManager methods...</p>";
    $user = $navManager->getCurrentUser();
    echo "<p>Current user: " . ($user ? htmlspecialchars($user['username']) : 'Not logged in') . "</p>";
    
    $isAdmin = $navManager->isAdmin();
    echo "<p>Is admin: " . ($isAdmin ? 'Yes' : 'No') . "</p>";
    
    echo "<p>5. Testing navigation CSS...</p>";
    $css = $navManager->getNavigationCSS();
    echo "<p>CSS length: " . strlen($css) . " characters</p>";
    
    echo "<p>✅ All NavigationManager tests passed!</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Fatal Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>
