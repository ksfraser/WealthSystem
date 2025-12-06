<?php

declare(strict_types=1);

namespace App\Events\Trading;

/**
 * Alert Generated Event
 * 
 * Dispatched when a trading alert is generated.
 * 
 * @package App\Events\Trading
 */
class AlertGeneratedEvent
{
    private string $type;
    private string $symbol;
    private string $message;
    private string $severity;
    private array $data;
    private float $timestamp;
    
    public function __construct(
        string $type,
        string $symbol,
        string $message,
        string $severity = 'medium',
        array $data = []
    ) {
        $this->type = $type;
        $this->symbol = $symbol;
        $this->message = $message;
        $this->severity = $severity;
        $this->data = $data;
        $this->timestamp = microtime(true);
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    public function getSymbol(): string
    {
        return $this->symbol;
    }
    
    public function getMessage(): string
    {
        return $this->message;
    }
    
    public function getSeverity(): string
    {
        return $this->severity;
    }
    
    public function getData(): array
    {
        return $this->data;
    }
    
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
    
    public function isHighSeverity(): bool
    {
        return $this->severity === 'high';
    }
    
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'symbol' => $this->symbol,
            'message' => $this->message,
            'severity' => $this->severity,
            'data' => $this->data,
            'timestamp' => $this->timestamp
        ];
    }
}
