<?php
// Test index.php step by step
echo "Testing index.php components...\n";

echo "1. Testing auth_check.php...\n";
try {
    require_once 'auth_check.php';
    echo "✅ auth_check.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ auth_check.php error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ auth_check.php fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "2. Testing NavigationManager.php...\n";
try {
    require_once 'NavigationManager.php';
    echo "✅ NavigationManager.php loaded successfully\n";
} catch (Exception $e) {
    echo "❌ NavigationManager.php error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ NavigationManager.php fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "3. Testing getNavManager()...\n";
try {
    $navManager = getNavManager();
    echo "✅ getNavManager() worked\n";
} catch (Exception $e) {
    echo "❌ getNavManager() error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ getNavManager() fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "4. Testing navigation methods...\n";
try {
    $currentUser = $navManager->getCurrentUser();
    $isAdmin = $navManager->isAdmin();
    echo "✅ Navigation methods worked\n";
    echo "   User: " . ($currentUser ? $currentUser['username'] : 'None') . "\n";
    echo "   Admin: " . ($isAdmin ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "❌ Navigation methods error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Navigation methods fatal error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "All tests passed!\n";
?>
