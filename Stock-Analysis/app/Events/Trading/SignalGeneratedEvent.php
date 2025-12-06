<?php

declare(strict_types=1);

namespace App\Events\Trading;

/**
 * Signal Generated Event
 * 
 * Dispatched when a trading strategy generates a signal.
 * 
 * @package App\Events\Trading
 */
class SignalGeneratedEvent
{
    private string $strategy;
    private string $symbol;
    private array $signal;
    private float $timestamp;
    
    public function __construct(
        string $strategy,
        string $symbol,
        array $signal
    ) {
        $this->strategy = $strategy;
        $this->symbol = $symbol;
        $this->signal = $signal;
        $this->timestamp = microtime(true);
    }
    
    public function getStrategy(): string
    {
        return $this->strategy;
    }
    
    public function getSymbol(): string
    {
        return $this->symbol;
    }
    
    public function getSignal(): array
    {
        return $this->signal;
    }
    
    public function getAction(): string
    {
        return $this->signal['action'] ?? 'NONE';
    }
    
    public function getConfidence(): float
    {
        return $this->signal['confidence'] ?? 0.0;
    }
    
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
    
    public function isActionable(): bool
    {
        return $this->getAction() !== 'NONE' && $this->getConfidence() > 0;
    }
    
    public function toArray(): array
    {
        return [
            'strategy' => $this->strategy,
            'symbol' => $this->symbol,
            'signal' => $this->signal,
            'timestamp' => $this->timestamp
        ];
    }
}
