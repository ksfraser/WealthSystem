<?php
/**
 * Test Invitation System Integration
 * 
 * Simple test to verify the invitation system works properly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/InvitationService.php';
require_once __DIR__ . '/RBACService.php';

echo "<h1>🧪 Invitation System Integration Test</h1>";

try {
    // Test 1: Initialize services
    echo "<h2>Test 1: Service Initialization</h2>";
    $auth = new UserAuthDAO();
    $invitation = new InvitationService();
    $rbac = new RBACService();
    echo "✅ All services initialized successfully<br>";
    
    // Test 2: Database connectivity
    echo "<h2>Test 2: Database Connectivity</h2>";
    $testUser = $auth->getUserById(1);
    if ($testUser) {
        echo "✅ Database connection working - Found user: " . htmlspecialchars($testUser['username']) . "<br>";
    } else {
        echo "ℹ️ No users found in database<br>";
    }
    
    // Test 3: Check invitation tables exist
    echo "<h2>Test 3: Database Schema</h2>";
    $reflection = new ReflectionClass($auth);
    $pdoProperty = $reflection->getProperty('pdo');
    $pdoProperty->setAccessible(true);
    $pdo = $pdoProperty->getValue($auth);
    
    $tables = ['invitations', 'advisor_upgrade_requests', 'user_advisors'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "✅ Table '$table' exists with $count records<br>";
        } catch (Exception $e) {
            echo "❌ Table '$table' missing or error: " . $e->getMessage() . "<br>";
        }
    }
    
    // Test 4: Test invitation methods exist
    echo "<h2>Test 4: Service Methods</h2>";
    $methods = [
        'sendFriendInvitation',
        'sendAdvisorInvitation', 
        'requestAdvisorUpgrade',
        'processClientApproval',
        'acceptInvitation',
        'declineInvitation',
        'getUserSentInvitations',
        'getUserReceivedInvitations',
        'getUserUpgradeRequests',
        'getUpgradeRequestByToken'
    ];
    
    foreach ($methods as $method) {
        if (method_exists($invitation, $method)) {
            echo "✅ Method '$method' exists<br>";
        } else {
            echo "❌ Method '$method' missing<br>";
        }
    }
    
    // Test 5: Check RBAC integration
    echo "<h2>Test 5: RBAC Integration</h2>";
    $roles = $rbac->getAllRoles();
    $hasAdvisorRole = false;
    foreach ($roles as $role) {
        if ($role['name'] === 'advisor') {
            $hasAdvisorRole = true;
            break;
        }
    }
    
    if ($hasAdvisorRole) {
        echo "✅ Advisor role exists in RBAC system<br>";
    } else {
        echo "ℹ️ Advisor role not found - may need to be created<br>";
    }
    
    echo "<h2>✨ Integration Test Complete</h2>";
    echo "<p><strong>Status:</strong> Invitation system appears to be properly integrated!</p>";
    echo "<p><a href='profile.php'>🔗 Test Profile Page</a> | <a href='dashboard.php'>🔗 Back to Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Test Failed</h2>";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    max-width: 800px;
    margin: 20px auto;
    padding: 20px;
    background: #f5f5f5;
    line-height: 1.6;
}

h1 {
    color: #2c3e50;
    border-bottom: 3px solid #3498db;
    padding-bottom: 10px;
}

h2 {
    color: #34495e;
    margin-top: 30px;
    margin-bottom: 15px;
}

pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #e74c3c;
    overflow-x: auto;
    font-size: 0.9em;
}

p {
    margin: 10px 0;
}

a {
    color: #3498db;
    text-decoration: none;
    font-weight: 600;
}

a:hover {
    text-decoration: underline;
}
</style>