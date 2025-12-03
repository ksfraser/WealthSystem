<?php
/**
 * Test Login Process Step by Step
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Process Test</h1>";
echo "<style>body { font-family: monospace; } .success { color: green; } .error { color: red; }</style>";

// Step 1: Load dependencies
require_once __DIR__ . '/UserAuthDAO.php';
echo "<h2>Step 1: Create UserAuthDAO</h2>";
$auth = new UserAuthDAO();
echo "✅ UserAuthDAO created<br>";
echo "Session active: " . ($auth->isLoggedIn() ? 'Yes' : 'No') . "<br>";

// Step 2: Check database
echo "<h2>Step 2: Check Database Connection</h2>";
$pdo = $auth->getPdo();
if ($pdo) {
    echo "✅ Database connected<br>";
    
    // Check if users exist
    $stmt = $pdo->query("SELECT username FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Available users: " . implode(', ', $users) . "<br>";
} else {
    echo "<div class='error'>❌ No database connection</div>";
    exit;
}

// Step 3: Try login with form
echo "<h2>Step 3: Login Test</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "Attempting login for user: <strong>$username</strong><br>";
    
    try {
        $user = $auth->loginUser($username, $password);
        echo "<div class='success'>✅ Login successful!</div>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
        
        // Check session immediately after login
        echo "<h3>Session After Login</h3>";
        echo "Session Status: " . session_status() . "<br>";
        echo "Session ID: " . session_id() . "<br>";
        echo "<pre>";
        print_r($_SESSION);
        echo "</pre>";
        
        // Check if isLoggedIn works
        echo "<h3>Auth Check</h3>";
        if ($auth->isLoggedIn()) {
            echo "<div class='success'>✅ isLoggedIn() returns true</div>";
            $currentUser = $auth->getCurrentUser();
            echo "<pre>";
            print_r($currentUser);
            echo "</pre>";
        } else {
            echo "<div class='error'>❌ isLoggedIn() returns false even though loginUser succeeded</div>";
        }
        
        echo "<p><a href='test-session.php'>Check session on another page</a></p>";
        echo "<p><a href='dashboard.php'>Go to dashboard</a></p>";
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ Login failed: " . $e->getMessage() . "</div>";
    }
} else {
    // Show login form
    echo '<form method="POST">';
    echo '  Username: <input type="text" name="username" value="admin"><br><br>';
    echo '  Password: <input type="password" name="password"><br><br>';
    echo '  <button type="submit">Test Login</button>';
    echo '</form>';
}
