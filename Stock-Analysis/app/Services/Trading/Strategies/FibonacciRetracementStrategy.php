<?php

declare(strict_types=1);

namespace App\Services\Trading\Strategies;

/**
 * Fibonacci Retracement Strategy
 * 
 * Uses Fibonacci retracement levels to identify potential support and resistance zones.
 * Common levels: 23.6%, 38.2%, 50%, 61.8% (golden ratio), 78.6%
 * 
 * Signals:
 * - BUY: Price bounces at Fibonacci support level
 * - SELL: Price rejected at Fibonacci resistance level
 * - HOLD: Price away from key levels or unclear pattern
 * 
 * @package App\Services\Trading\Strategies
 */
class FibonacciRetracementStrategy
{
    private float $proximityThreshold;
    private int $swingLookback;
    
    private const FIB_LEVELS = [
        '0.000' => 0.000,
        '0.236' => 0.236,
        '0.382' => 0.382,
        '0.500' => 0.500,
        '0.618' => 0.618,
        '0.786' => 0.786,
        '1.000' => 1.000
    ];
    
    /**
     * Create new Fibonacci Retracement strategy
     *
     * @param array<string, mixed> $parameters Optional parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->proximityThreshold = $parameters['proximity_threshold'] ?? 0.02;  // 2%
        $this->swingLookback = $parameters['swing_lookback'] ?? 30;
    }
    
    /**
     * Analyze market data and generate trading signal
     *
     * @param string $symbol Stock symbol
     * @param array<int, array<string, mixed>> $historicalData Historical OHLCV data
     * @return array<string, mixed> Trading signal with confidence and details
     */
    public function analyze(string $symbol, array $historicalData): array
    {
        $dataCount = count($historicalData);
        
        if ($dataCount < 20) {
            return [
                'signal' => 'HOLD',
                'confidence' => 0.3,
                'strategy' => 'FibonacciRetracementStrategy',
                'reason' => 'Insufficient data for Fibonacci analysis'
            ];
        }
        
        // Find swing high and low
        $swingPoints = $this->findSwingPoints($historicalData);
        $swingHigh = $swingPoints['high'];
        $swingLow = $swingPoints['low'];
        
        // Calculate Fibonacci levels
        $fibLevels = $this->calculateFibonacciLevels($swingHigh, $swingLow);
        
        // Determine trend
        $trend = $this->determineTrend($historicalData);
        
        // Get current price
        $currentPrice = $historicalData[$dataCount - 1]['close'];
        
        // Calculate retracement percentage
        $retracementPct = $this->calculateRetracementPercentage(
            $currentPrice,
            $swingHigh,
            $swingLow
        );
        
        // Find nearest Fibonacci level
        $nearestLevel = $this->findNearestLevel($currentPrice, $fibLevels);
        
        // Count touches at levels
        $levelTouches = $this->countLevelTouches($historicalData, $fibLevels);
        
        // Detect bounces and rejections
        $priceAction = $this->analyzePriceAction($historicalData, $fibLevels, $trend);
        
        // Generate signal
        $signal = 'HOLD';
        $confidence = 0.5;
        $reason = '';
        
        if ($priceAction['type'] === 'bounce' && $trend === 'uptrend') {
            $signal = 'BUY';
            $confidence = 0.65;
            $reason = "Bounce at {$priceAction['level']} Fibonacci support";
            
            if ($priceAction['level'] === '0.618') {
                $confidence = 0.75;
                $reason .= ' (golden ratio)';
            }
            
            if ($levelTouches > 1) {
                $confidence += 0.05;
            }
        } elseif ($priceAction['type'] === 'rejection' && $trend === 'downtrend') {
            $signal = 'SELL';
            $confidence = 0.65;
            $reason = "Rejection at {$priceAction['level']} Fibonacci resistance";
            
            if ($priceAction['level'] === '0.618') {
                $confidence = 0.75;
                $reason .= ' (golden ratio)';
            }
            
            if ($levelTouches > 1) {
                $confidence += 0.05;
            }
        } elseif ($priceAction['type'] === 'breakout') {
            $signal = 'BUY';
            $confidence = 0.7;
            $reason = "Breakout above Fibonacci resistance at {$priceAction['level']}";
        } elseif ($priceAction['type'] === 'breakdown') {
            $signal = 'SELL';
            $confidence = 0.7;
            $reason = "Breakdown below Fibonacci support at {$priceAction['level']}";
        } else {
            $reason = 'Price not at key Fibonacci level';
        }
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'strategy' => 'FibonacciRetracementStrategy',
            'reason' => $reason,
            'fib_levels' => $fibLevels,
            'swing_high' => $swingHigh,
            'swing_low' => $swingLow,
            'retracement_pct' => $retracementPct,
            'nearest_level' => $nearestLevel,
            'trend' => $trend,
            'level_touches' => $levelTouches
        ];
    }
    
    /**
     * Find swing high and low points
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return array<string, float> Swing points
     */
    private function findSwingPoints(array $data): array
    {
        $lookback = min($this->swingLookback, count($data));
        $recentData = array_slice($data, -$lookback);
        
        $highs = array_column($recentData, 'high');
        $lows = array_column($recentData, 'low');
        
        return [
            'high' => max($highs),
            'low' => min($lows)
        ];
    }
    
    /**
     * Calculate Fibonacci retracement levels
     *
     * @param float $high Swing high
     * @param float $low Swing low
     * @return array<string, float> Fibonacci levels
     */
    private function calculateFibonacciLevels(float $high, float $low): array
    {
        $range = $high - $low;
        $levels = [];
        
        foreach (self::FIB_LEVELS as $name => $ratio) {
            $levels[$name] = $low + ($range * (1 - $ratio));
        }
        
        return $levels;
    }
    
    /**
     * Determine overall trend
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return string Trend direction
     */
    private function determineTrend(array $data): string
    {
        $dataCount = count($data);
        
        if ($dataCount < 20) {
            return 'sideways';
        }
        
        $firstPrice = $data[max(0, $dataCount - 20)]['close'];
        $lastPrice = $data[$dataCount - 1]['close'];
        
        $change = ($lastPrice - $firstPrice) / $firstPrice;
        
        if ($change > 0.05) {
            return 'uptrend';
        } elseif ($change < -0.05) {
            return 'downtrend';
        }
        
        return 'sideways';
    }
    
    /**
     * Calculate current retracement percentage
     *
     * @param float $currentPrice Current price
     * @param float $high Swing high
     * @param float $low Swing low
     * @return float Retracement percentage
     */
    private function calculateRetracementPercentage(
        float $currentPrice,
        float $high,
        float $low
    ): float {
        if ($high === $low) {
            return 0.0;
        }
        
        return ($currentPrice - $low) / ($high - $low);
    }
    
    /**
     * Find nearest Fibonacci level to current price
     *
     * @param float $price Current price
     * @param array<string, float> $levels Fibonacci levels
     * @return string Nearest level name
     */
    private function findNearestLevel(float $price, array $levels): string
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearest = '0.000';
        
        foreach ($levels as $name => $level) {
            $distance = abs($price - $level);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $name;
            }
        }
        
        return $nearest;
    }
    
    /**
     * Count how many times price touched Fibonacci levels
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<string, float> $levels Fibonacci levels
     * @return int Touch count
     */
    private function countLevelTouches(array $data, array $levels): int
    {
        $touches = 0;
        
        foreach ($data as $bar) {
            foreach ($levels as $level) {
                $low = $bar['low'];
                $high = $bar['high'];
                
                $tolerance = $level * $this->proximityThreshold;
                
                if ($low <= $level + $tolerance && $high >= $level - $tolerance) {
                    $touches++;
                    break;  // Count once per bar
                }
            }
        }
        
        return $touches;
    }
    
    /**
     * Analyze price action at Fibonacci levels
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<string, float> $levels Fibonacci levels
     * @param string $trend Current trend
     * @return array<string, mixed> Price action analysis
     */
    private function analyzePriceAction(array $data, array $levels, string $trend): array
    {
        $dataCount = count($data);
        
        if ($dataCount < 3) {
            return ['type' => 'none', 'level' => null];
        }
        
        $currentBar = $data[$dataCount - 1];
        $previousBar = $data[$dataCount - 2];
        $currentPrice = $currentBar['close'];
        $previousPrice = $previousBar['close'];
        
        // Check each level for bounce, rejection, breakout, or breakdown
        foreach ($levels as $name => $levelPrice) {
            $tolerance = $levelPrice * $this->proximityThreshold;
            
            // Skip 0% and 100% levels
            if ($name === '0.000' || $name === '1.000') {
                continue;
            }
            
            // Bounce (price was at/below level, now above)
            if ($trend === 'uptrend' &&
                $previousBar['low'] <= $levelPrice + $tolerance &&
                $currentPrice > $levelPrice + $tolerance &&
                $currentPrice > $previousPrice) {
                return ['type' => 'bounce', 'level' => $name];
            }
            
            // Rejection (price was at/above level, now below)
            if ($trend === 'downtrend' &&
                $previousBar['high'] >= $levelPrice - $tolerance &&
                $currentPrice < $levelPrice - $tolerance &&
                $currentPrice < $previousPrice) {
                return ['type' => 'rejection', 'level' => $name];
            }
            
            // Breakout (strong move above resistance)
            if ($previousPrice < $levelPrice && $currentPrice > $levelPrice * 1.02) {
                return ['type' => 'breakout', 'level' => $name];
            }
            
            // Breakdown (strong move below support)
            if ($previousPrice > $levelPrice && $currentPrice < $levelPrice * 0.98) {
                return ['type' => 'breakdown', 'level' => $name];
            }
        }
        
        return ['type' => 'none', 'level' => null];
    }
}
