<?php

declare(strict_types=1);

namespace App\Strategy;

/**
 * VWAP (Volume-Weighted Average Price) Trading Strategy
 * 
 * VWAP represents the average price weighted by trading volume.
 * Formula: VWAP = Σ(Price × Volume) / Σ(Volume)
 * 
 * Trading Logic:
 * - Price significantly below VWAP → BUY (oversold, bargain prices)
 * - Price significantly above VWAP → SELL (overbought, premium prices)
 * - Price near VWAP → HOLD (fair value)
 * 
 * VWAP is commonly used by institutional traders to:
 * - Assess execution quality
 * - Identify value zones
 * - Determine support/resistance levels
 * 
 * @package App\Strategy
 */
class VWAPStrategy
{
    /**
     * Strategy configuration
     *
     * @var array{
     *     deviation_threshold: float,
     *     min_data_points: int
     * }
     */
    private array $config;

    /**
     * Initialize VWAP strategy with optional configuration.
     *
     * @param array{
     *     deviation_threshold?: float,
     *     min_data_points?: int
     * } $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'deviation_threshold' => 2.0,  // Signal when price deviates >2% from VWAP
            'min_data_points' => 5         // Minimum data points for analysis
        ], $config);
    }

    /**
     * Analyze market data and generate trading signal using VWAP.
     *
     * @param array{
     *     symbol: string,
     *     price: float,
     *     high: float,
     *     low: float,
     *     volume: float,
     *     prices: array<float>,
     *     volumes: array<float>,
     *     highs: array<float>,
     *     lows: array<float>
     * } $data Market data for analysis
     * @return StrategySignal Trading signal with action, strength, and reason
     * @throws \InvalidArgumentException If required data is missing or insufficient
     */
    public function analyze(array $data): StrategySignal
    {
        // Validate required fields
        if (!isset($data['symbol'])) {
            throw new \InvalidArgumentException('symbol is required');
        }

        $required = ['price', 'high', 'low', 'volume', 'prices', 'volumes', 'highs', 'lows'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \InvalidArgumentException("$field is required");
            }
        }

        // Validate data sufficiency
        $dataPoints = count($data['prices']);
        if ($dataPoints < $this->config['min_data_points']) {
            throw new \InvalidArgumentException(
                "Insufficient data: need at least {$this->config['min_data_points']} data points, got {$dataPoints}"
            );
        }

        // Calculate VWAP using typical prices
        $typicalPrices = [];
        for ($i = 0; $i < $dataPoints; $i++) {
            $typicalPrices[] = $this->calculateTypicalPrice(
                $data['highs'][$i],
                $data['lows'][$i],
                $data['prices'][$i]
            );
        }

        $vwap = $this->calculateVWAP($typicalPrices, $data['volumes']);
        $currentTypical = $this->calculateTypicalPrice($data['high'], $data['low'], $data['price']);
        $deviation = $this->calculateDeviation($currentTypical, $vwap);

        // Determine signal based on VWAP deviation
        $threshold = $this->config['deviation_threshold'];

        if ($this->isBelowVWAP($currentTypical, $vwap, $threshold)) {
            // Price below VWAP - potential BUY opportunity
            $strength = min(abs($deviation) / 10, 1.0);  // Scale to 0-1
            $reason = sprintf(
                'Price %.2f%% below VWAP ($%.2f), trading at discount',
                abs($deviation),
                $vwap
            );

            return new StrategySignal(
                symbol: $data['symbol'],
                action: 'BUY',
                strength: $strength,
                reason: $reason,
                metadata: [
                    'vwap' => $vwap,
                    'current_price' => $data['price'],
                    'typical_price' => $currentTypical,
                    'deviation' => $deviation
                ]
            );
        }

        if ($this->isAboveVWAP($currentTypical, $vwap, $threshold)) {
            // Price above VWAP - potential SELL opportunity
            $strength = min(abs($deviation) / 10, 1.0);
            $reason = sprintf(
                'Price %.2f%% above VWAP ($%.2f), trading at premium',
                abs($deviation),
                $vwap
            );

            return new StrategySignal(
                symbol: $data['symbol'],
                action: 'SELL',
                strength: $strength,
                reason: $reason,
                metadata: [
                    'vwap' => $vwap,
                    'current_price' => $data['price'],
                    'typical_price' => $currentTypical,
                    'deviation' => $deviation
                ]
            );
        }

        // Price near VWAP - fair value
        return new StrategySignal(
            symbol: $data['symbol'],
            action: 'HOLD',
            strength: 0.0,
            reason: sprintf('Price near VWAP ($%.2f), fair value zone', $vwap),
            metadata: [
                'vwap' => $vwap,
                'current_price' => $data['price'],
                'typical_price' => $currentTypical,
                'deviation' => $deviation
            ]
        );
    }

    /**
     * Calculate Volume-Weighted Average Price.
     *
     * @param array<float> $prices Price data
     * @param array<float> $volumes Volume data
     * @return float VWAP value
     */
    public function calculateVWAP(array $prices, array $volumes): float
    {
        $priceVolumeSum = 0.0;
        $volumeSum = 0.0;

        $count = min(count($prices), count($volumes));

        for ($i = 0; $i < $count; $i++) {
            $priceVolumeSum += $prices[$i] * $volumes[$i];
            $volumeSum += $volumes[$i];
        }

        return $volumeSum > 0 ? $priceVolumeSum / $volumeSum : 0.0;
    }

    /**
     * Calculate typical price: (High + Low + Close) / 3
     *
     * @param float $high High price
     * @param float $low Low price
     * @param float $close Close price
     * @return float Typical price
     */
    public function calculateTypicalPrice(float $high, float $low, float $close): float
    {
        return ($high + $low + $close) / 3;
    }

    /**
     * Calculate percentage deviation from VWAP.
     *
     * @param float $currentPrice Current price
     * @param float $vwap VWAP value
     * @return float Percentage deviation (positive = above, negative = below)
     */
    public function calculateDeviation(float $currentPrice, float $vwap): float
    {
        if ($vwap == 0) {
            return 0.0;
        }

        return (($currentPrice - $vwap) / $vwap) * 100;
    }

    /**
     * Check if price is significantly below VWAP.
     *
     * @param float $currentPrice Current price
     * @param float $vwap VWAP value
     * @param float $threshold Percentage threshold
     * @return bool True if price is below VWAP by threshold
     */
    public function isBelowVWAP(float $currentPrice, float $vwap, float $threshold): bool
    {
        $deviation = $this->calculateDeviation($currentPrice, $vwap);
        return $deviation < -$threshold;
    }

    /**
     * Check if price is significantly above VWAP.
     *
     * @param float $currentPrice Current price
     * @param float $vwap VWAP value
     * @param float $threshold Percentage threshold
     * @return bool True if price is above VWAP by threshold
     */
    public function isAboveVWAP(float $currentPrice, float $vwap, float $threshold): bool
    {
        $deviation = $this->calculateDeviation($currentPrice, $vwap);
        return $deviation > $threshold;
    }

    /**
     * Get strategy configuration.
     *
     * @return array{
     *     deviation_threshold: float,
     *     min_data_points: int
     * } Current configuration
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }

    /**
     * Get strategy metadata and description.
     *
     * @return array{
     *     name: string,
     *     description: string,
     *     indicators: array<string>,
     *     timeframe: string,
     *     risk_level: string
     * } Strategy metadata
     */
    public function getMetadata(): array
    {
        return [
            'name' => 'VWAP Strategy',
            'description' => 'Volume-Weighted Average Price trading strategy for identifying value zones',
            'indicators' => ['VWAP', 'Typical Price', 'Volume'],
            'timeframe' => 'Intraday',
            'risk_level' => 'Medium'
        ];
    }
}
