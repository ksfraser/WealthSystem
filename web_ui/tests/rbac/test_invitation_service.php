<?php
require_once __DIR__ . '/../../InvitationService.php';
require_once __DIR__ . '/../../UserAuthDAO.php';

echo "🧪 Testing InvitationService...\n\n";

try {
    $invitationService = new InvitationService();
    $auth = new UserAuthDAO();
    
    // Test data
    $adminUserId = 8;  // admin user
    $regularUserId = 3; // Kevin
    $testEmail = 'test@example.com';
    
    // Test 1: Send friend invitation
    echo "📋 Test 1: Send Friend Invitation\n";
    $result = $invitationService->sendFriendInvitation(
        $adminUserId, 
        $testEmail, 
        'Would you like to join my portfolio network?'
    );
    
    if ($result['success']) {
        echo "  ✅ Friend invitation sent successfully\n";
        echo "  📧 Invitation ID: {$result['invitation_id']}\n";
        echo "  🔗 Token: " . substr($result['token'], 0, 16) . "...\n";
    } else {
        echo "  ❌ Failed: {$result['error']}\n";
    }
    
    // Test 2: Send advisor invitation
    echo "\n📋 Test 2: Send Advisor Invitation\n";
    $result2 = $invitationService->sendAdvisorInvitation(
        $regularUserId,
        $adminUserId, 
        'read_write',
        'I would like you to be my financial advisor'
    );
    
    if ($result2['success']) {
        echo "  ✅ Advisor invitation sent successfully\n";
        echo "  📧 Invitation ID: {$result2['invitation_id']}\n";
    } else {
        echo "  ❌ Failed: {$result2['error']}\n";
    }
    
    // Test 3: Request advisor upgrade
    echo "\n📋 Test 3: Request Advisor Upgrade\n";
    $upgradeDetails = [
        'business_name' => 'Financial Advisory Services LLC',
        'credentials' => 'CFP, CFA',
        'description' => 'I want to provide professional financial advice'
    ];
    
    $result3 = $invitationService->requestAdvisorUpgrade(
        $regularUserId,
        $adminUserId,
        $upgradeDetails
    );
    
    if ($result3['success']) {
        echo "  ✅ Advisor upgrade request submitted\n";
        echo "  📧 Request ID: {$result3['request_id']}\n";
        echo "  🔗 Approval Token: " . substr($result3['approval_token'], 0, 16) . "...\n";
    } else {
        echo "  ❌ Failed: {$result3['error']}\n";
    }
    
    // Test 4: Get user's sent invitations
    echo "\n📋 Test 4: Get User Invitations\n";
    $sentInvitations = $invitationService->getUserSentInvitations($adminUserId);
    echo "  📤 Admin sent invitations: " . count($sentInvitations) . "\n";
    
    $upgradeRequests = $invitationService->getUserUpgradeRequests($regularUserId);
    echo "  📈 Kevin's upgrade requests: " . count($upgradeRequests) . "\n";
    
    // Test 5: Try to send duplicate invitation (should fail)
    echo "\n📋 Test 5: Duplicate Invitation Test\n";
    $duplicateResult = $invitationService->sendFriendInvitation(
        $adminUserId, 
        $testEmail, 
        'Duplicate invitation'
    );
    
    if (!$duplicateResult['success'] && strpos($duplicateResult['error'], 'already sent') !== false) {
        echo "  ✅ Correctly prevented duplicate invitation\n";
    } else {
        echo "  ❌ Should have prevented duplicate invitation\n";
    }
    
    echo "\n✅ InvitationService tests completed!\n";
    echo "\nNext steps:\n";
    echo "  - Implement email sending functionality\n";
    echo "  - Build web UI for invitation management\n";
    echo "  - Add profile upgrade interface\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "📍 Error at: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>