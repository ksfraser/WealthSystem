<?php

namespace Tests\Portfolio;

use PHPUnit\Framework\TestCase;
use WealthSystem\StockAnalysis\Portfolio\ModernPortfolioTheoryOptimizer;
use Psr\Log\NullLogger;

/**
 * Tests for Modern Portfolio Theory Optimizer
 */
class ModernPortfolioTheoryOptimizerTest extends TestCase
{
    private ModernPortfolioTheoryOptimizer $optimizer;
    
    protected function setUp(): void
    {
        $this->optimizer = new ModernPortfolioTheoryOptimizer(new NullLogger());
    }
    
    public function testMaximizeSharpeRatioReturnsValidResult(): void
    {
        $tickers = ['AAPL', 'MSFT', 'GOOGL'];
        
        $result = $this->optimizer->maximizeSharpeRatio($tickers);
        
        $this->assertTrue($result->isValid());
        $this->assertNull($result->error);
        $this->assertEquals('maximize_sharpe_ratio', $result->method);
        $this->assertGreaterThan(0, $result->sharpeRatio);
    }
    
    public function testMaximizeSharpeRatioWeightsSumToOne(): void
    {
        $tickers = ['AAPL', 'MSFT', 'GOOGL'];
        
        $result = $this->optimizer->maximizeSharpeRatio($tickers);
        
        $weightSum = array_sum($result->weights);
        $this->assertEqualsWithDelta(1.0, $weightSum, 0.0001);
    }
    
    public function testMaximizeSharpeRatioRespectsWeightConstraints(): void
    {
        $tickers = ['AAPL', 'MSFT'];
        
        $result = $this->optimizer->maximizeSharpeRatio($tickers, [
            'min_weight' => 0.2,
            'max_weight' => 0.6,
        ]);
        
        foreach ($result->weights as $weight) {
            $this->assertGreaterThanOrEqual(0.2, $weight);
            $this->assertLessThanOrEqual(0.6, $weight);
        }
    }
    
    public function testMinimizeVarianceReturnsLowestRisk(): void
    {
        $tickers = ['AAPL', 'MSFT', 'GOOGL'];
        
        $result = $this->optimizer->minimizeVariance($tickers);
        
        $this->assertTrue($result->isValid());
        $this->assertEquals('minimize_variance', $result->method);
        $this->assertGreaterThan(0, $result->volatility);
        $this->assertLessThan(1.0, $result->volatility); // Volatility < 100%
    }
    
    public function testTargetReturnAchievesTarget(): void
    {
        $tickers = ['AAPL', 'MSFT', 'GOOGL'];
        $targetReturn = 0.10; // 10%
        
        $result = $this->optimizer->targetReturn($tickers, $targetReturn);
        
        if ($result->isValid()) {
            $this->assertEquals('target_return', $result->method);
            $this->assertEqualsWithDelta($targetReturn, $result->expectedReturn, 0.01);
        } else {
            // Target may not be achievable with given tickers
            $this->assertStringContainsString('Could not find', $result->error);
        }
    }
    
    public function testEfficientFrontierReturnsSortedPoints(): void
    {
        $tickers = ['AAPL', 'MSFT', 'GOOGL'];
        
        $points = $this->optimizer->calculateEfficientFrontier($tickers, 5);
        
        $this->assertCount(5, $points);
        
        // Check sorted by volatility
        for ($i = 1; $i < count($points); $i++) {
            $this->assertGreaterThanOrEqual(
                $points[$i - 1]->volatility,
                $points[$i]->volatility
            );
        }
    }
    
    public function testEfficientFrontierWeightsSumToOne(): void
    {
        $tickers = ['AAPL', 'MSFT'];
        
        $points = $this->optimizer->calculateEfficientFrontier($tickers, 3);
        
        foreach ($points as $point) {
            $weightSum = array_sum($point->weights);
            $this->assertEqualsWithDelta(1.0, $weightSum, 0.0001);
        }
    }
    
    public function testCustomRiskFreeRate(): void
    {
        $tickers = ['AAPL', 'MSFT'];
        
        $result1 = $this->optimizer->maximizeSharpeRatio($tickers, [
            'risk_free_rate' => 0.02,
        ]);
        
        $result2 = $this->optimizer->maximizeSharpeRatio($tickers, [
            'risk_free_rate' => 0.05,
        ]);
        
        // Higher risk-free rate should result in lower Sharpe ratio
        $this->assertLessThan($result1->sharpeRatio, $result2->sharpeRatio);
    }
    
    public function testOptimizerName(): void
    {
        $this->assertEquals('Modern Portfolio Theory', $this->optimizer->getOptimizerName());
    }
    
    public function testOptimizerIsAvailable(): void
    {
        $this->assertTrue($this->optimizer->isAvailable());
    }
}
