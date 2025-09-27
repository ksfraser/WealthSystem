<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

try {
    $dao = new UserAuthDAO();
    $reflection = new ReflectionClass($dao);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($dao);
    
    echo "Checking for existing invitation-related tables:\n";
    
    $tables = $pdo->query("SHOW TABLES LIKE '%invite%'")->fetchAll();
    if (empty($tables)) {
        echo "  No invitation tables found\n";
    } else {
        foreach($tables as $table) {
            echo "  - " . array_values($table)[0] . "\n";
        }
    }
    
    // Also check user_advisors table structure
    echo "\nChecking user_advisors table:\n";
    $result = $pdo->query('DESCRIBE user_advisors')->fetchAll();
    foreach($result as $row) {
        echo "  {$row['Field']} - {$row['Type']} - {$row['Key']}\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>