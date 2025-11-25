<?php
/**
 * PortfolioDAO: Clean, refactored portfolio data access
 * Uses BaseDAO for common functionality and clean architecture
 */

require_once __DIR__ . '/BaseDAO.php';

class PortfolioDAO extends BaseDAO {
    private $csvPaths;
    private $csvPath;
    
    public function __construct($csvPaths) {
        parent::__construct('portfolio');
        
        // Handle both single path and array of paths
        $this->csvPaths = is_array($csvPaths) ? $csvPaths : [$csvPaths];
        $this->csvPath = $this->findExistingFile($this->csvPaths) ?? $this->csvPaths[0];
    }
    
    /**
     * Read portfolio data, returning latest date entries
     */
    public function readPortfolio() {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) {
            return [];
        }
        
        // If Date column exists, filter to latest date
        if (isset($data[0]['Date'])) {
            return $this->filterToLatestDate($data);
        }
        
        return $data;
    }
    
    /**
     * Write portfolio data
     */
    public function writePortfolio($data) {
        if (empty($data)) {
            $this->logError('No portfolio data provided for writing');
            return false;
        }
        
        $success = $this->writeCsv($this->csvPath, $data);
        return $this->handleOperationResult($success, $data);
    }
    
    /**
     * Append new portfolio entries
     */
    public function appendPortfolio($data) {
        if (empty($data)) {
            $this->logError('No portfolio data provided for appending');
            return false;
        }
        
        $success = $this->appendCsv($this->csvPath, $data);
        return $this->handleOperationResult($success, $data);
    }
    
    /**
     * Get portfolio summary statistics
     */
    public function getPortfolioSummary() {
        $data = $this->readPortfolio();
        if (empty($data)) {
            return null;
        }
        
        $summary = [
            'total_positions' => count($data),
            'total_value' => 0,
            'total_pnl' => 0,
            'tickers' => []
        ];
        
        foreach ($data as $position) {
            $value = (float)($position['Total Value'] ?? $position['market_value'] ?? 0);
            $pnl = (float)($position['PnL'] ?? $position['unrealized_pnl'] ?? 0);
            $ticker = $position['Ticker'] ?? $position['Symbol'] ?? $position['symbol'] ?? '';
            
            $summary['total_value'] += $value;
            $summary['total_pnl'] += $pnl;
            
            if (!empty($ticker)) {
                $summary['tickers'][] = $ticker;
            }
        }
        
        $summary['tickers'] = array_unique($summary['tickers']);
        return $summary;
    }
    
    /**
     * Validate portfolio data structure
     */
    public function validatePortfolioStructure() {
        $expectedColumns = ['Ticker', 'Date', 'Shares', 'Buy Price', 'Current Price', 'Total Value'];
        return $this->validateCsv($this->csvPath, $expectedColumns);
    }
    
    /**
     * Filter data to latest date entries
     */
    private function filterToLatestDate($data) {
        $dates = array_column($data, 'Date');
        $latestDate = max($dates);
        
        return array_values(array_filter($data, function($row) use ($latestDate) {
            return $row['Date'] === $latestDate;
        }));
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
        
        return $this->writePortfolio($retryData);
    }
}
