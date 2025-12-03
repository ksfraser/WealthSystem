<?php
/**
 * Session Configuration Debug
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Configuration</h1>";
echo "<style>body { font-family: monospace; }</style>";

// Start session
session_start();

echo "<h2>Session Settings</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Name: " . session_name() . "<br>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h2>Session Cookie Parameters</h2>";
$params = session_get_cookie_params();
echo "<pre>";
print_r($params);
echo "</pre>";

echo "<h2>PHP Session INI Settings</h2>";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "<br>";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "<br>";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "<br>";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "<br>";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "<br>";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "<br>";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "<br>";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "<br>";

echo "<h2>HTTP Headers</h2>";
if (function_exists('apache_response_headers')) {
    echo "<pre>";
    print_r(apache_response_headers());
    echo "</pre>";
} else {
    echo "apache_response_headers() not available<br>";
}

echo "<h2>Cookies Sent to Browser</h2>";
echo "PHP Session Cookie (PHPSESSID): ";
if (isset($_COOKIE[session_name()])) {
    echo $_COOKIE[session_name()];
} else {
    echo "NOT SET - This is the problem!";
}
echo "<br>";

echo "<h2>All Cookies</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Test: Set Session Data</h2>";
$_SESSION['test_time'] = time();
$_SESSION['test_value'] = 'hello';
echo "Set test data in session<br>";
echo "Current session data:<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='" . $_SERVER['PHP_SELF'] . "'>Reload this page</a> - session data should persist</p>";
