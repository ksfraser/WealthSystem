<?php
/**
 * Minimal test for Progress Check functionality
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../UserAuthDAO.php';
require_once '../StockDAO.php';
require_once '../../ProgressiveHistoricalLoader.php';

echo "<h1>Progressive Data Loader Test</h1>";

try {
    // Check authentication and admin status
    $auth = new UserAuthDAO();
    $auth->requireAdmin();
    
    echo "<p>✅ Authentication successful</p>";
    
    // Get database connection
    $db = $auth->getPdo();
    
    echo "<p>✅ Database connection established</p>";
    
    // Test form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_progress') {
        $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
        echo "<p>Testing progress check for symbol: {$symbol}</p>";
        
        if (!empty($symbol)) {
            $loader = new ProgressiveHistoricalLoader($db);
            echo "<p>✅ ProgressiveHistoricalLoader created</p>";
            
            $progressInfo = $loader->getProgressInfo($symbol);
            echo "<p>✅ Progress info retrieved</p>";
            
            echo "<pre>";
            print_r($progressInfo);
            echo "</pre>";
        } else {
            echo "<p>❌ Empty symbol</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>

<form method="POST">
    <input type="hidden" name="action" value="check_progress">
    <label for="symbol">Stock Symbol:</label>
    <input type="text" name="symbol" placeholder="e.g., AAPL" required>
    <button type="submit">Test Progress Check</button>
</form>