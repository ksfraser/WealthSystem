<?php

declare(strict_types=1);

namespace App\Indicators;

/**
 * Calculate Bollinger Bands
 */
class BollingerBands
{
    /**
     * Calculate Bollinger Bands
     *
     * @param array $prices Price data
     * @param int $period Moving average period
     * @param float $stdDevMultiplier Standard deviation multiplier
     * @return array ['middle' => float, 'upper' => float, 'lower' => float, 'bandwidth' => float]
     */
    public function calculate(array $prices, int $period = 20, float $stdDevMultiplier = 2.0): array
    {
        if (count($prices) < $period) {
            return [
                'middle' => 0.0,
                'upper' => 0.0,
                'lower' => 0.0,
                'bandwidth' => 0.0,
            ];
        }
        
        $recentPrices = array_slice($prices, -$period);
        $middle = array_sum($recentPrices) / $period;
        
        $variance = 0.0;
        foreach ($recentPrices as $price) {
            $variance += pow($price - $middle, 2);
        }
        $stdDev = sqrt($variance / $period);
        
        $upper = $middle + ($stdDevMultiplier * $stdDev);
        $lower = $middle - ($stdDevMultiplier * $stdDev);
        
        $bandwidth = ($upper - $lower) / $middle;
        
        return [
            'middle' => $middle,
            'upper' => $upper,
            'lower' => $lower,
            'bandwidth' => $bandwidth,
        ];
    }
    
    /**
     * Get %B indicator (position within bands)
     *
     * @param float $currentPrice
     * @param array $prices
     * @param int $period
     * @param float $stdDevMultiplier
     * @return float %B value (0-1 when within bands, <0 or >1 when outside)
     */
    public function getPercentB(float $currentPrice, array $prices, int $period = 20, float $stdDevMultiplier = 2.0): float
    {
        $bands = $this->calculate($prices, $period, $stdDevMultiplier);
        
        if ($bands['upper'] == $bands['lower']) {
            return 0.5;
        }
        
        return ($currentPrice - $bands['lower']) / ($bands['upper'] - $bands['lower']);
    }
}
