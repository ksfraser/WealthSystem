<?php

declare(strict_types=1);

namespace App\Backtesting;

/**
 * Performance Metrics Calculator
 * 
 * Calculates comprehensive performance metrics for backtesting results:
 * - Returns (total, annualized)
 * - Risk-adjusted returns (Sharpe, Sortino)
 * - Drawdown analysis
 * - Win/loss statistics
 * - Profit metrics
 * - Expectancy
 * 
 * @package App\Backtesting
 */
class PerformanceMetrics
{
    /**
     * Calculate total return percentage
     *
     * @param float $initialValue Initial portfolio value
     * @param float $finalValue Final portfolio value
     * @return float Return percentage
     */
    public function calculateTotalReturn(float $initialValue, float $finalValue): float
    {
        if ($initialValue == 0) {
            return 0.0;
        }
        
        return (($finalValue - $initialValue) / $initialValue) * 100;
    }
    
    /**
     * Calculate annualized return
     *
     * @param float $totalReturn Total return percentage
     * @param int $days Number of days
     * @return float Annualized return percentage
     */
    public function calculateAnnualizedReturn(float $totalReturn, int $days): float
    {
        if ($days <= 0) {
            return 0.0;
        }
        
        $years = $days / 365.0;
        
        return (pow(1 + ($totalReturn / 100), 1 / $years) - 1) * 100;
    }
    
    /**
     * Calculate Sharpe ratio
     *
     * @param array<int, float> $returns Period returns
     * @param float $riskFreeRate Annual risk-free rate (e.g., 0.02 for 2%)
     * @return float Sharpe ratio
     */
    public function calculateSharpeRatio(array $returns, float $riskFreeRate = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $avgReturn = array_sum($returns) / count($returns);
        $stdDev = $this->calculateStandardDeviation($returns);
        
        if ($stdDev == 0) {
            return 0.0;
        }
        
        $excessReturn = $avgReturn - ($riskFreeRate / 252); // Daily risk-free rate
        
        return ($excessReturn / $stdDev) * sqrt(252); // Annualized
    }
    
    /**
     * Calculate Sortino ratio
     *
     * @param array<int, float> $returns Period returns
     * @param float $riskFreeRate Annual risk-free rate
     * @return float Sortino ratio
     */
    public function calculateSortinoRatio(array $returns, float $riskFreeRate = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $avgReturn = array_sum($returns) / count($returns);
        $downside = $this->calculateDownsideDeviation($returns, $riskFreeRate / 252);
        
        if ($downside == 0) {
            return 0.0;
        }
        
        $excessReturn = $avgReturn - ($riskFreeRate / 252);
        
        return ($excessReturn / $downside) * sqrt(252); // Annualized
    }
    
    /**
     * Calculate maximum drawdown
     *
     * @param array<int, float> $equityCurve Equity curve values
     * @return float Maximum drawdown percentage (negative)
     */
    public function calculateMaxDrawdown(array $equityCurve): float
    {
        if (empty($equityCurve)) {
            return 0.0;
        }
        
        $maxDrawdown = 0.0;
        $peak = $equityCurve[0];
        
        foreach ($equityCurve as $value) {
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
     * Calculate win rate
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Win rate percentage
     */
    public function calculateWinRate(array $trades): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $wins = 0;
        
        foreach ($trades as $trade) {
            if (isset($trade['profit']) && $trade['profit'] > 0) {
                $wins++;
            }
        }
        
        return ($wins / count($trades)) * 100;
    }
    
    /**
     * Calculate profit factor
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Profit factor
     */
    public function calculateProfitFactor(array $trades): float
    {
        $totalProfit = 0.0;
        $totalLoss = 0.0;
        
        foreach ($trades as $trade) {
            if (isset($trade['profit'])) {
                if ($trade['profit'] > 0) {
                    $totalProfit += $trade['profit'];
                } elseif ($trade['profit'] < 0) {
                    $totalLoss += abs($trade['profit']);
                }
            }
        }
        
        if ($totalLoss == 0) {
            return 0.0;
        }
        
        return $totalProfit / $totalLoss;
    }
    
    /**
     * Calculate average winning trade
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Average win
     */
    public function calculateAverageWin(array $trades): float
    {
        $wins = [];
        
        foreach ($trades as $trade) {
            if (isset($trade['profit']) && $trade['profit'] > 0) {
                $wins[] = $trade['profit'];
            }
        }
        
        if (empty($wins)) {
            return 0.0;
        }
        
        return array_sum($wins) / count($wins);
    }
    
    /**
     * Calculate average losing trade
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Average loss (negative)
     */
    public function calculateAverageLoss(array $trades): float
    {
        $losses = [];
        
        foreach ($trades as $trade) {
            if (isset($trade['profit']) && $trade['profit'] < 0) {
                $losses[] = $trade['profit'];
            }
        }
        
        if (empty($losses)) {
            return 0.0;
        }
        
        return array_sum($losses) / count($losses);
    }
    
    /**
     * Calculate reward/risk ratio
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Reward/risk ratio
     */
    public function calculateRewardRiskRatio(array $trades): float
    {
        $avgWin = $this->calculateAverageWin($trades);
        $avgLoss = abs($this->calculateAverageLoss($trades));
        
        if ($avgLoss == 0) {
            return 0.0;
        }
        
        return $avgWin / $avgLoss;
    }
    
    /**
     * Calculate volatility (standard deviation of returns)
     *
     * @param array<int, float> $returns Period returns
     * @return float Volatility (annualized)
     */
    public function calculateVolatility(array $returns): float
    {
        $stdDev = $this->calculateStandardDeviation($returns);
        
        return $stdDev * sqrt(252); // Annualized
    }
    
    /**
     * Calculate expectancy
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return float Expectancy per trade
     */
    public function calculateExpectancy(array $trades): float
    {
        if (empty($trades)) {
            return 0.0;
        }
        
        $winRate = $this->calculateWinRate($trades) / 100;
        $lossRate = 1 - $winRate;
        $avgWin = $this->calculateAverageWin($trades);
        $avgLoss = abs($this->calculateAverageLoss($trades));
        
        return ($winRate * $avgWin) - ($lossRate * $avgLoss);
    }
    
    /**
     * Calculate total trades
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return int Total number of trades
     */
    public function calculateTotalTrades(array $trades): int
    {
        return count($trades);
    }
    
    /**
     * Calculate winning trades count
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return int Number of winning trades
     */
    public function calculateWinningTrades(array $trades): int
    {
        $wins = 0;
        
        foreach ($trades as $trade) {
            if (isset($trade['profit']) && $trade['profit'] > 0) {
                $wins++;
            }
        }
        
        return $wins;
    }
    
    /**
     * Calculate losing trades count
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return int Number of losing trades
     */
    public function calculateLosingTrades(array $trades): int
    {
        $losses = 0;
        
        foreach ($trades as $trade) {
            if (isset($trade['profit']) && $trade['profit'] < 0) {
                $losses++;
            }
        }
        
        return $losses;
    }
    
    /**
     * Generate performance summary
     *
     * @param array<string, mixed> $backtestResult Backtest results
     * @return array<string, mixed> Performance summary
     */
    public function generateSummary(array $backtestResult): array
    {
        $totalReturn = $this->calculateTotalReturn(
            $backtestResult['initial_capital'],
            $backtestResult['final_value']
        );
        
        $annualizedReturn = $this->calculateAnnualizedReturn(
            $totalReturn,
            $backtestResult['days']
        );
        
        $returns = $this->extractReturns($backtestResult['trades']);
        
        return [
            'total_return' => $totalReturn,
            'annualized_return' => $annualizedReturn,
            'sharpe_ratio' => $this->calculateSharpeRatio($returns),
            'sortino_ratio' => $this->calculateSortinoRatio($returns),
            'max_drawdown' => $this->calculateMaxDrawdown($backtestResult['equity_curve']),
            'win_rate' => $this->calculateWinRate($backtestResult['trades']),
            'profit_factor' => $this->calculateProfitFactor($backtestResult['trades']),
            'total_trades' => $this->calculateTotalTrades($backtestResult['trades']),
            'winning_trades' => $this->calculateWinningTrades($backtestResult['trades']),
            'losing_trades' => $this->calculateLosingTrades($backtestResult['trades']),
            'avg_win' => $this->calculateAverageWin($backtestResult['trades']),
            'avg_loss' => $this->calculateAverageLoss($backtestResult['trades']),
            'expectancy' => $this->calculateExpectancy($backtestResult['trades']),
            'volatility' => $this->calculateVolatility($returns)
        ];
    }
    
    /**
     * Extract returns from trades
     *
     * @param array<int, array<string, mixed>> $trades List of trades
     * @return array<int, float> Returns
     */
    private function extractReturns(array $trades): array
    {
        $returns = [];
        
        foreach ($trades as $trade) {
            if (isset($trade['return'])) {
                $returns[] = $trade['return'];
            }
        }
        
        return $returns;
    }
    
    /**
     * Calculate standard deviation
     *
     * @param array<int, float> $values Values
     * @return float Standard deviation
     */
    private function calculateStandardDeviation(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }
        
        $mean = array_sum($values) / count($values);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        $variance = array_sum($squaredDiffs) / count($values);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate downside deviation
     *
     * @param array<int, float> $returns Returns
     * @param float $targetReturn Target return (usually risk-free rate)
     * @return float Downside deviation
     */
    private function calculateDownsideDeviation(array $returns, float $targetReturn = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $downsideReturns = [];
        
        foreach ($returns as $return) {
            if ($return < $targetReturn) {
                $downsideReturns[] = pow($return - $targetReturn, 2);
            }
        }
        
        if (empty($downsideReturns)) {
            return 0.0;
        }
        
        $downsideVariance = array_sum($downsideReturns) / count($returns);
        
        return sqrt($downsideVariance);
    }
}
