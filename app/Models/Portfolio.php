<?php

namespace App\Models;

use App\Core\Interfaces\ModelInterface;

/**
 * Portfolio Model
 * 
 * Represents portfolio holding entity compatible with existing DAO structure.
 * Works with PortfolioDAO, UserPortfolioDAO, and existing portfolio management.
 */
class Portfolio extends BaseModel implements ModelInterface
{
    protected array $validationRules = [
        'symbol' => ['required', 'max:10'],
        'shares' => ['required', 'numeric'],
        'market_value' => ['required', 'numeric'],
        'date' => ['required']
    ];
    
    /**
     * Initialize portfolio with default values
     */
    public function __construct(array $data = [])
    {
        // Set default attributes for portfolio
        $this->attributes = [
            'symbol' => '',
            'shares' => 0.0,
            'market_value' => 0.0,
            'book_cost' => 0.0,
            'gain_loss' => 0.0,
            'gain_loss_percent' => 0.0,
            'current_price' => 0.0,
            'date' => date('Y-m-d'),
            'user_id' => null,
            'position_size' => 0.0,
            'avg_cost' => 0.0,
            'unrealized_pnl' => 0.0,
            'realized_pnl' => 0.0
        ];
        
        parent::__construct($data);
    }
    
    /**
     * Get stock symbol
     */
    public function getSymbol(): string
    {
        return $this->attributes['symbol'] ?? '';
    }
    
    /**
     * Get number of shares
     */
    public function getShares(): float
    {
        return (float) ($this->attributes['shares'] ?? 0);
    }
    
    /**
     * Get market value
     */
    public function getMarketValue(): float
    {
        return (float) ($this->attributes['market_value'] ?? 0);
    }
    
    /**
     * Get book cost
     */
    public function getBookCost(): float
    {
        return (float) ($this->attributes['book_cost'] ?? 0);
    }
    
    /**
     * Get current price per share
     */
    public function getCurrentPrice(): float
    {
        return (float) ($this->attributes['current_price'] ?? 0);
    }
    
    /**
     * Get gain/loss amount
     */
    public function getGainLoss(): float
    {
        return (float) ($this->attributes['gain_loss'] ?? 0);
    }
    
    /**
     * Get gain/loss percentage
     */
    public function getGainLossPercent(): float
    {
        return (float) ($this->attributes['gain_loss_percent'] ?? 0);
    }
    
    /**
     * Get position date
     */
    public function getDate(): string
    {
        return $this->attributes['date'] ?? date('Y-m-d');
    }
    
    /**
     * Get user ID (for user-specific portfolios)
     */
    public function getUserId(): ?int
    {
        return $this->attributes['user_id'] ?? null;
    }
    
    /**
     * Set user ID
     */
    public function setUserId(int $userId): void
    {
        $this->attributes['user_id'] = $userId;
    }
    
    /**
     * Calculate gain/loss from current values
     */
    public function calculateGainLoss(): void
    {
        $marketValue = $this->getMarketValue();
        $bookCost = $this->getBookCost();
        
        $this->attributes['gain_loss'] = $marketValue - $bookCost;
        
        if ($bookCost > 0) {
            $this->attributes['gain_loss_percent'] = (($marketValue - $bookCost) / $bookCost) * 100;
        } else {
            $this->attributes['gain_loss_percent'] = 0;
        }
    }
    
    /**
     * Update market value based on current price and shares
     */
    public function updateMarketValue(): void
    {
        $shares = $this->getShares();
        $currentPrice = $this->getCurrentPrice();
        
        $this->attributes['market_value'] = $shares * $currentPrice;
        
        // Recalculate gain/loss
        $this->calculateGainLoss();
    }
    
    /**
     * Set shares and recalculate values
     */
    public function setShares(float $shares): void
    {
        $this->attributes['shares'] = $shares;
        $this->updateMarketValue();
    }
    
    /**
     * Set current price and recalculate values
     */
    public function setCurrentPrice(float $price): void
    {
        $this->attributes['current_price'] = $price;
        $this->updateMarketValue();
    }
    
    /**
     * Set book cost and recalculate gain/loss
     */
    public function setBookCost(float $cost): void
    {
        $this->attributes['book_cost'] = $cost;
        $this->calculateGainLoss();
    }
    
    /**
     * Check if position is profitable
     */
    public function isProfitable(): bool
    {
        return $this->getGainLoss() > 0;
    }
    
    /**
     * Get position value as percentage of total portfolio
     */
    public function getWeightPercent(float $totalPortfolioValue): float
    {
        if ($totalPortfolioValue <= 0) {
            return 0;
        }
        
        return ($this->getMarketValue() / $totalPortfolioValue) * 100;
    }
    
    /**
     * Format for CSV export (compatible with existing system)
     */
    public function toCsvArray(): array
    {
        return [
            'Ticker' => $this->getSymbol(),
            'Shares' => number_format($this->getShares(), 4),
            'Buy Price' => number_format($this->getBookCost() / max($this->getShares(), 1), 4),
            'Current Price' => number_format($this->getCurrentPrice(), 4),
            'Market Value' => number_format($this->getMarketValue(), 2),
            'Book Cost' => number_format($this->getBookCost(), 2),
            'Gain/Loss' => number_format($this->getGainLoss(), 2),
            'Gain/Loss %' => number_format($this->getGainLossPercent(), 2),
            'Date' => $this->getDate()
        ];
    }
    
    /**
     * Create from CSV row (compatible with existing system)
     */
    public static function fromCsvArray(array $row): self
    {
        $symbol = $row['Ticker'] ?? $row['symbol'] ?? '';
        $shares = (float) ($row['Shares'] ?? $row['shares'] ?? 0);
        $currentPrice = (float) ($row['Current Price'] ?? $row['current_price'] ?? 0);
        $bookCost = (float) ($row['Book Cost'] ?? $row['book_cost'] ?? 0);
        
        $portfolio = new self([
            'symbol' => $symbol,
            'shares' => $shares,
            'current_price' => $currentPrice,
            'book_cost' => $bookCost,
            'date' => $row['Date'] ?? $row['date'] ?? date('Y-m-d')
        ]);
        
        $portfolio->updateMarketValue();
        
        return $portfolio;
    }
    
    /**
     * Custom validation for portfolio-specific rules
     */
    protected function validateField(string $field, $value, array $rules): void
    {
        parent::validateField($field, $value, $rules);
        
        // Additional portfolio-specific validations
        switch ($field) {
            case 'symbol':
                if (!empty($value) && !preg_match('/^[A-Z]{1,10}$/', strtoupper($value))) {
                    $this->errors[$field][] = 'Symbol must be 1-10 uppercase letters';
                }
                break;
                
            case 'shares':
                if ($value !== null && $value < 0) {
                    $this->errors[$field][] = 'Shares cannot be negative';
                }
                break;
                
            case 'market_value':
            case 'book_cost':
                if ($value !== null && $value < 0) {
                    $this->errors[$field][] = ucfirst($field) . ' cannot be negative';
                }
                break;
        }
    }
}