<?php

declare(strict_types=1);

namespace App\Events\Trading;

/**
 * Trade Executed Event
 * 
 * Dispatched when a trade is executed.
 * 
 * @package App\Events\Trading
 */
class TradeExecutedEvent
{
    private string $symbol;
    private string $action;
    private float $quantity;
    private float $price;
    private float $timestamp;
    private array $metadata;
    
    public function __construct(
        string $symbol,
        string $action,
        float $quantity,
        float $price,
        array $metadata = []
    ) {
        $this->symbol = $symbol;
        $this->action = $action;
        $this->quantity = $quantity;
        $this->price = $price;
        $this->timestamp = microtime(true);
        $this->metadata = $metadata;
    }
    
    public function getSymbol(): string
    {
        return $this->symbol;
    }
    
    public function getAction(): string
    {
        return $this->action;
    }
    
    public function getQuantity(): float
    {
        return $this->quantity;
    }
    
    public function getPrice(): float
    {
        return $this->price;
    }
    
    public function getTimestamp(): float
    {
        return $this->timestamp;
    }
    
    public function getMetadata(): array
    {
        return $this->metadata;
    }
    
    public function getTotalValue(): float
    {
        return $this->quantity * $this->price;
    }
    
    public function toArray(): array
    {
        return [
            'symbol' => $this->symbol,
            'action' => $this->action,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'total_value' => $this->getTotalValue(),
            'timestamp' => $this->timestamp,
            'metadata' => $this->metadata
        ];
    }
}
