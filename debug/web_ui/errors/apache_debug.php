<?php
/**
 * Apache Compatibility Check
 * Diagnose potential issues causing 500 errors on Apache vs PHP built-in server
 */

echo "🔍 Apache Compatibility Diagnostic\n";
echo "==================================\n\n";

// Check PHP configuration differences
echo "📋 PHP Configuration:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "SAPI: " . php_sapi_name() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? getcwd()) . "\n\n";

// Check error reporting
echo "⚠️  Error Reporting Settings:\n";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . (ini_get('error_log') ?: 'default') . "\n\n";

// Check file permissions (Windows vs Linux)
echo "📁 File Permission Analysis:\n";
$criticalFiles = [
    'index.php',
    'login.php', 
    'trades.php',
    'UserAuthDAO.php',
    'CommonDAO.php',
    'EnhancedCommonDAO.php',
    'DbConfigClasses.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $perms = fileperms($file);
        $readable = is_readable($file) ? '✅' : '❌';
        $writable = is_writable($file) ? '✅' : '❌';
        echo "  $file: R:$readable W:$writable (perms: " . substr(sprintf('%o', $perms), -4) . ")\n";
    } else {
        echo "  $file: ❌ NOT FOUND\n";
    }
}

echo "\n🔧 Common Apache Issues Check:\n";

// Check for short open tags
echo "📝 Checking for short open tags (<?): ";
$shortTagFiles = [];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (preg_match('/^<\?\s/', $content) && !preg_match('/^<\?php/', $content)) {
            $shortTagFiles[] = $file;
        }
    }
}
echo empty($shortTagFiles) ? "✅ None found\n" : "❌ Found in: " . implode(', ', $shortTagFiles) . "\n";

// Check for BOM (Byte Order Mark)
echo "📝 Checking for BOM headers: ";
$bomFiles = [];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $bomFiles[] = $file;
        }
    }
}
echo empty($bomFiles) ? "✅ None found\n" : "❌ Found in: " . implode(', ', $bomFiles) . "\n";

// Check for output before headers
echo "📝 Checking for potential 'headers already sent' issues: ";
$headerIssues = [];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Look for whitespace or content before <?php
        if (preg_match('/^(\s+|.+)<\?php/', $content)) {
            $headerIssues[] = $file;
        }
    }
}
echo empty($headerIssues) ? "✅ None found\n" : "❌ Potential issues in: " . implode(', ', $headerIssues) . "\n";

// Check include/require paths
echo "📝 Checking include paths: ";
$includeIssues = [];
foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        // Look for relative paths that might break on Apache
        if (preg_match_all('/(?:include|require)(?:_once)?\s*[\'"]([^\'\"]+)[\'"]/', $content, $matches)) {
            foreach ($matches[1] as $path) {
                if (strpos($path, './') === 0 || strpos($path, '../') === 0) {
                    $includeIssues[] = "$file: $path";
                }
            }
        }
    }
}
echo empty($includeIssues) ? "✅ Paths look good\n" : "⚠️  Relative paths found:\n  " . implode("\n  ", $includeIssues) . "\n";

echo "\n🌐 Environment Variables:\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "PATH_INFO: " . ($_SERVER['PATH_INFO'] ?? 'not set') . "\n";

echo "\n🔍 Recommendations for Apache:\n";
echo "1. Check Apache error logs for specific PHP errors\n";
echo "2. Verify .htaccess files don't conflict with server config\n";
echo "3. Ensure PHP modules loaded: php -m | grep -E '(pdo|mysqli|curl)'\n";
echo "4. Check file ownership/permissions on Linux (www-data user)\n";
echo "5. Verify Apache PHP handler configuration\n";
echo "6. Test with: tail -f /var/log/apache2/error.log while reproducing issue\n";

echo "\n✅ Test this script on both environments to compare output!\n";
?>