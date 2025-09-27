<?php
// Step-by-step diagnostic for logged-in admin user
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Database Diagnostic</title></head><body>";
echo "<h1>Database Page Diagnostic - Step by Step</h1>";

// Step 1: Basic setup
echo "<p><strong>Step 1:</strong> Basic PHP setup - ✅ OK</p>";

// Step 2: Session check
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "<p><strong>Step 2:</strong> Session started - ✅ OK</p>";
} catch (Exception $e) {
    echo "<p><strong>Step 2:</strong> Session error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Step 3: Auth check
try {
    require_once 'auth_check.php';
    echo "<p><strong>Step 3:</strong> Auth check loaded - ✅ OK</p>";
    
    $currentUser = getCurrentUser();
    echo "<p><strong>Step 3a:</strong> Current user: " . htmlspecialchars($currentUser['username']) . " - ✅ OK</p>";
    
    if (isCurrentUserAdmin()) {
        echo "<p><strong>Step 3b:</strong> Admin privileges confirmed - ✅ OK</p>";
    } else {
        echo "<p><strong>Step 3b:</strong> User is not admin - ⚠️ WARNING</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Step 3:</strong> Auth error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    exit("</body></html>");
}

// Step 4: Navigation header (this might be the issue)
try {
    echo "<p><strong>Step 4:</strong> About to load nav_header.php...</p>";
    require_once 'nav_header.php';
    echo "<p><strong>Step 4:</strong> Nav header loaded - ✅ OK</p>";
} catch (Exception $e) {
    echo "<p><strong>Step 4:</strong> Nav header error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    exit("</body></html>");
}

// Step 5: Database loader
try {
    echo "<p><strong>Step 5:</strong> About to load database classes...</p>";
    require_once 'database_loader.php';
    echo "<p><strong>Step 5:</strong> Database loader - ✅ OK</p>";
} catch (Exception $e) {
    echo "<p><strong>Step 5:</strong> Database loader error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
    exit("</body></html>");
}

// Step 6: Database connection
try {
    echo "<p><strong>Step 6:</strong> About to test database connection...</p>";
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    
    if ($connection) {
        echo "<p><strong>Step 6:</strong> Database connection successful - ✅ OK</p>";
        
        // Test query
        try {
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $row = $stmt->fetch();
            echo "<p><strong>Step 6a:</strong> Database query test - Count: " . $row['count'] . " - ✅ OK</p>";
        } catch (Exception $queryError) {
            echo "<p><strong>Step 6a:</strong> Database query error - ❌ " . htmlspecialchars($queryError->getMessage()) . "</p>";
        }
    } else {
        echo "<p><strong>Step 6:</strong> Database connection failed - ❌ No connection</p>";
    }
} catch (Exception $e) {
    echo "<p><strong>Step 6:</strong> Database connection error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Step 7: Try rendering navigation header
try {
    echo "<p><strong>Step 7:</strong> About to render navigation header...</p>";
    
    // Capture output to see if there are issues
    ob_start();
    renderNavigationHeader('Test Database Page');
    $navOutput = ob_get_clean();
    
    echo "<p><strong>Step 7:</strong> Navigation header rendered - ✅ OK (Length: " . strlen($navOutput) . " chars)</p>";
    
    // Actually output the navigation
    echo $navOutput;
    
} catch (Exception $e) {
    echo "<p><strong>Step 7:</strong> Navigation render error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Error $e) {
    echo "<p><strong>Step 7:</strong> Navigation render fatal error - ❌ " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>✅ All diagnostic steps completed successfully!</h2>";
echo "<p>If you see this message, all components are working. The issue with database.php might be elsewhere.</p>";
echo "<p><a href='database.php'>Try database.php again</a> | <a href='index.php'>Go to Dashboard</a></p>";
echo "</body></html>";
?>
