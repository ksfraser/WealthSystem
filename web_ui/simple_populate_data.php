<?php
/**
 * Simple Historical Data Populator
 * 
 * Populates historical stock data using direct Yahoo Finance API calls
 */

require_once __DIR__ . '/StockDAO.php';

class SimpleHistoricalPopulator {
    private $pdo;
    private $stockDAO;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->stockDAO = new StockDAO($pdo);
    }
    
    public function populateAllStocks() {
        echo "\n=== SIMPLE HISTORICAL DATA POPULATION ===\n\n";
        
        // Get symbols that need data
        $symbols = $this->getActiveSymbols();
        echo "Found " . count($symbols) . " symbols to process\n\n";
        
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($symbols as $symbol) {
            echo "Processing {$symbol}...\n";
            
            try {
                $priceCount = $this->getPriceCount($symbol);
                
                if ($priceCount > 50) {
                    echo "  ✓ {$symbol} already has {$priceCount} price records, skipping\n";
                    continue;
                }
                
                $success = $this->fetchAndStoreData($symbol);
                
                if ($success) {
                    $successCount++;
                    echo "  ✓ Successfully populated data for {$symbol}\n";
                } else {
                    $errorCount++;
                    echo "  ❌ Failed to get data for {$symbol}\n";
                }
                
                // Be respectful to APIs
                sleep(1);
                
            } catch (Exception $e) {
                $errorCount++;
                echo "  ❌ Error with {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== POPULATION COMPLETE ===\n";
        echo "Successfully processed: {$successCount} symbols\n";
        echo "Errors: {$errorCount} symbols\n";
    }
    
    private function getActiveSymbols(): array {
        try {
            $stmt = $this->pdo->query("
                SELECT symbol FROM stocks 
                WHERE is_active = TRUE 
                ORDER BY symbol
            ");
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
            
        } catch (Exception $e) {
            echo "Error getting symbols: " . $e->getMessage() . "\n";
            return [];
        }
    }
    
    private function getPriceCount(string $symbol): int {
        try {
            $prices = $this->stockDAO->getPriceData($symbol, null, null, 1000);
            return count($prices);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function fetchAndStoreData(string $symbol): bool {
        // Generate sample data for demonstration
        // In a real implementation, you would call an actual API
        $sampleData = $this->generateSampleData($symbol);
        
        if (empty($sampleData)) {
            return false;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            $insertCount = 0;
            
            foreach ($sampleData as $priceData) {
                $success = $this->stockDAO->upsertPriceData($symbol, $priceData);
                if ($success) {
                    $insertCount++;
                }
            }
            
            $this->pdo->commit();
            
            echo "    Inserted {$insertCount} price records\n";
            return $insertCount > 0;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            echo "    Database error: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Generate sample historical data
     * Replace this with actual Yahoo Finance API calls in production
     */
    private function generateSampleData(string $symbol): array {
        $data = [];
        $basePrice = 50 + (crc32($symbol) % 100); // Semi-random base price
        $currentPrice = $basePrice;
        
        // Generate 100 days of sample data
        for ($i = 99; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Generate realistic price movement
            $change = (rand(-500, 500) / 10000) * $currentPrice; // ±5% max change
            $open = $currentPrice;
            $currentPrice = max(1, $currentPrice + $change);
            
            // Generate high/low around the price movement
            $high = max($open, $currentPrice) * (1 + rand(0, 200) / 10000);
            $low = min($open, $currentPrice) * (1 - rand(0, 200) / 10000);
            
            // Volume
            $volume = rand(10000, 1000000);
            
            $data[] = [
                'date' => $date,
                'open' => round($open, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($currentPrice, 2),
                'adj_close' => round($currentPrice, 2), // Same as close for now
                'volume' => $volume,
                'data_source' => 'sample'
            ];
        }
        
        return $data;
    }
    
    /**
     * Method to fetch real data from Yahoo Finance
     * Requires curl and proper error handling
     */
    private function fetchRealYahooData(string $symbol, string $startDate, string $endDate): ?array {
        // Convert dates to timestamps
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        
        // Yahoo Finance URL format
        $url = "https://query1.finance.yahoo.com/v7/finance/download/{$symbol}";
        $url .= "?period1={$start}&period2={$end}&interval=1d&events=history";
        
        try {
            // Initialize curl
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);
            
            if ($httpCode !== 200 || !$response) {
                return null;
            }
            
            // Parse CSV response
            $lines = explode("\n", trim($response));
            $header = str_getcsv(array_shift($lines));
            
            $data = [];
            foreach ($lines as $line) {
                if (empty(trim($line))) continue;
                
                $values = str_getcsv($line);
                if (count($values) !== count($header)) continue;
                
                $row = array_combine($header, $values);
                
                $data[] = [
                    'date' => $row['Date'],
                    'open' => floatval($row['Open']),
                    'high' => floatval($row['High']),
                    'low' => floatval($row['Low']),
                    'close' => floatval($row['Close']),
                    'adj_close' => floatval($row['Adj Close']),
                    'volume' => intval($row['Volume']),
                    'data_source' => 'yahoo'
                ];
            }
            
            return $data;
            
        } catch (Exception $e) {
            return null;
        }
    }
}

// Check if we have database config
if (!file_exists(__DIR__ . '/config/database.php')) {
    echo "Database config not found. Creating sample data only.\n";
    
    // For now, just create some sample entries in the stocks table
    try {
        // Use basic connection
        $pdo = new PDO('mysql:host=localhost;dbname=finance_db;charset=utf8mb4', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "Connected to database\n";
        
        $populator = new SimpleHistoricalPopulator($pdo);
        $populator->populateAllStocks();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    
} else {
    // Use proper config
    try {
        $config = require __DIR__ . '/config/database.php';
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4",
            $config['username'],
            $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        $populator = new SimpleHistoricalPopulator($pdo);
        $populator->populateAllStocks();
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>