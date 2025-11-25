<?php
/**
 * 404 Error Page - Page Not Found
 */

// Include navigation header (don't require auth for error pages)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once 'UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
} catch (Exception $e) {
    $userAuth = null;
}

require_once 'nav_header.php';

// Get the requested page from the URL
$requestedPage = $_SERVER['REQUEST_URI'] ?? 'Unknown page';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .error-page {
            background: white;
            padding: 60px 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-top: 40px;
        }
        .error-page h1 {
            color: #dc3545;
            font-size: 4rem;
            margin-bottom: 20px;
        }
        .error-page h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        .error-page p {
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
        .quick-links {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 30px;
        }
        .quick-links h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .quick-links a {
            display: inline-block;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px;
            font-size: 14px;
        }
        .quick-links a:hover {
            background: #0056b3;
        }
        .error-details {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 14px;
            color: #856404;
        }
    </style>
</head>
<body>

<?php renderNavigationHeader('Enhanced Trading System'); ?>

<div class="container">
    <div class="error-page">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>Sorry, the page you're looking for doesn't exist or has been moved.</p>
        
        <div class="error-details">
            <strong>Requested:</strong> <?php echo htmlspecialchars($requestedPage); ?>
        </div>
        
        <div class="quick-links">
            <h3>Try these popular pages instead:</h3>
            <a href="index.php">Dashboard</a>
            <a href="portfolios.php">Portfolios</a>
            <a href="trades.php">Trade History</a>
            <a href="analytics.php">Analytics</a>
            <?php if ($userAuth && $userAuth->isLoggedIn() && $userAuth->isAdmin()): ?>
                <a href="admin_users.php">User Management</a>
                <a href="system_status.php">System Status</a>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn">Go to Dashboard</a>
            <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: #666;">
            <p>If you believe this is an error, please contact your system administrator.</p>
        </div>
    </div>
</div>

</body>
</html>
