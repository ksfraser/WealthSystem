<?php
namespace Ksfraser\Finance\Backtesting\Interfaces;

/**
 * Backtesting Engine Interface
 * 
 * Contract for backtesting implementations
 */
interface BacktestingEngineInterface
{
    /**
     * Run backtest for a strategy
     * 
     * @param string $strategyClass Strategy class name
     * @param array $parameters Strategy parameters
     * @param array $marketData Historical market data
     * @param array $options Backtesting options (start_date, end_date, initial_capital, etc.)
     * @return array Backtest results
     */
    public function runBacktest(string $strategyClass, array $parameters, array $marketData, array $options = []): array;

    /**
     * Calculate performance metrics
     * 
     * @param array $trades Trade history
     * @param float $initialCapital Starting capital
     * @return array Performance metrics
     */
    public function calculateMetrics(array $trades, float $initialCapital): array;

    /**
     * Score strategy performance
     * 
     * @param array $backtestResults Results from runBacktest
     * @return array Strategy score breakdown
     */
    public function scoreStrategy(array $backtestResults): array;

    /**
     * Compare multiple strategies
     * 
     * @param array $strategies Array of strategy results
     * @return array Comparison results with rankings
     */
    public function compareStrategies(array $strategies): array;
}
