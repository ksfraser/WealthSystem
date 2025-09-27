<?php
/**
 * User Profile Management Page
 * 
 * Allows users to:
 * - Edit their profile information
 * - Upgrade to advisor account
 * - Manage invitations and advisor relationships
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/InvitationService.php';
require_once __DIR__ . '/RBACService.php';

$auth = new UserAuthDAO();
$invitationService = new InvitationService();
$rbac = new RBACService();

// Require login
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$userId = $currentUser['id'];
$message = '';
$messageType = '';

// Handle messages from URL parameters (from redirects)
if (isset($_GET['success'])) {
    $message = $_GET['success'];
    $messageType = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $messageType = 'error';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_profile':
            $result = handleProfileUpdate($auth, $userId, $_POST);
            break;
            
        case 'upgrade_to_advisor':
            $result = handleAdvisorUpgrade($invitationService, $userId, $_POST);
            break;
            
        case 'invite_friend':
            $result = handleFriendInvitation($invitationService, $userId, $_POST);
            break;
            
        case 'invite_advisor':
            $result = handleAdvisorInvitation($invitationService, $userId, $_POST);
            break;
            
        default:
            $result = ['success' => false, 'message' => 'Unknown action'];
    }
    
    $message = $result['message'] ?? '';
    $messageType = $result['success'] ? 'success' : 'error';
}

// Get user's roles and upgrade status
$userRoles = $rbac->getUserRoles($userId);
$isAdvisor = in_array('advisor', $userRoles);
$hasUpgradeRequest = !empty($invitationService->getUserUpgradeRequests($userId));

// Get invitations
$sentInvitations = $invitationService->getUserSentInvitations($userId);
$receivedInvitations = $invitationService->getUserReceivedInvitations($userId);

function handleProfileUpdate($auth, $userId, $postData) {
    try {
        $username = trim($postData['username'] ?? '');
        $email = trim($postData['email'] ?? '');
        
        if (empty($username) || empty($email)) {
            return ['success' => false, 'message' => 'Username and email are required'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        
        // Update user profile (assuming method exists in UserAuthDAO)
        $success = $auth->updateUserProfile($userId, $email);
        
        if ($success) {
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
        
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating your profile'];
    }
}

function handleAdvisorUpgrade($invitationService, $userId, $postData) {
    try {
        $clientIdOrEmail = trim($postData['client_id_or_email'] ?? '');
        $businessName = trim($postData['business_name'] ?? '');
        $credentials = trim($postData['credentials'] ?? '');
        $description = trim($postData['description'] ?? '');
        
        if (empty($clientIdOrEmail)) {
            return ['success' => false, 'message' => 'Client information is required'];
        }
        
        $upgradeDetails = [
            'business_name' => $businessName,
            'credentials' => $credentials,
            'description' => $description
        ];
        
        return $invitationService->requestAdvisorUpgrade($userId, $clientIdOrEmail, $upgradeDetails);
        
    } catch (Exception $e) {
        error_log("Advisor upgrade error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to submit advisor upgrade request'];
    }
}

function handleFriendInvitation($invitationService, $userId, $postData) {
    try {
        $email = trim($postData['friend_email'] ?? '');
        $message = trim($postData['friend_message'] ?? '');
        
        if (empty($email)) {
            return ['success' => false, 'message' => 'Email is required'];
        }
        
        return $invitationService->sendFriendInvitation($userId, $email, $message);
        
    } catch (Exception $e) {
        error_log("Friend invitation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send friend invitation'];
    }
}

function handleAdvisorInvitation($invitationService, $userId, $postData) {
    try {
        $advisorEmail = trim($postData['advisor_email'] ?? '');
        $permissionLevel = $postData['permission_level'] ?? 'read';
        $message = trim($postData['advisor_message'] ?? '');
        
        if (empty($advisorEmail)) {
            return ['success' => false, 'message' => 'Advisor email is required'];
        }
        
        return $invitationService->sendAdvisorInvitation($userId, $advisorEmail, $permissionLevel, $message);
        
    } catch (Exception $e) {
        error_log("Advisor invitation error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Failed to send advisor invitation'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Portfolio Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .user-details h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }
        
        .user-roles {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
            margin-top: 10px;
        }
        
        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #eee;
            margin-bottom: 30px;
        }
        
        .tab-button {
            background: none;
            border: none;
            padding: 15px 25px;
            cursor: pointer;
            font-size: 1rem;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: bold;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn.btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }
        
        .btn.btn-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .btn.btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }
        
        .advisor-upgrade-card {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .advisor-upgrade-card h3 {
            margin-bottom: 15px;
        }
        
        .invitation-list {
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .invitation-item {
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .invitation-item:last-child {
            border-bottom: none;
        }
        
        .invitation-details h4 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .invitation-meta {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .invitation-status {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-accepted {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-declined {
            background: #f8d7da;
            color: #721c24;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>My Profile</h1>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <h2><?= htmlspecialchars($currentUser['username']) ?></h2>
                    <p><?= htmlspecialchars($currentUser['email']) ?></p>
                    <div class="user-roles">
                        <?php foreach ($userRoles as $role): ?>
                            <span class="role-badge"><?= ucfirst($role) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab-button active" onclick="showTab('profile')">Profile Settings</button>
                <button class="tab-button" onclick="showTab('invitations')">Invitations</button>
                <?php if (!$isAdvisor): ?>
                    <button class="tab-button" onclick="showTab('upgrade')">Become Advisor</button>
                <?php endif; ?>
            </div>
            
            <!-- Profile Settings Tab -->
            <div id="profile" class="tab-content active">
                <div class="form-section">
                    <h3>Profile Information</h3>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" 
                                   value="<?= htmlspecialchars($currentUser['username']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address:</label>
                            <input type="email" id="email" name="email" 
                                   value="<?= htmlspecialchars($currentUser['email']) ?>" required>
                        </div>
                        
                        <button type="submit" class="btn">Update Profile</button>
                    </form>
                </div>
                
                <div class="grid">
                    <div class="form-section">
                        <h3>Invite Friend</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="invite_friend">
                            
                            <div class="form-group">
                                <label for="friend_email">Friend's Email:</label>
                                <input type="email" id="friend_email" name="friend_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="friend_message">Personal Message (optional):</label>
                                <textarea id="friend_message" name="friend_message" rows="3" 
                                         placeholder="Would you like to join my portfolio network?"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-success">Send Friend Invitation</button>
                        </form>
                    </div>
                    
                    <div class="form-section">
                        <h3>Invite Advisor</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="invite_advisor">
                            
                            <div class="form-group">
                                <label for="advisor_email">Advisor's Email:</label>
                                <input type="email" id="advisor_email" name="advisor_email" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="permission_level">Permission Level:</label>
                                <select id="permission_level" name="permission_level" required>
                                    <option value="read">Read Only</option>
                                    <option value="read_write">Read & Write</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="advisor_message">Message (optional):</label>
                                <textarea id="advisor_message" name="advisor_message" rows="3" 
                                         placeholder="I would like you to be my financial advisor..."></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">Send Advisor Invitation</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Invitations Tab -->
            <div id="invitations" class="tab-content">
                <div class="form-section">
                    <h3>Sent Invitations</h3>
                    <?php if (empty($sentInvitations)): ?>
                        <p>You haven't sent any invitations yet.</p>
                    <?php else: ?>
                        <div class="invitation-list">
                            <?php foreach ($sentInvitations as $invitation): ?>
                                <div class="invitation-item">
                                    <div class="invitation-details">
                                        <h4><?= htmlspecialchars($invitation['subject']) ?></h4>
                                        <div class="invitation-meta">
                                            To: <?= htmlspecialchars($invitation['invitee_email']) ?> • 
                                            Type: <?= ucfirst($invitation['invitation_type']) ?> • 
                                            Sent: <?= date('M j, Y', strtotime($invitation['sent_at'])) ?>
                                        </div>
                                    </div>
                                    <span class="invitation-status status-<?= $invitation['status'] ?>">
                                        <?= ucfirst($invitation['status']) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="form-section">
                    <h3>Received Invitations</h3>
                    <?php if (empty($receivedInvitations)): ?>
                        <p>You don't have any pending invitations.</p>
                    <?php else: ?>
                        <div class="invitation-list">
                            <?php foreach ($receivedInvitations as $invitation): ?>
                                <div class="invitation-item">
                                    <div class="invitation-details">
                                        <h4><?= htmlspecialchars($invitation['subject']) ?></h4>
                                        <div class="invitation-meta">
                                            From: <?= htmlspecialchars($invitation['inviter_name']) ?> • 
                                            Type: <?= ucfirst($invitation['invitation_type']) ?> • 
                                            Received: <?= date('M j, Y', strtotime($invitation['sent_at'])) ?>
                                        </div>
                                        <?php if ($invitation['message']): ?>
                                            <p style="margin-top: 10px; font-style: italic;">
                                                "<?= htmlspecialchars($invitation['message']) ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($invitation['status'] === 'pending'): ?>
                                            <a href="accept_invitation.php?token=<?= $invitation['invitation_token'] ?>" 
                                               class="btn btn-success" style="margin-right: 10px;">Accept</a>
                                            <a href="decline_invitation.php?token=<?= $invitation['invitation_token'] ?>" 
                                               class="btn btn-danger">Decline</a>
                                        <?php else: ?>
                                            <span class="invitation-status status-<?= $invitation['status'] ?>">
                                                <?= ucfirst($invitation['status']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Upgrade to Advisor Tab -->
            <?php if (!$isAdvisor): ?>
                <div id="upgrade" class="tab-content">
                    <?php if ($hasUpgradeRequest): ?>
                        <div class="advisor-upgrade-card">
                            <h3>🕒 Advisor Upgrade In Progress</h3>
                            <p>Your advisor upgrade request is currently being processed. You'll receive an email notification once it's approved.</p>
                        </div>
                    <?php else: ?>
                        <div class="advisor-upgrade-card">
                            <h3>🚀 Upgrade to Advisor Account</h3>
                            <p>Become a financial advisor and help clients manage their portfolios!</p>
                        </div>
                        
                        <div class="form-section">
                            <h3>Advisor Upgrade Request</h3>
                            <p style="margin-bottom: 20px;">
                                To upgrade to an advisor account, you need approval from a client. 
                                You can either select an existing user or invite someone new.
                            </p>
                            
                            <form method="POST">
                                <input type="hidden" name="action" value="upgrade_to_advisor">
                                
                                <div class="form-group">
                                    <label for="client_id_or_email">Client (User ID or Email):</label>
                                    <input type="text" id="client_id_or_email" name="client_id_or_email" 
                                           placeholder="Enter existing user ID or email address" required>
                                    <small style="color: #666;">
                                        Enter the user ID of an existing client or an email address to invite someone new.
                                    </small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="business_name">Business Name (optional):</label>
                                    <input type="text" id="business_name" name="business_name" 
                                           placeholder="Your Financial Advisory Business">
                                </div>
                                
                                <div class="form-group">
                                    <label for="credentials">Professional Credentials (optional):</label>
                                    <input type="text" id="credentials" name="credentials" 
                                           placeholder="CFP, CFA, ChFC, etc.">
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Why do you want to become an advisor?</label>
                                    <textarea id="description" name="description" rows="4" 
                                             placeholder="Describe your background and why you want to provide financial advisory services..." required></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">Submit Advisor Upgrade Request</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-button');
            buttons.forEach(button => button.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
    </script>
</body>
</html>