<?php

namespace App\Services;

use App\Services\Interfaces\MarketDataServiceInterface;

/**
 * Data Synchronization Service
 * 
 * Coordinates data flow between existing Python systems, CSV files, database,
 * and the new MVC architecture to ensure portfolio shows actual data.
 */
class DataSynchronizationService
{
    private MarketDataServiceInterface $marketDataService;
    private PythonIntegrationService $pythonService;
    
    public function __construct(MarketDataServiceInterface $marketDataService, PythonIntegrationService $pythonService)
    {
        $this->marketDataService = $marketDataService;
        $this->pythonService = $pythonService;
    }
    
    /**
     * Synchronize all portfolio data from various sources
     */
    public function synchronizePortfolioData(): array
    {
        $results = [
            'portfolio_sync' => false,
            'price_sync' => false,
            'errors' => []
        ];
        
        try {
            // 1. Load portfolio data from CSV
            $portfolioResult = $this->syncPortfolioFromCSV();
            $results['portfolio_sync'] = $portfolioResult['success'];
            if (!$portfolioResult['success']) {
                $results['errors'][] = "Portfolio sync failed: " . $portfolioResult['error'];
            }
            
            // 2. Update prices for all symbols in portfolio
            if ($portfolioResult['success'] && !empty($portfolioResult['symbols'])) {
                $priceResult = $this->syncPricesForSymbols($portfolioResult['symbols']);
                $results['price_sync'] = $priceResult['success'];
                if (!$priceResult['success']) {
                    $results['errors'][] = "Price sync failed: " . $priceResult['error'];
                }
            }
            
            // 3. Validate data availability
            $validation = $this->validateDataAvailability();
            $results['validation'] = $validation;
            
        } catch (\Exception $e) {
            $results['errors'][] = "Sync error: " . $e->getMessage();
        }
        
        return $results;
    }
    
    /**
     * Sync portfolio data from CSV files
     */
    private function syncPortfolioFromCSV(): array
    {
        try {
            // Try multiple possible CSV locations
            $csvPaths = [
                __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv',
                __DIR__ . '/../../Start Your Own/chatgpt_portfolio_update.csv',
                __DIR__ . '/../../data_micro_cap/chatgpt_portfolio_update.csv'
            ];
            
            $symbols = [];
            $portfolioData = [];
            
            foreach ($csvPaths as $csvPath) {
                if (file_exists($csvPath)) {
                    $data = $this->readCSV($csvPath);
                    if (!empty($data)) {
                        $portfolioData = array_merge($portfolioData, $data);
                        
                        // Extract symbols for price updates
                        foreach ($data as $row) {
                            if (isset($row['Ticker']) && !empty($row['Ticker']) && strtoupper($row['Ticker']) !== 'TOTAL') {
                                $symbols[] = strtoupper(trim($row['Ticker']));
                            }
                        }
                        break; // Use first valid CSV found
                    }
                }
            }
            
            if (empty($portfolioData)) {
                return ['success' => false, 'error' => 'No portfolio CSV files found or readable'];
            }
            
            return [
                'success' => true,
                'symbols' => array_unique($symbols),
                'portfolio_data' => $portfolioData
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Sync prices for portfolio symbols
     */
    private function syncPricesForSymbols(array $symbols): array
    {
        try {
            $updated = 0;
            $errors = [];
            
            foreach ($symbols as $symbol) {
                try {
                    // Try to get current price from market data service
                    $priceData = $this->marketDataService->getCurrentPrice($symbol);
                    
                    if (!$priceData) {
                        // Fallback to Python integration
                        $pythonResult = $this->pythonService->fetchPriceData($symbol, '5d');
                        if ($pythonResult['success']) {
                            $updated++;
                        } else {
                            $errors[] = "Failed to update {$symbol}: " . $pythonResult['error'];
                        }
                    } else {
                        $updated++;
                    }
                    
                } catch (\Exception $e) {
                    $errors[] = "Error updating {$symbol}: " . $e->getMessage();
                }
            }
            
            return [
                'success' => $updated > 0,
                'updated' => $updated,
                'total_symbols' => count($symbols),
                'errors' => $errors
            ];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Validate that we have data for the dashboard
     */
    private function validateDataAvailability(): array
    {
        $validation = [
            'portfolio_file_exists' => false,
            'database_accessible' => false,
            'market_data_available' => false,
            'python_environment' => false
        ];
        
        // Check portfolio files
        $csvPaths = [
            __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv',
            __DIR__ . '/../../Start Your Own/chatgpt_portfolio_update.csv'
        ];
        
        foreach ($csvPaths as $path) {
            if (file_exists($path) && filesize($path) > 0) {
                $validation['portfolio_file_exists'] = true;
                break;
            }
        }
        
        // Check database
        try {
            require_once __DIR__ . '/../../DynamicStockDataAccess.php';
            $dataAccess = new \DynamicStockDataAccess();
            $validation['database_accessible'] = true;
        } catch (\Exception $e) {
            // Database not accessible
        }
        
        // Check market data
        try {
            $testPrice = $this->marketDataService->getCurrentPrice('AAPL');
            $validation['market_data_available'] = !empty($testPrice);
        } catch (\Exception $e) {
            // Market data not accessible
        }
        
        // Check Python environment
        $pythonCheck = $this->pythonService->checkPythonEnvironment();
        $validation['python_environment'] = $pythonCheck['available'];
        
        return $validation;
    }
    
    /**
     * Read CSV file into array
     */
    private function readCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return [];
        }
        
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle) {
            $headers = fgetcsv($handle);
            if ($headers) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($headers)) {
                        $data[] = array_combine($headers, $row);
                    }
                }
            }
            fclose($handle);
        }
        
        return $data;
    }
    
    /**
     * Get sync status summary
     */
    public function getSyncStatus(): array
    {
        $validation = $this->validateDataAvailability();
        
        return [
            'overall_status' => array_sum($validation) >= 2 ? 'good' : (array_sum($validation) === 1 ? 'partial' : 'poor'),
            'details' => $validation,
            'recommendations' => $this->getRecommendations($validation)
        ];
    }
    
    /**
     * Get recommendations based on validation results
     */
    private function getRecommendations(array $validation): array
    {
        $recommendations = [];
        
        if (!$validation['portfolio_file_exists']) {
            $recommendations[] = 'Upload or create portfolio CSV file in Scripts and CSV Files directory';
        }
        
        if (!$validation['database_accessible']) {
            $recommendations[] = 'Check database connection and DynamicStockDataAccess configuration';
        }
        
        if (!$validation['market_data_available']) {
            $recommendations[] = 'Verify market data API access and configuration';
        }
        
        if (!$validation['python_environment']) {
            $recommendations[] = 'Install Python and required packages for data fetching';
        }
        
        if (empty($recommendations)) {
            $recommendations[] = 'All systems operational - data should be available';
        }
        
        return $recommendations;
    }
}