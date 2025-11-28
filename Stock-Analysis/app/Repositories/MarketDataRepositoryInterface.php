<?php

namespace App\Repositories;

/**
 * Market Data Repository Interface
 * 
 * Handles persistence and retrieval of market data (fundamentals, etc.)
 * Follows Repository Pattern to abstract data storage.
 */
interface MarketDataRepositoryInterface
{
    /**
     * Store fundamental data for a symbol
     * 
     * @param string $symbol Stock ticker symbol
     * @param array $fundamentals Fundamental data
     * @return bool Success status
     */
    public function storeFundamentals(string $symbol, array $fundamentals): bool;
    
    /**
     * Retrieve fundamental data
     * 
     * @param string $symbol Stock ticker symbol
     * @param int|null $maxAge Maximum age in seconds (null = any age)
     * @return array|null Fundamental data or null if not found/expired
     */
    public function getFundamentals(string $symbol, ?int $maxAge = 86400): ?array;
    
    /**
     * Store historical price data
     * 
     * @param string $symbol Stock ticker symbol
     * @param array $priceData Array of OHLCV data
     * @return bool Success status
     */
    public function storePriceHistory(string $symbol, array $priceData): bool;
    
    /**
     * Retrieve historical price data
     * 
     * @param string $symbol Stock ticker symbol
     * @param string|null $startDate Start date (Y-m-d format)
     * @param string|null $endDate End date (Y-m-d format)
     * @return array Array of price data
     */
    public function getPriceHistory(string $symbol, ?string $startDate = null, ?string $endDate = null): array;
    
    /**
     * Store current price snapshot
     * 
     * @param string $symbol Stock ticker symbol
     * @param array $priceData Current price data
     * @return bool Success status
     */
    public function storeCurrentPrice(string $symbol, array $priceData): bool;
    
    /**
     * Retrieve current price
     * 
     * @param string $symbol Stock ticker symbol
     * @param int|null $maxAge Maximum age in seconds (null = any age)
     * @return array|null Price data or null if not found/expired
     */
    public function getCurrentPrice(string $symbol, ?int $maxAge = 60): ?array;
    
    /**
     * Get symbols that need data refresh
     * 
     * @param int $maxAge Age threshold in seconds
     * @param int $limit Maximum number of symbols to return
     * @return array Array of symbols needing refresh
     */
    public function getStaleSymbols(int $maxAge = 3600, int $limit = 100): array;
}
