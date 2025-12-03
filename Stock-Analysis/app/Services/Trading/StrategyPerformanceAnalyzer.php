<?php

namespace App\Services\Trading;

class StrategyPerformanceAnalyzer
{
    private array $tradeHistory = [];
    
    /**
     * Record a trade execution
     * @param string $symbol Stock symbol
     * @param string $strategy Strategy name
     * @param array $trade Trade details (entry_date, entry_price, exit_date, exit_price, action, confidence)
     */
    public function recordTrade(string $symbol, string $strategy, array $trade): void
    {
        $return = 0;
        if (isset($trade['entry_price'], $trade['exit_price'])) {
            $return = ($trade['exit_price'] - $trade['entry_price']) / $trade['entry_price'];
        }
        
        $this->tradeHistory[] = [
            'symbol' => $symbol,
            'strategy' => $strategy,
            'entry_date' => $trade['entry_date'] ?? null,
            'entry_price' => $trade['entry_price'] ?? 0,
            'exit_date' => $trade['exit_date'] ?? null,
            'exit_price' => $trade['exit_price'] ?? 0,
            'action' => $trade['action'] ?? 'BUY',
            'confidence' => $trade['confidence'] ?? 0,
            'return' => $return,
            'profit_loss' => ($trade['exit_price'] ?? 0) - ($trade['entry_price'] ?? 0),
            'holding_days' => $this->calculateHoldingDays($trade['entry_date'] ?? null, $trade['exit_date'] ?? null)
        ];
    }
    
    /**
     * Load trade history from array
     */
    public function loadTradeHistory(array $trades): void
    {
        foreach ($trades as $trade) {
            $this->recordTrade(
                $trade['symbol'],
                $trade['strategy'],
                $trade
            );
        }
    }
    
    /**
     * Calculate performance metrics for a specific strategy
     * @param string $strategy Strategy name (or 'all' for overall)
     * @return array Performance metrics
     */
    public function analyzeStrategy(string $strategy = 'all'): array
    {
        $trades = $strategy === 'all' 
            ? $this->tradeHistory 
            : array_filter($this->tradeHistory, fn($t) => $t['strategy'] === $strategy);
        
        if (empty($trades)) {
            return $this->getEmptyMetrics();
        }
        
        $returns = array_column($trades, 'return');
        $profitLosses = array_column($trades, 'profit_loss');
        
        $winningTrades = array_filter($returns, fn($r) => $r > 0);
        $losingTrades = array_filter($returns, fn($r) => $r < 0);
        
        $totalTrades = count($trades);
        $winCount = count($winningTrades);
        $lossCount = count($losingTrades);
        
        $winRate = $totalTrades > 0 ? $winCount / $totalTrades : 0;
        $avgReturn = $totalTrades > 0 ? array_sum($returns) / $totalTrades : 0;
        $avgWin = $winCount > 0 ? array_sum($winningTrades) / $winCount : 0;
        $avgLoss = $lossCount > 0 ? abs(array_sum($losingTrades) / $lossCount) : 0;
        
        $profitFactor = 0;
        if ($lossCount > 0) {
            $totalWins = array_sum(array_filter($profitLosses, fn($p) => $p > 0));
            $totalLosses = abs(array_sum(array_filter($profitLosses, fn($p) => $p < 0)));
            $profitFactor = $totalLosses > 0 ? $totalWins / $totalLosses : 0;
        }
        
        return [
            'strategy' => $strategy,
            'total_trades' => $totalTrades,
            'winning_trades' => $winCount,
            'losing_trades' => $lossCount,
            'win_rate' => round($winRate, 4),
            'average_return' => round($avgReturn, 4),
            'average_win' => round($avgWin, 4),
            'average_loss' => round($avgLoss, 4),
            'profit_factor' => round($profitFactor, 2),
            'sharpe_ratio' => $this->calculateSharpeRatio($returns),
            'max_drawdown' => $this->calculateMaxDrawdown($returns),
            'total_return' => round(array_sum($returns), 4),
            'best_trade' => !empty($returns) ? round(max($returns), 4) : 0,
            'worst_trade' => !empty($returns) ? round(min($returns), 4) : 0,
            'average_holding_days' => $this->calculateAverageHoldingDays($trades),
            'expectancy' => $this->calculateExpectancy($winRate, $avgWin, $avgLoss)
        ];
    }
    
    /**
     * Compare performance across all strategies
     * @return array Array of strategy metrics sorted by Sharpe ratio
     */
    public function compareStrategies(): array
    {
        $strategies = array_unique(array_column($this->tradeHistory, 'strategy'));
        $comparison = [];
        
        foreach ($strategies as $strategy) {
            $comparison[] = $this->analyzeStrategy($strategy);
        }
        
        // Sort by Sharpe ratio descending
        usort($comparison, fn($a, $b) => $b['sharpe_ratio'] <=> $a['sharpe_ratio']);
        
        return $comparison;
    }
    
    /**
     * Calculate correlation matrix between strategies
     * @return array Correlation coefficients between strategy returns
     */
    public function calculateStrategyCorrelations(): array
    {
        $strategies = array_unique(array_column($this->tradeHistory, 'strategy'));
        
        if (count($strategies) < 2) {
            return [];
        }
        
        // Group returns by strategy and date
        $strategyReturns = [];
        foreach ($strategies as $strategy) {
            $trades = array_filter($this->tradeHistory, fn($t) => $t['strategy'] === $strategy);
            $strategyReturns[$strategy] = array_column($trades, 'return');
        }
        
        // Calculate correlations
        $correlations = [];
        foreach ($strategies as $strategy1) {
            foreach ($strategies as $strategy2) {
                if ($strategy1 === $strategy2) {
                    $correlations[$strategy1][$strategy2] = 1.0;
                } else {
                    $correlations[$strategy1][$strategy2] = $this->calculateCorrelation(
                        $strategyReturns[$strategy1] ?? [],
                        $strategyReturns[$strategy2] ?? []
                    );
                }
            }
        }
        
        return $correlations;
    }
    
    /**
     * Find optimal strategy combination for diversification
     * @param int $maxStrategies Maximum number of strategies to combine
     * @return array Recommended strategy combination with weights
     */
    public function findOptimalCombination(int $maxStrategies = 4): array
    {
        $strategies = $this->compareStrategies();
        
        if (empty($strategies)) {
            return [];
        }
        
        // Filter strategies with positive expectancy and decent win rate
        $viableStrategies = array_filter($strategies, function($s) {
            return $s['expectancy'] > 0 && $s['win_rate'] >= 0.40 && $s['sharpe_ratio'] > 0.5;
        });
        
        if (empty($viableStrategies)) {
            // Fall back to top strategies by Sharpe ratio
            $viableStrategies = array_slice($strategies, 0, $maxStrategies);
        }
        
        // Limit to maxStrategies
        $selected = array_slice($viableStrategies, 0, $maxStrategies);
        
        // Calculate correlations between selected strategies
        $correlations = $this->calculateStrategyCorrelations();
        
        // Weight strategies inversely to their average correlation with others
        $weights = [];
        $totalScore = 0;
        
        foreach ($selected as $strategy) {
            $name = $strategy['strategy'];
            
            // Score = Sharpe ratio * (1 - average correlation with other selected)
            $avgCorrelation = 0;
            $correlationCount = 0;
            
            foreach ($selected as $otherStrategy) {
                $otherName = $otherStrategy['strategy'];
                if ($name !== $otherName && isset($correlations[$name][$otherName])) {
                    $avgCorrelation += abs($correlations[$name][$otherName]);
                    $correlationCount++;
                }
            }
            
            if ($correlationCount > 0) {
                $avgCorrelation /= $correlationCount;
            }
            
            // Higher score for high Sharpe + low correlation
            $score = $strategy['sharpe_ratio'] * (1.5 - $avgCorrelation);
            $weights[$name] = max(0, $score);
            $totalScore += $weights[$name];
        }
        
        // Normalize weights
        if ($totalScore > 0) {
            foreach ($weights as $name => $weight) {
                $weights[$name] = round($weight / $totalScore, 3);
            }
        }
        
        return [
            'recommended_strategies' => $selected,
            'weights' => $weights,
            'expected_sharpe' => $this->calculatePortfolioSharpe($selected, $weights),
            'diversification_benefit' => $this->calculateDiversificationBenefit($selected, $correlations)
        ];
    }
    
    /**
     * Get performance over time (cumulative returns)
     * @param string $strategy Strategy name or 'all'
     * @return array Time series of cumulative returns
     */
    public function getPerformanceTimeSeries(string $strategy = 'all'): array
    {
        $trades = $strategy === 'all' 
            ? $this->tradeHistory 
            : array_filter($this->tradeHistory, fn($t) => $t['strategy'] === $strategy);
        
        // Sort by exit date
        usort($trades, fn($a, $b) => strtotime($a['exit_date'] ?? 'now') <=> strtotime($b['exit_date'] ?? 'now'));
        
        $cumulative = 1.0;
        $series = [];
        
        foreach ($trades as $trade) {
            $cumulative *= (1 + $trade['return']);
            $series[] = [
                'date' => $trade['exit_date'],
                'symbol' => $trade['symbol'],
                'return' => $trade['return'],
                'cumulative_return' => round($cumulative - 1, 4),
                'cumulative_value' => round($cumulative, 4)
            ];
        }
        
        return $series;
    }
    
    /**
     * Calculate Sharpe ratio (risk-adjusted return)
     * Assumes risk-free rate of 3% annually
     */
    private function calculateSharpeRatio(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStdDev($returns);
        
        if ($stdDev == 0) {
            return 0.0;
        }
        
        $riskFreeRate = 0.03 / 252; // Daily risk-free rate (~3% annual)
        $sharpeRatio = ($avgReturn - $riskFreeRate) / $stdDev;
        
        // Annualize (sqrt(252) trading days)
        return round($sharpeRatio * sqrt(252), 2);
    }
    
    /**
     * Calculate maximum drawdown
     */
    private function calculateMaxDrawdown(array $returns): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $cumulative = 1.0;
        $peak = 1.0;
        $maxDrawdown = 0.0;
        
        foreach ($returns as $return) {
            $cumulative *= (1 + $return);
            
            if ($cumulative > $peak) {
                $peak = $cumulative;
            }
            
            $drawdown = ($peak - $cumulative) / $peak;
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
            return 0.0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($v) => pow($v - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / (count($values) - 1);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate correlation coefficient between two return series
     */
    private function calculateCorrelation(array $x, array $y): float
    {
        $n = min(count($x), count($y));
        
        if ($n < 2) {
            return 0.0;
        }
        
        // Use first n elements
        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);
        
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        
        $numerator = 0;
        $sumSqX = 0;
        $sumSqY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $diffY = $y[$i] - $meanY;
            
            $numerator += $diffX * $diffY;
            $sumSqX += $diffX * $diffX;
            $sumSqY += $diffY * $diffY;
        }
        
        $denominator = sqrt($sumSqX * $sumSqY);
        
        if ($denominator == 0) {
            return 0.0;
        }
        
        return round($numerator / $denominator, 3);
    }
    
    /**
     * Calculate holding days between two dates
     */
    private function calculateHoldingDays(?string $entryDate, ?string $exitDate): int
    {
        if (!$entryDate || !$exitDate) {
            return 0;
        }
        
        $entry = strtotime($entryDate);
        $exit = strtotime($exitDate);
        
        return (int)round(($exit - $entry) / 86400);
    }
    
    /**
     * Calculate average holding days
     */
    private function calculateAverageHoldingDays(array $trades): int
    {
        if (empty($trades)) {
            return 0;
        }
        
        $holdingDays = array_column($trades, 'holding_days');
        return (int)round(array_sum($holdingDays) / count($holdingDays));
    }
    
    /**
     * Calculate expectancy (expected value per trade)
     */
    private function calculateExpectancy(float $winRate, float $avgWin, float $avgLoss): float
    {
        return round(($winRate * $avgWin) - ((1 - $winRate) * $avgLoss), 4);
    }
    
    /**
     * Calculate portfolio Sharpe ratio from strategy combination
     */
    private function calculatePortfolioSharpe(array $strategies, array $weights): float
    {
        $weightedSharpe = 0;
        
        foreach ($strategies as $strategy) {
            $name = $strategy['strategy'];
            $weight = $weights[$name] ?? 0;
            $weightedSharpe += $strategy['sharpe_ratio'] * $weight;
        }
        
        return round($weightedSharpe, 2);
    }
    
    /**
     * Calculate diversification benefit
     */
    private function calculateDiversificationBenefit(array $strategies, array $correlations): float
    {
        if (count($strategies) < 2) {
            return 0.0;
        }
        
        $avgCorrelation = 0;
        $count = 0;
        
        foreach ($strategies as $s1) {
            foreach ($strategies as $s2) {
                $name1 = $s1['strategy'];
                $name2 = $s2['strategy'];
                
                if ($name1 !== $name2 && isset($correlations[$name1][$name2])) {
                    $avgCorrelation += abs($correlations[$name1][$name2]);
                    $count++;
                }
            }
        }
        
        if ($count > 0) {
            $avgCorrelation /= $count;
        }
        
        // Benefit is higher when correlation is lower
        return round((1 - $avgCorrelation) * 100, 1);
    }
    
    /**
     * Get empty metrics structure
     */
    private function getEmptyMetrics(): array
    {
        return [
            'strategy' => 'none',
            'total_trades' => 0,
            'winning_trades' => 0,
            'losing_trades' => 0,
            'win_rate' => 0,
            'average_return' => 0,
            'average_win' => 0,
            'average_loss' => 0,
            'profit_factor' => 0,
            'sharpe_ratio' => 0,
            'max_drawdown' => 0,
            'total_return' => 0,
            'best_trade' => 0,
            'worst_trade' => 0,
            'average_holding_days' => 0,
            'expectancy' => 0
        ];
    }
    
    /**
     * Get all trade history
     */
    public function getTradeHistory(): array
    {
        return $this->tradeHistory;
    }
    
    /**
     * Clear trade history
     */
    public function clearHistory(): void
    {
        $this->tradeHistory = [];
    }
}
