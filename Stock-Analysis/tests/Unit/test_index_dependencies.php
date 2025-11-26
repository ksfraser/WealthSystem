<?php
// Test index.php execution step by step
echo "Testing index.php execution...\n";

// Step 1: Test session initialization
echo "\n1. Testing session initialization...\n";
require_once __DIR__ . '/SessionManager.php';

try {
    $sessionManager = SessionManager::getInstance();
    echo "✅ SessionManager loaded\n";
    
    if ($sessionManager->isSessionActive()) {
        echo "✅ Session is active\n";
    } else {
        $error = $sessionManager->getInitializationError();
        if ($error) {
            echo "⚠️ Session not active: " . $error . "\n";
        } else {
            echo "⚠️ Session not active (no error reported)\n";
        }
    }
} catch (Exception $e) {
    echo "❌ SessionManager failed: " . $e->getMessage() . "\n";
}

// Step 2: Test database connection
echo "\n2. Testing database connection...\n";
require_once __DIR__ . '/db.php';

try {
    // This should initialize the $pdo variable
    echo "✅ Database config loaded\n";
    
    if (isset($pdo) && $pdo instanceof PDO) {
        echo "✅ PDO object created\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            echo "✅ Database connection works\n";
        } else {
            echo "❌ Database query failed\n";
        }
    } else {
        echo "❌ PDO object not created\n";
    }
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Step 3: Test auth check
echo "\n3. Testing auth check...\n";
try {
    require_once __DIR__ . '/auth_check.php';
    echo "✅ Auth check loaded\n";
    
    if (isset($_SESSION['username'])) {
        echo "✅ User logged in: " . $_SESSION['username'] . "\n";
    } else {
        echo "⚠️ No user logged in\n";
    }
} catch (Exception $e) {
    echo "❌ Auth check failed: " . $e->getMessage() . "\n";
}

// Step 4: Test UI components
echo "\n4. Testing UI component initialization...\n";
try {
    require_once __DIR__ . '/UiRenderer.php';
    require_once __DIR__ . '/MenuService.php';
    echo "✅ UI components loaded\n";
} catch (Exception $e) {
    echo "❌ UI component loading failed: " . $e->getMessage() . "\n";
}

echo "\n✅ All index.php dependencies tested!\n";
?>
