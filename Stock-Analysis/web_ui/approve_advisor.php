<?php
/**
 * Advisor Upgrade Approval Page
 * 
 * Allows clients to approve or deny advisor upgrade requests
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/InvitationService.php';

$auth = new UserAuthDAO();
$invitationService = new InvitationService();

// Require login
$auth->requireLogin();

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token)) {
    die("Invalid request token");
}

$currentUser = $auth->getCurrentUser();
$message = '';
$messageType = '';

// Get upgrade request details
$upgradeRequest = $invitationService->getUpgradeRequestByToken($token);

if (!$upgradeRequest || $upgradeRequest['client_id'] != $currentUser['id']) {
    die("Invalid or expired request");
}

// Handle approval/denial
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approve = isset($_POST['approve']);
    
    try {
        $result = $invitationService->processClientApproval($token, $approve);
        $message = $result['message'];
        $messageType = $result['success'] ? 'success' : 'error';
        
        // Refresh upgrade request data
        $upgradeRequest = $invitationService->getUpgradeRequestByToken($token);
        
    } catch (Exception $e) {
        error_log("Advisor approval error: " . $e->getMessage());
        $message = "An error occurred while processing your decision";
        $messageType = 'error';
    }
}

$applicant = $auth->getUserById($upgradeRequest['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisor Upgrade Request - Portfolio Management</title>
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
            max-width: 800px;
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
            font-size: 2rem;
            margin-bottom: 10px;
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
        
        .request-card {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .applicant-info {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .applicant-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            margin-right: 20px;
        }
        
        .applicant-details h2 {
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .applicant-details p {
            color: #6c757d;
        }
        
        .request-details {
            margin-bottom: 25px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            padding: 10px 0;
        }
        
        .detail-label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 150px;
        }
        
        .detail-value {
            color: #495057;
        }
        
        .description-box {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 15px;
            margin-top: 10px;
            font-style: italic;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
            text-align: center;
            margin-top: 20px;
        }
        
        .status-approved {
            background: #d4edda;
            color: #155724;
        }
        
        .status-denied {
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
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        .info-box h3 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .info-box p {
            color: #424242;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Advisor Upgrade Request</h1>
            <p>Review and approve advisor application</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h3>What does this mean?</h3>
                <p><strong><?= htmlspecialchars($applicant['username']) ?></strong> has requested to upgrade their account to become a financial advisor.</p>
                <p>If approved, they will be able to access and manage portfolios for clients who grant them permission.</p>
                <p>As the sponsoring client, your approval is required for their advisor certification.</p>
            </div>
            
            <div class="request-card">
                <div class="applicant-info">
                    <div class="applicant-avatar">
                        <?= strtoupper(substr($applicant['username'], 0, 1)) ?>
                    </div>
                    <div class="applicant-details">
                        <h2><?= htmlspecialchars($applicant['username']) ?></h2>
                        <p><?= htmlspecialchars($applicant['email']) ?></p>
                    </div>
                </div>
                
                <div class="request-details">
                    <div class="detail-row">
                        <div class="detail-label">Requested On:</div>
                        <div class="detail-value"><?= date('F j, Y \a\t g:i A', strtotime($upgradeRequest['created_at'])) ?></div>
                    </div>
                    
                    <?php if ($upgradeRequest['business_name']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Business Name:</div>
                            <div class="detail-value"><?= htmlspecialchars($upgradeRequest['business_name']) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($upgradeRequest['credentials']): ?>
                        <div class="detail-row">
                            <div class="detail-label">Credentials:</div>
                            <div class="detail-value"><?= htmlspecialchars($upgradeRequest['credentials']) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value"><?= ucfirst($upgradeRequest['status']) ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label">Application Statement:</div>
                        <div class="detail-value">
                            <div class="description-box">
                                <?= nl2br(htmlspecialchars($upgradeRequest['description'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($upgradeRequest['status'] === 'pending'): ?>
                    <form method="POST">
                        <div class="action-buttons">
                            <button type="submit" name="approve" class="btn btn-success">
                                ✅ Approve as My Advisor
                            </button>
                            <button type="submit" name="deny" class="btn btn-danger">
                                ❌ Decline Request
                            </button>
                        </div>
                    </form>
                <?php elseif ($upgradeRequest['status'] === 'approved'): ?>
                    <div class="status-badge status-approved">
                        ✅ Request Approved - <?= htmlspecialchars($applicant['username']) ?> is now an advisor
                    </div>
                <?php else: ?>
                    <div class="status-badge status-denied">
                        ❌ Request Declined
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="back-link">
                <a href="dashboard.php">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>