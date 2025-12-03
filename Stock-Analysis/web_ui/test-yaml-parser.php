<?php
/**
 * Test YAML parser and database connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing YAML Parser and Database Connection</h1>";

// Test 1: Load the config class
echo "<h2>Test 1: Load DbConfigClasses.php</h2>";
try {
    require_once __DIR__ . '/DbConfigClasses.php';
    echo "✅ DbConfigClasses.php loaded<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Try to load YAML config
echo "<h2>Test 2: Load YAML Config</h2>";
try {
    $config = LegacyDatabaseConfig::load();
    echo "✅ Config loaded successfully<br>";
    echo "<pre>";
    print_r($config);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
    exit;
}

// Test 3: Get parsed database config
echo "<h2>Test 3: Get Database Config</h2>";
try {
    $dbConfig = LegacyDatabaseConfig::getConfig();
    echo "✅ Database config parsed:<br>";
    echo "<pre>";
    print_r($dbConfig);
    echo "</pre>";
} catch (Exception $e) {
    echo "❌ Error getting config: " . $e->getMessage() . "<br>";
    exit;
}

// Test 4: Try to create connection
echo "<h2>Test 4: Create Database Connection</h2>";
try {
    $pdo = LegacyDatabaseConfig::createConnection();
    echo "✅ Connection created successfully!<br>";
    
    // Test query
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Test query successful: " . $result['test'] . "<br>";
    
} catch (PDOException $e) {
    echo "❌ PDO Error: " . $e->getMessage() . "<br>";
    echo "Error Code: " . $e->getCode() . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
