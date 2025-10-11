<?php

namespace App\Services\Interfaces;

/**
 * Portfolio Service Interface
 * 
 * Defines portfolio management and calculation operations.
 * Handles performance metrics and portfolio analytics.
 */
interface PortfolioServiceInterface
{
    /**
     * Get dashboard data for user
     */
    public function getDashboardData(int $userId): array;
    
    /**
     * Calculate portfolio performance
     */
    public function calculatePerformance(int $userId): array;
    
    /**
     * Get current portfolio value
     */
    public function getCurrentValue(int $userId): float;
    
    /**
     * Get portfolio positions
     */
    public function getPositions(int $userId): array;
    
    /**
     * Update portfolio with new transaction
     */
    public function addTransaction(int $userId, array $transactionData): bool;
    
    /**
     * Get performance charts data
     */
    public function getChartsData(int $userId, ?string $period = null): array;
    
    /**
     * Calculate portfolio metrics (Sharpe ratio, volatility, etc.)
     */
    public function calculateMetrics(int $userId): array;
    
    /**
     * Get portfolio summary
     */
    public function getSummary(int $userId): array;
}