<?php
// minimal_status.php - Absolute minimal system status for testing
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up basic server vars if not set
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/minimal_status';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head><title>Minimal System Status</title></head>\n";
echo "<body>\n";
echo "<h1>Minimal System Status</h1>\n";

// Test 1: Basic PHP
echo "<h2>1. Basic PHP Test</h2>\n";
echo "<p>✅ PHP is working: " . PHP_VERSION . "</p>\n";

// Test 2: File includes
echo "<h2>2. File Include Test</h2>\n";
echo "<p>Working Directory: " . getcwd() . "</p>\n";
echo "<p>Script Directory: " . __DIR__ . "</p>\n";

$files = ['SessionManager.php', 'UserAuthDAO.php', 'AuthExceptions.php'];
foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<p>✅ $file exists at $fullPath</p>\n";
        try {
            require_once $fullPath;
            echo "<p>✅ $file loaded</p>\n";
        } catch (Exception $e) {
            echo "<p>❌ $file error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    } else {
        echo "<p>❌ $file missing at $fullPath</p>\n";
    }
}

// Test 3: Session
echo "<h2>3. Session Test</h2>\n";
try {
    $sm = SessionManager::getInstance();
    echo "<p>✅ SessionManager created</p>\n";
    echo "<p>Session active: " . ($sm->isSessionActive() ? 'Yes' : 'No') . "</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Session error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 4: Auth (expect failure)
echo "<h2>4. Auth Test (Expecting Failure)</h2>\n";
try {
    $auth = new UserAuthDAO();
    echo "<p>✅ UserAuthDAO created</p>\n";
    
    $loggedIn = $auth->isLoggedIn();
    echo "<p>Logged in: " . ($loggedIn ? 'Yes' : 'No') . "</p>\n";
    
    if (!$loggedIn) {
        echo "<p>⚠️ Not logged in (expected)</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Auth error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

// Test 5: Complete but minimal
echo "<h2>5. Complete Test</h2>\n";
echo "<p>If you can see this entire page, the basic system is working.</p>\n";
echo "<p>Any 500 errors are likely coming from authentication redirects or missing login.</p>\n";

echo "</body>\n";
echo "</html>\n";
?>
