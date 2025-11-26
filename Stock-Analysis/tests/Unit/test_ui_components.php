<?php
// Test all major UI components step by step
require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';

echo "Testing UI Components step by step...\n";

// Test 1: Basic DTOs
try {
    echo "\n1. Testing NavigationDto...\n";
    $navDto = new Ksfraser\UIRenderer\DTOs\NavigationDto('Test App', 'dashboard');
    echo "✅ NavigationDto created\n";
} catch (Exception $e) {
    echo "❌ NavigationDto failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Interfaces
try {
    echo "\n2. Testing ComponentInterface...\n";
    if (interface_exists('Ksfraser\UIRenderer\Contracts\ComponentInterface')) {
        echo "✅ ComponentInterface exists\n";
    } else {
        throw new Exception("ComponentInterface not found");
    }
} catch (Exception $e) {
    echo "❌ ComponentInterface failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: NavigationComponent
try {
    echo "\n3. Testing NavigationComponent...\n";
    $navComponent = new Ksfraser\UIRenderer\Components\NavigationComponent($navDto);
    echo "✅ NavigationComponent created\n";
    
    $html = $navComponent->toHtml();
    echo "✅ NavigationComponent rendered (" . strlen($html) . " chars)\n";
} catch (Exception $e) {
    echo "❌ NavigationComponent failed: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ NavigationComponent error: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: UiFactory
try {
    echo "\n4. Testing UiFactory::createNavigation...\n";
    $navigation = Ksfraser\UIRenderer\Factories\UiFactory::createNavigation(
        'Test App',
        'dashboard',
        ['username' => 'TestUser'],
        false,
        []
    );
    echo "✅ UiFactory::createNavigation worked\n";
    
    $factoryHtml = $navigation->toHtml();
    echo "✅ Factory navigation rendered (" . strlen($factoryHtml) . " chars)\n";
} catch (Exception $e) {
    echo "❌ UiFactory failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ UiFactory error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 5: UiRenderer compatibility layer
try {
    echo "\n5. Testing UiRenderer compatibility layer...\n";
    require_once __DIR__ . '/UiRenderer.php';
    
    $compatNavigation = UiFactory::createNavigationComponent(
        'Test App',
        'dashboard',
        ['username' => 'TestUser'],
        false,
        []
    );
    echo "✅ UiRenderer compatibility layer worked\n";
    
    $compatHtml = $compatNavigation->toHtml();
    echo "✅ Compatibility navigation rendered (" . strlen($compatHtml) . " chars)\n";
} catch (Exception $e) {
    echo "❌ UiRenderer compatibility failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ UiRenderer compatibility error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ All UI component tests passed!\n";
?>
