<?php
/**
 * Debug version of admin_users.php
 */

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Starting admin_users debug...\n";

echo "1. Including UserAuthDAO...\n";
require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/UserAuthDAO.php';

echo "2. Including autoloader...\n";
require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'../src/Ksfraser/UIRenderer/autoload.php';

echo "3. Importing controller...\n";
use Ksfraser\User\Controllers\UserManagementController;

echo "4. Creating UserAuthDAO...\n";
try {
    $userAuth = new UserAuthDAO();
    echo "UserAuthDAO created successfully\n";
} catch (Exception $e) {
    echo "Error creating UserAuthDAO: " . $e->getMessage() . "\n";
    exit;
}

echo "5. Creating controller...\n";
try {
    $controller = new UserManagementController($userAuth);
    echo "Controller created successfully\n";
} catch (Exception $e) {
    echo "Error creating controller: " . $e->getMessage() . "\n";
    exit;
}

echo "6. Rendering page...\n";
try {
    $output = $controller->renderPage();
    echo "Page rendered successfully. Output length: " . strlen($output) . "\n";
    echo "First 200 chars of output: " . substr($output, 0, 200) . "\n";
} catch (Exception $e) {
    echo "Error rendering page: " . $e->getMessage() . "\n";
}

echo "Debug complete.\n";
?>
