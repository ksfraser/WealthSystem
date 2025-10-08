<?php
// CLI tool for admin password reset (like unix passwd)
require_once __DIR__ . '/web_ui/UserAuthDAO.php';

function prompt($prompt) {
    echo $prompt;
    return rtrim(fgets(STDIN), "\r\n");
}

function promptHidden($prompt) {
    if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
        // Windows: fallback to visible input
        return prompt($prompt);
    } else {
        // Unix: hide input
        echo $prompt;
        system('stty -echo');
        $value = rtrim(fgets(STDIN), "\r\n");
        system('stty echo');
        echo "\n";
        return $value;
    }
}

$auth = new UserAuthDAO();


$input = $argv[1] ?? null;
if (!$input) {
    $input = prompt("Enter username or email to reset: ");
}

// Efficient DB lookup by username or email
$user = $auth->getUserByUsernameOrEmail($input);
if (!$user) {
    echo "User not found: $input\n";
    exit(1);
}
$userId = $user['id'];
$username = $user['username'];

while (true) {
    $pw1 = promptHidden("Enter new password: ");
    $pw2 = promptHidden("Retype new password: ");
    if ($pw1 !== $pw2) {
        echo "Passwords do not match. Try again.\n";
        continue;
    }
    if (strlen($pw1) < 8) {
        echo "Password must be at least 8 characters. Try again.\n";
        continue;
    }
    try {
        $auth->adminResetUserPassword($userId, $pw1);
        echo "Password reset successful for user $username (ID $userId)\n";
        exit(0);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
