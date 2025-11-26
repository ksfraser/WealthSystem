<?php
// Simple test - output first, then try to create SessionManager
echo "This is output that sends headers\n";

echo "Now trying to create SessionManager...\n";

try {
    require_once __DIR__ . '/SessionManager.php';
    $sm = SessionManager::getInstance();
    
    echo "SessionManager created\n";
    echo "Session active: " . ($sm->isSessionActive() ? 'Yes' : 'No') . "\n";
    
    $error = $sm->getInitializationError();
    if ($error) {
        echo "Initialization error: $error\n";
    }
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

echo "\nNow trying to create UserAuthDAO...\n";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    
    echo "UserAuthDAO created successfully\n";
    
    $errors = $userAuth->getErrors();
    if ($errors) {
        echo "UserAuthDAO errors:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "UserAuthDAO Exception: " . $e->getMessage() . "\n";
}

echo "Test complete\n";
?>
