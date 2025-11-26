<?php
namespace Ksfraser\Finance\Interfaces;

/**
 * Trading Strategy Interface
 * 
 * Defines the contract for all trading strategies in the Finance system.
 * Follows the Interface Segregation Principle by providing a focused interface.
 */
interface TradingStrategyInterface
{
    /**
     * Generate trading signal for a given symbol
     * 
     * @param string $symbol Stock symbol to analyze
     * @param array|null $marketData Optional pre-loaded market data
     * @return array|null Trading signal with action, price, confidence, etc. or null if no signal
     */
    public function generateSignal(string $symbol, ?array $marketData = null): ?array;

    /**
     * Get strategy name
     * 
     * @return string Human-readable strategy name
     */
    public function getName(): string;

    /**
     * Get strategy description
     * 
     * @return string Detailed description of the strategy
     */
    public function getDescription(): string;

    /**
     * Get current strategy parameters
     * 
     * @return array Current parameter configuration
     */
    public function getParameters(): array;

    /**
     * Set strategy parameters
     * 
     * @param array $parameters New parameter values
     */
    public function setParameters(array $parameters): void;

    /**
     * Validate parameter values
     * 
     * @param array $parameters Parameters to validate
     * @return bool True if parameters are valid
     */
    public function validateParameters(array $parameters): bool;
}
