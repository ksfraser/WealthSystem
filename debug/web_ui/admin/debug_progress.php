<?php
/**
 * Direct test of the progress check functionality
 * Simulates the exact POST request that's causing the 500 error
 */

// Enable full error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/'debug.log');

echo "<h1>Progressive Data Loader Debug</h1>";

try {
    echo "<p>Step 1: Testing includes...</p>";
    
    require_once 'C:\Users\prote\Documents\ChatGPT-Micro-Cap-Experiment/web_ui/UserAuthDAO.php';
    echo "<p>✅ UserAuthDAO loaded</p>";
    
    require_once '../StockDAO.php';
    echo "<p>✅ StockDAO loaded</p>";
    
    require_once '../../ProgressiveHistoricalLoader.php';
    echo "<p>✅ ProgressiveHistoricalLoader loaded</p>";
    
    echo "<p>Step 2: Testing authentication...</p>";
    
    // Check authentication (without requiring admin for debug)
    $auth = new UserAuthDAO();
    echo "<p>✅ UserAuthDAO instantiated</p>";
    
    // Get database connection
    $db = $auth->getPdo();
    echo "<p>✅ Database connection obtained</p>";
    
    echo "<p>Step 3: Testing ProgressiveHistoricalLoader instantiation...</p>";
    
    $loader = new ProgressiveHistoricalLoader($db);
    echo "<p>✅ ProgressiveHistoricalLoader instantiated</p>";
    
    echo "<p>Step 4: Testing getProgressInfo method...</p>";
    
    // Test with AAPL (a common stock symbol)
    $symbol = 'AAPL';
    $progressInfo = $loader->getProgressInfo($symbol);
    echo "<p>✅ getProgressInfo executed successfully</p>";
    
    echo "<h3>Progress Info Result:</h3>";
    echo "<pre>";
    print_r($progressInfo);
    echo "</pre>";
    
    // Test form simulation
    if ($_POST['test'] ?? false) {
        echo "<h3>Testing Form Submission</h3>";
        $_POST['action'] = 'check_progress';
        $_POST['symbol'] = 'AAPL';
        
        $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
        if (!empty($symbol)) {
            $loader = new ProgressiveHistoricalLoader($db);
            $progressInfo = $loader->getProgressInfo($symbol);
            $message = "Progress information retrieved for {$symbol}";
            
            echo "<p>✅ Form simulation successful: {$message}</p>";
            echo "<pre>";
            print_r($progressInfo);
            echo "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-left: 4px solid #c62828; margin: 10px 0;'>";
    echo "<strong>❌ Error Details:</strong><br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><br>";
    echo "<pre style='background: #fff; padding: 10px; overflow: auto;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
    echo "</div>";
} catch (Error $e) {
    echo "<div style='background: #ffebee; color: #c62828; padding: 15px; border-left: 4px solid #c62828; margin: 10px 0;'>";
    echo "<strong>❌ Fatal Error:</strong><br>";
    echo "<strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>File:</strong> " . htmlspecialchars($e->getFile()) . "<br>";
    echo "<strong>Line:</strong> " . $e->getLine() . "<br>";
    echo "</div>";
}

?>

<h3>Manual Form Test</h3>
<form method="POST">
    <input type="hidden" name="test" value="1">
    <label>This will simulate the exact form submission that's causing the error:</label><br>
    <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; margin-top: 10px;">
        Test Form Submission
    </button>
</form>