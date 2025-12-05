<?php

declare(strict_types=1);

namespace App\Services\Trading\Strategies;

/**
 * Volume Profile Strategy
 * 
 * Analyzes price distribution by volume to identify key support/resistance levels.
 * Focuses on Point of Control (POC), Value Area High/Low, and volume nodes.
 * 
 * Key Concepts:
 * - Point of Control (POC): Price level with highest volume
 * - Value Area: Range containing 70% of volume
 * - High Volume Nodes (HVN): Price levels with significant volume
 * - Low Volume Nodes (LVN): Price levels with minimal volume
 * 
 * Signals:
 * - BUY: Price at Value Area Low or bouncing from support
 * - SELL: Price at Value Area High or rejected from resistance
 * - HOLD: Price within Value Area or no clear signal
 * 
 * @package App\Services\Trading\Strategies
 */
class VolumeProfileStrategy
{
    private int $priceLevels;
    private float $valueAreaPercentage;
    private float $proximityThreshold;
    
    /**
     * Create new Volume Profile strategy
     *
     * @param array<string, mixed> $parameters Optional parameters
     */
    public function __construct(array $parameters = [])
    {
        $this->priceLevels = $parameters['price_levels'] ?? 30;
        $this->valueAreaPercentage = $parameters['value_area_percentage'] ?? 0.70;  // 70%
        $this->proximityThreshold = $parameters['proximity_threshold'] ?? 0.01;  // 1%
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
                'strategy' => 'VolumeProfileStrategy',
                'reason' => 'Insufficient data for volume profile analysis'
            ];
        }
        
        // Build volume profile
        $volumeProfile = $this->buildVolumeProfile($historicalData);
        
        // Find Point of Control (highest volume price level)
        $pointOfControl = $this->findPointOfControl($volumeProfile);
        
        // Calculate Value Area (70% of volume)
        $valueArea = $this->calculateValueArea($volumeProfile, $pointOfControl);
        
        // Identify high and low volume nodes
        $highVolumeNodes = $this->findHighVolumeNodes($volumeProfile);
        $lowVolumeNodes = $this->findLowVolumeNodes($volumeProfile);
        
        // Get current price
        $currentPrice = $historicalData[$dataCount - 1]['close'];
        
        // Determine price position
        $pricePosition = $this->determinePricePosition(
            $currentPrice,
            $valueArea['low'],
            $valueArea['high'],
            $pointOfControl
        );
        
        // Check if near POC
        $nearPOC = abs($currentPrice - $pointOfControl) / $pointOfControl < $this->proximityThreshold;
        
        // Calculate volume concentration
        $volumeConcentration = $this->calculateVolumeConcentration($volumeProfile);
        
        // Analyze price action
        $priceAction = $this->analyzePriceAction($historicalData, $valueArea, $pointOfControl);
        
        // Generate signal
        $signal = 'HOLD';
        $confidence = 0.5;
        $reason = '';
        
        // Check position relative to value area
        if ($pricePosition === 'at_value_area_low' || 
            ($pricePosition === 'below_value_area' && $priceAction === 'bouncing')) {
            $signal = 'BUY';
            $confidence = 0.7;
            $reason = 'Price at Value Area Low (support)';
            
            if ($nearPOC) {
                $confidence = 0.75;
                $reason .= ' near Point of Control';
            }
        } elseif ($pricePosition === 'at_value_area_high' || 
                  ($pricePosition === 'above_value_area' && $priceAction === 'rejecting')) {
            $signal = 'SELL';
            $confidence = 0.7;
            $reason = 'Price at Value Area High (resistance)';
            
            if ($nearPOC) {
                $confidence = 0.75;
                $reason .= ' near Point of Control';
            }
        } elseif ($pricePosition === 'below_value_area') {
            $signal = 'BUY';
            $confidence = 0.6;
            $reason = 'Price below Value Area (potential support)';
        } elseif ($pricePosition === 'above_value_area') {
            $signal = 'SELL';
            $confidence = 0.6;
            $reason = 'Price above Value Area (potential resistance)';
        } elseif ($pricePosition === 'at_poc') {
            $signal = 'HOLD';
            $confidence = 0.5;
            $reason = 'Price at Point of Control - neutral zone';
        } else {
            $reason = 'Price within Value Area - no clear signal';
        }
        
        return [
            'signal' => $signal,
            'confidence' => $confidence,
            'strategy' => 'VolumeProfileStrategy',
            'reason' => $reason,
            'volume_profile' => $volumeProfile,
            'point_of_control' => $pointOfControl,
            'value_area_high' => $valueArea['high'],
            'value_area_low' => $valueArea['low'],
            'price_position' => $pricePosition,
            'near_poc' => $nearPOC,
            'volume_concentration' => $volumeConcentration,
            'high_volume_nodes' => $highVolumeNodes,
            'low_volume_nodes' => $lowVolumeNodes
        ];
    }
    
    /**
     * Build volume profile from historical data
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @return array<string, float> Price level => total volume
     */
    private function buildVolumeProfile(array $data): array
    {
        // Find price range
        $allPrices = [];
        foreach ($data as $bar) {
            $allPrices[] = $bar['high'];
            $allPrices[] = $bar['low'];
        }
        
        $minPrice = min($allPrices);
        $maxPrice = max($allPrices);
        $priceRange = $maxPrice - $minPrice;
        
        if ($priceRange == 0) {
            return [];
        }
        
        $levelSize = $priceRange / $this->priceLevels;
        
        // Initialize levels
        $profile = [];
        for ($i = 0; $i < $this->priceLevels; $i++) {
            $level = $minPrice + ($i * $levelSize);
            $profile[number_format($level, 2)] = 0.0;
        }
        
        // Distribute volume across price levels
        foreach ($data as $bar) {
            $barRange = $bar['high'] - $bar['low'];
            if ($barRange == 0) {
                // All volume at close price
                $level = $this->findNearestLevel($bar['close'], array_keys($profile));
                $profile[$level] += $bar['volume'];
            } else {
                // Distribute volume proportionally across bar's range
                $volumePerPrice = $bar['volume'] / $barRange;
                
                foreach (array_keys($profile) as $levelKey) {
                    $level = (float)$levelKey;
                    if ($level >= $bar['low'] && $level <= $bar['high']) {
                        $profile[$levelKey] += $volumePerPrice * $levelSize;
                    }
                }
            }
        }
        
        return $profile;
    }
    
    /**
     * Find Point of Control (price with highest volume)
     *
     * @param array<string, float> $profile Volume profile
     * @return float POC price level
     */
    private function findPointOfControl(array $profile): float
    {
        $maxVolume = 0.0;
        $poc = 0.0;
        
        foreach ($profile as $price => $volume) {
            if ($volume > $maxVolume) {
                $maxVolume = $volume;
                $poc = (float)$price;
            }
        }
        
        return $poc;
    }
    
    /**
     * Calculate Value Area (70% of volume)
     *
     * @param array<string, float> $profile Volume profile
     * @param float $poc Point of Control
     * @return array<string, float> Value area high and low
     */
    private function calculateValueArea(array $profile, float $poc): array
    {
        $totalVolume = array_sum($profile);
        $targetVolume = $totalVolume * $this->valueAreaPercentage;
        
        // Sort by price
        ksort($profile, SORT_NUMERIC);
        $prices = array_keys($profile);
        
        // Find POC index
        $pocIndex = 0;
        foreach ($prices as $index => $price) {
            if ((float)$price >= $poc) {
                $pocIndex = $index;
                break;
            }
        }
        
        // Expand from POC until we capture target volume
        $lowIndex = $pocIndex;
        $highIndex = $pocIndex;
        $capturedVolume = $profile[$prices[$pocIndex]];
        
        while ($capturedVolume < $targetVolume && ($lowIndex > 0 || $highIndex < count($prices) - 1)) {
            $addLow = ($lowIndex > 0) ? $profile[$prices[$lowIndex - 1]] : 0;
            $addHigh = ($highIndex < count($prices) - 1) ? $profile[$prices[$highIndex + 1]] : 0;
            
            if ($addLow > $addHigh && $lowIndex > 0) {
                $lowIndex--;
                $capturedVolume += $addLow;
            } elseif ($highIndex < count($prices) - 1) {
                $highIndex++;
                $capturedVolume += $addHigh;
            } else {
                break;
            }
        }
        
        return [
            'low' => (float)$prices[$lowIndex],
            'high' => (float)$prices[$highIndex]
        ];
    }
    
    /**
     * Determine price position relative to value area
     *
     * @param float $price Current price
     * @param float $valueLow Value Area Low
     * @param float $valueHigh Value Area High
     * @param float $poc Point of Control
     * @return string Position description
     */
    private function determinePricePosition(
        float $price,
        float $valueLow,
        float $valueHigh,
        float $poc
    ): string {
        $tolerance = $poc * $this->proximityThreshold;
        
        if (abs($price - $poc) < $tolerance) {
            return 'at_poc';
        } elseif (abs($price - $valueLow) < $tolerance) {
            return 'at_value_area_low';
        } elseif (abs($price - $valueHigh) < $tolerance) {
            return 'at_value_area_high';
        } elseif ($price < $valueLow) {
            return 'below_value_area';
        } elseif ($price > $valueHigh) {
            return 'above_value_area';
        } else {
            return 'in_value_area';
        }
    }
    
    /**
     * Calculate volume concentration (how concentrated volume is)
     *
     * @param array<string, float> $profile Volume profile
     * @return float Concentration ratio (0-1)
     */
    private function calculateVolumeConcentration(array $profile): float
    {
        if (empty($profile)) {
            return 0.0;
        }
        
        $totalVolume = array_sum($profile);
        if ($totalVolume == 0) {
            return 0.0;
        }
        
        // Calculate Gini coefficient as concentration measure
        $volumes = array_values($profile);
        sort($volumes);
        
        $n = count($volumes);
        $sum = 0.0;
        
        for ($i = 0; $i < $n; $i++) {
            $sum += ($i + 1) * $volumes[$i];
        }
        
        $concentration = (2 * $sum) / ($n * $totalVolume) - ($n + 1) / $n;
        
        return max(0.0, min(1.0, $concentration));
    }
    
    /**
     * Find high volume nodes
     *
     * @param array<string, float> $profile Volume profile
     * @return array<int, float> High volume price levels
     */
    private function findHighVolumeNodes(array $profile): array
    {
        $avgVolume = array_sum($profile) / count($profile);
        $threshold = $avgVolume * 1.5;  // 50% above average
        
        $nodes = [];
        foreach ($profile as $price => $volume) {
            if ($volume > $threshold) {
                $nodes[] = (float)$price;
            }
        }
        
        return $nodes;
    }
    
    /**
     * Find low volume nodes
     *
     * @param array<string, float> $profile Volume profile
     * @return array<int, float> Low volume price levels
     */
    private function findLowVolumeNodes(array $profile): array
    {
        $avgVolume = array_sum($profile) / count($profile);
        $threshold = $avgVolume * 0.5;  // 50% below average
        
        $nodes = [];
        foreach ($profile as $price => $volume) {
            if ($volume < $threshold) {
                $nodes[] = (float)$price;
            }
        }
        
        return $nodes;
    }
    
    /**
     * Analyze recent price action
     *
     * @param array<int, array<string, mixed>> $data Historical data
     * @param array<string, float> $valueArea Value area
     * @param float $poc Point of Control
     * @return string Price action description
     */
    private function analyzePriceAction(array $data, array $valueArea, float $poc): string
    {
        $dataCount = count($data);
        
        if ($dataCount < 3) {
            return 'none';
        }
        
        $currentBar = $data[$dataCount - 1];
        $previousBar = $data[$dataCount - 2];
        
        $currentPrice = $currentBar['close'];
        $previousPrice = $previousBar['close'];
        
        // Check for bounce at Value Area Low
        if ($previousPrice <= $valueArea['low'] && $currentPrice > $previousPrice) {
            return 'bouncing';
        }
        
        // Check for rejection at Value Area High
        if ($previousPrice >= $valueArea['high'] && $currentPrice < $previousPrice) {
            return 'rejecting';
        }
        
        return 'none';
    }
    
    /**
     * Find nearest price level in profile
     *
     * @param float $price Target price
     * @param array<int, string> $levels Available levels
     * @return string Nearest level
     */
    private function findNearestLevel(float $price, array $levels): string
    {
        $minDistance = PHP_FLOAT_MAX;
        $nearest = $levels[0];
        
        foreach ($levels as $level) {
            $distance = abs($price - (float)$level);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $level;
            }
        }
        
        return $nearest;
    }
}
