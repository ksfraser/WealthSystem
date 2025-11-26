<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "PHP is working<br>";

echo "Testing auth_check.php...<br>";
try {
    require_once 'auth_check.php';
    echo "✅ auth_check.php loaded<br>";
} catch (Throwable $e) {
    echo "❌ auth_check.php error: " . $e->getMessage() . "<br>";
}

echo "Testing NavigationManager.php...<br>";
try {
    require_once 'NavigationManager.php';
    echo "✅ NavigationManager.php loaded<br>";
} catch (Throwable $e) {
    echo "❌ NavigationManager.php error: " . $e->getMessage() . "<br>";
}

echo "Done.";
?>
