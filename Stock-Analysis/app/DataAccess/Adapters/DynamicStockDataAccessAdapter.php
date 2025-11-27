<?php

namespace App\DataAccess\Adapters;

use App\DataAccess\Interfaces\StockDataAccessInterface;

// Include existing class
require_once __DIR__ . '/../../../DynamicStockDataAccess.php';

/**
 * Adapter for DynamicStockDataAccess
 * 
 * Wraps the existing DynamicStockDataAccess class to implement StockDataAccessInterface.
 * This allows dependency injection while maintaining compatibility with legacy code.
 */
class DynamicStockDataAccessAdapter implements StockDataAccessInterface
{
    private ?\DynamicStockDataAccess $stockDataAccess;
    
    /**
     * Constructor
     * 
     * @param \DynamicStockDataAccess|null $stockDataAccess Optional instance (for testing)
     */
    public function __construct(?\DynamicStockDataAccess $stockDataAccess = null)
    {
        if ($stockDataAccess !== null) {
            $this->stockDataAccess = $stockDataAccess;
        } else {
            try {
                $this->stockDataAccess = new \DynamicStockDataAccess();
            } catch (\Exception $e) {
                // If initialization fails, stockDataAccess will be null
                // Service methods will handle gracefully
                $this->stockDataAccess = null;
                error_log('DynamicStockDataAccess initialization failed: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getLatestPrice(string $symbol): ?array
    {
        if ($this->stockDataAccess === null) {
            return null;
        }
        
        try {
            return $this->stockDataAccess->getLatestPrice($symbol);
        } catch (\Exception $e) {
            error_log("Failed to get latest price for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPriceData(
        string $symbol,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $limit = null
    ): array {
        if ($this->stockDataAccess === null) {
            return [];
        }
        
        try {
            return $this->stockDataAccess->getPriceData($symbol, $startDate, $endDate, $limit);
        } catch (\Exception $e) {
            error_log("Failed to get price data for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if the underlying data access is available
     * 
     * @return bool True if available, false otherwise
     */
    public function isAvailable(): bool
    {
        return $this->stockDataAccess !== null;
    }
}
