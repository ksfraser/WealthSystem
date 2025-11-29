<?php

namespace App\Services\Trading;

use App\Services\MarketDataService;
use App\Repositories\MarketDataRepositoryInterface;

/**
 * Support and Resistance Strategy
 * 
 * Technical analysis strategy that identifies key support and resistance levels
 * using historical price pivots and generates signals when price approaches
 * these critical levels.
 * 
 * Strategy Overview:
 * - Identifies support levels (where price historically bounces up)
 * - Identifies resistance levels (where price historically gets rejected down)
 * - BUY signals near support with volume confirmation
 * - SELL signals near resistance
 * - Uses multiple timeframe analysis for stronger signals
 * 
 * Entry Rules:
 * - BUY: Price approaches support level (within tolerance)
 * - SHORT: Price approaches resistance level from below
 * 
 * Exit Rules:
 * - SELL: Price reaches resistance or breaks below support
 * - COVER: Price breaks above resistance
 * 
 * Level Identification:
 * - Pivot points: Local highs and lows over lookback period
 * - Clustering: Multiple touches at similar price levels
 * - Volume confirmation: Higher volume at bounce/rejection points
 * - Strength rating: Based on number of touches and hold time
 * 
 * @package App\Services\Trading
 */
class SupportResistanceStrategyService implements TradingStrategyInterface
{
    private const SIGNAL_BUY = 'BUY';
    private const SIGNAL_SELL = 'SELL';
    private const SIGNAL_SHORT = 'SHORT';
    private const SIGNAL_COVER = 'COVER';
    private const SIGNAL_HOLD = 'HOLD';

    private MarketDataService $marketDataService;
    private MarketDataRepositoryInterface $marketDataRepository;
    
    /**
     * Strategy parameters
     * 
     * @var array
     */
    private array $parameters = [
        'lookback_period' => 60,        // Days to look back for pivots
        'pivot_strength' => 3,          // Bars on each side for pivot
        'level_tolerance' => 0.02,      // 2% tolerance for level proximity
        'min_touches' => 2,             // Minimum touches to confirm level
        'volume_threshold' => 1.2,      // 20% above average volume
        'stop_loss_percent' => 0.05,    // 5% stop loss
        'take_profit_percent' => 0.10,  // 10% take profit
        'position_size' => 0.10,        // 10% position size
        'require_volume_confirmation' => true
    ];

    public function __construct(
        MarketDataService $marketDataService,
        MarketDataRepositoryInterface $marketDataRepository
    ) {
        $this->marketDataService = $marketDataService;
        $this->marketDataRepository = $marketDataRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Support and Resistance';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Identifies key support and resistance levels from historical pivots. '
             . 'Generates BUY signals when price approaches support, SELL at resistance. '
             . 'Uses volume confirmation and multiple touches for level validation.';
    }

    /**
     * {@inheritdoc}
     */
    public function analyze(string $symbol, string $date = 'today'): array
    {
        // Validate inputs
        if (empty($symbol)) {
            throw new \InvalidArgumentException('Symbol cannot be empty');
        }

        // Get historical price data
        $requiredDays = $this->getRequiredHistoricalDays();
        $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, null, null, $requiredDays);

        if (empty($priceHistory) || count($priceHistory) < $requiredDays) {
            return $this->createHoldSignal('Insufficient price data for support/resistance analysis');
        }

        // Get current price
        $currentPriceData = $this->marketDataService->getCurrentPrice($symbol);
        $currentPrice = $currentPriceData['price'] ?? 0;
        
        if ($currentPrice < 0.01) {
            return $this->createHoldSignal('Invalid price data');
        }

        // Identify support and resistance levels
        $supportLevels = $this->identifySupportLevels($priceHistory);
        $resistanceLevels = $this->identifyResistanceLevels($priceHistory);

        if (empty($supportLevels) && empty($resistanceLevels)) {
            return $this->createHoldSignal('Unable to identify support/resistance levels');
        }

        // Calculate average volume for confirmation
        $avgVolume = $this->calculateAverageVolume($priceHistory);
        $currentVolume = end($priceHistory)['volume'] ?? 0;
        $volumeConfirmed = ($currentVolume >= $avgVolume * $this->parameters['volume_threshold']);

        // Check if price is near support (potential BUY)
        $nearestSupport = $this->findNearestLevel($currentPrice, $supportLevels);
        if ($nearestSupport !== null) {
            $distanceToSupport = abs($currentPrice - $nearestSupport['price']) / $currentPrice;
            
            if ($distanceToSupport <= $this->parameters['level_tolerance']) {
                // Price is near support
                if (!$this->parameters['require_volume_confirmation'] || $volumeConfirmed) {
                    $stopLoss = $currentPrice * (1 - $this->parameters['stop_loss_percent']);
                    $takeProfit = $currentPrice * (1 + $this->parameters['take_profit_percent']);
                    
                    $confidence = $this->calculateConfidence($nearestSupport, $volumeConfirmed);
                    
                    return $this->createBuySignal(
                        $symbol,
                        $currentPrice,
                        $stopLoss,
                        $takeProfit,
                        $nearestSupport,
                        $confidence,
                        'Price near support at ' . number_format($nearestSupport['price'], 2) 
                        . ' (' . $nearestSupport['touches'] . ' touches)'
                        . ($volumeConfirmed ? ' with volume confirmation' : '')
                    );
                } else {
                    return $this->createHoldSignal(
                        'Price near support at ' . number_format($nearestSupport['price'], 2) 
                        . ' but volume too low for confirmation'
                    );
                }
            }
        }

        // Check if price is near resistance (potential SELL/SHORT)
        $nearestResistance = $this->findNearestLevel($currentPrice, $resistanceLevels);
        if ($nearestResistance !== null) {
            $distanceToResistance = abs($currentPrice - $nearestResistance['price']) / $currentPrice;
            
            if ($distanceToResistance <= $this->parameters['level_tolerance']) {
                // Price is near resistance
                $confidence = $this->calculateConfidence($nearestResistance, $volumeConfirmed);
                
                return $this->createSellSignal(
                    $currentPrice,
                    $nearestResistance,
                    $confidence,
                    'Price near resistance at ' . number_format($nearestResistance['price'], 2) 
                    . ' (' . $nearestResistance['touches'] . ' touches)'
                );
            }
        }

        // Price is between levels
        $supportInfo = $nearestSupport ? number_format($nearestSupport['price'], 2) : 'none';
        $resistanceInfo = $nearestResistance ? number_format($nearestResistance['price'], 2) : 'none';
        
        return $this->createHoldSignal(
            'Price between levels. Support: ' . $supportInfo . ', Resistance: ' . $resistanceInfo
        );
    }

    /**
     * Identify support levels from price history
     * 
     * @param array $priceHistory Historical price data
     * @return array Support levels with strength
     */
    private function identifySupportLevels(array $priceHistory): array
    {
        $pivotLows = $this->findPivotLows($priceHistory);
        return $this->clusterLevels($pivotLows);
    }

    /**
     * Identify resistance levels from price history
     * 
     * @param array $priceHistory Historical price data
     * @return array Resistance levels with strength
     */
    private function identifyResistanceLevels(array $priceHistory): array
    {
        $pivotHighs = $this->findPivotHighs($priceHistory);
        return $this->clusterLevels($pivotHighs);
    }

    /**
     * Find pivot lows (local minima)
     * 
     * @param array $priceHistory Historical price data
     * @return array Pivot low prices
     */
    private function findPivotLows(array $priceHistory): array
    {
        $pivots = [];
        $strength = $this->parameters['pivot_strength'];
        $count = count($priceHistory);

        for ($i = $strength; $i < $count - $strength; $i++) {
            $currentLow = $priceHistory[$i]['low'];
            $isPivot = true;

            // Check left side
            for ($j = 1; $j <= $strength; $j++) {
                if ($priceHistory[$i - $j]['low'] <= $currentLow) {
                    $isPivot = false;
                    break;
                }
            }

            // Check right side
            if ($isPivot) {
                for ($j = 1; $j <= $strength; $j++) {
                    if ($priceHistory[$i + $j]['low'] <= $currentLow) {
                        $isPivot = false;
                        break;
                    }
                }
            }

            if ($isPivot) {
                $pivots[] = [
                    'price' => $currentLow,
                    'date' => $priceHistory[$i]['date'],
                    'volume' => $priceHistory[$i]['volume']
                ];
            }
        }

        return $pivots;
    }

    /**
     * Find pivot highs (local maxima)
     * 
     * @param array $priceHistory Historical price data
     * @return array Pivot high prices
     */
    private function findPivotHighs(array $priceHistory): array
    {
        $pivots = [];
        $strength = $this->parameters['pivot_strength'];
        $count = count($priceHistory);

        for ($i = $strength; $i < $count - $strength; $i++) {
            $currentHigh = $priceHistory[$i]['high'];
            $isPivot = true;

            // Check left side
            for ($j = 1; $j <= $strength; $j++) {
                if ($priceHistory[$i - $j]['high'] >= $currentHigh) {
                    $isPivot = false;
                    break;
                }
            }

            // Check right side
            if ($isPivot) {
                for ($j = 1; $j <= $strength; $j++) {
                    if ($priceHistory[$i + $j]['high'] >= $currentHigh) {
                        $isPivot = false;
                        break;
                    }
                }
            }

            if ($isPivot) {
                $pivots[] = [
                    'price' => $currentHigh,
                    'date' => $priceHistory[$i]['date'],
                    'volume' => $priceHistory[$i]['volume']
                ];
            }
        }

        return $pivots;
    }

    /**
     * Cluster similar price levels together
     * 
     * @param array $pivots Pivot points
     * @return array Clustered levels with touch count
     */
    private function clusterLevels(array $pivots): array
    {
        if (empty($pivots)) {
            return [];
        }

        $levels = [];
        $tolerance = $this->parameters['level_tolerance'];

        foreach ($pivots as $pivot) {
            $foundCluster = false;

            foreach ($levels as &$level) {
                $priceDiff = abs($pivot['price'] - $level['price']) / $level['price'];
                
                if ($priceDiff <= $tolerance) {
                    // Add to existing cluster
                    $level['price'] = ($level['price'] * $level['touches'] + $pivot['price']) / ($level['touches'] + 1);
                    $level['touches']++;
                    $level['total_volume'] += $pivot['volume'];
                    $foundCluster = true;
                    break;
                }
            }

            if (!$foundCluster) {
                // Create new cluster
                $levels[] = [
                    'price' => $pivot['price'],
                    'touches' => 1,
                    'total_volume' => $pivot['volume'],
                    'last_touch' => $pivot['date']
                ];
            }
        }

        // Filter by minimum touches
        $levels = array_filter($levels, function($level) {
            return $level['touches'] >= $this->parameters['min_touches'];
        });

        // Sort by touches (strongest levels first)
        usort($levels, function($a, $b) {
            return $b['touches'] - $a['touches'];
        });

        return array_values($levels);
    }

    /**
     * Find nearest level to current price
     * 
     * @param float $currentPrice Current price
     * @param array $levels Support or resistance levels
     * @return array|null Nearest level or null
     */
    private function findNearestLevel(float $currentPrice, array $levels): ?array
    {
        if (empty($levels)) {
            return null;
        }

        $nearest = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($levels as $level) {
            $distance = abs($currentPrice - $level['price']);
            
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearest = $level;
            }
        }

        return $nearest;
    }

    /**
     * Calculate average volume
     * 
     * @param array $priceHistory Historical price data
     * @return float Average volume
     */
    private function calculateAverageVolume(array $priceHistory): float
    {
        $volumes = array_column($priceHistory, 'volume');
        return array_sum($volumes) / count($volumes);
    }

    /**
     * Calculate confidence based on level strength and volume
     * 
     * @param array $level Support or resistance level
     * @param bool $volumeConfirmed Volume confirmation
     * @return float Confidence (0.0 to 1.0)
     */
    private function calculateConfidence(array $level, bool $volumeConfirmed): float
    {
        $baseConfidence = 0.60;
        
        // Bonus for multiple touches
        $touchBonus = min(0.20, ($level['touches'] - 2) * 0.05);
        $baseConfidence += $touchBonus;
        
        // Bonus for volume confirmation
        if ($volumeConfirmed) {
            $baseConfidence += 0.15;
        }
        
        return min(1.0, $baseConfidence);
    }

    /**
     * Create a BUY signal
     */
    private function createBuySignal(
        string $symbol,
        float $price,
        float $stopLoss,
        float $takeProfit,
        array $supportLevel,
        float $confidence,
        string $reason
    ): array {
        return [
            'signal' => self::SIGNAL_BUY,
            'confidence' => $confidence,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'position_size' => $this->parameters['position_size'],
            'metadata' => [
                'support_level' => $supportLevel['price'],
                'level_touches' => $supportLevel['touches'],
                'level_strength' => $this->calculateLevelStrength($supportLevel),
                'distance_to_level' => abs($price - $supportLevel['price']) / $price,
                'risk_reward_ratio' => ($takeProfit - $price) / ($price - $stopLoss)
            ]
        ];
    }

    /**
     * Create a SELL signal
     */
    private function createSellSignal(
        float $price,
        array $resistanceLevel,
        float $confidence,
        string $reason
    ): array {
        return [
            'signal' => self::SIGNAL_SELL,
            'confidence' => $confidence,
            'reason' => $reason,
            'entry_price' => $price,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => 1.0, // Exit entire position
            'metadata' => [
                'resistance_level' => $resistanceLevel['price'],
                'level_touches' => $resistanceLevel['touches'],
                'level_strength' => $this->calculateLevelStrength($resistanceLevel),
                'distance_to_level' => abs($price - $resistanceLevel['price']) / $price
            ]
        ];
    }

    /**
     * Create a HOLD signal
     */
    private function createHoldSignal(string $reason): array
    {
        return [
            'signal' => self::SIGNAL_HOLD,
            'confidence' => 0.0,
            'reason' => $reason,
            'entry_price' => null,
            'stop_loss' => null,
            'take_profit' => null,
            'position_size' => null,
            'metadata' => []
        ];
    }

    /**
     * Calculate level strength score
     * 
     * @param array $level Support or resistance level
     * @return float Strength score (0.0 to 1.0)
     */
    private function calculateLevelStrength(array $level): float
    {
        // Based on number of touches
        $touchScore = min(1.0, $level['touches'] / 5.0);
        
        // Based on volume at touches
        $avgTouchVolume = $level['total_volume'] / $level['touches'];
        $volumeScore = min(1.0, $avgTouchVolume / 1000000); // Normalize
        
        return ($touchScore * 0.7) + ($volumeScore * 0.3);
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function canExecute(string $symbol): bool
    {
        try {
            $requiredDays = $this->getRequiredHistoricalDays();
            $priceHistory = $this->marketDataService->getHistoricalPrices($symbol, null, null, $requiredDays);
            
            return count($priceHistory) >= $requiredDays;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredHistoricalDays(): int
    {
        // Need lookback period + pivot strength buffer
        return $this->parameters['lookback_period'] + ($this->parameters['pivot_strength'] * 2);
    }
}
