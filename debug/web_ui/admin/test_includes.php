<?php
// Test each include individually
echo "<h1>Testing Individual Includes</h1>";

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<p>Starting tests...</p>";

// Test 1: UserAuthDAO
try {
    echo "<p>Testing UserAuthDAO include...</p>";
    require_once '../UserAuthDAO.php';
    echo "<p>✅ UserAuthDAO included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ UserAuthDAO failed: " . $e->getMessage() . "</p>";
}

// Test 2: StockDAO
try {
    echo "<p>Testing StockDAO include...</p>";
    require_once '../StockDAO.php';
    echo "<p>✅ StockDAO included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ StockDAO failed: " . $e->getMessage() . "</p>";
}

// Test 3: ProgressiveHistoricalLoader
try {
    echo "<p>Testing ProgressiveHistoricalLoader include...</p>";
    require_once '../../ProgressiveHistoricalLoader.php';
    echo "<p>✅ ProgressiveHistoricalLoader included successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ProgressiveHistoricalLoader failed: " . $e->getMessage() . "</p>";
}

// Test 4: Class instantiation
try {
    echo "<p>Testing class instantiation...</p>";
    $auth = new UserAuthDAO();
    echo "<p>✅ UserAuthDAO instantiated</p>";
    
    $db = $auth->getPdo();
    echo "<p>✅ Database connection obtained</p>";
    
    $loader = new ProgressiveHistoricalLoader($db);
    echo "<p>✅ ProgressiveHistoricalLoader instantiated</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Instantiation failed: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}

echo "<p>All tests completed.</p>";
?>