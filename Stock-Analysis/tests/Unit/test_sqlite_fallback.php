<?php
/**
 * Quick test of Enhanced Database Manager SQLite fallback
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Testing Enhanced Database Manager SQLite fallback...\n";

try {
    require_once __DIR__ . '/../src/Ksfraser/Database/EnhancedDbManager.php';
    require_once __DIR__ . '/../src/Ksfraser/Database/PdoConnection.php';
    require_once __DIR__ . '/../src/Ksfraser/Database/MysqliConnection.php';
    require_once __DIR__ . '/../src/Ksfraser/Database/PdoSqliteConnection.php';
    
    echo "Classes loaded successfully.\n";
    
    // Override the driver priority to force SQLite
    \Ksfraser\Database\EnhancedDbManager::resetConnection();
    
    // Create a mock config with invalid MySQL settings to force SQLite
    $_ENV['DB_HOST'] = 'invalid_host';
    $_ENV['DB_USERNAME'] = 'invalid';
    $_ENV['DB_PASSWORD'] = 'invalid';
    $_ENV['DB_DATABASE'] = 'invalid';
    
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    $driver = \Ksfraser\Database\EnhancedDbManager::getCurrentDriver();
    
    echo "Connection successful with driver: $driver\n";
    
    // Test a simple query
    $result = \Ksfraser\Database\EnhancedDbManager::fetchValue("SELECT 1 as test");
    echo "Test query result: $result\n";
    
    echo "SUCCESS: Enhanced database manager is working with SQLite fallback!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
