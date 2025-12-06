<?php

declare(strict_types=1);

namespace App\Risk;

/**
 * Calculate various risk metrics for portfolios
 */
class RiskMetrics
{
    /**
     * Calculate Sharpe Ratio
     *
     * @param array $returns Portfolio returns
     * @param float $riskFreeRate Risk-free rate (annual)
     * @return float Sharpe ratio
     */
    public function sharpeRatio(array $returns, float $riskFreeRate = 0.02): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $stdDev = $this->standardDeviation($returns);
        
        if ($stdDev == 0.0) {
            return 0.0;
        }
        
        // Convert annual risk-free rate to period rate (assuming daily returns)
        $periodRiskFreeRate = $riskFreeRate / 252;
        
        return ($mean - $periodRiskFreeRate) / $stdDev;
    }
    
    /**
     * Calculate Sortino Ratio (like Sharpe but only uses downside deviation)
     *
     * @param array $returns Portfolio returns
     * @param float $targetReturn Target or minimum acceptable return
     * @return float Sortino ratio
     */
    public function sortinoRatio(array $returns, float $targetReturn = 0.0): float
    {
        if (empty($returns)) {
            return 0.0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $downsideDeviation = $this->downsideDeviation($returns, $targetReturn);
        
        if ($downsideDeviation == 0.0) {
            return 0.0;
        }
        
        return ($mean - $targetReturn) / $downsideDeviation;
    }
    
    /**
     * Calculate Maximum Drawdown
     *
     * @param array $cumulativeReturns Cumulative returns over time
     * @return float Maximum drawdown as a percentage
     */
    public function maxDrawdown(array $cumulativeReturns): float
    {
        if (empty($cumulativeReturns)) {
            return 0.0;
        }
        
        $peak = $cumulativeReturns[0];
        $maxDD = 0.0;
        
        foreach ($cumulativeReturns as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            
            $drawdown = ($peak - $value) / $peak;
            
            if ($drawdown > $maxDD) {
                $maxDD = $drawdown;
            }
        }
        
        return $maxDD;
    }
    
    /**
     * Calculate standard deviation of returns
     *
     * @param array $returns
     * @return float
     */
    public function standardDeviation(array $returns): float
    {
        if (count($returns) < 2) {
            return 0.0;
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $returns)) / (count($returns) - 1);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate downside deviation (only negative deviations from target)
     *
     * @param array $returns
     * @param float $targetReturn
     * @return float
     */
    public function downsideDeviation(array $returns, float $targetReturn = 0.0): float
    {
        $downsideReturns = array_filter($returns, fn($r) => $r < $targetReturn);
        
        if (empty($downsideReturns)) {
            return 0.0;
        }
        
        $squaredDeviations = array_map(function($r) use ($targetReturn) {
            return pow($r - $targetReturn, 2);
        }, $downsideReturns);
        
        $variance = array_sum($squaredDeviations) / count($returns);
        
        return sqrt($variance);
    }
    
    /**
     * Calculate Calmar Ratio (return / max drawdown)
     *
     * @param array $returns
     * @param array $cumulativeReturns
     * @return float
     */
    public function calmarRatio(array $returns, array $cumulativeReturns): float
    {
        if (empty($returns) || empty($cumulativeReturns)) {
            return 0.0;
        }
        
        $annualizedReturn = (array_sum($returns) / count($returns)) * 252;
        $maxDD = $this->maxDrawdown($cumulativeReturns);
        
        if ($maxDD == 0.0) {
            return 0.0;
        }
        
        return $annualizedReturn / $maxDD;
    }
}
