<?php
/**
 * Comprehensive Database and Registration Diagnostic
 */

// Start output buffering to capture all output
ob_start();

echo "=== Database and Registration Diagnostic ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Check configuration files
echo "1. Configuration Files Check:\n";
$configFiles = [
    __DIR__ . '/db_config.yml' => 'web_ui/db_config.yml',
    __DIR__ . '/../db_config.yml' => 'db_config.yml',
    __DIR__ . '/db_config.ini' => 'web_ui/db_config.ini',
    __DIR__ . '/../db_config.ini' => 'db_config.ini'
];

foreach ($configFiles as $path => $label) {
    if (file_exists($path)) {
        echo "   ✓ Found: $label\n";
        $size = filesize($path);
        echo "     Size: $size bytes\n";
    } else {
        echo "   ✗ Missing: $label\n";
    }
}
echo "\n";

// Test 2: Load configuration classes
echo "2. Loading Configuration Classes:\n";
try {
    require_once __DIR__ . '/DbConfigClasses.php';
    echo "   ✓ DbConfigClasses.php loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ Failed to load DbConfigClasses.php: " . $e->getMessage() . "\n";
    $output = ob_get_clean();
    file_put_contents(__DIR__ . '/diagnostic_output.txt', $output);
    exit;
}

// Test 3: Test configuration loading
echo "\n3. Configuration Loading Test:\n";
try {
    $config = LegacyDatabaseConfig::load();
    echo "   ✓ Configuration loaded successfully\n";
    echo "   Config structure:\n";
    echo "   " . str_replace("\n", "\n   ", print_r($config, true)) . "\n";
} catch (Exception $e) {
    echo "   ✗ Configuration loading failed: " . $e->getMessage() . "\n";
}

// Test 4: Get processed config
echo "\n4. Processed Configuration Test:\n";
try {
    $processedConfig = LegacyDatabaseConfig::getConfig();
    echo "   ✓ Processed configuration:\n";
    foreach ($processedConfig as $key => $value) {
        if ($key === 'password') {
            echo "     $key: [HIDDEN]\n";
        } else {
            echo "     $key: $value\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Processed configuration failed: " . $e->getMessage() . "\n";
}

// Test 5: Database connection
echo "\n5. Database Connection Test:\n";
try {
    $pdo = LegacyDatabaseConfig::createConnection();
    if ($pdo) {
        echo "   ✓ Database connection successful\n";
        echo "   PDO class: " . get_class($pdo) . "\n";
        
        // Test query
        $stmt = $pdo->query("SELECT DATABASE() as current_db, USER() as current_user, VERSION() as mysql_version");
        $result = $stmt->fetch();
        echo "   Current database: " . ($result['current_db'] ?? 'Unknown') . "\n";
        echo "   Current user: " . ($result['current_user'] ?? 'Unknown') . "\n";
        echo "   MySQL version: " . ($result['mysql_version'] ?? 'Unknown') . "\n";
        
    } else {
        echo "   ✗ Database connection returned null\n";
    }
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "   Error code: " . $e->getCode() . "\n";
}

// Test 6: UserAuthDAO loading
echo "\n6. UserAuthDAO Loading Test:\n";
try {
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "   ✓ UserAuthDAO.php loaded successfully\n";
    
    $auth = new UserAuthDAO();
    echo "   ✓ UserAuthDAO instance created\n";
    
} catch (Exception $e) {
    echo "   ✗ UserAuthDAO loading failed: " . $e->getMessage() . "\n";
}

// Test 7: User listing
echo "\n7. User Listing Test:\n";
try {
    $users = $auth->getAllUsers();
    echo "   ✓ getAllUsers() successful\n";
    echo "   Found " . count($users) . " users\n";
    
    if (count($users) > 0) {
        echo "   Users:\n";
        foreach ($users as $user) {
            $adminBadge = $user['is_admin'] ? ' [ADMIN]' : '';
            echo "     - ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}$adminBadge\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ User listing failed: " . $e->getMessage() . "\n";
}

// Test 8: User registration
echo "\n8. User Registration Test:\n";
try {
    $testUsername = "diagnostic_user_" . time();
    $testEmail = "diagnostic" . time() . "@example.com";
    $testPassword = "testpass123";
    
    echo "   Attempting to register user: $testUsername\n";
    
    $userId = $auth->registerUser($testUsername, $testEmail, $testPassword);
    
    if ($userId && is_numeric($userId)) {
        echo "   ✓ Registration successful! User ID: $userId\n";
        
        // Verify user was created
        $users = $auth->getAllUsers();
        $found = false;
        foreach ($users as $user) {
            if ($user['id'] == $userId) {
                $found = true;
                echo "   ✓ User verified in database\n";
                echo "     Username: {$user['username']}\n";
                echo "     Email: {$user['email']}\n";
                echo "     Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "\n";
                break;
            }
        }
        
        if (!$found) {
            echo "   ⚠ User registration reported success but user not found\n";
        }
        
    } else {
        echo "   ✗ Registration failed. Result: " . var_export($userId, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Registration test failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo "   " . str_replace("\n", "\n   ", $e->getTraceAsString()) . "\n";
}

echo "\n=== End of Diagnostic ===\n";

// Save output to file
$output = ob_get_clean();
file_put_contents(__DIR__ . '/diagnostic_output.txt', $output);

// Also echo for web display
echo "<pre>" . htmlspecialchars($output) . "</pre>";
?>
