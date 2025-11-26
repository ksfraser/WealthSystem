<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting test...\n";

try {
    require_once __DIR__ . '/../src/Ksfraser/UIRenderer/autoload.php';
    echo "Autoload successful\n";
    
    require_once 'MenuService.php';
    echo "MenuService loaded\n";
    
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "UserAuthDAO loaded\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

use Ksfraser\UIRenderer\Factories\UiFactory;
echo "UiFactory imported\n";
?>
