<?php
/**
 * Database Autoloader
 * Includes all necessary database classes for the Enhanced Trading System
 */

// Define the base path for database files
$databasePath = __DIR__ . '/../src/Ksfraser/Database/';

// Include all required database files
$requiredFiles = [
    'EnhancedDbManager.php',
    'PdoConnection.php',
    'MysqliConnection.php', 
    'PdoSqliteConnection.php'
];

foreach ($requiredFiles as $file) {
    $filePath = $databasePath . $file;
    if (file_exists($filePath)) {
        require_once $filePath;
    } else {
        throw new Exception("Required database file not found: {$file}");
    }
}

// Alias for easier access
use Ksfraser\Database\EnhancedDbManager;

/**
 * Helper function to get database connection
 */
function getEnhancedDbConnection() {
    return EnhancedDbManager::getConnection();
}

/**
 * Helper function to get current database driver
 */
function getDbDriver() {
    return EnhancedDbManager::getCurrentDriver();
}
?>
