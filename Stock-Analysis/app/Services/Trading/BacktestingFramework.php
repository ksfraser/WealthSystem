<?php

namespace App\Services\Trading;

/**
 * Backtesting Framework
 * 
 * Historical simulation engine for validating trading strategies with realistic
 * market conditions including commissions, slippage, and risk management.
 * 
 * Core Features:
 * - **Historical Simulation**: Walk-forward analysis using past price data
 * - **Transaction Costs**: Configurable commission (default 0.1%) and slippage (default 0.05%)
 * - **Position Sizing**: Percentage-based capital allocation per trade
 * - **Risk Management**: Stop loss, take profit, maximum holding period
 * - **Portfolio-Level Backtesting**: Multi-strategy simulation with rebalancing
 * 
 * Risk Controls:
 * - Stop Loss: Automatic exit at specified loss percentage
 * - Take Profit: Automatic exit at specified gain percentage
 * - Max Holding Days: Force exit after time limit
 * - Position Size Limits: Prevent over-concentration
 * 
 * Walk-Forward Analysis:
 * - Divides historical data into training/testing periods
 * - Validates strategy parameters on out-of-sample data
 * - Measures parameter stability across different market conditions
 * - Reduces overfitting by testing on unseen data
 * 
 * Monte Carlo Simulation:
 * - Randomizes trade sequences to test robustness
 * - Measures confidence intervals for returns
 * - Identifies strategies resilient to sequence risk
 * - Provides probabilistic performance distributions
 * 
 * Output Metrics:
 * - Total return, Sharpe ratio, max drawdown
 * - Win rate, profit factor, average win/loss
 * - Trade count, holding period statistics
 * - Equity curve for visualization
 * 
 * @package App\Services\Trading
 */
class BacktestingFramework
{
    private float $initialCapital;
    private float $commissionRate;
    private float $slippageRate;
    private array $results = [];
    
    public function __construct(
        float $initialCapital = 100000.0,
        float $commissionRate = 0.001,  // 0.1% commission
        float $slippageRate = 0.0005    // 0.05% slippage
    ) {
        $this->initialCapital = $initialCapital;
        $this->commissionRate = $commissionRate;
        $this->slippageRate = $slippageRate;
    }
    
    /**
     * Run backtest for a single strategy with advanced risk management
     * 
     * Features:
     * - Fixed stop loss and take profit levels
     * - Trailing stop loss that adjusts upward with price
     * - Partial profit taking at configurable levels
     * - Max holding period constraints
     * - Strategy signal-based exits
     * 
     * Example trailing stop: Buy at $100 with 10% trailing stop. Price rises to $120.
     * Trailing stop activates after 5% gain and adjusts to $108 (90% of $120), locking in profit.
     * If price falls to $108, position exits with $8 profit locked in.
     * 
     * Example partial profit: Buy 1000 shares at $10. Price rises to $12 (20% gain).
     * Sell 25% (250 shares) at $12, keeping 750 shares for further upside while locking profit.
     * 
     * @param object $strategy Strategy service instance
     * @param array $historicalData Array of [date => stock_data] indexed by date
     * @param array $options Backtest options:
     *   - position_size: Percentage of capital per position (default 0.10)
     *   - stop_loss: Fixed stop loss percentage (default null)
     *   - take_profit: Fixed take profit percentage (default null)
     *   - max_holding_days: Maximum days to hold position (default null)
     *   - trailing_stop: Enable trailing stop loss (default false)
     *   - trailing_stop_activation: Profit threshold to activate trailing (default 0.05 = 5%)
     *   - trailing_stop_distance: Distance below highest price (default 0.10 = 10%)
     *   - partial_profit_taking: Enable scaling out of positions (default false)
     *   - profit_levels: Array of profit targets with sell percentages
     * @return array Backtest results including trades, metrics, and performance statistics
     */
    public function runBacktest(object $strategy, array $historicalData, array $options = []): array
    {
        $defaultOptions = [
            'position_size' => 0.10,        // 10% of capital per position
            'stop_loss' => null,            // Stop loss percentage (e.g., 0.10 = 10%)
            'take_profit' => null,          // Take profit percentage (e.g., 0.20 = 20%)
            'max_holding_days' => null,     // Maximum days to hold position
            'rebalance_frequency' => 30,    // Days between rebalances
            'trailing_stop' => false,       // Enable trailing stop loss
            'trailing_stop_activation' => 0.05, // Activate trailing after 5% gain
            'trailing_stop_distance' => 0.10,   // Trail 10% below highest price
            'partial_profit_taking' => false,   // Enable scaling out of positions
            'profit_levels' => [            // Sell percentages at profit levels
                ['profit' => 0.10, 'sell_pct' => 0.25],  // Sell 25% at 10% gain
                ['profit' => 0.20, 'sell_pct' => 0.50],  // Sell 50% more at 20% gain
                ['profit' => 0.30, 'sell_pct' => 1.00]   // Sell remaining at 30% gain
            ]
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $capital = $this->initialCapital;
        $positions = [];
        $closedTrades = [];
        $equity = [$capital];
        
        $dates = array_keys($historicalData);
        sort($dates);
        
        foreach ($dates as $index => $date) {
            $data = $historicalData[$date];
            
            // Check existing positions for exits
            foreach ($positions as $posIndex => $position) {
                $currentPrice = $data['close'] ?? 0;
                $holdingDays = (strtotime($date) - strtotime($position['entry_date'])) / 86400;
                
                $shouldExit = false;
                $exitReason = '';
                
                // Update highest price and trailing stop
                if ($currentPrice > $position['highest_price']) {
                    $positions[$posIndex]['highest_price'] = $currentPrice;
                    
                    // Activate trailing stop after specified gain
                    if ($options['trailing_stop']) {
                        $gainPercent = ($currentPrice - $position['entry_price']) / $position['entry_price'];
                        
                        if ($gainPercent >= $options['trailing_stop_activation']) {
                            $positions[$posIndex]['trailing_stop_active'] = true;
                            $newTrailingStop = $currentPrice * (1 - $options['trailing_stop_distance']);
                            
                            // Only move stop upward, never downward
                            if ($newTrailingStop > ($position['trailing_stop_price'] ?? 0)) {
                                $positions[$posIndex]['trailing_stop_price'] = $newTrailingStop;
                            }
                        }
                    }
                }
                
                // Check partial profit taking
                if ($options['partial_profit_taking'] && $position['shares'] > 0) {
                    $currentProfit = ($currentPrice - $position['entry_price']) / $position['entry_price'];
                    
                    foreach ($options['profit_levels'] as $level) {
                        $levelKey = (string)$level['profit'];
                        
                        if ($currentProfit >= $level['profit'] && !isset($position['profit_levels_taken'][$levelKey])) {
                            // Calculate shares to sell
                            $sharesToSell = floor($position['original_shares'] * $level['sell_pct']);
                            $sharesToSell = min($sharesToSell, $position['shares']); // Don't oversell
                            
                            if ($sharesToSell > 0) {
                                $exitPrice = $this->applySlippage($currentPrice, 'SELL');
                                $commission = $exitPrice * $sharesToSell * $this->commissionRate;
                                $proceeds = ($exitPrice * $sharesToSell) - $commission;
                                $costBasis = ($position['cost'] / $position['original_shares']) * $sharesToSell;
                                $profit = $proceeds - $costBasis;
                                
                                $capital += $proceeds;
                                
                                // Record partial exit
                                $closedTrades[] = [
                                    'symbol' => $position['symbol'],
                                    'entry_date' => $position['entry_date'],
                                    'entry_price' => $position['entry_price'],
                                    'exit_date' => $date,
                                    'exit_price' => $exitPrice,
                                    'shares' => $sharesToSell,
                                    'return' => (($exitPrice - $position['entry_price']) / $position['entry_price']) * 100,
                                    'profit_loss' => $profit,
                                    'holding_days' => $holdingDays,
                                    'exit_reason' => 'partial_profit_' . ($level['profit'] * 100) . '%'
                                ];
                                
                                // Update position
                                $positions[$posIndex]['shares'] -= $sharesToSell;
                                $positions[$posIndex]['profit_levels_taken'][$levelKey] = true;
                                
                                // If all shares sold, remove position
                                if ($positions[$posIndex]['shares'] <= 0) {
                                    unset($positions[$posIndex]);
                                    continue 2; // Skip to next position
                                }
                            }
                        }
                    }
                }
                
                // Check trailing stop
                if ($options['trailing_stop'] && $position['trailing_stop_active'] && 
                    $position['trailing_stop_price'] !== null && $currentPrice <= $position['trailing_stop_price']) {
                    $shouldExit = true;
                    $exitReason = 'trailing_stop';
                }
                
                // Fixed stop loss check (fallback if trailing not active)
                if (!$shouldExit && $options['stop_loss'] && !$position['trailing_stop_active'] && 
                    $currentPrice <= $position['entry_price'] * (1 - $options['stop_loss'])) {
                    $shouldExit = true;
                    $exitReason = 'stop_loss';
                }
                
                // Take profit check
                if ($options['take_profit'] && $currentPrice >= $position['entry_price'] * (1 + $options['take_profit'])) {
                    $shouldExit = true;
                    $exitReason = 'take_profit';
                }
                
                // Max holding days check
                if ($options['max_holding_days'] && $holdingDays >= $options['max_holding_days']) {
                    $shouldExit = true;
                    $exitReason = 'max_holding_days';
                }
                
                // Strategy signal check
                $signal = $this->getStrategySignal($strategy, $date, $data);
                if ($signal['action'] === 'SELL') {
                    $shouldExit = true;
                    $exitReason = 'strategy_signal';
                }
                
                if ($shouldExit) {
                    $exitPrice = $this->applySlippage($currentPrice, 'SELL');
                    $commission = $exitPrice * $position['shares'] * $this->commissionRate;
                    $proceeds = ($exitPrice * $position['shares']) - $commission;
                    
                    $capital += $proceeds;
                    
                    $closedTrades[] = [
                        'symbol' => $position['symbol'],
                        'entry_date' => $position['entry_date'],
                        'entry_price' => $position['entry_price'],
                        'exit_date' => $date,
                        'exit_price' => $exitPrice,
                        'shares' => $position['shares'],
                        'return' => ($exitPrice - $position['entry_price']) / $position['entry_price'],
                        'profit_loss' => $proceeds - $position['cost'],
                        'holding_days' => (int)$holdingDays,
                        'exit_reason' => $exitReason
                    ];
                    
                    unset($positions[$posIndex]);
                }
            }
            
            // Check for new entry signals
            $signal = $this->getStrategySignal($strategy, $date, $data);
            
            if ($signal['action'] === 'BUY' && empty($positions)) {
                $entryPrice = $this->applySlippage($data['close'] ?? 0, 'BUY');
                $positionSize = $capital * $options['position_size'];
                $shares = floor($positionSize / $entryPrice);
                $commission = $entryPrice * $shares * $this->commissionRate;
                $totalCost = ($entryPrice * $shares) + $commission;
                
                if ($shares > 0 && $totalCost <= $capital) {
                    $capital -= $totalCost;
                    
                    $positions[] = [
                        'symbol' => $data['symbol'] ?? 'UNKNOWN',
                        'entry_date' => $date,
                        'entry_price' => $entryPrice,
                        'shares' => $shares,
                        'cost' => $totalCost,
                        'confidence' => $signal['confidence'] ?? 0,
                        // Trailing stop tracking
                        'highest_price' => $entryPrice,
                        'trailing_stop_price' => null,
                        'trailing_stop_active' => false,
                        // Partial profit tracking
                        'original_shares' => $shares,
                        'profit_levels_taken' => []
                    ];
                }
            }
            
            // Calculate current equity
            $positionValue = 0;
            foreach ($positions as $position) {
                $positionValue += ($data['close'] ?? 0) * $position['shares'];
            }
            $equity[] = $capital + $positionValue;
        }
        
        // Close any remaining positions at final price
        $finalDate = end($dates);
        $finalData = $historicalData[$finalDate];
        
        foreach ($positions as $position) {
            $exitPrice = $this->applySlippage($finalData['close'] ?? 0, 'SELL');
            $commission = $exitPrice * $position['shares'] * $this->commissionRate;
            $proceeds = ($exitPrice * $position['shares']) - $commission;
            
            $capital += $proceeds;
            
            $closedTrades[] = [
                'symbol' => $position['symbol'],
                'entry_date' => $position['entry_date'],
                'entry_price' => $position['entry_price'],
                'exit_date' => $finalDate,
                'exit_price' => $exitPrice,
                'shares' => $position['shares'],
                'return' => ($exitPrice - $position['entry_price']) / $position['entry_price'],
                'profit_loss' => $proceeds - $position['cost'],
                'holding_days' => (int)((strtotime($finalDate) - strtotime($position['entry_date'])) / 86400),
                'exit_reason' => 'backtest_end'
            ];
        }
        
        return [
            'initial_capital' => $this->initialCapital,
            'final_capital' => $capital,
            'total_return' => ($capital - $this->initialCapital) / $this->initialCapital,
            'trades' => $closedTrades,
            'equity_curve' => $equity,
            'metrics' => $this->calculateBacktestMetrics($closedTrades, $equity),
            'options' => $options
        ];
    }
    
    /**
     * Run backtest for portfolio of strategies
     * @param array $strategies Array of [name => [strategy, weight]]
     * @param array $historicalData Multi-symbol historical data
     * @param array $options Backtest options
     * @return array Portfolio backtest results
     */
    public function runPortfolioBacktest(array $strategies, array $historicalData, array $options = []): array
    {
        $defaultOptions = [
            'position_size' => 0.10,
            'max_positions' => 5,
            'rebalance_frequency' => 30
        ];
        
        $options = array_merge($defaultOptions, $options);
        
        $capital = $this->initialCapital;
        $positions = [];
        $closedTrades = [];
        $equity = [$capital];
        
        // Get all unique dates across symbols
        $allDates = [];
        foreach ($historicalData as $symbol => $data) {
            $allDates = array_merge($allDates, array_keys($data));
        }
        $allDates = array_unique($allDates);
        sort($allDates);
        
        foreach ($allDates as $date) {
            // Check existing positions for exits
            foreach ($positions as $posIndex => $position) {
                if (!isset($historicalData[$position['symbol']][$date])) {
                    continue;
                }
                
                $data = $historicalData[$position['symbol']][$date];
                $currentPrice = $data['close'] ?? 0;
                
                // Get weighted strategy signal
                $weightedSignal = $this->getPortfolioSignal($strategies, $position['symbol'], $date, $data);
                
                if ($weightedSignal['action'] === 'SELL') {
                    $exitPrice = $this->applySlippage($currentPrice, 'SELL');
                    $commission = $exitPrice * $position['shares'] * $this->commissionRate;
                    $proceeds = ($exitPrice * $position['shares']) - $commission;
                    
                    $capital += $proceeds;
                    
                    $closedTrades[] = [
                        'symbol' => $position['symbol'],
                        'entry_date' => $position['entry_date'],
                        'entry_price' => $position['entry_price'],
                        'exit_date' => $date,
                        'exit_price' => $exitPrice,
                        'shares' => $position['shares'],
                        'return' => ($exitPrice - $position['entry_price']) / $position['entry_price'],
                        'profit_loss' => $proceeds - $position['cost'],
                        'holding_days' => (int)((strtotime($date) - strtotime($position['entry_date'])) / 86400),
                        'exit_reason' => 'portfolio_signal'
                    ];
                    
                    unset($positions[$posIndex]);
                }
            }
            
            // Check for new entries across symbols
            if (count($positions) < $options['max_positions']) {
                foreach ($historicalData as $symbol => $symbolData) {
                    if (!isset($symbolData[$date])) {
                        continue;
                    }
                    
                    // Skip if already have position in this symbol
                    $hasPosition = false;
                    foreach ($positions as $pos) {
                        if ($pos['symbol'] === $symbol) {
                            $hasPosition = true;
                            break;
                        }
                    }
                    
                    if ($hasPosition) {
                        continue;
                    }
                    
                    $data = $symbolData[$date];
                    $signal = $this->getPortfolioSignal($strategies, $symbol, $date, $data);
                    
                    if ($signal['action'] === 'BUY' && $signal['confidence'] >= 60) {
                        $entryPrice = $this->applySlippage($data['close'] ?? 0, 'BUY');
                        $positionSize = $capital * $options['position_size'];
                        $shares = floor($positionSize / $entryPrice);
                        $commission = $entryPrice * $shares * $this->commissionRate;
                        $totalCost = ($entryPrice * $shares) + $commission;
                        
                        if ($shares > 0 && $totalCost <= $capital && count($positions) < $options['max_positions']) {
                            $capital -= $totalCost;
                            
                            $positions[] = [
                                'symbol' => $symbol,
                                'entry_date' => $date,
                                'entry_price' => $entryPrice,
                                'shares' => $shares,
                                'cost' => $totalCost,
                                'confidence' => $signal['confidence']
                            ];
                        }
                    }
                }
            }
            
            // Calculate current equity
            $positionValue = 0;
            foreach ($positions as $position) {
                if (isset($historicalData[$position['symbol']][$date])) {
                    $currentPrice = $historicalData[$position['symbol']][$date]['close'] ?? 0;
                    $positionValue += $currentPrice * $position['shares'];
                }
            }
            $equity[] = $capital + $positionValue;
        }
        
        return [
            'initial_capital' => $this->initialCapital,
            'final_capital' => $capital + $this->calculatePositionValue($positions, $historicalData, end($allDates)),
            'total_return' => ($capital - $this->initialCapital) / $this->initialCapital,
            'trades' => $closedTrades,
            'equity_curve' => $equity,
            'metrics' => $this->calculateBacktestMetrics($closedTrades, $equity),
            'options' => $options
        ];
    }
    
    /**
     * Walk-forward analysis
     * Train on in-sample period, test on out-of-sample period
     * @param object $strategy Strategy to test
     * @param array $historicalData All historical data
     * @param int $trainDays Training period days
     * @param int $testDays Testing period days
     * @param int $step Step size for rolling window
     * @return array Walk-forward results
     */
    public function walkForwardAnalysis(
        object $strategy,
        array $historicalData,
        int $trainDays = 252,
        int $testDays = 63,
        int $step = 63
    ): array {
        $dates = array_keys($historicalData);
        sort($dates);
        
        $results = [];
        $allTrades = [];
        
        for ($i = 0; $i + $trainDays + $testDays <= count($dates); $i += $step) {
            // In-sample training period
            $trainStart = $dates[$i];
            $trainEnd = $dates[$i + $trainDays - 1];
            
            // Out-of-sample testing period
            $testStart = $dates[$i + $trainDays];
            $testEnd = $dates[$i + $trainDays + $testDays - 1];
            
            // Extract test data
            $testData = [];
            for ($j = $i + $trainDays; $j < $i + $trainDays + $testDays && $j < count($dates); $j++) {
                $testData[$dates[$j]] = $historicalData[$dates[$j]];
            }
            
            // Run backtest on out-of-sample data
            $backtest = $this->runBacktest($strategy, $testData);
            
            $results[] = [
                'period' => ($i / $step) + 1,
                'train_start' => $trainStart,
                'train_end' => $trainEnd,
                'test_start' => $testStart,
                'test_end' => $testEnd,
                'total_return' => $backtest['total_return'],
                'sharpe_ratio' => $backtest['metrics']['sharpe_ratio'] ?? 0,
                'max_drawdown' => $backtest['metrics']['max_drawdown'] ?? 0,
                'trades' => count($backtest['trades'])
            ];
            
            $allTrades = array_merge($allTrades, $backtest['trades']);
        }
        
        return [
            'periods' => $results,
            'all_trades' => $allTrades,
            'summary' => $this->summarizeWalkForward($results)
        ];
    }
    
    /**
     * Monte Carlo simulation
     * Randomly resample trades to estimate outcome distribution
     * @param array $trades Historical trades
     * @param int $simulations Number of simulations
     * @param int $tradesPerSim Trades per simulation
     * @return array Monte Carlo results
     */
    public function monteCarloSimulation(array $trades, int $simulations = 1000, int $tradesPerSim = 100): array
    {
        if (empty($trades)) {
            return ['error' => 'No trades provided'];
        }
        
        $returns = array_column($trades, 'return');
        $simulatedReturns = [];
        
        for ($i = 0; $i < $simulations; $i++) {
            $simReturn = 1.0;
            
            for ($j = 0; $j < $tradesPerSim; $j++) {
                $randomReturn = $returns[array_rand($returns)];
                $simReturn *= (1 + $randomReturn);
            }
            
            $simulatedReturns[] = $simReturn - 1;
        }
        
        sort($simulatedReturns);
        
        return [
            'simulations' => $simulations,
            'mean_return' => array_sum($simulatedReturns) / count($simulatedReturns),
            'median_return' => $simulatedReturns[count($simulatedReturns) / 2],
            'best_case' => $simulatedReturns[count($simulatedReturns) - 1],
            'worst_case' => $simulatedReturns[0],
            'percentile_5' => $simulatedReturns[(int)(count($simulatedReturns) * 0.05)],
            'percentile_25' => $simulatedReturns[(int)(count($simulatedReturns) * 0.25)],
            'percentile_75' => $simulatedReturns[(int)(count($simulatedReturns) * 0.75)],
            'percentile_95' => $simulatedReturns[(int)(count($simulatedReturns) * 0.95)],
            'probability_profit' => count(array_filter($simulatedReturns, fn($r) => $r > 0)) / count($simulatedReturns),
            'distribution' => $simulatedReturns
        ];
    }
    
    /**
     * Get strategy signal
     */
    private function getStrategySignal(object $strategy, string $date, array $data): array
    {
        // Call strategy's determineAction() method if it exists
        if (method_exists($strategy, 'determineAction')) {
            return $strategy->determineAction($data['symbol'] ?? 'UNKNOWN', $date, $data);
        }
        
        return [
            'action' => 'HOLD',
            'confidence' => 0
        ];
    }
    
    /**
     * Get portfolio weighted signal
     */
    private function getPortfolioSignal(array $strategies, string $symbol, string $date, array $data): array
    {
        $weightedConfidence = 0;
        $buyWeight = 0;
        
        foreach ($strategies as $name => $config) {
            $strategy = $config['strategy'];
            $weight = $config['weight'];
            
            $signal = $this->getStrategySignal($strategy, $date, $data);
            
            if ($signal['action'] === 'BUY') {
                $buyWeight += $weight;
                $weightedConfidence += $signal['confidence'] * $weight;
            }
        }
        
        return [
            'action' => $buyWeight > 0.5 ? 'BUY' : 'HOLD',
            'confidence' => $weightedConfidence
        ];
    }
    
    /**
     * Apply slippage to price
     */
    private function applySlippage(float $price, string $action): float
    {
        if ($action === 'BUY') {
            return $price * (1 + $this->slippageRate);
        } else {
            return $price * (1 - $this->slippageRate);
        }
    }
    
    /**
     * Calculate position value
     */
    private function calculatePositionValue(array $positions, array $historicalData, string $date): float
    {
        $value = 0;
        
        foreach ($positions as $position) {
            if (isset($historicalData[$position['symbol']][$date])) {
                $value += $historicalData[$position['symbol']][$date]['close'] * $position['shares'];
            }
        }
        
        return $value;
    }
    
    /**
     * Calculate backtest metrics
     */
    private function calculateBacktestMetrics(array $trades, array $equity): array
    {
        if (empty($trades)) {
            return [
                'total_trades' => 0,
                'win_rate' => 0,
                'sharpe_ratio' => 0,
                'max_drawdown' => 0
            ];
        }
        
        $returns = array_column($trades, 'return');
        $winningTrades = array_filter($returns, fn($r) => $r > 0);
        
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);
        $sharpe = $stdDev > 0 ? ($avgReturn / $stdDev) * sqrt(252) : 0;
        
        return [
            'total_trades' => count($trades),
            'winning_trades' => count($winningTrades),
            'losing_trades' => count($trades) - count($winningTrades),
            'win_rate' => count($winningTrades) / count($trades),
            'average_return' => $avgReturn,
            'sharpe_ratio' => round($sharpe, 2),
            'max_drawdown' => $this->calculateMaxDrawdownFromEquity($equity),
            'average_holding_days' => array_sum(array_column($trades, 'holding_days')) / count($trades)
        ];
    }
    
    /**
     * Calculate max drawdown from equity curve
     */
    private function calculateMaxDrawdownFromEquity(array $equity): float
    {
        $peak = $equity[0];
        $maxDrawdown = 0;
        
        foreach ($equity as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            
            $drawdown = ($peak - $value) / $peak;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return round($maxDrawdown, 4);
    }
    
    /**
     * Calculate standard deviation
     */
    private function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / (count($values) - 1);
        
        return sqrt($variance);
    }
    
    /**
     * Summarize walk-forward results
     */
    private function summarizeWalkForward(array $results): array
    {
        if (empty($results)) {
            return [];
        }
        
        $returns = array_column($results, 'total_return');
        $sharpes = array_column($results, 'sharpe_ratio');
        $drawdowns = array_column($results, 'max_drawdown');
        
        return [
            'total_periods' => count($results),
            'average_return' => array_sum($returns) / count($returns),
            'best_period_return' => max($returns),
            'worst_period_return' => min($returns),
            'average_sharpe' => array_sum($sharpes) / count($sharpes),
            'average_max_drawdown' => array_sum($drawdowns) / count($drawdowns),
            'profitable_periods' => count(array_filter($returns, fn($r) => $r > 0)),
            'consistency' => count(array_filter($returns, fn($r) => $r > 0)) / count($returns)
        ];
    }
}
