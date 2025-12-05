<?php

namespace App\DAO;

/**
 * Sector Analysis Data Access Object Interface
 * 
 * Defines contract for sector analysis data access operations.
 * Implementations should handle database queries for sector-related data.
 * 
 * Design Principles:
 * - Interface Segregation Principle (ISP): Focused interface
 * - Dependency Inversion Principle (DIP): Depend on abstractions
 * - Single Responsibility: Data access only
 * 
 * @package App\DAO
 * @version 1.0.0
 */
interface SectorAnalysisDAO
{
    /**
     * Get portfolio sector data for a user
     * 
     * @param int $userId User ID
     * @return array{
     *   symbol: string,
     *   sector: string,
     *   value: float,
     *   shares: int
     * }[]
     */
    public function getPortfolioSectorData(int $userId): array;
    
    /**
     * Get S&P 500 sector weights (benchmark)
     * 
     * @return array<string, float> Sector name => Weight percentage
     */
    public function getSP500SectorWeights(): array;
    
    /**
     * Get sector data for specific symbols
     * 
     * @param string[] $symbols Stock symbols
     * @return array<string, string> Symbol => Sector name
     */
    public function getSectorsBySymbols(array $symbols): array;
    
    /**
     * Get historical sector weights for user portfolio
     * 
     * Returns sector allocation percentages over time.
     * 
     * @param int $userId User ID
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array<string, array<string, float>> Date => [Sector => Weight%]
     */
    public function getHistoricalSectorWeights(int $userId, string $startDate, string $endDate): array;
    
    /**
     * Get sector breakdown with values and percentages
     * 
     * Returns aggregated sector data for portfolio export.
     * 
     * @param int $userId User ID
     * @return array{
     *   sector: string,
     *   value: float,
     *   percentage: float
     * }[]
     */
    public function getSectorBreakdown(int $userId): array;
}
