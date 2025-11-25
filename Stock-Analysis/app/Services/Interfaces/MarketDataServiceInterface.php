<?php

namespace App\Services\Interfaces;

/**
 * Market Data Service Interface
 * 
 * Defines contract for market data services providing real-time and historical price data.
 * Follows Interface Segregation Principle (ISP) by focusing only on market data concerns.
 */
interface MarketDataServiceInterface
{
    /**
     * Get current stock prices for multiple symbols
     */
    public function getCurrentPrices(array $symbols): array;
    
    /**
     * Get current price for a single symbol
     */
    public function getCurrentPrice(string $symbol): ?array;
    
    /**
     * Get historical price data for a symbol
     */
    public function getHistoricalPrices(string $symbol, ?string $startDate = null, ?string $endDate = null, ?int $limit = null): array;
    
    /**
     * Get market summary data (major indices)
     */
    public function getMarketSummary(): array;
    
    /**
     * Update stock prices from external sources
     */
    public function updatePricesFromExternalSources(array $symbols): array;
    
    /**
     * Get price data for portfolio calculations
     */
    public function getPricesForPortfolioCalculations(array $symbols): array;
    
    /**
     * Check if market data is available for symbol
     */
    public function hasDataForSymbol(string $symbol): bool;
}