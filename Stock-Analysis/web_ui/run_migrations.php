<?php
/**
 * Simple script to run database schema migrations
 */

require_once __DIR__ . '/DbConfigClasses.php';
require_once __DIR__ . '/SchemaMigrator.php';

try {
    // Create PDO connection using LegacyDatabaseConfig
    $pdo = LegacyDatabaseConfig::createConnection();

    // Create schema migrator and run migrations
    $schemaDir = __DIR__ . '/schema';
    $migrator = new SchemaMigrator($pdo, $schemaDir);
    $migrator->migrate();

    echo "Migrations completed successfully!\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>