<?php
/**
 * Data Source Interface for Stock Market Data
 * 
 * Defines the contract for fetching stock price data from various sources.
 * Follows the Interface Segregation Principle by keeping the interface minimal.
 */

namespace Ksfraser\Finance\Interfaces;

interface DataSourceInterface
{
    /**
     * Fetch stock price data for a single symbol
     * 
     * @param string $symbol Stock symbol (e.g., 'AAPL', 'GOOGL')
     * @return array|null Normalized stock data or null if unavailable
     */
    public function fetchStockPrice(string $symbol): ?array;
    
    /**
     * Fetch stock price data for multiple symbols
     * 
     * @param array $symbols Array of stock symbols
     * @return array Associative array of symbol => data
     */
    public function fetchMultipleStockPrices(array $symbols): array;
    
    /**
     * Check if the data source is available
     * 
     * @return bool True if the source can be used
     */
    public function isAvailable(): bool;
    
    /**
     * Get the name of the data source
     * 
     * @return string Human-readable name
     */
    public function getName(): string;
}
