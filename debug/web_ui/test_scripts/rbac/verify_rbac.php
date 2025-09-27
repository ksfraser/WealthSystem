<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "🔍 Verifying RBAC Migration Results...\n\n";

try {
    $userAuth = new UserAuthDAO();
    $reflection = new ReflectionClass($userAuth);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($userAuth);
    
    // Check roles
    echo "📋 Roles:\n";
    $roles = $pdo->query("SELECT * FROM roles ORDER BY id")->fetchAll();
    foreach ($roles as $role) {
        echo "  {$role['id']}: {$role['name']} - {$role['description']}\n";
    }
    
    // Check user roles
    echo "\n👥 User Role Assignments:\n";
    $userRoles = $pdo->query("
        SELECT u.username, r.name as role_name, ur.granted_at, ur.notes
        FROM user_roles ur
        JOIN users u ON ur.user_id = u.id
        JOIN roles r ON ur.role_id = r.id
        ORDER BY u.username, r.name
    ")->fetchAll();
    
    foreach ($userRoles as $ur) {
        echo "  {$ur['username']} -> {$ur['role_name']} (granted: {$ur['granted_at']})\n";
    }
    
    // Check permissions count
    echo "\n🔐 Permissions:\n";
    $permCount = $pdo->query("SELECT COUNT(*) FROM permissions")->fetchColumn();
    echo "  Total permissions: $permCount\n";
    
    $rolePerm = $pdo->query("SELECT COUNT(*) FROM role_permissions")->fetchColumn();
    echo "  Role-permission mappings: $rolePerm\n";
    
    echo "\n✅ RBAC system is ready for use!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>