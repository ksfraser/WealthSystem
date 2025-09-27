<?php
/**
 * Simple Database Page - No Navigation Header
 * Testing version to isolate HTTP 500 error
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic auth check without redirect
try {
    require_once 'UserAuthDAO.php';
    $userAuth = new UserAuthDAO();
    
    if (!$userAuth->isLoggedIn()) {
        echo "<!DOCTYPE html><html><head><title>Login Required</title></head><body>";
        echo "<h1>Login Required</h1>";
        echo "<p><a href='login.php'>Please log in</a></p>";
        echo "</body></html>";
        exit;
    }
    
    $currentUser = $userAuth->getCurrentUser();
    
} catch (Exception $e) {
    echo "Auth error: " . $e->getMessage();
    exit;
}

// Test database connectivity
$dbStatus = 'disconnected';
$dbMessage = 'Unable to connect to database';
$dbDetails = [];

try {
    require_once 'database_loader.php';
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    
    if ($connection) {
        $dbStatus = 'connected';
        $dbMessage = 'Database connection successful';
        
        $dbDetails = [
            'driver' => \Ksfraser\Database\EnhancedDbManager::getCurrentDriver(),
            'config_source' => 'Enhanced Database Configuration'
        ];
        
        // Test query
        try {
            $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row) {
                $dbDetails['user_count'] = $row['count'];
            }
        } catch (Exception $e) {
            $dbDetails['query_error'] = $e->getMessage();
        }
    }
} catch (Exception $e) {
    $dbStatus = 'error';
    $dbMessage = 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Database Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 4px; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 4px; }
        .info { background: #d1ecf1; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Simple Database Test</h1>
    
    <div class="info">
        <strong>User:</strong> <?php echo htmlspecialchars($currentUser['username']); ?>
        <?php if ($userAuth->isAdmin()): ?>
            <span style="background: red; color: white; padding: 2px 6px; border-radius: 3px; margin-left: 10px;">ADMIN</span>
        <?php endif; ?>
    </div>
    
    <h2>Database Status</h2>
    <div class="<?php echo $dbStatus === 'connected' ? 'success' : 'error'; ?>">
        <strong>Status:</strong> <?php echo ucfirst($dbStatus); ?><br>
        <strong>Message:</strong> <?php echo htmlspecialchars($dbMessage); ?>
    </div>
    
    <?php if ($dbStatus === 'connected' && !empty($dbDetails)): ?>
        <h3>Database Details</h3>
        <ul>
            <li><strong>Driver:</strong> <?php echo htmlspecialchars($dbDetails['driver'] ?? 'Unknown'); ?></li>
            <li><strong>Config:</strong> <?php echo htmlspecialchars($dbDetails['config_source'] ?? 'Unknown'); ?></li>
            <?php if (isset($dbDetails['user_count'])): ?>
                <li><strong>Users:</strong> <?php echo $dbDetails['user_count']; ?></li>
            <?php endif; ?>
            <?php if (isset($dbDetails['query_error'])): ?>
                <li><strong>Query Error:</strong> <?php echo htmlspecialchars($dbDetails['query_error']); ?></li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
    
    <p><a href="index.php">Back to Dashboard</a> | <a href="database.php">Try Full Database Page</a></p>
</body>
</html>
