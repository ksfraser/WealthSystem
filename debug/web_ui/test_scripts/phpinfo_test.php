<?php
// phpinfo_test.php - Simple environment test
echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head><title>PHP Environment Test</title></head>\n";
echo "<body>\n";
echo "<h1>PHP Environment Test</h1>\n";

echo "<h2>Current Working Directory</h2>\n";
echo "<p>" . getcwd() . "</p>\n";

echo "<h2>Script Directory</h2>\n";
echo "<p>" . __DIR__ . "</p>\n";

echo "<h2>Include Path</h2>\n";
echo "<p>" . ini_get('include_path') . "</p>\n";

echo "<h2>Files in Current Directory</h2>\n";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        if (is_file($file)) {
            echo "<p>📄 $file</p>\n";
        } else {
            echo "<p>📁 $file/</p>\n";
        }
    }
}

echo "<h2>Key Files Check</h2>\n";
$key_files = ['SessionManager.php', 'UserAuthDAO.php', 'AuthExceptions.php', 'auth_check.php'];
foreach ($key_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p>✅ $file found at $path</p>\n";
    } else {
        echo "<p>❌ $file missing at $path</p>\n";
    }
}

echo "</body>\n";
echo "</html>\n";
?>
