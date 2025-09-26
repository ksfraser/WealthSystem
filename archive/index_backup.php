<?php
/**
 * Enhanced Trading System Dashboard - Main Index Page (Resilient Version)
 */

$authError = false;
$currentUser = null;
$isAdmin = false;
$navManager = null;

// Try to include authentication, but handle failures gracefully
try {
    // Only try auth if headers haven't been sent
    if (!headers_sent()) {
        require_once 'auth_check.php';
        
        // Try to load NavigationManager
        require_once 'NavigationManager.php';
        $navManager = getNavManager();
        $currentUser = $navManager->getCurrentUser();
        $isAdmin = $navManager->isAdmin();
    } else {
        $authError = true;
    }
} catch (Exception $e) {
    $authError = true;
    error_log('Auth error: ' . $e->getMessage());
} catch (Error $e) {
    $authError = true;
    error_log('Auth fatal error: ' . $e->getMessage());
}

// If auth failed, set defaults
if ($authError || !isset($navManager) || $navManager === null) {
    $currentUser = ['username' => 'Guest (Auth Unavailable)'];
    $isAdmin = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .success { border-left: 4px solid #28a745; }
        .info { border-left: 4px solid #007bff; }
        .warning { border-left: 4px solid #ffc107; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px; transition: background-color 0.3s;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .stat-card {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        /* Include Navigation CSS */
        <?php 
        if ($navManager) {
            echo $navManager->getNavigationCSS(); 
        } else {
            // Fallback CSS for navigation
            echo '
            .nav-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white; padding: 15px 0; margin-bottom: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .nav-header.admin { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
            .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
            .nav-title { font-size: 24px; font-weight: bold; margin: 0; }
            .nav-user { font-size: 14px; }
            .admin-badge { background: #ffffff; color: #dc3545; padding: 2px 6px; border-radius: 3px; font-size: 12px; font-weight: bold; margin-left: 10px; }
            ';
        }
        ?>
    </style>
</head>
<body>

<?php 
// Render navigation header using NavigationManager or fallback
if ($navManager) {
    $navManager->renderNavigationHeader('Enhanced Trading System Dashboard', 'dashboard');
} else {
    // Fallback navigation
    echo '<div class="nav-header' . ($isAdmin ? ' admin' : '') . '">';
    echo '<div class="nav-container">';
    echo '<h1 class="nav-title">Enhanced Trading System Dashboard</h1>';
    echo '<div class="nav-user">👤 ' . htmlspecialchars($currentUser['username']);
    if ($isAdmin) echo '<span class="admin-badge">ADMIN</span>';
    echo '</div></div></div>';
}
?>

<div class="container">
    <div class="header">
        <h1>Welcome to Your Trading Dashboard</h1>
        <p>Manage your portfolios, track trades, and analyze performance across all market segments</p>
    </div>

    <?php if ($authError): ?>
    <div class="card warning">
        <h3>⚠️ Authentication System Unavailable</h3>
        <p>The authentication system is currently unavailable, likely due to database connectivity issues.</p>
        <p>Please check database connectivity. You can still access basic functionality.</p>
        <a href="login.php" class="btn">Try Login Page</a>
        <a href="database.php" class="btn btn-warning">Check Database Status</a>
    </div>
    <?php elseif ($isAdmin): ?>
    <div class="card warning">
        <h3>🔧 Administrator Access</h3>
        <p>You have administrator privileges. You can manage users, system settings, and access all system functions.</p>
        <a href="admin_users.php" class="btn btn-warning">👥 User Management</a>
        <a href="system_status.php" class="btn btn-success">📊 System Status</a>
        <a href="database.php" class="btn">🗄️ Database Management</a>
    </div>
    <?php endif; ?>

    <div class="grid">
        <div class="card success">
            <h3>📈 Portfolio Overview</h3>
            <p>Monitor your investment performance across all market cap segments</p>
            <a href="portfolios.php" class="btn btn-success">View Portfolios</a>
        </div>

        <div class="card info">
            <h3>📋 Trade History</h3>
            <p>Review your trading activity and transaction history</p>
            <a href="trades.php" class="btn">View Trades</a>
        </div>

        <div class="card info">
            <h3>📊 Analytics & Reports</h3>
            <p>Analyze your trading patterns and portfolio performance</p>
            <a href="analytics.php" class="btn">View Analytics</a>
        </div>

        <div class="card">
            <h3>🎯 Quick Actions</h3>
            <p>Access frequently used features and system management</p>
            <?php 
            // Use NavigationManager to get role-based quick actions
            if ($navManager) {
                $quickActions = $navManager->getQuickActions();
                foreach ($quickActions as $action): 
                ?>
                    <a href="<?php echo htmlspecialchars($action['url']); ?>" class="<?php echo htmlspecialchars($action['class']); ?>">
                        <?php echo $action['icon']; ?> <?php echo htmlspecialchars($action['label']); ?>
                    </a>
                <?php 
                endforeach;
            } else {
                // Fallback quick actions
                ?>
                <a href="portfolios.php" class="btn btn-success">📈 Add Portfolio</a>
                <a href="trades.php" class="btn">📋 Record Trade</a>
                <?php if (!$authError && $isAdmin): ?>
                    <a href="admin_users.php" class="btn btn-warning">👥 Manage Users</a>
                    <a href="database.php" class="btn">🗄️ Database Tools</a>
                <?php endif;
            }
            ?>
        </div>
    </div>

    <div class="grid">
        <div class="stat-card">
            <div class="stat-number">✅</div>
            <div class="stat-label">System Status</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo htmlspecialchars($currentUser['username']); ?></div>
            <div class="stat-label">Current User</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number"><?php echo $isAdmin ? 'Admin' : 'User'; ?></div>
            <div class="stat-label">Access Level</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">🗄️</div>
            <div class="stat-label">Database Ready</div>
        </div>
    </div>

    <div class="card info">
        <h3>📋 System Information</h3>
        <p><strong>Database Architecture:</strong> Enhanced multi-driver system with PDO and MySQLi support</p>
        <p><strong>User Management:</strong> Role-based access control with admin privileges</p>
        <p><strong>Trading Features:</strong> Portfolio management, trade tracking, and performance analytics</p>
        <?php if ($isAdmin): ?>
            <p><strong>Admin Tools:</strong> User management, system monitoring, database administration</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>