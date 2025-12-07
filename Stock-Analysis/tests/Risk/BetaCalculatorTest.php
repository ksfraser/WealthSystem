<?php

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\BetaCalculator;

class BetaCalculatorTest extends TestCase
{
    private BetaCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new BetaCalculator();
    }

    public function testBetaOneForMarketLikeAsset(): void
    {
        // Asset moves exactly with market
        $assetReturns = [0.01, 0.02, -0.01, 0.03, -0.02];
        $marketReturns = [0.01, 0.02, -0.01, 0.03, -0.02];

        $beta = $this->calculator->beta($assetReturns, $marketReturns);

        $this->assertEquals(1.0, $beta, '', 0.0001);
    }

    public function testBetaGreaterThanOneForVolatileAsset(): void
    {
        // Asset amplifies market movements (2x)
        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $assetReturns = [0.02, 0.04, -0.02, 0.06];

        $beta = $this->calculator->beta($assetReturns, $marketReturns);

        $this->assertGreaterThan(1.8, $beta);
        $this->assertLessThan(2.2, $beta);
    }

    public function testBetaLessThanOneForDefensiveAsset(): void
    {
        // Asset moves less than market (0.5x)
        $marketReturns = [0.02, 0.04, -0.02, 0.06];
        $assetReturns = [0.01, 0.02, -0.01, 0.03];

        $beta = $this->calculator->beta($assetReturns, $marketReturns);

        $this->assertGreaterThan(0.3, $beta);
        $this->assertLessThan(0.7, $beta);
    }

    public function testNegativeBetaForInverseAsset(): void
    {
        // Asset moves opposite to market
        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $assetReturns = [-0.01, -0.02, 0.01, -0.03];

        $beta = $this->calculator->beta($assetReturns, $marketReturns);

        $this->assertLessThan(0, $beta);
    }

    public function testAlphaPositiveForOutperformer(): void
    {
        // Asset returns exceed CAPM expectation
        $assetReturns = [0.05, 0.06, 0.04, 0.07]; // High returns
        $marketReturns = [0.02, 0.03, 0.01, 0.04]; // Lower returns
        $riskFreeRate = 0.02;

        $alpha = $this->calculator->alpha($assetReturns, $marketReturns, $riskFreeRate);

        $this->assertGreaterThan(0, $alpha);
    }

    public function testAlphaNegativeForUnderperformer(): void
    {
        // Asset returns below CAPM expectation
        $assetReturns = [0.01, 0.01, 0.00, 0.01]; // Low returns
        $marketReturns = [0.03, 0.04, 0.02, 0.05]; // Higher returns
        $riskFreeRate = 0.02;

        $alpha = $this->calculator->alpha($assetReturns, $marketReturns, $riskFreeRate);

        $this->assertLessThan(0, $alpha);
    }

    public function testRSquared(): void
    {
        // Perfect correlation: R² = 1.0
        $assetReturns = [0.01, 0.02, 0.03, 0.04];
        $marketReturns = [0.02, 0.04, 0.06, 0.08];

        $rSquared = $this->calculator->rSquared($assetReturns, $marketReturns);

        $this->assertEquals(1.0, $rSquared, '', 0.0001);
    }

    public function testRSquaredLowForUncorrelatedAsset(): void
    {
        // Uncorrelated: R² close to 0
        $assetReturns = [0.01, -0.01, 0.02, -0.02];
        $marketReturns = [0.01, 0.02, 0.01, 0.02];

        $rSquared = $this->calculator->rSquared($assetReturns, $marketReturns);

        $this->assertLessThan(0.5, $rSquared);
    }

    public function testCalculateAllMetrics(): void
    {
        $assetReturns = [0.02, 0.04, -0.02, 0.06];
        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $riskFreeRate = 0.02;

        $result = $this->calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);

        // Check all keys present
        $this->assertArrayHasKey('beta', $result);
        $this->assertArrayHasKey('alpha', $result);
        $this->assertArrayHasKey('r_squared', $result);
        $this->assertArrayHasKey('asset_return', $result);
        $this->assertArrayHasKey('market_return', $result);
        $this->assertArrayHasKey('asset_volatility', $result);
        $this->assertArrayHasKey('market_volatility', $result);
        $this->assertArrayHasKey('systematic_risk', $result);
        $this->assertArrayHasKey('unsystematic_risk', $result);
        $this->assertArrayHasKey('systematic_percent', $result);
        $this->assertArrayHasKey('unsystematic_percent', $result);
        $this->assertArrayHasKey('beta_interpretation', $result);
        $this->assertArrayHasKey('alpha_interpretation', $result);

        // Check beta > 1 (asset is 2x volatile)
        $this->assertGreaterThan(1.0, $result['beta']);

        // Check systematic + unsystematic = 100%
        $this->assertEquals(
            100.0,
            $result['systematic_percent'] + $result['unsystematic_percent'],
            '',
            0.1
        );
    }

    public function testBetaInterpretation(): void
    {
        $testCases = [
            [[0.02, 0.04, -0.02, 0.06], [0.01, 0.02, -0.01, 0.03], 'high_volatility'], // Beta > 1.2
            [[0.015, 0.025, -0.015, 0.035], [0.01, 0.02, -0.01, 0.03], 'above_market'], // Beta > 1.0
            [[0.01, 0.02, -0.01, 0.03], [0.01, 0.02, -0.01, 0.03], 'market_like'], // Beta ~ 1.0
            [[0.005, 0.01, -0.005, 0.015], [0.01, 0.02, -0.01, 0.03], 'low_volatility'], // Beta < 0.8
            [[-0.01, -0.02, 0.01, -0.03], [0.01, 0.02, -0.01, 0.03], 'negative_correlation'], // Beta < 0
        ];

        foreach ($testCases as [$assetReturns, $marketReturns, $expectedInterpretation]) {
            $result = $this->calculator->calculate($assetReturns, $marketReturns, 0.02);
            
            $this->assertEquals(
                $expectedInterpretation,
                $result['beta_interpretation'],
                "Failed for expected interpretation: $expectedInterpretation"
            );
        }
    }

    public function testAlphaInterpretation(): void
    {
        $marketReturns = [0.02, 0.03, 0.01, 0.04];
        $riskFreeRate = 0.02;

        $testCases = [
            [[0.10, 0.12, 0.08, 0.15], 'strong_outperformance'], // Very high returns
            [[0.05, 0.06, 0.04, 0.07], 'outperformance'], // Good returns
            [[0.02, 0.03, 0.01, 0.04], 'market_performance'], // Market-like returns
            [[0.00, 0.01, -0.01, 0.01], 'underperformance'], // Poor returns
            [[-0.05, -0.03, -0.07, -0.02], 'strong_underperformance'], // Very poor returns
        ];

        foreach ($testCases as [$assetReturns, $expectedInterpretation]) {
            $result = $this->calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);
            
            $this->assertEquals(
                $expectedInterpretation,
                $result['alpha_interpretation'],
                "Failed for expected interpretation: $expectedInterpretation"
            );
        }
    }

    public function testRollingBeta(): void
    {
        $assetReturns = array_fill(0, 100, 0.02);
        $marketReturns = array_fill(0, 100, 0.01);
        $window = 30;

        $rollingBeta = $this->calculator->rollingBeta($assetReturns, $marketReturns, $window);

        // Should have length = count - window + 1
        $expectedLength = count($assetReturns) - $window + 1;
        $this->assertCount($expectedLength, $rollingBeta);

        // All betas should be around 2.0 (asset is 2x market)
        foreach ($rollingBeta as $beta) {
            $this->assertGreaterThan(1.5, $beta);
            $this->assertLessThan(2.5, $beta);
        }
    }

    public function testRollingBetaChangingMarketConditions(): void
    {
        // First half: high beta, second half: low beta
        $marketReturns = array_merge(
            array_fill(0, 50, 0.01),
            array_fill(0, 50, 0.02)
        );
        
        $assetReturns = array_merge(
            array_fill(0, 50, 0.03), // 3x market (high beta)
            array_fill(0, 50, 0.02)  // 1x market (low beta)
        );

        $window = 20;
        $rollingBeta = $this->calculator->rollingBeta($assetReturns, $marketReturns, $window);

        // Early betas should be higher
        $earlyBeta = array_slice($rollingBeta, 0, 10);
        $lateBeta = array_slice($rollingBeta, -10);

        $this->assertGreaterThan(
            array_sum($lateBeta) / count($lateBeta),
            array_sum($earlyBeta) / count($earlyBeta)
        );
    }

    public function testRollingAlpha(): void
    {
        $assetReturns = array_fill(0, 100, 0.05); // Consistently high
        $marketReturns = array_fill(0, 100, 0.02); // Lower
        $riskFreeRate = 0.01;
        $window = 30;

        $rollingAlpha = $this->calculator->rollingAlpha(
            $assetReturns,
            $marketReturns,
            $riskFreeRate,
            $window
        );

        // All alphas should be positive (outperformance)
        foreach ($rollingAlpha as $alpha) {
            $this->assertGreaterThan(0, $alpha);
        }
    }

    public function testTreynorRatio(): void
    {
        $assetReturns = [0.05, 0.06, 0.04, 0.07]; // High returns
        $marketReturns = [0.02, 0.03, 0.01, 0.04];
        $riskFreeRate = 0.02;

        $treynor = $this->calculator->treynorRatio($assetReturns, $marketReturns, $riskFreeRate);

        // Should be positive for profitable asset
        $this->assertGreaterThan(0, $treynor);
    }

    public function testTreynorRatioHigherForBetterRiskAdjustedReturns(): void
    {
        $marketReturns = [0.02, 0.03, 0.01, 0.04];
        $riskFreeRate = 0.02;

        // Asset A: High returns, high beta
        $assetA = [0.08, 0.10, 0.06, 0.12];
        $treynorA = $this->calculator->treynorRatio($assetA, $marketReturns, $riskFreeRate);

        // Asset B: Moderate returns, low beta
        $assetB = [0.03, 0.04, 0.02, 0.05];
        $treynorB = $this->calculator->treynorRatio($assetB, $marketReturns, $riskFreeRate);

        // Asset A should have better risk-adjusted return
        $this->assertGreaterThan($treynorB, $treynorA);
    }

    public function testJensensAlpha(): void
    {
        $assetReturns = [0.05, 0.06, 0.04, 0.07];
        $marketReturns = [0.02, 0.03, 0.01, 0.04];
        $riskFreeRate = 0.02;

        $jensensAlpha = $this->calculator->jensensAlpha($assetReturns, $marketReturns, $riskFreeRate);
        $alpha = $this->calculator->alpha($assetReturns, $marketReturns, $riskFreeRate);

        // Jensen's alpha should equal regular alpha
        $this->assertEquals($alpha, $jensensAlpha, '', 0.0001);
    }

    public function testMarketTiming(): void
    {
        // Create returns with good timing (high beta in up markets, low in down)
        $marketReturns = [0.05, 0.03, -0.02, -0.04, 0.06, 0.02, -0.03, -0.01];
        
        // Asset amplifies gains, dampens losses (good timing)
        $assetReturns = [0.10, 0.06, -0.01, -0.02, 0.12, 0.04, -0.015, -0.005];

        $timing = $this->calculator->marketTiming($assetReturns, $marketReturns);

        // Check keys present
        $this->assertArrayHasKey('beta_up', $timing);
        $this->assertArrayHasKey('beta_down', $timing);
        $this->assertArrayHasKey('timing_coefficient', $timing);
        $this->assertArrayHasKey('up_periods', $timing);
        $this->assertArrayHasKey('down_periods', $timing);

        // Beta up should be higher than beta down (good timing)
        $this->assertGreaterThan($timing['beta_down'], $timing['beta_up']);

        // Timing coefficient should be positive
        $this->assertGreaterThan(0, $timing['timing_coefficient']);

        // Check period counts
        $this->assertEquals(4, $timing['up_periods']);
        $this->assertEquals(4, $timing['down_periods']);
    }

    public function testMarketTimingPoorTiming(): void
    {
        $marketReturns = [0.05, 0.03, -0.02, -0.04];
        
        // Asset amplifies losses, dampens gains (poor timing)
        $assetReturns = [0.03, 0.02, -0.04, -0.08];

        $timing = $this->calculator->marketTiming($assetReturns, $marketReturns);

        // Beta down should be higher than beta up (poor timing)
        $this->assertGreaterThan($timing['beta_up'], $timing['beta_down']);

        // Timing coefficient should be negative
        $this->assertLessThan(0, $timing['timing_coefficient']);
    }

    public function testSystematicVsUnsystematicRisk(): void
    {
        // High R²: most risk is systematic
        $assetReturns = [0.02, 0.04, -0.02, 0.06];
        $marketReturns = [0.01, 0.02, -0.01, 0.03];
        $riskFreeRate = 0.02;

        $result = $this->calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);

        // With high correlation, systematic risk should dominate
        $this->assertGreaterThan(70, $result['systematic_percent']);
        $this->assertLessThan(30, $result['unsystematic_percent']);
    }

    public function testEmptyInputHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->beta([], []);
    }

    public function testMismatchedLengthHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->calculator->beta([1, 2, 3], [1, 2]);
    }

    public function testZeroMarketVarianceHandling(): void
    {
        $assetReturns = [0.01, 0.02, 0.03];
        $marketReturns = [0.02, 0.02, 0.02]; // No variance

        // Should handle gracefully
        $beta = $this->calculator->beta($assetReturns, $marketReturns);
        
        $this->assertTrue(is_nan($beta) || $beta === 0.0);
    }
}
