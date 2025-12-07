<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use PHPUnit\Framework\TestCase;
use WealthSystem\Backtesting\ShortSellingBacktestEngine;
use InvalidArgumentException;

/**
 * Tests for ShortSellingBacktestEngine
 * 
 * Covers long/short positions, margin, interest, liquidation
 */
class ShortSellingBacktestEngineTest extends TestCase
{
    private ShortSellingBacktestEngine $engine;
    
    protected function setUp(): void
    {
        $this->engine = new ShortSellingBacktestEngine([
            'initial_capital' => 100000,
            'commission_rate' => 0.001,
            'slippage_rate' => 0.0005,
            'margin_requirement' => 1.5,
            'short_interest_rate' => 0.03
        ]);
    }
    
    // ==================== Long Position Tests ====================
    
    public function testEnterLongPositionSuccess(): void
    {
        $result = $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('BUY', $result['action']);
        $this->assertEquals(100, $result['shares']);
        $this->assertGreaterThan(150.0, $result['price']); // Slippage
        $this->assertGreaterThan(0, $result['commission']);
        
        $state = $this->engine->getState();
        $this->assertEquals(100, $state['long_positions']['AAPL']['shares']);
    }
    
    public function testEnterLongPositionInsufficientCash(): void
    {
        $result = $this->engine->enterLongPosition('AAPL', 1000, 150.0, '2025-01-01');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient cash', $result['error']);
        $this->assertArrayHasKey('required', $result);
        $this->assertArrayHasKey('available', $result);
    }
    
    public function testEnterLongPositionAverageCostBasis(): void
    {
        // First purchase
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        
        // Second purchase at different price
        $this->engine->enterLongPosition('AAPL', 100, 160.0, '2025-01-02');
        
        $state = $this->engine->getState();
        $this->assertEquals(200, $state['long_positions']['AAPL']['shares']);
        
        // Cost basis should be average
        $costBasis = $state['long_positions']['AAPL']['cost_basis'];
        $this->assertGreaterThan(150, $costBasis);
        $this->assertLessThan(160, $costBasis);
    }
    
    public function testExitLongPositionSuccess(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitLongPosition('AAPL', 100, 160.0, '2025-01-02');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('SELL', $result['action']);
        $this->assertEquals(100, $result['shares']);
        $this->assertGreaterThan(0, $result['profit']); // Profitable trade
        $this->assertGreaterThan(0, $result['profit_percent']);
        
        $state = $this->engine->getState();
        $this->assertArrayNotHasKey('AAPL', $state['long_positions']);
    }
    
    public function testExitLongPositionPartial(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitLongPosition('AAPL', 50, 160.0, '2025-01-02');
        
        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['shares']);
        
        $state = $this->engine->getState();
        $this->assertEquals(50, $state['long_positions']['AAPL']['shares']);
    }
    
    public function testExitLongPositionNoPosition(): void
    {
        $result = $this->engine->exitLongPosition('AAPL', 100, 160.0, '2025-01-01');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('No long position to exit', $result['error']);
    }
    
    public function testExitLongPositionInsufficientShares(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitLongPosition('AAPL', 200, 160.0, '2025-01-02');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient shares', $result['error']);
    }
    
    public function testExitLongPositionLoss(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitLongPosition('AAPL', null, 140.0, '2025-01-02');
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(0, $result['profit']); // Loss
        $this->assertLessThan(0, $result['profit_percent']);
    }
    
    // ==================== Short Position Tests ====================
    
    public function testEnterShortPositionSuccess(): void
    {
        $result = $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('SHORT', $result['action']);
        $this->assertEquals(100, $result['shares']);
        $this->assertGreaterThan(0, $result['margin_required']);
        
        $state = $this->engine->getState();
        $this->assertEquals(100, $state['short_positions']['AAPL']['shares']);
        $this->assertGreaterThan(0, $state['margin_balance']);
    }
    
    public function testEnterShortPositionInsufficientMargin(): void
    {
        // Try to short too much
        $result = $this->engine->enterShortPosition('AAPL', 1000, 150.0, '2025-01-01');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Insufficient cash for margin', $result['error']);
        $this->assertArrayHasKey('required_margin', $result);
    }
    
    public function testEnterShortPositionMarginCalculation(): void
    {
        $result = $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        
        // Margin should be 150% of position value
        $positionValue = 100 * 150.0;
        $expectedMargin = $positionValue * 1.5;
        
        $this->assertEqualsWithDelta($expectedMargin, $result['margin_required'], 300); // Allow for slippage/commission
    }
    
    public function testExitShortPositionProfit(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitShortPosition('AAPL', null, 140.0, '2025-01-10');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('COVER', $result['action']);
        $this->assertGreaterThan(0, $result['profit']); // Price down = profit
        $this->assertGreaterThan(0, $result['profit_percent']);
        $this->assertGreaterThan(0, $result['short_interest']); // Interest charged
        
        $state = $this->engine->getState();
        $this->assertArrayNotHasKey('AAPL', $state['short_positions']);
    }
    
    public function testExitShortPositionLoss(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitShortPosition('AAPL', null, 160.0, '2025-01-10');
        
        $this->assertTrue($result['success']);
        $this->assertLessThan(0, $result['profit']); // Price up = loss
        $this->assertLessThan(0, $result['profit_percent']);
    }
    
    public function testExitShortPositionPartial(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        $result = $this->engine->exitShortPosition('AAPL', 50, 140.0, '2025-01-10');
        
        $this->assertTrue($result['success']);
        $this->assertEquals(50, $result['shares']);
        
        $state = $this->engine->getState();
        $this->assertEquals(50, $state['short_positions']['AAPL']['shares']);
    }
    
    public function testExitShortPositionNoPosition(): void
    {
        $result = $this->engine->exitShortPosition('AAPL', 100, 140.0, '2025-01-01');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('No short position to exit', $result['error']);
    }
    
    public function testShortInterestCalculation(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        
        // Hold for 30 days
        $result = $this->engine->exitShortPosition('AAPL', null, 150.0, '2025-01-31');
        
        // Interest = position_value * (0.03/365) * 30 days
        $expectedInterest = 15000 * (0.03 / 365) * 30;
        $this->assertEqualsWithDelta($expectedInterest, $result['short_interest'], 5);
    }
    
    // ==================== Margin Call Tests ====================
    
    public function testMarginCallDetection(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 100.0, '2025-01-01');
        
        // Price rises significantly
        $marginCalls = $this->engine->checkMarginRequirements(['AAPL' => 150.0], '2025-01-02');
        
        $this->assertNotEmpty($marginCalls);
        $this->assertEquals('AAPL', $marginCalls[0]['symbol']);
        $this->assertEquals('add_margin_or_liquidate', $marginCalls[0]['action_required']);
        $this->assertLessThan(0, $marginCalls[0]['unrealized_loss']);
    }
    
    public function testNoMarginCallWhenSafe(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 100.0, '2025-01-01');
        
        // Price stays same
        $marginCalls = $this->engine->checkMarginRequirements(['AAPL' => 100.0], '2025-01-02');
        
        $this->assertEmpty($marginCalls);
    }
    
    public function testForceLiquidation(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 100.0, '2025-01-01');
        
        $result = $this->engine->forceLiquidate('AAPL', 150.0, '2025-01-02');
        
        $this->assertTrue($result['success']);
        $this->assertEquals('FORCED_LIQUIDATION', $result['action']);
        $this->assertArrayHasKey('penalty', $result);
        
        $state = $this->engine->getState();
        $this->assertArrayNotHasKey('AAPL', $state['short_positions']);
    }
    
    // ==================== Portfolio Valuation Tests ====================
    
    public function testCalculatePortfolioValueLongOnly(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        
        $value = $this->engine->calculatePortfolioValue(['AAPL' => 160.0]);
        
        $this->assertEquals(16000, $value['long_value']);
        $this->assertEquals(0, $value['short_liability']);
        $this->assertGreaterThan(0, $value['cash']);
        $this->assertGreaterThan(100000, $value['net_worth']); // Started with 100k, now profitable
    }
    
    public function testCalculatePortfolioValueShortOnly(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        
        $value = $this->engine->calculatePortfolioValue(['AAPL' => 140.0]);
        
        $this->assertEquals(14000, $value['short_liability']);
        $this->assertGreaterThan(0, $value['margin_balance']);
        // Net worth should increase when short position profits
        $this->assertGreaterThan(100000, $value['net_worth']);
    }
    
    public function testCalculatePortfolioValueMixed(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $this->engine->enterShortPosition('MSFT', 50, 300.0, '2025-01-01');
        
        $value = $this->engine->calculatePortfolioValue([
            'AAPL' => 160.0,
            'MSFT' => 290.0
        ]);
        
        $this->assertGreaterThan(0, $value['long_value']);
        $this->assertGreaterThan(0, $value['short_liability']);
        $this->assertEquals(1, $value['long_positions_count']);
        $this->assertEquals(1, $value['short_positions_count']);
    }
    
    public function testCalculatePortfolioValueEmptyPortfolio(): void
    {
        $value = $this->engine->calculatePortfolioValue([]);
        
        $this->assertEquals(100000, $value['cash']);
        $this->assertEquals(0, $value['long_value']);
        $this->assertEquals(0, $value['short_liability']);
        $this->assertEquals(100000, $value['net_worth']);
    }
    
    // ==================== Summary Tests ====================
    
    public function testGetSummary(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $this->engine->enterShortPosition('MSFT', 50, 300.0, '2025-01-01');
        
        $summary = $this->engine->getSummary();
        
        $this->assertEquals(2, $summary['total_trades']);
        $this->assertEquals(1, $summary['long_trades']);
        $this->assertEquals(1, $summary['short_trades']);
        $this->assertArrayHasKey('current_positions', $summary);
    }
    
    public function testGetSummaryWithInterest(): void
    {
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        $this->engine->exitShortPosition('AAPL', null, 140.0, '2025-02-01');
        
        $summary = $this->engine->getSummary();
        
        $this->assertGreaterThan(0, $summary['short_interest_paid']);
    }
    
    // ==================== Edge Cases ====================
    
    public function testInvalidShares(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->engine->enterLongPosition('AAPL', 0, 150.0, '2025-01-01');
    }
    
    public function testInvalidPrice(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->engine->enterLongPosition('AAPL', 100, -150.0, '2025-01-01');
    }
    
    public function testMultiplePositionsSameSymbol(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        $this->engine->enterLongPosition('AAPL', 50, 160.0, '2025-01-02');
        
        $state = $this->engine->getState();
        $this->assertEquals(150, $state['long_positions']['AAPL']['shares']);
    }
    
    public function testExitAllPositions(): void
    {
        $this->engine->enterLongPosition('AAPL', 100, 150.0, '2025-01-01');
        
        // Exit with null shares = exit all
        $result = $this->engine->exitLongPosition('AAPL', null, 160.0, '2025-01-02');
        
        $this->assertTrue($result['success']);
        $this->assertEquals(100, $result['shares']);
    }
    
    public function testCommissionAndSlippage(): void
    {
        $result = $this->engine->enterLongPosition('AAPL', 100, 100.0, '2025-01-01');
        
        // Price should include slippage
        $this->assertGreaterThan(100.0, $result['price']);
        
        // Commission should be calculated
        $this->assertGreaterThan(0, $result['commission']);
        
        // Total cost = shares * price + commission
        $this->assertGreaterThan(10000, $result['total_cost']);
    }
    
    public function testMarginBalanceTracking(): void
    {
        $initialState = $this->engine->getState();
        $this->assertEquals(0, $initialState['margin_balance']);
        
        $this->engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
        
        $afterShort = $this->engine->getState();
        $this->assertGreaterThan(0, $afterShort['margin_balance']);
        
        $this->engine->exitShortPosition('AAPL', null, 140.0, '2025-01-10');
        
        $afterCover = $this->engine->getState();
        $this->assertEquals(0, $afterCover['margin_balance']);
    }
}
