<?php

declare(strict_types=1);

namespace App\User;

/**
 * Represents a user's trading portfolio
 */
class Portfolio
{
    private int $id;
    private int $userId;
    private string $name;
    
    /** @var array<string, float> Symbol => Quantity */
    private array $holdings = [];
    
    private float $cashBalance;
    private int $createdAt;
    
    public function __construct(int $id, int $userId, string $name, float $initialBalance = 10000.0)
    {
        $this->id = $id;
        $this->userId = $userId;
        $this->name = $name;
        $this->cashBalance = $initialBalance;
        $this->createdAt = time();
    }
    
    public function getId(): int
    {
        return $this->id;
    }
    
    public function getUserId(): int
    {
        return $this->userId;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getCashBalance(): float
    {
        return $this->cashBalance;
    }
    
    public function getHoldings(): array
    {
        return $this->holdings;
    }
    
    public function getPosition(string $symbol): float
    {
        return $this->holdings[$symbol] ?? 0.0;
    }
    
    public function hasPosition(string $symbol): bool
    {
        return isset($this->holdings[$symbol]) && $this->holdings[$symbol] > 0;
    }
    
    public function addCash(float $amount): void
    {
        $this->cashBalance += $amount;
    }
    
    public function deductCash(float $amount): void
    {
        $this->cashBalance -= $amount;
    }
    
    public function addHolding(string $symbol, float $quantity): void
    {
        if (!isset($this->holdings[$symbol])) {
            $this->holdings[$symbol] = 0.0;
        }
        $this->holdings[$symbol] += $quantity;
        
        // Clean up zero positions
        if ($this->holdings[$symbol] <= 0) {
            unset($this->holdings[$symbol]);
        }
    }
    
    public function removeHolding(string $symbol, float $quantity): void
    {
        $this->addHolding($symbol, -$quantity);
    }
    
    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'name' => $this->name,
            'cash_balance' => $this->cashBalance,
            'holdings' => $this->holdings,
            'created_at' => $this->createdAt,
        ];
    }
}
