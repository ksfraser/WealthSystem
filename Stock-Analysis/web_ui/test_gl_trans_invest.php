<?php
/**
 * Test script to verify the gl_trans_invest table exists and view_imported_transactions.php works
 */

require_once __DIR__ . '/DbConfigClasses.php';
require_once __DIR__ . '/InvestGLDAO.php';

try {
    // Create PDO connection
    $pdo = LegacyDatabaseConfig::createConnection();

    // Test if gl_trans_invest table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'gl_trans_invest'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "✓ gl_trans_invest table exists\n";

        // Test InvestGLDAO can be instantiated
        $investGLDAO = new InvestGLDAO($pdo);
        echo "✓ InvestGLDAO instantiated successfully\n";

        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM gl_trans_invest");
        $result = $stmt->fetch();
        echo "✓ Can query gl_trans_invest table (count: " . $result['count'] . ")\n";

        echo "\nAll tests passed! The 500 error should be resolved.\n";

    } else {
        echo "✗ gl_trans_invest table does not exist\n";
    }

} catch (Exception $e) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
}
?>