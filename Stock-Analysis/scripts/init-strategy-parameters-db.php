<?php

/**
 * Initialize Strategy Parameters Database
 * 
 * Run this script to create the strategy_parameters table and populate it with default values.
 * 
 * Usage: php scripts/init-strategy-parameters-db.php
 */

// No bootstrap needed - standalone script

$databasePath = __DIR__ . '/../storage/database/stock_analysis.db';
$migrationFile = __DIR__ . '/../database/migrations/create_strategy_parameters_table.sql';

echo "Initializing Strategy Parameters Database...\n";
echo "Database: $databasePath\n";
echo "Migration: $migrationFile\n\n";

try {
    // Create storage/database directory if it doesn't exist
    $dbDir = dirname($databasePath);
    if (!file_exists($dbDir)) {
        mkdir($dbDir, 0755, true);
        echo "Created directory: $dbDir\n";
    }

    // Connect to database
    $pdo = new PDO("sqlite:$databasePath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to database\n";

    // Read and execute migration SQL
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }

    $sql = file_get_contents($migrationFile);
    $pdo->exec($sql);
    echo "Migration executed successfully\n\n";

    // Verify table creation
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='strategy_parameters'");
    $table = $stmt->fetch();

    if ($table) {
        echo "✅ Table 'strategy_parameters' created successfully\n";
        
        // Count inserted records
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM strategy_parameters");
        $result = $stmt->fetch();
        echo "✅ Inserted {$result['count']} default parameter records\n";
        
        // Show strategies
        $stmt = $pdo->query("SELECT DISTINCT strategy_name FROM strategy_parameters ORDER BY strategy_name");
        $strategies = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "\n📊 Configured Strategies:\n";
        foreach ($strategies as $strategy) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM strategy_parameters WHERE strategy_name = ?");
            $stmt->execute([$strategy]);
            $count = $stmt->fetch();
            echo "   - $strategy ({$count['count']} parameters)\n";
        }
        
        echo "\n✅ Database initialization complete!\n";
        echo "\nYou can now:\n";
        echo "  1. Run tests: php run-tests.php trading\n";
        echo "  2. Access UI: Open web_ui/strategy-config.html in browser\n";
        echo "  3. Configure parameters via the web interface\n";
        
    } else {
        throw new Exception("Table creation failed");
    }

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
