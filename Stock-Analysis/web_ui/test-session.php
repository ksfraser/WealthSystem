<?php
/**
 * Session Diagnostic
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Diagnostic</h1>";
echo "<style>body { font-family: monospace; } .success { color: green; } .error { color: red; }</style>";

// Check session status before any includes
echo "<h2>Before Loading Files</h2>";
echo "Session Status: " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "Session ID: " . (session_id() ?: 'none') . "<br>";

// Load UserAuthDAO
require_once __DIR__ . '/UserAuthDAO.php';

echo "<h2>After Loading UserAuthDAO</h2>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . (session_id() ?: 'none') . "<br>";

// Create auth instance
$auth = new UserAuthDAO();

echo "<h2>After Creating UserAuthDAO Instance</h2>";
echo "Session Status: " . session_status() . "<br>";
echo "Session ID: " . (session_id() ?: 'none') . "<br>";

// Check if logged in
$isLoggedIn = $auth->isLoggedIn();
echo "<h2>Login Status</h2>";
if ($isLoggedIn) {
    echo "<div class='success'>✅ Logged in</div>";
    $user = $auth->getCurrentUser();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "<div class='error'>❌ Not logged in</div>";
}

// Show session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION ?? []);
echo "</pre>";

// Show cookies
echo "<h2>Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";
