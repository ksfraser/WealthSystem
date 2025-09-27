<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "Checking RBAC tables...\n";

$dao = new UserAuthDAO();
$reflection = new ReflectionClass($dao);
$pdoProperty = $reflection->getProperty('pdo');
$pdoProperty->setAccessible(true);
$pdo = $pdoProperty->getValue($dao);

$tables = $pdo->query("SHOW TABLES LIKE '%role%'")->fetchAll();
echo "Tables with 'role' in name:\n";
foreach ($tables as $table) {
    echo "  - " . array_values($table)[0] . "\n";
}

$tables = $pdo->query("SHOW TABLES LIKE '%permission%'")->fetchAll();
echo "Tables with 'permission' in name:\n";
foreach ($tables as $table) {
    echo "  - " . array_values($table)[0] . "\n";
}

// Check if roles table has data
try {
    $roles = $pdo->query("SELECT COUNT(*) as count FROM roles")->fetch();
    echo "Roles count: " . $roles['count'] . "\n";
} catch (Exception $e) {
    echo "Roles table doesn't exist or is empty\n";
}

// Check if permissions table has data
try {
    $permissions = $pdo->query("SELECT COUNT(*) as count FROM permissions")->fetch();
    echo "Permissions count: " . $permissions['count'] . "\n";
} catch (Exception $e) {
    echo "Permissions table doesn't exist or is empty\n";
}
?>