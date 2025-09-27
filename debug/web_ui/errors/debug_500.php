<?php
// Error checker for debugging HTTP 500 errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Debugging HTTP 500 Error</h1>\n";
echo "<h2>Testing Components...</h2>\n";

// Test 1: Basic PHP
echo "<p>✅ PHP is working</p>\n";

// Test 2: Check session
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p>✅ Session system working</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 3: Check auth_check.php
try {
    require_once 'auth_check.php';
    echo "<p>✅ auth_check.php loaded successfully</p>\n";
} catch (Exception $e) {
    echo "<p>❌ auth_check.php error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ auth_check.php fatal error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 4: Check nav_header.php
try {
    require_once 'nav_header.php';
    echo "<p>✅ nav_header.php loaded successfully</p>\n";
} catch (Exception $e) {
    echo "<p>❌ nav_header.php error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ nav_header.php fatal error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 5: Check database manager path
try {
    require_once '../src/Ksfraser/Database/EnhancedDbManager.php';
    require_once '../src/Ksfraser/Database/PdoConnection.php';
    require_once '../src/Ksfraser/Database/MysqliConnection.php';
    require_once '../src/Ksfraser/Database/PdoSqliteConnection.php';
    echo "<p>✅ All database classes loaded successfully</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Database classes error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ Database classes fatal error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 6: Check database connection
try {
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    echo "<p>✅ Database connection method called successfully</p>\n";
    
    if ($connection) {
        echo "<p>✅ Database connection successful</p>\n";
        
        // Test a simple query
        try {
            $stmt = $connection->prepare("SELECT 1 as test");
            $stmt->execute();
            echo "<p>✅ Database query test successful</p>\n";
        } catch (Exception $queryError) {
            echo "<p>⚠️ Database query test failed: " . htmlspecialchars($queryError->getMessage()) . "</p>\n";
        }
    } else {
        echo "<p>❌ Database connection returned null</p>\n";
    }
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Error $e) {
    echo "<p>❌ Database connection fatal error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<h2>Environment Info</h2>\n";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>\n";
echo "<p><strong>Current Directory:</strong> " . getcwd() . "</p>\n";
echo "<p><strong>Include Path:</strong> " . get_include_path() . "</p>\n";

echo "<h2>File Existence Check</h2>\n";
$files = [
    'auth_check.php',
    'nav_header.php',
    'UserAuthDAO.php',
    '../src/Ksfraser/Database/EnhancedDbManager.php',
    '../src/Ksfraser/Database/PdoConnection.php',
    '../src/Ksfraser/Database/MysqliConnection.php',
    '../src/Ksfraser/Database/PdoSqliteConnection.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ {$file} exists</p>\n";
    } else {
        echo "<p>❌ {$file} NOT FOUND</p>\n";
    }
}
?>
