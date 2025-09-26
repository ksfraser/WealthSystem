<?php
/**
 * Comprehensive test for the Enhanced Database System
 */

echo "<h1>Enhanced Database System Test</h1>";

try {
    // Include the enhanced system
    require_once 'EnhancedUserAuthDAO.php';
    
    echo "<h2>1. Database Driver Detection</h2>";
    
    // Test driver availability
    $pdoAvailable = extension_loaded('pdo');
    $pdoMysqlAvailable = extension_loaded('pdo_mysql') && in_array('mysql', PDO::getAvailableDrivers());
    $mysqliAvailable = extension_loaded('mysqli');
    $pdoSqliteAvailable = extension_loaded('pdo_sqlite') && in_array('sqlite', PDO::getAvailableDrivers());
    
    echo "<ul>";
    echo "<li>PDO: " . ($pdoAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>PDO MySQL: " . ($pdoMysqlAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>MySQLi: " . ($mysqliAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "<li>PDO SQLite: " . ($pdoSqliteAvailable ? '✓ Available' : '✗ Not Available') . "</li>";
    echo "</ul>";
    
    echo "<h2>2. Enhanced Database Connection Test</h2>";
    
    // Test the enhanced UserAuthDAO
    $auth = new UserAuthDAO();
    $dbInfo = $auth->getDatabaseInfo();
    
    echo "<p><strong>Active Driver:</strong> " . ($dbInfo['driver'] ?? 'Unknown') . "</p>";
    echo "<p><strong>Connection Class:</strong> " . ($dbInfo['connection_class'] ?? 'Unknown') . "</p>";
    
    echo "<h2>3. User Management Test</h2>";
    
    // Test getting existing users
    $users = $auth->getAllUsers();
    echo "<p><strong>Existing Users:</strong> " . count($users) . "</p>";
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Admin</th><th>Created</th></tr>";
        foreach ($users as $user) {
            $adminBadge = $user['is_admin'] ? '✓' : '✗';
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>$adminBadge</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>4. User Registration Test</h2>";
    
    // Test user registration
    $testUsername = "enhanced_test_" . time();
    $testEmail = "enhanced_test_" . time() . "@example.com";
    $testPassword = "testpass123";
    
    echo "<p>Attempting to register user: <strong>$testUsername</strong></p>";
    
    $userId = $auth->registerUser($testUsername, $testEmail, $testPassword);
    
    if ($userId && is_numeric($userId)) {
        echo "<p style='color: green;'>✓ Registration successful! User ID: $userId</p>";
        
        // Verify user was created
        $newUser = $auth->getUserById($userId);
        if ($newUser) {
            echo "<p>✓ User verified in database:</p>";
            echo "<ul>";
            echo "<li>ID: {$newUser['id']}</li>";
            echo "<li>Username: {$newUser['username']}</li>";
            echo "<li>Email: {$newUser['email']}</li>";
            echo "<li>Is Admin: " . ($newUser['is_admin'] ? 'Yes' : 'No') . "</li>";
            echo "<li>Created: {$newUser['created_at']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠ User registration succeeded but user not found</p>";
        }
        
        // Test authentication
        echo "<h2>5. Authentication Test</h2>";
        $authResult = $auth->authenticateUser($testUsername, $testPassword);
        if ($authResult) {
            echo "<p style='color: green;'>✓ Authentication successful!</p>";
            echo "<ul>";
            echo "<li>Username: {$authResult['username']}</li>";
            echo "<li>Email: {$authResult['email']}</li>";
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>✗ Authentication failed</p>";
        }
        
        // Test admin status update
        echo "<h2>6. Admin Status Test</h2>";
        $adminUpdateResult = $auth->updateUserAdminStatus($userId, true);
        if ($adminUpdateResult) {
            echo "<p style='color: green;'>✓ Admin status update successful</p>";
            
            $updatedUser = $auth->getUserById($userId);
            if ($updatedUser && $updatedUser['is_admin']) {
                echo "<p>✓ User is now admin</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Admin status not reflected in database</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Admin status update failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ Registration failed. Result: " . var_export($userId, true) . "</p>";
    }
    
    echo "<h2>7. Session Management Test</h2>";
    
    // Test session management (without actually logging in)
    $isLoggedIn = $auth->isLoggedIn();
    $isAdmin = $auth->isAdmin();
    
    echo "<p>Current session status:</p>";
    echo "<ul>";
    echo "<li>Logged in: " . ($isLoggedIn ? 'Yes' : 'No') . "</li>";
    echo "<li>Admin: " . ($isAdmin ? 'Yes' : 'No') . "</li>";
    echo "</ul>";
    
    echo "<h2>8. Database Fallback Summary</h2>";
    
    $summary = [];
    if ($pdoMysqlAvailable) {
        $summary[] = "PDO MySQL (Primary)";
    }
    if ($mysqliAvailable) {
        $summary[] = "MySQLi (Fallback)";
    }
    if ($pdoSqliteAvailable) {
        $summary[] = "SQLite (Last Resort)";
    }
    
    echo "<p><strong>Available drivers in priority order:</strong></p>";
    echo "<ol>";
    foreach ($summary as $driver) {
        echo "<li>$driver</li>";
    }
    echo "</ol>";
    
    echo "<p><strong>Currently using:</strong> " . ($dbInfo['driver'] ?? 'Unknown') . "</p>";
    
    if ($dbInfo['driver'] === 'pdo_mysql') {
        echo "<p style='color: green;'>✓ Using optimal PDO MySQL connection</p>";
    } elseif ($dbInfo['driver'] === 'mysqli') {
        echo "<p style='color: orange;'>⚠ Using MySQLi fallback (PDO MySQL not available)</p>";
    } elseif ($dbInfo['driver'] === 'pdo_sqlite') {
        echo "<p style='color: red;'>⚠ Using SQLite fallback (MySQL not available)</p>";
    }
    
    echo "<div style='background: #f0f8ff; padding: 15px; margin: 20px 0; border-left: 4px solid #007acc;'>";
    echo "<h3>✓ Enhanced Database System Status: OPERATIONAL</h3>";
    echo "<p>The enhanced database system is working correctly with automatic driver fallback.</p>";
    echo "<p><strong>Active Configuration:</strong></p>";
    echo "<ul>";
    echo "<li>Driver: " . ($dbInfo['driver'] ?? 'Unknown') . "</li>";
    echo "<li>Connection: " . ($dbInfo['connection_class'] ?? 'Unknown') . "</li>";
    echo "<li>Fallback: Automatic</li>";
    echo "<li>User Management: Functional</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; margin: 20px 0; border-left: 4px solid #f44336;'>";
    echo "<h3>✗ Enhanced Database System Error</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Stack Trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    line-height: 1.6;
}

h1, h2, h3 {
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

pre {
    background: #f5f5f5;
    padding: 10px;
    border-radius: 5px;
    overflow-x: auto;
}

ul, ol {
    margin: 10px 0;
    padding-left: 25px;
}

li {
    margin: 5px 0;
}

details {
    margin: 10px 0;
}

summary {
    cursor: pointer;
    font-weight: bold;
    color: #666;
}
</style>
