<?php
/**
 * Stock Price Retrieval Service
 * 
 * Integrates with existing Python trading_script.py for price data fetching
 * Provides PHP interface to Yahoo Finance and other data sources
 */

require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/models/StockModels.php';

class StockPriceService {
    private $stockDAO;
    private $pythonScript;
    private $config;
    
    public function __construct(StockDAO $stockDAO, array $config = []) {
        $this->stockDAO = $stockDAO;
        $this->pythonScript = $config['python_script'] ?? __DIR__ . '/../trading_script.py';
        $this->config = array_merge([
            'python_executable' => 'python',
            'cache_timeout' => 300, // 5 minutes
            'batch_size' => 50,
            'max_retries' => 3,
            'yahoo_finance_enabled' => true,
            'stooq_enabled' => false,
            'debug_mode' => false
        ], $config);
    }
    
    /**
     * Fetch current price for a single stock
     */
    public function fetchCurrentPrice(string $symbol): ?array {
        try {
            // Check cache first
            $cached = $this->getCachedPrice($symbol);
            if ($cached) {
                return $cached;
            }
            
            // Fetch from Python script
            $command = sprintf(
                '%s "%s" --symbol="%s" --action="get_current_price" --output="json"',
                $this->config['python_executable'],
                $this->pythonScript,
                escapeshellarg($symbol)
            );
            
            $output = $this->executeCommand($command);
            
            if ($output && isset($output['success']) && $output['success']) {
                $priceData = $this->formatPriceData($output['data']);
                
                // Cache the result
                $this->cachePrice($symbol, $priceData);
                
                // Save to database
                $this->stockDAO->upsertPriceData($symbol, $priceData);
                
                return $priceData;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Failed to fetch current price for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch historical price data
     */
    public function fetchHistoricalPrices(string $symbol, string $startDate, ?string $endDate = null): array {
        try {
            $endDate = $endDate ?? date('Y-m-d');
            
            $command = sprintf(
                '%s "%s" --symbol="%s" --action="get_historical_prices" --start_date="%s" --end_date="%s" --output="json"',
                $this->config['python_executable'],
                $this->pythonScript,
                escapeshellarg($symbol),
                escapeshellarg($startDate),
                escapeshellarg($endDate)
            );
            
            $output = $this->executeCommand($command);
            
            if ($output && isset($output['success']) && $output['success']) {
                $historicalData = [];
                
                foreach ($output['data'] as $row) {
                    $priceData = $this->formatPriceData($row);
                    $historicalData[] = $priceData;
                }
                
                // Batch insert to database
                if (!empty($historicalData)) {
                    $this->stockDAO->batchUpsertPriceData($symbol, $historicalData);
                }
                
                return $historicalData;
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log("Failed to fetch historical prices for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Fetch prices for multiple stocks
     */
    public function fetchMultipleCurrentPrices(array $symbols): array {
        $results = [];
        $batches = array_chunk($symbols, $this->config['batch_size']);
        
        foreach ($batches as $batch) {
            try {
                $symbolList = implode(',', array_map('escapeshellarg', $batch));
                
                $command = sprintf(
                    '%s "%s" --symbols="%s" --action="get_multiple_prices" --output="json"',
                    $this->config['python_executable'],
                    $this->pythonScript,
                    $symbolList
                );
                
                $output = $this->executeCommand($command);
                
                if ($output && isset($output['success']) && $output['success']) {
                    foreach ($output['data'] as $symbol => $data) {
                        $priceData = $this->formatPriceData($data);
                        $results[$symbol] = $priceData;
                        
                        // Save to database
                        $this->stockDAO->upsertPriceData($symbol, $priceData);
                        
                        // Cache the result
                        $this->cachePrice($symbol, $priceData);
                    }
                }
                
            } catch (Exception $e) {
                error_log("Failed to fetch batch prices: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Update all tracked stocks with current prices
     */
    public function updateAllStockPrices(): array {
        try {
            // Get all active stocks from database
            $stocks = $this->stockDAO->searchStocks(['limit' => 1000]);
            $symbols = array_column($stocks, 'symbol');
            
            if (empty($symbols)) {
                return ['updated' => 0, 'failed' => 0, 'symbols' => []];
            }
            
            $results = $this->fetchMultipleCurrentPrices($symbols);
            
            return [
                'updated' => count($results),
                'failed' => count($symbols) - count($results),
                'symbols' => array_keys($results),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Failed to update all stock prices: " . $e->getMessage());
            return ['updated' => 0, 'failed' => 0, 'symbols' => []];
        }
    }
    
    /**
     * Get real-time quote with additional info
     */
    public function getRealTimeQuote(string $symbol): ?array {
        try {
            $command = sprintf(
                '%s "%s" --symbol="%s" --action="get_quote" --output="json"',
                $this->config['python_executable'],
                $this->pythonScript,
                escapeshellarg($symbol)
            );
            
            $output = $this->executeCommand($command);
            
            if ($output && isset($output['success']) && $output['success']) {
                $quote = $output['data'];
                
                // Format and enhance quote data
                return [
                    'symbol' => $symbol,
                    'price' => $quote['price'] ?? null,
                    'change' => $quote['change'] ?? null,
                    'change_percent' => $quote['change_percent'] ?? null,
                    'volume' => $quote['volume'] ?? null,
                    'market_cap' => $quote['market_cap'] ?? null,
                    'pe_ratio' => $quote['pe_ratio'] ?? null,
                    'dividend_yield' => $quote['dividend_yield'] ?? null,
                    'fifty_two_week_high' => $quote['fifty_two_week_high'] ?? null,
                    'fifty_two_week_low' => $quote['fifty_two_week_low'] ?? null,
                    'avg_volume' => $quote['avg_volume'] ?? null,
                    'beta' => $quote['beta'] ?? null,
                    'timestamp' => $quote['timestamp'] ?? date('Y-m-d H:i:s'),
                    'market_state' => $quote['market_state'] ?? 'UNKNOWN',
                    'data_source' => $quote['data_source'] ?? 'yahoo'
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Failed to get real-time quote for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculate technical indicators using Python script
     */
    public function calculateTechnicalIndicators(string $symbol, int $period = 100): ?array {
        try {
            $command = sprintf(
                '%s "%s" --symbol="%s" --action="calculate_indicators" --period="%d" --output="json"',
                $this->config['python_executable'],
                $this->pythonScript,
                escapeshellarg($symbol),
                $period
            );
            
            $output = $this->executeCommand($command);
            
            if ($output && isset($output['success']) && $output['success']) {
                $indicators = $output['data'];
                
                // Save technical indicators to database
                if (!empty($indicators)) {
                    foreach ($indicators as $date => $dayIndicators) {
                        $this->stockDAO->updateTechnicalIndicators($symbol, $date, $dayIndicators);
                    }
                }
                
                return $indicators;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log("Failed to calculate technical indicators for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Execute Python command and parse JSON output
     */
    private function executeCommand(string $command): ?array {
        if ($this->config['debug_mode']) {
            error_log("Executing command: " . $command);
        }
        
        $retries = 0;
        
        while ($retries < $this->config['max_retries']) {
            $output = shell_exec($command . ' 2>&1');
            
            if ($output === null) {
                $retries++;
                continue;
            }
            
            // Try to parse JSON output
            $decoded = json_decode($output, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            if ($this->config['debug_mode']) {
                error_log("Command output (attempt " . ($retries + 1) . "): " . $output);
            }
            
            $retries++;
            sleep(1); // Wait before retry
        }
        
        error_log("Command failed after {$this->config['max_retries']} retries: " . $command);
        return null;
    }
    
    /**
     * Format raw price data into standardized format
     */
    private function formatPriceData(array $rawData): array {
        return [
            'date' => $rawData['Date'] ?? $rawData['date'] ?? date('Y-m-d'),
            'open' => $this->parseFloat($rawData['Open'] ?? $rawData['open']),
            'high' => $this->parseFloat($rawData['High'] ?? $rawData['high']),
            'low' => $this->parseFloat($rawData['Low'] ?? $rawData['low']),
            'close' => $this->parseFloat($rawData['Close'] ?? $rawData['close']),
            'adj_close' => $this->parseFloat($rawData['Adj Close'] ?? $rawData['adj_close'] ?? $rawData['Close'] ?? $rawData['close']),
            'volume' => $this->parseInt($rawData['Volume'] ?? $rawData['volume']),
            'split_coefficient' => $this->parseFloat($rawData['split_coefficient'] ?? 1.0),
            'dividend_amount' => $this->parseFloat($rawData['dividend_amount'] ?? 0.0),
            'data_source' => $rawData['data_source'] ?? 'yahoo'
        ];
    }
    
    /**
     * Parse float value safely
     */
    private function parseFloat($value): ?float {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }
        
        return (float) $value;
    }
    
    /**
     * Parse integer value safely
     */
    private function parseInt($value): ?int {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }
        
        return (int) $value;
    }
    
    /**
     * Cache price data in memory/file
     */
    private function cachePrice(string $symbol, array $priceData): void {
        try {
            $cacheDir = __DIR__ . '/cache';
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir, 0755, true);
            }
            
            $cacheFile = $cacheDir . '/price_' . $symbol . '.json';
            $cacheData = [
                'data' => $priceData,
                'timestamp' => time(),
                'expires' => time() + $this->config['cache_timeout']
            ];
            
            file_put_contents($cacheFile, json_encode($cacheData));
            
        } catch (Exception $e) {
            error_log("Failed to cache price for {$symbol}: " . $e->getMessage());
        }
    }
    
    /**
     * Get cached price data if not expired
     */
    private function getCachedPrice(string $symbol): ?array {
        try {
            $cacheFile = __DIR__ . '/cache/price_' . $symbol . '.json';
            
            if (!file_exists($cacheFile)) {
                return null;
            }
            
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            if (!$cacheData || time() > $cacheData['expires']) {
                unlink($cacheFile); // Remove expired cache
                return null;
            }
            
            return $cacheData['data'];
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Validate Python script availability
     */
    public function validatePythonScript(): array {
        $result = [
            'python_available' => false,
            'script_exists' => false,
            'dependencies_ok' => false,
            'errors' => []
        ];
        
        // Check Python executable
        $pythonCheck = shell_exec($this->config['python_executable'] . ' --version 2>&1');
        if ($pythonCheck && strpos($pythonCheck, 'Python') !== false) {
            $result['python_available'] = true;
        } else {
            $result['errors'][] = 'Python executable not found or not working';
        }
        
        // Check script file
        if (file_exists($this->pythonScript)) {
            $result['script_exists'] = true;
        } else {
            $result['errors'][] = 'Trading script not found at: ' . $this->pythonScript;
        }
        
        // Test script execution
        if ($result['python_available'] && $result['script_exists']) {
            $testCommand = sprintf(
                '%s "%s" --action="test" --output="json" 2>&1',
                $this->config['python_executable'],
                $this->pythonScript
            );
            
            $testOutput = shell_exec($testCommand);
            $testResult = json_decode($testOutput, true);
            
            if ($testResult && isset($testResult['success']) && $testResult['success']) {
                $result['dependencies_ok'] = true;
            } else {
                $result['errors'][] = 'Script test failed: ' . ($testOutput ?: 'Unknown error');
            }
        }
        
        return $result;
    }
    
    /**
     * Get service status and statistics
     */
    public function getServiceStatus(): array {
        $validation = $this->validatePythonScript();
        
        return [
            'service_available' => $validation['python_available'] && $validation['script_exists'] && $validation['dependencies_ok'],
            'python_script_path' => $this->pythonScript,
            'cache_timeout' => $this->config['cache_timeout'],
            'batch_size' => $this->config['batch_size'],
            'validation' => $validation,
            'last_check' => date('Y-m-d H:i:s')
        ];
    }
}