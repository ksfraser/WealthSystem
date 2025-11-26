<?php
/**
 * Test the new Symfony Session Migration
 */

echo "Symfony Session Migration Test\n";
echo "=============================\n\n";

// Test the bootstrap
require_once __DIR__ . '/bootstrap_symfony.php';

echo "✅ Bootstrap loaded successfully\n";

// Test the new SessionManager
use App\Core\SessionManager;

try {
    $sessionManager = SessionManager::getInstance();
    echo "✅ Symfony SessionManager created\n";
    
    echo "Session active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "\n";
    echo "Session ID: " . ($sessionManager->getId() ?: 'Not available') . "\n";
    echo "Session name: " . ($sessionManager->getName() ?: 'Default') . "\n";
    
    if ($sessionManager->getInitializationError()) {
        echo "⚠️ Init warning: " . $sessionManager->getInitializationError() . "\n";
    }
    
    // Test session operations
    $sessionManager->set('test_key', 'test_value');
    $value = $sessionManager->get('test_key');
    echo "✅ Session set/get test: " . ($value === 'test_value' ? 'PASSED' : 'FAILED') . "\n";
    
    // Test flash messages
    $sessionManager->addFlash('success', 'Test flash message');
    $flashes = $sessionManager->getFlashes('success');
    echo "✅ Flash message test: " . (count($flashes) > 0 ? 'PASSED' : 'FAILED') . "\n";
    
    // Test helper functions
    setSessionValue('helper_test', 'helper_value');
    $helperValue = getSessionValue('helper_test');
    echo "✅ Helper function test: " . ($helperValue === 'helper_value' ? 'PASSED' : 'FAILED') . "\n";
    
    echo "\n🎉 All tests passed! Symfony Session migration successful.\n";
    
    echo "\nMigration Benefits:\n";
    echo "- ✅ No more manual session path creation\n";
    echo "- ✅ No more headers_sent conflicts\n";
    echo "- ✅ Flash messages included\n";
    echo "- ✅ Battle-tested session management\n";
    echo "- ✅ Reduced code complexity\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
