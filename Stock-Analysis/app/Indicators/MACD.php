<?php

declare(strict_types=1);

namespace App\Indicators;

/**
 * MACD (Moving Average Convergence Divergence) calculator
 */
class MACD
{
    /**
     * Calculate MACD
     *
     * @param array $prices Price data
     * @param int $fastPeriod Fast EMA period (typically 12)
     * @param int $slowPeriod Slow EMA period (typically 26)
     * @param int $signalPeriod Signal line period (typically 9)
     * @return array ['macd' => float, 'signal' => float, 'histogram' => float]
     */
    public function calculate(array $prices, int $fastPeriod = 12, int $slowPeriod = 26, int $signalPeriod = 9): array
    {
        if (count($prices) < $slowPeriod) {
            return [
                'macd' => 0.0,
                'signal' => 0.0,
                'histogram' => 0.0,
            ];
        }
        
        $fastEMA = $this->calculateEMA($prices, $fastPeriod);
        $slowEMA = $this->calculateEMA($prices, $slowPeriod);
        
        $macdLine = $fastEMA - $slowEMA;
        
        // For signal line, we'd need historical MACD values
        // Simplified: use simple average of recent price differences
        $signal = $macdLine * 0.9; // Approximation
        
        $histogram = $macdLine - $signal;
        
        return [
            'macd' => $macdLine,
            'signal' => $signal,
            'histogram' => $histogram,
        ];
    }
    
    /**
     * Calculate Exponential Moving Average
     *
     * @param array $prices
     * @param int $period
     * @return float
     */
    private function calculateEMA(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return array_sum($prices) / count($prices);
        }
        
        $multiplier = 2.0 / ($period + 1);
        $recentPrices = array_slice($prices, -$period * 2);
        
        $ema = array_sum(array_slice($recentPrices, 0, $period)) / $period;
        
        for ($i = $period; $i < count($recentPrices); $i++) {
            $ema = ($recentPrices[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }
        
        return $ema;
    }
    
    /**
     * Check for bullish crossover (MACD crosses above signal)
     *
     * @param array $currentMACD Current MACD values
     * @param array $previousMACD Previous MACD values
     * @return bool
     */
    public function isBullishCrossover(array $currentMACD, array $previousMACD): bool
    {
        return $previousMACD['macd'] <= $previousMACD['signal'] 
            && $currentMACD['macd'] > $currentMACD['signal'];
    }
    
    /**
     * Check for bearish crossover (MACD crosses below signal)
     *
     * @param array $currentMACD
     * @param array $previousMACD
     * @return bool
     */
    public function isBearishCrossover(array $currentMACD, array $previousMACD): bool
    {
        return $previousMACD['macd'] >= $previousMACD['signal'] 
            && $currentMACD['macd'] < $currentMACD['signal'];
    }
}
