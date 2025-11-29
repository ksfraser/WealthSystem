<?php

namespace App\Services\Trading;

/**
 * Trading Strategy Interface
 * 
 * Defines the contract that all trading strategies must implement.
 * Each strategy analyzes market conditions and generates trading signals.
 * 
 * Design Pattern: Strategy Pattern
 * Purpose: Allows different trading algorithms to be used interchangeably
 * 
 * @package App\Services\Trading
 */
interface TradingStrategyInterface
{
    /**
     * Get the name of the strategy
     * 
     * @return string Strategy name (e.g., "Turtle Trading System", "MA Crossover")
     */
    public function getName(): string;

    /**
     * Get a description of how the strategy works
     * 
     * @return string Strategy description
     */
    public function getDescription(): string;

    /**
     * Analyze a stock symbol and generate a trading signal
     * 
     * @param string $symbol Stock symbol (e.g., "AAPL", "TSLA")
     * @param string $date Date in Y-m-d format (for backtesting) or 'today'
     * @return array Trading signal with structure:
     *   [
     *     'signal' => 'BUY'|'SELL'|'SHORT'|'COVER'|'HOLD',
     *     'confidence' => float (0.0 to 1.0),
     *     'reason' => string (explanation of signal),
     *     'entry_price' => float|null,
     *     'stop_loss' => float|null,
     *     'take_profit' => float|null,
     *     'position_size' => float|null (as percentage of portfolio),
     *     'metadata' => array (strategy-specific data)
     *   ]
     */
    public function analyze(string $symbol, string $date = 'today'): array;

    /**
     * Get the parameters/configuration for this strategy
     * 
     * @return array Strategy parameters (e.g., moving average periods, ATR multiplier)
     */
    public function getParameters(): array;

    /**
     * Set/update strategy parameters
     * 
     * @param array $parameters New parameters to apply
     * @return void
     */
    public function setParameters(array $parameters): void;

    /**
     * Validate if the strategy can be executed with current market data
     * 
     * @param string $symbol Stock symbol
     * @return bool True if strategy can be executed, false otherwise
     */
    public function canExecute(string $symbol): bool;

    /**
     * Get the minimum number of historical data points required
     * 
     * @return int Number of days of historical data needed
     */
    public function getRequiredHistoricalDays(): int;
}
