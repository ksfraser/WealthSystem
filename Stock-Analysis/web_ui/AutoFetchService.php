<?php
/**
 * Auto-Fetch Service for Daily Stock Data
 * Handles automatic fetching of stock data when users log in
 */

class AutoFetchService {
    private $configFile;
    private $stockDAO;
    
    public function __construct($stockDAO = null) {
        $this->configFile = __DIR__ . '/data/auto_fetch_config.json';
        $this->stockDAO = $stockDAO;
        
        // Ensure data directory exists
        if (!is_dir(__DIR__ . '/data')) {
            mkdir(__DIR__ . '/data', 0755, true);
        }
    }
    
    /**
     * Check if auto-fetch is enabled and perform fetch if needed
     */
    public function performAutoFetchIfNeeded() {
        if (!$this->isAutoFetchEnabled()) {
            return ['success' => true, 'message' => 'Auto-fetch is disabled'];
        }
        
        if (!$this->shouldFetchToday()) {
            return ['success' => true, 'message' => 'Data already fetched today'];
        }
        
        return $this->performDailyFetch();
    }
    
    /**
     * Check if auto-fetch is enabled
     */
    public function isAutoFetchEnabled() {
        if (!file_exists($this->configFile)) {
            return false;
        }
        
        $config = json_decode(file_get_contents($this->configFile), true);
        return isset($config['auto_fetch_enabled']) && $config['auto_fetch_enabled'] === true;
    }
    
    /**
     * Check if we should fetch data today
     */
    private function shouldFetchToday() {
        if (!file_exists($this->configFile)) {
            return true; // First time, should fetch
        }
        
        $config = json_decode(file_get_contents($this->configFile), true);
        $lastFetchDate = $config['last_fetch_date'] ?? null;
        
        if ($lastFetchDate === null) {
            return true; // Never fetched before
        }
        
        $today = date('Y-m-d');
        return $lastFetchDate !== $today;
    }
    
    /**
     * Perform the daily data fetch
     */
    private function performDailyFetch() {
        try {
            $portfolioSymbols = $this->getPortfolioSymbols();
            
            if (empty($portfolioSymbols)) {
                return ['success' => false, 'message' => 'No portfolio symbols found'];
            }
            
            $fetchedSymbols = [];
            $errors = [];
            
            foreach ($portfolioSymbols as $symbol) {
                $result = $this->fetchSingleSymbol($symbol);
                if ($result['success']) {
                    $fetchedSymbols[] = $symbol;
                } else {
                    $errors[] = "$symbol: " . $result['error'];
                }
            }
            
            // Update last fetch date
            $this->updateLastFetchDate();
            
            if (empty($errors)) {
                return [
                    'success' => true, 
                    'message' => 'Successfully fetched data for all symbols: ' . implode(', ', $fetchedSymbols)
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Partial success. Errors: ' . implode('; ', $errors)
                ];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Auto-fetch failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Fetch data for a single symbol
     */
    private function fetchSingleSymbol($symbol) {
        try {
            // Use Python script to fetch data
            $command = "cd \"c:\\Users\\prote\\Documents\\ChatGPT-Micro-Cap-Experiment\" && python fetch_historical_data.py \"$symbol\" 1 2>&1";
            $output = shell_exec($command);
            
            if ($output === null) {
                return ['success' => false, 'error' => 'Failed to execute Python script'];
            }
            
            // Check for Python errors in output
            if (strpos($output, 'Error:') !== false || strpos($output, 'Traceback') !== false) {
                return ['success' => false, 'error' => 'Python script error: ' . trim($output)];
            }
            
            $data = json_decode($output, true);
            if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
                return ['success' => false, 'error' => 'Invalid JSON response from Python script'];
            }
            
            // Store the data if StockDAO is available
            if ($this->stockDAO) {
                foreach ($data['data'] as $record) {
                    $this->stockDAO->upsertPriceData($symbol, [
                        'date' => $record['Date'],
                        'open' => $record['Open'],
                        'high' => $record['High'],
                        'low' => $record['Low'],
                        'close' => $record['Close'],
                        'volume' => $record['Volume']
                    ]);
                }
            } else {
                // Log to a simple file if no DAO available
                $this->logFetchResult($symbol, $data['data']);
            }
            
            return ['success' => true];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get portfolio symbols from CSV
     */
    private function getPortfolioSymbols() {
        $portfolioFile = __DIR__ . '/Scripts and CSV Files/chatgpt_portfolio_update.csv';
        $symbols = [];
        
        if (file_exists($portfolioFile)) {
            $handle = fopen($portfolioFile, 'r');
            $header = fgetcsv($handle); // Skip header
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0])) {
                    $symbols[] = strtoupper(trim($data[0]));
                }
            }
            fclose($handle);
        }
        
        return array_unique($symbols);
    }
    
    /**
     * Update the last fetch date
     */
    private function updateLastFetchDate() {
        $config = ['auto_fetch_enabled' => true, 'last_fetch_date' => date('Y-m-d')];
        file_put_contents($this->configFile, json_encode($config));
    }
    
    /**
     * Simple logging when DAO is not available
     */
    private function logFetchResult($symbol, $data) {
        $logFile = __DIR__ . '/data/auto_fetch_log.txt';
        $logEntry = date('Y-m-d H:i:s') . " - Fetched " . count($data) . " records for $symbol\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Enable auto-fetch
     */
    public function enableAutoFetch() {
        $config = ['auto_fetch_enabled' => true, 'last_fetch_date' => null];
        return file_put_contents($this->configFile, json_encode($config)) !== false;
    }
    
    /**
     * Disable auto-fetch
     */
    public function disableAutoFetch() {
        $config = ['auto_fetch_enabled' => false, 'last_fetch_date' => null];
        return file_put_contents($this->configFile, json_encode($config)) !== false;
    }
    
    /**
     * Get auto-fetch status information
     */
    public function getStatus() {
        if (!file_exists($this->configFile)) {
            return [
                'enabled' => false,
                'last_fetch_date' => null,
                'should_fetch_today' => false
            ];
        }
        
        $config = json_decode(file_get_contents($this->configFile), true);
        return [
            'enabled' => isset($config['auto_fetch_enabled']) && $config['auto_fetch_enabled'] === true,
            'last_fetch_date' => $config['last_fetch_date'] ?? null,
            'should_fetch_today' => $this->shouldFetchToday()
        ];
    }
}