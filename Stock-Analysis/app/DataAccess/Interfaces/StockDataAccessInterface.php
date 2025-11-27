<?php

namespace App\DataAccess\Interfaces;

/**
 * Stock Data Access Interface
 * 
 * Abstraction for accessing stock price data from various sources
 * (database, CSV files, APIs, etc.)
 */
interface StockDataAccessInterface
{
    /**
     * Get the latest price data for a symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @return array|null Price data array or null if not found
     */
    public function getLatestPrice(string $symbol): ?array;
    
    /**
     * Get historical price data for a symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @param string|null $startDate Start date (Y-m-d format)
     * @param string|null $endDate End date (Y-m-d format)
     * @param int|null $limit Maximum number of records
     * @return array Array of price data
     */
    public function getPriceData(
        string $symbol,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $limit = null
    ): array;
}
