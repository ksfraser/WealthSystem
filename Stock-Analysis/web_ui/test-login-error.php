<?php
/**
 * Error display for login debugging
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Login Test with Error Display</h1>";
echo "<style>body { font-family: monospace; } .error { color: red; background: #fee; padding: 10px; margin: 10px 0; }</style>";

try {
    require_once __DIR__ . '/UserAuthDAO.php';
    echo "✅ UserAuthDAO loaded<br>";
    
    $auth = new UserAuthDAO();
    echo "✅ UserAuthDAO instantiated<br>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        echo "<h2>Attempting Login</h2>";
        echo "Username: $username<br>";
        
        try {
            $user = $auth->loginUser($username, $password);
            echo "<div style='color: green; background: #efe; padding: 10px; margin: 10px 0;'>";
            echo "✅ Login successful!<br>";
            echo "User ID: " . $user['id'] . "<br>";
            echo "Username: " . $user['username'] . "<br>";
            echo "</div>";
            
            echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
            
        } catch (Exception $e) {
            echo "<div class='error'>";
            echo "❌ Login error: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "Stack trace:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            echo "</div>";
        }
    } else {
        echo "<h2>Login Form</h2>";
        echo '<form method="POST">';
        echo 'Username: <input type="text" name="username" value="Kevin"><br><br>';
        echo 'Password: <input type="password" name="password"><br><br>';
        echo '<button type="submit">Login</button>';
        echo '</form>';
    }
    
} catch (Throwable $e) {
    echo "<div class='error'>";
    echo "❌ Fatal error: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
    echo "Stack trace:<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}
