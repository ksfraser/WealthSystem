<?php
// comprehensive_debug.php - Complete diagnostic for VS Code server 500 errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Capture all output
ob_start();

echo "=== COMPREHENSIVE 500 ERROR DIAGNOSTIC ===\n\n";

echo "1. PHP Environment:\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Error Reporting: " . error_reporting() . "\n";
echo "Display Errors: " . ini_get('display_errors') . "\n";
echo "Log Errors: " . ini_get('log_errors') . "\n";
echo "Error Log: " . ini_get('error_log') . "\n\n";

echo "2. Server Environment:\n";
echo "SERVER_SOFTWARE: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not set') . "\n";
echo "REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'CLI') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "\n\n";

echo "3. File System Checks:\n";
$files_to_check = [
    'AuthExceptions.php',
    'SessionManager.php', 
    'UserAuthDAO.php',
    'auth_check.php',
    'CommonDAO.php',
    'DbConfigClasses.php',
    'NavigationManager.php'
];

foreach ($files_to_check as $file) {
    $path = 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'' . $file;
    if (file_exists($path)) {
        echo "✅ $file exists (" . filesize($path) . " bytes)\n";
        
        // Check for syntax errors
        $syntax_check = shell_exec("php -l " . escapeshellarg($path) . " 2>&1");
        if (strpos($syntax_check, 'No syntax errors') !== false) {
            echo "   ✅ Syntax OK\n";
        } else {
            echo "   ❌ Syntax Error: " . trim($syntax_check) . "\n";
        }
    } else {
        echo "❌ $file missing\n";
    }
}

echo "\n4. Testing Core Components:\n";

try {
    echo "Loading AuthExceptions...\n";
    require_once 'AuthExceptions.php';
    echo "✅ AuthExceptions loaded\n";
} catch (Exception $e) {
    echo "❌ AuthExceptions error: " . $e->getMessage() . "\n";
}

try {
    echo "Loading SessionManager...\n";
    require_once 'SessionManager.php';
    $sm = SessionManager::getInstance();
    echo "✅ SessionManager loaded, active: " . ($sm->isSessionActive() ? 'Yes' : 'No') . "\n";
    if (!$sm->isSessionActive()) {
        echo "   Error: " . ($sm->getInitializationError() ?: 'Unknown') . "\n";
    }
} catch (Exception $e) {
    echo "❌ SessionManager error: " . $e->getMessage() . "\n";
}

try {
    echo "Loading UserAuthDAO...\n";
    require_once 'UserAuthDAO.php';
    $auth = new UserAuthDAO();
    echo "✅ UserAuthDAO loaded\n";
    echo "   Logged in: " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "\n";
} catch (Exception $e) {
    echo "❌ UserAuthDAO error: " . $e->getMessage() . "\n";
}

echo "\n5. Testing Auth Check Include:\n";
try {
    $_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/diagnostic';
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // Capture any output from auth_check
    ob_start();
    include 'auth_check.php';
    $auth_output = ob_get_contents();
    ob_end_clean();
    
    echo "✅ auth_check.php included successfully\n";
    if ($auth_output) {
        echo "   Output captured: " . strlen($auth_output) . " bytes\n";
    }
    
} catch (LoginRequiredException $e) {
    echo "✅ LoginRequiredException caught (expected): " . $e->getMessage() . "\n";
    echo "   Redirect URL: " . $e->getRedirectUrl() . "\n";
} catch (Exception $e) {
    echo "❌ auth_check.php error: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}

echo "\n6. Testing NavigationManager:\n";
try {
    require_once 'NavigationManager.php';
    $nav = new NavigationManager();
    echo "✅ NavigationManager loaded\n";
} catch (Exception $e) {
    echo "❌ NavigationManager error: " . $e->getMessage() . "\n";
}

echo "\n7. Minimal Page Test:\n";
try {
    $_SERVER['REQUEST_URI'] = '/test_minimal';
    
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head><title>Minimal Test</title></head>
<body>
<h1>Minimal Page Test</h1>
<p>If you see this, basic PHP/HTML works.</p>
<?php
    // Try to include auth but catch exceptions
    try {
        require_once 'auth_check.php';
        echo "<p>✅ Authentication passed - user is logged in</p>";
        global $user, $isAdmin;
        echo "<p>User: " . htmlspecialchars($user['username'] ?? 'Unknown') . "</p>";
        echo "<p>Admin: " . ($isAdmin ? 'Yes' : 'No') . "</p>";
    } catch (LoginRequiredException $e) {
        echo "<p>⚠️ Login required: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Would redirect to: " . htmlspecialchars($e->getRedirectUrl()) . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ Auth error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
?>
</body>
</html>
<?php
    $page_output = ob_get_contents();
    ob_end_clean();
    
    echo "✅ Minimal page generated (" . strlen($page_output) . " bytes)\n";
    
} catch (Exception $e) {
    echo "❌ Minimal page error: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";

$output = ob_get_contents();
ob_end_clean();

// Save to log file
file_put_contents('diagnostic.log', $output);

// Display output
echo $output;
?>
