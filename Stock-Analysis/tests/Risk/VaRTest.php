<?php

declare(strict_types=1);

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\VaRCalculator;
use App\Risk\CVaRCalculator;

class VaRTest extends TestCase
{
    private VaRCalculator $var;
    private CVaRCalculator $cvar;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->var = new VaRCalculator();
        $this->cvar = new CVaRCalculator();
    }
    
    public function testVaRHistorical(): void
    {
        $returns = [-0.05, -0.03, -0.02, -0.01, 0.00, 0.01, 0.02, 0.03, 0.04, 0.05];
        
        $var95 = $this->var->calculateHistorical($returns, 0.95);
        
        $this->assertGreaterThan(0, $var95);
        $this->assertLessThan(0.06, $var95);
    }
    
    public function testVaRHistoricalEmpty(): void
    {
        $var = $this->var->calculateHistorical([], 0.95);
        
        $this->assertSame(0.0, $var);
    }
    
    public function testVaRParametric(): void
    {
        $var95 = $this->var->calculateParametric(0.001, 0.02, 0.95);
        
        $this->assertGreaterThan(0, $var95);
    }
    
    public function testVaRParametricDifferentConfidenceLevels(): void
    {
        $var90 = $this->var->calculateParametric(0.001, 0.02, 0.90);
        $var95 = $this->var->calculateParametric(0.001, 0.02, 0.95);
        $var99 = $this->var->calculateParametric(0.001, 0.02, 0.99);
        
        // VaR should increase with confidence level
        $this->assertLessThanOrEqual($var95, $var90);
        $this->assertGreaterThanOrEqual($var95, $var99);
    }
    
    public function testPortfolioVaR(): void
    {
        $returns = [-0.05, -0.03, -0.01, 0.01, 0.03, 0.05];
        $portfolioValue = 100000.0;
        
        $var = $this->var->calculatePortfolioVaR($portfolioValue, $returns, 0.95);
        
        $this->assertGreaterThan(0, $var);
        $this->assertLessThan($portfolioValue, $var);
    }
    
    public function testCVaRHistorical(): void
    {
        $returns = [-0.10, -0.08, -0.05, -0.02, 0.00, 0.02, 0.05, 0.08, 0.10];
        
        $cvar95 = $this->cvar->calculateHistorical($returns, 0.95);
        
        $this->assertGreaterThan(0, $cvar95);
    }
    
    public function testCVaREmpty(): void
    {
        $cvar = $this->cvar->calculateHistorical([], 0.95);
        
        $this->assertSame(0.0, $cvar);
    }
    
    public function testCVaRGreaterThanVaR(): void
    {
        $returns = [-0.10, -0.08, -0.06, -0.04, -0.02, 0.00, 0.02, 0.04, 0.06, 0.08];
        
        $var95 = $this->var->calculateHistorical($returns, 0.95);
        $cvar95 = $this->cvar->calculateHistorical($returns, 0.95);
        
        // CVaR should be >= VaR (expected loss given loss exceeds VaR)
        $this->assertGreaterThanOrEqual($var95, $cvar95);
    }
    
    public function testPortfolioCVaR(): void
    {
        $returns = [-0.05, -0.03, -0.01, 0.01, 0.03, 0.05];
        $portfolioValue = 100000.0;
        
        $cvar = $this->cvar->calculatePortfolioCVaR($portfolioValue, $returns, 0.95);
        
        $this->assertGreaterThan(0, $cvar);
        $this->assertLessThan($portfolioValue, $cvar);
    }
    
    public function testCVaRToVaRRatio(): void
    {
        $returns = [-0.10, -0.08, -0.06, -0.04, -0.02, 0.00, 0.02, 0.04, 0.06, 0.08];
        
        $ratio = $this->cvar->calculateCVaRToVaRRatio($returns, 0.95);
        
        $this->assertGreaterThanOrEqual(1.0, $ratio);
    }
}
