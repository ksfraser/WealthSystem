<?php

declare(strict_types=1);

namespace App\Risk;

/**
 * Stress testing for portfolios
 */
class StressTester
{
    /**
     * Apply a market crash scenario
     *
     * @param float $portfolioValue Current portfolio value
     * @param float $crashPercent Percentage drop (e.g., 0.20 for 20% crash)
     * @return array Results with stressed value and loss
     */
    public function marketCrash(float $portfolioValue, float $crashPercent = 0.20): array
    {
        $stressedValue = $portfolioValue * (1 - $crashPercent);
        $loss = $portfolioValue - $stressedValue;
        
        return [
            'scenario' => 'Market Crash',
            'crash_percent' => $crashPercent * 100,
            'original_value' => $portfolioValue,
            'stressed_value' => $stressedValue,
            'loss' => $loss,
            'loss_percent' => $crashPercent * 100,
        ];
    }
    
    /**
     * Apply a volatility spike scenario
     *
     * @param array $returns Historical returns
     * @param float $volMultiplier Multiplier for volatility (e.g., 2.0 for doubling)
     * @return array Results with adjusted returns
     */
    public function volatilitySpike(array $returns, float $volMultiplier = 2.0): array
    {
        $mean = array_sum($returns) / count($returns);
        
        $stressedReturns = array_map(function($return) use ($mean, $volMultiplier) {
            return $mean + ($return - $mean) * $volMultiplier;
        }, $returns);
        
        return [
            'scenario' => 'Volatility Spike',
            'multiplier' => $volMultiplier,
            'original_std' => $this->calculateStdDev($returns),
            'stressed_std' => $this->calculateStdDev($stressedReturns),
            'stressed_returns' => $stressedReturns,
        ];
    }
    
    /**
     * Apply multiple stress scenarios
     *
     * @param float $portfolioValue Current portfolio value
     * @param array $returns Historical returns
     * @return array Results from all scenarios
     */
    public function runAllScenarios(float $portfolioValue, array $returns): array
    {
        return [
            'mild_crash' => $this->marketCrash($portfolioValue, 0.10),
            'moderate_crash' => $this->marketCrash($portfolioValue, 0.20),
            'severe_crash' => $this->marketCrash($portfolioValue, 0.40),
            'volatility_spike' => $this->volatilitySpike($returns, 2.0),
            'extreme_volatility' => $this->volatilitySpike($returns, 3.0),
        ];
    }
    
    /**
     * Calculate standard deviation
     *
     * @param array $values
     * @return float
     */
    private function calculateStdDev(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }
        
        $mean = array_sum($values) / count($values);
        $variance = array_sum(array_map(function($x) use ($mean) {
            return pow($x - $mean, 2);
        }, $values)) / (count($values) - 1);
        
        return sqrt($variance);
    }
}
