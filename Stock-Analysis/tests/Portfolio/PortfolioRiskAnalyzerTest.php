<?php

namespace Tests\Portfolio;

use PHPUnit\Framework\TestCase;
use WealthSystem\StockAnalysis\Portfolio\PortfolioRiskAnalyzer;
use Psr\Log\NullLogger;

/**
 * Tests for Portfolio Risk Analyzer
 */
class PortfolioRiskAnalyzerTest extends TestCase
{
    private PortfolioRiskAnalyzer $analyzer;
    
    protected function setUp(): void
    {
        $this->analyzer = new PortfolioRiskAnalyzer(new NullLogger());
    }
    
    public function testAnalyzePortfolioReturnsAllMetrics(): void
    {
        $weights = ['AAPL' => 0.6, 'MSFT' => 0.4];
        $returns = [
            'AAPL' => array_fill(0, 252, 0.001), // 0.1% daily
            'MSFT' => array_fill(0, 252, 0.001),
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $this->assertArrayHasKey('volatility', $metrics);
        $this->assertArrayHasKey('sharpe_ratio', $metrics);
        $this->assertArrayHasKey('sortino_ratio', $metrics);
        $this->assertArrayHasKey('max_drawdown', $metrics);
        $this->assertArrayHasKey('var_95', $metrics);
        $this->assertArrayHasKey('var_99', $metrics);
        $this->assertArrayHasKey('beta', $metrics);
        $this->assertArrayHasKey('correlation_matrix', $metrics);
        $this->assertArrayHasKey('expected_return', $metrics);
    }
    
    public function testVolatilityIsPositive(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => array_map(fn() => (rand(-100, 100) / 10000), range(1, 252)),
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $this->assertGreaterThan(0, $metrics['volatility']);
    }
    
    public function testSharpeRatioCalculation(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => array_fill(0, 252, 0.001), // Consistent 0.1% daily
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns, [
            'risk_free_rate' => 0.02,
        ]);
        
        // Sharpe = (return - risk_free) / volatility
        $expectedReturn = $metrics['expected_return'];
        $volatility = $metrics['volatility'];
        $expectedSharpe = ($expectedReturn - 0.02) / $volatility;
        
        $this->assertEqualsWithDelta($expectedSharpe, $metrics['sharpe_ratio'], 0.01);
    }
    
    public function testMaxDrawdownIsNonNegative(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => [0.01, -0.02, 0.015, -0.005], // Mixed returns
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $this->assertGreaterThanOrEqual(0, $metrics['max_drawdown']);
        $this->assertLessThanOrEqual(1, $metrics['max_drawdown']); // Max 100% loss
    }
    
    public function testVaRIsNonNegative(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => array_map(fn() => (rand(-300, 300) / 10000), range(1, 252)),
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $this->assertGreaterThanOrEqual(0, $metrics['var_95']);
        $this->assertGreaterThanOrEqual(0, $metrics['var_99']);
        $this->assertGreaterThan($metrics['var_95'], $metrics['var_99']); // 99% > 95%
    }
    
    public function testBetaWithMarketReturns(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => array_fill(0, 252, 0.001),
        ];
        $marketReturns = array_fill(0, 252, 0.001);
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns, [
            'market_returns' => $marketReturns,
        ]);
        
        $this->assertNotNull($metrics['beta']);
        $this->assertEqualsWithDelta(1.0, $metrics['beta'], 0.1);
    }
    
    public function testBetaNullWithoutMarketReturns(): void
    {
        $weights = ['AAPL' => 1.0];
        $returns = [
            'AAPL' => array_fill(0, 252, 0.001),
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $this->assertNull($metrics['beta']);
    }
    
    public function testCorrelationMatrixStructure(): void
    {
        $weights = ['AAPL' => 0.5, 'MSFT' => 0.5];
        $returns = [
            'AAPL' => array_fill(0, 252, 0.001),
            'MSFT' => array_fill(0, 252, 0.001),
        ];
        
        $metrics = $this->analyzer->analyzePortfolio($weights, $returns);
        
        $matrix = $metrics['correlation_matrix'];
        
        // Check diagonal is 1.0 (self-correlation)
        $this->assertEqualsWithDelta(1.0, $matrix['AAPL']['AAPL'], 0.001);
        $this->assertEqualsWithDelta(1.0, $matrix['MSFT']['MSFT'], 0.001);
        
        // Check symmetry
        $this->assertEqualsWithDelta(
            $matrix['AAPL']['MSFT'],
            $matrix['MSFT']['AAPL'],
            0.001
        );
    }
    
    public function testFormatRiskMetrics(): void
    {
        $metrics = [
            'expected_return' => 0.10,
            'volatility' => 0.15,
            'sharpe_ratio' => 0.53,
            'sortino_ratio' => 0.67,
            'max_drawdown' => 0.20,
            'var_95' => 0.05,
            'var_99' => 0.08,
            'beta' => 1.2,
            'correlation_matrix' => [],
        ];
        
        $formatted = $this->analyzer->formatRiskMetrics($metrics);
        
        $this->assertStringContainsString('Expected Return: 10.00%', $formatted);
        $this->assertStringContainsString('Volatility (Risk): 15.00%', $formatted);
        $this->assertStringContainsString('Sharpe Ratio: 0.53', $formatted);
        $this->assertStringContainsString('Beta: 1.20', $formatted);
    }
    
    public function testSortinoRatioOnlyUsesDownsideRisk(): void
    {
        $weights = ['AAPL' => 1.0];
        
        // Portfolio with positive returns only
        $positiveReturns = ['AAPL' => array_fill(0, 252, 0.001)];
        $metrics1 = $this->analyzer->analyzePortfolio($weights, $positiveReturns);
        
        // Portfolio with mixed returns
        $mixedReturns = ['AAPL' => array_merge(
            array_fill(0, 126, 0.002),
            array_fill(0, 126, -0.001)
        )];
        $metrics2 = $this->analyzer->analyzePortfolio($weights, $mixedReturns);
        
        // Mixed returns should have higher Sortino (penalized by downside)
        $this->assertGreaterThan($metrics2['sortino_ratio'], $metrics1['sortino_ratio']);
    }
}
