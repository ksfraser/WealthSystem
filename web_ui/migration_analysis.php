<?php
/**
 * Data Migration Script
 * 1. Fix adjusted close column naming
 * 2. Migrate CSV trade data 
 * 3. Migrate financial statement data
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/StockDAO.php';

try {
    $auth = new UserAuthDAO();
    $pdo = $auth->getPDO();
    
    echo "=== DATA MIGRATION SCRIPT ===\n\n";
    
    // Step 1: Fix adjusted close column naming
    echo "Step 1: Standardizing adjusted close column...\n";
    
    // Check if we have both columns now
    $columns = $pdo->query("DESCRIBE AAPL_prices")->fetchAll(PDO::FETCH_COLUMN);
    $hasAdjClose = in_array('adj_close_price', $columns);
    $hasAdjusted = in_array('adjusted_close', $columns);
    
    echo "Has adj_close_price: " . ($hasAdjClose ? 'Yes' : 'No') . "\n";
    echo "Has adjusted_close: " . ($hasAdjusted ? 'Yes' : 'No') . "\n";
    
    if ($hasAdjClose && $hasAdjusted) {
        echo "Removing duplicate adjusted_close column...\n";
        $pdo->exec("ALTER TABLE AAPL_prices DROP COLUMN adjusted_close");
        echo "✓ Removed duplicate column\n\n";
    }
    
    // Step 2: Read and analyze CSV data
    echo "Step 2: Analyzing CSV trade data...\n";
    
    $csvFile = __DIR__ . '/../chatgpt_trade_log.csv';
    if (!file_exists($csvFile)) {
        $csvFile = __DIR__ . '/../Scripts and CSV Files/chatgpt_trade_log.csv';
    }
    
    if (file_exists($csvFile)) {
        echo "Reading CSV file: " . basename($csvFile) . "\n";
        
        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle);
        echo "Headers: " . implode(', ', $header) . "\n";
        
        $rows = [];
        $symbols = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $data = array_combine($header, $row);
                $rows[] = $data;
                if (!empty($data['Ticker'])) {
                    $symbols[$data['Ticker']] = true;
                }
            }
        }
        fclose($handle);
        
        echo "Found " . count($rows) . " trade records\n";
        echo "Unique symbols: " . implode(', ', array_keys($symbols)) . "\n\n";
        
        // Show sample data structure
        if (!empty($rows)) {
            echo "Sample record:\n";
            print_r($rows[0]);
            echo "\n";
        }
        
        // Step 3: Get unique symbols from financial data
        echo "Step 3: Analyzing financial statement data...\n";
        
        $finSymbols = $pdo->query("
            SELECT DISTINCT symbol, COUNT(*) as records 
            FROM fin_statement 
            WHERE symbol IS NOT NULL AND symbol != '' 
            GROUP BY symbol 
            ORDER BY records DESC 
            LIMIT 20
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Top symbols in financial data:\n";
        foreach ($finSymbols as $sym) {
            echo "  - {$sym['symbol']}: {$sym['records']} records\n";
        }
        
        // Step 4: Identify symbols to migrate
        $allSymbols = array_unique(array_merge(
            array_keys($symbols),
            array_column($finSymbols, 'symbol')
        ));
        
        echo "\nAll symbols found: " . implode(', ', $allSymbols) . "\n";
        echo "Total unique symbols to process: " . count($allSymbols) . "\n\n";
        
        // Step 5: Check which symbols already exist in new structure
        echo "Step 5: Checking existing stock tables...\n";
        
        $existingStocks = $pdo->query("SELECT symbol FROM stocks")->fetchAll(PDO::FETCH_COLUMN);
        echo "Stocks already in new structure: " . implode(', ', $existingStocks) . "\n";
        
        $needToAdd = array_diff($allSymbols, $existingStocks);
        echo "Symbols that need to be added: " . implode(', ', $needToAdd) . "\n\n";
        
    } else {
        echo "CSV file not found at expected locations\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>