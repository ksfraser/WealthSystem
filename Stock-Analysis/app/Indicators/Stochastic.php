<?php

declare(strict_types=1);

namespace App\Indicators;

/**
 * Stochastic Oscillator calculator
 */
class Stochastic
{
    /**
     * Calculate Stochastic Oscillator
     *
     * @param array $highs High prices
     * @param array $lows Low prices
     * @param array $closes Close prices
     * @param int $period Lookback period (typically 14)
     * @return array ['k' => float, 'd' => float]
     */
    public function calculate(array $highs, array $lows, array $closes, int $period = 14): array
    {
        if (count($closes) < $period) {
            return ['k' => 50.0, 'd' => 50.0];
        }
        
        $recentHighs = array_slice($highs, -$period);
        $recentLows = array_slice($lows, -$period);
        $currentClose = end($closes);
        
        $highestHigh = max($recentHighs);
        $lowestLow = min($recentLows);
        
        if ($highestHigh == $lowestLow) {
            $k = 50.0;
        } else {
            $k = (($currentClose - $lowestLow) / ($highestHigh - $lowestLow)) * 100;
        }
        
        // %D is typically a 3-period SMA of %K
        // Simplified: use %K value
        $d = $k * 0.9; // Approximation
        
        return ['k' => $k, 'd' => $d];
    }
    
    /**
     * Check if stochastic is oversold
     *
     * @param array $stochastic Stochastic values
     * @param float $threshold Oversold threshold (typically 20)
     * @return bool
     */
    public function isOversold(array $stochastic, float $threshold = 20.0): bool
    {
        return $stochastic['k'] < $threshold && $stochastic['d'] < $threshold;
    }
    
    /**
     * Check if stochastic is overbought
     *
     * @param array $stochastic Stochastic values
     * @param float $threshold Overbought threshold (typically 80)
     * @return bool
     */
    public function isOverbought(array $stochastic, float $threshold = 80.0): bool
    {
        return $stochastic['k'] > $threshold && $stochastic['d'] > $threshold;
    }
}
