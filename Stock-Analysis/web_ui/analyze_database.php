<?php
/**
 * Database Analysis - Check existing tables and structure
 */

require_once __DIR__ . '/UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    $pdo = $auth->getPDO();
    
    echo "=== DATABASE ANALYSIS ===\n\n";
    
    // Get current database name
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "✓ Database: {$dbName}\n\n";
    
    // Get all existing tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== ALL TABLES ===\n";
    $oldTables = [];
    $newTables = [];
    $otherTables = [];
    
    foreach ($tables as $table) {
        echo "- {$table}\n";
        
        // Categorize tables
        if (preg_match('/^[A-Z]+_[a-z_]+$/', $table)) {
            $newTables[] = $table;
        } elseif (in_array($table, ['portfolio_positions', 'stock_prices', 'trade_log', 'historical_data', 'market_data'])) {
            $oldTables[] = $table;
        } else {
            $otherTables[] = $table;
        }
    }
    
    echo "\n=== TABLE CATEGORIES ===\n";
    echo "Old Tables (potential migration sources):\n";
    foreach ($oldTables as $table) {
        echo "  - {$table}\n";
        
        // Check table structure
        try {
            $columns = $pdo->query("DESCRIBE {$table}")->fetchAll(PDO::FETCH_ASSOC);
            echo "    Columns: ";
            $colNames = array_column($columns, 'Field');
            echo implode(', ', $colNames) . "\n";
            
            // Check record count
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "    Records: {$count}\n";
        } catch (Exception $e) {
            echo "    Error reading table: " . $e->getMessage() . "\n";
        }
        echo "\n";
    }
    
    echo "New Individual Stock Tables:\n";
    foreach ($newTables as $table) {
        echo "  - {$table}\n";
    }
    
    echo "\nOther Tables:\n";
    foreach ($otherTables as $table) {
        echo "  - {$table}\n";
    }
    
    // Check current price table structure
    echo "\n=== CURRENT PRICE TABLE STRUCTURE ===\n";
    if (!empty($newTables)) {
        $priceTable = $newTables[0]; // Take first new table as example
        echo "Example table: {$priceTable}\n";
        
        try {
            $columns = $pdo->query("DESCRIBE {$priceTable}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "  - {$col['Field']} ({$col['Type']}) {$col['Null']} {$col['Key']} {$col['Default']}\n";
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
    
    // Look for potential old stock data tables
    echo "\n=== SEARCHING FOR OLD STOCK DATA ===\n";
    $potentialTables = ['stock_data', 'prices', 'historical_prices', 'stock_history', 'market_prices'];
    
    foreach ($potentialTables as $tableName) {
        if (in_array($tableName, $tables)) {
            echo "✓ Found potential source table: {$tableName}\n";
            
            try {
                $columns = $pdo->query("DESCRIBE {$tableName}")->fetchAll(PDO::FETCH_ASSOC);
                echo "  Columns: ";
                foreach ($columns as $col) {
                    echo $col['Field'] . " ";
                }
                echo "\n";
                
                $count = $pdo->query("SELECT COUNT(*) FROM {$tableName}")->fetchColumn();
                echo "  Records: {$count}\n";
                
                // Show sample data
                $sample = $pdo->query("SELECT * FROM {$tableName} LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($sample)) {
                    echo "  Sample data:\n";
                    foreach ($sample as $row) {
                        echo "    " . json_encode($row) . "\n";
                    }
                }
            } catch (Exception $e) {
                echo "  Error: " . $e->getMessage() . "\n";
            }
            echo "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>