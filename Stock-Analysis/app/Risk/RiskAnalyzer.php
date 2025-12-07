<?php

namespace App\Risk;

use App\Risk\RiskMetrics;
use App\Risk\CorrelationMatrix;
use App\Risk\BetaCalculator;

/**
 * Comprehensive Risk Analyzer
 * 
 * Aggregates all risk metrics into a unified portfolio risk assessment.
 * Provides risk scoring, stress testing, and actionable recommendations.
 * 
 * Components:
 * - Value at Risk (VaR) analysis
 * - Correlation and diversification metrics
 * - Beta and systematic risk analysis
 * - Risk-adjusted performance metrics
 * - Portfolio risk scoring (0-100)
 * 
 * Example:
 * ```php
 * $analyzer = new RiskAnalyzer();
 * 
 * $portfolio = [
 *     'AAPL' => ['returns' => [...], 'weight' => 0.3],
 *     'MSFT' => ['returns' => [...], 'weight' => 0.3],
 *     'GOOGL' => ['returns' => [...], 'weight' => 0.4],
 * ];
 * 
 * $marketReturns = [...];
 * 
 * $report = $analyzer->analyzePortfolio($portfolio, $marketReturns);
 * print_r($report);
 * ```
 */
class RiskAnalyzer
{
    public function __construct(
        private readonly RiskMetrics $riskMetrics = new RiskMetrics(),
        private readonly CorrelationMatrix $correlationMatrix = new CorrelationMatrix(),
        private readonly BetaCalculator $betaCalculator = new BetaCalculator()
    ) {
    }

    /**
     * Comprehensive portfolio risk analysis
     * 
     * @param array $portfolio Portfolio with returns and weights per symbol
     * @param array $marketReturns Market/benchmark returns
     * @param float $riskFreeRate Risk-free rate (default: 0.02 = 2%)
     * @param float $confidenceLevel VaR confidence level (default: 0.95)
     * @return array Complete risk analysis
     */
    public function analyzePortfolio(
        array $portfolio,
        array $marketReturns,
        float $riskFreeRate = 0.02,
        float $confidenceLevel = 0.95
    ): array {
        // Calculate portfolio returns
        $portfolioReturns = $this->calculatePortfolioReturns($portfolio);

        // 1. VaR Analysis
        $varAnalysis = [
            'historical_var' => $this->riskMetrics->historicalVaR($portfolioReturns, $confidenceLevel),
            'parametric_var' => $this->riskMetrics->parametricVaR($portfolioReturns, $confidenceLevel),
            'monte_carlo_var' => $this->riskMetrics->monteCarloVaR($portfolioReturns, $confidenceLevel, 10000),
            'cvar' => $this->riskMetrics->cvar($portfolioReturns, $confidenceLevel),
            'confidence_level' => $confidenceLevel,
        ];

        // 2. Correlation and Diversification
        $returns = array_map(fn($asset) => $asset['returns'], $portfolio);
        $correlationAnalysis = [
            'correlation_matrix' => $this->correlationMatrix->calculate($returns),
            'diversification' => $this->correlationMatrix->diversificationScore($returns),
            'correlated_pairs' => $this->correlationMatrix->findCorrelatedPairs($returns, 0.7),
        ];

        // 3. Beta and Systematic Risk
        $betaAnalysis = $this->betaCalculator->calculate(
            $portfolioReturns,
            $marketReturns,
            $riskFreeRate
        );

        // 4. Performance Metrics
        $performanceMetrics = [
            'sharpe_ratio' => $this->riskMetrics->sharpeRatio($portfolioReturns, $riskFreeRate),
            'sortino_ratio' => $this->riskMetrics->sortinoRatio($portfolioReturns, $riskFreeRate),
            'treynor_ratio' => $this->betaCalculator->treynorRatio($portfolioReturns, $marketReturns, $riskFreeRate),
            'information_ratio' => $this->riskMetrics->informationRatio($portfolioReturns, $marketReturns),
        ];

        // 5. Risk Score (0-100, lower is better)
        $riskScore = $this->calculateRiskScore([
            'var' => $varAnalysis,
            'correlation' => $correlationAnalysis,
            'beta' => $betaAnalysis,
            'performance' => $performanceMetrics,
        ]);

        // 6. Recommendations
        $recommendations = $this->generateRecommendations([
            'var' => $varAnalysis,
            'correlation' => $correlationAnalysis,
            'beta' => $betaAnalysis,
            'performance' => $performanceMetrics,
            'risk_score' => $riskScore,
        ]);

        return [
            'risk_score' => $riskScore,
            'var_analysis' => $varAnalysis,
            'correlation_analysis' => $correlationAnalysis,
            'beta_analysis' => $betaAnalysis,
            'performance_metrics' => $performanceMetrics,
            'recommendations' => $recommendations,
            'summary' => $this->generateSummary($riskScore),
        ];
    }

    /**
     * Stress test portfolio under adverse scenarios
     * 
     * @param array $portfolio Portfolio data
     * @param array $scenarios Stress scenarios
     * @return array Stress test results
     */
    public function stressTest(array $portfolio, array $scenarios): array
    {
        $results = [];

        foreach ($scenarios as $name => $scenario) {
            $stressedReturns = [];

            foreach ($portfolio as $symbol => $data) {
                $returns = $data['returns'];
                $weight = $data['weight'];

                // Apply scenario shock
                $shock = $scenario['shocks'][$symbol] ?? $scenario['market_shock'] ?? 0;
                $stressedReturns[$symbol] = array_map(fn($r) => $r + $shock, $returns);
            }

            // Calculate stressed portfolio returns
            $portfolioReturns = $this->calculatePortfolioReturns(
                array_map(fn($symbol) => [
                    'returns' => $stressedReturns[$symbol],
                    'weight' => $portfolio[$symbol]['weight']
                ], array_keys($portfolio))
            );

            $results[$name] = [
                'var_95' => $this->riskMetrics->historicalVaR($portfolioReturns, 0.95),
                'var_99' => $this->riskMetrics->historicalVaR($portfolioReturns, 0.99),
                'expected_return' => array_sum($portfolioReturns) / count($portfolioReturns),
                'max_loss' => -min($portfolioReturns),
                'severity' => $this->stressSeverity($portfolioReturns),
            ];
        }

        return [
            'scenarios' => $results,
            'worst_case' => $this->findWorstScenario($results),
            'average_impact' => $this->averageStressImpact($results),
        ];
    }

    /**
     * Calculate individual asset risk contributions
     * 
     * @param array $portfolio Portfolio data
     * @return array Risk contributions by asset
     */
    public function riskContribution(array $portfolio): array
    {
        $returns = array_map(fn($asset) => $asset['returns'], $portfolio);
        $weights = array_map(fn($asset) => $asset['weight'], $portfolio);
        
        $portfolioReturns = $this->calculatePortfolioReturns($portfolio);
        $portfolioVar = $this->riskMetrics->parametricVaR($portfolioReturns, 0.95);

        $contributions = [];

        foreach ($portfolio as $symbol => $data) {
            $assetReturns = $data['returns'];
            $weight = $data['weight'];

            // Marginal VaR
            $correlation = $this->correlation($assetReturns, $portfolioReturns);
            $assetVol = $this->standardDeviation($assetReturns);
            
            $marginalVar = $correlation * $assetVol;
            $componentVar = $weight * $marginalVar;

            $contributions[$symbol] = [
                'weight' => $weight,
                'volatility' => $assetVol,
                'marginal_var' => $marginalVar,
                'component_var' => $componentVar,
                'contribution_pct' => $portfolioVar > 0 ? ($componentVar / $portfolioVar) * 100 : 0,
            ];
        }

        // Sort by contribution
        uasort($contributions, fn($a, $b) => $b['contribution_pct'] <=> $a['contribution_pct']);

        return [
            'portfolio_var' => $portfolioVar,
            'contributions' => $contributions,
            'top_contributor' => array_key_first($contributions),
        ];
    }

    /**
     * Calculate portfolio returns from weighted asset returns
     * 
     * @param array $portfolio Portfolio data
     * @return array Portfolio returns
     */
    private function calculatePortfolioReturns(array $portfolio): array
    {
        $periods = count(current($portfolio)['returns']);
        $portfolioReturns = array_fill(0, $periods, 0.0);

        foreach ($portfolio as $data) {
            $returns = $data['returns'];
            $weight = $data['weight'];

            for ($i = 0; $i < $periods; $i++) {
                $portfolioReturns[$i] += $returns[$i] * $weight;
            }
        }

        return $portfolioReturns;
    }

    /**
     * Calculate risk score (0-100)
     * 
     * @param array $metrics All risk metrics
     * @return array Risk score with breakdown
     */
    private function calculateRiskScore(array $metrics): array
    {
        $scores = [];

        // VaR score (0-25 points)
        $var = $metrics['var']['historical_var'];
        $scores['var'] = min(25, $var * 100 * 2.5); // Scale: 10% VaR = 25 points

        // Diversification score (0-25 points)
        $divScore = $metrics['correlation']['diversification']['score'];
        $scores['diversification'] = (1 - $divScore) * 25; // Invert: lower correlation = better

        // Beta score (0-25 points)
        $beta = abs($metrics['beta']['beta'] - 1.0); // Distance from 1.0
        $scores['beta'] = min(25, $beta * 25);

        // Performance score (0-25 points)
        $sharpe = $metrics['performance']['sharpe_ratio'];
        $scores['performance'] = max(0, 25 - ($sharpe * 10)); // Higher Sharpe = lower score

        $totalScore = array_sum($scores);

        return [
            'total' => min(100, $totalScore),
            'breakdown' => $scores,
            'rating' => $this->riskRating($totalScore),
        ];
    }

    /**
     * Generate recommendations based on risk analysis
     * 
     * @param array $analysis Full analysis
     * @return array Recommendations
     */
    private function generateRecommendations(array $analysis): array
    {
        $recommendations = [];

        // VaR recommendations
        $var = $analysis['var']['historical_var'];
        if ($var > 0.15) {
            $recommendations[] = [
                'type' => 'high_risk',
                'priority' => 'high',
                'message' => 'Portfolio VaR exceeds 15%. Consider reducing position sizes or adding defensive assets.',
            ];
        }

        // Diversification recommendations
        $divScore = $analysis['correlation']['diversification']['score'];
        if ($divScore < 0.5) {
            $recommendations[] = [
                'type' => 'poor_diversification',
                'priority' => 'medium',
                'message' => 'Low diversification detected. Consider adding uncorrelated assets to reduce risk.',
            ];
        }

        // Correlation recommendations
        foreach ($analysis['correlation']['correlated_pairs'] as $pair) {
            if (abs($pair['correlation']) > 0.85) {
                $recommendations[] = [
                    'type' => 'high_correlation',
                    'priority' => 'low',
                    'message' => "{$pair['symbol1']} and {$pair['symbol2']} are highly correlated ({$pair['correlation']}). Consider reducing exposure to one.",
                ];
            }
        }

        // Beta recommendations
        $beta = $analysis['beta']['beta'];
        if ($beta > 1.5) {
            $recommendations[] = [
                'type' => 'high_beta',
                'priority' => 'medium',
                'message' => "Portfolio beta is {$beta}, indicating high market sensitivity. Consider adding low-beta assets.",
            ];
        }

        // Alpha recommendations
        $alpha = $analysis['beta']['alpha'];
        if ($alpha < -0.02) {
            $recommendations[] = [
                'type' => 'negative_alpha',
                'priority' => 'high',
                'message' => "Portfolio has negative alpha ({$alpha}). Review strategy or consider index funds.",
            ];
        }

        // Performance recommendations
        $sharpe = $analysis['performance']['sharpe_ratio'];
        if ($sharpe < 0.5) {
            $recommendations[] = [
                'type' => 'low_sharpe',
                'priority' => 'medium',
                'message' => "Sharpe ratio is below 0.5. Risk-adjusted returns are poor. Consider strategy adjustment.",
            ];
        }

        return $recommendations;
    }

    /**
     * Generate executive summary
     * 
     * @param array $riskScore Risk score
     * @return string Summary
     */
    private function generateSummary(array $riskScore): string
    {
        $rating = $riskScore['rating'];
        $score = $riskScore['total'];

        $summaries = [
            'low' => "Portfolio exhibits low risk (score: {$score}/100). Well-diversified with acceptable volatility and good risk-adjusted returns.",
            'moderate' => "Portfolio has moderate risk (score: {$score}/100). Some areas for improvement in diversification or volatility management.",
            'high' => "Portfolio shows elevated risk (score: {$score}/100). Consider rebalancing to reduce VaR and improve diversification.",
            'very_high' => "Portfolio has very high risk (score: {$score}/100). Immediate action recommended to reduce exposure and improve risk profile.",
        ];

        return $summaries[$rating] ?? $summaries['moderate'];
    }

    /**
     * Determine risk rating
     * 
     * @param float $score Risk score
     * @return string Rating
     */
    private function riskRating(float $score): string
    {
        if ($score < 30) return 'low';
        if ($score < 50) return 'moderate';
        if ($score < 70) return 'high';
        return 'very_high';
    }

    /**
     * Find worst stress test scenario
     * 
     * @param array $results Stress test results
     * @return array Worst scenario
     */
    private function findWorstScenario(array $results): array
    {
        $worst = null;
        $worstLoss = 0;

        foreach ($results as $name => $result) {
            if ($result['max_loss'] > $worstLoss) {
                $worstLoss = $result['max_loss'];
                $worst = ['name' => $name, 'result' => $result];
            }
        }

        return $worst ?? [];
    }

    /**
     * Calculate average stress impact
     * 
     * @param array $results Stress test results
     * @return array Average impact
     */
    private function averageStressImpact(array $results): array
    {
        $avgVar95 = 0;
        $avgVar99 = 0;
        $avgReturn = 0;
        $count = count($results);

        foreach ($results as $result) {
            $avgVar95 += $result['var_95'];
            $avgVar99 += $result['var_99'];
            $avgReturn += $result['expected_return'];
        }

        return [
            'avg_var_95' => $count > 0 ? $avgVar95 / $count : 0,
            'avg_var_99' => $count > 0 ? $avgVar99 / $count : 0,
            'avg_return' => $count > 0 ? $avgReturn / $count : 0,
        ];
    }

    /**
     * Determine stress severity
     * 
     * @param array $returns Stressed returns
     * @return string Severity
     */
    private function stressSeverity(array $returns): string
    {
        $maxLoss = -min($returns);
        
        if ($maxLoss > 0.30) return 'extreme';
        if ($maxLoss > 0.20) return 'severe';
        if ($maxLoss > 0.10) return 'moderate';
        return 'mild';
    }

    /**
     * Calculate correlation
     * 
     * @param array $x First series
     * @param array $y Second series
     * @return float Correlation
     */
    private function correlation(array $x, array $y): float
    {
        if (count($x) !== count($y) || count($x) < 2) {
            return 0.0;
        }

        $n = count($x);
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $covariance = 0;
        $varX = 0;
        $varY = 0;

        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $meanX;
            $dy = $y[$i] - $meanY;
            
            $covariance += $dx * $dy;
            $varX += $dx * $dx;
            $varY += $dy * $dy;
        }

        $denominator = sqrt($varX * $varY);
        
        return $denominator > 0 ? $covariance / $denominator : 0.0;
    }

    /**
     * Calculate standard deviation
     * 
     * @param array $values Values
     * @return float Standard deviation
     */
    private function standardDeviation(array $values): float
    {
        if (count($values) < 2) {
            return 0.0;
        }

        $mean = array_sum($values) / count($values);
        $sum = 0;

        foreach ($values as $value) {
            $sum += pow($value - $mean, 2);
        }

        return sqrt($sum / (count($values) - 1));
    }
}
