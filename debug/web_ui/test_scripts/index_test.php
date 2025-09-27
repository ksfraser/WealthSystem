<?php
/**
 * Simple Index Test - Check if CSS issue is resolved
 */

require_once 'auth_check.php';
$currentUser = getCurrentUser();
$isAdmin = isCurrentUserAdmin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Test - Enhanced Trading System</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0; padding: 0; background: #f5f5f5; 
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .btn { 
            display: inline-block; padding: 10px 20px; background: #007bff; color: white; 
            text-decoration: none; border-radius: 4px; margin: 5px;
        }
        .btn:hover { background: #0056b3; }
        .card { 
            background: white; padding: 20px; margin: 10px 0; border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .nav-header.admin {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
    </style>
</head>
<body>

<div class="nav-header<?php echo $isAdmin ? ' admin' : ''; ?>">
    <div style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h1>Enhanced Trading System - Test Page</h1>
        <p>User: <?php echo htmlspecialchars($currentUser['username']); ?>
        <?php if ($isAdmin): ?>
            <span style="background: white; color: red; padding: 2px 6px; border-radius: 3px; margin-left: 10px;">ADMIN</span>
        <?php endif; ?>
        </p>
    </div>
</div>

<div class="container">
    <div class="card">
        <h2>CSS Test Results</h2>
        <p>If you can see this styled properly with:</p>
        <ul>
            <li>✅ Proper fonts and spacing</li>
            <li>✅ Blue buttons (or red header for admin)</li>
            <li>✅ Card styling with shadows</li>
            <li>✅ No raw CSS text visible</li>
        </ul>
        <p>Then the CSS issue is resolved!</p>
        
        <a href="index.php" class="btn">Back to Real Index</a>
        <a href="admin_users.php" class="btn">Admin Users</a>
    </div>
</div>

</body>
</html>
