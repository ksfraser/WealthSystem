<?php
/**
 * Simple DB Connection Test
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Database Connection Test</h1>";
echo "<style>body { font-family: monospace; } .success { color: green; } .error { color: red; }</style>";

// Step 1: Try to instantiate UserAuthDAO
echo "<h2>Step 1: Create UserAuthDAO</h2>";
try {
    require_once __DIR__ . '/UserAuthDAO.php';
    $auth = new UserAuthDAO();
    echo "<div class='success'>✅ UserAuthDAO created</div>";
    
    // Check PDO
    $pdo = $auth->getPdo();
    if ($pdo) {
        echo "<div class='success'>✅ PDO connection exists</div>";
        
        // Try a test query
        try {
            $stmt = $pdo->query("SELECT DATABASE() as db_name");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<div class='success'>✅ Connected to database: " . $result['db_name'] . "</div>";
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Query failed: " . $e->getMessage() . "</div>";
        }
    } else {
        echo "<div class='error'>❌ PDO is null - database connection failed</div>";
        
        // Get errors
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            echo "<div class='error'>Errors:</div>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>" . htmlspecialchars($error) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Exception: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
