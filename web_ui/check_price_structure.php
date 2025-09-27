<?php
/**
 * Check current price table structure and add adjusted_close if missing
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/StockDatabaseManager.php';

try {
    $auth = new UserAuthDAO();
    $pdo = $auth->getPDO();
    
    echo "=== PRICE TABLE STRUCTURE ANALYSIS ===\n\n";
    
    // Check AAPL_prices structure
    echo "Current AAPL_prices structure:\n";
    $columns = $pdo->query("DESCRIBE AAPL_prices")->fetchAll(PDO::FETCH_ASSOC);
    
    $hasAdjustedClose = false;
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
        if ($col['Field'] === 'adjusted_close' || $col['Field'] === 'adj_close') {
            $hasAdjustedClose = true;
        }
    }
    
    if ($hasAdjustedClose) {
        echo "✓ Adjusted close column already exists!\n\n";
    } else {
        echo "❌ Adjusted close column missing - need to add it\n\n";
        
        echo "Adding adjusted_close column to AAPL_prices...\n";
        $pdo->exec("ALTER TABLE AAPL_prices ADD COLUMN adjusted_close DECIMAL(12,6) NULL AFTER close_price");
        echo "✓ Added adjusted_close column\n\n";
    }
    
    // Check historical_prices structure
    echo "Historical_prices table structure:\n";
    $histColumns = $pdo->query("DESCRIBE historical_prices")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($histColumns as $col) {
        echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']}\n";
    }
    
    // Check if we have any data in other potential source tables
    echo "\n=== CHECKING FOR HISTORICAL DATA ===\n";
    
    $potentialSources = [
        'historical_prices',
        'portfolio_data', 
        'holdings',
        'trades_enhanced',
        'fin_statement',
        'stock_symbol_registry'
    ];
    
    foreach ($potentialSources as $table) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "{$table}: {$count} records\n";
            
            if ($count > 0) {
                // Show sample to understand structure
                $sample = $pdo->query("SELECT * FROM {$table} LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
                echo "  Sample data:\n";
                foreach ($sample as $i => $row) {
                    if ($i < 1) { // Show only first record to save space
                        echo "    " . json_encode(array_slice($row, 0, 10, true)) . "\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "{$table}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== LOOKING FOR STOCK SYMBOLS ===\n";
    
    // Check what symbols we have data for
    $tables_with_symbols = ['trades_enhanced', 'holdings', 'portfolio_data', 'stock_symbol_registry'];
    
    foreach ($tables_with_symbols as $table) {
        try {
            $symbols = $pdo->query("SELECT DISTINCT symbol FROM {$table} WHERE symbol IS NOT NULL LIMIT 10")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($symbols)) {
                echo "{$table} symbols: " . implode(', ', $symbols) . "\n";
            }
        } catch (Exception $e) {
            // Table might not have symbol column
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>