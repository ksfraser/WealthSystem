<?php

namespace App\Risk;

/**
 * Beta Calculator
 * 
 * Calculates beta, alpha, and related systematic risk metrics for portfolio analysis.
 * Beta measures an asset's sensitivity to market movements.
 * 
 * Key Metrics:
 * - Beta (β): Systematic risk relative to market (β=1 means same volatility as market)
 * - Alpha (α): Excess return above what beta predicts
 * - R-squared (R²): Percentage of variance explained by market
 * - Tracking Error: Standard deviation of excess returns
 * - Information Ratio: Risk-adjusted excess return
 * 
 * Example:
 * ```php
 * $beta = new BetaCalculator();
 * 
 * $stockReturns = [0.01, 0.02, -0.01, 0.03];
 * $marketReturns = [0.015, 0.01, -0.005, 0.02];
 * 
 * $metrics = $beta->calculate($stockReturns, $marketReturns);
 * echo "Beta: {$metrics['beta']}\n";
 * echo "Alpha: {$metrics['alpha']}\n";
 * ```
 */
class BetaCalculator
{
    /**
     * Calculate beta (systematic risk)
     * 
     * Beta = Covariance(Stock, Market) / Variance(Market)
     * 
     * Interpretation:
     * - β = 1.0: Asset moves with market
     * - β > 1.0: Asset more volatile than market
     * - β < 1.0: Asset less volatile than market
     * - β = 0.0: No correlation with market
     * - β < 0.0: Asset moves opposite to market
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market/benchmark returns
     * @return float Beta
     */
    public function beta(array $assetReturns, array $marketReturns): float
    {
        if (count($assetReturns) !== count($marketReturns) || count($assetReturns) < 2) {
            return 0.0;
        }

        $covariance = $this->covariance($assetReturns, $marketReturns);
        $marketVariance = $this->variance($marketReturns);

        if ($marketVariance == 0) {
            return 0.0;
        }

        return $covariance / $marketVariance;
    }

    /**
     * Calculate alpha (excess return)
     * 
     * Alpha = Asset Return - (Risk-Free Rate + Beta * (Market Return - Risk-Free Rate))
     * 
     * Interpretation:
     * - α > 0: Outperforming relative to risk
     * - α = 0: Performing as expected given risk
     * - α < 0: Underperforming relative to risk
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param float $riskFreeRate Risk-free rate
     * @return float Alpha
     */
    public function alpha(
        array $assetReturns,
        array $marketReturns,
        float $riskFreeRate = 0.0
    ): float {
        $beta = $this->beta($assetReturns, $marketReturns);
        $assetReturn = $this->mean($assetReturns);
        $marketReturn = $this->mean($marketReturns);

        // CAPM: Expected Return = Rf + β * (Rm - Rf)
        $expectedReturn = $riskFreeRate + $beta * ($marketReturn - $riskFreeRate);

        // Alpha = Actual Return - Expected Return
        return $assetReturn - $expectedReturn;
    }

    /**
     * Calculate R-squared (coefficient of determination)
     * 
     * R² measures how much of the asset's variance is explained by market movements.
     * 
     * Interpretation:
     * - R² = 1.0: 100% explained by market
     * - R² = 0.0: 0% explained by market
     * - High R² (>0.7): Asset closely tracks market
     * - Low R² (<0.3): Asset independent of market
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @return float R-squared (0 to 1)
     */
    public function rSquared(array $assetReturns, array $marketReturns): float
    {
        if (count($assetReturns) !== count($marketReturns) || count($assetReturns) < 2) {
            return 0.0;
        }

        // Calculate correlation
        $correlation = $this->correlation($assetReturns, $marketReturns);

        // R² = correlation²
        return $correlation * $correlation;
    }

    /**
     * Calculate all beta-related metrics
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param float $riskFreeRate Risk-free rate (default: 0)
     * @return array Comprehensive metrics
     */
    public function calculate(
        array $assetReturns,
        array $marketReturns,
        float $riskFreeRate = 0.0
    ): array {
        $beta = $this->beta($assetReturns, $marketReturns);
        $alpha = $this->alpha($assetReturns, $marketReturns, $riskFreeRate);
        $rSquared = $this->rSquared($assetReturns, $marketReturns);

        // Calculate additional metrics
        $assetReturn = $this->mean($assetReturns);
        $marketReturn = $this->mean($marketReturns);
        $assetVolatility = $this->standardDeviation($assetReturns);
        $marketVolatility = $this->standardDeviation($marketReturns);

        // Systematic vs unsystematic risk
        $systematicRisk = $beta * $marketVolatility;
        $totalRisk = $assetVolatility;
        $unsystematicRisk = sqrt(max(0, $totalRisk * $totalRisk - $systematicRisk * $systematicRisk));

        return [
            'beta' => $beta,
            'alpha' => $alpha,
            'r_squared' => $rSquared,
            'asset_return' => $assetReturn,
            'market_return' => $marketReturn,
            'asset_volatility' => $assetVolatility,
            'market_volatility' => $marketVolatility,
            'systematic_risk' => $systematicRisk,
            'unsystematic_risk' => $unsystematicRisk,
            'systematic_risk_pct' => $totalRisk > 0 ? ($systematicRisk / $totalRisk) * 100 : 0,
            'unsystematic_risk_pct' => $totalRisk > 0 ? ($unsystematicRisk / $totalRisk) * 100 : 0,
            'beta_interpretation' => $this->betaInterpretation($beta),
            'alpha_interpretation' => $this->alphaInterpretation($alpha),
        ];
    }

    /**
     * Calculate rolling beta
     * 
     * Computes beta over a moving window to capture time-varying sensitivity.
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param int $window Window size (default: 60 periods)
     * @return array Array of beta values over time
     */
    public function rollingBeta(
        array $assetReturns,
        array $marketReturns,
        int $window = 60
    ): array {
        if (count($assetReturns) !== count($marketReturns) || count($assetReturns) < $window) {
            return [];
        }

        $betas = [];
        $n = count($assetReturns);

        for ($i = $window - 1; $i < $n; $i++) {
            $windowAsset = array_slice($assetReturns, $i - $window + 1, $window);
            $windowMarket = array_slice($marketReturns, $i - $window + 1, $window);
            
            $betas[] = $this->beta($windowAsset, $windowMarket);
        }

        return $betas;
    }

    /**
     * Calculate rolling alpha
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param int $window Window size
     * @param float $riskFreeRate Risk-free rate
     * @return array Array of alpha values
     */
    public function rollingAlpha(
        array $assetReturns,
        array $marketReturns,
        int $window = 60,
        float $riskFreeRate = 0.0
    ): array {
        if (count($assetReturns) !== count($marketReturns) || count($assetReturns) < $window) {
            return [];
        }

        $alphas = [];
        $n = count($assetReturns);

        for ($i = $window - 1; $i < $n; $i++) {
            $windowAsset = array_slice($assetReturns, $i - $window + 1, $window);
            $windowMarket = array_slice($marketReturns, $i - $window + 1, $window);
            
            $alphas[] = $this->alpha($windowAsset, $windowMarket, $riskFreeRate);
        }

        return $alphas;
    }

    /**
     * Calculate Treynor ratio
     * 
     * Risk-adjusted return using beta instead of standard deviation.
     * Treynor = (Return - Risk-Free Rate) / Beta
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param float $riskFreeRate Risk-free rate
     * @return float Treynor ratio
     */
    public function treynorRatio(
        array $assetReturns,
        array $marketReturns,
        float $riskFreeRate = 0.0
    ): float {
        $beta = $this->beta($assetReturns, $marketReturns);
        
        if ($beta == 0) {
            return 0.0;
        }

        $assetReturn = $this->mean($assetReturns);
        
        return ($assetReturn - $riskFreeRate) / $beta;
    }

    /**
     * Calculate Jensen's alpha
     * 
     * Another measure of risk-adjusted performance.
     * Jensen's Alpha = Portfolio Return - [Rf + Beta * (Market Return - Rf)]
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @param float $riskFreeRate Risk-free rate
     * @return float Jensen's alpha
     */
    public function jensensAlpha(
        array $assetReturns,
        array $marketReturns,
        float $riskFreeRate = 0.0
    ): float {
        // Same as alpha() - included for completeness
        return $this->alpha($assetReturns, $marketReturns, $riskFreeRate);
    }

    /**
     * Calculate market timing ability
     * 
     * Measures if manager successfully increases/decreases beta in up/down markets.
     * Uses Treynor-Mazuy model.
     * 
     * @param array $assetReturns Asset returns
     * @param array $marketReturns Market returns
     * @return array Timing metrics
     */
    public function marketTiming(array $assetReturns, array $marketReturns): array
    {
        if (count($assetReturns) !== count($marketReturns) || count($assetReturns) < 10) {
            return ['timing_coefficient' => 0.0, 'timing_ability' => 'insufficient_data'];
        }

        // Split into up and down markets
        $upMarketAsset = [];
        $upMarketBenchmark = [];
        $downMarketAsset = [];
        $downMarketBenchmark = [];

        for ($i = 0; $i < count($marketReturns); $i++) {
            if ($marketReturns[$i] >= 0) {
                $upMarketAsset[] = $assetReturns[$i];
                $upMarketBenchmark[] = $marketReturns[$i];
            } else {
                $downMarketAsset[] = $assetReturns[$i];
                $downMarketBenchmark[] = $marketReturns[$i];
            }
        }

        $betaUp = !empty($upMarketAsset) ? $this->beta($upMarketAsset, $upMarketBenchmark) : 0;
        $betaDown = !empty($downMarketAsset) ? $this->beta($downMarketAsset, $downMarketBenchmark) : 0;

        // Good timing: higher beta in up markets, lower in down markets
        $timingCoefficient = $betaUp - $betaDown;

        return [
            'beta_up_market' => $betaUp,
            'beta_down_market' => $betaDown,
            'timing_coefficient' => $timingCoefficient,
            'timing_ability' => $this->timingInterpretation($timingCoefficient),
            'up_market_periods' => count($upMarketAsset),
            'down_market_periods' => count($downMarketAsset),
        ];
    }

    /**
     * Calculate covariance between two series
     * 
     * @param array $x First series
     * @param array $y Second series
     * @return float Covariance
     */
    private function covariance(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        $n = count($x);
        $meanX = $this->mean($x);
        $meanY = $this->mean($y);

        $sum = 0;
        for ($i = 0; $i < $n; $i++) {
            $sum += ($x[$i] - $meanX) * ($y[$i] - $meanY);
        }

        return $sum / ($n - 1);
    }

    /**
     * Calculate variance
     * 
     * @param array $values Values
     * @return float Variance
     */
    private function variance(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = $this->mean($values);
        $sum = 0;

        foreach ($values as $value) {
            $sum += pow($value - $mean, 2);
        }

        return $sum / (count($values) - 1);
    }

    /**
     * Calculate correlation coefficient
     * 
     * @param array $x First series
     * @param array $y Second series
     * @return float Correlation (-1 to 1)
     */
    private function correlation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        $covariance = $this->covariance($x, $y);
        $stdX = $this->standardDeviation($x);
        $stdY = $this->standardDeviation($y);

        if ($stdX == 0 || $stdY == 0) {
            return 0.0;
        }

        return $covariance / ($stdX * $stdY);
    }

    /**
     * Calculate mean
     * 
     * @param array $values Values
     * @return float Mean
     */
    private function mean(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        return array_sum($values) / count($values);
    }

    /**
     * Calculate standard deviation
     * 
     * @param array $values Values
     * @return float Standard deviation
     */
    private function standardDeviation(array $values): float
    {
        return sqrt($this->variance($values));
    }

    /**
     * Interpret beta value
     * 
     * @param float $beta Beta value
     * @return string Interpretation
     */
    private function betaInterpretation(float $beta): string
    {
        if ($beta > 1.2) return 'high_volatility';
        if ($beta > 1.0) return 'above_market';
        if ($beta >= 0.8) return 'market_like';
        if ($beta >= 0.5) return 'low_volatility';
        if ($beta >= 0.0) return 'very_low_volatility';
        return 'negative_correlation';
    }

    /**
     * Interpret alpha value
     * 
     * @param float $alpha Alpha value
     * @return string Interpretation
     */
    private function alphaInterpretation(float $alpha): string
    {
        if ($alpha > 0.05) return 'strong_outperformance';
        if ($alpha > 0.02) return 'outperformance';
        if ($alpha > -0.02) return 'market_performance';
        if ($alpha > -0.05) return 'underperformance';
        return 'strong_underperformance';
    }

    /**
     * Interpret timing coefficient
     * 
     * @param float $coefficient Timing coefficient
     * @return string Interpretation
     */
    private function timingInterpretation(float $coefficient): string
    {
        if ($coefficient > 0.5) return 'excellent';
        if ($coefficient > 0.2) return 'good';
        if ($coefficient > -0.2) return 'neutral';
        if ($coefficient > -0.5) return 'poor';
        return 'very_poor';
    }
}
