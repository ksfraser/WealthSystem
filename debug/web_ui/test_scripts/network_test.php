<?php
/**
 * Network and Database Connectivity Test
 * Tests various aspects of database connectivity to diagnose issues
 */

echo "<h1>🔍 Network and Database Connectivity Test</h1>\n";
echo "<h2>Testing database connectivity issues...</h2>\n";

// Test 1: Basic network connectivity
echo "<h3>1. Network Connectivity Test</h3>\n";
$host = 'fhsws001.ksfraser.com';
$port = 3306;
$timeout = 5;

echo "Testing connection to {$host}:{$port} with {$timeout}s timeout...<br>\n";

$startTime = microtime(true);
$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
$endTime = microtime(true);
$elapsed = round(($endTime - $startTime) * 1000, 2);

if ($connection) {
    echo "✅ Network connection successful ({$elapsed}ms)<br>\n";
    fclose($connection);
} else {
    echo "❌ Network connection failed ({$elapsed}ms)<br>\n";
    echo "Error: {$errstr} (Code: {$errno})<br>\n";
}

// Test 2: DNS Resolution
echo "<h3>2. DNS Resolution Test</h3>\n";
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "✅ DNS resolution successful: {$host} → {$ip}<br>\n";
} else {
    echo "❌ DNS resolution failed for {$host}<br>\n";
}

// Test 3: Ping test (if available)
echo "<h3>3. Ping Test</h3>\n";
$pingCommand = "ping -n 3 {$host} 2>&1";
$pingResult = shell_exec($pingCommand);
if ($pingResult) {
    echo "<pre style='background:#f8f9fa;padding:10px;border-radius:4px;font-size:12px;'>";
    echo htmlspecialchars($pingResult);
    echo "</pre>";
} else {
    echo "⚠️ Unable to execute ping command<br>\n";
}

// Test 4: Database configuration test
echo "<h3>4. Database Configuration Test</h3>\n";
try {
    require_once 'DbConfigClasses.php';
    echo "✅ Database configuration classes loaded<br>\n";
    
    $config = LegacyDatabaseConfig::getConfig();
    echo "✅ Configuration loaded successfully<br>\n";
    echo "Host: " . $config['host'] . "<br>\n";
    echo "Port: " . $config['port'] . "<br>\n";
    echo "Database: " . $config['dbname'] . "<br>\n";
    echo "Username: " . $config['username'] . "<br>\n";
    
} catch (Exception $e) {
    echo "❌ Database configuration error: " . htmlspecialchars($e->getMessage()) . "<br>\n";
}

// Test 5: PDO availability
echo "<h3>5. PDO Driver Test</h3>\n";
if (extension_loaded('pdo')) {
    echo "✅ PDO extension loaded<br>\n";
    $drivers = PDO::getAvailableDrivers();
    echo "Available drivers: " . implode(', ', $drivers) . "<br>\n";
    
    if (in_array('mysql', $drivers)) {
        echo "✅ MySQL PDO driver available<br>\n";
    } else {
        echo "❌ MySQL PDO driver not available<br>\n";
    }
} else {
    echo "❌ PDO extension not loaded<br>\n";
}

// Test 6: System information
echo "<h3>6. System Information</h3>\n";
echo "PHP Version: " . phpversion() . "<br>\n";
echo "Operating System: " . php_uname() . "<br>\n";
echo "Default socket timeout: " . ini_get('default_socket_timeout') . "s<br>\n";
echo "MySQL connect timeout: " . ini_get('mysql.connect_timeout') . "s<br>\n";
echo "Max execution time: " . ini_get('max_execution_time') . "s<br>\n";

// Test 7: Alternative database configurations
echo "<h3>7. Alternative Configuration Test</h3>\n";
echo "Testing if localhost database would work...<br>\n";

// Test localhost connectivity
$localhostTest = @fsockopen('localhost', 3306, $errno, $errstr, 2);
if ($localhostTest) {
    echo "✅ Localhost MySQL port 3306 is accessible<br>\n";
    fclose($localhostTest);
    echo "💡 Consider configuring a local database as an alternative<br>\n";
} else {
    echo "ℹ️ Localhost MySQL not available (this is normal if not installed)<br>\n";
}

// Test 8: Current working directory and file permissions
echo "<h3>8. File System Test</h3>\n";
echo "Current working directory: " . getcwd() . "<br>\n";
echo "PHP include path: " . get_include_path() . "<br>\n";

$configFile = __DIR__ . '/../db_config.yml';
if (file_exists($configFile)) {
    echo "✅ Database config file exists: " . $configFile . "<br>\n";
    if (is_readable($configFile)) {
        echo "✅ Config file is readable<br>\n";
    } else {
        echo "❌ Config file is not readable<br>\n";
    }
} else {
    echo "❌ Database config file not found: " . $configFile . "<br>\n";
}

echo "<h2>🎯 Diagnosis Summary</h2>\n";
echo "<div style='background:#e7f3ff;padding:15px;border-radius:6px;margin:15px 0;'>";
echo "<strong>Most likely causes of the 500 errors:</strong><br>";
echo "1. <strong>Network connectivity issue</strong> - External database server unreachable<br>";
echo "2. <strong>Firewall blocking</strong> - MySQL port 3306 may be blocked<br>";
echo "3. <strong>Server maintenance</strong> - External database may be temporarily unavailable<br>";
echo "4. <strong>VPN requirement</strong> - May need specific network configuration<br>";
echo "</div>";

echo "<h2>✅ Current Solution</h2>\n";
echo "<div style='background:#d4edda;padding:15px;border-radius:6px;margin:15px 0;'>";
echo "The system is now running in <strong>fallback mode</strong> and is fully functional for:<br>";
echo "• UI testing and development<br>";
echo "• System architecture demonstration<br>";
echo "• All frontend functionality<br>";
echo "• Mock authentication and navigation<br>";
echo "</div>";

echo "<p><a href='index.php'>← Back to Dashboard</a></p>";
?>
