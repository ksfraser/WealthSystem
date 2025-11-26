<?php
namespace Ksfraser\Finance\Services;

use Ksfraser\Finance\Interfaces\DataRepositoryInterface;
use Ksfraser\Finance\Services\StockDataService;

class BacktestingService
{
    private DataRepositoryInterface $repository;
    private StockDataService $stockDataService;

    public function __construct(DataRepositoryInterface $repository, StockDataService $stockDataService)
    {
        $this->repository = $repository;
        $this->stockDataService = $stockDataService;
    }

    public function runBacktest(
        int $strategyId,
        string $symbol,
        string $startDate,
        string $endDate,
        float $initialCapital,
        array $parameters = []
    ): array {
        try {
            // Get strategy details
            $strategySql = "SELECT * FROM trading_strategies WHERE id = ?";
            $strategy = $this->repository->query($strategySql, [$strategyId])[0] ?? null;
            
            if (!$strategy) {
                throw new \Exception("Strategy not found: {$strategyId}");
            }

            // Get historical data for the period
            $marketData = $this->getHistoricalDataForPeriod($symbol, $startDate, $endDate);
            
            if (empty($marketData)) {
                throw new \Exception("No market data available for {$symbol} in the specified period");
            }

            // Merge strategy parameters with custom parameters
            $strategyParams = json_decode($strategy['parameters'], true) ?? [];
            $mergedParams = array_merge($strategyParams, $parameters);

            // Run the backtest simulation
            $backtestResult = $this->simulateStrategy($strategy, $symbol, $marketData, $mergedParams, $initialCapital);

            // Calculate performance metrics
            $performanceMetrics = $this->calculatePerformanceMetrics($backtestResult, $initialCapital);

            // Save backtest results
            $backtestId = $this->saveBacktestResults($strategyId, $symbol, $startDate, $endDate, $initialCapital, $performanceMetrics, $mergedParams);

            // Save individual trades
            $this->saveBacktestTrades($backtestId, $backtestResult['trades']);

            return [
                'backtest_id' => $backtestId,
                'strategy' => $strategy,
                'symbol' => $symbol,
                'period' => ['start' => $startDate, 'end' => $endDate],
                'initial_capital' => $initialCapital,
                'final_capital' => $performanceMetrics['final_capital'],
                'performance' => $performanceMetrics,
                'trades' => $backtestResult['trades'],
                'equity_curve' => $backtestResult['equity_curve']
            ];

        } catch (\Exception $e) {
            throw new \Exception("Backtest failed: " . $e->getMessage());
        }
    }

    private function simulateStrategy(array $strategy, string $symbol, array $marketData, array $params, float $initialCapital): array
    {
        $trades = [];
        $equityCurve = [];
        $currentCapital = $initialCapital;
        $position = null; // Current open position
        $entryPrice = 0;
        $entryDate = '';
        $positionSize = 0;

        foreach ($marketData as $index => $dataPoint) {
            $date = $dataPoint['date'];
            $price = $dataPoint['close'];
            
            // Add to equity curve
            $equityValue = $currentCapital;
            if ($position) {
                $unrealizedPnl = ($price - $entryPrice) * $positionSize * ($position === 'LONG' ? 1 : -1);
                $equityValue += $unrealizedPnl;
            }
            $equityCurve[] = ['date' => $date, 'equity' => $equityValue];

            // Get signal from strategy
            $signal = $this->getStrategySignal($strategy, $symbol, array_slice($marketData, 0, $index + 1), $params);

            if ($signal) {
                if (!$position && in_array($signal['action'], ['BUY', 'SHORT'])) {
                    // Open new position
                    $riskAmount = $currentCapital * ($params['unit_risk'] ?? 0.02); // 2% default risk
                    $stopDistance = abs($price - ($signal['stop_loss'] ?? $price * 0.98));
                    
                    if ($stopDistance > 0) {
                        $positionSize = floor($riskAmount / $stopDistance);
                        
                        if ($positionSize > 0) {
                            $position = $signal['action'] === 'BUY' ? 'LONG' : 'SHORT';
                            $entryPrice = $price;
                            $entryDate = $date;
                            
                            // Deduct commission
                            $commission = $params['commission'] ?? 1.0;
                            $currentCapital -= $commission;
                        }
                    }
                } elseif ($position && in_array($signal['action'], ['SELL', 'COVER'])) {
                    // Close position
                    if (($position === 'LONG' && $signal['action'] === 'SELL') ||
                        ($position === 'SHORT' && $signal['action'] === 'COVER')) {
                        
                        $exitPrice = $price;
                        $pnl = ($exitPrice - $entryPrice) * $positionSize * ($position === 'LONG' ? 1 : -1);
                        $commission = $params['commission'] ?? 1.0;
                        $netPnl = $pnl - $commission;
                        
                        $currentCapital += $netPnl;
                        
                        // Record trade
                        $trades[] = [
                            'symbol' => $symbol,
                            'entry_date' => $entryDate,
                            'exit_date' => $date,
                            'entry_price' => $entryPrice,
                            'exit_price' => $exitPrice,
                            'quantity' => $positionSize,
                            'trade_type' => $position,
                            'entry_signal' => 'Strategy Entry',
                            'exit_signal' => $signal['reasoning'] ?? 'Strategy Exit',
                            'pnl' => $netPnl,
                            'pnl_percentage' => $entryPrice > 0 ? ($pnl / ($entryPrice * $positionSize)) * 100 : 0,
                            'duration_days' => $this->calculateDaysBetween($entryDate, $date),
                            'commission' => $commission * 2 // Entry + Exit
                        ];
                        
                        // Reset position
                        $position = null;
                        $entryPrice = 0;
                        $positionSize = 0;
                    }
                }
            }

            // Check for stop-loss or take-profit if position is open
            if ($position) {
                $stopLoss = $signal['stop_loss'] ?? null;
                $takeProfit = $signal['take_profit'] ?? null;
                
                $shouldExit = false;
                $exitReason = '';
                
                if ($position === 'LONG') {
                    if ($stopLoss && $price <= $stopLoss) {
                        $shouldExit = true;
                        $exitReason = 'Stop Loss';
                    } elseif ($takeProfit && $price >= $takeProfit) {
                        $shouldExit = true;
                        $exitReason = 'Take Profit';
                    }
                } else { // SHORT
                    if ($stopLoss && $price >= $stopLoss) {
                        $shouldExit = true;
                        $exitReason = 'Stop Loss';
                    } elseif ($takeProfit && $price <= $takeProfit) {
                        $shouldExit = true;
                        $exitReason = 'Take Profit';
                    }
                }
                
                if ($shouldExit) {
                    $exitPrice = $price;
                    $pnl = ($exitPrice - $entryPrice) * $positionSize * ($position === 'LONG' ? 1 : -1);
                    $commission = $params['commission'] ?? 1.0;
                    $netPnl = $pnl - $commission;
                    
                    $currentCapital += $netPnl;
                    
                    $trades[] = [
                        'symbol' => $symbol,
                        'entry_date' => $entryDate,
                        'exit_date' => $date,
                        'entry_price' => $entryPrice,
                        'exit_price' => $exitPrice,
                        'quantity' => $positionSize,
                        'trade_type' => $position,
                        'entry_signal' => 'Strategy Entry',
                        'exit_signal' => $exitReason,
                        'pnl' => $netPnl,
                        'pnl_percentage' => $entryPrice > 0 ? ($pnl / ($entryPrice * $positionSize)) * 100 : 0,
                        'duration_days' => $this->calculateDaysBetween($entryDate, $date),
                        'commission' => $commission * 2
                    ];
                    
                    $position = null;
                    $entryPrice = 0;
                    $positionSize = 0;
                }
            }
        }

        return [
            'trades' => $trades,
            'equity_curve' => $equityCurve,
            'final_capital' => $currentCapital
        ];
    }

    private function getStrategySignal(array $strategy, string $symbol, array $marketData, array $params): ?array
    {
        // Simplified strategy logic - in practice, this would call the actual strategy classes
        switch ($strategy['strategy_type']) {
            case 'turtle':
                return $this->getTurtleSignal($marketData, $params);
            case 'support_resistance':
                return $this->getSupportResistanceSignal($marketData, $params);
            case 'technical_analysis':
                return $this->getTechnicalAnalysisSignal($marketData, $params);
            default:
                return null;
        }
    }

    private function getTurtleSignal(array $marketData, array $params): ?array
    {
        $entryDays = $params['entry_days'] ?? 20;
        $exitDays = $params['exit_days'] ?? 10;
        
        if (count($marketData) < $entryDays) {
            return null;
        }
        
        $latest = end($marketData);
        $highs = array_column(array_slice($marketData, -$entryDays), 'high');
        $lows = array_column(array_slice($marketData, -$exitDays), 'low');
        
        $highestHigh = max($highs);
        $lowestLow = min($lows);
        
        if ($latest['close'] > $highestHigh) {
            $atr = $this->calculateATR(array_slice($marketData, -20));
            return [
                'action' => 'BUY',
                'price' => $latest['close'],
                'stop_loss' => $latest['close'] - (2 * $atr),
                'reasoning' => 'Turtle entry signal'
            ];
        } elseif ($latest['close'] < $lowestLow) {
            return [
                'action' => 'SELL',
                'price' => $latest['close'],
                'reasoning' => 'Turtle exit signal'
            ];
        }
        
        return null;
    }

    private function getSupportResistanceSignal(array $marketData, array $params): ?array
    {
        // Simplified support/resistance logic
        $lookback = $params['lookback_period'] ?? 50;
        
        if (count($marketData) < $lookback) {
            return null;
        }
        
        $latest = end($marketData);
        $recentData = array_slice($marketData, -$lookback);
        $lows = array_column($recentData, 'low');
        $supportLevel = min($lows);
        
        $distanceFromSupport = abs($latest['close'] - $supportLevel) / $supportLevel;
        
        if ($distanceFromSupport <= 0.02) { // Within 2% of support
            return [
                'action' => 'BUY',
                'price' => $latest['close'],
                'stop_loss' => $supportLevel * 0.98,
                'reasoning' => 'Support level buy'
            ];
        }
        
        return null;
    }

    private function getTechnicalAnalysisSignal(array $marketData, array $params): ?array
    {
        $fastPeriod = $params['fast_period'] ?? 20;
        $slowPeriod = $params['slow_period'] ?? 50;
        
        if (count($marketData) < $slowPeriod) {
            return null;
        }
        
        $closes = array_column($marketData, 'close');
        $fastMA = array_sum(array_slice($closes, -$fastPeriod)) / $fastPeriod;
        $slowMA = array_sum(array_slice($closes, -$slowPeriod)) / $slowPeriod;
        
        $latest = end($marketData);
        
        if ($fastMA > $slowMA && $latest['close'] > $fastMA) {
            return [
                'action' => 'BUY',
                'price' => $latest['close'],
                'stop_loss' => $slowMA,
                'reasoning' => 'MA crossover buy'
            ];
        } elseif ($fastMA < $slowMA && $latest['close'] < $fastMA) {
            return [
                'action' => 'SELL',
                'price' => $latest['close'],
                'reasoning' => 'MA crossover sell'
            ];
        }
        
        return null;
    }

    private function calculateATR(array $marketData): float
    {
        if (count($marketData) < 2) {
            return 0;
        }

        $trueRanges = [];
        for ($i = 1; $i < count($marketData); $i++) {
            $current = $marketData[$i];
            $previous = $marketData[$i - 1];

            $tr1 = $current['high'] - $current['low'];
            $tr2 = abs($current['high'] - $previous['close']);
            $tr3 = abs($current['low'] - $previous['close']);

            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum($trueRanges) / count($trueRanges);
    }

    private function calculatePerformanceMetrics(array $backtestResult, float $initialCapital): array
    {
        $trades = $backtestResult['trades'];
        $equityCurve = $backtestResult['equity_curve'];
        $finalCapital = $backtestResult['final_capital'];

        if (empty($trades)) {
            return [
                'final_capital' => $finalCapital,
                'total_return' => 0,
                'total_trades' => 0,
                'winning_trades' => 0,
                'losing_trades' => 0,
                'win_rate' => 0,
                'profit_factor' => 0,
                'sharpe_ratio' => 0,
                'max_drawdown' => 0
            ];
        }

        $totalReturn = (($finalCapital - $initialCapital) / $initialCapital) * 100;
        $winningTrades = array_filter($trades, fn($trade) => $trade['pnl'] > 0);
        $losingTrades = array_filter($trades, fn($trade) => $trade['pnl'] <= 0);
        
        $totalWins = array_sum(array_column($winningTrades, 'pnl'));
        $totalLosses = abs(array_sum(array_column($losingTrades, 'pnl')));
        
        $winRate = count($trades) > 0 ? (count($winningTrades) / count($trades)) * 100 : 0;
        $profitFactor = $totalLosses > 0 ? $totalWins / $totalLosses : 0;
        
        // Calculate Sharpe ratio (simplified)
        $returns = [];
        for ($i = 1; $i < count($equityCurve); $i++) {
            $prevEquity = $equityCurve[$i - 1]['equity'];
            $currentEquity = $equityCurve[$i]['equity'];
            if ($prevEquity > 0) {
                $returns[] = ($currentEquity - $prevEquity) / $prevEquity;
            }
        }
        
        $avgReturn = count($returns) > 0 ? array_sum($returns) / count($returns) : 0;
        $stdDev = $this->calculateStandardDeviation($returns);
        $sharpeRatio = $stdDev > 0 ? ($avgReturn * sqrt(252)) / ($stdDev * sqrt(252)) : 0; // Annualized
        
        // Calculate maximum drawdown
        $maxDrawdown = $this->calculateMaxDrawdown($equityCurve);

        return [
            'final_capital' => $finalCapital,
            'total_return' => $totalReturn,
            'total_trades' => count($trades),
            'winning_trades' => count($winningTrades),
            'losing_trades' => count($losingTrades),
            'win_rate' => $winRate,
            'profit_factor' => $profitFactor,
            'avg_winning_trade' => count($winningTrades) > 0 ? $totalWins / count($winningTrades) : 0,
            'avg_losing_trade' => count($losingTrades) > 0 ? $totalLosses / count($losingTrades) : 0,
            'sharpe_ratio' => $sharpeRatio,
            'max_drawdown' => $maxDrawdown
        ];
    }

    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        
        return sqrt(array_sum($squaredDiffs) / count($values));
    }

    private function calculateMaxDrawdown(array $equityCurve): float
    {
        $maxDrawdown = 0;
        $peak = 0;
        
        foreach ($equityCurve as $point) {
            $equity = $point['equity'];
            if ($equity > $peak) {
                $peak = $equity;
            }
            
            $drawdown = ($peak - $equity) / $peak * 100;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return $maxDrawdown;
    }

    private function calculateDaysBetween(string $startDate, string $endDate): int
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        return $start->diff($end)->days;
    }

    private function getHistoricalDataForPeriod(string $symbol, string $startDate, string $endDate): array
    {
        $sql = "
            SELECT date, open_price as open, high_price as high, low_price as low, close_price as close, volume
            FROM market_data 
            WHERE symbol = ? AND date BETWEEN ? AND ?
            ORDER BY date ASC
        ";
        
        return $this->repository->query($sql, [$symbol, $startDate, $endDate]);
    }

    private function saveBacktestResults(int $strategyId, string $symbol, string $startDate, string $endDate, float $initialCapital, array $metrics, array $params): int
    {
        $sql = "
            INSERT INTO backtesting_results 
            (strategy_id, symbol, start_date, end_date, initial_capital, final_capital, total_return, 
             sharpe_ratio, max_drawdown, win_rate, total_trades, winning_trades, losing_trades,
             avg_winning_trade, avg_losing_trade, profit_factor, parameters)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $this->repository->execute($sql, [
            $strategyId,
            $symbol,
            $startDate,
            $endDate,
            $initialCapital,
            $metrics['final_capital'],
            $metrics['total_return'],
            $metrics['sharpe_ratio'],
            $metrics['max_drawdown'],
            $metrics['win_rate'],
            $metrics['total_trades'],
            $metrics['winning_trades'],
            $metrics['losing_trades'],
            $metrics['avg_winning_trade'],
            $metrics['avg_losing_trade'],
            $metrics['profit_factor'],
            json_encode($params)
        ]);
        
        // Get the inserted ID
        $result = $this->repository->query("SELECT LAST_INSERT_ID() as id");
        return $result[0]['id'];
    }

    private function saveBacktestTrades(int $backtestId, array $trades): void
    {
        foreach ($trades as $trade) {
            $sql = "
                INSERT INTO backtest_trades 
                (backtest_id, symbol, entry_date, exit_date, entry_price, exit_price, quantity,
                 trade_type, entry_signal, exit_signal, pnl, pnl_percentage, duration_days, commission)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->repository->execute($sql, [
                $backtestId,
                $trade['symbol'],
                $trade['entry_date'],
                $trade['exit_date'],
                $trade['entry_price'],
                $trade['exit_price'],
                $trade['quantity'],
                $trade['trade_type'],
                $trade['entry_signal'],
                $trade['exit_signal'],
                $trade['pnl'],
                $trade['pnl_percentage'],
                $trade['duration_days'],
                $trade['commission']
            ]);
        }
    }

    public function getStrategyPerformance(int $strategyId): array
    {
        $sql = "
            SELECT 
                COUNT(*) as total_backtests,
                AVG(total_return) as avg_return,
                AVG(sharpe_ratio) as avg_sharpe,
                AVG(max_drawdown) as avg_drawdown,
                AVG(win_rate) as avg_win_rate,
                MAX(total_return) as best_return,
                MIN(total_return) as worst_return
            FROM backtesting_results 
            WHERE strategy_id = ?
        ";
        
        return $this->repository->query($sql, [$strategyId])[0] ?? [];
    }

    public function getBacktestHistory(int $limit = 20): array
    {
        $sql = "
            SELECT 
                br.*,
                ts.name as strategy_name
            FROM backtesting_results br
            JOIN trading_strategies ts ON br.strategy_id = ts.id
            ORDER BY br.created_at DESC
            LIMIT ?
        ";
        
        return $this->repository->query($sql, [$limit]);
    }
}
