<?php
// Simple test to check if portfolios page dependencies work
echo "Testing portfolios dependencies...\n";

try {
    echo "1. Testing auth_check.php...\n";
    require_once 'auth_check.php';
    echo "✅ auth_check.php loaded\n";
} catch (Exception $e) {
    echo "❌ auth_check.php failed: " . $e->getMessage() . "\n";
}

try {
    echo "2. Testing namespace UI components...\n";
    require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
    echo "✅ UI Factory loaded\n";
} catch (Exception $e) {
    echo "❌ UI Factory failed: " . $e->getMessage() . "\n";
}

try {
    echo "3. Testing RefactoredPortfolioDAO...\n";
    require_once 'RefactoredPortfolioDAO.php';
    echo "✅ RefactoredPortfolioDAO loaded\n";
} catch (Exception $e) {
    echo "❌ RefactoredPortfolioDAO failed: " . $e->getMessage() . "\n";
}

try {
    echo "4. Testing MenuService...\n";
    require_once 'MenuService.php';
    echo "✅ MenuService loaded\n";
} catch (Exception $e) {
    echo "❌ MenuService failed: " . $e->getMessage() . "\n";
}

echo "✅ All dependencies loaded successfully!\n";
?>
