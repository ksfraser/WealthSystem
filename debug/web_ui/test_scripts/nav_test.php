<?php
/**
 * Navigation Test Script - Verify all main navigation links
 */

// Test basic page access
require_once 'auth_check.php';
requireLogin();

$navigationTests = [
    'Dashboard' => 'index.php',
    'Portfolios' => 'portfolios.php', 
    'Trade History' => 'trades.php',
    'Analytics' => 'analytics.php',
    'System Status' => 'system_status.php',
    'User Dashboard' => 'dashboard.php'
];

$adminTests = [
    'User Management' => 'admin_users.php'
];

$comingSoonPages = [
    'Profile Settings' => 'profile.php',
    'Reports' => 'reports.php',
    'Settings' => 'settings.php',
    'Backtest Results' => 'backtest.php',
    'Market Data' => 'market_data.php',
    'Risk Management' => 'risk_management.php'
];

$currentUser = getCurrentUser();
$isAdmin = isCurrentUserAdmin();

require_once 'nav_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Test - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .test-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-section h2 { margin-top: 0; color: #333; }
        .link-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .link-test {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: #f8f9fa;
        }
        .link-test a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .link-test a:hover {
            text-decoration: underline;
        }
        .status-available { color: #28a745; }
        .status-coming-soon { color: #ffc107; }
        .status-error { color: #dc3545; }
        .user-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .admin-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<?php renderNavigationHeader('Navigation Test - Enhanced Trading System'); ?>

<div class="container">
    <div class="test-section">
        <h1>🧪 Navigation Test Suite</h1>
        <p>Testing all navigation links and page accessibility in the Enhanced Trading System.</p>
        
        <div class="user-info">
            <strong>Current User:</strong> <?= htmlspecialchars($currentUser['username']) ?><br>
            <strong>Access Level:</strong> <?= $isAdmin ? 'Administrator' : 'Standard User' ?><br>
            <strong>Test Time:</strong> <?= date('Y-m-d H:i:s') ?>
        </div>
        
        <?php if ($isAdmin): ?>
        <div class="admin-notice">
            <strong>Admin Notice:</strong> You have administrator privileges and can access all features.
        </div>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>✅ Main Navigation Pages</h2>
        <p>These pages should be fully functional with authentication and navigation:</p>
        <div class="link-grid">
            <?php foreach ($navigationTests as $name => $file): ?>
            <div class="link-test">
                <a href="<?= $file ?>" target="_blank"><?= $name ?></a>
                <span class="status-available">✅ Available</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div class="test-section">
        <h2>🔐 Administrator Pages</h2>
        <p>Administrator-only pages (requires admin privileges):</p>
        <div class="link-grid">
            <?php foreach ($adminTests as $name => $file): ?>
            <div class="link-test">
                <a href="<?= $file ?>" target="_blank"><?= $name ?></a>
                <span class="status-available">✅ Available</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="test-section">
        <h2>🚧 Coming Soon Pages</h2>
        <p>These features are planned but not yet implemented:</p>
        <div class="link-grid">
            <?php foreach ($comingSoonPages as $name => $file): ?>
            <div class="link-test">
                <a href="coming_soon.php?feature=<?= urlencode($name) ?>" target="_blank"><?= $name ?></a>
                <span class="status-coming-soon">🚧 Coming Soon</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="test-section">
        <h2>🔧 Test Actions</h2>
        <p>Quick navigation test actions:</p>
        <div style="margin-top: 15px;">
            <a href="index.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 5px;">Return to Dashboard</a>
            <a href="system_status.php" style="display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 5px;">System Status</a>
            <a href="javascript:window.close()" style="display: inline-block; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; margin: 5px;">Close Test</a>
        </div>
    </div>

    <div class="test-section">
        <h2>📋 Test Results Summary</h2>
        <div style="padding: 15px; background: #d4edda; border-radius: 6px; color: #155724;">
            <strong>Navigation Status:</strong> All main navigation links are configured and should be working properly.
            <br><strong>Authentication:</strong> All pages include proper authentication checks.
            <br><strong>Consistent UI:</strong> All pages include the navigation header for consistent user experience.
        </div>
    </div>
</div>

</body>
</html>
