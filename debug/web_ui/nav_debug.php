<?php
/**
 * Navigation Debug - Helps identify navigation issues
 */

// Start session for user checks
session_start();

echo "<!DOCTYPE html>\n<html><head><title>Navigation Debug</title></head><body>\n";
echo "<h1>Navigation Debug Information</h1>\n";

echo "<h2>Current Session Status</h2>\n";
echo "<pre>\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>\n";

echo "<h2>Authentication Status</h2>\n";
try {
    require_once 'UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    
    echo "<p>User logged in: " . ($userAuth->isLoggedIn() ? 'Yes' : 'No') . "</p>\n";
    
    if ($userAuth->isLoggedIn()) {
        echo "<p>User is admin: " . ($userAuth->isAdmin() ? 'Yes' : 'No') . "</p>\n";
        $currentUser = $userAuth->getCurrentUser();
        if ($currentUser) {
            echo "<p>Current user: " . htmlspecialchars($currentUser['username']) . "</p>\n";
            echo "<p>User ID: " . $currentUser['id'] . "</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Authentication error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h2>QuickActions File Status</h2>\n";
require_once 'QuickActions.php';

// Get the actions using reflection
$reflection = new ReflectionClass('QuickActions');
$actionsProperty = $reflection->getProperty('actions');
$actionsProperty->setAccessible(true);
$actions = $actionsProperty->getValue();

echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Label</th><th>File</th><th>Exists</th><th>Test Link</th></tr>\n";

foreach ($actions as $action) {
    $filePath = 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'' . $action['href'];
    $exists = file_exists($filePath);
    $status = $exists ? '✅' : '❌';
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($action['label']) . "</td>";
    echo "<td>" . htmlspecialchars($action['href']) . "</td>";
    echo "<td>$status</td>";
    echo "<td><a href='" . htmlspecialchars($action['href']) . "' target='_blank'>Test</a></td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<h2>Server Information</h2>\n";
echo "<pre>\n";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Name: " . $_SERVER['SCRIPT_NAME'] . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "Current Directory: " . __DIR__ . "\n";
echo "</pre>\n";

echo "<h2>Quick Test Links</h2>\n";
echo "<div style='margin: 10px 0;'>\n";
foreach ($actions as $action) {
    echo "<a href='" . htmlspecialchars($action['href']) . "' style='display: inline-block; margin: 5px; padding: 8px 12px; background: #007cba; color: white; text-decoration: none; border-radius: 4px;'>" . htmlspecialchars($action['label']) . "</a>\n";
}
echo "</div>\n";

echo "</body></html>";
?>
