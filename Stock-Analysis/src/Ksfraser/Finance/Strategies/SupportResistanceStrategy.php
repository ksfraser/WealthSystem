<?php
namespace Ksfraser\Finance\Strategies;

use Ksfraser\Finance\Interfaces\TradingStrategyInterface;
use Ksfraser\Finance\Services\StockDataService;
use Ksfraser\Finance\Constants\StrategyConstants;

/**
 * Support and Resistance Strategy
 * 
 * Refactored from 2000/strategies/buyLeadingStocksAtSupport.php
 * Implements buying leading stocks at support levels with technical confirmation
 */
class SupportResistanceStrategy implements TradingStrategyInterface
{
    private StockDataService $stockDataService;
    private array $parameters;

    public function __construct(StockDataService $stockDataService, array $parameters = [])
    {
        $this->stockDataService = $stockDataService;
        $this->parameters = array_merge([
            'lookback_period' => 50,        // Period to find support/resistance levels
            'support_threshold' => 0.02,    // 2% threshold for support proximity
            'resistance_threshold' => 0.02, // 2% threshold for resistance proximity
            'ma_period' => 50,              // Moving average confirmation period
            'volume_confirmation' => true,   // Require volume confirmation
            'volume_multiplier' => 1.2,     // Volume must be 20% above average
            'trend_channel_period' => 20,   // Trend channel calculation period
            'min_touches' => 2,             // Minimum touches for valid support/resistance
            'leading_stock_criteria' => [   // Criteria for leading stocks
                'min_price' => 10,          // Minimum stock price
                'min_volume' => 100000,     // Minimum daily volume
                'market_cap_min' => 1000000000 // Minimum market cap ($1B)
            ]
        ], $parameters);
    }

    public function generateSignal(string $symbol, ?array $marketData = null): ?array
    {
        if (!$marketData) {
            $marketData = $this->stockDataService->getStockData($symbol, '1y');
        }

        if (empty($marketData) || count($marketData) < $this->parameters['lookback_period']) {
            return null;
        }

        $latest = end($marketData);
        $currentPrice = $latest['close'];
        $currentVolume = $latest['volume'];

        // Check if this is a leading stock
        if (!$this->isLeadingStock($latest, $marketData)) {
            return null;
        }

        // Find support and resistance levels
        $supportLevels = $this->findSupportLevels($marketData);
        $resistanceLevels = $this->findResistanceLevels($marketData);

        // Check for support buying opportunity
        $supportSignal = $this->checkSupportBuySignal($currentPrice, $currentVolume, $marketData, $supportLevels);
        if ($supportSignal) {
            return $supportSignal;
        }

        // Check for resistance selling opportunity
        $resistanceSignal = $this->checkResistanceSellSignal($currentPrice, $currentVolume, $marketData, $resistanceLevels);
        if ($resistanceSignal) {
            return $resistanceSignal;
        }

        return null;
    }

    /**
     * Check if stock meets leading stock criteria
     */
    private function isLeadingStock(array $latestData, array $marketData): bool
    {
        $criteria = $this->parameters['leading_stock_criteria'];
        
        // Price criterion
        if ($latestData['close'] < $criteria['min_price']) {
            return false;
        }

        // Volume criterion
        if ($latestData['volume'] < $criteria['min_volume']) {
            return false;
        }

        // Additional criteria could include:
        // - Relative strength vs market
        // - Recent performance
        // - Institutional ownership
        // For now, we'll use price and volume

        return true;
    }

    /**
     * Find support levels using swing lows
     */
    private function findSupportLevels(array $marketData): array
    {
        $lookback = $this->parameters['lookback_period'];
        $minTouches = $this->parameters['min_touches'];
        $threshold = $this->parameters['support_threshold'];
        
        $recentData = array_slice($marketData, -$lookback);
        $supportLevels = [];

        // Find swing lows
        for ($i = 2; $i < count($recentData) - 2; $i++) {
            $current = $recentData[$i];
            $prev2 = $recentData[$i - 2];
            $prev1 = $recentData[$i - 1];
            $next1 = $recentData[$i + 1];
            $next2 = $recentData[$i + 2];

            // Check if this is a swing low
            if ($current['low'] <= $prev2['low'] && 
                $current['low'] <= $prev1['low'] && 
                $current['low'] <= $next1['low'] && 
                $current['low'] <= $next2['low']) {
                
                $supportLevel = $current['low'];
                
                // Count how many times price has touched this level
                $touches = $this->countLevelTouches($recentData, $supportLevel, $threshold, 'support');
                
                if ($touches >= $minTouches) {
                    $supportLevels[] = [
                        'level' => $supportLevel,
                        'touches' => $touches,
                        'strength' => $touches / $lookback, // Relative strength
                        'date' => $current['date']
                    ];
                }
            }
        }

        // Sort by strength (most touches first)
        usort($supportLevels, fn($a, $b) => $b['touches'] <=> $a['touches']);

        return array_slice($supportLevels, 0, 5); // Return top 5 support levels
    }

    /**
     * Find resistance levels using swing highs
     */
    private function findResistanceLevels(array $marketData): array
    {
        $lookback = $this->parameters['lookback_period'];
        $minTouches = $this->parameters['min_touches'];
        $threshold = $this->parameters['resistance_threshold'];
        
        $recentData = array_slice($marketData, -$lookback);
        $resistanceLevels = [];

        // Find swing highs
        for ($i = 2; $i < count($recentData) - 2; $i++) {
            $current = $recentData[$i];
            $prev2 = $recentData[$i - 2];
            $prev1 = $recentData[$i - 1];
            $next1 = $recentData[$i + 1];
            $next2 = $recentData[$i + 2];

            // Check if this is a swing high
            if ($current['high'] >= $prev2['high'] && 
                $current['high'] >= $prev1['high'] && 
                $current['high'] >= $next1['high'] && 
                $current['high'] >= $next2['high']) {
                
                $resistanceLevel = $current['high'];
                
                // Count how many times price has touched this level
                $touches = $this->countLevelTouches($recentData, $resistanceLevel, $threshold, 'resistance');
                
                if ($touches >= $minTouches) {
                    $resistanceLevels[] = [
                        'level' => $resistanceLevel,
                        'touches' => $touches,
                        'strength' => $touches / $lookback,
                        'date' => $current['date']
                    ];
                }
            }
        }

        // Sort by strength
        usort($resistanceLevels, fn($a, $b) => $b['touches'] <=> $a['touches']);

        return array_slice($resistanceLevels, 0, 5);
    }

    /**
     * Count how many times price has touched a level
     */
    private function countLevelTouches(array $marketData, float $level, float $threshold, string $type): int
    {
        $touches = 0;
        $toleranceRange = $level * $threshold;

        foreach ($marketData as $data) {
            if ($type === 'support') {
                if (abs($data['low'] - $level) <= $toleranceRange) {
                    $touches++;
                }
            } else { // resistance
                if (abs($data['high'] - $level) <= $toleranceRange) {
                    $touches++;
                }
            }
        }

        return $touches;
    }

    /**
     * Check for support buy signal
     */
    private function checkSupportBuySignal(float $currentPrice, int $currentVolume, array $marketData, array $supportLevels): ?array
    {
        if (empty($supportLevels)) {
            return null;
        }

        $threshold = $this->parameters['support_threshold'];
        
        // Find the nearest support level
        $nearestSupport = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($supportLevels as $support) {
            $distance = abs($currentPrice - $support['level']) / $support['level'];
            if ($distance < $minDistance && $currentPrice >= $support['level']) {
                $minDistance = $distance;
                $nearestSupport = $support;
            }
        }

        if (!$nearestSupport || $minDistance > $threshold) {
            return null; // Not near support
        }

        // Additional confirmations
        $confirmations = [];
        $confidence = 0.5;

        // 50-day moving average confirmation
        $ma50 = $this->calculateMovingAverage($marketData, $this->parameters['ma_period']);
        if ($currentPrice > $ma50) {
            $confirmations[] = "Above 50-day MA ({$ma50})";
            $confidence += 0.2;
        }

        // Volume confirmation
        if ($this->parameters['volume_confirmation']) {
            $avgVolume = $this->calculateAverageVolume($marketData, 20);
            $volumeMultiplier = $this->parameters['volume_multiplier'];
            
            if ($currentVolume >= $avgVolume * $volumeMultiplier) {
                $confirmations[] = "Strong volume ({$currentVolume} vs avg {$avgVolume})";
                $confidence += 0.15;
            }
        }

        // Trend channel confirmation
        $trendDirection = $this->getTrendDirection($marketData);
        if ($trendDirection === 'up' || $trendDirection === 'sideways') {
            $confirmations[] = "Favorable trend ({$trendDirection})";
            $confidence += 0.1;
        }

        // Support strength confirmation
        if ($nearestSupport['touches'] >= 3) {
            $confirmations[] = "Strong support ({$nearestSupport['touches']} touches)";
            $confidence += 0.05;
        }

        // Only signal if we have sufficient confidence
        if ($confidence >= 0.65) {
            return [
                'action' => 'BUY',
                'price' => $currentPrice,
                'stop_loss' => $nearestSupport['level'] * 0.98, // 2% below support
                'take_profit' => $this->calculateTakeProfit($currentPrice, $nearestSupport['level']),
                'confidence' => $confidence,
                'reasoning' => "Support buy at {$currentPrice}, support level {$nearestSupport['level']}. " . 
                             implode(', ', $confirmations),
                'support_level' => $nearestSupport['level'],
                'support_strength' => $nearestSupport['touches'],
                'distance_from_support' => $minDistance * 100,
                'confirmations' => $confirmations
            ];
        }

        return null;
    }

    /**
     * Check for resistance sell signal
     */
    private function checkResistanceSellSignal(float $currentPrice, int $currentVolume, array $marketData, array $resistanceLevels): ?array
    {
        if (empty($resistanceLevels)) {
            return null;
        }

        $threshold = $this->parameters['resistance_threshold'];
        
        // Find the nearest resistance level
        $nearestResistance = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($resistanceLevels as $resistance) {
            $distance = abs($currentPrice - $resistance['level']) / $resistance['level'];
            if ($distance < $minDistance && $currentPrice <= $resistance['level']) {
                $minDistance = $distance;
                $nearestResistance = $resistance;
            }
        }

        if (!$nearestResistance || $minDistance > $threshold) {
            return null;
        }

        // Additional confirmations for selling
        $confirmations = [];
        $confidence = 0.5;

        // Below moving average
        $ma50 = $this->calculateMovingAverage($marketData, $this->parameters['ma_period']);
        if ($currentPrice < $ma50) {
            $confirmations[] = "Below 50-day MA ({$ma50})";
            $confidence += 0.2;
        }

        // Volume confirmation
        if ($this->parameters['volume_confirmation']) {
            $avgVolume = $this->calculateAverageVolume($marketData, 20);
            $volumeMultiplier = $this->parameters['volume_multiplier'];
            
            if ($currentVolume >= $avgVolume * $volumeMultiplier) {
                $confirmations[] = "High volume selling";
                $confidence += 0.15;
            }
        }

        if ($confidence >= 0.65) {
            return [
                'action' => 'SELL',
                'price' => $currentPrice,
                'stop_loss' => $nearestResistance['level'] * 1.02, // 2% above resistance
                'confidence' => $confidence,
                'reasoning' => "Resistance sell at {$currentPrice}, resistance level {$nearestResistance['level']}. " . 
                             implode(', ', $confirmations),
                'resistance_level' => $nearestResistance['level'],
                'resistance_strength' => $nearestResistance['touches'],
                'distance_from_resistance' => $minDistance * 100
            ];
        }

        return null;
    }

    private function calculateMovingAverage(array $marketData, int $period): float
    {
        $closes = array_column(array_slice($marketData, -$period), 'close');
        return array_sum($closes) / count($closes);
    }

    private function calculateAverageVolume(array $marketData, int $period): int
    {
        $volumes = array_column(array_slice($marketData, -$period), 'volume');
        return (int)(array_sum($volumes) / count($volumes));
    }

    private function getTrendDirection(array $marketData): string
    {
        $period = $this->parameters['trend_channel_period'];
        $recentData = array_slice($marketData, -$period);
        
        $firstPrice = $recentData[0]['close'];
        $lastPrice = end($recentData)['close'];
        
        $change = ($lastPrice - $firstPrice) / $firstPrice;
        
        if ($change > 0.05) return 'up';
        if ($change < -0.05) return 'down';
        return 'sideways';
    }

    private function calculateTakeProfit(float $currentPrice, float $supportLevel): float
    {
        $riskDistance = $currentPrice - $supportLevel;
        $rewardRatio = 2.0; // 2:1 reward to risk ratio
        return $currentPrice + ($riskDistance * $rewardRatio);
    }

    public function getName(): string
    {
        return "Support and Resistance Strategy";
    }

    public function getDescription(): string
    {
        return "Buy leading stocks at support levels with technical confirmation including 50-day MA, volume, and trend analysis";
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    public function validateParameters(array $parameters): bool
    {
        $required = ['lookback_period', 'support_threshold', 'ma_period'];
        
        foreach ($required as $param) {
            if (!isset($parameters[$param]) || !is_numeric($parameters[$param])) {
                return false;
            }
        }

        return $parameters['lookback_period'] > 0 && 
               $parameters['support_threshold'] > 0 && 
               $parameters['ma_period'] > 0;
    }
}
