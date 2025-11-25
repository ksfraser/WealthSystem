<?php

namespace App\Services;

use App\Services\Interfaces\MarketDataServiceInterface;

// Include existing data access systems
require_once __DIR__ . '/../../DynamicStockDataAccess.php';

/**
 * Market Data Service Implementation
 * 
 * Provides real-time and historical market data by integrating with existing
 * data fetching systems including DynamicStockDataAccess, Python scripts, and APIs.
 */
class MarketDataService implements MarketDataServiceInterface
{
    private ?\DynamicStockDataAccess $stockDataAccess = null;
    private array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
        
        // Initialize existing stock data access
        try {
            $this->stockDataAccess = new \DynamicStockDataAccess();
        } catch (\Exception $e) {
            // Will work with limited functionality
        }
    }
    
    /**
     * Get current stock prices for symbols
     */
    public function getCurrentPrices(array $symbols): array
    {
        $prices = [];
        
        foreach ($symbols as $symbol) {
            try {
                $price = $this->getCurrentPrice($symbol);
                if ($price !== null) {
                    $prices[$symbol] = $price;
                }
            } catch (\Exception $e) {
                // Skip failed symbol
                $prices[$symbol] = null;
            }
        }
        
        return $prices;
    }
    
    /**
     * Get current price for a single symbol
     */
    public function getCurrentPrice(string $symbol): ?array
    {
        if (!$this->stockDataAccess) {
            return null;
        }
        
        try {
            $priceData = $this->stockDataAccess->getLatestPrice($symbol);
            
            if ($priceData) {
                return [
                    'symbol' => $symbol,
                    'price' => (float)($priceData['close'] ?? 0),
                    'open' => (float)($priceData['open'] ?? 0),
                    'high' => (float)($priceData['high'] ?? 0),
                    'low' => (float)($priceData['low'] ?? 0),
                    'volume' => (int)($priceData['volume'] ?? 0),
                    'date' => $priceData['date'] ?? date('Y-m-d'),
                    'change' => $this->calculateDayChange($priceData),
                    'change_percent' => $this->calculateDayChangePercent($priceData)
                ];
            }
        } catch (\Exception $e) {
            error_log("Failed to get current price for {$symbol}: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Get historical price data for a symbol
     */
    public function getHistoricalPrices(string $symbol, ?string $startDate = null, ?string $endDate = null, ?int $limit = null): array
    {
        if (!$this->stockDataAccess) {
            return [];
        }
        
        try {
            $priceData = $this->stockDataAccess->getPriceData($symbol, $startDate, $endDate, $limit);
            
            if (empty($priceData)) {
                // Try to fetch from Python script as fallback
                return $this->fetchFromPythonScript($symbol, $startDate, $endDate);
            }
            
            return $priceData;
            
        } catch (\Exception $e) {
            error_log("Failed to get historical prices for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get market summary data (major indices)
     */
    public function getMarketSummary(): array
    {
        $indices = [
            '^GSPC' => 'S&P 500',
            '^DJI' => 'Dow Jones',
            '^IXIC' => 'NASDAQ'
        ];
        
        $marketData = [];
        
        foreach ($indices as $symbol => $name) {
            $priceData = $this->getCurrentPrice($symbol);
            if ($priceData) {
                $marketData[] = [
                    'name' => $name,
                    'symbol' => $symbol,
                    'value' => number_format($priceData['price'], 2),
                    'change' => $priceData['change'],
                    'change_percent' => $priceData['change_percent']
                ];
            }
        }
        
        return $marketData;
    }
    
    /**
     * Update stock prices from external sources
     */
    public function updatePricesFromExternalSources(array $symbols): array
    {
        $results = [];
        
        foreach ($symbols as $symbol) {
            try {
                // Try to fetch from Python trading script
                $updated = $this->fetchAndStorePriceData($symbol);
                $results[$symbol] = $updated;
            } catch (\Exception $e) {
                $results[$symbol] = false;
                error_log("Failed to update prices for {$symbol}: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Fetch price data using Python trading script
     */
    private function fetchFromPythonScript(string $symbol, ?string $startDate = null, ?string $endDate = null): array
    {
        // This would call the trading_script.py to fetch data
        // For now, return empty array as placeholder
        return [];
    }
    
    /**
     * Fetch and store price data for a symbol
     */
    private function fetchAndStorePriceData(string $symbol): bool
    {
        if (!$this->stockDataAccess) {
            return false;
        }
        
        try {
            // This would integrate with the Python data fetching
            // For now, return false as placeholder
            return false;
        } catch (\Exception $e) {
            error_log("Failed to fetch and store data for {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate day change
     */
    private function calculateDayChange(array $priceData): float
    {
        $current = (float)($priceData['close'] ?? 0);
        $previous = (float)($priceData['previous_close'] ?? $priceData['open'] ?? $current);
        
        return $current - $previous;
    }
    
    /**
     * Calculate day change percent
     */
    private function calculateDayChangePercent(array $priceData): float
    {
        $change = $this->calculateDayChange($priceData);
        $previous = (float)($priceData['previous_close'] ?? $priceData['open'] ?? 0);
        
        return $previous > 0 ? ($change / $previous) * 100 : 0;
    }
    
    /**
     * Get price data for portfolio calculations
     */
    public function getPricesForPortfolioCalculations(array $symbols): array
    {
        return $this->getCurrentPrices($symbols);
    }
    
    /**
     * Check if market data is available for symbol
     */
    public function hasDataForSymbol(string $symbol): bool
    {
        if (!$this->stockDataAccess) {
            return false;
        }
        
        try {
            $price = $this->stockDataAccess->getLatestPrice($symbol);
            return !empty($price);
        } catch (\Exception $e) {
            return false;
        }
    }
}