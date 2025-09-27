<?php
/**
 * Populate Historical Stock Data
 * 
 * Fetches historical price data from Yahoo Finance for stocks in our system
 * and populates individual stock tables with proper adjusted close prices.
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/StockDAO.php';

class HistoricalDataPopulator {
    private $pdo;
    private $stockDAO;
    private $pythonScript;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->stockDAO = new StockDAO($pdo);
        $this->pythonScript = __DIR__ . '/../trading_script.py';
    }
    
    public function populateHistoricalData() {
        echo "\n=== HISTORICAL DATA POPULATION ===\n\n";
        
        // Get all active stocks that need historical data
        $symbols = $this->getSymbolsNeedingData();
        echo "Found " . count($symbols) . " symbols needing historical data\n\n";
        
        if (empty($symbols)) {
            echo "No symbols need historical data population.\n";
            return;
        }
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($symbols as $symbol) {
            echo "Processing {$symbol}...\n";
            
            try {
                $success = $this->fetchAndStoreHistoricalData($symbol);
                
                if ($success) {
                    $successCount++;
                    echo "  ✓ Successfully populated historical data for {$symbol}\n";
                } else {
                    $errorCount++;
                    echo "  ❌ Failed to populate data for {$symbol}\n";
                }
                
                // Small delay to be respectful to Yahoo Finance
                usleep(500000); // 0.5 second delay
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ❌ Error processing {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== POPULATION COMPLETE ===\n";
        echo "Successfully processed: {$successCount} symbols\n";
        echo "Errors: {$errorCount} symbols\n";
    }
    
    private function getSymbolsNeedingData(): array {
        try {
            // Get all active stocks
            $stmt = $this->pdo->query("
                SELECT symbol FROM stocks 
                WHERE is_active = TRUE 
                ORDER BY symbol
            ");
            
            $allSymbols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $needsData = [];
            
            foreach ($allSymbols as $symbol) {
                // Check if symbol has recent price data (within last 30 days)
                if (!$this->hasRecentData($symbol)) {
                    $needsData[] = $symbol;
                }
            }
            
            return $needsData;
            
        } catch (Exception $e) {
            echo "Error getting symbols: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    private function hasRecentData(string $symbol): bool {
        try {
            $recentPrices = $this->stockDAO->getPriceData(
                $symbol, 
                date('Y-m-d', strtotime('-30 days')), 
                date('Y-m-d'), 
                1
            );
            
            return !empty($recentPrices);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function fetchAndStoreHistoricalData(string $symbol): bool {
        try {
            // Use simple Python script to fetch data
            $startDate = date('Y-m-d', strtotime('-2 years')); // Get 2 years of data
            $endDate = date('Y-m-d');
            $pythonScript = __DIR__ . '/../fetch_historical_data.py';
            
            // Build command to run Python script for specific symbol
            $command = sprintf(
                'python "%s" --symbol "%s" --start-date "%s" --end-date "%s" --output-format json',
                $pythonScript,
                $symbol,
                $startDate,
                $endDate
            );
            
            // Execute command and capture output
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode !== 0) {
                echo "    Python script failed for {$symbol}: " . implode("\n", $output) . "\n";
                return false;
            }
            
            // Parse JSON output
            $jsonOutput = implode("\n", $output);
            $data = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "    JSON parse error for {$symbol}: " . json_last_error_msg() . "\n";
                return false;
            }
            
            if (empty($data) || !is_array($data)) {
                echo "    No data returned for {$symbol}\n";
                return false;
            }
            
            // Store the data
            return $this->storeHistoricalData($symbol, $data);
            
        } catch (Exception $e) {
            echo "    Exception fetching data for {$symbol}: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    private function storeHistoricalData(string $symbol, array $data): bool {
        try {
            $this->pdo->beginTransaction();
            
            $insertCount = 0;
            
            foreach ($data as $row) {
                // Ensure we have required fields
                if (!isset($row['Date']) || !isset($row['Close'])) {
                    continue;
                }
                
                $priceData = [
                    'date' => $row['Date'],
                    'open' => $row['Open'] ?? $row['Close'],
                    'high' => $row['High'] ?? $row['Close'],
                    'low' => $row['Low'] ?? $row['Close'],
                    'close' => $row['Close'],
                    'adj_close' => $row['Adj Close'] ?? $row['Close'], // This is the key field!
                    'volume' => $row['Volume'] ?? 0,
                    'split_coefficient' => 1.0,
                    'dividend_amount' => 0.0,
                    'data_source' => 'yahoo'
                ];
                
                $success = $this->stockDAO->upsertPriceData($symbol, $priceData);
                
                if ($success) {
                    $insertCount++;
                }
            }
            
            $this->pdo->commit();
            
            echo "    Inserted {$insertCount} price records for {$symbol}\n";
            return $insertCount > 0;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "    Database error for {$symbol}: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Alternative method: Process legacy SQL data if needed
     */
    public function processLegacySqlData(string $sqlFile): bool {
        if (!file_exists($sqlFile)) {
            echo "SQL file not found: {$sqlFile}\n";
            return false;
        }
        
        echo "\n=== PROCESSING LEGACY SQL DATA ===\n\n";
        
        try {
            // Read and parse SQL file
            $sqlContent = file_get_contents($sqlFile);
            
            // Extract INSERT statements
            preg_match_all('/INSERT IGNORE INTO `stockprices`[^;]+;/i', $sqlContent, $matches);
            
            if (empty($matches[0])) {
                echo "No INSERT statements found in SQL file\n";
                return false;
            }
            
            $processedCount = 0;
            
            foreach ($matches[0] as $insertStatement) {
                // Parse VALUES from INSERT statement
                if (preg_match('/VALUES\s*(.+);$/i', $insertStatement, $valuesMatch)) {
                    $valuesString = $valuesMatch[1];
                    
                    // Parse individual value rows
                    preg_match_all('/\(([^)]+)\)/i', $valuesString, $rowMatches);
                    
                    foreach ($rowMatches[1] as $rowData) {
                        $values = str_getcsv($rowData, ',', "'");
                        
                        if (count($values) >= 11) {
                            $symbol = trim($values[0], "' \"");
                            $date = trim($values[1], "' \"");
                            $open = floatval($values[3]);
                            $low = floatval($values[4]);
                            $high = floatval($values[5]);
                            $close = floatval($values[6]);
                            $volume = intval($values[10]);
                            
                            // Store in new format
                            $priceData = [
                                'date' => $date,
                                'open' => $open,
                                'high' => $high,
                                'low' => $low,
                                'close' => $close,
                                'adj_close' => $close, // Legacy data doesn't have adjusted close
                                'volume' => $volume,
                                'data_source' => 'legacy'
                            ];
                            
                            $this->stockDAO->upsertPriceData($symbol, $priceData);
                            $processedCount++;
                        }
                    }
                }
            }
            
            echo "Processed {$processedCount} legacy price records\n";
            return true;
            
        } catch (Exception $e) {
            echo "Error processing legacy SQL: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Main execution
if (isset($argv[1]) && $argv[1] === 'legacy') {
    // Process legacy SQL file if requested
    $sqlFile = $argv[2] ?? __DIR__ . '/../../Stock-Analysis-Extension/Legacy/SQL/stockprices.sql';
    
    try {
        // Direct PDO connection for standalone script
        $config = require __DIR__ . '/config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $populator = new HistoricalDataPopulator($pdo);
        $populator->processLegacySqlData($sqlFile);
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    // Default: fetch fresh data from Yahoo Finance
    try {
        // Direct PDO connection for standalone script
        $config = require __DIR__ . '/config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $populator = new HistoricalDataPopulator($pdo);
        $populator->populateHistoricalData();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>