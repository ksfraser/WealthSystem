<?php

declare(strict_types=1);

namespace App\Indicators;

/**
 * Relative Strength Index (RSI) calculator
 */
class RSI
{
    /**
     * Calculate RSI
     *
     * @param array $prices Price data
     * @param int $period Period for RSI calculation (typically 14)
     * @return float RSI value (0-100)
     */
    public function calculate(array $prices, int $period = 14): float
    {
        if (count($prices) < $period + 1) {
            return 50.0; // Neutral value
        }
        
        $changes = [];
        for ($i = 1; $i < count($prices); $i++) {
            $changes[] = $prices[$i] - $prices[$i - 1];
        }
        
        $recentChanges = array_slice($changes, -$period);
        
        $gains = array_filter($recentChanges, fn($c) => $c > 0);
        $losses = array_map('abs', array_filter($recentChanges, fn($c) => $c < 0));
        
        $avgGain = empty($gains) ? 0 : array_sum($gains) / $period;
        $avgLoss = empty($losses) ? 0 : array_sum($losses) / $period;
        
        if ($avgLoss == 0) {
            return 100.0;
        }
        
        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));
        
        return $rsi;
    }
    
    /**
     * Check if RSI indicates oversold condition
     *
     * @param float $rsi RSI value
     * @param float $threshold Oversold threshold (typically 30)
     * @return bool
     */
    public function isOversold(float $rsi, float $threshold = 30.0): bool
    {
        return $rsi < $threshold;
    }
    
    /**
     * Check if RSI indicates overbought condition
     *
     * @param float $rsi RSI value
     * @param float $threshold Overbought threshold (typically 70)
     * @return bool
     */
    public function isOverbought(float $rsi, float $threshold = 70.0): bool
    {
        return $rsi > $threshold;
    }
}
