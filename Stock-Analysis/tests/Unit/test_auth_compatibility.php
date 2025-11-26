<?php
/**
 * Test UserAuthDAO Compatibility with Symfony Session
 */

echo "UserAuthDAO Compatibility Test\n";
echo "==============================\n\n";

// Test the compatibility
require_once __DIR__ . '/bootstrap_symfony.php';

echo "✅ Bootstrap loaded\n";

try {
    // Test UserAuthDAO directly
    $userAuth = getUserAuth();
    if ($userAuth) {
        echo "✅ UserAuthDAO created\n";
        
        $loggedIn = $userAuth->isLoggedIn();
        echo "User logged in (UserAuthDAO): " . ($loggedIn ? 'Yes' : 'No') . "\n";
        
        if ($loggedIn) {
            $user = $userAuth->getCurrentUser();
            echo "User data: " . json_encode($user) . "\n";
        }
    } else {
        echo "❌ UserAuthDAO failed to create\n";
    }
    
    // Test compatibility functions
    echo "\nCompatibility functions:\n";
    echo "isLoggedIn(): " . (isLoggedIn() ? 'Yes' : 'No') . "\n";
    echo "isAdmin(): " . (isAdmin() ? 'Yes' : 'No') . "\n";
    
    $currentUser = getCurrentUser();
    if ($currentUser) {
        echo "Current user: " . json_encode($currentUser) . "\n";
    } else {
        echo "No current user found\n";
    }
    
    // Test Symfony session
    $sessionManager = \App\Core\SessionManager::getInstance();
    echo "\nSymfony session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
    echo "Session ID: " . ($sessionManager->getId() ?: 'Not available') . "\n";
    
    echo "\n🎉 Compatibility test completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
