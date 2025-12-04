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
}
