<?php
/**
 * Coming Soon Page Template
 * Use this for pages that are planned but not yet implemented
 */

// Get the page name from the URL
$pageName = $_GET['page'] ?? 'This Feature';
$pageTitle = ucwords(str_replace(['_', '-'], ' ', $pageName));

// Include authentication check (will redirect if not logged in)
require_once 'auth_check.php';

// Include navigation header
require_once 'nav_header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .coming-soon {
            background: white;
            padding: 60px 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        .coming-soon h1 {
            color: #667eea;
            font-size: 3rem;
            margin-bottom: 20px;
        }
        .coming-soon h2 {
            color: #333;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .coming-soon p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .feature-list {
            text-align: left;
            max-width: 600px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
        }
        .feature-list h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .feature-list ul {
            color: #666;
        }
        .feature-list li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>

<?php renderNavigationHeader('Enhanced Trading System'); ?>

<div class="container">
    <div class="coming-soon">
        <h1>🚧</h1>
        <h2><?php echo htmlspecialchars($pageTitle); ?> - Coming Soon</h2>
        <p>This feature is currently under development and will be available in a future update.</p>
        <p>We're working hard to bring you the best trading and portfolio management experience possible.</p>
        
        <div class="feature-list">
            <h3>What you can do right now:</h3>
            <ul>
                <li>✅ <strong>User Management:</strong> Create and manage user accounts</li>
                <li>✅ <strong>Portfolio Tracking:</strong> View portfolio data and performance</li>
                <li>✅ <strong>Trade History:</strong> Review trading activity and logs</li>
                <li>✅ <strong>System Status:</strong> Monitor system health and performance</li>
                <li>✅ <strong>Database Management:</strong> Access and manage system data</li>
                <li>🔄 <strong>Advanced Analytics:</strong> In development</li>
                <li>🔄 <strong>Automated Trading:</strong> In development</li>
                <li>🔄 <strong>Advanced Reporting:</strong> In development</li>
            </ul>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn">Return to Dashboard</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: #666;">
            <p>Have a suggestion for this feature? Contact your system administrator.</p>
        </div>
    </div>
</div>

</body>
</html>
