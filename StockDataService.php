<?php
/**
 * Stock Data Service - PHP Alternative to Python Script
 * Integrates YahooFinancePhp with existing ProgressiveHistoricalLoader system
 */

require_once __DIR__ . '/YahooFinancePhp.php';

class StockDataService {
    private $yahooClient;
    private $debug;
    
    public function __construct($debug = false) {
        $this->yahooClient = new YahooFinancePhp();
        $this->debug = $debug;
    }
    
    /**
     * Fetch historical data - compatible with existing Python script interface
     * Returns data in same format as Python script for seamless integration
     */
    public function fetchHistoricalData($symbol, $startDate, $endDate) {
        try {
            if ($this->debug) {
                error_log("StockDataService: Fetching data for {$symbol} from {$startDate} to {$endDate}");
            }
            
            $result = $this->yahooClient->fetchHistoricalData($symbol, $startDate, $endDate);
            
            // Convert to Python script compatible format
            $pythonCompatibleData = [];
            foreach ($result['data'] as $row) {
                $pythonCompatibleData[] = [
                    'Date' => $row['Date'],
                    'Open' => floatval($row['Open']),
                    'High' => floatval($row['High']),
                    'Low' => floatval($row['Low']),
                    'Close' => floatval($row['Close']),
                    'Adj Close' => floatval($row['Adj Close']),
                    'Volume' => intval($row['Volume'])
                ];
            }
            
            $response = [
                'success' => true,
                'symbol' => $result['symbol'],
                'start_date' => $result['start_date'],
                'end_date' => $result['end_date'],
                'total_records' => $result['count'],
                'data' => $pythonCompatibleData,
                'source' => 'PHP_YahooFinance'
            ];
            
            if ($this->debug) {
                error_log("StockDataService: Successfully fetched {$result['count']} records for {$symbol}");
            }
            
            return json_encode($response);
            
        } catch (Exception $e) {
            $errorResponse = [
                'success' => false,
                'error' => $e->getMessage(),
                'symbol' => $symbol,
                'source' => 'PHP_YahooFinance'
            ];
            
            if ($this->debug) {
                error_log("StockDataService: Error fetching {$symbol}: " . $e->getMessage());
            }
            
            return json_encode($errorResponse);
        }
    }
    
    /**
     * Save data to CSV file
     */
    public function saveToCSV($jsonData, $filename = null) {
        try {
            $data = json_decode($jsonData, true);
            
            if (!$data['success']) {
                throw new Exception("Cannot save failed data to CSV");
            }
            
            // Ensure data directory structure exists
            $dataDir = __DIR__ . '/data/csv';
            if (!is_dir($dataDir)) {
                mkdir($dataDir, 0755, true);
            }
            
            $yahooData = [
                'symbol' => $data['symbol'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'data' => $data['data'],
                'count' => $data['total_records']
            ];
            
            return $this->yahooClient->saveToCSV($yahooData, $filename);
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("StockDataService: CSV save error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Test Yahoo Finance connection
     */
    public function testConnection() {
        return $this->yahooClient->testConnection();
    }
    
    /**
     * Get chunked data (for progressive loading compatibility)
     * Splits large date ranges into smaller chunks
     */
    public function getChunkedData($symbol, $startDate, $endDate, $chunkMonths = 6) {
        $chunks = [];
        $currentStart = new DateTime($startDate);
        $finalEnd = new DateTime($endDate);
        
        while ($currentStart < $finalEnd) {
            $currentEnd = clone $currentStart;
            $currentEnd->add(new DateInterval("P{$chunkMonths}M"));
            
            if ($currentEnd > $finalEnd) {
                $currentEnd = $finalEnd;
            }
            
            $chunks[] = [
                'start' => $currentStart->format('Y-m-d'),
                'end' => $currentEnd->format('Y-m-d')
            ];
            
            $currentStart = clone $currentEnd;
            $currentStart->add(new DateInterval('P1D'));
        }
        
        return $chunks;
    }
    
    /**
     * Fetch data for a specific chunk
     */
    public function fetchChunkData($symbol, $chunkStart, $chunkEnd) {
        return $this->fetchHistoricalData($symbol, $chunkStart, $chunkEnd);
    }
}

// CLI testing interface
if (php_sapi_name() === 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $service = new StockDataService(true);
    
    if ($argc >= 4) {
        $symbol = strtoupper($argv[1]);
        $startDate = $argv[2];
        $endDate = $argv[3];
        
        echo "Testing StockDataService with {$symbol} from {$startDate} to {$endDate}\n";
        
        $result = $service->fetchHistoricalData($symbol, $startDate, $endDate);
        $data = json_decode($result, true);
        
        if ($data['success']) {
            echo "Success! Retrieved {$data['total_records']} records\n";
            echo "Date range: {$data['start_date']} to {$data['end_date']}\n";
            
            if (in_array('--save-csv', $argv)) {
                $csvFile = $service->saveToCSV($result);
                echo "CSV saved to: {$csvFile}\n";
            }
            
            if (in_array('--show-data', $argv)) {
                echo "\nFirst 3 records:\n";
                foreach (array_slice($data['data'], 0, 3) as $row) {
                    echo "  {$row['Date']}: {$row['Open']} -> {$row['Close']} (Vol: {$row['Volume']})\n";
                }
            }
        } else {
            echo "Error: {$data['error']}\n";
        }
    } else {
        echo "Usage: php StockDataService.php <symbol> <start_date> <end_date> [--save-csv] [--show-data]\n";
    }
}
?>