<?php
echo "<h2>MySQLi Connection Test</h2>";

try {
    require_once 'MySQLiDatabaseConfig.php';
    
    echo "<h3>Testing MySQLi Extension:</h3>";
    if (extension_loaded('mysqli')) {
        echo "<p style='color: green;'>✓ MySQLi extension is loaded</p>";
    } else {
        echo "<p style='color: red;'>✗ MySQLi extension is not loaded</p>";
        throw new Exception('MySQLi extension not available');
    }
    
    echo "<h3>Testing Database Connection:</h3>";
    $mysqli = MySQLiDatabaseConfig::createConnection();
    
    if ($mysqli) {
        echo "<p style='color: green;'>✓ MySQLi database connection successful!</p>";
        echo "<p>MySQL version: " . $mysqli->server_info . "</p>";
        echo "<p>Character set: " . $mysqli->character_set_name() . "</p>";
        
        // Test a simple query
        $result = MySQLiDatabaseConfig::query("SELECT DATABASE() as current_db, USER() as current_user");
        $row = $result->fetch_assoc();
        echo "<p>Current database: " . ($row['current_db'] ?? 'Unknown') . "</p>";
        echo "<p>Current user: " . ($row['current_user'] ?? 'Unknown') . "</p>";
        
        // Check if users table exists
        $result = MySQLiDatabaseConfig::query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows > 0) {
            echo "<p style='color: green;'>✓ Users table exists</p>";
            
            // Check table structure
            $result = MySQLiDatabaseConfig::query("DESCRIBE users");
            echo "<h4>Users table structure:</h4><ul>";
            while ($row = $result->fetch_assoc()) {
                echo "<li>{$row['Field']} - {$row['Type']}</li>";
            }
            echo "</ul>";
            
            // Count existing users
            $result = MySQLiDatabaseConfig::query("SELECT COUNT(*) as user_count FROM users");
            $row = $result->fetch_assoc();
            echo "<p>Existing users: " . ($row['user_count'] ?? 0) . "</p>";
            
        } else {
            echo "<p style='color: orange;'>⚠ Users table does not exist - needs to be created</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ MySQLi connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
