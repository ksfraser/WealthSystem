<?php

declare(strict_types=1);

namespace App\Indicators;

/**
 * Average True Range (ATR) calculator - measures volatility
 */
class ATR
{
    /**
     * Calculate ATR
     *
     * @param array $highs High prices
     * @param array $lows Low prices
     * @param array $closes Close prices
     * @param int $period Period for ATR (typically 14)
     * @return float ATR value
     */
    public function calculate(array $highs, array $lows, array $closes, int $period = 14): float
    {
        if (count($closes) < $period + 1) {
            return 0.0;
        }
        
        $trueRanges = [];
        
        for ($i = 1; $i < count($closes); $i++) {
            $high = $highs[$i];
            $low = $lows[$i];
            $prevClose = $closes[$i - 1];
            
            $tr = max(
                $high - $low,
                abs($high - $prevClose),
                abs($low - $prevClose)
            );
            
            $trueRanges[] = $tr;
        }
        
        $recentTR = array_slice($trueRanges, -$period);
        
        return array_sum($recentTR) / $period;
    }
    
    /**
     * Calculate volatility normalized by price
     *
     * @param float $atr ATR value
     * @param float $currentPrice Current price
     * @return float Volatility as percentage
     */
    public function getVolatilityPercent(float $atr, float $currentPrice): float
    {
        if ($currentPrice == 0) {
            return 0.0;
        }
        
        return ($atr / $currentPrice) * 100;
    }
}
