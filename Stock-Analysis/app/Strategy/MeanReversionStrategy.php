<?php

declare(strict_types=1);

namespace App\Strategy;

use InvalidArgumentException;

/**
 * Mean Reversion Trading Strategy
 * 
 * Trades based on the principle that prices tend to revert to their mean.
 * Uses Bollinger Bands and statistical deviation to identify oversold/overbought
 * conditions.
 * 
 * Features:
 * - Bollinger Bands calculation
 * - Standard deviation analysis
 * - Volatility adjustment
 * - Configurable thresholds
 * 
 * @package App\Strategy
 */
class MeanReversionStrategy
{
    private array $config;

    /**
     * Create new mean reversion strategy
     *
     * @param array<string, mixed> $config Strategy configuration
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'period' => 20,
            'std_dev_multiplier' => 2.0,
            'oversold_threshold' => -2.0,
            'overbought_threshold' => 2.0
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
        $currentPrice = $data['current_price'];

        // Calculate indicators
        $bands = $this->calculateBollingerBands($prices);
        $deviation = $this->calculateDeviation($prices, $currentPrice);
        $volatility = $this->calculateVolatility($prices);

        // Generate signal
        $action = 'HOLD';
        $strength = 0.5;
        $reason = '';

        if ($this->isOversold($prices, $currentPrice)) {
            $action = 'BUY';
            $strength = $this->calculateSignalStrength($deviation, $volatility);
            $reason = sprintf(
                "Price (%.2f) below lower Bollinger Band (%.2f), expecting reversion to mean (%.2f)",
                $currentPrice,
                $bands['lower'],
                $bands['middle']
            );
        } elseif ($this->isOverbought($prices, $currentPrice)) {
            $action = 'SELL';
            $strength = $this->calculateSignalStrength($deviation, $volatility);
            $reason = sprintf(
                "Price (%.2f) above upper Bollinger Band (%.2f), expecting reversion to mean (%.2f)",
                $currentPrice,
                $bands['upper'],
                $bands['middle']
            );
        } else {
            $reason = sprintf(
                "Price (%.2f) within normal range [%.2f - %.2f]",
                $currentPrice,
                $bands['lower'],
                $bands['upper']
            );
        }

        return new StrategySignal(
            $symbol,
            $action,
            $strength,
            $reason,
            [
                'bollinger_bands' => $bands,
                'deviation' => $deviation,
                'volatility' => $volatility
            ]
        );
    }

    /**
     * Calculate Bollinger Bands
     *
     * @param array<int, float> $prices Price history
     * @return array<string, float> Bands (upper, middle, lower)
     */
    public function calculateBollingerBands(array $prices): array
    {
        $period = min($this->config['period'], count($prices));
        $recentPrices = array_slice($prices, -$period);

        $middle = $this->calculateMean($recentPrices);
        $stdDev = $this->calculateStandardDeviation($recentPrices);

        $multiplier = $this->config['std_dev_multiplier'];

        return [
            'upper' => $middle + ($stdDev * $multiplier),
            'middle' => $middle,
            'lower' => $middle - ($stdDev * $multiplier)
        ];
    }

    /**
     * Calculate standard deviation
     *
     * @param array<int, float> $prices Price data
     * @return float Standard deviation
     */
    public function calculateStandardDeviation(array $prices): float
    {
        if (count($prices) < 2) {
            return 0.0;
        }

        $mean = $this->calculateMean($prices);
        $squaredDiffs = array_map(function ($price) use ($mean) {
            return pow($price - $mean, 2);
        }, $prices);

        $variance = array_sum($squaredDiffs) / count($prices);
        
        return sqrt($variance);
    }

    /**
     * Calculate mean (average) price
     *
     * @param array<int, float> $prices Price data
     * @return float Mean price
     */
    public function calculateMean(array $prices): float
    {
        if (empty($prices)) {
            return 0.0;
        }

        return array_sum($prices) / count($prices);
    }

    /**
     * Calculate price deviation from mean in standard deviations
     *
     * @param array<int, float> $prices Price history
     * @param float $currentPrice Current price
     * @return float Deviation in standard deviations
     */
    public function calculateDeviation(array $prices, float $currentPrice): float
    {
        $period = min($this->config['period'], count($prices));
        $recentPrices = array_slice($prices, -$period);

        $mean = $this->calculateMean($recentPrices);
        $stdDev = $this->calculateStandardDeviation($recentPrices);

        if ($stdDev == 0) {
            return 0.0;
        }

        return ($currentPrice - $mean) / $stdDev;
    }

    /**
     * Check if price is oversold
     *
     * @param array<int, float> $prices Price history
     * @param float $currentPrice Current price
     * @return bool True if oversold
     */
    public function isOversold(array $prices, float $currentPrice): bool
    {
        $deviation = $this->calculateDeviation($prices, $currentPrice);
        return $deviation < $this->config['oversold_threshold'];
    }

    /**
     * Check if price is overbought
     *
     * @param array<int, float> $prices Price history
     * @param float $currentPrice Current price
     * @return bool True if overbought
     */
    public function isOverbought(array $prices, float $currentPrice): bool
    {
        $deviation = $this->calculateDeviation($prices, $currentPrice);
        return $deviation > $this->config['overbought_threshold'];
    }

    /**
     * Calculate volatility
     *
     * @param array<int, float> $prices Price history
     * @return float Volatility percentage
     */
    public function calculateVolatility(array $prices): float
    {
        $period = min($this->config['period'], count($prices));
        $recentPrices = array_slice($prices, -$period);

        $mean = $this->calculateMean($recentPrices);
        $stdDev = $this->calculateStandardDeviation($recentPrices);

        if ($mean == 0) {
            return 0.0;
        }

        return ($stdDev / $mean) * 100;
    }

    /**
     * Calculate signal strength adjusted for volatility
     *
     * @param float $deviation Price deviation
     * @param float $volatility Market volatility
     * @return float Strength (0.0 to 1.0)
     */
    private function calculateSignalStrength(float $deviation, float $volatility): float
    {
        // Base strength on deviation magnitude
        $baseStrength = min(abs($deviation) / 3.0, 1.0);

        // Reduce confidence in high volatility
        $volatilityFactor = 1.0 - min($volatility / 50.0, 0.3);

        return $baseStrength * $volatilityFactor;
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
            'name' => 'Mean Reversion Strategy',
            'description' => 'Trades based on price deviation from statistical mean using Bollinger Bands',
            'category' => 'Mean Reversion',
            'indicators' => ['Bollinger Bands', 'Standard Deviation', 'Volatility'],
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

        if (count($data['prices']) < $this->config['period']) {
            throw new InvalidArgumentException(
                "Insufficient price data. Need at least {$this->config['period']} data points"
            );
        }

        if (!isset($data['current_price'])) {
            throw new InvalidArgumentException("Current price is required");
        }
    }
}
