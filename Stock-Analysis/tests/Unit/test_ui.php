<?php
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
use Ksfraser\UIRenderer\Factories\UiFactory;

try {
    echo "Testing UiFactory...\n";
    
    $card = UiFactory::createCard('Test', 'This is a test card');
    echo "Card created successfully\n";
    
    $navigation = UiFactory::createNavigation('Test', 'test', null, false, [], false);
    echo "Navigation created successfully\n";
    
    $page = UiFactory::createPage('Test Page', $navigation, [$card]);
    echo "Page created successfully\n";
    
    $html = $page->render();
    echo "Page rendered successfully\n";
    echo "Length: " . strlen($html) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
