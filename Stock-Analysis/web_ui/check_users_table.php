<?php
/**
 * Check Users Table Structure
 */

require_once __DIR__ . '/UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    
    // Get reflection to access the PDO object
    $reflection = new ReflectionClass($auth);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($auth);
    
    if ($pdo) {
        echo "<h2>Users Table Structure</h2>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        foreach ($columns as $column) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test getting users
        echo "<h2>Sample User Data</h2>";
        $stmt = $pdo->query("SELECT * FROM users LIMIT 1");
        $sampleUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sampleUser) {
            echo "<pre>";
            print_r($sampleUser);
            echo "</pre>";
        } else {
            echo "No users found in table";
        }
        
    } else {
        echo "Could not access database connection";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>