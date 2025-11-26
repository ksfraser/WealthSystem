<?php
// Test autoloader for NavigationDto and ComponentInterface
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

echo "Testing autoloader...\n";

try {
    $navDto = new Ksfraser\UIRenderer\DTOs\NavigationDto('Test', 'test-page');
    echo "✅ NavigationDto autoloaded successfully\n";
    echo "Title: " . $navDto->title . "\n";
    echo "Page: " . $navDto->currentPage . "\n";
} catch (Exception $e) {
    echo "❌ NavigationDto autoload failed: " . $e->getMessage() . "\n";
}

try {
    $cardDto = new Ksfraser\UIRenderer\DTOs\CardDto('Test Card', 'Test content');
    echo "✅ CardDto autoloaded successfully\n";
    echo "Title: " . $cardDto->title . "\n";
} catch (Exception $e) {
    echo "❌ CardDto autoload failed: " . $e->getMessage() . "\n";
}

// Test if ComponentInterface can be loaded by checking if it exists
if (interface_exists('Ksfraser\UIRenderer\Contracts\ComponentInterface')) {
    echo "✅ ComponentInterface autoloaded successfully\n";
} else {
    echo "❌ ComponentInterface not found\n";
}

// Test NavigationComponent creation
try {
    $navComponent = new Ksfraser\UIRenderer\Components\NavigationComponent($navDto);
    echo "✅ NavigationComponent created successfully\n";
    $html = $navComponent->toHtml();
    echo "Generated HTML length: " . strlen($html) . " characters\n";
} catch (Exception $e) {
    echo "❌ NavigationComponent failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ NavigationComponent error: " . $e->getMessage() . "\n";
}

echo "Test complete.\n";
?>
