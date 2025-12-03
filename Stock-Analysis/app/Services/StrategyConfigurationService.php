<?php

namespace App\Services;

use App\Repositories\StrategyParametersRepositoryInterface;
use App\Services\Trading\TradingStrategyInterface;

/**
 * Strategy Configuration Service
 * 
 * Loads configurable parameters from database into strategy instances.
 * Ensures strategies use user-configured values instead of hardcoded defaults.
 * 
 * @package App\Services
 */
class StrategyConfigurationService
{
    private StrategyParametersRepositoryInterface $parametersRepository;

    public function __construct(StrategyParametersRepositoryInterface $parametersRepository)
    {
        $this->parametersRepository = $parametersRepository;
    }

    /**
     * Load and apply configuration to a strategy
     * 
     * @param TradingStrategyInterface $strategy Strategy instance to configure
     * @return TradingStrategyInterface Configured strategy
     */
    public function configureStrategy(TradingStrategyInterface $strategy): TradingStrategyInterface
    {
        $strategyName = $strategy->getName();
        $parameters = $this->parametersRepository->getStrategyParameters($strategyName);
        
        if (!empty($parameters)) {
            $strategy->setParameters($parameters);
        }
        
        return $strategy;
    }

    /**
     * Get current configuration for a strategy
     * 
     * @param string $strategyName Strategy name
     * @return array Current parameters
     */
    public function getConfiguration(string $strategyName): array
    {
        return $this->parametersRepository->getStrategyParameters($strategyName);
    }

    /**
     * Update configuration for a strategy
     * 
     * @param string $strategyName Strategy name
     * @param array $parameters New parameter values
     * @return bool Success status
     */
    public function updateConfiguration(string $strategyName, array $parameters): bool
    {
        return $this->parametersRepository->setStrategyParameters($strategyName, $parameters);
    }

    /**
     * Get all available strategies
     * 
     * @return array List of strategy names
     */
    public function getAvailableStrategies(): array
    {
        return $this->parametersRepository->getAvailableStrategies();
    }

    /**
     * Get parameters with full metadata for UI rendering
     * 
     * @param string $strategyName Strategy name
     * @return array Parameters with metadata
     */
    public function getConfigurationMetadata(string $strategyName): array
    {
        return $this->parametersRepository->getParametersWithMetadata($strategyName);
    }

    /**
     * Get parameters grouped by category for organized UI display
     * 
     * @param string $strategyName Strategy name
     * @return array Parameters grouped by category
     */
    public function getConfigurationByCategory(string $strategyName): array
    {
        return $this->parametersRepository->getParametersByCategory($strategyName);
    }

    /**
     * Validate parameter value against constraints
     * 
     * @param array $metadata Parameter metadata
     * @param mixed $value Proposed value
     * @return array ['valid' => bool, 'message' => string]
     */
    public function validateParameter(array $metadata, $value): array
    {
        $type = $metadata['parameter_type'];
        $min = $metadata['min_value'] ?? null;
        $max = $metadata['max_value'] ?? null;

        // Type validation
        switch ($type) {
            case 'int':
            case 'integer':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be an integer'];
                }
                $value = (int) $value;
                break;
            
            case 'float':
            case 'double':
                if (!is_numeric($value)) {
                    return ['valid' => false, 'message' => 'Value must be a number'];
                }
                $value = (float) $value;
                break;
            
            case 'bool':
            case 'boolean':
                if (!in_array($value, [0, 1, '0', '1', true, false], true)) {
                    return ['valid' => false, 'message' => 'Value must be true/false'];
                }
                break;
        }

        // Range validation
        if ($min !== null && $value < $min) {
            return ['valid' => false, 'message' => "Value must be at least {$min}"];
        }

        if ($max !== null && $value > $max) {
            return ['valid' => false, 'message' => "Value must be at most {$max}"];
        }

        return ['valid' => true, 'message' => 'Valid'];
    }

    /**
     * Export strategy configuration to JSON
     * 
     * @param string $strategyName Strategy name
     * @return string JSON representation
     */
    public function exportToJson(string $strategyName): string
    {
        $config = $this->parametersRepository->exportParameters($strategyName);
        return json_encode($config, JSON_PRETTY_PRINT);
    }

    /**
     * Import strategy configuration from JSON
     * 
     * @param string $strategyName Strategy name
     * @param string $json JSON configuration
     * @return bool Success status
     */
    public function importFromJson(string $strategyName, string $json): bool
    {
        $config = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return $this->parametersRepository->importParameters($strategyName, $config);
    }
}
