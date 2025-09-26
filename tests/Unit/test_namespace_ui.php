<?php
// Simple test with namespaced UI components
echo "Testing namespaced UI components...\n";

require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

use Ksfraser\UIRenderer\Factories\UiFactory;

try {
    echo "✅ Autoloader loaded\n";
    echo "✅ UiFactory imported\n";
    
    // Test creating a simple card
    $card = UiFactory::createCard('Test', 'This is a test', 'info');
    echo "✅ Card created\n";
    
    // Test creating navigation
    $nav = UiFactory::createNavigation('Test App', 'test', ['username' => 'TestUser'], false, []);
    echo "✅ Navigation created\n";
    
    // Test creating page
    $page = UiFactory::createPage('Test Page', $nav, [$card]);
    echo "✅ Page created\n";
    
    // Test rendering
    $html = $page->render();
    echo "✅ Page rendered (" . strlen($html) . " chars)\n";
    echo "First 100 chars: " . substr($html, 0, 100) . "...\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
