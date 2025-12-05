<?php

declare(strict_types=1);

namespace App\Alert;

use InvalidArgumentException;

/**
 * Alert Condition Evaluator
 * 
 * Represents a single alert condition that can be evaluated against market data.
 * Supports various condition types including price thresholds, percentage changes,
 * and volume conditions.
 * 
 * @package App\Alert
 */
class AlertCondition
{
    private string $type;
    private float $value;

    /**
     * Create new alert condition
     *
     * @param string $type Condition type
     * @param float $value Threshold value
     * @throws InvalidArgumentException If type is invalid
     */
    public function __construct(string $type, float $value)
    {
        $validTypes = [
            'price_above',
            'price_below',
            'percent_change',
            'volume_above',
            'volume_below'
        ];

        if (!in_array($type, $validTypes, true)) {
            throw new InvalidArgumentException("Invalid condition type: {$type}");
        }

        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Evaluate condition against market data
     *
     * @param array<string, mixed> $data Market data
     * @return bool True if condition is met
     */
    public function evaluate(array $data): bool
    {
        return match ($this->type) {
            'price_above' => isset($data['price']) && $data['price'] > $this->value,
            'price_below' => isset($data['price']) && $data['price'] < $this->value,
            'volume_above' => isset($data['volume']) && $data['volume'] > $this->value,
            'volume_below' => isset($data['volume']) && $data['volume'] < $this->value,
            'percent_change' => $this->evaluatePercentChange($data),
            default => false
        };
    }

    /**
     * Get condition type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get condition value
     *
     * @return float
     */
    public function getValue(): float
    {
        return $this->value;
    }

    /**
     * Evaluate percentage change condition
     *
     * @param array<string, mixed> $data Market data
     * @return bool
     */
    private function evaluatePercentChange(array $data): bool
    {
        if (!isset($data['previous_price']) || !isset($data['current_price'])) {
            return false;
        }

        $previous = $data['previous_price'];
        $current = $data['current_price'];

        if ($previous == 0) {
            return false;
        }

        $percentChange = (($current - $previous) / $previous) * 100;

        return abs($percentChange) >= $this->value;
    }
}
