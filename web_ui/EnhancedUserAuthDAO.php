<?php
/**
 * Migration adapter for the new database system
 * Allows existing code to work with the enhanced database layer
 */

// Include the enhanced database classes
require_once __DIR__ . '/../src/Ksfraser/Database/EnhancedDbManager.php';
require_once __DIR__ . '/../src/Ksfraser/Database/PdoConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/MysqliConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/PdoSqliteConnection.php';
require_once __DIR__ . '/../src/Ksfraser/Database/EnhancedUserAuthDAO.php';

use Ksfraser\Database\EnhancedDbManager;
use Ksfraser\Database\EnhancedUserAuthDAO;
?>
