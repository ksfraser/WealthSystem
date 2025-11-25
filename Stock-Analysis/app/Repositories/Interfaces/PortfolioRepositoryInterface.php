<?php

namespace App\Repositories\Interfaces;

use App\Core\Interfaces\RepositoryInterface;

/**
 * Portfolio Repository Interface
 * 
 * Defines contract for portfolio data access operations.
 * Supports portfolio performance tracking and statistics.
 */
interface PortfolioRepositoryInterface extends RepositoryInterface
{
    /**
     * Get user's current portfolio
     */
    public function getCurrentPortfolio(int $userId): ?object;
    
    /**
     * Get portfolio performance history
     */
    public function getPerformanceHistory(int $userId, ?string $startDate = null, ?string $endDate = null): array;
    
    /**
     * Get portfolio statistics
     */
    public function getStatistics(int $userId): array;
    
    /**
     * Update portfolio value
     */
    public function updateValue(int $portfolioId, float $value): bool;
    
    /**
     * Get current positions
     */
    public function getCurrentPositions(int $userId): array;
    
    /**
     * Calculate portfolio metrics
     */
    public function calculateMetrics(int $userId): array;
}