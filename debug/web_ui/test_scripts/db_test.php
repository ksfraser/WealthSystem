<?php
/**
 * PHP Database Connection Test
 * Tests database connectivity using the same configuration as Python scripts
 */

// Function to parse YAML configuration (simple parser for our needs)
function parseDbConfig($configFile) {
    if (!file_exists($configFile)) {
        return null;
    }
    
    $content = file_get_contents($configFile);
    $lines = explode("\n", $content);
    $config = [];
    $inDatabase = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === 'database:') {
            $inDatabase = true;
            continue;
        }
        
        if ($inDatabase) {
            if (strpos($line, 'host:') === 0) {
                $config['host'] = trim(str_replace('host:', '', $line));
            } elseif (strpos($line, 'port:') === 0) {
                $config['port'] = trim(str_replace('port:', '', $line));
            } elseif (strpos($line, 'username:') === 0) {
                $config['username'] = trim(str_replace('username:', '', $line));
            } elseif (strpos($line, 'password:') === 0) {
                $config['password'] = trim(str_replace('password:', '', $line));
            } elseif (strpos($line, 'database:') !== false && strpos($line, 'database:') === 0) {
                // Skip nested database entries
                continue;
            } elseif ($line && !preg_match('/^\s/', $line) && $line !== 'database:') {
                // End of database section
                break;
            }
        }
    }
    
    return $config;
}

// Test database connections
echo "<h2>PHP Database Connection Test</h2>\n";
echo "<p>Testing connectivity using configuration from YAML files...</p>\n";

// Try to load configuration
$configs = [
    '../db_config.yml',
    '../db_config_refactored.yml'
];

$dbConfig = null;
$configUsed = '';

foreach ($configs as $configFile) {
    $dbConfig = parseDbConfig($configFile);
    if ($dbConfig && isset($dbConfig['host'])) {
        $configUsed = $configFile;
        break;
    }
}

if (!$dbConfig) {
    echo "<p style='color: red;'>❌ Could not load database configuration from YAML files</p>";
    echo "<p>Please ensure db_config_refactored.yml exists with proper database configuration.</p>";
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 10px 0; color: #721c24;'>";
    echo "<h4>Configuration Required</h4>";
    echo "<p>The database configuration file is missing. Expected location: <code>../db_config_refactored.yml</code></p>";
    echo "<p>This file should contain database connection details including host, username, password, and port.</p>";
    echo "</div>";
    return;
}

echo "<p><strong>Configuration loaded from:</strong> {$configUsed}</p>";
echo "<p><strong>Host:</strong> {$dbConfig['host']}</p>";
echo "<p><strong>Username:</strong> {$dbConfig['username']}</p>";
echo "<p><strong>Port:</strong> " . ($dbConfig['port'] ?? '3306') . "</p>";

// Test databases
$databases = [
    ['name' => 'Master Database (stock_market_2)', 'db' => 'stock_market_2'],
    ['name' => 'Micro-cap Database', 'db' => 'stock_market_micro_cap_trading']
];

echo "<h3>Connection Test Results:</h3>";

foreach ($databases as $database) {
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";
    echo "<strong>{$database['name']}</strong><br>";
    
    try {
        $host = $dbConfig['host'];
        $port = $dbConfig['port'] ?? '3306';
        $username = $dbConfig['username'];
        $password = $dbConfig['password'];
        $dbname = $database['db'];
        
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname}";
        echo "DSN: {$dsn}<br>";
        
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]);
        
        // Get server info
        $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        
        // Count tables
        $stmt = $pdo->query("SHOW TABLES");
        $tableCount = $stmt->rowCount();
        
        echo "<span style='color: green;'>✅ Connection successful!</span><br>";
        echo "Server Version: {$version}<br>";
        echo "Tables: {$tableCount}<br>";
        
    } catch(PDOException $e) {
        echo "<span style='color: red;'>❌ Connection failed!</span><br>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "Error Code: " . $e->getCode() . "<br>";
    }
    
    echo "</div>";
}

// Additional network test
echo "<h3>Network Connectivity Test:</h3>";
echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px;'>";

$host = $dbConfig['host'];
$port = $dbConfig['port'] ?? '3306';

echo "<strong>Testing network connection to {$host}:{$port}</strong><br>";

$socket = @fsockopen($host, $port, $errno, $errstr, 10);
if ($socket) {
    echo "<span style='color: green;'>✅ Network connection successful</span><br>";
    fclose($socket);
} else {
    echo "<span style='color: red;'>❌ Network connection failed</span><br>";
    echo "Error: {$errstr} (Code: {$errno})<br>";
}

echo "</div>";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
