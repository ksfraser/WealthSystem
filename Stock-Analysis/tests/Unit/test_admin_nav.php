<?php
// Test admin navigation class specifically
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin_user';
$_SESSION['is_admin'] = true;

ob_start();
include 'analytics.php';
$output = ob_get_clean();

// Extract just the navigation section
$start = strpos($output, '<div class="nav-header');
$end = strpos($output, '</div>', $start);
$end = strpos($output, '</div>', $end + 1);
$end = strpos($output, '</div>', $end + 1) + 6;

$navSection = substr($output, $start, $end - $start);

echo "Navigation section:\n";
echo $navSection . "\n";

// Check for admin elements
echo "\nAdmin checks:\n";
echo "Has 'nav-header admin': " . (strpos($navSection, 'nav-header admin') !== false ? 'YES' : 'NO') . "\n";
echo "Has admin badge: " . (strpos($navSection, 'admin-badge') !== false ? 'YES' : 'NO') . "\n";
echo "Has ADMIN text: " . (strpos($navSection, '>ADMIN<') !== false ? 'YES' : 'NO') . "\n";
?>
