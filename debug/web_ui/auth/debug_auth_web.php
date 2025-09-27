<?php
// debug_auth_web.php - Simple debug page for web server testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Auth Debug</title></head><body>";
echo "<h1>Authentication Debug</h1>";

try {
    echo "<p>1. Testing SessionManager...</p>";
    require_once 'SessionManager.php';
    $sessionManager = SessionManager::getInstance();
    echo "<p>SessionManager: " . ($sessionManager->isSessionActive() ? 'Active' : 'Inactive') . "</p>";
    
    echo "<p>2. Testing UserAuthDAO...</p>";
    require_once 'UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    echo "<p>UserAuthDAO created successfully</p>";
    
    echo "<p>3. Testing login status...</p>";
    $isLoggedIn = $userAuth->isLoggedIn();
    echo "<p>Is logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "</p>";
    
    if ($isLoggedIn) {
        $currentUser = $userAuth->getCurrentUser();
        echo "<p>Current user: " . htmlspecialchars(print_r($currentUser, true)) . "</p>";
        echo "<p>Is admin: " . ($userAuth->isAdmin() ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p>User not logged in - would normally redirect to login</p>";
        echo "<p>In a real page, this would trigger LoginRequiredException</p>";
    }
    
    echo "<p>4. Testing auth_check inclusion...</p>";
    
    // Test auth_check in a controlled way
    try {
        // Don't actually include auth_check as it will redirect
        // Instead, test the exception directly
        if (!$isLoggedIn) {
            require_once 'AuthExceptions.php';
            throw new LoginRequiredException('login.php?test=1', 'Test login required');
        }
        echo "<p>Auth check would pass</p>";
    } catch (LoginRequiredException $e) {
        echo "<p>LoginRequiredException caught: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Redirect URL: " . htmlspecialchars($e->getRedirectUrl()) . "</p>";
        echo "<p>In a real page, this would redirect to login</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p style='color: red;'>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
    echo "<p style='color: red;'>Trace:<br>" . nl2br(htmlspecialchars($e->getTraceAsString())) . "</p>";
}

echo "<p>Debug completed</p>";
echo "</body></html>";
?>
