<?php
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "🔍 Checking current users in database...\n";

try {
    $userAuth = new UserAuthDAO();
    $users = $userAuth->getAllUsers(100, 0);
    
    echo "Current users in database:\n";
    $adminUserId = null;
    
    foreach($users as $user) {
        $adminStatus = $user['is_admin'] ? 'Yes' : 'No';
        echo "  ID: {$user['id']}, Username: {$user['username']}, Admin: $adminStatus\n";
        
        if ($user['is_admin']) {
            $adminUserId = $user['id'];
        }
    }
    
    if ($adminUserId) {
        echo "\n✅ Found admin user with ID: $adminUserId\n";
    } else {
        echo "\n⚠️  No admin user found!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>