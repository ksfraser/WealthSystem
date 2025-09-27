<?php
/**
 * PHP Yahoo Finance Data Fetcher
 * Alternative to Python script using pure PHP and Guzzle HTTP client
 */

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class YahooFinancePhp {
    private $client;
    private $baseUrl = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    
    public function __construct() {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // Disable SSL verification for now
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ]
        ]);
    }
    
    /**
     * Fetch historical data for a symbol
     */
    public function fetchHistoricalData($symbol, $startDate, $endDate) {
        try {
            $startTimestamp = strtotime($startDate);
            $endTimestamp = strtotime($endDate);
            
            if (!$startTimestamp || !$endTimestamp) {
                throw new Exception("Invalid date format");
            }
            
            $url = $this->baseUrl . $symbol;
            $params = [
                'period1' => $startTimestamp,
                'period2' => $endTimestamp,
                'interval' => '1d',
                'includePrePost' => 'true',
                'events' => 'div,splits'
            ];
            
            $response = $this->client->get($url, ['query' => $params]);
            $data = json_decode($response->getBody(), true);
            
            if (!isset($data['chart']['result'][0])) {
                throw new Exception("No data returned from Yahoo Finance");
            }
            
            return $this->parseYahooData($data['chart']['result'][0], $symbol);
            
        } catch (RequestException $e) {
            throw new Exception("HTTP request failed: " . $e->getMessage());
        } catch (Exception $e) {
            throw new Exception("Data fetch failed: " . $e->getMessage());
        }
    }
    
    /**
     * Parse Yahoo Finance API response
     */
    private function parseYahooData($result, $symbol) {
        $meta = $result['meta'];
        $timestamps = $result['timestamp'];
        $quotes = $result['indicators']['quote'][0];
        
        $data = [];
        
        for ($i = 0; $i < count($timestamps); $i++) {
            // Skip if essential data is missing
            if (!isset($quotes['open'][$i]) || is_null($quotes['open'][$i])) {
                continue;
            }
            
            $date = date('Y-m-d', $timestamps[$i]);
            
            $data[] = [
                'Date' => $date,
                'Open' => round($quotes['open'][$i], 4),
                'High' => round($quotes['high'][$i], 4),
                'Low' => round($quotes['low'][$i], 4),
                'Close' => round($quotes['close'][$i], 4),
                'Adj Close' => round($quotes['close'][$i], 4), // Yahoo API doesn't separate these
                'Volume' => intval($quotes['volume'][$i] ?? 0)
            ];
        }
        
        // Sort by date
        usort($data, function($a, $b) {
            return strcmp($a['Date'], $b['Date']);
        });
        
        return [
            'symbol' => $symbol,
            'start_date' => reset($data)['Date'] ?? null,
            'end_date' => end($data)['Date'] ?? null,
            'data' => $data,
            'count' => count($data)
        ];
    }
    
    /**
     * Save data to CSV file
     */
    public function saveToCSV($data, $filename = null) {
        // Ensure data directory exists
        $dataDir = __DIR__ . '/data/csv';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        if (!$filename) {
            $filename = "{$data['symbol']}_{$data['start_date']}_to_{$data['end_date']}.csv";
        }
        
        // Add full path if filename doesn't contain directory
        if (strpos($filename, DIRECTORY_SEPARATOR) === false) {
            $filename = $dataDir . DIRECTORY_SEPARATOR . $filename;
        }
        
        $file = fopen($filename, 'w');
        
        // Write header
        fputcsv($file, ['Date', 'Open', 'High', 'Low', 'Close', 'Adj Close', 'Volume']);
        
        // Write data
        foreach ($data['data'] as $row) {
            fputcsv($file, [
                $row['Date'],
                $row['Open'],
                $row['High'],
                $row['Low'],
                $row['Close'],
                $row['Adj Close'],
                $row['Volume']
            ]);
        }
        
        fclose($file);
        return $filename;
    }
    
    /**
     * Test connection to Yahoo Finance
     */
    public function testConnection() {
        try {
            $response = $this->client->get('https://finance.yahoo.com/', ['timeout' => 10]);
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }
}

// CLI usage (if called directly)
if (php_sapi_name() === 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $usage = "Usage: php " . basename(__FILE__) . " <symbol> <start_date> <end_date> [--save-csv] [--json]\n";
    $usage .= "Example: php " . basename(__FILE__) . " AAPL 2020-01-01 2020-12-31 --save-csv\n";
    
    if ($argc < 4) {
        echo $usage;
        exit(1);
    }
    
    $symbol = strtoupper($argv[1]);
    $startDate = $argv[2];
    $endDate = $argv[3];
    $saveCsv = in_array('--save-csv', $argv);
    $outputJson = in_array('--json', $argv);
    
    try {
        $yahoo = new YahooFinancePhp();
        
        echo "Fetching historical data for {$symbol} from {$startDate} to {$endDate}...\n";
        
        $result = $yahoo->fetchHistoricalData($symbol, $startDate, $endDate);
        
        echo "Retrieved {$result['count']} records\n";
        
        if ($saveCsv) {
            $csvFile = $yahoo->saveToCSV($result);
            echo "CSV saved to: {$csvFile}\n";
        }
        
        if ($outputJson) {
            echo json_encode($result, JSON_PRETTY_PRINT);
        } else {
            // Simple output
            echo "Data range: {$result['start_date']} to {$result['end_date']}\n";
            echo "Sample data (first 3 records):\n";
            foreach (array_slice($result['data'], 0, 3) as $row) {
                echo "  {$row['Date']}: Open={$row['Open']}, Close={$row['Close']}, Volume={$row['Volume']}\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>