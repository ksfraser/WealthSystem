<?php
require_once __DIR__ . '/web_ui/DbConfigClasses.php';

try {
    $dbConfig = new LegacyDatabaseConfig();
    $conn = $dbConfig->createConnection();

    $sql = file_get_contents(__DIR__ . '/web_ui/schema/016_create_bank_account_access.sql');
    $conn->exec($sql);

    echo "✅ bank_account_access table created successfully!\n";
} catch (Exception $e) {
    echo "❌ Error creating table: " . $e->getMessage() . "\n";
}
?>