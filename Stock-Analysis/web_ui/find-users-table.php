<?php
/**
 * Check which database has the users table
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Find Users Table</h1>";
echo "<style>body { font-family: monospace; } .success { color: green; } .error { color: red; }</style>";

$host = 'fhsws001.ksfraser.com';
$username = 'stocks';
$password = 'stocks';
$databases = ['stock_market_2', 'stock_market_micro_cap_trading'];

foreach ($databases as $dbname) {
    echo "<h2>Checking: $dbname</h2>";
    
    try {
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "<div class='success'>✅ Connected to $dbname</div>";
        
        // Check for users table
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        $result = $stmt->fetch();
        
        if ($result) {
            echo "<div class='success'>✅ Found 'users' table!</div>";
            
            // Get table structure
            $stmt = $pdo->query("DESCRIBE users");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Table Structure:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // Count users
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetch();
            echo "<div class='success'>User count: {$count['count']}</div>";
            
        } else {
            echo "<div class='error'>❌ No 'users' table found</div>";
        }
        
    } catch (PDOException $e) {
        echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
}
