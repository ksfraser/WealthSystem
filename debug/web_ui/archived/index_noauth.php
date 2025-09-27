<?php
/**
 * Temporary Index Page - No Auth for Testing
 */
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
        .warning { border-left: 4px solid #ffc107; }
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; text-align: center; }
        .nav-title { font-size: 24px; font-weight: bold; margin: 0; }
    </style>
</head>
<body>

<div class="nav-header">
    <div class="nav-container">
        <h1 class="nav-title">Enhanced Trading System Dashboard (Test Mode)</h1>
    </div>
</div>

<div class="container">
    <div class="header">
        <h1>System Status Test</h1>
        <p>Testing basic page functionality without authentication</p>
    </div>

    <div class="card warning">
        <h3>🔧 Test Mode Active</h3>
        <p>This is a temporary test page to verify basic functionality.</p>
        <p>If you can see this page, the web server and PHP are working correctly.</p>
        <p>The 500 error is likely related to database connectivity or authentication.</p>
    </div>

    <div class="card">
        <h3>📋 System Information</h3>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
        <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Server Software:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
    </div>
</div>

</body>
</html>
