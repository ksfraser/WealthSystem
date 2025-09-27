<?php
/**
 * Clean Index Page - No CSS Issues
 */

// Include authentication check (will redirect if not logged in)
require_once 'auth_check.php';

// Get user info without including problematic nav_header.php
$currentUser = getCurrentUser();
$isAdmin = isCurrentUserAdmin();
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
        
        /* Navigation Header Styles */
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav-title {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .nav-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }
        .admin-badge {
            background: #ffffff;
            color: #dc3545;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            border: 1px solid #ffffff;
        }
        .nav-header.admin .admin-badge {
            background: #ffffff;
            color: #dc3545;
            border: 2px solid #ffffff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .user-info {
            font-size: 14px;
        }
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<!-- Navigation Header -->
<div class="nav-header<?php echo $isAdmin ? ' admin' : ''; ?>">
    <div class="nav-container">
        <h1 class="nav-title">Enhanced Trading System Dashboard</h1>
        
        <div class="nav-user">
            <div class="nav-links">
                <a href="index.php">Dashboard</a>
                <a href="portfolios.php">Portfolios</a>
                <a href="trades.php">Trades</a>
                <a href="analytics.php">Analytics</a>
                <?php if ($isAdmin): ?>
                    <a href="admin_users.php">Users</a>
                    <a href="system_status.php">System</a>
                    <a href="database.php">Database</a>
                <?php endif; ?>
            </div>
            
            <div class="user-info">
                <span>👤 <?php echo htmlspecialchars($currentUser['username']); ?></span>
                <?php if ($isAdmin): ?>
                    <span class="admin-badge">ADMIN</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>Welcome to Your Trading Dashboard</h1>
        <p>Manage your portfolios, track trades, and analyze performance across all market segments</p>
    </div>

    <?php if ($isAdmin): ?>
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
            <p>Access frequently used features</p>
            <a href="portfolios.php" class="btn">Add Portfolio</a>
            <a href="trades.php" class="btn">Record Trade</a>
            <?php if ($isAdmin): ?>
                <a href="admin_users.php" class="btn btn-warning">Manage Users</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid">
        <div class="stat-card">
            <div class="stat-number">Active</div>
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
    </div>
</div>

</body>
</html>
