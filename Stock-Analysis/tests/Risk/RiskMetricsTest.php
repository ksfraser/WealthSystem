<?php

declare(strict_types=1);

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\RiskMetrics;

class RiskMetricsTest extends TestCase
{
    private RiskMetrics $metrics;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->metrics = new RiskMetrics();
    }
    
    public function testSharpeRatio(): void
    {
        $returns = [0.01, 0.02, -0.01, 0.03, 0.01];
        
        $sharpe = $this->metrics->sharpeRatio($returns, 0.02);
        
        $this->assertIsFloat($sharpe);
    }
    
    public function testSharpeRatioEmpty(): void
    {
        $sharpe = $this->metrics->sharpeRatio([], 0.02);
        
        $this->assertSame(0.0, $sharpe);
    }
    
    public function testSortinoRatio(): void
    {
        $returns = [0.05, 0.03, -0.02, -0.04, 0.02];
        
        $sortino = $this->metrics->sortinoRatio($returns, 0.0);
        
        $this->assertIsFloat($sortino);
    }
    
    public function testSortinoRatioEmpty(): void
    {
        $sortino = $this->metrics->sortinoRatio([], 0.0);
        
        $this->assertSame(0.0, $sortino);
    }
    
    public function testMaxDrawdown(): void
    {
        $cumulativeReturns = [100, 110, 120, 100, 90, 95, 105, 115];
        
        $maxDD = $this->metrics->maxDrawdown($cumulativeReturns);
        
        // From peak 120 to trough 90 = 25% drawdown
        $this->assertEqualsWithDelta(0.25, $maxDD, 0.01);
    }
    
    public function testMaxDrawdownNoDrawdown(): void
    {
        $cumulativeReturns = [100, 110, 120, 130, 140];
        
        $maxDD = $this->metrics->maxDrawdown($cumulativeReturns);
        
        $this->assertSame(0.0, $maxDD);
    }
    
    public function testMaxDrawdownEmpty(): void
    {
        $maxDD = $this->metrics->maxDrawdown([]);
        
        $this->assertSame(0.0, $maxDD);
    }
    
    public function testStandardDeviation(): void
    {
        $returns = [0.01, 0.02, 0.03, 0.04, 0.05];
        
        $stdDev = $this->metrics->standardDeviation($returns);
        
        $this->assertGreaterThan(0, $stdDev);
        $this->assertLessThan(0.1, $stdDev);
    }
    
    public function testStandardDeviationSingleValue(): void
    {
        $stdDev = $this->metrics->standardDeviation([0.05]);
        
        $this->assertSame(0.0, $stdDev);
    }
    
    public function testDownsideDeviation(): void
    {
        $returns = [0.05, 0.03, -0.02, -0.04, 0.02];
        
        $downside = $this->metrics->downsideDeviation($returns, 0.0);
        
        $this->assertGreaterThan(0, $downside);
    }
    
    public function testDownsideDeviationNoNegatives(): void
    {
        $returns = [0.01, 0.02, 0.03, 0.04];
        
        $downside = $this->metrics->downsideDeviation($returns, 0.0);
        
        $this->assertSame(0.0, $downside);
    }
    
    public function testCalmarRatio(): void
    {
        $returns = [0.01, 0.02, -0.01, 0.01, 0.02];
        $cumulativeReturns = [100, 101, 103, 102, 103, 105];
        
        $calmar = $this->metrics->calmarRatio($returns, $cumulativeReturns);
        
        $this->assertIsFloat($calmar);
    }
    
    public function testCalmarRatioEmpty(): void
    {
        $calmar = $this->metrics->calmarRatio([], []);
        
        $this->assertSame(0.0, $calmar);
    }
    
    public function testCalmarRatioNoDrawdown(): void
    {
        $returns = [0.01, 0.02, 0.01];
        $cumulativeReturns = [100, 101, 103, 104];
        
        $calmar = $this->metrics->calmarRatio($returns, $cumulativeReturns);
        
        $this->assertSame(0.0, $calmar);
    }
}
