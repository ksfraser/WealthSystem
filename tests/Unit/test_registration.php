<?php
/**
 * Simple Registration Test
 */

echo "<h2>User Registration Test</h2>";

require_once 'UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    
    echo "<h3>Testing Database Connectivity</h3>";
    
    // First test - can we get existing users?
    $users = $auth->getAllUsers();
    echo "<p>✓ Database connection working. Found " . count($users) . " existing users.</p>";
    
    if (count($users) > 0) {
        echo "<h4>Existing Users:</h4><ul>";
        foreach ($users as $user) {
            $adminBadge = $user['is_admin'] ? ' <span style="color: orange;">[ADMIN]</span>' : '';
            echo "<li>ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}$adminBadge</li>";
        }
        echo "</ul>";
    }
    
    echo "<h3>Testing User Registration</h3>";
    
    // Generate unique test user
    $testUsername = "testuser_" . time();
    $testEmail = "test" . time() . "@example.com";
    $testPassword = "testpass123";
    
    echo "<p>Attempting to register:</p>";
    echo "<ul>";
    echo "<li>Username: $testUsername</li>";
    echo "<li>Email: $testEmail</li>";
    echo "<li>Password: [hidden]</li>";
    echo "</ul>";
    
    $userId = $auth->registerUser($testUsername, $testEmail, $testPassword);
    
    if ($userId && is_numeric($userId)) {
        echo "<p style='color: green;'>✓ Registration successful! User ID: $userId</p>";
        
        // Verify the user was created
        $users = $auth->getAllUsers();
        $found = false;
        foreach ($users as $user) {
            if ($user['id'] == $userId) {
                $found = true;
                echo "<p>✓ User verified in database:</p>";
                echo "<ul>";
                echo "<li>ID: {$user['id']}</li>";
                echo "<li>Username: {$user['username']}</li>";
                echo "<li>Email: {$user['email']}</li>";
                echo "<li>Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "</li>";
                echo "</ul>";
                break;
            }
        }
        
        if (!$found) {
            echo "<p style='color: orange;'>⚠ User registration reported success but user not found in database</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Registration failed. Result: " . htmlspecialchars(var_export($userId, true)) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Full Error Details</summary>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</details>";
}
?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
details { margin: 10px 0; }
summary { cursor: pointer; color: #666; }
</style>
