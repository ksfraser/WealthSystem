<?php
/**
 * Database Configuration Test Page
 */

echo "<h2>Database Configuration Test</h2>";

try {
    require_once 'DbConfigClasses.php';
    
    echo "<h3>Configuration Files Found:</h3>";
    $configFiles = [];
    if (file_exists(__DIR__ . '/db_config.yml')) $configFiles[] = 'db_config.yml';
    if (file_exists(__DIR__ . '/db_config.yaml')) $configFiles[] = 'db_config.yaml';
    if (file_exists(__DIR__ . '/db_config.ini')) $configFiles[] = 'db_config.ini';
    if (file_exists(__DIR__ . '/../db_config.yml')) $configFiles[] = '../db_config.yml';
    if (file_exists(__DIR__ . '/../db_config.yaml')) $configFiles[] = '../db_config.yaml';
    if (file_exists(__DIR__ . '/../db_config.ini')) $configFiles[] = '../db_config.ini';
    
    echo "<ul>";
    foreach ($configFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    
    echo "<h3>Testing LegacyDatabaseConfig:</h3>";
    $config = LegacyDatabaseConfig::getConfig();
    echo "<pre>";
    print_r($config);
    echo "</pre>";
    
    echo "<h3>Testing Database Connection:</h3>";
    $pdo = LegacyDatabaseConfig::createConnection();
    if ($pdo) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        echo "<p>PDO class: " . get_class($pdo) . "</p>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT DATABASE() as current_db");
        $result = $stmt->fetch();
        echo "<p>Current database: " . ($result['current_db'] ?? 'Unknown') . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed (null returned)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<details><summary>Stack trace</summary><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre></details>";
}
?>
<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>
