<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "🧪 Testing UserAuthDAO RBAC Integration...\n\n";

try {
    $auth = new UserAuthDAO();
    
    // Test 1: Check admin user (ID 8)
    echo "📋 Test 1: Admin User (ID 8)\n";
    echo "  isAdmin() with RBAC: " . ($auth->isAdmin(8) ? '✅ Yes' : '❌ No') . "\n";
    echo "  hasRole('admin'): " . ($auth->hasRole('admin', 8) ? '✅ Yes' : '❌ No') . "\n";
    echo "  hasPermission('system.config.edit'): " . ($auth->hasPermission('system.config.edit', 8) ? '✅ Yes' : '❌ No') . "\n";
    
    // Test 2: Check regular user (ID 3)
    echo "\n📋 Test 2: Regular User (ID 3)\n";
    echo "  isAdmin() with RBAC: " . ($auth->isAdmin(3) ? '❌ No (incorrect)' : '✅ Yes (correctly denied)') . "\n";
    echo "  hasRole('user'): " . ($auth->hasRole('user', 3) ? '✅ Yes' : '❌ No') . "\n";
    echo "  hasPermission('portfolio.view.own'): " . ($auth->hasPermission('portfolio.view.own', 3) ? '✅ Yes' : '❌ No') . "\n";
    
    // Test 3: Menu items
    echo "\n📋 Test 3: Menu Items\n";
    $adminMenu = $auth->getPermittedMenuItems(8);
    echo "  Admin menu items: " . count($adminMenu) . "\n";
    
    $userMenu = $auth->getPermittedMenuItems(3);
    echo "  User menu items: " . count($userMenu) . "\n";
    
    // Test 4: Data access control
    echo "\n📋 Test 4: Data Access Control\n";
    echo "  Admin can access user 3 data: " . ($auth->canAccessUserData(3, 'user.view.own', 8) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User 3 can access own data: " . ($auth->canAccessUserData(3, 'user.view.own', 3) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User 3 can access admin data: " . ($auth->canAccessUserData(8, 'user.view.own', 3) ? '❌ No (incorrect)' : '✅ Yes (correctly denied)') . "\n";
    
    // Test 5: Role display
    echo "\n📋 Test 5: User Roles\n";
    $adminRoles = $auth->getUserRoles(8);
    echo "  Admin roles: " . implode(', ', $adminRoles) . "\n";
    
    $userRoles = $auth->getUserRoles(3);
    echo "  User roles: " . implode(', ', $userRoles) . "\n";
    
    echo "\n✅ UserAuthDAO RBAC integration tests completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "📍 Error at: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>