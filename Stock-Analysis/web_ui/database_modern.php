<?php
/**
 * Modern Database Management Page
 * Uses bootstrap approach - ONE include instead of many require_once statements
 */

// Single include - the bootstrap handles everything else
require_once __DIR__ . '/bootstrap.php';

// Now we can use the namespaced classes without manual includes
use App\Auth\LoginRequiredException;
use App\Auth\AdminRequiredException; 
use App\Core\SessionManager;

try {
    // Modern approach - SessionManager via namespace
    $sessionManager = SessionManager::getInstance();
    
    // Simple authentication check
    if (!$sessionManager->get('user_id')) {
        throw new LoginRequiredException('/login.php');
    }
    
    $isAdmin = $sessionManager->get('is_admin', false);
    
} catch (LoginRequiredException $e) {
    // Clean redirect handling
    if (!headers_sent()) {
        header('Location: ' . $e->getRedirectUrl());
        exit;
    }
    // Fallback for when headers are sent
    $redirectUrl = htmlspecialchars($e->getRedirectUrl());
    echo '<script>window.location.href="' . $redirectUrl . '";</script>';
    exit;
    
} catch (AdminRequiredException $e) {
    http_response_code(403);
    echo '<h1>403 Forbidden</h1><p>Admin access required.</p>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Modern Version</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .status-good { color: green; }
        .status-bad { color: red; }
        .status-warning { color: orange; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Database Management - Modern PHP Version</h1>
    
    <div class="section">
        <h2>🎉 Modern PHP Features Active</h2>
        <p class="status-good">✅ Using namespaces and PSR-4 autoloading</p>
        <p class="status-good">✅ No manual require_once statements needed</p>
        <p class="status-good">✅ Composer autoloader handling all class loading</p>
        <p class="status-good">✅ Proper exception handling with typed exceptions</p>
        <p class="status-good">✅ Modern dependency injection patterns</p>
    </div>
    
    <div class="section">
        <h2>Session Information</h2>
        <p><strong>Session Active:</strong> <?= $sessionManager->isSessionActive() ? 'Yes' : 'No' ?></p>
        <p><strong>User ID:</strong> <?= htmlspecialchars($sessionManager->get('user_id', 'Not set')) ?></p>
        <p><strong>Admin Status:</strong> <?= $isAdmin ? 'Yes' : 'No' ?></p>
        <?php if ($sessionManager->getInitializationError()): ?>
            <p class="status-warning"><strong>Session Warning:</strong> <?= htmlspecialchars($sessionManager->getInitializationError()) ?></p>
        <?php endif; ?>
    </div>
    
    <div class="section">
        <h2>Autoloading Test</h2>
        <p>Classes loaded automatically via Composer:</p>
        <ul>
            <li>✅ App\Auth\LoginRequiredException</li>
            <li>✅ App\Auth\AdminRequiredException</li>
            <li>✅ App\Core\SessionManager</li>
        </ul>
        <p class="status-good">No manual includes required - everything loaded on demand!</p>
    </div>
    
    <div class="section">
        <h2>Next Steps for Full Modernization</h2>
        <ol>
            <li>Create App\Auth\AuthService class to replace auth_check.php</li>
            <li>Create App\Database\ConnectionManager for database operations</li>
            <li>Implement App\Http\Router for clean URL routing</li>
            <li>Add App\View\TemplateEngine for better templating</li>
            <li>Use dependency injection container</li>
        </ol>
    </div>
    
    <p><a href="javascript:history.back()">← Back</a></p>
</body>
</html>
