<?php

namespace App\DataAccess\Interfaces;

/**
 * Portfolio Data Source Interface
 * 
 * Abstraction for accessing portfolio data from various sources
 * (DAOs, databases, CSV files, etc.)
 */
interface PortfolioDataSourceInterface
{
    /**
     * Read portfolio data
     * 
     * @param int|null $userId User ID (optional for some implementations)
     * @return array Array of portfolio rows/holdings
     */
    public function readPortfolio(?int $userId = null): array;
    
    /**
     * Write/update portfolio data
     * 
     * @param array $portfolioData Portfolio data to write
     * @param int|null $userId User ID (optional)
     * @return bool Success status
     */
    public function writePortfolio(array $portfolioData, ?int $userId = null): bool;
    
    /**
     * Check if data source is available/initialized
     * 
     * @return bool True if available, false otherwise
     */
    public function isAvailable(): bool;
}
