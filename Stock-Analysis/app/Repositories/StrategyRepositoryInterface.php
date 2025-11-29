<?php

namespace App\Repositories;

/**
 * Strategy Repository Interface
 * 
 * Provides persistence and retrieval capabilities for trading strategy
 * executions, signals, backtests, and performance metrics.
 */
interface StrategyRepositoryInterface
{
    /**
     * Store a strategy execution (signal generation)
     * 
     * @param string $strategyName Strategy name
     * @param string $symbol Stock symbol
     * @param array $signal Signal data from analyze()
     * @param string $timestamp Execution timestamp
     * @return string Execution ID
     */
    public function storeExecution(string $strategyName, string $symbol, array $signal, string $timestamp): string;

    /**
     * Get strategy executions for a symbol
     * 
     * @param string $symbol Stock symbol
     * @param string|null $strategyName Optional strategy filter
     * @param int $limit Maximum results
     * @return array Array of executions
     */
    public function getExecutions(string $symbol, ?string $strategyName = null, int $limit = 100): array;

    /**
     * Get recent executions across all symbols
     * 
     * @param string|null $strategyName Optional strategy filter
     * @param int $limit Maximum results
     * @return array Array of executions
     */
    public function getRecentExecutions(?string $strategyName = null, int $limit = 50): array;

    /**
     * Store backtest results
     * 
     * @param string $strategyName Strategy name
     * @param array $config Backtest configuration
     * @param array $results Results including trades, metrics
     * @param string $timestamp Backtest timestamp
     * @return string Backtest ID
     */
    public function storeBacktest(string $strategyName, array $config, array $results, string $timestamp): string;

    /**
     * Get backtest results
     * 
     * @param string $backtestId Backtest ID
     * @return array|null Backtest data or null
     */
    public function getBacktest(string $backtestId): ?array;

    /**
     * Get all backtests for a strategy
     * 
     * @param string $strategyName Strategy name
     * @param int $limit Maximum results
     * @return array Array of backtests
     */
    public function getBacktestsByStrategy(string $strategyName, int $limit = 20): array;

    /**
     * Store strategy performance metrics
     * 
     * @param string $strategyName Strategy name
     * @param array $metrics Performance metrics
     * @param string $period Period identifier (e.g., '2024-Q1')
     * @return bool Success
     */
    public function storePerformanceMetrics(string $strategyName, array $metrics, string $period): bool;

    /**
     * Get strategy performance metrics
     * 
     * @param string $strategyName Strategy name
     * @param string|null $period Optional period filter
     * @return array Performance metrics
     */
    public function getPerformanceMetrics(string $strategyName, ?string $period = null): array;

    /**
     * Get strategy statistics summary
     * 
     * @param string $strategyName Strategy name
     * @return array Statistics (total signals, win rate, etc.)
     */
    public function getStrategyStatistics(string $strategyName): array;

    /**
     * Delete old executions
     * 
     * @param int $daysToKeep Keep executions from last N days
     * @return int Number of executions deleted
     */
    public function deleteOldExecutions(int $daysToKeep = 90): int;

    /**
     * Get all available strategies (from stored executions)
     * 
     * @return array List of strategy names
     */
    public function getAvailableStrategies(): array;
}
