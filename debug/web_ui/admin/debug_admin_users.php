<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing admin_users rebuild...\n";

try {
    echo "1. Loading autoloader...\n";
    require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'../src/Ksfraser/UIRenderer/autoload.php';
    
    echo "2. Loading UserAuthDAO...\n";
    require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/UserAuthDAO.php';
    
    echo "3. Creating UserAuthDAO...\n";
    $userAuth = new UserAuthDAO();
    
    echo "4. Success with basic loading!\n";
    
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
