<?php
/**
 * Complete Data Migration Script
 * Migrates historical data to new individual stock table structure
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/StockDatabaseManager.php';

class DataMigrator {
    private $pdo;
    private $stockDAO;
    
    public function __construct() {
        $auth = new UserAuthDAO();
        $this->pdo = $auth->getPDO();
        $this->stockDAO = new StockDAO($this->pdo);
    }
    
    public function migrate() {
        echo "=== COMPREHENSIVE DATA MIGRATION ===\n\n";
        
        // Step 1: Get all symbols that need migration
        $symbolsToMigrate = $this->getSymbolsToMigrate();
        echo "Symbols to migrate: " . implode(', ', $symbolsToMigrate) . "\n\n";
        
        // Step 2: Create stock records and individual tables
        $this->createStockRecords($symbolsToMigrate);
        
        // Step 3: Migrate CSV trade data
        $this->migrateCsvTradeData();
        
        // Step 4: Migrate financial statement data
        $this->migrateFinancialData();
        
        // Step 5: Update stocks table with latest information
        $this->updateStockInformation();
        
        echo "\n=== MIGRATION COMPLETE ===\n";
    }
    
    private function getSymbolsToMigrate() {
        // Get symbols from CSV
        $csvSymbols = $this->getCsvSymbols();
        
        // Get symbols from financial data
        $finSymbols = $this->pdo->query("
            SELECT DISTINCT symbol 
            FROM fin_statement 
            WHERE symbol IS NOT NULL AND symbol != '' AND LENGTH(symbol) <= 10
        ")->fetchAll(PDO::FETCH_COLUMN);
        
        // Get existing symbols
        $existingSymbols = $this->pdo->query("SELECT symbol FROM stocks")->fetchAll(PDO::FETCH_COLUMN);
        
        // Return symbols that need to be added
        $allSymbols = array_unique(array_merge($csvSymbols, $finSymbols));
        return array_diff($allSymbols, $existingSymbols);
    }
    
    private function getCsvSymbols() {
        $csvFile = __DIR__ . '/../chatgpt_trade_log.csv';
        if (!file_exists($csvFile)) {
            $csvFile = __DIR__ . '/../Scripts and CSV Files/chatgpt_trade_log.csv';
        }
        
        $symbols = [];
        if (file_exists($csvFile)) {
            $handle = fopen($csvFile, 'r');
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) === count($header)) {
                    $data = array_combine($header, $row);
                    if (!empty($data['Ticker'])) {
                        $symbols[] = $data['Ticker'];
                    }
                }
            }
            fclose($handle);
        }
        
        return array_unique($symbols);
    }
    
    private function createStockRecords($symbols) {
        echo "Creating stock records and tables...\n";
        
        foreach ($symbols as $symbol) {
            try {
                echo "  Processing {$symbol}...\n";
                
                // Add to stocks table
                $stmt = $this->pdo->prepare("
                    INSERT IGNORE INTO stocks (symbol, name, is_active) 
                    VALUES (?, ?, TRUE)
                ");
                $stmt->execute([$symbol, $symbol]); // Use symbol as name initially
                
                // Initialize individual tables
                $success = $this->stockDAO->initializeStock($symbol);
                
                if ($success) {
                    echo "    ✓ Created tables for {$symbol}\n";
                } else {
                    echo "    ❌ Failed to create tables for {$symbol}\n";
                }
                
            } catch (Exception $e) {
                echo "    ❌ Error with {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        echo "\n";
    }
    
    private function migrateCsvTradeData() {
        echo "Migrating CSV trade data...\n";
        
        $csvFile = __DIR__ . '/../chatgpt_trade_log.csv';
        if (!file_exists($csvFile)) {
            $csvFile = __DIR__ . '/../Scripts and CSV Files/chatgpt_trade_log.csv';
        }
        
        if (!file_exists($csvFile)) {
            echo "  CSV file not found, skipping...\n\n";
            return;
        }
        
        $handle = fopen($csvFile, 'r');
        $header = fgetcsv($handle);
        
        $migrated = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($header)) {
                $data = array_combine($header, $row);
                
                if (!empty($data['Ticker']) && !empty($data['Date']) && !empty($data['Buy Price'])) {
                    try {
                        $symbol = $data['Ticker'];
                        $date = date('Y-m-d', strtotime($data['Date']));
                        $price = floatval($data['Buy Price']);
                        
                        if ($price > 0) {
                            // Insert as price data (using buy price as close price)
                            $priceData = [
                                'date' => $date,
                                'open' => $price,
                                'high' => $price,
                                'low' => $price,
                                'close' => $price,
                                'adj_close' => $price, // Same as close for now
                                'volume' => !empty($data['Shares Bought']) ? intval(floatval($data['Shares Bought'])) : null,
                                'data_source' => 'csv'
                            ];
                            
                            $result = $this->stockDAO->upsertPriceData($symbol, $priceData);
                            if ($result) {
                                $migrated++;
                            } else {
                                echo "Failed to upsert price data for {$symbol}: " . implode(', ', $this->pdo->errorInfo()) . "\n";
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo "    Error migrating trade record: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
        fclose($handle);
        
        echo "  ✓ Migrated {$migrated} trade records as price data\n\n";
    }
    
    private function migrateFinancialData() {
        echo "Migrating financial statement data...\n";
        
        // Get all financial records
        $financials = $this->pdo->query("
            SELECT * FROM fin_statement 
            WHERE symbol IS NOT NULL AND symbol != '' AND LENGTH(symbol) <= 10
            ORDER BY symbol, lasteval
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $migrated = 0;
        $currentSymbol = '';
        
        foreach ($financials as $record) {
            try {
                $symbol = $record['symbol'];
                
                if ($symbol !== $currentSymbol) {
                    echo "  Migrating fundamentals for {$symbol}...\n";
                    $currentSymbol = $symbol;
                }
                
                // Prepare fundamental data (matching updateFundamentals method expected fields)
                $fundamentalData = [
                    'report_date' => date('Y-m-d', strtotime($record['lasteval'])),
                    'earnings_per_share' => $record['earningpershare'] ?: null,
                    'dividend_per_share' => $record['dividendpershare'] ?: null,
                    'pe_ratio' => null, // Calculate if we have price data
                    'data_source' => 'migration'
                ];
                
                // Add calculated ratios if we have the data
                if (!empty($record['totalequity']) && !empty($record['outstandingshares'])) {
                    $fundamentalData['book_value_per_share'] = floatval($record['totalequity']) / floatval($record['outstandingshares']);
                }
                
                if (!empty($record['totaldebt']) && !empty($record['totalequity'])) {
                    $fundamentalData['debt_to_equity'] = floatval($record['totaldebt']) / floatval($record['totalequity']);
                }
                
                // Remove null values
                $fundamentalData = array_filter($fundamentalData, function($value) {
                    return $value !== null && $value !== 0;
                });
                
                if (!empty($fundamentalData)) {
                    $success = $this->stockDAO->updateFundamentals($symbol, $fundamentalData);
                    if ($success) {
                        $migrated++;
                    } else {
                        echo "    Failed to update fundamentals for {$symbol}\n";
                    }
                }
                
            } catch (Exception $e) {
                echo "    Error migrating financial record for {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "  ✓ Migrated {$migrated} fundamental records\n\n";
    }
    
    private function updateStockInformation() {
        echo "Updating stock information...\n";
        
        // Update stock names with better information where available
        $updates = [
            'T' => 'AT&T Inc.',
            'NOK' => 'Nokia Corporation',
            'K' => 'Kellogg Company',
            'HD' => 'The Home Depot Inc.',
            'UNH' => 'UnitedHealth Group Inc.',
            'TD' => 'Toronto-Dominion Bank',
            'RY' => 'Royal Bank of Canada',
            'ABX' => 'Barrick Gold Corporation',
            'TEVA' => 'Teva Pharmaceutical Industries Ltd.'
        ];
        
        foreach ($updates as $symbol => $name) {
            try {
                $stmt = $this->pdo->prepare("UPDATE stocks SET name = ? WHERE symbol = ?");
                $stmt->execute([$name, $symbol]);
                echo "  ✓ Updated {$symbol} -> {$name}\n";
            } catch (Exception $e) {
                echo "  ❌ Error updating {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n";
    }
}

// Run migration if this file is executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $migrator = new DataMigrator();
        $migrator->migrate();
        echo "✓ Migration completed successfully!\n";
    } catch (Exception $e) {
        echo "❌ Migration failed: " . $e->getMessage() . "\n";
    }
}
?>