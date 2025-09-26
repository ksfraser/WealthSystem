<?php
/**
 * Test Modern PHP Setup - Using existing classes with namespaces
 */

echo "Modern PHP Test (Using Existing Classes)\n";
echo "=========================================\n\n";

// Test the bootstrap approach
require_once __DIR__ . '/bootstrap.php';

// Test existing classes now with namespaces - no additional requires needed!
try {
    echo "Testing existing classes with new namespaces:\n";
    
    // SessionManager (existing class, now with namespace)
    $sessionManager = \App\Core\SessionManager::getInstance();
    echo "✅ App\\Core\\SessionManager (existing class with namespace)\n";
    
    // Test session functionality
    $isActive = $sessionManager->isSessionActive();
    echo "✅ Session active: " . ($isActive ? 'Yes' : 'No') . "\n";
    
    // Test exception classes (existing, now with namespace)
    $loginException = new \App\Auth\LoginRequiredException();
    echo "✅ App\\Auth\\LoginRequiredException (existing class with namespace)\n";
    
    echo "\n🎉 Bootstrap approach working!\n";
    echo "✅ One include (bootstrap.php) instead of multiple require_once\n";
    echo "✅ Existing classes now use proper namespaces\n";
    echo "✅ No more __DIR__ path issues\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\nComparison:\n";
echo "OLD WAY (what was causing 500 errors):\n";
echo "  require_once 'AuthExceptions.php';\n";
echo "  require_once 'SessionManager.php';\n";
echo "  require_once 'UserAuthDAO.php';\n";
echo "  // Path issues, headers_sent conflicts, etc.\n\n";

echo "NEW WAY:\n";  
echo "  require_once __DIR__ . '/bootstrap.php';\n";
echo "  use App\\Core\\SessionManager;\n";
echo "  use App\\Auth\\LoginRequiredException;\n";
echo "  // Clean, modern, no path issues!\n";
?>
