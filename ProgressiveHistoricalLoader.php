<?php
/**
 * Progressive Historical Data Loader
 * Fetches historical data in chunks to overcome Yahoo Finance limitations
 */

require_once __DIR__ . '/web_ui/StockDAO.php';
require_once __DIR__ . '/web_ui/StockDatabaseManager.php';
require_once __DIR__ . '/StockDataService.php';

class ProgressiveHistoricalLoader {
    private $stockDAO;
    private $logger;
    private $stockDataService;
    private $maxYears = 5; // Yahoo Finance limit
    private $delayBetweenRequests = 2; // seconds to avoid rate limiting
    
    public function __construct($database, $logger = null) {
        $this->stockDAO = new StockDAO($database);
        $this->logger = $logger ?: new SimpleLogger();
        $this->stockDataService = new StockDataService(true); // Enable debug mode
    }
    
    /**
     * Load all available historical data for a symbol
     */
    public function loadAllHistoricalData($symbol, $startFromDate = null) {
        $this->logger->info("Starting progressive historical data load for {$symbol}");
        
        try {
            // Determine starting point
            $oldestDate = $this->getOldestDataDate($symbol);
            $latestDate = $this->getLatestDataDate($symbol);
            
            if ($startFromDate) {
                $targetDate = new DateTime($startFromDate);
            } elseif ($oldestDate) {
                // Start from 5 years before oldest existing data
                $targetDate = clone $oldestDate;
                $targetDate->modify('-5 years');
            } else {
                // No existing data, start from IPO date or reasonable default
                $ipoDate = $this->estimateIPODate($symbol);
                $targetDate = $ipoDate ?: new DateTime('1990-01-01'); // Default fallback
            }
            
            $currentDate = new DateTime();
            $totalChunks = 0;
            $totalRecords = 0;
            $chunks = [];
            
            // Calculate all chunks needed
            $chunkEndDate = clone $targetDate;
            while ($chunkEndDate < $currentDate) {
                $chunkStartDate = clone $chunkEndDate;
                $chunkEndDate = clone $chunkStartDate;
                $chunkEndDate->modify('+5 years');
                
                // Don't go beyond current date
                if ($chunkEndDate > $currentDate) {
                    $chunkEndDate = $currentDate;
                }
                
                $chunks[] = [
                    'start' => clone $chunkStartDate,
                    'end' => clone $chunkEndDate,
                    'days' => $chunkStartDate->diff($chunkEndDate)->days
                ];
                
                $chunkEndDate->modify('+1 day'); // Start next chunk the day after
                $totalChunks++;
            }
            
            $this->logger->info("Planned {$totalChunks} chunks for {$symbol}");
            
            // Process each chunk
            foreach ($chunks as $index => $chunk) {
                $chunkNumber = $index + 1;
                $startDate = $chunk['start']->format('Y-m-d');
                $endDate = $chunk['end']->format('Y-m-d');
                
                $this->logger->info("Processing chunk {$chunkNumber}/{$totalChunks} for {$symbol}: {$startDate} to {$endDate}");
                
                // Check if we already have data for this period
                if ($this->hasDataForPeriod($symbol, $chunk['start'], $chunk['end'])) {
                    $this->logger->info("Skipping chunk {$chunkNumber} - data already exists");
                    continue;
                }
                
                // Fetch data for this chunk
                $result = $this->fetchChunkData($symbol, $chunk['start'], $chunk['end']);
                
                if ($result['success']) {
                    $totalRecords += $result['records'];
                    $this->logger->info("Chunk {$chunkNumber} completed: {$result['records']} records");
                } else {
                    $this->logger->warning("Chunk {$chunkNumber} failed: {$result['error']}");
                    
                    // If we get a "no data" error, we've probably gone too far back
                    if (strpos($result['error'], 'No data') !== false) {
                        $this->logger->info("No more data available before {$startDate}");
                        break;
                    }
                }
                
                // Rate limiting delay
                if ($chunkNumber < $totalChunks) {
                    $this->logger->debug("Waiting {$this->delayBetweenRequests} seconds before next chunk...");
                    sleep($this->delayBetweenRequests);
                }
            }
            
            return [
                'success' => true,
                'symbol' => $symbol,
                'chunks_processed' => $totalChunks,
                'total_records' => $totalRecords,
                'date_range' => [
                    'oldest' => $targetDate->format('Y-m-d'),
                    'newest' => $currentDate->format('Y-m-d')
                ]
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Progressive load failed for {$symbol}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'symbol' => $symbol
            ];
        }
    }
    
    /**
     * Get oldest data date for a symbol
     */
    private function getOldestDataDate($symbol) {
        try {
            $data = $this->stockDAO->getPriceDataOrdered($symbol, 'ASC', 1);
            if (!empty($data)) {
                return new DateTime($data[0]['date']);
            }
        } catch (Exception $e) {
            $this->logger->debug("Could not get oldest date for {$symbol}: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Get latest data date for a symbol
     */
    private function getLatestDataDate($symbol) {
        try {
            $data = $this->stockDAO->getLatestPrice($symbol);
            if ($data && isset($data['date'])) {
                return new DateTime($data['date']);
            }
        } catch (Exception $e) {
            $this->logger->debug("Could not get latest date for {$symbol}: " . $e->getMessage());
        }
        return null;
    }
    
    /**
     * Estimate IPO date for a symbol (simplified)
     */
    private function estimateIPODate($symbol) {
        // This could be enhanced with a lookup table or API call
        $knownIPOs = [
            'AAPL' => '1980-12-12',
            'MSFT' => '1986-03-13',
            'GOOGL' => '2004-08-19',
            'AMZN' => '1997-05-15',
            'TSLA' => '2010-06-29',
            'META' => '2012-05-18',
            'NVDA' => '1999-01-22'
        ];
        
        if (isset($knownIPOs[$symbol])) {
            return new DateTime($knownIPOs[$symbol]);
        }
        
        return null; // Unknown, will use default
    }
    
    /**
     * Check if we already have data for a specific period
     */
    private function hasDataForPeriod($symbol, $startDate, $endDate) {
        try {
            // Sample a few dates in the period to see if we have data
            $checkDate = clone $startDate;
            $checkDate->modify('+30 days'); // Check 30 days in
            
            if ($checkDate > $endDate) {
                $checkDate = clone $endDate;
            }
            
            $data = $this->stockDAO->getPriceDataForDate($symbol, $checkDate->format('Y-m-d'));
            return !empty($data);
            
        } catch (Exception $e) {
            return false; // Assume no data if we can't check
        }
    }
    
    /**
     * Fetch data for a specific chunk using PHP Stock Data Service
     */
    private function fetchChunkData($symbol, $startDate, $endDate) {
        try {
            $startDateStr = $startDate->format('Y-m-d');
            $endDateStr = $endDate->format('Y-m-d');
            
            // Use PHP Stock Data Service instead of Python script
            $this->logger->debug("Fetching data using PHP service for {$symbol} from {$startDateStr} to {$endDateStr}");
            
            $jsonOutput = $this->stockDataService->fetchHistoricalData($symbol, $startDateStr, $endDateStr);
            
            // Save raw output for debugging
            $debugDir = __DIR__ . '/data/debug';
            if (!is_dir($debugDir)) {
                mkdir($debugDir, 0755, true);
            }
            $debugFile = $debugDir . "/debug_output_{$symbol}_{$startDateStr}.json";
            file_put_contents($debugFile, $jsonOutput);
            $this->logger->debug("Raw JSON output saved to: {$debugFile}");
            
            $data = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error("JSON decode error: " . json_last_error_msg());
                $this->logger->error("Raw output: " . substr($jsonOutput, 0, 1000));
                return ['success' => false, 'error' => 'Invalid JSON response: ' . json_last_error_msg()];
            }
            
            if (!$data['success']) {
                $this->logger->error("Stock data service returned error: " . ($data['error'] ?? 'Unknown error'));
                return ['success' => false, 'error' => $data['error'] ?? 'Unknown error from stock data service'];
            }
            
            if (!isset($data['data']) || !is_array($data['data'])) {
                $this->logger->error("No data array found in response");
                return ['success' => false, 'error' => 'No data array in response'];
            }
            
            // Initialize stock tables if needed
            $this->stockDAO->initializeStock($symbol);
            
            // Store the data using StockDAO
            $recordCount = 0;
            $errors = [];
            
            foreach ($data['data'] as $record) {
                try {
                    $result = $this->stockDAO->upsertPriceData($symbol, [
                        'date' => $record['Date'],
                        'open' => $record['Open'],
                        'high' => $record['High'],
                        'low' => $record['Low'],
                        'close' => $record['Close'],
                        'volume' => $record['Volume']
                    ]);
                    
                    if ($result) {
                        $recordCount++;
                    } else {
                        $errors[] = "Failed to insert record for date: " . $record['Date'];
                    }
                } catch (Exception $e) {
                    $errors[] = "Error inserting record for " . $record['Date'] . ": " . $e->getMessage();
                }
            }
            
            if (!empty($errors) && $recordCount === 0) {
                $this->logger->error("All records failed to insert: " . implode('; ', array_slice($errors, 0, 3)));
                return ['success' => false, 'error' => 'Failed to insert any records: ' . $errors[0]];
            }
            
            if (!empty($errors)) {
                $this->logger->warning("Some records failed to insert: " . count($errors) . " errors");
            }
            
            return [
                'success' => true,
                'records' => $recordCount,
                'start_date' => $startDateStr,
                'end_date' => $endDate->format('Y-m-d'),
                'total_fetched' => count($data['data']),
                'errors' => count($errors)
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Exception in fetchChunkData: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process CSV files from a directory
     */
    public function processCsvFiles($csvDirectory) {
        $results = [];
        
        if (!is_dir($csvDirectory)) {
            return ['success' => false, 'error' => 'CSV directory does not exist'];
        }
        
        $csvFiles = glob($csvDirectory . '/*.csv');
        $this->logger->info("Found " . count($csvFiles) . " CSV files to process");
        
        foreach ($csvFiles as $csvFile) {
            $filename = basename($csvFile);
            $this->logger->info("Processing CSV file: {$filename}");
            
            // Try to extract symbol from filename
            if (preg_match('/([A-Z]+)/', $filename, $matches)) {
                $symbol = $matches[1];
            } else {
                $this->logger->warning("Could not extract symbol from filename: {$filename}");
                continue;
            }
            
            $result = $this->processSingleCsvFile($csvFile, $symbol);
            $results[$symbol] = $result;
        }
        
        return [
            'success' => true,
            'processed_files' => count($csvFiles),
            'results' => $results
        ];
    }
    
    /**
     * Process a single CSV file
     */
    private function processSingleCsvFile($csvFile, $symbol) {
        try {
            if (!file_exists($csvFile)) {
                return ['success' => false, 'error' => 'File does not exist'];
            }
            
            $this->stockDAO->initializeStock($symbol);
            
            $handle = fopen($csvFile, 'r');
            if ($handle === false) {
                return ['success' => false, 'error' => 'Could not open CSV file'];
            }
            
            // Read header
            $header = fgetcsv($handle, 0, ',', '"', '\\');
            if (!$header) {
                fclose($handle);
                return ['success' => false, 'error' => 'Could not read CSV header'];
            }
            
            // Map header columns
            $columnMap = $this->mapCsvColumns($header);
            if (!$columnMap) {
                fclose($handle);
                return ['success' => false, 'error' => 'Could not map CSV columns'];
            }
            
            $recordCount = 0;
            $errors = [];
            
            while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
                try {
                    $record = $this->mapCsvRow($data, $columnMap);
                    if ($record) {
                        $result = $this->stockDAO->upsertPriceData($symbol, $record);
                        if ($result) {
                            $recordCount++;
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = $e->getMessage();
                    if (count($errors) > 10) break; // Stop after too many errors
                }
            }
            
            fclose($handle);
            
            return [
                'success' => true,
                'records' => $recordCount,
                'errors' => count($errors),
                'file' => basename($csvFile)
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Map CSV column headers to our expected format
     */
    private function mapCsvColumns($header) {
        $map = [];
        
        foreach ($header as $index => $column) {
            $column = strtolower(trim($column));
            
            switch ($column) {
                case 'date':
                    $map['date'] = $index;
                    break;
                case 'open':
                    $map['open'] = $index;
                    break;
                case 'high':
                    $map['high'] = $index;
                    break;
                case 'low':
                    $map['low'] = $index;
                    break;
                case 'close':
                case 'adj close':
                    $map['close'] = $index;
                    break;
                case 'volume':
                    $map['volume'] = $index;
                    break;
            }
        }
        
        // Check required fields
        if (!isset($map['date']) || !isset($map['close'])) {
            return false;
        }
        
        return $map;
    }
    
    /**
     * Map a CSV row to our record format
     */
    private function mapCsvRow($data, $columnMap) {
        if (empty($data) || !isset($data[$columnMap['date']])) {
            return null;
        }
        
        return [
            'date' => $data[$columnMap['date']],
            'open' => isset($columnMap['open']) ? floatval($data[$columnMap['open']] ?? 0) : 0,
            'high' => isset($columnMap['high']) ? floatval($data[$columnMap['high']] ?? 0) : 0,
            'low' => isset($columnMap['low']) ? floatval($data[$columnMap['low']] ?? 0) : 0,
            'close' => floatval($data[$columnMap['close']] ?? 0),
            'volume' => isset($columnMap['volume']) ? intval($data[$columnMap['volume']] ?? 0) : 0
        ];
    }
    
    /**
     * Load historical data for multiple symbols
     */
    public function loadMultipleSymbols($symbols, $maxConcurrent = 1) {
        $results = [];
        $processed = 0;
        
        $this->logger->info("Starting progressive load for " . count($symbols) . " symbols");
        
        foreach ($symbols as $symbol) {
            $processed++;
            $this->logger->info("Processing symbol {$processed}/" . count($symbols) . ": {$symbol}");
            
            $result = $this->loadAllHistoricalData($symbol);
            $results[$symbol] = $result;
            
            // Brief delay between symbols
            if ($processed < count($symbols)) {
                sleep(1);
            }
        }
        
        return $results;
    }
    
    /**
     * Get progress information for a symbol
     */
    public function getProgressInfo($symbol) {
        $oldestDate = $this->getOldestDataDate($symbol);
        $latestDate = $this->getLatestDataDate($symbol);
        
        $info = [
            'symbol' => $symbol,
            'has_data' => $oldestDate !== null,
            'oldest_date' => $oldestDate ? $oldestDate->format('Y-m-d') : null,
            'latest_date' => $latestDate ? $latestDate->format('Y-m-d') : null,
            'total_records' => $this->stockDAO->getPriceDataCount($symbol)
        ];
        
        if ($oldestDate && $latestDate) {
            $info['date_span_days'] = $oldestDate->diff($latestDate)->days;
            $info['date_span_years'] = round($info['date_span_days'] / 365, 1);
        }
        
        return $info;
    }
}

/**
 * Simple logger class
 */
class SimpleLogger {
    public function info($message) {
        echo "[INFO] " . date('Y-m-d H:i:s') . " - {$message}\n";
    }
    
    public function warning($message) {
        echo "[WARNING] " . date('Y-m-d H:i:s') . " - {$message}\n";
    }
    
    public function error($message) {
        echo "[ERROR] " . date('Y-m-d H:i:s') . " - {$message}\n";
    }
    
    public function debug($message) {
        // Only show debug in verbose mode
        if (isset($_GET['debug']) || (isset($GLOBALS['argv']) && in_array('--debug', $GLOBALS['argv']))) {
            echo "[DEBUG] " . date('Y-m-d H:i:s') . " - {$message}\n";
        }
    }
}