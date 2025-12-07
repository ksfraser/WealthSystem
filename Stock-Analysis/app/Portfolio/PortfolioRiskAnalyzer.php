<?php

namespace WealthSystem\StockAnalysis\Portfolio;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Portfolio Risk Analysis Service
 * 
 * Calculates comprehensive risk metrics for portfolios:
 * - Volatility (standard deviation of returns)
 * - Sharpe Ratio (risk-adjusted return)
 * - Beta (market correlation)
 * - Value at Risk (VaR)
 * - Maximum Drawdown
 * - Correlation Matrix
 * 
 * Use Cases:
 * - Assess current portfolio risk
 * - Compare before/after rebalancing
 * - Monitor risk metrics over time
 * - Validate optimization results
 */
class PortfolioRiskAnalyzer
{
    private readonly LoggerInterface $logger;

    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Calculate comprehensive risk metrics for portfolio
     * 
     * @param array<string, float> $weights Ticker => weight mapping
     * @param array<string, array<float>> $returns Ticker => daily returns
     * @param array $options Optional parameters:
     *   - 'risk_free_rate': float (annual, default: 0.02)
     *   - 'confidence_level': float (for VaR, default: 0.95)
     *   - 'market_returns': array (for beta calculation)
     * 
     * @return array{
     *   volatility: float,
     *   sharpe_ratio: float,
     *   sortino_ratio: float,
     *   max_drawdown: float,
     *   var_95: float,
     *   var_99: float,
     *   beta: ?float,
     *   correlation_matrix: array
     * }
     */
    public function analyzePortfolio(array $weights, array $returns, array $options = []): array
    {
        $this->logger->info("Analyzing portfolio risk", ['tickers' => array_keys($weights)]);

        // Calculate portfolio returns
        $portfolioReturns = $this->calculatePortfolioReturns($weights, $returns);

        // Basic metrics
        $volatility = $this->calculateVolatility($portfolioReturns);
        $avgReturn = array_sum($portfolioReturns) / count($portfolioReturns) * 252; // Annualized
        $riskFreeRate = $options['risk_free_rate'] ?? 0.02;
        
        $sharpeRatio = $volatility > 0 ? ($avgReturn - $riskFreeRate) / $volatility : 0;
        $sortinoRatio = $this->calculateSortinoRatio($portfolioReturns, $riskFreeRate);
        $maxDrawdown = $this->calculateMaxDrawdown($portfolioReturns);
        
        // Value at Risk
        $var95 = $this->calculateVaR($portfolioReturns, 0.95);
        $var99 = $this->calculateVaR($portfolioReturns, 0.99);
        
        // Beta (if market returns provided)
        $beta = null;
        if (!empty($options['market_returns'])) {
            $beta = $this->calculateBeta($portfolioReturns, $options['market_returns']);
        }
        
        // Correlation matrix
        $correlationMatrix = $this->calculateCorrelationMatrix($returns);

        return [
            'volatility' => $volatility,
            'sharpe_ratio' => $sharpeRatio,
            'sortino_ratio' => $sortinoRatio,
            'max_drawdown' => $maxDrawdown,
            'var_95' => $var95,
            'var_99' => $var99,
            'beta' => $beta,
            'correlation_matrix' => $correlationMatrix,
            'expected_return' => $avgReturn,
        ];
    }

    /**
     * Calculate portfolio returns from individual asset returns and weights
     */
    private function calculatePortfolioReturns(array $weights, array $returns): array
    {
        $tickers = array_keys($weights);
        $numPeriods = count($returns[$tickers[0]]);
        $portfolioReturns = array_fill(0, $numPeriods, 0.0);

        foreach ($tickers as $ticker) {
            $weight = $weights[$ticker];
            for ($i = 0; $i < $numPeriods; $i++) {
                $portfolioReturns[$i] += $weight * ($returns[$ticker][$i] ?? 0);
            }
        }

        return $portfolioReturns;
    }

    /**
     * Calculate volatility (annualized standard deviation)
     */
    private function calculateVolatility(array $returns): float
    {
        $n = count($returns);
        if ($n === 0) return 0.0;

        $mean = array_sum($returns) / $n;
        $variance = 0.0;

        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }

        $variance = $variance / ($n - 1);
        $dailyVolatility = sqrt($variance);

        // Annualize: sqrt(252) trading days
        return $dailyVolatility * sqrt(252);
    }

    /**
     * Calculate Sortino Ratio (downside risk-adjusted return)
     * 
     * Similar to Sharpe, but only considers downside volatility
     */
    private function calculateSortinoRatio(array $returns, float $riskFreeRate): float
    {
        $n = count($returns);
        if ($n === 0) return 0.0;

        $avgReturn = array_sum($returns) / $n * 252; // Annualized
        $downsideReturns = array_filter($returns, fn($r) => $r < 0);

        if (empty($downsideReturns)) return INF; // No downside risk

        $downsideDeviation = 0.0;
        foreach ($downsideReturns as $return) {
            $downsideDeviation += pow($return, 2);
        }

        $downsideDeviation = sqrt($downsideDeviation / $n) * sqrt(252); // Annualized

        return $downsideDeviation > 0 ? ($avgReturn - $riskFreeRate) / $downsideDeviation : 0;
    }

    /**
     * Calculate Maximum Drawdown
     * 
     * Largest peak-to-trough decline in portfolio value
     */
    private function calculateMaxDrawdown(array $returns): float
    {
        $cumReturns = [1.0]; // Start with $1
        foreach ($returns as $return) {
            $cumReturns[] = end($cumReturns) * (1 + $return);
        }

        $maxDrawdown = 0.0;
        $peak = $cumReturns[0];

        foreach ($cumReturns as $value) {
            if ($value > $peak) {
                $peak = $value;
            }

            $drawdown = ($peak - $value) / $peak;
            if ($drawdown > $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }

        return $maxDrawdown;
    }

    /**
     * Calculate Value at Risk (VaR)
     * 
     * Maximum expected loss at given confidence level
     * 
     * @param array $returns Daily returns
     * @param float $confidenceLevel Confidence level (e.g., 0.95 = 95%)
     * @return float VaR as positive number (e.g., 0.05 = 5% loss)
     */
    private function calculateVaR(array $returns, float $confidenceLevel): float
    {
        if (empty($returns)) return 0.0;

        // Sort returns ascending (worst losses first)
        $sortedReturns = $returns;
        sort($sortedReturns);

        // Find percentile
        $index = (int)((1 - $confidenceLevel) * count($sortedReturns));
        $var = -$sortedReturns[$index]; // Make positive (loss)

        // Annualize: sqrt(252) for scaling
        return $var * sqrt(252);
    }

    /**
     * Calculate Beta (market correlation coefficient)
     * 
     * Beta measures portfolio sensitivity to market movements:
     * - Beta = 1.0: Moves with market
     * - Beta > 1.0: More volatile than market
     * - Beta < 1.0: Less volatile than market
     * - Beta < 0: Moves opposite to market
     * 
     * @param array $portfolioReturns Portfolio daily returns
     * @param array $marketReturns Market (S&P 500) daily returns
     * @return float Beta coefficient
     */
    private function calculateBeta(array $portfolioReturns, array $marketReturns): float
    {
        $n = min(count($portfolioReturns), count($marketReturns));
        if ($n === 0) return 1.0; // Default to market

        // Calculate covariance and market variance
        $portfolioMean = array_sum($portfolioReturns) / count($portfolioReturns);
        $marketMean = array_sum($marketReturns) / count($marketReturns);

        $covariance = 0.0;
        $marketVariance = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $portfolioDev = $portfolioReturns[$i] - $portfolioMean;
            $marketDev = $marketReturns[$i] - $marketMean;

            $covariance += $portfolioDev * $marketDev;
            $marketVariance += pow($marketDev, 2);
        }

        $covariance /= ($n - 1);
        $marketVariance /= ($n - 1);

        return $marketVariance > 0 ? $covariance / $marketVariance : 1.0;
    }

    /**
     * Calculate correlation matrix between assets
     * 
     * Correlation ranges from -1 (inverse) to +1 (perfect)
     * Values close to 0 indicate diversification benefit
     * 
     * @param array<string, array<float>> $returns Ticker => returns
     * @return array<string, array<string, float>> Correlation matrix
     */
    private function calculateCorrelationMatrix(array $returns): array
    {
        $tickers = array_keys($returns);
        $matrix = [];

        foreach ($tickers as $ticker1) {
            $matrix[$ticker1] = [];
            foreach ($tickers as $ticker2) {
                if ($ticker1 === $ticker2) {
                    $matrix[$ticker1][$ticker2] = 1.0;
                } else {
                    $matrix[$ticker1][$ticker2] = $this->calculateCorrelation(
                        $returns[$ticker1],
                        $returns[$ticker2]
                    );
                }
            }
        }

        return $matrix;
    }

    /**
     * Calculate correlation coefficient between two return series
     */
    private function calculateCorrelation(array $returns1, array $returns2): float
    {
        $n = min(count($returns1), count($returns2));
        if ($n === 0) return 0.0;

        $mean1 = array_sum($returns1) / count($returns1);
        $mean2 = array_sum($returns2) / count($returns2);

        $covariance = 0.0;
        $variance1 = 0.0;
        $variance2 = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dev1 = $returns1[$i] - $mean1;
            $dev2 = $returns2[$i] - $mean2;

            $covariance += $dev1 * $dev2;
            $variance1 += pow($dev1, 2);
            $variance2 += pow($dev2, 2);
        }

        $stdDev1 = sqrt($variance1 / ($n - 1));
        $stdDev2 = sqrt($variance2 / ($n - 1));

        if ($stdDev1 == 0 || $stdDev2 == 0) return 0.0;

        return $covariance / (($n - 1) * $stdDev1 * $stdDev2);
    }

    /**
     * Format risk metrics for display
     */
    public function formatRiskMetrics(array $metrics): string
    {
        $output = "Portfolio Risk Analysis:\n";
        $output .= "  Expected Return: " . number_format($metrics['expected_return'] * 100, 2) . "%\n";
        $output .= "  Volatility (Risk): " . number_format($metrics['volatility'] * 100, 2) . "%\n";
        $output .= "  Sharpe Ratio: " . number_format($metrics['sharpe_ratio'], 2) . "\n";
        $output .= "  Sortino Ratio: " . number_format($metrics['sortino_ratio'], 2) . "\n";
        $output .= "  Max Drawdown: " . number_format($metrics['max_drawdown'] * 100, 2) . "%\n";
        $output .= "  VaR (95%): " . number_format($metrics['var_95'] * 100, 2) . "%\n";
        $output .= "  VaR (99%): " . number_format($metrics['var_99'] * 100, 2) . "%\n";

        if ($metrics['beta'] !== null) {
            $output .= "  Beta: " . number_format($metrics['beta'], 2) . "\n";
        }

        return $output;
    }
}
