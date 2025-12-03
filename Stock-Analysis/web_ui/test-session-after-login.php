<?php
/**
 * Test Session After Login
 * Access this immediately after successful login to see session state
 */

require_once __DIR__ . '/SessionManager.php';
$sessionManager = SessionManager::getInstance();

header('Content-Type: text/plain');

echo "=== SESSION DIAGNOSTIC AFTER LOGIN ===\n\n";

echo "1. Session Status:\n";
echo "   - PHP Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "ACTIVE" : "INACTIVE") . "\n";
echo "   - Session ID: " . session_id() . "\n";
echo "   - SessionManager Active: " . ($sessionManager->isSessionActive() ? "Yes" : "No") . "\n\n";

echo "2. Cookie Information:\n";
echo "   - PHPSESSID in \$_COOKIE: " . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : "NOT SET") . "\n";
echo "   - All Cookies: " . print_r($_COOKIE, true) . "\n\n";

echo "3. Session Data (Raw \$_SESSION):\n";
echo print_r($_SESSION, true) . "\n\n";

echo "4. User Auth Data (via SessionManager):\n";
$userAuth = $sessionManager->get('user_auth');
if ($userAuth) {
    echo "   - Found user_auth in session\n";
    echo "   - User ID: " . ($userAuth['id'] ?? 'not set') . "\n";
    echo "   - Username: " . ($userAuth['username'] ?? 'not set') . "\n";
    echo "   - Login Time: " . ($userAuth['login_time'] ?? 'not set') . "\n";
    echo "   - Full data: " . print_r($userAuth, true) . "\n";
} else {
    echo "   - NO user_auth found in session\n";
}
echo "\n";

echo "5. HTTP Headers (what we're sending):\n";
$headers = headers_list();
foreach ($headers as $header) {
    if (stripos($header, 'Set-Cookie') !== false || stripos($header, 'Cookie') !== false) {
        echo "   - $header\n";
    }
}
if (empty($headers)) {
    echo "   - No headers set yet\n";
}
echo "\n";

echo "6. Session Cookie Configuration:\n";
$params = session_get_cookie_params();
echo "   - Lifetime: " . $params['lifetime'] . "\n";
echo "   - Path: " . $params['path'] . "\n";
echo "   - Domain: " . $params['domain'] . "\n";
echo "   - Secure: " . ($params['secure'] ? 'true' : 'false') . "\n";
echo "   - HttpOnly: " . ($params['httponly'] ? 'true' : 'false') . "\n";
echo "   - SameSite: " . ($params['samesite'] ?? 'not set') . "\n\n";

echo "7. Request Information:\n";
echo "   - Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
echo "   - HTTP Host: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n";
echo "   - User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') . "\n\n";

echo "=== DIAGNOSIS ===\n";
if (!isset($_COOKIE['PHPSESSID'])) {
    echo "❌ PROBLEM: Browser is NOT sending PHPSESSID cookie back\n";
    echo "   This means the browser doesn't support cookies or is blocking them.\n";
    echo "   - VS Code Simple Browser: Known to not handle cookies properly\n";
    echo "   - Solution: Use Chrome, Firefox, or Edge\n";
} elseif ($_COOKIE['PHPSESSID'] !== session_id()) {
    echo "❌ PROBLEM: Session ID mismatch\n";
    echo "   Cookie: " . $_COOKIE['PHPSESSID'] . "\n";
    echo "   Server: " . session_id() . "\n";
} elseif (!$userAuth) {
    echo "❌ PROBLEM: Session cookie working, but no user_auth data\n";
    echo "   This means login didn't properly set session data, or session was destroyed.\n";
} else {
    echo "✅ SESSION IS WORKING CORRECTLY\n";
    echo "   User is logged in as: " . $userAuth['username'] . "\n";
}
