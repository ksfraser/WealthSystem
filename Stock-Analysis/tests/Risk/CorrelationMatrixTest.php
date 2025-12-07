<?php

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\CorrelationMatrix;

class CorrelationMatrixTest extends TestCase
{
    private CorrelationMatrix $correlationMatrix;

    protected function setUp(): void
    {
        $this->correlationMatrix = new CorrelationMatrix();
    }

    public function testPearsonCorrelationPerfectPositive(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [2, 4, 6, 8, 10]; // Perfect positive correlation

        $correlation = $this->correlationMatrix->pearsonCorrelation($x, $y);

        $this->assertEquals(1.0, $correlation, '', 0.0001);
    }

    public function testPearsonCorrelationPerfectNegative(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [10, 8, 6, 4, 2]; // Perfect negative correlation

        $correlation = $this->correlationMatrix->pearsonCorrelation($x, $y);

        $this->assertEquals(-1.0, $correlation, '', 0.0001);
    }

    public function testPearsonCorrelationNoCorrelation(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [5, 3, 4, 1, 2]; // Random, low correlation

        $correlation = $this->correlationMatrix->pearsonCorrelation($x, $y);

        $this->assertLessThan(0.5, abs($correlation));
    }

    public function testSpearmanCorrelationMonotonic(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [1, 4, 9, 16, 25]; // Monotonic but not linear

        $spearman = $this->correlationMatrix->spearmanCorrelation($x, $y);
        $pearson = $this->correlationMatrix->pearsonCorrelation($x, $y);

        // Spearman should be 1.0 (perfect rank correlation)
        $this->assertEquals(1.0, $spearman, '', 0.0001);
        
        // Pearson should be less than 1.0 (not perfectly linear)
        $this->assertLessThan(1.0, $pearson);
    }

    public function testKendallCorrelation(): void
    {
        $x = [1, 2, 3, 4, 5];
        $y = [5, 1, 2, 4, 3]; // Some concordant, some discordant pairs

        $kendall = $this->correlationMatrix->kendallCorrelation($x, $y);

        // Should be between -1 and 1
        $this->assertGreaterThanOrEqual(-1.0, $kendall);
        $this->assertLessThanOrEqual(1.0, $kendall);
    }

    public function testCalculateCorrelationMatrixPearson(): void
    {
        $returns = [
            'AAPL' => [0.01, 0.02, -0.01, 0.03],
            'MSFT' => [0.01, 0.015, -0.005, 0.025],
            'GOOGL' => [-0.01, -0.02, 0.01, -0.015],
        ];

        $matrix = $this->correlationMatrix->calculate($returns, 'pearson');

        // Check diagonal is 1.0 (self-correlation)
        $this->assertEquals(1.0, $matrix['AAPL']['AAPL'], '', 0.0001);
        $this->assertEquals(1.0, $matrix['MSFT']['MSFT'], '', 0.0001);
        $this->assertEquals(1.0, $matrix['GOOGL']['GOOGL'], '', 0.0001);

        // Check symmetry
        $this->assertEquals(
            $matrix['AAPL']['MSFT'],
            $matrix['MSFT']['AAPL'],
            '',
            0.0001
        );

        // Check AAPL-MSFT positive correlation (similar movements)
        $this->assertGreaterThan(0.5, $matrix['AAPL']['MSFT']);

        // Check AAPL-GOOGL negative correlation (opposite movements)
        $this->assertLessThan(-0.5, $matrix['AAPL']['GOOGL']);
    }

    public function testCalculateCorrelationMatrixSpearman(): void
    {
        $returns = [
            'AAPL' => [0.01, 0.02, 0.03, 0.04],
            'MSFT' => [1, 4, 9, 16], // Monotonic but not linear
        ];

        $matrixPearson = $this->correlationMatrix->calculate($returns, 'pearson');
        $matrixSpearman = $this->correlationMatrix->calculate($returns, 'spearman');

        // Spearman should detect perfect monotonic relationship
        $this->assertEquals(1.0, $matrixSpearman['AAPL']['MSFT'], '', 0.0001);

        // Pearson should be less than 1.0
        $this->assertLessThan(1.0, $matrixPearson['AAPL']['MSFT']);
    }

    public function testRollingCorrelation(): void
    {
        $x = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $y = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];
        $window = 3;

        $rolling = $this->correlationMatrix->rollingCorrelation($x, $y, $window);

        // Should have length = count - window + 1
        $this->assertCount(8, $rolling);

        // All should be 1.0 (perfect positive correlation)
        foreach ($rolling as $corr) {
            $this->assertEquals(1.0, $corr, '', 0.0001);
        }
    }

    public function testRollingCorrelationChangingRelationship(): void
    {
        // First half: positive correlation, second half: negative correlation
        $x = [1, 2, 3, 4, 5, 6, 7, 8];
        $y = [2, 4, 6, 8, 8, 6, 4, 2];
        $window = 3;

        $rolling = $this->correlationMatrix->rollingCorrelation($x, $y, $window);

        // Early windows should be positive
        $this->assertGreaterThan(0.5, $rolling[0]);

        // Later windows should be negative
        $this->assertLessThan(-0.5, $rolling[count($rolling) - 1]);
    }

    public function testCorrelationStats(): void
    {
        $x = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $y = [2, 4, 6, 8, 10, 12, 14, 16, 18, 20];

        $stats = $this->correlationMatrix->correlationStats($x, $y, 3);

        // Check all stats present
        $this->assertArrayHasKey('pearson', $stats);
        $this->assertArrayHasKey('spearman', $stats);
        $this->assertArrayHasKey('kendall', $stats);
        $this->assertArrayHasKey('rolling', $stats);
        $this->assertArrayHasKey('interpretation', $stats);

        // Check perfect correlation detected
        $this->assertEquals(1.0, $stats['pearson'], '', 0.0001);
        $this->assertEquals('very_strong', $stats['interpretation']);
    }

    public function testCorrelationInterpretation(): void
    {
        $testCases = [
            [0.95, 'very_strong'],
            [0.75, 'strong'],
            [0.50, 'moderate'],
            [0.25, 'weak'],
            [0.05, 'very_weak'],
            [-0.95, 'very_strong'],
            [-0.75, 'strong'],
        ];

        foreach ($testCases as [$value, $expected]) {
            $x = [1, 2, 3, 4, 5];
            
            // Create y that produces desired correlation
            if ($value > 0) {
                $y = array_map(fn($val) => $val * $value + (1 - $value) * 3, $x);
            } else {
                $y = array_map(fn($val) => -$val * abs($value) + (1 + abs($value)) * 3, $x);
            }
            
            $stats = $this->correlationMatrix->correlationStats($x, $y);
            
            $this->assertEquals($expected, $stats['interpretation']);
        }
    }

    public function testFindCorrelatedPairs(): void
    {
        $returns = [
            'AAPL' => [0.01, 0.02, -0.01, 0.03],
            'MSFT' => [0.01, 0.02, -0.01, 0.03], // Same as AAPL
            'GOOGL' => [0.02, 0.03, -0.015, 0.04], // Similar to AAPL/MSFT
            'TSLA' => [-0.01, -0.02, 0.01, -0.03], // Opposite
        ];

        $pairs = $this->correlationMatrix->findCorrelatedPairs($returns, 0.95);

        // Should find AAPL-MSFT pair (identical)
        $found = false;
        foreach ($pairs as $pair) {
            if (($pair['asset1'] === 'AAPL' && $pair['asset2'] === 'MSFT') ||
                ($pair['asset1'] === 'MSFT' && $pair['asset2'] === 'AAPL')) {
                $found = true;
                $this->assertEquals(1.0, $pair['correlation'], '', 0.0001);
                break;
            }
        }
        $this->assertTrue($found, 'Expected to find AAPL-MSFT pair');
    }

    public function testDiversificationScore(): void
    {
        // Test 1: Perfectly correlated assets (poor diversification)
        $poorlyDiversified = [
            'AAPL' => [0.01, 0.02, 0.03, 0.04],
            'MSFT' => [0.01, 0.02, 0.03, 0.04],
            'GOOGL' => [0.01, 0.02, 0.03, 0.04],
        ];

        $poorScore = $this->correlationMatrix->diversificationScore($poorlyDiversified);
        
        $this->assertArrayHasKey('score', $poorScore);
        $this->assertArrayHasKey('interpretation', $poorScore);
        $this->assertLessThan(0.3, $poorScore['score']); // Low score = poor diversification
        $this->assertEquals('very_poor', $poorScore['interpretation']);

        // Test 2: Uncorrelated assets (good diversification)
        $wellDiversified = [
            'AAPL' => [0.01, 0.02, 0.03, 0.04],
            'BOND' => [0.01, -0.01, 0.02, -0.01],
            'GOLD' => [-0.01, 0.01, -0.01, 0.02],
        ];

        $goodScore = $this->correlationMatrix->diversificationScore($wellDiversified);
        
        $this->assertGreaterThan(0.6, $goodScore['score']); // High score = good diversification
        $this->assertContains($goodScore['interpretation'], ['good', 'excellent']);
    }

    public function testToDistanceMatrix(): void
    {
        $correlationMatrix = [
            'AAPL' => ['AAPL' => 1.0, 'MSFT' => 0.8, 'GOOGL' => -0.5],
            'MSFT' => ['AAPL' => 0.8, 'MSFT' => 1.0, 'GOOGL' => -0.3],
            'GOOGL' => ['AAPL' => -0.5, 'MSFT' => -0.3, 'GOOGL' => 1.0],
        ];

        $distanceMatrix = $this->correlationMatrix->toDistanceMatrix($correlationMatrix);

        // Check diagonal is 0 (distance to self)
        $this->assertEquals(0.0, $distanceMatrix['AAPL']['AAPL'], '', 0.0001);
        
        // Check symmetry
        $this->assertEquals(
            $distanceMatrix['AAPL']['MSFT'],
            $distanceMatrix['MSFT']['AAPL'],
            '',
            0.0001
        );

        // Check formula: distance = sqrt(2 * (1 - correlation))
        $expectedDistance = sqrt(2 * (1 - 0.8)); // AAPL-MSFT
        $this->assertEquals($expectedDistance, $distanceMatrix['AAPL']['MSFT'], '', 0.0001);

        // Negative correlation should give large distance
        $this->assertGreaterThan(1.0, $distanceMatrix['AAPL']['GOOGL']);
    }

    public function testEmptyInputHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->correlationMatrix->pearsonCorrelation([], []);
    }

    public function testMismatchedLengthHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->correlationMatrix->pearsonCorrelation([1, 2, 3], [1, 2]);
    }

    public function testInsufficientDataForRollingWindow(): void
    {
        $x = [1, 2];
        $y = [1, 2];
        $window = 5; // Larger than data

        $rolling = $this->correlationMatrix->rollingCorrelation($x, $y, $window);

        $this->assertEmpty($rolling);
    }

    public function testConstantDataHandling(): void
    {
        $x = [5, 5, 5, 5, 5]; // No variance
        $y = [1, 2, 3, 4, 5];

        // Should handle gracefully (return 0 or NaN)
        $correlation = $this->correlationMatrix->pearsonCorrelation($x, $y);
        
        $this->assertTrue(is_nan($correlation) || $correlation === 0.0);
    }
}
