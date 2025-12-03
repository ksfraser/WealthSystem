<?php

namespace App\Repositories;

/**
 * Strategy Parameters Repository Interface
 * 
 * Manages storage and retrieval of configurable strategy parameters.
 * Provides methods to load, save, and manage parameters for all trading strategies.
 * 
 * @package App\Repositories
 */
interface StrategyParametersRepositoryInterface
{
    /**
     * Get all parameters for a specific strategy
     * 
     * @param string $strategyName Strategy name (e.g., "Warren Buffett Value Strategy")
     * @return array Associative array of parameter_key => parameter_value
     */
    public function getStrategyParameters(string $strategyName): array;

    /**
     * Get a single parameter value for a strategy
     * 
     * @param string $strategyName Strategy name
     * @param string $parameterKey Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value
     */
    public function getParameter(string $strategyName, string $parameterKey, $default = null);

    /**
     * Set/update a single parameter for a strategy
     * 
     * @param string $strategyName Strategy name
     * @param string $parameterKey Parameter key
     * @param mixed $value New parameter value
     * @return bool Success status
     */
    public function setParameter(string $strategyName, string $parameterKey, $value): bool;

    /**
     * Set/update multiple parameters for a strategy
     * 
     * @param string $strategyName Strategy name
     * @param array $parameters Associative array of parameter_key => value
     * @return bool Success status
     */
    public function setStrategyParameters(string $strategyName, array $parameters): bool;

    /**
     * Get all parameters with metadata (for UI display)
     * 
     * @param string $strategyName Strategy name
     * @return array Array of parameter records with metadata
     */
    public function getParametersWithMetadata(string $strategyName): array;

    /**
     * Get all available strategies that have configured parameters
     * 
     * @return array List of strategy names
     */
    public function getAvailableStrategies(): array;

    /**
     * Reset parameters to defaults for a strategy
     * 
     * @param string $strategyName Strategy name
     * @return bool Success status
     */
    public function resetToDefaults(string $strategyName): bool;

    /**
     * Get parameters grouped by category
     * 
     * @param string $strategyName Strategy name
     * @return array Parameters grouped by category
     */
    public function getParametersByCategory(string $strategyName): array;

    /**
     * Export strategy parameters to array (for backup/sharing)
     * 
     * @param string $strategyName Strategy name
     * @return array Complete parameter configuration
     */
    public function exportParameters(string $strategyName): array;

    /**
     * Import strategy parameters from array
     * 
     * @param string $strategyName Strategy name
     * @param array $parameters Complete parameter configuration
     * @return bool Success status
     */
    public function importParameters(string $strategyName, array $parameters): bool;
}
