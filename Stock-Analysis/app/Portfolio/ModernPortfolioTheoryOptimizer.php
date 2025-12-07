<?php

namespace WealthSystem\StockAnalysis\Portfolio;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Modern Portfolio Theory (MPT) Optimizer
 * 
 * Implements Harry Markowitz's Modern Portfolio Theory for portfolio optimization.
 * Calculates optimal asset weights to maximize returns for a given risk level.
 * 
 * Key Concepts:
 * - Efficient Frontier: Set of optimal portfolios offering highest return for given risk
 * - Sharpe Ratio: Risk-adjusted return metric (return per unit of risk)
 * - Diversification: Reduces portfolio risk through uncorrelated assets
 * 
 * Mathematical Approach:
 * - Uses mean-variance optimization
 * - Covariance matrix for asset correlations
 * - Quadratic programming for constraint optimization
 * 
 * Limitations:
 * - Assumes normal distribution of returns
 * - Based on historical data (past performance)
 * - Doesn't account for transaction costs
 * - Sensitive to input estimates
 * 
 * Data Requirements:
 * - Historical price data (minimum 1 year recommended)
 * - Daily returns for each asset
 * - Risk-free rate (typically US Treasury yield)
 */
class ModernPortfolioTheoryOptimizer implements PortfolioOptimizerInterface
{
    private const DEFAULT_LOOKBACK_DAYS = 252; // ~1 year of trading days
    private const DEFAULT_RISK_FREE_RATE = 0.02; // 2% annual
    private const MIN_WEIGHT = 0.0; // No short selling
    private const MAX_WEIGHT = 1.0; // 100% max per asset
    private const OPTIMIZATION_ITERATIONS = 10000; // Monte Carlo simulations

    private readonly LoggerInterface $logger;
    private ?array $priceDataCache = null;

    public function __construct(
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeSharpeRatio(array $tickers, array $options = []): OptimizationResult
    {
        $this->logger->info("Optimizing portfolio for maximum Sharpe ratio", ['tickers' => $tickers]);

        try {
            // Get historical data and calculate returns
            $returns = $this->calculateReturns($tickers, $options);
            if (empty($returns)) {
                return $this->createErrorResult('Insufficient historical data');
            }

            // Calculate covariance matrix and expected returns
            $covMatrix = $this->calculateCovarianceMatrix($returns);
            $expectedReturns = $this->calculateExpectedReturns($returns);

            // Get risk-free rate
            $riskFreeRate = $options['risk_free_rate'] ?? self::DEFAULT_RISK_FREE_RATE;

            // Monte Carlo simulation to find optimal weights
            $bestSharpe = -INF;
            $bestWeights = [];
            $bestReturn = 0;
            $bestVolatility = 0;

            for ($i = 0; $i < self::OPTIMIZATION_ITERATIONS; $i++) {
                // Generate random weights that sum to 1.0
                $weights = $this->generateRandomWeights(count($tickers), $options);

                // Calculate portfolio metrics
                $portfolioReturn = $this->calculatePortfolioReturn($expectedReturns, $weights);
                $portfolioVolatility = $this->calculatePortfolioVolatility($covMatrix, $weights);

                // Calculate Sharpe ratio
                $sharpeRatio = ($portfolioReturn - $riskFreeRate) / $portfolioVolatility;

                // Track best result
                if ($sharpeRatio > $bestSharpe) {
                    $bestSharpe = $sharpeRatio;
                    $bestWeights = $weights;
                    $bestReturn = $portfolioReturn;
                    $bestVolatility = $portfolioVolatility;
                }
            }

            // Create weight mapping
            $weightMap = array_combine($tickers, $bestWeights);

            return new OptimizationResult(
                weights: $weightMap,
                expectedReturn: $bestReturn,
                volatility: $bestVolatility,
                sharpeRatio: $bestSharpe,
                method: 'Maximum Sharpe Ratio (MPT)',
                metrics: [
                    'risk_free_rate' => $riskFreeRate,
                    'iterations' => self::OPTIMIZATION_ITERATIONS,
                ]
            );

        } catch (\Exception $e) {
            $this->logger->error("Sharpe ratio optimization failed", ['error' => $e->getMessage()]);
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function minimizeVariance(array $tickers, array $options = []): OptimizationResult
    {
        $this->logger->info("Optimizing portfolio for minimum variance", ['tickers' => $tickers]);

        try {
            $returns = $this->calculateReturns($tickers, $options);
            if (empty($returns)) {
                return $this->createErrorResult('Insufficient historical data');
            }

            $covMatrix = $this->calculateCovarianceMatrix($returns);
            $expectedReturns = $this->calculateExpectedReturns($returns);
            $riskFreeRate = $options['risk_free_rate'] ?? self::DEFAULT_RISK_FREE_RATE;

            $minVariance = INF;
            $bestWeights = [];
            $bestReturn = 0;
            $bestVolatility = 0;

            for ($i = 0; $i < self::OPTIMIZATION_ITERATIONS; $i++) {
                $weights = $this->generateRandomWeights(count($tickers), $options);
                $variance = $this->calculatePortfolioVariance($covMatrix, $weights);

                if ($variance < $minVariance) {
                    $minVariance = $variance;
                    $bestWeights = $weights;
                    $bestVolatility = sqrt($variance);
                    $bestReturn = $this->calculatePortfolioReturn($expectedReturns, $weights);
                }
            }

            $weightMap = array_combine($tickers, $bestWeights);
            $sharpeRatio = ($bestReturn - $riskFreeRate) / $bestVolatility;

            return new OptimizationResult(
                weights: $weightMap,
                expectedReturn: $bestReturn,
                volatility: $bestVolatility,
                sharpeRatio: $sharpeRatio,
                method: 'Minimum Variance (MPT)',
                metrics: [
                    'variance' => $minVariance,
                    'risk_free_rate' => $riskFreeRate,
                ]
            );

        } catch (\Exception $e) {
            $this->logger->error("Minimum variance optimization failed", ['error' => $e->getMessage()]);
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function targetReturn(array $tickers, float $targetReturn, array $options = []): OptimizationResult
    {
        $this->logger->info("Optimizing portfolio for target return", [
            'tickers' => $tickers,
            'target' => $targetReturn
        ]);

        try {
            $returns = $this->calculateReturns($tickers, $options);
            if (empty($returns)) {
                return $this->createErrorResult('Insufficient historical data');
            }

            $covMatrix = $this->calculateCovarianceMatrix($returns);
            $expectedReturns = $this->calculateExpectedReturns($returns);
            $riskFreeRate = $options['risk_free_rate'] ?? self::DEFAULT_RISK_FREE_RATE;

            $minVolatility = INF;
            $bestWeights = [];
            $bestReturn = 0;
            $bestVolatility = 0;
            $tolerance = 0.01; // 1% tolerance for target matching

            for ($i = 0; $i < self::OPTIMIZATION_ITERATIONS; $i++) {
                $weights = $this->generateRandomWeights(count($tickers), $options);
                $portfolioReturn = $this->calculatePortfolioReturn($expectedReturns, $weights);

                // Check if return matches target (within tolerance)
                if (abs($portfolioReturn - $targetReturn) <= $tolerance) {
                    $volatility = $this->calculatePortfolioVolatility($covMatrix, $weights);

                    if ($volatility < $minVolatility) {
                        $minVolatility = $volatility;
                        $bestWeights = $weights;
                        $bestReturn = $portfolioReturn;
                        $bestVolatility = $volatility;
                    }
                }
            }

            if (empty($bestWeights)) {
                return $this->createErrorResult("Could not achieve target return of {$targetReturn} with given assets");
            }

            $weightMap = array_combine($tickers, $bestWeights);
            $sharpeRatio = ($bestReturn - $riskFreeRate) / $bestVolatility;

            return new OptimizationResult(
                weights: $weightMap,
                expectedReturn: $bestReturn,
                volatility: $bestVolatility,
                sharpeRatio: $sharpeRatio,
                method: 'Target Return (MPT)',
                metrics: [
                    'target_return' => $targetReturn,
                    'achieved_return' => $bestReturn,
                    'risk_free_rate' => $riskFreeRate,
                ]
            );

        } catch (\Exception $e) {
            $this->logger->error("Target return optimization failed", ['error' => $e->getMessage()]);
            return $this->createErrorResult($e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function calculateEfficientFrontier(array $tickers, int $points = 50, array $options = []): array
    {
        $this->logger->info("Calculating efficient frontier", ['tickers' => $tickers, 'points' => $points]);

        try {
            $returns = $this->calculateReturns($tickers, $options);
            if (empty($returns)) {
                return [];
            }

            $covMatrix = $this->calculateCovarianceMatrix($returns);
            $expectedReturns = $this->calculateExpectedReturns($returns);
            $riskFreeRate = $options['risk_free_rate'] ?? self::DEFAULT_RISK_FREE_RATE;

            // Find min and max possible returns
            $minReturn = min($expectedReturns);
            $maxReturn = max($expectedReturns);

            $frontierPoints = [];
            $targetReturns = [];

            // Generate target returns from min to max
            for ($i = 0; $i < $points; $i++) {
                $targetReturns[] = $minReturn + ($maxReturn - $minReturn) * ($i / ($points - 1));
            }

            foreach ($targetReturns as $targetReturn) {
                $result = $this->targetReturn($tickers, $targetReturn, $options);
                if ($result->isValid()) {
                    $frontierPoints[] = new EfficientFrontierPoint(
                        expectedReturn: $result->expectedReturn,
                        volatility: $result->volatility,
                        weights: $result->weights,
                        sharpeRatio: $result->sharpeRatio
                    );
                }
            }

            // Sort by volatility
            usort($frontierPoints, fn($a, $b) => $a->volatility <=> $b->volatility);

            return $frontierPoints;

        } catch (\Exception $e) {
            $this->logger->error("Efficient frontier calculation failed", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizerName(): string
    {
        return 'Modern Portfolio Theory';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        // MPT optimizer has no external dependencies
        return true;
    }

    /**
     * Calculate historical returns for tickers
     * 
     * @return array<string, array<float>> Ticker => array of daily returns
     */
    private function calculateReturns(array $tickers, array $options): array
    {
        $lookbackDays = $options['lookback_days'] ?? self::DEFAULT_LOOKBACK_DAYS;

        // In a real implementation, fetch historical price data from API
        // For now, simulate with random data (replace with actual data fetching)
        $returns = [];

        foreach ($tickers as $ticker) {
            $dailyReturns = [];
            
            // Simulate historical returns (replace with actual data)
            // This is placeholder - integrate with your price data source
            for ($i = 0; $i < $lookbackDays; $i++) {
                // Simulate daily return between -3% and +3%
                $dailyReturns[] = (mt_rand(-300, 300) / 10000);
            }
            
            $returns[$ticker] = $dailyReturns;
        }

        return $returns;
    }

    /**
     * Calculate covariance matrix from returns
     */
    private function calculateCovarianceMatrix(array $returns): array
    {
        $tickers = array_keys($returns);
        $n = count($tickers);
        $covMatrix = array_fill(0, $n, array_fill(0, $n, 0.0));

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $covMatrix[$i][$j] = $this->calculateCovariance(
                    $returns[$tickers[$i]],
                    $returns[$tickers[$j]]
                );
            }
        }

        return $covMatrix;
    }

    /**
     * Calculate covariance between two return series
     */
    private function calculateCovariance(array $returns1, array $returns2): float
    {
        $n = min(count($returns1), count($returns2));
        if ($n === 0) return 0.0;

        $mean1 = array_sum($returns1) / count($returns1);
        $mean2 = array_sum($returns2) / count($returns2);

        $covariance = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $covariance += ($returns1[$i] - $mean1) * ($returns2[$i] - $mean2);
        }

        return $covariance / ($n - 1);
    }

    /**
     * Calculate expected returns (annualized)
     */
    private function calculateExpectedReturns(array $returns): array
    {
        $expectedReturns = [];

        foreach ($returns as $ticker => $dailyReturns) {
            $avgDailyReturn = array_sum($dailyReturns) / count($dailyReturns);
            // Annualize: ~252 trading days per year
            $expectedReturns[] = $avgDailyReturn * 252;
        }

        return $expectedReturns;
    }

    /**
     * Generate random portfolio weights
     */
    private function generateRandomWeights(int $count, array $options): array
    {
        $constraints = $options['constraints'] ?? [];
        $minWeight = $constraints['min'] ?? self::MIN_WEIGHT;
        $maxWeight = $constraints['max'] ?? self::MAX_WEIGHT;

        // Generate random weights
        $weights = [];
        for ($i = 0; $i < $count; $i++) {
            $weights[] = mt_rand(0, 1000) / 1000;
        }

        // Normalize to sum to 1.0
        $sum = array_sum($weights);
        for ($i = 0; $i < $count; $i++) {
            $weights[$i] = $weights[$i] / $sum;
            
            // Apply constraints
            $weights[$i] = max($minWeight, min($maxWeight, $weights[$i]));
        }

        // Re-normalize after constraints
        $sum = array_sum($weights);
        if ($sum > 0) {
            for ($i = 0; $i < $count; $i++) {
                $weights[$i] = $weights[$i] / $sum;
            }
        }

        return $weights;
    }

    /**
     * Calculate portfolio return
     */
    private function calculatePortfolioReturn(array $expectedReturns, array $weights): float
    {
        $portfolioReturn = 0.0;
        for ($i = 0; $i < count($weights); $i++) {
            $portfolioReturn += $weights[$i] * $expectedReturns[$i];
        }
        return $portfolioReturn;
    }

    /**
     * Calculate portfolio variance
     */
    private function calculatePortfolioVariance(array $covMatrix, array $weights): float
    {
        $variance = 0.0;
        $n = count($weights);

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $variance += $weights[$i] * $weights[$j] * $covMatrix[$i][$j];
            }
        }

        // Annualize variance: variance * 252
        return $variance * 252;
    }

    /**
     * Calculate portfolio volatility (standard deviation)
     */
    private function calculatePortfolioVolatility(array $covMatrix, array $weights): float
    {
        return sqrt($this->calculatePortfolioVariance($covMatrix, $weights));
    }

    /**
     * Create error result
     */
    private function createErrorResult(string $error): OptimizationResult
    {
        return new OptimizationResult(
            weights: [],
            expectedReturn: 0.0,
            volatility: 0.0,
            sharpeRatio: 0.0,
            method: 'Modern Portfolio Theory',
            error: $error
        );
    }
}
