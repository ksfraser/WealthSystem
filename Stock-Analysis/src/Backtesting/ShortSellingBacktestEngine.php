<?php

declare(strict_types=1);

namespace WealthSystem\Backtesting;

use InvalidArgumentException;

/**
 * Short Selling Backtest Engine
 * 
 * Extends backtesting capabilities to support short positions:
 * - Enter/exit short positions
 * - Track short interest costs (borrow fees)
 * - Margin requirement calculations
 * - Liquidation on margin calls
 * - Mixed long/short portfolio tracking
 * 
 * Short selling allows profiting from declining prices but introduces additional risks:
 * - Unlimited loss potential (price can rise indefinitely)
 * - Margin requirements (typically 150% of position value)
 * - Short interest costs (daily borrow fees)
 * - Short squeeze risk (forced buyback at high prices)
 * 
 * @package WealthSystem\Backtesting
 * @author WealthSystem Team
 */
class ShortSellingBacktestEngine
{
    private array $config;
    private array $state;
    
    /**
     * @param array $config Configuration options
     *   - initial_capital: Starting capital (default: 100000)
     *   - commission_rate: Commission as decimal (default: 0.001 = 0.1%)
     *   - slippage_rate: Slippage as decimal (default: 0.0005 = 0.05%)
     *   - margin_requirement: Margin requirement for shorts (default: 1.5 = 150%)
     *   - short_interest_rate: Annual borrow rate (default: 0.03 = 3%)
     *   - margin_call_threshold: Margin call trigger (default: 1.3 = 130%)
     *   - max_position_size: Max position as % of portfolio (default: 0.2 = 20%)
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'initial_capital' => 100000.0,
            'commission_rate' => 0.001,
            'slippage_rate' => 0.0005,
            'margin_requirement' => 1.5,      // 150% of short position value
            'short_interest_rate' => 0.03,    // 3% annual borrow rate
            'margin_call_threshold' => 1.3,   // Margin call at 130%
            'max_position_size' => 0.2,       // 20% max position
            'liquidation_penalty' => 0.02     // 2% penalty on forced liquidation
        ], $config);
        
        $this->initializeState();
    }
    
    /**
     * Initialize backtest state
     */
    private function initializeState(): void
    {
        $this->state = [
            'cash' => $this->config['initial_capital'],
            'long_positions' => [],   // ['symbol' => ['shares' => int, 'cost_basis' => float, ...]]
            'short_positions' => [],  // ['symbol' => ['shares' => int, 'entry_price' => float, ...]]
            'margin_balance' => 0.0,  // Funds held as margin for short positions
            'trades' => [],
            'margin_calls' => [],
            'portfolio_values' => [],
            'short_interest_paid' => 0.0
        ];
    }
    
    /**
     * Get current state (for testing/inspection)
     */
    public function getState(): array
    {
        return $this->state;
    }
    
    /**
     * Enter a long position
     * 
     * @param string $symbol Stock symbol
     * @param int $shares Number of shares to buy
     * @param float $price Current price per share
     * @param string $date Trade date
     * @return array Trade details or error
     */
    public function enterLongPosition(
        string $symbol,
        int $shares,
        float $price,
        string $date
    ): array {
        if ($shares <= 0) {
            throw new InvalidArgumentException('Shares must be positive');
        }
        if ($price <= 0) {
            throw new InvalidArgumentException('Price must be positive');
        }
        
        // Apply slippage and commission
        $executionPrice = $price * (1 + $this->config['slippage_rate']);
        $grossCost = $shares * $executionPrice;
        $commission = $grossCost * $this->config['commission_rate'];
        $totalCost = $grossCost + $commission;
        
        // Check if we have enough cash
        if ($totalCost > $this->state['cash']) {
            return [
                'success' => false,
                'error' => 'Insufficient cash',
                'required' => $totalCost,
                'available' => $this->state['cash']
            ];
        }
        
        // Execute trade
        $this->state['cash'] -= $totalCost;
        
        if (!isset($this->state['long_positions'][$symbol])) {
            $this->state['long_positions'][$symbol] = [
                'shares' => 0,
                'cost_basis' => 0.0
            ];
        }
        
        // Update position (average cost if adding to existing)
        $existingShares = $this->state['long_positions'][$symbol]['shares'];
        $existingCost = $this->state['long_positions'][$symbol]['cost_basis'] * $existingShares;
        $newShares = $existingShares + $shares;
        $newCostBasis = ($existingCost + $totalCost) / $newShares;
        
        $this->state['long_positions'][$symbol] = [
            'shares' => $newShares,
            'cost_basis' => $newCostBasis
        ];
        
        $trade = [
            'date' => $date,
            'symbol' => $symbol,
            'action' => 'BUY',
            'shares' => $shares,
            'price' => $executionPrice,
            'commission' => $commission,
            'total_cost' => $totalCost,
            'success' => true
        ];
        
        $this->state['trades'][] = $trade;
        
        return $trade;
    }
    
    /**
     * Exit a long position
     * 
     * @param string $symbol Stock symbol
     * @param int $shares Number of shares to sell (null = all)
     * @param float $price Current price per share
     * @param string $date Trade date
     * @return array Trade details or error
     */
    public function exitLongPosition(
        string $symbol,
        ?int $shares,
        float $price,
        string $date
    ): array {
        if (!isset($this->state['long_positions'][$symbol]) || 
            $this->state['long_positions'][$symbol]['shares'] <= 0) {
            return [
                'success' => false,
                'error' => 'No long position to exit'
            ];
        }
        
        $position = $this->state['long_positions'][$symbol];
        $sharesToSell = $shares ?? $position['shares'];
        
        if ($sharesToSell > $position['shares']) {
            return [
                'success' => false,
                'error' => 'Insufficient shares',
                'requested' => $sharesToSell,
                'available' => $position['shares']
            ];
        }
        
        if ($price <= 0) {
            throw new InvalidArgumentException('Price must be positive');
        }
        
        // Apply slippage and commission
        $executionPrice = $price * (1 - $this->config['slippage_rate']);
        $grossProceeds = $sharesToSell * $executionPrice;
        $commission = $grossProceeds * $this->config['commission_rate'];
        $netProceeds = $grossProceeds - $commission;
        
        // Calculate P&L
        $costBasis = $sharesToSell * $position['cost_basis'];
        $profit = $netProceeds - $costBasis;
        $profitPercent = ($executionPrice - $position['cost_basis']) / $position['cost_basis'];
        
        // Update state
        $this->state['cash'] += $netProceeds;
        $this->state['long_positions'][$symbol]['shares'] -= $sharesToSell;
        
        if ($this->state['long_positions'][$symbol]['shares'] <= 0) {
            unset($this->state['long_positions'][$symbol]);
        }
        
        $trade = [
            'date' => $date,
            'symbol' => $symbol,
            'action' => 'SELL',
            'shares' => $sharesToSell,
            'price' => $executionPrice,
            'commission' => $commission,
            'total_proceeds' => $netProceeds,
            'cost_basis' => $position['cost_basis'],
            'profit' => $profit,
            'profit_percent' => $profitPercent,
            'success' => true
        ];
        
        $this->state['trades'][] = $trade;
        
        return $trade;
    }
    
    /**
     * Enter a short position
     * 
     * Borrow shares and sell them at current price.
     * Must maintain margin requirement (typically 150% of position value).
     * 
     * @param string $symbol Stock symbol
     * @param int $shares Number of shares to short
     * @param float $price Current price per share
     * @param string $date Trade date
     * @return array Trade details or error
     */
    public function enterShortPosition(
        string $symbol,
        int $shares,
        float $price,
        string $date
    ): array {
        if ($shares <= 0) {
            throw new InvalidArgumentException('Shares must be positive');
        }
        if ($price <= 0) {
            throw new InvalidArgumentException('Price must be positive');
        }
        
        // Apply slippage and commission
        $executionPrice = $price * (1 - $this->config['slippage_rate']); // Sell at lower price
        $grossProceeds = $shares * $executionPrice;
        $commission = $grossProceeds * $this->config['commission_rate'];
        $netProceeds = $grossProceeds - $commission;
        
        // Calculate required margin
        $requiredMargin = $grossProceeds * $this->config['margin_requirement'];
        
        // Check if we have enough cash for margin
        if ($requiredMargin > $this->state['cash']) {
            return [
                'success' => false,
                'error' => 'Insufficient cash for margin',
                'required_margin' => $requiredMargin,
                'available' => $this->state['cash']
            ];
        }
        
        // Execute short sale
        // Proceeds are held as part of margin (can't use them)
        // Additional cash is locked as margin
        $additionalMargin = $requiredMargin - $netProceeds;
        $this->state['cash'] -= $additionalMargin;
        $this->state['margin_balance'] += $requiredMargin;
        
        if (!isset($this->state['short_positions'][$symbol])) {
            $this->state['short_positions'][$symbol] = [
                'shares' => 0,
                'entry_price' => 0.0,
                'margin_held' => 0.0,
                'entry_date' => $date
            ];
        }
        
        // Update position (average if adding to existing short)
        $existingShares = $this->state['short_positions'][$symbol]['shares'];
        $existingValue = $existingShares * $this->state['short_positions'][$symbol]['entry_price'];
        $newShares = $existingShares + $shares;
        $newEntryPrice = ($existingValue + $grossProceeds) / $newShares;
        
        $this->state['short_positions'][$symbol]['shares'] = $newShares;
        $this->state['short_positions'][$symbol]['entry_price'] = $newEntryPrice;
        $this->state['short_positions'][$symbol]['margin_held'] += $requiredMargin;
        
        $trade = [
            'date' => $date,
            'symbol' => $symbol,
            'action' => 'SHORT',
            'shares' => $shares,
            'price' => $executionPrice,
            'commission' => $commission,
            'proceeds' => $netProceeds,
            'margin_required' => $requiredMargin,
            'success' => true
        ];
        
        $this->state['trades'][] = $trade;
        
        return $trade;
    }
    
    /**
     * Exit a short position (cover the short)
     * 
     * Buy back borrowed shares to close the position.
     * 
     * @param string $symbol Stock symbol
     * @param int $shares Number of shares to cover (null = all)
     * @param float $price Current price per share
     * @param string $date Trade date
     * @return array Trade details or error
     */
    public function exitShortPosition(
        string $symbol,
        ?int $shares,
        float $price,
        string $date
    ): array {
        if (!isset($this->state['short_positions'][$symbol]) || 
            $this->state['short_positions'][$symbol]['shares'] <= 0) {
            return [
                'success' => false,
                'error' => 'No short position to exit'
            ];
        }
        
        $position = $this->state['short_positions'][$symbol];
        $sharesToCover = $shares ?? $position['shares'];
        
        if ($sharesToCover > $position['shares']) {
            return [
                'success' => false,
                'error' => 'Insufficient shares',
                'requested' => $sharesToCover,
                'available' => $position['shares']
            ];
        }
        
        if ($price <= 0) {
            throw new InvalidArgumentException('Price must be positive');
        }
        
        // Calculate days held for interest calculation
        $daysHeld = $this->calculateDaysHeld($position['entry_date'], $date);
        $interestCost = $this->calculateShortInterest(
            $sharesToCover,
            $position['entry_price'],
            $daysHeld
        );
        
        // Apply slippage and commission
        $executionPrice = $price * (1 + $this->config['slippage_rate']); // Buy at higher price
        $grossCost = $sharesToCover * $executionPrice;
        $commission = $grossCost * $this->config['commission_rate'];
        $totalCost = $grossCost + $commission + $interestCost;
        
        // Calculate P&L (profit if price went down, loss if up)
        $entryValue = $sharesToCover * $position['entry_price'];
        $profit = $entryValue - $totalCost;
        $profitPercent = ($position['entry_price'] - $executionPrice) / $position['entry_price'];
        
        // Release margin proportionally
        $marginToRelease = ($sharesToCover / $position['shares']) * $position['margin_held'];
        
        // Update state
        $this->state['margin_balance'] -= $marginToRelease;
        $this->state['cash'] += ($marginToRelease - $totalCost);
        $this->state['short_interest_paid'] += $interestCost;
        
        $this->state['short_positions'][$symbol]['shares'] -= $sharesToCover;
        $this->state['short_positions'][$symbol]['margin_held'] -= $marginToRelease;
        
        if ($this->state['short_positions'][$symbol]['shares'] <= 0) {
            unset($this->state['short_positions'][$symbol]);
        }
        
        $trade = [
            'date' => $date,
            'symbol' => $symbol,
            'action' => 'COVER',
            'shares' => $sharesToCover,
            'price' => $executionPrice,
            'commission' => $commission,
            'short_interest' => $interestCost,
            'total_cost' => $totalCost,
            'entry_price' => $position['entry_price'],
            'profit' => $profit,
            'profit_percent' => $profitPercent,
            'days_held' => $daysHeld,
            'success' => true
        ];
        
        $this->state['trades'][] = $trade;
        
        return $trade;
    }
    
    /**
     * Calculate short interest cost
     * 
     * @param int $shares Number of shares borrowed
     * @param float $price Price per share
     * @param int $days Days position was held
     * @return float Interest cost
     */
    private function calculateShortInterest(int $shares, float $price, int $days): float
    {
        $positionValue = $shares * $price;
        $annualRate = $this->config['short_interest_rate'];
        $dailyRate = $annualRate / 365;
        
        return $positionValue * $dailyRate * $days;
    }
    
    /**
     * Calculate days between two dates
     */
    private function calculateDaysHeld(string $entryDate, string $exitDate): int
    {
        $entry = new \DateTime($entryDate);
        $exit = new \DateTime($exitDate);
        $interval = $entry->diff($exit);
        
        return (int) $interval->days;
    }
    
    /**
     * Check margin requirements and trigger margin calls if needed
     * 
     * @param array $currentPrices Current prices ['symbol' => price]
     * @param string $date Current date
     * @return array Margin call details if triggered
     */
    public function checkMarginRequirements(array $currentPrices, string $date): array
    {
        $marginCalls = [];
        
        foreach ($this->state['short_positions'] as $symbol => $position) {
            if (!isset($currentPrices[$symbol])) {
                continue;
            }
            
            $currentPrice = $currentPrices[$symbol];
            $currentValue = $position['shares'] * $currentPrice;
            $entryValue = $position['shares'] * $position['entry_price'];
            
            // Calculate current margin ratio
            // margin_held / (current_value) should be >= margin_requirement
            $currentMarginRatio = $position['margin_held'] / $currentValue;
            
            // Margin call if ratio falls below threshold
            if ($currentMarginRatio < $this->config['margin_call_threshold']) {
                $marginCalls[] = [
                    'date' => $date,
                    'symbol' => $symbol,
                    'shares' => $position['shares'],
                    'entry_price' => $position['entry_price'],
                    'current_price' => $currentPrice,
                    'margin_held' => $position['margin_held'],
                    'current_margin_ratio' => $currentMarginRatio,
                    'required_ratio' => $this->config['margin_requirement'],
                    'unrealized_loss' => $currentValue - $entryValue,
                    'action_required' => 'add_margin_or_liquidate'
                ];
                
                $this->state['margin_calls'][] = end($marginCalls);
            }
        }
        
        return $marginCalls;
    }
    
    /**
     * Force liquidate a position (e.g., on margin call)
     * 
     * @param string $symbol Symbol to liquidate
     * @param float $price Current price
     * @param string $date Current date
     * @return array Liquidation details
     */
    public function forceLiquidate(string $symbol, float $price, string $date): array
    {
        // Apply liquidation penalty
        $penalizedPrice = $price * (1 + $this->config['liquidation_penalty']);
        
        // Exit short position at penalty price
        $result = $this->exitShortPosition($symbol, null, $penalizedPrice, $date);
        
        if ($result['success']) {
            $result['action'] = 'FORCED_LIQUIDATION';
            $result['penalty'] = $this->config['liquidation_penalty'];
        }
        
        return $result;
    }
    
    /**
     * Calculate current portfolio value
     * 
     * @param array $currentPrices Current prices ['symbol' => price]
     * @return array Portfolio valuation
     */
    public function calculatePortfolioValue(array $currentPrices): array
    {
        $longValue = 0.0;
        $shortValue = 0.0;
        $shortLiability = 0.0;
        
        // Value long positions
        foreach ($this->state['long_positions'] as $symbol => $position) {
            if (isset($currentPrices[$symbol])) {
                $longValue += $position['shares'] * $currentPrices[$symbol];
            }
        }
        
        // Value short positions (liability)
        foreach ($this->state['short_positions'] as $symbol => $position) {
            if (isset($currentPrices[$symbol])) {
                $currentValue = $position['shares'] * $currentPrices[$symbol];
                $shortValue += $currentValue; // What we owe
                $shortLiability += $currentValue;
            }
        }
        
        $totalAssets = $this->state['cash'] + $longValue + $this->state['margin_balance'];
        $netWorth = $totalAssets - $shortLiability;
        
        return [
            'cash' => $this->state['cash'],
            'long_value' => $longValue,
            'short_liability' => $shortLiability,
            'margin_balance' => $this->state['margin_balance'],
            'total_assets' => $totalAssets,
            'net_worth' => $netWorth,
            'long_positions_count' => count($this->state['long_positions']),
            'short_positions_count' => count($this->state['short_positions'])
        ];
    }
    
    /**
     * Get summary statistics
     */
    public function getSummary(): array
    {
        return [
            'total_trades' => count($this->state['trades']),
            'long_trades' => count(array_filter($this->state['trades'], fn($t) => $t['action'] === 'BUY')),
            'short_trades' => count(array_filter($this->state['trades'], fn($t) => $t['action'] === 'SHORT')),
            'margin_calls' => count($this->state['margin_calls']),
            'short_interest_paid' => $this->state['short_interest_paid'],
            'current_positions' => [
                'long' => $this->state['long_positions'],
                'short' => $this->state['short_positions']
            ]
        ];
    }
}
