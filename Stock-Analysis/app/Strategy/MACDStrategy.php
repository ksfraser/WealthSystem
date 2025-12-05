<?php

declare(strict_types=1);

namespace App\Strategy;

/**
 * MACD (Moving Average Convergence Divergence) Trading Strategy
 * 
 * MACD is a trend-following momentum indicator showing relationship
 * between two moving averages of a security's price.
 * 
 * Components:
 * - MACD Line: 12-period EMA - 26-period EMA
 * - Signal Line: 9-period EMA of MACD Line
 * - Histogram: MACD Line - Signal Line
 * 
 * Trading Signals:
 * - Bullish Crossover: MACD crosses above Signal → BUY
 * - Bearish Crossover: MACD crosses below Signal → SELL
 * - Histogram expansion → Trend strengthening
 * - Histogram contraction → Trend weakening
 * 
 * @package App\Strategy
 */
class MACDStrategy
{
    /**
     * Strategy configuration
     *
     * @var array{
     *     fast_period: int,
     *     slow_period: int,
     *     signal_period: int,
     *     min_data_points: int
     * }
     */
    private array $config;

    /**
     * Initialize MACD strategy with optional configuration.
     *
     * @param array{
     *     fast_period?: int,
     *     slow_period?: int,
     *     signal_period?: int,
     *     min_data_points?: int
     * } $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'fast_period' => 12,        // Fast EMA period
            'slow_period' => 26,        // Slow EMA period
            'signal_period' => 9,       // Signal line EMA period
            'min_data_points' => 35     // Minimum: slow_period + signal_period
        ], $config);
    }

    /**
     * Analyze market data and generate trading signal using MACD.
     *
     * @param array{
     *     symbol: string,
     *     price: float,
     *     prices: array<float>,
     *     volume: float
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

        if (!isset($data['prices']) || !isset($data['price'])) {
            throw new \InvalidArgumentException('prices and price are required');
        }

        // Validate data sufficiency
        $dataPoints = count($data['prices']);
        if ($dataPoints < $this->config['min_data_points']) {
            throw new \InvalidArgumentException(
                "Insufficient data: need at least {$this->config['min_data_points']} data points, got {$dataPoints}"
            );
        }

        // Calculate MACD components
        $macdLine = $this->calculateMACDLine($data['prices']);
        $signalLine = $this->calculateSignalLine($data['prices']);
        $histogram = $this->calculateHistogram($data['prices']);

        // Determine crossover direction
        $isBullish = $this->isBullishCrossover($data['prices']);
        $isBearish = $this->isBearishCrossover($data['prices']);

        // Also check for strong histogram values (MACD significantly above/below signal)
        $strongBullish = !$isBullish && $histogram > 2.0;  // Strong positive histogram
        $strongBearish = !$isBearish && $histogram < -2.0;  // Strong negative histogram

        // Generate signal based on crossover or strong divergence
        if ($isBullish || $strongBullish) {
            // MACD crossed above Signal Line or strongly above - BUY signal
            $strength = min(abs($histogram) / 5, 1.0);  // Scale based on histogram strength
            $reason = $isBullish 
                ? sprintf('MACD bullish crossover (MACD: %.2f, Signal: %.2f, Histogram: %.2f)', $macdLine, $signalLine, $histogram)
                : sprintf('MACD strongly above signal line (MACD: %.2f, Signal: %.2f, Histogram: %.2f)', $macdLine, $signalLine, $histogram);

            return new StrategySignal(
                symbol: $data['symbol'],
                action: 'BUY',
                strength: max($strength, 0.6),  // Minimum 0.6 for crossover signals
                reason: $reason,
                metadata: [
                    'macd' => $macdLine,
                    'signal' => $signalLine,
                    'histogram' => $histogram,
                    'trend' => 'bullish'
                ]
            );
        }

        if ($isBearish || $strongBearish) {
            // MACD crossed below Signal Line or strongly below - SELL signal
            $strength = min(abs($histogram) / 5, 1.0);
            $reason = $isBearish
                ? sprintf('MACD bearish crossover (MACD: %.2f, Signal: %.2f, Histogram: %.2f)', $macdLine, $signalLine, $histogram)
                : sprintf('MACD strongly below signal line (MACD: %.2f, Signal: %.2f, Histogram: %.2f)', $macdLine, $signalLine, $histogram);

            return new StrategySignal(
                symbol: $data['symbol'],
                action: 'SELL',
                strength: max($strength, 0.6),
                reason: $reason,
                metadata: [
                    'macd' => $macdLine,
                    'signal' => $signalLine,
                    'histogram' => $histogram,
                    'trend' => 'bearish'
                ]
            );
        }

        // No crossover - HOLD
        $trend = $macdLine > $signalLine ? 'bullish' : 'bearish';
        return new StrategySignal(
            symbol: $data['symbol'],
            action: 'HOLD',
            strength: 0.0,
            reason: sprintf(
                'No MACD crossover, current trend: %s (MACD: %.2f, Signal: %.2f)',
                $trend,
                $macdLine,
                $signalLine
            ),
            metadata: [
                'macd' => $macdLine,
                'signal' => $signalLine,
                'histogram' => $histogram,
                'trend' => $trend
            ]
        );
    }

    /**
     * Calculate Exponential Moving Average.
     *
     * @param array<float> $prices Price data
     * @param int $period EMA period
     * @return float EMA value
     */
    public function calculateEMA(array $prices, int $period): float
    {
        if (count($prices) < $period) {
            return 0.0;
        }

        // Calculate initial SMA
        $sma = array_sum(array_slice($prices, 0, $period)) / $period;

        // EMA multiplier: 2 / (period + 1)
        $multiplier = 2 / ($period + 1);

        // Calculate EMA iteratively
        $ema = $sma;
        for ($i = $period; $i < count($prices); $i++) {
            $ema = ($prices[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }

        return $ema;
    }

    /**
     * Calculate MACD Line: Fast EMA - Slow EMA
     *
     * @param array<float> $prices Price data
     * @return float MACD Line value
     */
    public function calculateMACDLine(array $prices): float
    {
        $fastEMA = $this->calculateEMA($prices, $this->config['fast_period']);
        $slowEMA = $this->calculateEMA($prices, $this->config['slow_period']);

        return $fastEMA - $slowEMA;
    }

    /**
     * Calculate Signal Line: EMA of MACD Line
     *
     * @param array<float> $prices Price data
     * @return float Signal Line value
     */
    public function calculateSignalLine(array $prices): float
    {
        // Need to calculate MACD for each point to get MACD series
        $macdSeries = [];
        $minPoints = $this->config['slow_period'];

        for ($i = $minPoints; $i <= count($prices); $i++) {
            $slice = array_slice($prices, 0, $i);
            $macdSeries[] = $this->calculateMACDLine($slice);
        }

        // Calculate EMA of MACD series
        if (count($macdSeries) < $this->config['signal_period']) {
            return 0.0;
        }

        return $this->calculateEMA($macdSeries, $this->config['signal_period']);
    }

    /**
     * Calculate Histogram: MACD Line - Signal Line
     *
     * @param array<float> $prices Price data
     * @return float Histogram value
     */
    public function calculateHistogram(array $prices): float
    {
        $macdLine = $this->calculateMACDLine($prices);
        $signalLine = $this->calculateSignalLine($prices);

        return $macdLine - $signalLine;
    }

    /**
     * Detect bullish crossover (MACD crosses above Signal).
     *
     * @param array<float> $prices Price data
     * @return bool True if bullish crossover detected
     */
    public function isBullishCrossover(array $prices): bool
    {
        if (count($prices) < $this->config['min_data_points'] + 2) {
            return false;
        }

        // Simplified: Check if histogram moved from negative to positive recently
        $current = $this->calculateHistogram($prices);
        $previous = $this->calculateHistogram(array_slice($prices, 0, -2));
        
        // Bullish crossover: histogram was negative and is now positive with momentum
        return $previous < 0 && $current > 0;
    }

    /**
     * Detect bearish crossover (MACD crosses below Signal).
     *
     * @param array<float> $prices Price data
     * @return bool True if bearish crossover detected
     */
    public function isBearishCrossover(array $prices): bool
    {
        if (count($prices) < $this->config['min_data_points'] + 2) {
            return false;
        }

        // Simplified: Check if histogram moved from positive to negative recently
        $current = $this->calculateHistogram($prices);
        $previous = $this->calculateHistogram(array_slice($prices, 0, -2));
        
        // Bearish crossover: histogram was positive and is now negative with momentum
        return $previous > 0 && $current < 0;
    }

    /**
     * Get strategy configuration.
     *
     * @return array{
     *     fast_period: int,
     *     slow_period: int,
     *     signal_period: int,
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
            'name' => 'MACD Strategy',
            'description' => 'Moving Average Convergence Divergence strategy for trend identification',
            'indicators' => ['MACD', 'Signal Line', 'Histogram'],
            'timeframe' => 'Medium-term',
            'risk_level' => 'Medium'
        ];
    }
}
