<?php
/**
 * Debug which SessionManager is being used
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>SessionManager Debug</h1>";
echo "<style>body { font-family: monospace; }</style>";

// Load bootstrap
$container = require_once __DIR__ . '/bootstrap.php';

echo "<h2>SessionManager Classes Found</h2>";
echo "1. Global SessionManager: " . (class_exists('SessionManager', false) ? 'loaded' : 'not loaded') . "<br>";
echo "2. App\\Core\\SessionManager: " . (class_exists('App\\Core\\SessionManager', false) ? 'loaded' : 'not loaded') . "<br>";
echo "3. App\\Security\\SessionManager: " . (class_exists('App\\Security\\SessionManager', false) ? 'loaded' : 'not loaded') . "<br>";

echo "<h2>UserAuthDAO Test</h2>";
$auth = $container->get(UserAuthDAO::class);
echo "UserAuthDAO created via container<br>";

// Use reflection to see what SessionManager it's using
$reflection = new ReflectionClass($auth);
$property = $reflection->getProperty('sessionManager');
$property->setAccessible(true);
$sessionManager = $property->getValue($auth);

echo "SessionManager class: " . get_class($sessionManager) . "<br>";
echo "SessionManager active: " . ($sessionManager->isSessionActive() ? 'Yes' : 'No') . "<br>";

echo "<h2>Session Data</h2>";
echo "Session status: " . session_status() . "<br>";
echo "Session ID: " . (session_id() ?: 'none') . "<br>";
echo "<pre>";
print_r($_SESSION ?? []);
echo "</pre>";

echo "<h2>Login Check</h2>";
if ($auth->isLoggedIn()) {
    echo "✅ Logged in<br>";
    $user = $auth->getCurrentUser();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "❌ Not logged in<br>";
}
