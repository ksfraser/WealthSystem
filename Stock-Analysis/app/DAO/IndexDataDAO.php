<?php

namespace App\DAO;

/**
 * Index Data Access Object Interface
 * 
 * Defines contract for accessing market index data.
 * Implementations should handle database or API queries for index prices.
 * 
 * Design Principles:
 * - ISP: Focused interface for index data only
 * - DIP: Depend on abstractions not concretions
 * - SRP: Data access responsibility only
 * 
 * @package App\DAO
 * @version 1.0.0
 */
interface IndexDataDAO
{
    /**
     * Get historical index data for specified period
     * 
     * @param string $indexSymbol Index symbol (SPX, IXIC, DJI, etc.)
     * @param string $period Time period (1M, 3M, 6M, 1Y, 3Y, 5Y)
     * @return array{date: string, close: float, volume: int}[]
     * @throws \InvalidArgumentException If invalid symbol or period
     */
    public function getIndexData(string $indexSymbol, string $period): array;
    
    /**
     * Get current index value
     * 
     * @param string $indexSymbol Index symbol
     * @return float Current index value
     * @throws \InvalidArgumentException If invalid symbol
     */
    public function getCurrentIndexValue(string $indexSymbol): float;
    
    /**
     * Get supported index symbols
     * 
     * @return array<string, string> Symbol => Name mapping
     */
    public function getSupportedIndexes(): array;
}
