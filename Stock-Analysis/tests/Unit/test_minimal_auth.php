<?php
// test_minimal_auth.php - Minimal auth test for web server
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Set up a custom error handler to catch any issues
set_error_handler(function($severity, $message, $file, $line) {
    echo "Error: $message in $file:$line<br>";
    return false;
});

set_exception_handler(function($exception) {
    echo "Uncaught exception: " . $exception->getMessage() . "<br>";
    echo "File: " . $exception->getFile() . " Line: " . $exception->getLine() . "<br>";
});

echo "Starting minimal auth test...<br>";

try {
    echo "1. Loading AuthExceptions...<br>";
    require_once 'AuthExceptions.php';
    echo "AuthExceptions loaded<br>";
    
    echo "2. Testing exception throw/catch...<br>";
    try {
        throw new LoginRequiredException('login.php', 'Test exception');
    } catch (LoginRequiredException $e) {
        echo "Exception caught successfully: " . $e->getMessage() . "<br>";
    }
    
    echo "3. Loading SessionManager...<br>";
    require_once 'SessionManager.php';
    $sessionManager = SessionManager::getInstance();
    echo "SessionManager loaded, active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "<br>";
    
    echo "4. Loading UserAuthDAO...<br>";
    require_once 'UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    echo "UserAuthDAO loaded<br>";
    
    echo "5. Checking login status...<br>";
    $isLoggedIn = $userAuth->isLoggedIn();
    echo "Logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "<br>";
    
    echo "6. Testing auth_check include...<br>";
    if ($isLoggedIn) {
        echo "User is logged in, auth_check should pass<br>";
        require_once 'auth_check.php';
        echo "auth_check completed successfully<br>";
    } else {
        echo "User not logged in, this would trigger LoginRequiredException<br>";
        echo "Simulating auth_check failure...<br>";
        throw new LoginRequiredException('login.php?return=' . urlencode($_SERVER['REQUEST_URI'] ?? ''), 'User not logged in');
    }
    
} catch (LoginRequiredException $e) {
    echo "LoginRequiredException handled: " . $e->getMessage() . "<br>";
    echo "Redirect URL: " . $e->getRedirectUrl() . "<br>";
    echo "In production, this would redirect to login<br>";
} catch (Exception $e) {
    echo "Other exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

echo "Test completed successfully<br>";
?>
