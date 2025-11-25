<?php
/**
 * Modern Bootstrap File - Uses existing classes with namespaces
 * This eliminates the need for manual includes throughout the application
 */

// Load Composer's autoloader first - this handles external dependencies
require_once __DIR__ . '/../vendor/autoload.php';

// For our existing classes that now use namespaces, we need to include them manually
// until we fully migrate to PSR-4 autoloading for our own classes

require_once __DIR__ . '/AuthExceptions.php';        // App\Auth namespace
require_once __DIR__ . '/SessionManager.php';       // App\Core namespace
require_once __DIR__ . '/UserAuthDAO.php';          // Will add namespace next
require_once __DIR__ . '/CommonDAO.php';            // Will add namespace next

// Optional: Set up any global configuration here
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', __DIR__);
}

// Error reporting for development
if (!defined('PRODUCTION')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}
