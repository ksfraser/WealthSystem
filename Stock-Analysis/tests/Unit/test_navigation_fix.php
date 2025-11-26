<?php
// Test the fix for the undefined array key issue
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
require_once 'MenuService.php';

use Ksfraser\UIRenderer\Factories\UiFactory;

echo "Testing NavigationComponent with MenuService...\n";

try {
    // Get menu items like index.php does
    $menuItems = MenuService::getMenuItems('dashboard', false, false);
    echo "✅ MenuItems retrieved (" . count($menuItems) . " items)\n";
    
    // Print menu structure for debugging
    foreach ($menuItems as $i => $item) {
        echo "  Item $i: " . json_encode($item) . "\n";
    }
    
    // Create navigation like index.php does
    $navigation = UiFactory::createNavigation(
        'Test Dashboard',
        'dashboard',
        ['username' => 'TestUser'],
        false,
        $menuItems,
        false
    );
    echo "✅ Navigation created successfully\n";
    
    // Render navigation
    $html = $navigation->toHtml();
    echo "✅ Navigation rendered (" . strlen($html) . " chars)\n";
    
    // Check for 'active' class in output
    if (strpos($html, 'active') !== false) {
        echo "✅ Active class found in navigation\n";
    } else {
        echo "⚠️ No active class found\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>
