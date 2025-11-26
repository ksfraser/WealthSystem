<?php
echo "Testing UserAuthDAO...\n";

try {
    require_once 'UserAuthDAO.php';
    echo "✅ UserAuthDAO.php loaded\n";
    
    $userAuth = new UserAuthDAO();
    echo "✅ UserAuthDAO created\n";
    
    $isLoggedIn = $userAuth->isLoggedIn();
    echo "✅ isLoggedIn() called: " . ($isLoggedIn ? 'true' : 'false') . "\n";
    
} catch (Exception $e) {
    echo "❌ UserAuthDAO error: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ UserAuthDAO fatal error: " . $e->getMessage() . "\n";
}

echo "Test complete\n";
?>
