<?php

declare(strict_types=1);

namespace App\Backtesting;

use App\Services\Trading\TradingStrategyInterface;
use InvalidArgumentException;

/**
 * Backtest Engine
 * 
 * Simulates trading strategy execution on historical data with:
 * - Trade execution (buy/sell based on strategy signals)
 * - Position management (tracks current holdings)
 * - Commission and slippage application
 * - Portfolio value tracking
 * - Equity curve generation
 * - Trade log with full details
 * 
 * @package App\Backtesting
 */
class BacktestEngine
{
    private array $config;
    private float $cash;
    private float $initialCapital;
    private int $shares = 0;
    private array $trades = [];
    private array $equityCurve = [];
    private float $totalCommission = 0.0;
    
    /**
     * Create new backtest engine
     *
     * @param array<string, mixed> $config Configuration
     *                                      - initial_capital: Starting capital
     *                                      - commission: Commission rate (e.g., 0.001 = 0.1%)
     *                                      - slippage: Slippage rate (e.g., 0.0005 = 0.05%)
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'initial_capital' => 10000.0,
            'commission' => 0.001,
            'slippage' => 0.0005
        ], $config);
        
        $this->initialCapital = $this->config['initial_capital'];
        $this->cash = $this->initialCapital;
    }
    
    /**
     * Get configuration
     *
     * @return array<string, mixed> Configuration
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }
    
    /**
     * Run backtest
     *
     * @param TradingStrategyInterface $strategy Trading strategy
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical price/volume data
     * @return array<string, mixed> Backtest results
     * @throws InvalidArgumentException
     */
    public function run(TradingStrategyInterface $strategy, string $symbol, array $historicalData): array
    {
        $this->validateInputs($symbol, $historicalData);
        $this->reset();
        
        foreach ($historicalData as $index => $bar) {
            // Get strategy signal
            $signalResult = $strategy->analyze($symbol, $bar['date']);
            $action = $signalResult['signal'] ?? 'HOLD';
            
            // Execute trade based on signal
            $this->executeSignal($action, $bar, $symbol);
            
            // Track equity
            $portfolioValue = $this->calculatePortfolioValue($bar['close']);
            $this->equityCurve[] = $portfolioValue;
        }
        
        // Calculate final results
        $finalValue = $this->calculatePortfolioValue($historicalData[count($historicalData) - 1]['close']);
        $returnPct = (($finalValue - $this->initialCapital) / $this->initialCapital) * 100;
        $maxDrawdown = $this->calculateMaxDrawdown();
        
        return [
            'symbol' => $symbol,
            'initial_capital' => $this->initialCapital,
            'final_value' => $finalValue,
            'return_pct' => $returnPct,
            'trades' => $this->trades,
            'total_commission' => $this->totalCommission,
            'equity_curve' => $this->equityCurve,
            'max_drawdown' => $maxDrawdown,
            'days' => count($historicalData)
        ];
    }
    
    /**
     * Validate inputs
     *
     * @param string $symbol Symbol
     * @param array<int, mixed> $historicalData Historical data
     * @return void
     * @throws InvalidArgumentException If invalid
     */
    private function validateInputs(string $symbol, array $historicalData): void
    {
        if (empty($symbol)) {
            throw new InvalidArgumentException('Symbol is required');
        }
        
        if (empty($historicalData)) {
            throw new InvalidArgumentException('Historical data is required');
        }
    }
    
    /**
     * Reset engine state
     *
     * @return void
     */
    private function reset(): void
    {
        $this->cash = $this->initialCapital;
        $this->shares = 0;
        $this->trades = [];
        $this->equityCurve = [];
        $this->totalCommission = 0.0;
    }
    
    /**
     * Execute signal (buy/sell/hold)
     *
     * @param string $action BUY, SELL, or HOLD
     * @param array<string, mixed> $bar Current price bar
     * @param string $symbol Symbol
     * @return void
     */
    private function executeSignal(string $action, array $bar, string $symbol): void
    {
        if ($action === 'BUY' && $this->shares === 0) {
            $this->executeBuy($bar, $symbol);
        } elseif ($action === 'SELL' && $this->shares > 0) {
            $this->executeSell($bar, $symbol);
        }
        // HOLD: do nothing
    }
    
    /**
     * Execute buy order
     *
     * @param array<string, mixed> $bar Price bar
     * @param string $symbol Symbol
     * @return void
     */
    private function executeBuy(array $bar, string $symbol): void
    {
        $price = $this->applySlippage($bar['close'], 'BUY');
        $commission = $this->cash * $this->config['commission'];
        $availableCash = $this->cash - $commission;
        
        if ($availableCash <= 0) {
            return; // Insufficient capital
        }
        
        $shares = (int) floor($availableCash / $price);
        
        if ($shares === 0) {
            return; // Can't afford even 1 share
        }
        
        $cost = $shares * $price + $commission;
        
        if ($cost > $this->cash) {
            return; // Insufficient capital after commission
        }
        
        $this->cash -= $cost;
        $this->shares = $shares;
        $this->totalCommission += $commission;
        
        $this->trades[] = [
            'date' => $bar['date'],
            'action' => 'BUY',
            'symbol' => $symbol,
            'price' => $price,
            'shares' => $shares,
            'cost' => $cost,
            'commission' => $commission
        ];
    }
    
    /**
     * Execute sell order
     *
     * @param array<string, mixed> $bar Price bar
     * @param string $symbol Symbol
     * @return void
     */
    private function executeSell(array $bar, string $symbol): void
    {
        $price = $this->applySlippage($bar['close'], 'SELL');
        $proceeds = $this->shares * $price;
        $commission = $proceeds * $this->config['commission'];
        $netProceeds = $proceeds - $commission;
        
        // Find corresponding buy trade to calculate profit
        $buyTrade = $this->getLastBuyTrade();
        $profit = $netProceeds - ($buyTrade['cost'] ?? 0);
        
        $this->cash += $netProceeds;
        $this->totalCommission += $commission;
        
        $this->trades[] = [
            'date' => $bar['date'],
            'action' => 'SELL',
            'symbol' => $symbol,
            'price' => $price,
            'shares' => $this->shares,
            'proceeds' => $netProceeds,
            'commission' => $commission,
            'profit' => $profit,
            'return' => $buyTrade ? (($price - $buyTrade['price']) / $buyTrade['price']) * 100 : 0
        ];
        
        $this->shares = 0;
    }
    
    /**
     * Apply slippage to price
     *
     * @param float $price Base price
     * @param string $action BUY or SELL
     * @return float Price with slippage
     */
    private function applySlippage(float $price, string $action): float
    {
        $slippage = $price * $this->config['slippage'];
        
        return $action === 'BUY' 
            ? $price + $slippage  // Pay more when buying
            : $price - $slippage; // Receive less when selling
    }
    
    /**
     * Calculate current portfolio value
     *
     * @param float $currentPrice Current stock price
     * @return float Portfolio value
     */
    private function calculatePortfolioValue(float $currentPrice): float
    {
        return $this->cash + ($this->shares * $currentPrice);
    }
    
    /**
     * Calculate maximum drawdown
     *
     * @return float Maximum drawdown percentage (negative)
     */
    private function calculateMaxDrawdown(): float
    {
        if (empty($this->equityCurve)) {
            return 0.0;
        }
        
        $maxDrawdown = 0.0;
        $peak = $this->equityCurve[0];
        
        foreach ($this->equityCurve as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            
            $drawdown = (($value - $peak) / $peak) * 100;
            
            if ($drawdown < $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return $maxDrawdown;
    }
    
    /**
     * Get price history up to current index
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param int $index Current index
     * @return array<int, float> Price history
     */
    private function getPriceHistory(array $data, int $index): array
    {
        $prices = [];
        
        for ($i = 0; $i <= $index; $i++) {
            $prices[] = $data[$i]['close'];
        }
        
        return $prices;
    }
    
    /**
     * Get volume history up to current index
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param int $index Current index
     * @return array<int, float> Volume history
     */
    private function getVolumeHistory(array $data, int $index): array
    {
        $volumes = [];
        
        for ($i = 0; $i <= $index; $i++) {
            $volumes[] = $data[$i]['volume'] ?? 0;
        }
        
        return $volumes;
    }
    
    /**
     * Get last buy trade
     *
     * @return array<string, mixed>|null Last buy trade
     */
    private function getLastBuyTrade(): ?array
    {
        for ($i = count($this->trades) - 1; $i >= 0; $i--) {
            if ($this->trades[$i]['action'] === 'BUY') {
                return $this->trades[$i];
            }
        }
        
        return null;
    }
}
