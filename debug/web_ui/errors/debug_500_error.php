<?php
/**
 * Debug script to catch 500 errors in admin_users.php
 */

// Enable all error reporting
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

echo "<h3>Debug: Admin Users 500 Error Investigation</h3>\n";

try {
    echo "<p>1. Testing UserAuthDAO inclusion...</p>\n";
    require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/UserAuthDAO.php';
    echo "<p>✓ UserAuthDAO included successfully</p>\n";
    
    echo "<p>2. Testing autoloader inclusion...</p>\n";
    require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'../src/Ksfraser/UIRenderer/autoload.php';
    echo "<p>✓ Autoloader included successfully</p>\n";
    
    echo "<p>3. Testing namespace import...</p>\n";
    // Need to import after autoloader
    if (class_exists('Ksfraser\User\Controllers\UserManagementController')) {
        echo "<p>✓ UserManagementController class exists</p>\n";
    } else {
        echo "<p>✗ UserManagementController class NOT found</p>\n";
    }
    
    echo "<p>4. Testing UserAuthDAO instantiation...</p>\n";
    $userAuth = new UserAuthDAO();
    echo "<p>✓ UserAuthDAO created successfully</p>\n";
    
    echo "<p>5. Testing UserManagementController instantiation...</p>\n";
    $controller = new Ksfraser\User\Controllers\UserManagementController($userAuth);
    echo "<p>✓ UserManagementController created successfully</p>\n";
    
    echo "<p>6. Testing page rendering...</p>\n";
    $output = $controller->renderPage();
    echo "<p>✓ Page rendered successfully. Output length: " . strlen($output) . " characters</p>\n";
    
    // Show first 500 characters of output
    echo "<h4>First 500 characters of rendered output:</h4>\n";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>\n";
    
} catch (Throwable $e) {
    echo "<h4 style='color: red;'>ERROR CAUGHT:</h4>\n";
    echo "<p><strong>Type:</strong> " . get_class($e) . "</p>\n";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><strong>File:</strong> " . $e->getFile() . "</p>\n";
    echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>\n";
    echo "<h5>Stack Trace:</h5>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
?>
