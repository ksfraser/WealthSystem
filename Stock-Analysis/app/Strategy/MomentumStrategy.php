<?php

declare(strict_types=1);

namespace App\Strategy;

use InvalidArgumentException;

/**
 * Momentum Trading Strategy
 * 
 * Analyzes price momentum and relative strength to generate trading signals.
 * Uses RSI, price trends, volume analysis, and breakout detection.
 * 
 * Features:
 * - Price momentum calculation
 * - Relative Strength Index (RSI)
 * - Breakout detection with volume confirmation
 * - Configurable thresholds
 * 
 * @package App\Strategy
 */
class MomentumStrategy
{
    private array $config;

    /**
     * Create new momentum strategy
     *
     * @param array<string, mixed> $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'lookback_period' => 20,
            'rsi_period' => 14,
            'rsi_oversold' => 30,
            'rsi_overbought' => 70,
            'volume_multiplier' => 1.5
        ], $config);
    }

    /**
     * Analyze market data and generate signal
     *
     * @param array<string, mixed> $data Market data
     * @return StrategySignal Trading signal
     * @throws InvalidArgumentException If data is invalid
     */
    public function analyze(array $data): StrategySignal
    {
        $this->validateData($data);

        $symbol = $data['symbol'];
        $prices = $data['prices'];
        $volumes = $data['volumes'];
        $currentPrice = $data['current_price'];
        $currentVolume = $data['current_volume'];

        // Calculate indicators
        $momentum = $this->calculateMomentum($prices);
        $rsi = $this->calculateRSI($prices);
        $isBreakout = $this->detectBreakout($prices, $volumes);

        // Generate signal
        $action = 'HOLD';
        $strength = 0.5;
        $reasons = [];

        // Prioritize strong signals first
        // Breakout with momentum is strongest buy signal
        if ($momentum > 0 && $isBreakout) {
            $action = 'BUY';
            $strength = 0.85;
            $reasons[] = "Strong upward momentum with breakout";
        }
        // Oversold is strong buy signal
        elseif ($this->isOversold($rsi)) {
            $action = 'BUY';
            $strength = 0.7;
            $reasons[] = "RSI oversold at " . round($rsi, 2);
        }
        // Strong positive momentum
        elseif ($momentum > 10) {
            $action = 'BUY';
            $strength = 0.75;
            $reasons[] = "Strong positive momentum: " . round($momentum, 2) . "%";
        }
        // Overbought with negative momentum is sell signal
        elseif ($this->isOverbought($rsi) && $momentum < 0) {
            $action = 'SELL';
            $strength = 0.8;
            $reasons[] = "RSI overbought at " . round($rsi, 2) . " with negative momentum";
        }
        // Strong negative momentum
        elseif ($momentum < -5) {
            $action = 'SELL';
            $strength = 0.7;
            $reasons[] = "Strong negative momentum: " . round($momentum, 2) . "%";
        }
        // Weak signals
        elseif ($this->isOverbought($rsi)) {
            $action = 'SELL';
            $strength = 0.6;
            $reasons[] = "RSI overbought at " . round($rsi, 2);
        }

        $reason = !empty($reasons) 
            ? implode('; ', $reasons)
            : "Neutral momentum conditions";

        return new StrategySignal(
            $symbol,
            $action,
            $strength,
            $reason,
            [
                'momentum' => $momentum,
                'rsi' => $rsi,
                'is_breakout' => $isBreakout
            ]
        );
    }

    /**
     * Calculate price momentum
     *
     * @param array<int, float> $prices Price history
     * @return float Momentum percentage
     */
    public function calculateMomentum(array $prices): float
    {
        if (count($prices) < 2) {
            return 0.0;
        }

        $period = min($this->config['lookback_period'], count($prices));
        $oldPrice = $prices[count($prices) - $period];
        $newPrice = $prices[count($prices) - 1];

        return (($newPrice - $oldPrice) / $oldPrice) * 100;
    }

    /**
     * Calculate Relative Strength Index
     *
     * @param array<int, float> $prices Price history
     * @return float RSI value (0-100)
     */
    public function calculateRSI(array $prices): float
    {
        $period = min($this->config['rsi_period'], count($prices) - 1);
        
        if ($period < 2) {
            return 50.0; // Neutral
        }

        $gains = 0.0;
        $losses = 0.0;

        for ($i = count($prices) - $period; $i < count($prices); $i++) {
            $change = $prices[$i] - $prices[$i - 1];
            
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses += abs($change);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

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
     * @return bool
     */
    public function isOversold(float $rsi): bool
    {
        return $rsi < $this->config['rsi_oversold'];
    }

    /**
     * Check if RSI indicates overbought condition
     *
     * @param float $rsi RSI value
     * @return bool
     */
    public function isOverbought(float $rsi): bool
    {
        return $rsi > $this->config['rsi_overbought'];
    }

    /**
     * Detect price breakout with volume confirmation
     *
     * @param array<int, float> $prices Price history
     * @param array<int, int> $volumes Volume history
     * @return bool True if breakout detected
     */
    public function detectBreakout(array $prices, array $volumes): bool
    {
        if (count($prices) < 10 || count($volumes) < 10) {
            return false;
        }

        // Check for price surge - compare recent price to older average
        $recentPrice = $prices[count($prices) - 1];
        $olderPrices = array_slice($prices, -10, 5); // Middle prices
        $olderAvg = array_sum($olderPrices) / count($olderPrices);
        
        $priceIncrease = (($recentPrice - $olderAvg) / $olderAvg) * 100;

        // Check for volume surge - recent volume vs older average
        $recentVolume = $volumes[count($volumes) - 1];
        $olderVolumes = array_slice($volumes, -10, 5); // Middle volumes
        $olderVolAvg = array_sum($olderVolumes) / count($olderVolumes);
        
        $volumeRatio = $olderVolAvg > 0 ? $recentVolume / $olderVolAvg : 0;

        // Breakout if significant price increase with volume confirmation
        return $priceIncrease > 5 && $volumeRatio >= $this->config['volume_multiplier'];
    }

    /**
     * Get strategy configuration
     *
     * @return array<string, mixed>
     */
    public function getConfiguration(): array
    {
        return $this->config;
    }

    /**
     * Get strategy metadata
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return [
            'name' => 'Momentum Strategy',
            'description' => 'Trades based on price momentum, RSI, and breakout patterns',
            'category' => 'Momentum',
            'indicators' => ['RSI', 'Price Momentum', 'Volume Analysis'],
            'timeframe' => 'Short to Medium term'
        ];
    }

    /**
     * Validate input data
     *
     * @param array<string, mixed> $data Input data
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateData(array $data): void
    {
        if (!isset($data['symbol']) || empty($data['symbol'])) {
            throw new InvalidArgumentException("Symbol is required");
        }

        if (!isset($data['prices']) || !is_array($data['prices'])) {
            throw new InvalidArgumentException("Prices array is required");
        }

        if (count($data['prices']) < $this->config['rsi_period']) {
            throw new InvalidArgumentException(
                "Insufficient price data. Need at least {$this->config['rsi_period']} data points"
            );
        }

        if (!isset($data['volumes']) || !is_array($data['volumes'])) {
            throw new InvalidArgumentException("Volumes array is required");
        }

        if (!isset($data['current_price']) || !isset($data['current_volume'])) {
            throw new InvalidArgumentException("Current price and volume are required");
        }
    }
}
