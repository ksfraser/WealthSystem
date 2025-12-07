<?php

namespace WealthSystem\StockAnalysis\Portfolio;

/**
 * Portfolio Optimization Result
 * 
 * Contains optimal portfolio weights and associated metrics.
 */
readonly class OptimizationResult
{
    /**
     * @param array<string, float> $weights Ticker => weight (0.0 to 1.0, sum = 1.0)
     * @param float $expectedReturn Expected annual return (e.g., 0.12 = 12%)
     * @param float $volatility Portfolio volatility/risk (std deviation)
     * @param float $sharpeRatio Risk-adjusted return metric
     * @param string $method Optimization method used
     * @param array $metrics Additional metrics (beta, alpha, VaR, etc.)
     * @param \DateTimeImmutable $calculatedAt When optimization was performed
     * @param string|null $error Error message if optimization failed
     */
    public function __construct(
        public array $weights,
        public float $expectedReturn,
        public float $volatility,
        public float $sharpeRatio,
        public string $method,
        public array $metrics = [],
        public \DateTimeImmutable $calculatedAt = new \DateTimeImmutable(),
        public ?string $error = null
    ) {
    }

    /**
     * Check if optimization succeeded
     */
    public function isValid(): bool
    {
        return $this->error === null;
    }

    /**
     * Get weight for specific ticker
     */
    public function getWeight(string $ticker): float
    {
        return $this->weights[$ticker] ?? 0.0;
    }

    /**
     * Get tickers sorted by weight (descending)
     */
    public function getSortedTickers(): array
    {
        $sorted = $this->weights;
        arsort($sorted);
        return array_keys($sorted);
    }

    /**
     * Get allocation in dollars for given portfolio value
     */
    public function getAllocation(float $portfolioValue): array
    {
        $allocation = [];
        foreach ($this->weights as $ticker => $weight) {
            $allocation[$ticker] = $portfolioValue * $weight;
        }
        return $allocation;
    }

    /**
     * Convert to array representation
     */
    public function toArray(): array
    {
        return [
            'weights' => $this->weights,
            'expectedReturn' => $this->expectedReturn,
            'volatility' => $this->volatility,
            'sharpeRatio' => $this->sharpeRatio,
            'method' => $this->method,
            'metrics' => $this->metrics,
            'calculatedAt' => $this->calculatedAt->format('Y-m-d H:i:s'),
            'error' => $this->error,
        ];
    }

    /**
     * Format for LLM prompt inclusion
     */
    public function toPromptString(): string
    {
        if (!$this->isValid()) {
            return "Portfolio optimization failed: {$this->error}";
        }

        $output = "Optimal Portfolio Allocation ({$this->method}):\n";
        $output .= "  Expected Annual Return: " . number_format($this->expectedReturn * 100, 2) . "%\n";
        $output .= "  Portfolio Volatility: " . number_format($this->volatility * 100, 2) . "%\n";
        $output .= "  Sharpe Ratio: " . number_format($this->sharpeRatio, 2) . "\n\n";

        $output .= "  Recommended Weights:\n";
        foreach ($this->getSortedTickers() as $ticker) {
            $weight = $this->weights[$ticker];
            if ($weight >= 0.01) { // Only show weights >= 1%
                $output .= "    {$ticker}: " . number_format($weight * 100, 1) . "%\n";
            }
        }

        if (!empty($this->metrics)) {
            $output .= "\n  Additional Metrics:\n";
            foreach ($this->metrics as $metric => $value) {
                if (is_numeric($value)) {
                    $output .= "    " . ucfirst($metric) . ": " . number_format($value, 3) . "\n";
                }
            }
        }

        return $output;
    }
}

/**
 * Efficient Frontier Point
 * 
 * Represents a point on the efficient frontier curve.
 */
readonly class EfficientFrontierPoint
{
    public function __construct(
        public float $expectedReturn,
        public float $volatility,
        public array $weights,
        public float $sharpeRatio
    ) {
    }
}

/**
 * Portfolio Optimizer Interface
 * 
 * Defines contract for portfolio optimization implementations.
 * Supports Modern Portfolio Theory (MPT), Black-Litterman, and other methods.
 */
interface PortfolioOptimizerInterface
{
    /**
     * Optimize portfolio to maximize Sharpe ratio
     * 
     * Finds portfolio weights that maximize risk-adjusted returns.
     * 
     * @param array<string> $tickers List of ticker symbols
     * @param array $options Optional parameters:
     *   - 'risk_free_rate': float (default: 0.02 = 2%)
     *   - 'constraints': array (min/max weights per ticker)
     *   - 'lookback_days': int (historical data period, default: 252)
     * 
     * @return OptimizationResult
     */
    public function maximizeSharpeRatio(array $tickers, array $options = []): OptimizationResult;

    /**
     * Optimize portfolio to minimize variance/risk
     * 
     * Finds portfolio weights with lowest volatility.
     * 
     * @param array<string> $tickers List of ticker symbols
     * @param array $options Optional parameters:
     *   - 'constraints': array (min/max weights per ticker)
     *   - 'lookback_days': int (historical data period, default: 252)
     * 
     * @return OptimizationResult
     */
    public function minimizeVariance(array $tickers, array $options = []): OptimizationResult;

    /**
     * Optimize portfolio for target return
     * 
     * Finds portfolio weights that achieve target return with minimum risk.
     * 
     * @param array<string> $tickers List of ticker symbols
     * @param float $targetReturn Target annual return (e.g., 0.10 = 10%)
     * @param array $options Optional parameters:
     *   - 'constraints': array (min/max weights per ticker)
     *   - 'lookback_days': int (historical data period, default: 252)
     * 
     * @return OptimizationResult
     */
    public function targetReturn(array $tickers, float $targetReturn, array $options = []): OptimizationResult;

    /**
     * Calculate efficient frontier
     * 
     * Generates series of optimal portfolios for different risk/return levels.
     * 
     * @param array<string> $tickers List of ticker symbols
     * @param int $points Number of points to calculate (default: 50)
     * @param array $options Optional parameters:
     *   - 'risk_free_rate': float
     *   - 'constraints': array
     *   - 'lookback_days': int
     * 
     * @return array<EfficientFrontierPoint>
     */
    public function calculateEfficientFrontier(array $tickers, int $points = 50, array $options = []): array;

    /**
     * Get optimizer name
     * 
     * @return string Optimizer name (e.g., 'Modern Portfolio Theory')
     */
    public function getOptimizerName(): string;

    /**
     * Check if optimizer is available
     * 
     * @return bool True if optimizer has required dependencies
     */
    public function isAvailable(): bool;
}
