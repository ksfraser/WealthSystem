#!/usr/bin/env php
<?php
/**
 * Migration Runner Script
 * 
 * Orchestrates the complete migration from legacy tables to per-symbol tables
 */

require_once __DIR__ . '/../src/MigrateSymbolsCliHandler.php';
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/../DynamicStockDataAccess.php';
require_once __DIR__ . '/../src/MigrateSymbolAction.php';

function showUsage()
{
    echo "Migration Runner Tool\n\n";
    echo "Usage:\n";
    echo "  php MigrateToPerSymbolTables.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --symbol=SYMBOL   Migrate specific symbol only\n";
    echo "  --dry-run         Show what would be done without making changes\n";
    echo "  --batch-size=N    Number of records to process at once (default: 1000)\n";
    echo "  --force           Skip confirmation prompts\n";
    echo "  --help            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php MigrateToPerSymbolTables.php\n";
    echo "  php MigrateToPerSymbolTables.php --symbol=IBM --dry-run\n";
    echo "  php MigrateToPerSymbolTables.php --batch-size=500 --force\n";
}

function checkPrerequisites()
{
    echo "=== Checking Prerequisites ===\n";
    
    try {
        DatabaseConfig::load();
        echo "✓ Database configuration loaded\n";
        
        // Check legacy database connection
        $legacyPdo = DatabaseConfig::createLegacyConnection();
        echo "✓ Legacy database connection established\n";
        
        // Check micro-cap database connection
        $microCapPdo = DatabaseConfig::createMicroCapConnection();
        echo "✓ Micro-cap database connection established\n";
        
        // Check if required legacy tables exist
        $requiredTables = ['historical_prices', 'technical_indicators', 'candlestick_patterns'];
        foreach ($requiredTables as $table) {
            $stmt = $legacyPdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() === 0) {
                throw new Exception("Required legacy table '{$table}' does not exist");
            }
            echo "✓ Legacy table '{$table}' exists\n";
        }
        
        // Check if symbol_registry table exists
        $stmt = $microCapPdo->query("SHOW TABLES LIKE 'symbol_registry'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("Symbol registry table does not exist");
        }
        echo "✓ Symbol registry table exists\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "✗ Prerequisite check failed: " . $e->getMessage() . "\n";
        echo "\nTo fix this issue:\n";
        echo "1. Run: php scripts/setup-database.php\n";
        echo "2. Import CSV data: python3 scripts/import-csv-to-database.py --generate-sample\n";
        echo "3. Try migration again\n";
        return false;
    }
}

function showMigrationSummary()
{
    echo "\n=== Migration Summary ===\n";
    
    try {
        $legacyPdo = DatabaseConfig::createLegacyConnection();
        $microCapPdo = DatabaseConfig::createMicroCapConnection();
        
        // Count records in legacy tables
        echo "Legacy Tables:\n";
        $legacyTables = [
            'historical_prices' => 'Historical price data',
            'technical_indicators' => 'Technical indicators',
            'candlestick_patterns' => 'Candlestick patterns'
        ];
        
        $totalLegacyRecords = 0;
        foreach ($legacyTables as $table => $description) {
            $stmt = $legacyPdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            $totalLegacyRecords += $count;
            echo "  {$table}: {$count} records ({$description})\n";
        }
        
        // Count symbols that will be affected
        $stmt = $legacyPdo->query("
            SELECT COUNT(DISTINCT symbol) as symbol_count 
            FROM (
                SELECT symbol FROM historical_prices
                UNION
                SELECT symbol FROM technical_indicators
                UNION 
                SELECT symbol FROM candlestick_patterns
            ) AS all_symbols
        ");
        $symbolCount = $stmt->fetch(PDO::FETCH_ASSOC)['symbol_count'];
        
        echo "\nSymbols to migrate: {$symbolCount}\n";
        echo "Total legacy records: {$totalLegacyRecords}\n";
        
        // Check existing per-symbol tables
        $stmt = $microCapPdo->query("SELECT COUNT(*) as count FROM symbol_registry WHERE active = 1");
        $activeSymbols = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "Active symbols in registry: {$activeSymbols}\n";
        
    } catch (Exception $e) {
        echo "Error generating summary: " . $e->getMessage() . "\n";
    }
}

// Main execution
if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line\n");
}

// Parse command line arguments
$options = [];
for ($i = 1; $i < $argc; $i++) {
    $arg = $argv[$i];
    
    if ($arg === '--help') {
        showUsage();
        exit(0);
    } elseif ($arg === '--dry-run') {
        $options[] = '--dry-run';
    } elseif ($arg === '--force') {
        $options[] = '--force';
    } elseif (strpos($arg, '--symbol=') === 0) {
        $options[] = $arg;
    } elseif (strpos($arg, '--batch-size=') === 0) {
        $options[] = $arg;
    }
}

echo "=== Migration to Per-Symbol Tables ===\n";
echo "This script will migrate data from legacy tables to per-symbol tables\n\n";

// Check prerequisites
if (!checkPrerequisites()) {
    exit(1);
}

// Show migration summary
showMigrationSummary();

// Confirm before proceeding (unless --force is specified)
if (!in_array('--force', $options)) {
    echo "\nProceed with migration? [y/N]: ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'y' && strtolower($line) !== 'yes') {
        echo "Migration cancelled.\n";
        exit(0);
    }
}

echo "\n=== Starting Migration ===\n";

try {
    // Create and run the migration handler
    $handler = new MigrateSymbolsCliHandler();
    
    // Prepare arguments for the handler
    $migrationArgs = ['MigrateToPerSymbolTables.php'];
    $migrationArgs = array_merge($migrationArgs, $options);
    
    // Run the migration
    $handler->run($migrationArgs);
    
    echo "\n=== Migration Complete ===\n";
    echo "✓ All data has been migrated to per-symbol tables\n";
    echo "✓ You can now use the modern per-symbol table system\n";
    
} catch (Exception $e) {
    echo "\n✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
