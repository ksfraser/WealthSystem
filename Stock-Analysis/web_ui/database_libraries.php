<?php
/**
 * Modern Database Page - Using Existing Libraries
 * - Symfony Session (battle-tested)
 * - Ksfraser\Auth (your existing auth system)
 * - No custom SessionManager needed!
 */

require_once __DIR__ . '/bootstrap_symfony.php';

use Ksfraser\Auth\AuthManager;
use Ksfraser\Auth\AuthConfig;

try {
    // Use your existing auth system - proper constructor
    $pdo = new PDO('mysql:host=localhost;dbname=microcap_trading', 'root', '');
    $authManager = new AuthManager($pdo, [
        'session_name' => 'microcap_session'
    ]);
    
    // Check authentication using your existing system
    if (!$authManager->isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    
    $user = $authManager->getCurrentUser();
    if (!$user || !$authManager->isAdmin()) {  // Use isAdmin() method
        http_response_code(403);
        echo '<h1>403 Forbidden</h1><p>Admin access required.</p>';
        exit;
    }
    
} catch (Exception $e) {
    error_log('Auth error: ' . $e->getMessage());
    header('Location: /login.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Using Existing Libraries</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status-good { color: green; }
        .status-info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Database Management - Using Existing Libraries</h1>
    
    <div class="section">
        <h2>🎉 Using Battle-Tested Libraries</h2>
        <p class="status-good">✅ Symfony Session Component (handles all session complexity)</p>
        <p class="status-good">✅ Your existing Ksfraser\Auth system (already tested)</p>
        <p class="status-good">✅ No custom SessionManager needed</p>
        <p class="status-good">✅ No reinventing the wheel</p>
    </div>
    
    <div class="section">
        <h2>Session Information (Symfony)</h2>
        <?php $session = getSession(); ?>
        <p><strong>Session ID:</strong> <?= htmlspecialchars($session->getId()) ?></p>
        <p><strong>Session Started:</strong> <?= $session->isStarted() ? 'Yes' : 'No' ?></p>
        <p><strong>Session Name:</strong> <?= htmlspecialchars($session->getName()) ?></p>
    </div>
    
    <div class="section">
        <h2>Authentication (Ksfraser\Auth)</h2>
        <p><strong>User:</strong> <?= htmlspecialchars($user['username'] ?? 'Unknown') ?></p>
        <p><strong>Logged In:</strong> <?= $authManager->isLoggedIn() ? 'Yes' : 'No' ?></p>
        <p><strong>Admin:</strong> <?= $authManager->isAdmin() ? 'Yes' : 'No' ?></p>
    </div>
    
    <div class="section">
        <h2>What Symfony Provides (vs Custom Code)</h2>
        <table border="1" style="border-collapse: collapse; width: 100%;">
            <tr style="background: #f0f0f0;">
                <th>Feature</th>
                <th>Our Custom Code</th>
                <th>Symfony Session</th>
            </tr>
            <tr>
                <td>Session path creation</td>
                <td>Manual ensureSessionPath()</td>
                <td>✅ Automatic</td>
            </tr>
            <tr>
                <td>Headers sent checking</td>
                <td>Manual headers_sent()</td>
                <td>✅ Automatic</td>
            </tr>
            <tr>
                <td>Session configuration</td>
                <td>Manual ini_set()</td>
                <td>✅ Configuration arrays</td>
            </tr>
            <tr>
                <td>Flash messages</td>
                <td>Not implemented</td>
                <td>✅ Built-in FlashBag</td>
            </tr>
            <tr>
                <td>CSRF protection</td>
                <td>Manual token generation</td>
                <td>✅ Built-in CSRF component</td>
            </tr>
            <tr>
                <td>Testing support</td>
                <td>None</td>
                <td>✅ MockSessionStorage</td>
            </tr>
        </table>
    </div>
    
    <div class="section">
        <h2>Recommendation</h2>
        <p class="status-info">🏆 <strong>Delete our custom SessionManager and use:</strong></p>
        <ol>
            <li><strong>Symfony Session</strong> for session management</li>
            <li><strong>Your existing Ksfraser\Auth</strong> for authentication</li>
            <li><strong>Symfony Security</strong> for advanced authorization (if needed)</li>
        </ol>
        <p>This eliminates ~250 lines of custom code and replaces it with battle-tested libraries.</p>
    </div>
    
    <p><a href="javascript:history.back()">← Back</a></p>
</body>
</html>
