<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

try {
    $dao = new UserAuthDAO();
    $reflection = new ReflectionClass($dao);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($dao);
    
    echo "user_advisors table structure:\n";
    $result = $pdo->query('DESCRIBE user_advisors')->fetchAll();
    foreach($result as $row) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
    
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>