<?php
/**
 * Simple Database Connection Test - Plain text output
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Database Connection Test ===\n\n";

try {
    echo "Loading database configuration classes...\n";
    require_once __DIR__ . '/DbConfigClasses.php';
    echo "✅ Database classes loaded successfully\n\n";
    
    echo "Testing Legacy Database Connection...\n";
    
    try {
        $pdo = LegacyDatabaseConfig::createConnection();
        echo "✅ Legacy database connection successful!\n";
        
        // Test basic query
        $stmt = $pdo->query("SELECT VERSION() as mysql_version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "MySQL Version: " . $result['mysql_version'] . "\n";
        
        // Test if users table exists
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Users table exists\n";
                
                // Count users
                $stmt = $pdo->query("SELECT COUNT(*) as user_count FROM users");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "User count: " . $result['user_count'] . "\n";
                
                // Show first few users
                $stmt = $pdo->query("SELECT id, username, email, is_admin FROM users LIMIT 5");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($users) {
                    echo "Sample Users:\n";
                    foreach ($users as $user) {
                        echo sprintf("  ID: %s, Username: %s, Email: %s, Admin: %s\n", 
                            $user['id'], 
                            $user['username'], 
                            $user['email'], 
                            $user['is_admin'] ? 'Yes' : 'No'
                        );
                    }
                }
            } else {
                echo "❌ Users table does not exist\n";
            }
        } catch (Exception $e) {
            echo "❌ Error checking users table: " . $e->getMessage() . "\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Legacy database connection failed: " . $e->getMessage() . "\n";
        echo "Error details: " . $e->getFile() . ':' . $e->getLine() . "\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\nTesting UserAuthDAO...\n";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    echo "✅ UserAuthDAO created successfully\n";
    
    if ($userAuth->getPdo()) {
        echo "✅ UserAuthDAO has database connection\n";
    } else {
        echo "❌ UserAuthDAO has no database connection\n";
    }
    
    $errors = $userAuth->getErrors();
    if ($errors) {
        echo "UserAuthDAO errors:\n";
        foreach ($errors as $error) {
            echo "  - " . $error . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ UserAuthDAO error: " . $e->getMessage() . "\n";
    echo "Error details: " . $e->getFile() . ':' . $e->getLine() . "\n";
}

echo "\nTest complete.\n";
?>
