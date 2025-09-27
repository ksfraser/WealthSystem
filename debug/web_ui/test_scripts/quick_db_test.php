<?php
/**
 * Quick Database Connection Test - With timeout handling
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Quick Database Test ===\n\n";

// Test 1: Check if config file exists
$configFile = __DIR__ . '/../db_config.yml';
if (file_exists($configFile)) {
    echo "✅ Config file exists: $configFile\n";
} else {
    echo "❌ Config file missing: $configFile\n";
    exit(1);
}

// Test 2: Try to connect with timeout
try {
    echo "Testing database connection with timeout...\n";
    
    // Set short timeout to prevent hanging
    ini_set('default_socket_timeout', 3);
    ini_set('mysql.connect_timeout', 3);
    
    require_once __DIR__ . '/DbConfigClasses.php';
    
    // Get config directly
    $config = LegacyDatabaseConfig::getConfig();
    echo "Database host: " . $config['host'] . "\n";
    echo "Database name: " . $config['dbname'] . "\n";
    
    // Try connection
    $start = microtime(true);
    $pdo = LegacyDatabaseConfig::createConnection();
    $end = microtime(true);
    
    echo "✅ Database connection successful!\n";
    echo "Connection time: " . round(($end - $start), 2) . " seconds\n";
    
    // Quick query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Test query result: " . $result['test'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "This likely means the database server is not reachable.\n";
}

echo "\nTesting SessionManager...\n";

try {
    require_once __DIR__ . '/SessionManager.php';
    $sm = SessionManager::getInstance();
    
    if ($sm->isSessionActive()) {
        echo "✅ SessionManager initialized successfully\n";
    } else {
        echo "❌ SessionManager not active\n";
        $error = $sm->getInitializationError();
        if ($error) {
            echo "Error: $error\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ SessionManager error: " . $e->getMessage() . "\n";
}

echo "\nTest complete.\n";
?>
