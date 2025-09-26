<?php
require_once 'web_ui/UserAuthDAO.php';

echo "Testing UserAuth system...\n";

$auth = new UserAuthDAO();
if ($auth->getPdo()) {
    echo "✅ Database connected successfully\n";
    
    try {
        $stmt = $auth->getPdo()->prepare('SELECT COUNT(*) as count FROM users');
        $stmt->execute();
        $result = $stmt->fetch();
        echo "📊 Users in database: " . $result['count'] . "\n";
        
        if ($result['count'] > 0) {
            $stmt = $auth->getPdo()->prepare('SELECT username, email, is_admin FROM users LIMIT 5');
            $stmt->execute();
            $users = $stmt->fetchAll();
            echo "👥 Sample users:\n";
            foreach ($users as $user) {
                echo "  - " . $user['username'] . " (" . $user['email'] . ")" . ($user['is_admin'] ? " [ADMIN]" : "") . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking users: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database connection failed\n";
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        echo "Errors: " . implode(', ', $errors) . "\n";
    }
}
?>