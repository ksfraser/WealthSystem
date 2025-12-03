<?php
/**
 * Test AuthDatabaseConfig directly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test AuthDatabaseConfig</h1>";
echo "<style>body { font-family: monospace; } .success { color: green; } .error { color: red; } pre { background: #f0f0f0; padding: 10px; }</style>";

// Step 1: Load DbConfigClasses
echo "<h2>Step 1: Load DbConfigClasses.php</h2>";
try {
    require_once __DIR__ . '/DbConfigClasses.php';
    echo "<div class='success'>✅ DbConfigClasses.php loaded</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    exit;
}

// Step 2: Check if AuthDatabaseConfig class exists
echo "<h2>Step 2: Check AuthDatabaseConfig Class</h2>";
if (class_exists('AuthDatabaseConfig')) {
    echo "<div class='success'>✅ AuthDatabaseConfig class exists</div>";
} else {
    echo "<div class='error'>❌ AuthDatabaseConfig class NOT found</div>";
    echo "Available classes: " . implode(', ', get_declared_classes()) . "<br>";
    exit;
}

// Step 3: Load YAML config
echo "<h2>Step 3: Load YAML Config</h2>";
try {
    $config = AuthDatabaseConfig::load();
    echo "<div class='success'>✅ Config loaded</div>";
    echo "<pre>";
    print_r($config);
    echo "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    exit;
}

// Step 4: Get parsed database config
echo "<h2>Step 4: Get Database Config</h2>";
try {
    $dbConfig = AuthDatabaseConfig::getConfig();
    echo "<div class='success'>✅ Database config parsed:</div>";
    echo "<pre>";
    print_r($dbConfig);
    echo "</pre>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    exit;
}

// Step 5: Create connection
echo "<h2>Step 5: Create Database Connection</h2>";
try {
    $pdo = AuthDatabaseConfig::createConnection();
    echo "<div class='success'>✅ Connection created!</div>";
    
    // Test query
    $stmt = $pdo->query("SELECT DATABASE() as db_name, COUNT(*) as user_count FROM users");
    $result = $stmt->fetch();
    echo "<div class='success'>✅ Connected to: " . $result['db_name'] . "</div>";
    echo "<div class='success'>✅ User count: " . $result['user_count'] . "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>❌ PDO Error: " . $e->getMessage() . "</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

// Step 6: Test with CommonDAO
echo "<h2>Step 6: Test CommonDAO with AuthDatabaseConfig</h2>";
try {
    require_once __DIR__ . '/CommonDAO.php';
    
    // Create a test class
    $testDAO = new class('AuthDatabaseConfig') extends CommonDAO {
        public function testConnection() {
            return $this->pdo !== null;
        }
        public function getConnectionErrors() {
            return $this->errors;
        }
    };
    
    if ($testDAO->testConnection()) {
        echo "<div class='success'>✅ CommonDAO connected successfully</div>";
        $pdo = $testDAO->getPdo();
        $stmt = $pdo->query("SELECT DATABASE() as db");
        $result = $stmt->fetch();
        echo "<div class='success'>✅ Database: " . $result['db'] . "</div>";
    } else {
        echo "<div class='error'>❌ CommonDAO connection failed</div>";
        $errors = $testDAO->getConnectionErrors();
        if (!empty($errors)) {
            echo "<div class='error'>Errors:</div><ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
