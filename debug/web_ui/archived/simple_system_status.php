<?php
// simple_system_status.php - Simplified version for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include custom exceptions first
require_once 'AuthExceptions.php';

echo "<!DOCTYPE html><html><head><title>Simple System Status</title></head><body>";
echo "<h1>Simple System Status - Testing Auth</h1>";

try {
    echo "<p>Loading authentication...</p>";
    require_once 'auth_check.php';
    
    echo "<p>Authentication passed - user is logged in!</p>";
    echo "<p>Current user: " . htmlspecialchars($user['username'] ?? 'Unknown') . "</p>";
    echo "<p>Is admin: " . ($isAdmin ? 'Yes' : 'No') . "</p>";
    
    echo "<h2>System Status</h2>";
    echo "<p>This is a simplified system status page.</p>";
    echo "<p>If you can see this, authentication is working correctly.</p>";
    
} catch (LoginRequiredException $e) {
    echo "<p>Login required. Redirecting...</p>";
    $redirectUrl = $e->getRedirectUrl();
    
    if (!headers_sent()) {
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($redirectUrl) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirectUrl) . '"></noscript>';
        echo '<p>Please <a href="' . htmlspecialchars($redirectUrl) . '">click here to login</a>.</p>';
    }
    
} catch (AdminRequiredException $e) {
    http_response_code(403);
    echo "<h1>Access Denied</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<p><a href="index.php">Return to Dashboard</a></p>';
    
} catch (AuthenticationException $e) {
    echo "<h1>Authentication Error</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<p><a href="login.php">Please login</a></p>';
    
} catch (Exception $e) {
    echo "<h1>System Error</h1>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . " Line: " . $e->getLine() . "</p>";
}

echo "</body></html>";
?>
