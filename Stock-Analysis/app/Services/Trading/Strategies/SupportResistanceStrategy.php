<?php

declare(strict_types=1);

namespace App\Services\Trading\Strategies;

/**
 * Support and Resistance Strategy
 * 
 * Identifies key price levels where buying or selling pressure is concentrated.
 * Uses horizontal levels, pivot points, and level strength analysis.
 * 
 * Key Concepts:
 * - Support: Price level where buying pressure exceeds selling pressure
 * - Resistance: Price level where selling pressure exceeds buying pressure
 * - Pivot Points: Mathematical support/resistance levels based on H/L/C
 * - Level Strength: Number of times a level has been tested
 * - Breakout: Price breaks above resistance with volume
 * - Breakdown: Price breaks below support with volume
 * 
 * Signals:
 * - BUY: Price at support level or breakout above resistance
 * - SELL: Price at resistance level or breakdown below support
 * - HOLD: Price between levels or no clear signal
 * 
 * @package App\Services\Trading\Strategies
 */
class SupportResistanceStrategy
{
    private float $proximityThreshold;
    private int $minTouches;
    private int $lookbackPeriod;
    
    /**
     * Create new Support/Resistance strategy
     *
     * @param array<string, mixed> $parameters Optional parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->proximityThreshold = $parameters['proximity_threshold'] ?? 0.02;  // 2%
        $this->minTouches = $parameters['min_touches'] ?? 2;
        $this->lookbackPeriod = $parameters['lookback_period'] ?? 30;
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
        
        if ($dataCount < 10) {
            return [
                'signal' => 'HOLD',
                'confidence' => 0.3,
                'strategy' => 'SupportResistanceStrategy',
                'reason' => 'Insufficient data for support/resistance analysis'
            ];
        }
        
        // Calculate pivot point
        $pivotPoint = $this->calculatePivotPoint($historicalData);
        
        // Identify support and resistance levels
        $supportLevels = $this->findSupportLevels($historicalData);
        $resistanceLevels = $this->findResistanceLevels($historicalData);
        
        // Count touches for each level
        $supportTouches = $this->countTouches($historicalData, $supportLevels);
        $resistanceTouches = $this->countTouches($historicalData, $resistanceLevels);
        
        // Get current price
        $currentPrice = $historicalData[$dataCount - 1]['close'];
        
        // Find nearest support and resistance
        $nearestSupport = $this->findNearestLevel($currentPrice, $supportLevels, 'below');
        $nearestResistance = $this->findNearestLevel($currentPrice, $resistanceLevels, 'above');
        
        // Check for breakout/breakdown
        $breakout = $this->detectBreakout($historicalData, $resistanceLevels);
        $breakdown = $this->detectBreakdown($historicalData, $supportLevels);
        
        // Generate signal
        $signal = 'HOLD';
        $confidence = 0.5;
        $reason = '';
        
        if ($breakout) {
            $signal = 'BUY';
            $confidence = 0.75;
            $reason = 'Breakout above resistance with strong volume';
        } elseif ($breakdown) {
            $signal = 'SELL';
            $confidence = 0.75;
            $reason = 'Breakdown below support with strong volume';
        } elseif ($nearestSupport !== null && 
                  abs($currentPrice - $nearestSupport) / $currentPrice < $this->proximityThreshold) {
            $signal = 'BUY';
            $confidence = 0.65;
            $reason = 'Price at support level';
            
            // Increase confidence with multiple touches
            $touches = $supportTouches[$nearestSupport] ?? 0;
            if ($touches >= 3) {
                $confidence = 0.75;
                $reason .= ' (strong level, ' . $touches . ' touches)';
            }
        } elseif ($nearestResistance !== null && 
                  abs($currentPrice - $nearestResistance) / $currentPrice < $this->proximityThreshold) {
            $signal = 'SELL';
            $confidence = 0.65;
            $reason = 'Price at resistance level';
            
            // Increase confidence with multiple touches
            $touches = $resistanceTouches[$nearestResistance] ?? 0;
            if ($touches >= 3) {
                $confidence = 0.75;
                $reason .= ' (strong level, ' . $touches . ' touches)';
            }
        } else {
            $reason = 'Price between support/resistance levels';
            
            // Slight bias based on position relative to pivot
            if ($currentPrice > $pivotPoint) {
                $reason .= ' (above pivot point)';
            } elseif ($currentPrice < $pivotPoint) {
                $reason .= ' (below pivot point)';
            }
        }
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'strategy' => 'SupportResistanceStrategy',
            'reason' => $reason,
            'pivot_point' => $pivotPoint,
            'support_levels' => $supportLevels,
            'resistance_levels' => $resistanceLevels,
            'nearest_support' => $nearestSupport,
            'nearest_resistance' => $nearestResistance,
            'support_touches' => $supportTouches,
            'resistance_touches' => $resistanceTouches,
            'breakout' => $breakout,
            'breakdown' => $breakdown
        ];
    }
    
    /**
     * Calculate pivot point from recent data
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return float Pivot point
     */
    private function calculatePivotPoint(array $data): float
    {
        $dataCount = count($data);
        $recentBar = $data[$dataCount - 1];
        
        // Standard pivot point: (High + Low + Close) / 3
        return ($recentBar['high'] + $recentBar['low'] + $recentBar['close']) / 3;
    }
    
    /**
     * Find support levels in historical data
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return array<int, float> Support levels
     */
    private function findSupportLevels(array $data): array
    {
        $levels = [];
        $dataCount = count($data);
        $startIndex = max(0, $dataCount - $this->lookbackPeriod);
        
        // Find local lows
        for ($i = $startIndex + 1; $i < $dataCount - 1; $i++) {
            $currentLow = $data[$i]['low'];
            $prevLow = $data[$i - 1]['low'];
            $nextLow = $data[$i + 1]['low'];
            
            // Local minimum
            if ($currentLow < $prevLow && $currentLow < $nextLow) {
                $levels[] = $currentLow;
            }
        }
        
        // Cluster nearby levels
        return $this->clusterLevels($levels);
    }
    
    /**
     * Find resistance levels in historical data
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return array<int, float> Resistance levels
     */
    private function findResistanceLevels(array $data): array
    {
        $levels = [];
        $dataCount = count($data);
        $startIndex = max(0, $dataCount - $this->lookbackPeriod);
        
        // Find local highs
        for ($i = $startIndex + 1; $i < $dataCount - 1; $i++) {
            $currentHigh = $data[$i]['high'];
            $prevHigh = $data[$i - 1]['high'];
            $nextHigh = $data[$i + 1]['high'];
            
            // Local maximum
            if ($currentHigh > $prevHigh && $currentHigh > $nextHigh) {
                $levels[] = $currentHigh;
            }
        }
        
        // Cluster nearby levels
        return $this->clusterLevels($levels);
    }
    
    /**
     * Cluster nearby price levels
     *
     * @param array<int, float> $levels Price levels
     * @return array<int, float> Clustered levels
     */
    private function clusterLevels(array $levels): array
    {
        if (empty($levels)) {
            return [];
        }
        
        sort($levels);
        $clustered = [];
        $currentCluster = [$levels[0]];
        
        for ($i = 1; $i < count($levels); $i++) {
            $avgLevel = array_sum($currentCluster) / count($currentCluster);
            
            // If within threshold, add to current cluster
            if (abs($levels[$i] - $avgLevel) / $avgLevel < $this->proximityThreshold) {
                $currentCluster[] = $levels[$i];
            } else {
                // Save cluster average and start new cluster
                $clustered[] = $avgLevel;
                $currentCluster = [$levels[$i]];
            }
        }
        
        // Add final cluster
        $clustered[] = array_sum($currentCluster) / count($currentCluster);
        
        return $clustered;
    }
    
    /**
     * Count how many times each level has been touched
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<int, float> $levels Price levels
     * @return array<float, int> Level => touch count
     */
    private function countTouches(array $data, array $levels): array
    {
        $touches = [];
        
        foreach ($levels as $level) {
            $touches[$level] = 0;
            
            foreach ($data as $bar) {
                // Check if price touched this level
                if ($bar['low'] <= $level && $bar['high'] >= $level) {
                    $touches[$level]++;
                } elseif (abs($bar['close'] - $level) / $level < $this->proximityThreshold / 2) {
                    // Close price very near level
                    $touches[$level]++;
                }
            }
        }
        
        return $touches;
    }
    
    /**
     * Find nearest level to current price
     *
     * @param float $price Current price
     * @param array<int, float> $levels Available levels
     * @param string $direction 'above' or 'below'
     * @return float|null Nearest level or null
     */
    private function findNearestLevel(float $price, array $levels, string $direction): ?float
    {
        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;
        
        foreach ($levels as $level) {
            if ($direction === 'above' && $level > $price) {
                $distance = $level - $price;
            } elseif ($direction === 'below' && $level < $price) {
                $distance = $price - $level;
            } else {
                continue;
            }
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $level;
            }
        }
        
        return $nearest;
    }
    
    /**
     * Detect breakout above resistance
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<int, float> $resistanceLevels Resistance levels
     * @return bool True if breakout detected
     */
    private function detectBreakout(array $data, array $resistanceLevels): bool
    {
        if (empty($resistanceLevels)) {
            return false;
        }
        
        $dataCount = count($data);
        if ($dataCount < 2) {
            return false;
        }
        
        $currentBar = $data[$dataCount - 1];
        $previousBar = $data[$dataCount - 2];
        
        // Find nearest resistance below current price
        $nearestResistance = null;
        foreach ($resistanceLevels as $level) {
            if ($level < $currentBar['close'] && $level > $previousBar['close']) {
                if ($nearestResistance === null || $level > $nearestResistance) {
                    $nearestResistance = $level;
                }
            }
        }
        
        if ($nearestResistance === null) {
            return false;
        }
        
        // Breakout if:
        // 1. Previous close was below resistance
        // 2. Current close is above resistance
        // 3. Volume is above average
        $avgVolume = array_sum(array_column(array_slice($data, -10), 'volume')) / 10;
        
        return $previousBar['close'] < $nearestResistance &&
               $currentBar['close'] > $nearestResistance &&
               $currentBar['volume'] > $avgVolume * 1.2;
    }
    
    /**
     * Detect breakdown below support
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<int, float> $supportLevels Support levels
     * @return bool True if breakdown detected
     */
    private function detectBreakdown(array $data, array $supportLevels): bool
    {
        if (empty($supportLevels)) {
            return false;
        }
        
        $dataCount = count($data);
        if ($dataCount < 2) {
            return false;
        }
        
        $currentBar = $data[$dataCount - 1];
        $previousBar = $data[$dataCount - 2];
        
        // Find nearest support above current price
        $nearestSupport = null;
        foreach ($supportLevels as $level) {
            if ($level > $currentBar['close'] && $level < $previousBar['close']) {
                if ($nearestSupport === null || $level < $nearestSupport) {
                    $nearestSupport = $level;
                }
            }
        }
        
        if ($nearestSupport === null) {
            return false;
        }
        
        // Breakdown if:
        // 1. Previous close was above support
        // 2. Current close is below support
        // 3. Volume is above average
        $avgVolume = array_sum(array_column(array_slice($data, -10), 'volume')) / 10;
        
        return $previousBar['close'] > $nearestSupport &&
               $currentBar['close'] < $nearestSupport &&
               $currentBar['volume'] > $avgVolume * 1.2;
    }
}
