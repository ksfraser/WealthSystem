<?php

declare(strict_types=1);

namespace Tests\Risk;

use PHPUnit\Framework\TestCase;
use App\Risk\StressTester;

class StressTestTest extends TestCase
{
    private StressTester $tester;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->tester = new StressTester();
    }
    
    public function testMarketCrash(): void
    {
        $result = $this->tester->marketCrash(100000.0, 0.20);
        
        $this->assertSame('Market Crash', $result['scenario']);
        $this->assertSame(100000.0, $result['original_value']);
        $this->assertSame(80000.0, $result['stressed_value']);
        $this->assertSame(20000.0, $result['loss']);
        $this->assertSame(20.0, $result['crash_percent']);
    }
    
    public function testMarketCrashDefaultPercent(): void
    {
        $result = $this->tester->marketCrash(50000.0);
        
        $this->assertSame(40000.0, $result['stressed_value']);
        $this->assertSame(20.0, $result['crash_percent']);
    }
    
    public function testVolatilitySpike(): void
    {
        $returns = [-0.02, -0.01, 0.00, 0.01, 0.02];
        
        $result = $this->tester->volatilitySpike($returns, 2.0);
        
        $this->assertSame('Volatility Spike', $result['scenario']);
        $this->assertSame(2.0, $result['multiplier']);
        $this->assertArrayHasKey('stressed_returns', $result);
        $this->assertCount(5, $result['stressed_returns']);
        $this->assertGreaterThan($result['original_std'], $result['stressed_std']);
    }
    
    public function testRunAllScenarios(): void
    {
        $returns = [-0.05, -0.03, -0.01, 0.01, 0.03, 0.05];
        
        $results = $this->tester->runAllScenarios(100000.0, $returns);
        
        $this->assertArrayHasKey('mild_crash', $results);
        $this->assertArrayHasKey('moderate_crash', $results);
        $this->assertArrayHasKey('severe_crash', $results);
        $this->assertArrayHasKey('volatility_spike', $results);
        $this->assertArrayHasKey('extreme_volatility', $results);
        
        // Verify crash severity ordering
        $this->assertGreaterThan($results['mild_crash']['loss'], $results['moderate_crash']['loss']);
        $this->assertGreaterThan($results['moderate_crash']['loss'], $results['severe_crash']['loss']);
    }
    
    public function testSevereCrash(): void
    {
        $result = $this->tester->marketCrash(100000.0, 0.40);
        
        $this->assertSame(60000.0, $result['stressed_value']);
        $this->assertSame(40000.0, $result['loss']);
    }
}
