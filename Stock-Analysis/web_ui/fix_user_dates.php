<?php
/**
 * Database Update Script - Fix created_at dates and table structure
 */

require_once 'database_loader.php';

echo "=== Database Update: Fix User Created Dates ===\n\n";

try {
    $connection = \Ksfraser\Database\EnhancedDbManager::getConnection();
    
    // Check current created_at values
    echo "Checking current user created_at values...\n";
    $stmt = $connection->prepare("SELECT id, username, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "Current users:\n";
    foreach ($users as $user) {
        $createdDate = $user['created_at'] ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : 'NULL';
        echo "  ID {$user['id']}: {$user['username']} - Created: {$createdDate}\n";
    }
    
    // Update users with invalid dates (1970-01-01 or NULL)
    echo "\nUpdating users with invalid created_at dates...\n";
    
    $updateStmt = $connection->prepare("
        UPDATE users 
        SET created_at = NOW() 
        WHERE created_at IS NULL 
           OR created_at = '0000-00-00 00:00:00' 
           OR created_at < '2020-01-01'
    ");
    
    if ($updateStmt->execute()) {
        $rowsUpdated = $updateStmt->rowCount();
        echo "✅ Updated {$rowsUpdated} user records with current timestamp\n";
    } else {
        echo "❌ Failed to update user dates\n";
    }
    
    // Try to alter table structure to set default value (if supported)
    echo "\nAttempting to set created_at default to CURRENT_TIMESTAMP...\n";
    try {
        $alterStmt = $connection->prepare("
            ALTER TABLE users 
            MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ");
        
        if ($alterStmt->execute()) {
            echo "✅ Table structure updated: created_at now defaults to CURRENT_TIMESTAMP\n";
        } else {
            echo "⚠️ Could not alter table structure (may not be supported by current database)\n";
        }
    } catch (Exception $alterError) {
        echo "⚠️ Table alteration not supported: " . $alterError->getMessage() . "\n";
        echo "   (This is normal for SQLite - the PHP code fix will handle new users)\n";
    }
    
    // Show updated results
    echo "\nChecking updated user created_at values...\n";
    $stmt = $connection->prepare("SELECT id, username, created_at FROM users");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "Updated users:\n";
    foreach ($users as $user) {
        $createdDate = $user['created_at'] ? date('Y-m-d H:i:s', strtotime($user['created_at'])) : 'NULL';
        echo "  ID {$user['id']}: {$user['username']} - Created: {$createdDate}\n";
    }
    
    echo "\n✅ Database update completed successfully!\n";
    echo "New users will now automatically get correct created_at timestamps.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
