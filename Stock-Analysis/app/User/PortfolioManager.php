<?php

declare(strict_types=1);

namespace App\User;

use App\Exceptions\DataException;

/**
 * Manages user portfolios
 */
class PortfolioManager
{
    /** @var array<int, Portfolio> */
    private array $portfolios = [];
    
    private int $nextId = 1;
    
    /**
     * Create a new portfolio for a user
     *
     * @param int $userId
     * @param string $name
     * @param float $initialBalance
     * @return Portfolio
     */
    public function createPortfolio(int $userId, string $name, float $initialBalance = 10000.0): Portfolio
    {
        $portfolio = new Portfolio($this->nextId++, $userId, $name, $initialBalance);
        $this->portfolios[$portfolio->getId()] = $portfolio;
        
        return $portfolio;
    }
    
    /**
     * Find portfolio by ID
     *
     * @param int $id
     * @return Portfolio|null
     */
    public function findById(int $id): ?Portfolio
    {
        return $this->portfolios[$id] ?? null;
    }
    
    /**
     * Get all portfolios for a user
     *
     * @param int $userId
     * @return array<Portfolio>
     */
    public function getByUserId(int $userId): array
    {
        return array_filter($this->portfolios, fn($p) => $p->getUserId() === $userId);
    }
    
    /**
     * Get all portfolios
     *
     * @return array<Portfolio>
     */
    public function getAll(): array
    {
        return array_values($this->portfolios);
    }
    
    /**
     * Execute a buy trade
     *
     * @param int $portfolioId
     * @param string $symbol
     * @param float $quantity
     * @param float $price
     * @return void
     * @throws DataException
     */
    public function executeBuy(int $portfolioId, string $symbol, float $quantity, float $price): void
    {
        $portfolio = $this->findById($portfolioId);
        
        if ($portfolio === null) {
            throw new DataException("Portfolio with ID {$portfolioId} not found");
        }
        
        $totalCost = $quantity * $price;
        
        if ($portfolio->getCashBalance() < $totalCost) {
            throw new DataException('Insufficient cash balance');
        }
        
        $portfolio->deductCash($totalCost);
        $portfolio->addHolding($symbol, $quantity);
    }
    
    /**
     * Execute a sell trade
     *
     * @param int $portfolioId
     * @param string $symbol
     * @param float $quantity
     * @param float $price
     * @return void
     * @throws DataException
     */
    public function executeSell(int $portfolioId, string $symbol, float $quantity, float $price): void
    {
        $portfolio = $this->findById($portfolioId);
        
        if ($portfolio === null) {
            throw new DataException("Portfolio with ID {$portfolioId} not found");
        }
        
        if ($portfolio->getPosition($symbol) < $quantity) {
            throw new DataException("Insufficient position in {$symbol}");
        }
        
        $totalProceeds = $quantity * $price;
        
        $portfolio->addCash($totalProceeds);
        $portfolio->removeHolding($symbol, $quantity);
    }
}
