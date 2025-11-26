<?php
/**
 * Database Connection Test - Quick diagnostic tool
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Connection Test</title></head><body>";
echo "<h1>Database Connection Test</h1>";

try {
    echo "<h2>Loading Database Configuration Classes...</h2>";
    require_once __DIR__ . '/DbConfigClasses.php';
    echo "<p>✅ Database classes loaded successfully</p>";
    
    echo "<h2>Testing Legacy Database Connection...</h2>";
    
    try {
        $pdo = LegacyDatabaseConfig::createConnection();
        echo "<p>✅ Legacy database connection successful!</p>";
        
        // Test basic query
        $stmt = $pdo->query("SELECT VERSION() as mysql_version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>MySQL Version: " . htmlspecialchars($result['mysql_version']) . "</p>";
        
        // Test if users table exists
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo "<p>✅ Users table exists</p>";
                
                // Count users
                $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "<p>User count: " . $result['user_count'] . "</p>";
                
                // Show first few users
                $stmt = $pdo->query("SELECT id, username, email, is_admin FROM users LIMIT 5");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($users) {
                    echo "<h3>Sample Users:</h3>";
                    echo "<table border='1' style='border-collapse:collapse;'>";
                    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Is Admin</th></tr>";
                    foreach ($users as $user) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                        echo "<td>" . ($user['is_admin'] ? 'Yes' : 'No') . "</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
            } else {
                echo "<p>❌ Users table does not exist</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Error checking users table: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Legacy database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Error details: " . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "</p>";
    }
    
    echo "<h2>Testing Micro-Cap Database Connection...</h2>";
    
    try {
        $pdo = MicroCapDatabaseConfig::createConnection();
        echo "<p>✅ Micro-cap database connection successful!</p>";
        
        // Test basic query
        $stmt = $pdo->query("SELECT VERSION() as mysql_version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>MySQL Version: " . htmlspecialchars($result['mysql_version']) . "</p>";
        
        // Show tables
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables: " . implode(', ', $tables) . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Micro-cap database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Error details: " . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Fatal error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error details: " . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "</p>";
}

echo "<h2>Testing UserAuthDAO...</h2>";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    echo "<p>✅ UserAuthDAO created successfully</p>";
    
    if ($userAuth->getPdo()) {
        echo "<p>✅ UserAuthDAO has database connection</p>";
    } else {
        echo "<p>❌ UserAuthDAO has no database connection</p>";
    }
    
    // Test session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    echo "<p>Session status: " . session_status() . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    
    if ($userAuth->isLoggedIn()) {
        $user = $userAuth->getCurrentUser();
        echo "<p>✅ User is logged in: " . htmlspecialchars($user['username']) . "</p>";
        echo "<p>Is admin: " . ($userAuth->isAdmin() ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p>❌ No user is logged in</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ UserAuthDAO error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Error details: " . htmlspecialchars($e->getFile() . ':' . $e->getLine()) . "</p>";
}

echo "<p><a href='index.php'>Return to Dashboard</a></p>";
echo "</body></html>";
?>
