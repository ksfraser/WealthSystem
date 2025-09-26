<?php
require_once 'web_ui/UserAuthDAO.php';

echo "Checking users table structure...\n";

$auth = new UserAuthDAO();
if ($auth->getPdo()) {
    try {
        $stmt = $auth->getPdo()->prepare('DESCRIBE users');
        $stmt->execute();
        $columns = $stmt->fetchAll();
        
        echo "Current users table structure:\n";
        foreach ($columns as $column) {
            echo "  - " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
        
        // Check if created_at column exists
        $hasCreatedAt = false;
        foreach ($columns as $column) {
            if ($column['Field'] === 'created_at') {
                $hasCreatedAt = true;
                break;
            }
        }
        
        if (!$hasCreatedAt) {
            echo "\n❌ 'created_at' column is missing!\n";
            echo "Adding 'created_at' column...\n";
            
            $stmt = $auth->getPdo()->prepare('ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
            $stmt->execute();
            
            echo "✅ 'created_at' column added successfully!\n";
        } else {
            echo "\n✅ 'created_at' column exists!\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Database connection failed\n";
    $errors = $auth->getErrors();
    if (!empty($errors)) {
        echo "Errors: " . implode(', ', $errors) . "\n";
    }
}
?>