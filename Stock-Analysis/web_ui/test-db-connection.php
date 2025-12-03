<?php
/**
 * Database Connection Diagnostic
 * 
 * Tests database connectivity and displays detailed information
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        h1 { color: #333; }
        pre { background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Database Connection Diagnostic</h1>
";

// Test 1: Check if config file exists
echo "<h2>Test 1: Configuration Files</h2>";

$configPaths = [
    __DIR__ . '/includes/config.php',
    __DIR__ . '/config/database.php',
    __DIR__ . '/../config/database.php',
    __DIR__ . '/database.php'
];

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        echo "<div class='test-result success'>✅ Found: " . htmlspecialchars($path) . "</div>";
    } else {
        echo "<div class='test-result info'>ℹ️ Not found: " . htmlspecialchars($path) . "</div>";
    }
}

// Test 2: Try to load UserAuthDAO
echo "<h2>Test 2: UserAuthDAO Connection</h2>";
try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $auth = new UserAuthDAO();
    echo "<div class='test-result success'>✅ UserAuthDAO instantiated successfully</div>";
    
    // Check if it has a database connection method
    $methods = get_class_methods($auth);
    echo "<div class='test-result info'>Available methods: " . implode(', ', array_slice($methods, 0, 10)) . "...</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Test 3: Check PDO availability
echo "<h2>Test 3: PDO Extensions</h2>";
$pdoDrivers = PDO::getAvailableDrivers();
if (empty($pdoDrivers)) {
    echo "<div class='test-result error'>❌ No PDO drivers available!</div>";
} else {
    echo "<div class='test-result success'>✅ Available PDO drivers: " . implode(', ', $pdoDrivers) . "</div>";
}

// Test 4: Check MySQL connection (if credentials available)
echo "<h2>Test 4: Direct MySQL Connection Test</h2>";

// Try to find database credentials - check YAML files first
$dbConfig = null;
$yamlPaths = [
    __DIR__ . '/../../db_config_simple.yml',
    __DIR__ . '/../../db_config.yml'
];

foreach ($yamlPaths as $path) {
    if (file_exists($path)) {
        try {
            $content = file_get_contents($path);
            
            // Parse YAML format (simple key: value pairs)
            $config = [];
            if (preg_match('/^\s*host:\s*([^\n\r]+)/m', $content, $matches)) {
                $config['host'] = trim($matches[1]);
            }
            if (preg_match('/^\s*username:\s*([^\n\r]+)/m', $content, $matches)) {
                $config['user'] = trim($matches[1]);
            }
            if (preg_match('/^\s*password:\s*([^\n\r]+)/m', $content, $matches)) {
                $config['pass'] = trim($matches[1]);
            }
            // Check for database under micro_cap section
            if (preg_match('/micro_cap:\s*\n\s*database:\s*([^\n\r]+)/s', $content, $matches)) {
                $config['name'] = trim($matches[1]);
            }
            
            if (isset($config['host']) && isset($config['name'])) {
                $dbConfig = $config;
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }
}

// Fallback to PHP config files if YAML not found
if (!$dbConfig) {
    foreach ($configPaths as $path) {
        if (file_exists($path)) {
            try {
                $content = file_get_contents($path);
                
                // Try to extract database config from PHP files
                if (preg_match('/db_host.*?[\'"]([^\'"]+)/', $content, $matches)) {
                    $dbConfig = ['host' => $matches[1]];
                }
                if (preg_match('/db_name.*?[\'"]([^\'"]+)/', $content, $matches)) {
                    $dbConfig['name'] = $matches[1] ?? '';
                }
                if (preg_match('/db_user.*?[\'"]([^\'"]+)/', $content, $matches)) {
                    $dbConfig['user'] = $matches[1] ?? 'root';
                }
                if (preg_match('/db_password.*?[\'"]([^\'"]+)/', $content, $matches)) {
                    $dbConfig['pass'] = $matches[1] ?? '';
                }
                
                if (isset($dbConfig['host']) && isset($dbConfig['name'])) {
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
}

if ($dbConfig) {
    echo "<div class='test-result info'>✅ Found database configuration</div>";
    echo "<pre>Host: " . htmlspecialchars($dbConfig['host']) . "\n";
    echo "Database: " . htmlspecialchars($dbConfig['name']) . "\n";
    echo "User: " . htmlspecialchars($dbConfig['user']) . "</pre>";
    
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "<div class='test-result success'>✅ MySQL connection successful!</div>";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT VERSION() as version");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<div class='test-result success'>MySQL Version: " . htmlspecialchars($result['version']) . "</div>";
        
    } catch (PDOException $e) {
        echo "<div class='test-result error'>❌ MySQL connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
} else {
    echo "<div class='test-result info'>ℹ️ Could not find database configuration in standard locations</div>";
}

// Test 5: Session status
echo "<h2>Test 5: Session Information</h2>";
echo "<div class='test-result info'>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</div>";
echo "<div class='test-result info'>PHP Version: " . PHP_VERSION . "</div>";

echo "</body></html>";
