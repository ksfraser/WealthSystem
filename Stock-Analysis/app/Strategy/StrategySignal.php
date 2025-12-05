<?php

declare(strict_types=1);

namespace App\Strategy;

/**
 * Represents a trading strategy signal
 * 
 * Value object containing the result of a strategy analysis including
 * the recommended action (BUY/SELL/HOLD), confidence strength, and reasoning.
 * 
 * @package App\Strategy
 */
class StrategySignal
{
    private string $action;
    private float $strength;
    private string $reason;
    private string $symbol;
    private array $metadata;

    /**
     * Create a new strategy signal
     *
     * @param string $symbol Stock symbol
     * @param string $action Recommended action: BUY, SELL, or HOLD
     * @param float $strength Signal strength (0.0 to 1.0)
     * @param string $reason Human-readable explanation
     * @param array<string, mixed> $metadata Additional context data
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function __construct(
        string $symbol,
        string $action,
        float $strength,
        string $reason,
        array $metadata = []
    ) {
        if (!in_array($action, ['BUY', 'SELL', 'HOLD'], true)) {
            throw new \InvalidArgumentException("Invalid action: {$action}");
        }

        if ($strength < 0.0 || $strength > 1.0) {
            throw new \InvalidArgumentException("Strength must be between 0.0 and 1.0");
        }

        if (empty($symbol)) {
            throw new \InvalidArgumentException("Symbol cannot be empty");
        }

        $this->symbol = $symbol;
        $this->action = $action;
        $this->strength = $strength;
        $this->reason = $reason;
        $this->metadata = $metadata;
    }

    /**
     * Get the stock symbol
     *
     * @return string
     */
    public function getSymbol(): string
    {
        return $this->symbol;
    }

    /**
     * Get the recommended action
     *
     * @return string BUY, SELL, or HOLD
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Get the signal strength
     *
     * @return float Value between 0.0 (weak) and 1.0 (strong)
     */
    public function getStrength(): float
    {
        return $this->strength;
    }

    /**
     * Get the reasoning for this signal
     *
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get additional metadata
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if this is a buy signal
     *
     * @return bool
     */
    public function isBuy(): bool
    {
        return $this->action === 'BUY';
    }

    /**
     * Check if this is a sell signal
     *
     * @return bool
     */
    public function isSell(): bool
    {
        return $this->action === 'SELL';
    }

    /**
     * Check if this is a hold signal
     *
     * @return bool
     */
    public function isHold(): bool
    {
        return $this->action === 'HOLD';
    }

    /**
     * Convert signal to array representation
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'action' => $this->action,
            'strength' => $this->strength,
            'reason' => $this->reason,
            'metadata' => $this->metadata
        ];
    }
}
