<?php

namespace App\Services;

use App\Services\Trading\TradingStrategyInterface;
use App\Repositories\StrategyRepositoryInterface;

/**
 * Backtest Engine
 * 
 * Simulates trading strategy execution on historical data to evaluate
 * performance before live trading.
 * 
 * Features:
 * - Historical data replay with time-based simulation
 * - Strategy signal execution and trade management
 * - P&L calculation with transaction costs
 * - Performance metrics (Sharpe, drawdown, win rate)
 * - Integration with StrategyRepository for results storage
 */
class BacktestEngine
{
    private StrategyRepositoryInterface $strategyRepository;
    private float $initialCapital;
    private float $transactionCostPercent;
    
    private array $trades = [];
    private array $positions = [];
    private array $equityCurve = [];
    private float $currentCapital;
    private float $peakCapital;

    public function __construct(
        StrategyRepositoryInterface $strategyRepository,
        float $initialCapital = 100000.0,
        float $transactionCostPercent = 0.001 // 0.1% transaction cost
    ) {
        $this->strategyRepository = $strategyRepository;
        $this->initialCapital = $initialCapital;
        $this->currentCapital = $initialCapital;
        $this->peakCapital = $initialCapital;
        $this->transactionCostPercent = $transactionCostPercent;
    }

    /**
     * Run backtest on historical data
     * 
     * @param TradingStrategyInterface $strategy Trading strategy
     * @param array $historicalData Historical price data [date => [symbol => priceData]]
     * @param array $symbols Symbols to test
     * @param array $config Backtest configuration
     * @return array Backtest results
     */
    public function runBacktest(
        TradingStrategyInterface $strategy,
        array $historicalData,
        array $symbols,
        array $config = []
    ): array {
        $this->reset();
        
        $startDate = $config['start_date'] ?? null;
        $endDate = $config['end_date'] ?? null;
        $maxPositionSize = $config['max_position_size'] ?? 0.2; // 20% of capital per position
        
        $strategyName = $this->getStrategyName($strategy);
        
        // Sort historical data by date
        ksort($historicalData);
        
        foreach ($historicalData as $date => $dayData) {
            // Check date range
            if ($startDate && $date < $startDate) continue;
            if ($endDate && $date > $endDate) break;
            
            // Update positions with current prices
            $this->updatePositions($date, $dayData);
            
            // Generate signals for each symbol
            foreach ($symbols as $symbol) {
                if (!isset($dayData[$symbol])) continue;
                
                // Get strategy signal using the date
                // Note: In real backtest, strategies would need access to historical data
                // For now, we pass the date and let strategy fetch data
                $signal = $strategy->analyze($symbol, $date);
                
                // Execute signal
                $this->executeSignal($symbol, $signal, $dayData[$symbol], $date, $maxPositionSize);
            }
            
            // Record equity for this day
            $this->recordEquity($date);
        }
        
        // Close all remaining positions at end date
        $this->closeAllPositions($historicalData, array_key_last($historicalData));
        
        // Calculate performance metrics
        $metrics = $this->calculateMetrics();
        
        // Store backtest results
        $backtestId = $this->strategyRepository->storeBacktest(
            $strategyName,
            $config,
            [
                'trades' => $this->trades,
                'equity_curve' => $this->equityCurve,
                'metrics' => $metrics
            ],
            date('Y-m-d H:i:s')
        );
        
        return [
            'backtest_id' => $backtestId,
            'strategy' => $strategyName,
            'initial_capital' => $this->initialCapital,
            'final_capital' => $this->currentCapital,
            'total_return' => ($this->currentCapital - $this->initialCapital) / $this->initialCapital,
            'total_trades' => count($this->trades),
            'equity_curve' => $this->equityCurve,
            'metrics' => $metrics,
            'trades' => $this->trades,
            'config' => $config
        ];
    }

    /**
     * Execute trading signal
     */
    private function executeSignal(
        string $symbol,
        array $signal,
        array $priceData,
        string $date,
        float $maxPositionSize
    ): void {
        $signalType = $signal['signal'];
        $price = $priceData['close'];
        
        if ($signalType === 'BUY' && !$this->hasPosition($symbol)) {
            $this->openLongPosition($symbol, $price, $date, $signal, $maxPositionSize);
        } elseif ($signalType === 'SELL' && $this->hasPosition($symbol, 'LONG')) {
            $this->closePosition($symbol, $price, $date, 'SELL_SIGNAL');
        } elseif ($signalType === 'SHORT' && !$this->hasPosition($symbol)) {
            $this->openShortPosition($symbol, $price, $date, $signal, $maxPositionSize);
        } elseif ($signalType === 'COVER' && $this->hasPosition($symbol, 'SHORT')) {
            $this->closePosition($symbol, $price, $date, 'COVER_SIGNAL');
        }
        
        // Check stop loss and take profit
        if ($this->hasPosition($symbol)) {
            $this->checkStopLossAndTakeProfit($symbol, $priceData, $date);
        }
    }

    /**
     * Open long position
     */
    private function openLongPosition(
        string $symbol,
        float $price,
        string $date,
        array $signal,
        float $maxPositionSize
    ): void {
        $positionValue = $this->currentCapital * $maxPositionSize;
        $shares = floor($positionValue / $price);
        
        if ($shares <= 0) return;
        
        $cost = $shares * $price;
        $transactionCost = $cost * $this->transactionCostPercent;
        $totalCost = $cost + $transactionCost;
        
        if ($totalCost > $this->currentCapital) return;
        
        $this->currentCapital -= $totalCost;
        
        $this->positions[$symbol] = [
            'type' => 'LONG',
            'shares' => $shares,
            'entry_price' => $price,
            'entry_date' => $date,
            'stop_loss' => $signal['stop_loss'] ?? null,
            'take_profit' => $signal['take_profit'] ?? null,
            'cost_basis' => $totalCost
        ];
    }

    /**
     * Open short position
     */
    private function openShortPosition(
        string $symbol,
        float $price,
        string $date,
        array $signal,
        float $maxPositionSize
    ): void {
        $positionValue = $this->currentCapital * $maxPositionSize;
        $shares = floor($positionValue / $price);
        
        if ($shares <= 0) return;
        
        $proceeds = $shares * $price;
        $transactionCost = $proceeds * $this->transactionCostPercent;
        
        $this->currentCapital += ($proceeds - $transactionCost);
        
        $this->positions[$symbol] = [
            'type' => 'SHORT',
            'shares' => $shares,
            'entry_price' => $price,
            'entry_date' => $date,
            'stop_loss' => $signal['stop_loss'] ?? null,
            'take_profit' => $signal['take_profit'] ?? null,
            'short_proceeds' => $proceeds
        ];
    }

    /**
     * Close position
     */
    private function closePosition(string $symbol, float $price, string $date, string $reason): void
    {
        if (!isset($this->positions[$symbol])) return;
        
        $position = $this->positions[$symbol];
        $shares = $position['shares'];
        
        if ($position['type'] === 'LONG') {
            $proceeds = $shares * $price;
            $transactionCost = $proceeds * $this->transactionCostPercent;
            $netProceeds = $proceeds - $transactionCost;
            
            $this->currentCapital += $netProceeds;
            $profitLoss = $netProceeds - $position['cost_basis'];
            $returnPercent = $profitLoss / $position['cost_basis'];
        } else { // SHORT
            $cost = $shares * $price;
            $transactionCost = $cost * $this->transactionCostPercent;
            $totalCost = $cost + $transactionCost;
            
            $this->currentCapital -= $totalCost;
            $profitLoss = $position['short_proceeds'] - $cost - $transactionCost;
            $returnPercent = $profitLoss / ($shares * $position['entry_price']);
        }
        
        // Record trade
        $this->trades[] = [
            'symbol' => $symbol,
            'type' => $position['type'],
            'entry_date' => $position['entry_date'],
            'exit_date' => $date,
            'entry_price' => $position['entry_price'],
            'exit_price' => $price,
            'shares' => $shares,
            'profit_loss' => $profitLoss,
            'return_percent' => $returnPercent,
            'exit_reason' => $reason,
            'holding_days' => $this->calculateDays($position['entry_date'], $date)
        ];
        
        // Update peak capital
        if ($this->currentCapital > $this->peakCapital) {
            $this->peakCapital = $this->currentCapital;
        }
        
        unset($this->positions[$symbol]);
    }

    /**
     * Check stop loss and take profit levels
     */
    private function checkStopLossAndTakeProfit(string $symbol, array $priceData, string $date): void
    {
        $position = $this->positions[$symbol];
        $currentPrice = $priceData['close'];
        $low = $priceData['low'];
        $high = $priceData['high'];
        
        if ($position['type'] === 'LONG') {
            // Check stop loss
            if ($position['stop_loss'] && $low <= $position['stop_loss']) {
                $this->closePosition($symbol, $position['stop_loss'], $date, 'STOP_LOSS');
                return;
            }
            
            // Check take profit
            if ($position['take_profit'] && $high >= $position['take_profit']) {
                $this->closePosition($symbol, $position['take_profit'], $date, 'TAKE_PROFIT');
                return;
            }
        } else { // SHORT
            // Check stop loss (price goes up)
            if ($position['stop_loss'] && $high >= $position['stop_loss']) {
                $this->closePosition($symbol, $position['stop_loss'], $date, 'STOP_LOSS');
                return;
            }
            
            // Check take profit (price goes down)
            if ($position['take_profit'] && $low <= $position['take_profit']) {
                $this->closePosition($symbol, $position['take_profit'], $date, 'TAKE_PROFIT');
                return;
            }
        }
    }

    /**
     * Update positions with current market prices
     */
    private function updatePositions(string $date, array $dayData): void
    {
        foreach ($this->positions as $symbol => $position) {
            if (!isset($dayData[$symbol])) continue;
            
            $this->checkStopLossAndTakeProfit($symbol, $dayData[$symbol], $date);
        }
    }

    /**
     * Close all open positions
     */
    private function closeAllPositions(array $historicalData, string $finalDate): void
    {
        $finalDayData = $historicalData[$finalDate] ?? [];
        
        foreach ($this->positions as $symbol => $position) {
            if (isset($finalDayData[$symbol])) {
                $this->closePosition($symbol, $finalDayData[$symbol]['close'], $finalDate, 'END_OF_BACKTEST');
            }
        }
    }

    /**
     * Record equity for the day
     */
    private function recordEquity(string $date): void
    {
        $this->equityCurve[] = [
            'date' => $date,
            'equity' => $this->getCurrentEquity(),
            'cash' => $this->currentCapital,
            'open_positions' => count($this->positions)
        ];
    }

    /**
     * Get current total equity (cash + positions)
     */
    private function getCurrentEquity(): float
    {
        $equity = $this->currentCapital;
        
        // This is a simplified calculation - in reality, we'd need current prices
        // For now, just return cash
        return $equity;
    }

    /**
     * Calculate performance metrics
     */
    private function calculateMetrics(): array
    {
        if (empty($this->trades)) {
            return $this->getEmptyMetrics();
        }
        
        $winningTrades = array_filter($this->trades, fn($t) => $t['profit_loss'] > 0);
        $losingTrades = array_filter($this->trades, fn($t) => $t['profit_loss'] < 0);
        
        $totalTrades = count($this->trades);
        $winCount = count($winningTrades);
        $loseCount = count($losingTrades);
        
        $totalProfit = array_sum(array_column($winningTrades, 'profit_loss'));
        $totalLoss = abs(array_sum(array_column($losingTrades, 'profit_loss')));
        
        $avgWin = $winCount > 0 ? $totalProfit / $winCount : 0;
        $avgLoss = $loseCount > 0 ? $totalLoss / $loseCount : 0;
        
        $winRate = $totalTrades > 0 ? $winCount / $totalTrades : 0;
        $profitFactor = $totalLoss > 0 ? $totalProfit / $totalLoss : 0;
        
        // Calculate returns for Sharpe ratio
        $returns = array_map(fn($t) => $t['return_percent'], $this->trades);
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);
        $sharpeRatio = $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0; // Annualized
        
        // Calculate max drawdown
        $maxDrawdown = $this->calculateMaxDrawdown();
        
        // Average holding period
        $avgHoldingDays = array_sum(array_column($this->trades, 'holding_days')) / $totalTrades;
        
        return [
            'total_trades' => $totalTrades,
            'winning_trades' => $winCount,
            'losing_trades' => $loseCount,
            'win_rate' => round($winRate, 4),
            'avg_win' => round($avgWin, 2),
            'avg_loss' => round($avgLoss, 2),
            'profit_factor' => round($profitFactor, 2),
            'sharpe_ratio' => round($sharpeRatio, 2),
            'max_drawdown' => round($maxDrawdown, 4),
            'avg_holding_days' => round($avgHoldingDays, 1),
            'total_return' => round(($this->currentCapital - $this->initialCapital) / $this->initialCapital, 4)
        ];
    }

    /**
     * Calculate maximum drawdown
     */
    private function calculateMaxDrawdown(): float
    {
        $maxDrawdown = 0;
        $peak = $this->initialCapital;
        
        foreach ($this->equityCurve as $point) {
            $equity = $point['equity'];
            
            if ($equity > $peak) {
                $peak = $equity;
            }
            
            $drawdown = ($peak - $equity) / $peak;
            
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return $maxDrawdown;
    }

    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        $count = count($values);
        if ($count <= 1) return 0;
        
        $mean = array_sum($values) / $count;
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / ($count - 1);
        
        return sqrt($variance);
    }

    /**
     * Get price history up to a specific date
     */
    private function getPriceHistory(array $historicalData, string $symbol, string $currentDate, int $lookback): array
    {
        $history = [];
        
        foreach ($historicalData as $date => $dayData) {
            if ($date > $currentDate) break;
            
            if (isset($dayData[$symbol])) {
                $history[] = $dayData[$symbol];
            }
        }
        
        // Return last N days
        return array_slice($history, -$lookback);
    }

    /**
     * Check if position exists
     */
    private function hasPosition(string $symbol, ?string $type = null): bool
    {
        if (!isset($this->positions[$symbol])) {
            return false;
        }
        
        if ($type !== null) {
            return $this->positions[$symbol]['type'] === $type;
        }
        
        return true;
    }

    /**
     * Calculate days between dates
     */
    private function calculateDays(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        return $start->diff($end)->days;
    }

    /**
     * Get strategy name from object
     */
    private function getStrategyName(TradingStrategyInterface $strategy): string
    {
        $className = get_class($strategy);
        return basename(str_replace('\\', '/', $className));
    }

    /**
     * Reset backtest state
     */
    private function reset(): void
    {
        $this->trades = [];
        $this->positions = [];
        $this->equityCurve = [];
        $this->currentCapital = $this->initialCapital;
        $this->peakCapital = $this->initialCapital;
    }

    /**
     * Get empty metrics structure
     */
    private function getEmptyMetrics(): array
    {
        return [
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'avg_win' => 0,
            'avg_loss' => 0,
            'profit_factor' => 0,
            'sharpe_ratio' => 0,
            'max_drawdown' => 0,
            'avg_holding_days' => 0,
            'total_return' => 0
        ];
    }
}
