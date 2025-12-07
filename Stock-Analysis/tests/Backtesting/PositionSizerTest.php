<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use PHPUnit\Framework\TestCase;
use WealthSystem\Backtesting\PositionSizer;
use InvalidArgumentException;

/**
 * Tests for PositionSizer class
 * 
 * Covers all position sizing methods with normal cases and edge cases
 */
class PositionSizerTest extends TestCase
{
    private PositionSizer $sizer;
    
    protected function setUp(): void
    {
        $this->sizer = new PositionSizer();
    }
    
    // ==================== Fixed Dollar Tests ====================
    
    public function testFixedDollarNormalCase(): void
    {
        $result = $this->sizer->fixedDollar(
            portfolioValue: 100000,
            fixedAmount: 10000,
            currentPrice: 50
        );
        
        $this->assertEquals(200, $result['shares']);
        $this->assertEquals(10000, $result['value']);
        $this->assertEquals(0.1, $result['percent']);
        $this->assertEquals('fixed_dollar', $result['method']);
    }
    
    public function testFixedDollarExceedsPortfolio(): void
    {
        $result = $this->sizer->fixedDollar(
            portfolioValue: 5000,
            fixedAmount: 10000,
            currentPrice: 50
        );
        
        // Should cap at portfolio value
        $this->assertEquals(100, $result['shares']);
        $this->assertEquals(5000, $result['value']);
        $this->assertEquals(1.0, $result['percent']);
    }
    
    public function testFixedDollarHighPrice(): void
    {
        $result = $this->sizer->fixedDollar(
            portfolioValue: 100000,
            fixedAmount: 10000,
            currentPrice: 500
        );
        
        $this->assertEquals(20, $result['shares']);
        $this->assertEquals(10000, $result['value']);
    }
    
    public function testFixedDollarInvalidPortfolio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Portfolio value must be positive');
        
        $this->sizer->fixedDollar(0, 10000, 50);
    }
    
    public function testFixedDollarInvalidAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Fixed amount must be positive');
        
        $this->sizer->fixedDollar(100000, -1000, 50);
    }
    
    // ==================== Fixed Percent Tests ====================
    
    public function testFixedPercentNormalCase(): void
    {
        $result = $this->sizer->fixedPercent(
            portfolioValue: 100000,
            percent: 0.1,
            currentPrice: 50
        );
        
        $this->assertEquals(200, $result['shares']);
        $this->assertEquals(10000, $result['value']);
        $this->assertEqualsWithDelta(0.1, $result['percent'], 0.001);
        $this->assertEquals('fixed_percent', $result['method']);
    }
    
    public function testFixedPercent20Percent(): void
    {
        $result = $this->sizer->fixedPercent(
            portfolioValue: 50000,
            percent: 0.2,
            currentPrice: 100
        );
        
        $this->assertEquals(100, $result['shares']);
        $this->assertEquals(10000, $result['value']);
        $this->assertEquals(0.2, $result['percent']);
    }
    
    public function testFixedPercentSmallPercent(): void
    {
        $result = $this->sizer->fixedPercent(
            portfolioValue: 100000,
            percent: 0.01,
            currentPrice: 50
        );
        
        $this->assertEquals(20, $result['shares']);
        $this->assertEquals(1000, $result['value']);
    }
    
    public function testFixedPercentInvalidPercent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Percent must be between 0 and 1');
        
        $this->sizer->fixedPercent(100000, 1.5, 50);
    }
    
    public function testFixedPercentZeroPercent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->sizer->fixedPercent(100000, 0, 50);
    }
    
    // ==================== Kelly Criterion Tests ====================
    
    public function testKellyCriterionPositiveEdge(): void
    {
        $result = $this->sizer->kellyCriterion(
            portfolioValue: 100000,
            winProbability: 0.6,
            avgWin: 1.2,     // 20% avg win
            avgLoss: 0.9,    // 10% avg loss
            currentPrice: 50,
            fraction: 0.5    // Half Kelly
        );
        
        $this->assertGreaterThan(0, $result['shares']);
        $this->assertGreaterThan(0, $result['kelly_percent']);
        $this->assertEquals('kelly_criterion', $result['method']);
        $this->assertLessThanOrEqual(0.25, $result['percent']); // Capped at 25%
    }
    
    public function testKellyCriterionHighWinRate(): void
    {
        $result = $this->sizer->kellyCriterion(
            portfolioValue: 100000,
            winProbability: 0.7,
            avgWin: 1.3,
            avgLoss: 0.85,
            currentPrice: 50,
            fraction: 0.5
        );
        
        // Higher win rate + better win/loss ratio = larger Kelly %
        $this->assertGreaterThan(0.1, $result['kelly_percent']);
    }
    
    public function testKellyCriterionNegativeEdge(): void
    {
        $result = $this->sizer->kellyCriterion(
            portfolioValue: 100000,
            winProbability: 0.4,
            avgWin: 1.1,
            avgLoss: 0.9,
            currentPrice: 50,
            fraction: 0.5
        );
        
        // Negative edge should result in zero position
        $this->assertEquals(0, $result['shares']);
        $this->assertLessThanOrEqual(0, $result['kelly_percent']);
    }
    
    public function testKellyCriterionFullKelly(): void
    {
        $result = $this->sizer->kellyCriterion(
            portfolioValue: 100000,
            winProbability: 0.6,
            avgWin: 1.2,
            avgLoss: 0.9,
            currentPrice: 50,
            fraction: 1.0  // Full Kelly
        );
        
        $this->assertGreaterThan(0, $result['shares']);
        $this->assertLessThanOrEqual(0.25, $result['percent']); // Still capped
    }
    
    public function testKellyCriterionInvalidWinProbability(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Win probability must be between 0 and 1');
        
        $this->sizer->kellyCriterion(100000, 1.5, 1.2, 0.9, 50);
    }
    
    // ==================== Volatility-Based Tests ====================
    
    public function testVolatilityBasedNormalCase(): void
    {
        $result = $this->sizer->volatilityBased(
            portfolioValue: 100000,
            riskPercent: 0.01,  // Risk 1% of portfolio
            atr: 2.0,           // $2 ATR
            currentPrice: 50,
            atrMultiplier: 2.0
        );
        
        $this->assertGreaterThan(0, $result['shares']);
        $this->assertEquals(1000, $result['risk_amount']); // 1% of 100k
        $this->assertEquals(4.0, $result['stop_loss_distance']); // 2 * ATR
        $this->assertEquals(46.0, $result['stop_loss_price']); // 50 - 4
        $this->assertEquals('volatility_based', $result['method']);
    }
    
    public function testVolatilityBasedHighVolatility(): void
    {
        $result = $this->sizer->volatilityBased(
            portfolioValue: 100000,
            riskPercent: 0.01,
            atr: 5.0,  // High volatility
            currentPrice: 100,
            atrMultiplier: 2.0
        );
        
        // High volatility = smaller position
        $this->assertLessThan(200, $result['shares']);
        $this->assertEquals(10.0, $result['stop_loss_distance']);
    }
    
    public function testVolatilityBasedLowVolatility(): void
    {
        $result = $this->sizer->volatilityBased(
            portfolioValue: 100000,
            riskPercent: 0.01,
            atr: 0.5,  // Low volatility
            currentPrice: 50,
            atrMultiplier: 2.0
        );
        
        // Low volatility = larger position (up to 25% cap)
        $this->assertGreaterThan(0, $result['shares']);
        $this->assertLessThanOrEqual(500, $result['shares']); // Capped at 25% = 25k/50 = 500
    }
    
    public function testVolatilityBasedInvalidRiskPercent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Risk percent must be between 0 and 0.1');
        
        $this->sizer->volatilityBased(100000, 0.15, 2.0, 50);
    }
    
    public function testVolatilityBasedInvalidATR(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ATR must be positive');
        
        $this->sizer->volatilityBased(100000, 0.01, -1.0, 50);
    }
    
    // ==================== Risk Parity Tests ====================
    
    public function testRiskParityTwoAssets(): void
    {
        $assets = [
            ['symbol' => 'AAPL', 'volatility' => 0.02, 'price' => 150],
            ['symbol' => 'MSFT', 'volatility' => 0.01, 'price' => 300]
        ];
        
        $result = $this->sizer->riskParity(100000, $assets);
        
        $this->assertArrayHasKey('positions', $result);
        $this->assertArrayHasKey('total_value', $result);
        $this->assertEquals('risk_parity', $result['method']);
        
        // Lower volatility should get higher weight
        $msftWeight = $result['positions']['MSFT']['target_weight'];
        $aaplWeight = $result['positions']['AAPL']['target_weight'];
        $this->assertGreaterThan($aaplWeight, $msftWeight);
    }
    
    public function testRiskParityEqualVolatility(): void
    {
        $assets = [
            ['symbol' => 'AAPL', 'volatility' => 0.02, 'price' => 100],
            ['symbol' => 'MSFT', 'volatility' => 0.02, 'price' => 100],
            ['symbol' => 'GOOGL', 'volatility' => 0.02, 'price' => 100]
        ];
        
        $result = $this->sizer->riskParity(100000, $assets);
        
        // Equal volatility = equal weights (approximately)
        $weights = array_column($result['positions'], 'target_weight');
        $this->assertEqualsWithDelta(0.333, $weights[0], 0.01);
        $this->assertEqualsWithDelta(0.333, $weights[1], 0.01);
        $this->assertEqualsWithDelta(0.333, $weights[2], 0.01);
    }
    
    public function testRiskParityThreeAssetsMixedVolatility(): void
    {
        $assets = [
            ['symbol' => 'AAPL', 'volatility' => 0.03, 'price' => 150],
            ['symbol' => 'MSFT', 'volatility' => 0.015, 'price' => 300],
            ['symbol' => 'BND', 'volatility' => 0.005, 'price' => 80]  // Low vol bond
        ];
        
        $result = $this->sizer->riskParity(100000, $assets);
        
        // Bond should have highest weight (lowest volatility)
        $bndWeight = $result['positions']['BND']['target_weight'];
        $msftWeight = $result['positions']['MSFT']['target_weight'];
        $aaplWeight = $result['positions']['AAPL']['target_weight'];
        
        $this->assertGreaterThan($msftWeight, $bndWeight);
        $this->assertGreaterThan($aaplWeight, $msftWeight);
    }
    
    public function testRiskParityEmptyAssets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Assets array cannot be empty');
        
        $this->sizer->riskParity(100000, []);
    }
    
    public function testRiskParityMissingFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Each asset must have symbol, volatility, and price');
        
        $assets = [
            ['symbol' => 'AAPL', 'price' => 150]  // Missing volatility
        ];
        
        $this->sizer->riskParity(100000, $assets);
    }
    
    public function testRiskParityInvalidVolatility(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Volatility must be positive for AAPL');
        
        $assets = [
            ['symbol' => 'AAPL', 'volatility' => -0.01, 'price' => 150]
        ];
        
        $this->sizer->riskParity(100000, $assets);
    }
    
    // ==================== Margin-Aware Tests ====================
    
    public function testMaxPositionWithMarginNormalCase(): void
    {
        $result = $this->sizer->maxPositionWithMargin(
            portfolioValue: 100000,
            availableCash: 50000,
            marginRequirement: 0.5,  // 50% margin
            maxLeverage: 2.0,
            currentPrice: 100
        );
        
        $this->assertEquals(1000, $result['shares']); // 50k/0.5 = 100k / 100 = 1000
        $this->assertEquals(100000, $result['value']);
        $this->assertEquals(50000, $result['margin_used']);
        $this->assertEquals(1.0, $result['leverage']);
        $this->assertEquals('max_margin', $result['method']);
    }
    
    public function testMaxPositionWithMarginHighLeverage(): void
    {
        $result = $this->sizer->maxPositionWithMargin(
            portfolioValue: 100000,
            availableCash: 50000,
            marginRequirement: 0.3,  // 30% margin
            maxLeverage: 4.0,  // Can go up to 4x
            currentPrice: 100
        );
        
        // Limited by margin requirement, not leverage
        $maxValue = 50000 / 0.3;  // ~166,666
        $this->assertLessThanOrEqual(1666, $result['shares']);
    }
    
    public function testMaxPositionWithMarginLeverageLimit(): void
    {
        $result = $this->sizer->maxPositionWithMargin(
            portfolioValue: 100000,
            availableCash: 100000,
            marginRequirement: 0.1,  // Low margin requirement
            maxLeverage: 1.5,  // But limited leverage
            currentPrice: 100
        );
        
        // Limited by leverage, not margin
        $this->assertEquals(1500, $result['shares']); // 150k / 100
        $this->assertEquals(150000, $result['value']);
        $this->assertEquals(1.5, $result['leverage']);
    }
    
    public function testMaxPositionWithMarginInvalidMarginRequirement(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Margin requirement must be between 0 and 1');
        
        $this->sizer->maxPositionWithMargin(100000, 50000, 1.5, 2.0, 100);
    }
    
    public function testMaxPositionWithMarginInvalidLeverage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Max leverage must be >= 1');
        
        $this->sizer->maxPositionWithMargin(100000, 50000, 0.5, 0.5, 100);
    }
    
    // ==================== Edge Cases ====================
    
    public function testFractionalShares(): void
    {
        // All methods should floor shares to integers
        $result = $this->sizer->fixedDollar(100000, 10000, 333);
        $this->assertEquals(30, $result['shares']); // floor(10000/333)
        $this->assertIsInt($result['shares']);
    }
    
    public function testVerySmallPosition(): void
    {
        $result = $this->sizer->fixedPercent(100000, 0.001, 50);
        $this->assertEquals(2, $result['shares']);
        $this->assertEquals(100, $result['value']);
    }
    
    public function testHighPriceStock(): void
    {
        $result = $this->sizer->fixedDollar(100000, 10000, 5000);
        $this->assertEquals(2, $result['shares']);
        $this->assertEquals(10000, $result['value']);
    }
    
    public function testZeroSharesCalculated(): void
    {
        // Price too high for position size
        $result = $this->sizer->fixedDollar(100000, 100, 500);
        $this->assertEquals(0, $result['shares']);
        $this->assertEquals(0, $result['value']);
    }
}
