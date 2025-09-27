<?php
require_once __DIR__ . '/../../RBACService.php';
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "🧪 Testing RBACService...\n\n";

try {
    // Create RBAC service
    $rbac = new RBACService();
    
    // Test 1: Check admin user permissions
    echo "📋 Test 1: Admin User Permissions\n";
    $adminUserId = 8; // We know this from earlier
    
    echo "  Admin has 'admin' role: " . ($rbac->hasRole('admin', $adminUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  Admin has 'user' role: " . ($rbac->hasRole('user', $adminUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  Admin has system.config.edit permission: " . ($rbac->hasPermission('system.config.edit', $adminUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  Admin isAdmin() check: " . ($rbac->isAdmin($adminUserId) ? '✅ Yes' : '❌ No') . "\n";
    
    // Test 2: Check regular user permissions
    echo "\n📋 Test 2: Regular User Permissions\n";
    $regularUserId = 3; // Kevin
    
    echo "  User has 'user' role: " . ($rbac->hasRole('user', $regularUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User has 'admin' role: " . ($rbac->hasRole('admin', $regularUserId) ? '❌ No' : '✅ Yes (correctly denied)') . "\n";
    echo "  User has portfolio.view.own permission: " . ($rbac->hasPermission('portfolio.view.own', $regularUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User isAdmin() check: " . ($rbac->isAdmin($regularUserId) ? '❌ No (correct)' : '✅ Yes (correctly denied)') . "\n";
    
    // Test 3: Menu items
    echo "\n📋 Test 3: Permitted Menu Items\n";
    $adminMenu = $rbac->getPermittedMenuItems($adminUserId);
    echo "  Admin menu items: " . count($adminMenu) . " items\n";
    foreach ($adminMenu as $item) {
        echo "    - {$item['icon']} {$item['name']}\n";
    }
    
    $userMenu = $rbac->getPermittedMenuItems($regularUserId);
    echo "  Regular user menu items: " . count($userMenu) . " items\n";
    foreach ($userMenu as $item) {
        echo "    - {$item['icon']} {$item['name']}\n";
    }
    
    // Test 4: Role and permission listing
    echo "\n📋 Test 4: System Data\n";
    $allRoles = $rbac->getAllRoles();
    echo "  Total roles in system: " . count($allRoles) . "\n";
    
    $allPermissions = $rbac->getAllPermissions();
    echo "  Total permissions in system: " . count($allPermissions) . "\n";
    
    // Test 5: User data access
    echo "\n📋 Test 5: Data Access Control\n";
    echo "  Admin can access user data: " . ($rbac->canAccessUserData($regularUserId, 'user.view.own', $adminUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User can access own data: " . ($rbac->canAccessUserData($regularUserId, 'user.view.own', $regularUserId) ? '✅ Yes' : '❌ No') . "\n";
    echo "  User can access admin data: " . ($rbac->canAccessUserData($adminUserId, 'user.view.own', $regularUserId) ? '❌ No (correct)' : '✅ Yes (correctly denied)') . "\n";
    
    echo "\n✅ All RBACService tests completed!\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "📍 Error at: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>