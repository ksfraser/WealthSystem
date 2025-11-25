<?php
/**
 * TradeLogDAO: Clean, refactored trade log data access
 * Uses BaseDAO for common functionality and clean architecture
 */

require_once __DIR__ . '/BaseDAO.php';

class TradeLogDAO extends BaseDAO {
    private $csvPaths;
    private $csvPath;
    
    public function __construct($csvPaths) {
        parent::__construct('tradelog');
        
        // Handle both single path and array of paths
        $this->csvPaths = is_array($csvPaths) ? $csvPaths : [$csvPaths];
        $this->csvPath = $this->findExistingFile($this->csvPaths) ?? $this->csvPaths[0];
    }
    
    /**
     * Read trade log data with optional filtering
     */
    public function readTradeLog($filters = []) {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) {
            return [];
        }
        
        return $this->applyFilters($data, $filters);
    }
    
    /**
     * Write trade log data
     */
    public function writeTradeLog($data) {
        if (empty($data)) {
            $this->logError('No trade log data provided for writing');
            return false;
        }
        
        $success = $this->writeCsv($this->csvPath, $data);
        return $this->handleOperationResult($success, $data);
    }
    
    /**
     * Append new trade entries
     */
    public function appendTrades($data) {
        if (empty($data)) {
            $this->logError('No trade data provided for appending');
            return false;
        }
        
        $success = $this->appendCsv($this->csvPath, $data);
        return $this->handleOperationResult($success, $data);
    }
    
    /**
     * Add a single trade entry
     */
    public function addTrade($tradeData) {
        return $this->appendTrades([$tradeData]);
    }
    
    /**
     * Get trade statistics
     */
    public function getTradeStatistics($filters = []) {
        $data = $this->readTradeLog($filters);
        if (empty($data)) {
            return null;
        }
        
        $stats = [
            'total_trades' => count($data),
            'total_volume' => 0,
            'total_value' => 0,
            'unique_tickers' => [],
            'date_range' => ['earliest' => null, 'latest' => null]
        ];
        
        foreach ($data as $trade) {
            // Volume calculation
            $shares = (int)($trade['Shares'] ?? $trade['Quantity'] ?? $trade['quantity'] ?? 0);
            $stats['total_volume'] += $shares;
            
            // Value calculation
            $cost = (float)($trade['Cost'] ?? $trade['Price'] ?? $trade['avg_cost'] ?? 0);
            $stats['total_value'] += $cost * $shares;
            
            // Ticker tracking
            $ticker = $trade['Ticker'] ?? $trade['Symbol'] ?? $trade['symbol'] ?? '';
            if (!empty($ticker)) {
                $stats['unique_tickers'][] = $ticker;
            }
            
            // Date range tracking
            $date = $trade['Date'] ?? $trade['date'] ?? '';
            if (!empty($date)) {
                if ($stats['date_range']['earliest'] === null || $date < $stats['date_range']['earliest']) {
                    $stats['date_range']['earliest'] = $date;
                }
                if ($stats['date_range']['latest'] === null || $date > $stats['date_range']['latest']) {
                    $stats['date_range']['latest'] = $date;
                }
            }
        }
        
        $stats['unique_tickers'] = array_unique($stats['unique_tickers']);
        $stats['unique_ticker_count'] = count($stats['unique_tickers']);
        
        return $stats;
    }
    
    /**
     * Validate trade log data structure
     */
    public function validateTradeLogStructure() {
        $expectedColumns = ['Date', 'Ticker', 'Action', 'Shares', 'Price'];
        return $this->validateCsv($this->csvPath, $expectedColumns);
    }
    
    /**
     * Apply filters to trade data
     */
    private function applyFilters($data, $filters) {
        if (empty($filters)) {
            return $data;
        }
        
        return array_filter($data, function($row) use ($filters) {
            // Date from filter
            if (!empty($filters['date_from'])) {
                $date = $row['Date'] ?? $row['date'] ?? '';
                if (!empty($date) && $date < $filters['date_from']) {
                    return false;
                }
            }
            
            // Date to filter
            if (!empty($filters['date_to'])) {
                $date = $row['Date'] ?? $row['date'] ?? '';
                if (!empty($date) && $date > $filters['date_to']) {
                    return false;
                }
            }
            
            // Ticker filter
            if (!empty($filters['ticker'])) {
                $ticker = $row['Ticker'] ?? $row['Symbol'] ?? $row['symbol'] ?? '';
                if (stripos($ticker, $filters['ticker']) === false) {
                    return false;
                }
            }
            
            // Cost min filter
            if (!empty($filters['cost_min'])) {
                $cost = (float)($row['Cost'] ?? $row['Price'] ?? $row['avg_cost'] ?? 0);
                if ($cost < (float)$filters['cost_min']) {
                    return false;
                }
            }
            
            // Cost max filter
            if (!empty($filters['cost_max'])) {
                $cost = (float)($row['Cost'] ?? $row['Price'] ?? $row['avg_cost'] ?? 0);
                if ($cost > (float)$filters['cost_max']) {
                    return false;
                }
            }
            
            // Action filter (buy/sell)
            if (!empty($filters['action'])) {
                $action = $row['Action'] ?? $row['action'] ?? '';
                if (stripos($action, $filters['action']) === false) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    /**
     * Retry last failed operation
     */
    public function retryLastOperation() {
        $retryData = $this->getRetryData();
        if (!$retryData) {
            $this->logError('No retry data available');
            return false;
        }
        
        return $this->writeTradeLog($retryData);
    }
}
