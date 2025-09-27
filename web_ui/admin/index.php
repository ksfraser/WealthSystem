<?php
require_once '../UserAuthDAO.php';
require_once '../NavigationService.php';

// Check authentication and admin status
$auth = new UserAuthDAO();
$auth->requireAdmin();

// Initialize Navigation Service
$navigation = new NavigationService();
$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <?php echo $navigation->getNavigationCSS(); ?>
    <?php echo $navigation->getAdminCSS(); ?>
    <style>
        .admin-dashboard {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .admin-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
            border-left: 4px solid #007bff;
        }
        .admin-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .admin-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .feature-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .status-indicator {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status-online {
            background-color: #d4edda;
            color: #155724;
        }
        .status-offline {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <?php echo $navigation->renderNavigationHeader($pageTitle, 'admin'); ?>
    
    <div class="admin-dashboard">
        <div class="admin-card">
            <h2>🔧 System Administration</h2>
            <p>Manage your trading platform, users, and system configuration from this central dashboard.</p>
        </div>

        <div class="admin-card-grid">
            <!-- User Management -->
            <div class="admin-card">
                <div class="feature-icon">👥</div>
                <h3>User Management</h3>
                <p>Manage user accounts, permissions, and access controls.</p>
                <a href="../admin_users.php" class="btn">Manage Users</a>
            </div>

            <!-- Advisor Management -->
            <div class="admin-card">
                <div class="feature-icon">🤝</div>
                <h3>Advisor Management</h3>
                <p>Configure advisor relationships and portfolio sharing.</p>
                <a href="../admin_advisor_management.php" class="btn">Manage Advisors</a>
            </div>

            <!-- Stock Data Management -->
            <div class="admin-card">
                <div class="feature-icon">📈</div>
                <h3>Stock Data Management</h3>
                <p>Fetch current data, populate historical records, and configure automated data updates.</p>
                <a href="stock_data_admin.php" class="btn">Manage Stock Data</a>
            </div>

            <!-- Database Management -->
            <div class="admin-card">
                <div class="feature-icon">🗄️</div>
                <h3>Database Management</h3>
                <p>Monitor database status, run diagnostics, and manage data integrity.</p>
                <a href="../database.php" class="btn">Database Tools</a>
            </div>

            <!-- System Status -->
            <div class="admin-card">
                <div class="feature-icon">⚙️</div>
                <h3>System Status</h3>
                <p>Monitor system health, check services, and view logs.</p>
                <div>
                    <strong>Database:</strong> 
                    <span class="status-indicator status-online">Online</span>
                </div>
                <div style="margin-top: 10px;">
                    <strong>Auto-Fetch:</strong> 
                    <?php
                    $autoFetchFile = '../data/auto_fetch_config.json';
                    $autoFetchEnabled = false;
                    if (file_exists($autoFetchFile)) {
                        $config = json_decode(file_get_contents($autoFetchFile), true);
                        $autoFetchEnabled = isset($config['auto_fetch_enabled']) && $config['auto_fetch_enabled'] === true;
                    }
                    ?>
                    <span class="status-indicator <?php echo $autoFetchEnabled ? 'status-online' : 'status-offline'; ?>">
                        <?php echo $autoFetchEnabled ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
            </div>

            <!-- Analytics -->
            <div class="admin-card">
                <div class="feature-icon">📊</div>
                <h3>System Analytics</h3>
                <p>View usage statistics, performance metrics, and system analytics.</p>
                <a href="../analytics.php" class="btn">View Analytics</a>
            </div>
        </div>

        <div class="admin-card">
            <h3>📋 Quick Actions</h3>
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <a href="stock_data_admin.php" class="btn">Fetch Latest Stock Data</a>
                <a href="../system_status.php" class="btn">System Health Check</a>
                <a href="../database.php" class="btn">Database Diagnostics</a>
                <a href="../admin_users.php" class="btn">Create New User</a>
            </div>
        </div>
    </div>
</body>
</html>