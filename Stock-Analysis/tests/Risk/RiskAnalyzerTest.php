<?php

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\RiskAnalyzer;

class RiskAnalyzerTest extends TestCase
{
    private RiskAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new RiskAnalyzer();
    }

    public function testAnalyzePortfolioBasic(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03, -0.02],
                'weight' => 0.5,
            ],
            'MSFT' => [
                'returns' => [0.015, 0.025, -0.005, 0.025, -0.015],
                'weight' => 0.5,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03, -0.02];
        $riskFreeRate = 0.02;
        $confidenceLevel = 0.95;

        $analysis = $this->analyzer->analyzePortfolio(
            $portfolio,
            $marketReturns,
            $riskFreeRate,
            $confidenceLevel
        );

        // Check all sections present
        $this->assertArrayHasKey('risk_score', $analysis);
        $this->assertArrayHasKey('var_analysis', $analysis);
        $this->assertArrayHasKey('correlation_analysis', $analysis);
        $this->assertArrayHasKey('beta_analysis', $analysis);
        $this->assertArrayHasKey('performance_metrics', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
        $this->assertArrayHasKey('summary', $analysis);
    }

    public function testRiskScoreComponents(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $riskScore = $analysis['risk_score'];

        // Check components
        $this->assertArrayHasKey('total', $riskScore);
        $this->assertArrayHasKey('var_component', $riskScore);
        $this->assertArrayHasKey('diversification_component', $riskScore);
        $this->assertArrayHasKey('beta_component', $riskScore);
        $this->assertArrayHasKey('performance_component', $riskScore);
        $this->assertArrayHasKey('rating', $riskScore);

        // Check total is between 0-100
        $this->assertGreaterThanOrEqual(0, $riskScore['total']);
        $this->assertLessThanOrEqual(100, $riskScore['total']);

        // Check components sum roughly to total (allowing for rounding)
        $componentSum = $riskScore['var_component'] +
                       $riskScore['diversification_component'] +
                       $riskScore['beta_component'] +
                       $riskScore['performance_component'];
        
        $this->assertEquals($riskScore['total'], $componentSum, '', 1.0);
    }

    public function testRiskScoreRatings(): void
    {
        $testCases = [
            [25, ['low', 'moderate']],
            [40, ['moderate']],
            [60, ['moderate', 'high']],
            [80, ['high', 'very_high']],
        ];

        foreach ($testCases as [$score, $expectedRatings]) {
            // Create mock score
            $rating = $this->getRatingForScore($score);
            
            $this->assertContains(
                $rating,
                $expectedRatings,
                "Score $score should map to one of: " . implode(', ', $expectedRatings)
            );
        }
    }

    private function getRatingForScore(float $score): string
    {
        if ($score < 30) return 'low';
        if ($score < 50) return 'moderate';
        if ($score < 70) return 'high';
        return 'very_high';
    }

    public function testVarAnalysisAllMethods(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => array_fill(0, 100, 0.01),
                'weight' => 1.0,
            ],
        ];

        $marketReturns = array_fill(0, 100, 0.01);
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $varAnalysis = $analysis['var_analysis'];

        // Check all VaR methods present
        $this->assertArrayHasKey('historical_var_95', $varAnalysis);
        $this->assertArrayHasKey('parametric_var_95', $varAnalysis);
        $this->assertArrayHasKey('monte_carlo_var_95', $varAnalysis);
        $this->assertArrayHasKey('cvar_95', $varAnalysis);
        $this->assertArrayHasKey('historical_var_99', $varAnalysis);
        $this->assertArrayHasKey('parametric_var_99', $varAnalysis);
    }

    public function testCorrelationAnalysis(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 0.5,
            ],
            'MSFT' => [
                'returns' => [0.01, 0.02, -0.01, 0.03], // Same as AAPL
                'weight' => 0.5,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $corrAnalysis = $analysis['correlation_analysis'];

        // Check components
        $this->assertArrayHasKey('matrix', $corrAnalysis);
        $this->assertArrayHasKey('diversification', $corrAnalysis);
        $this->assertArrayHasKey('highly_correlated_pairs', $corrAnalysis);

        // AAPL-MSFT should be perfectly correlated
        $this->assertEquals(1.0, $corrAnalysis['matrix']['AAPL']['MSFT'], '', 0.0001);

        // Diversification should be poor
        $this->assertLessThan(0.3, $corrAnalysis['diversification']['score']);
    }

    public function testBetaAnalysisPerAsset(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.02, 0.04, -0.02, 0.06], // High beta
                'weight' => 0.5,
            ],
            'BOND' => [
                'returns' => [0.005, 0.01, -0.005, 0.015], // Low beta
                'weight' => 0.5,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $betaAnalysis = $analysis['beta_analysis'];

        // Check both assets analyzed
        $this->assertArrayHasKey('AAPL', $betaAnalysis);
        $this->assertArrayHasKey('BOND', $betaAnalysis);

        // AAPL should have higher beta
        $this->assertGreaterThan($betaAnalysis['BOND']['beta'], $betaAnalysis['AAPL']['beta']);
    }

    public function testPerformanceMetrics(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.05, 0.06, 0.04, 0.07],
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.02, 0.03, 0.01, 0.04];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $perfMetrics = $analysis['performance_metrics'];

        // Check all metrics present
        $this->assertArrayHasKey('sharpe_ratio', $perfMetrics);
        $this->assertArrayHasKey('sortino_ratio', $perfMetrics);
        $this->assertArrayHasKey('treynor_ratio', $perfMetrics);
        $this->assertArrayHasKey('information_ratio', $perfMetrics);

        // High returns should give good Sharpe
        $this->assertGreaterThan(1.0, $perfMetrics['sharpe_ratio']);
    }

    public function testRecommendationsGenerated(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => array_fill(0, 100, 0.01),
                'weight' => 1.0,
            ],
        ];

        $marketReturns = array_fill(0, 100, 0.01);
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $recommendations = $analysis['recommendations'];

        // Should be an array
        $this->assertIsArray($recommendations);

        // Each recommendation should have priority and message
        foreach ($recommendations as $rec) {
            $this->assertArrayHasKey('priority', $rec);
            $this->assertArrayHasKey('message', $rec);
            $this->assertContains($rec['priority'], ['high', 'medium', 'low']);
        }
    }

    public function testHighVarGeneratesRecommendation(): void
    {
        // Create highly volatile portfolio
        $portfolio = [
            'VOLATILE' => [
                'returns' => [0.20, -0.15, 0.25, -0.18, 0.22], // Extreme volatility
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03, -0.02];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $recommendations = $analysis['recommendations'];

        // Should recommend reducing VaR
        $varRecommendation = array_filter($recommendations, function ($rec) {
            return stripos($rec['message'], 'VaR') !== false ||
                   stripos($rec['message'], 'position') !== false;
        });

        $this->assertNotEmpty($varRecommendation);
    }

    public function testPoorDiversificationGeneratesRecommendation(): void
    {
        // Create poorly diversified portfolio (identical assets)
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 0.5,
            ],
            'MSFT' => [
                'returns' => [0.01, 0.02, -0.01, 0.03], // Identical
                'weight' => 0.5,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $recommendations = $analysis['recommendations'];

        // Should recommend improving diversification
        $divRecommendation = array_filter($recommendations, function ($rec) {
            return stripos($rec['message'], 'diversif') !== false ||
                   stripos($rec['message'], 'uncorrelated') !== false;
        });

        $this->assertNotEmpty($divRecommendation);
    }

    public function testSummaryGenerated(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $summary = $analysis['summary'];

        // Should be a string
        $this->assertIsString($summary);
        $this->assertNotEmpty($summary);

        // Should mention key metrics
        $this->assertStringContainsString('risk score', strtolower($summary));
    }

    public function testStressTest(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 1.0,
            ],
        ];

        $scenarios = [
            'market_crash' => [
                'description' => 'Market crashes 20%',
                'asset_shocks' => ['AAPL' => -0.20],
            ],
            'moderate_correction' => [
                'description' => 'Market corrects 10%',
                'asset_shocks' => ['AAPL' => -0.10],
            ],
        ];

        $stressTestResults = $this->analyzer->stressTest($portfolio, $scenarios);

        // Check both scenarios tested
        $this->assertArrayHasKey('market_crash', $stressTestResults);
        $this->assertArrayHasKey('moderate_correction', $stressTestResults);

        // Check scenario results
        $crashResult = $stressTestResults['market_crash'];
        $this->assertArrayHasKey('var_95', $crashResult);
        $this->assertArrayHasKey('var_99', $crashResult);
        $this->assertArrayHasKey('expected_return', $crashResult);
        $this->assertArrayHasKey('max_loss', $crashResult);
        $this->assertArrayHasKey('severity', $crashResult);

        // Crash should be more severe than correction
        $this->assertGreaterThan(
            $stressTestResults['moderate_correction']['severity'],
            $crashResult['severity']
        );
    }

    public function testRiskContribution(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.05, 0.06, 0.04, 0.07], // High risk
                'weight' => 0.3,
            ],
            'BOND' => [
                'returns' => [0.01, 0.01, 0.01, 0.01], // Low risk
                'weight' => 0.7,
            ],
        ];

        $contributions = $this->analyzer->riskContribution($portfolio);

        // Check both assets present
        $this->assertArrayHasKey('AAPL', $contributions);
        $this->assertArrayHasKey('BOND', $contributions);

        // AAPL should contribute more risk despite lower weight
        $this->assertGreaterThan(
            $contributions['BOND']['contribution_percent'],
            $contributions['AAPL']['contribution_percent']
        );

        // Contributions should sum to 100%
        $totalContribution = $contributions['AAPL']['contribution_percent'] +
                            $contributions['BOND']['contribution_percent'];
        
        $this->assertEquals(100.0, $totalContribution, '', 1.0);
    }

    public function testCalculateRiskScore(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $analysis = $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);

        $riskScore = $analysis['risk_score'];

        // Check score calculation
        $this->assertIsFloat($riskScore['total']);
        $this->assertGreaterThanOrEqual(0, $riskScore['total']);
        $this->assertLessThanOrEqual(100, $riskScore['total']);

        // Check all components between 0-25
        foreach (['var_component', 'diversification_component', 'beta_component', 'performance_component'] as $component) {
            $this->assertGreaterThanOrEqual(0, $riskScore[$component]);
            $this->assertLessThanOrEqual(25, $riskScore[$component]);
        }
    }

    public function testEmptyPortfolioHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->analyzer->analyzePortfolio([], [], 0.02, 0.95);
    }

    public function testMissingWeightsHandling(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                // Missing 'weight'
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];

        $this->expectException(\InvalidArgumentException::class);
        $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);
    }

    public function testInvalidConfidenceLevelHandling(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 1.0,
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];

        $this->expectException(\InvalidArgumentException::class);
        $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 1.5); // > 1.0
    }

    public function testWeightsSumTo100Percent(): void
    {
        $portfolio = [
            'AAPL' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 0.6,
            ],
            'MSFT' => [
                'returns' => [0.01, 0.02, -0.01, 0.03],
                'weight' => 0.5, // Sums to 1.1
            ],
        ];

        $marketReturns = [0.01, 0.02, -0.01, 0.03];

        $this->expectException(\InvalidArgumentException::class);
        $this->analyzer->analyzePortfolio($portfolio, $marketReturns, 0.02, 0.95);
    }
}
