<?php

declare(strict_types=1);

namespace Tests\Crypto;

use App\Crypto\ETFPremiumDiscountTracker;
use App\Crypto\CryptoDataService;
use PHPUnit\Framework\TestCase;

/**
 * ETF Premium/Discount Tracker Test Suite
 * 
 * Tests premium/discount calculation and tracking for crypto ETFs.
 * Critical for identifying arbitrage opportunities:
 * - Premium: ETF trades above NAV (sell signal)
 * - Discount: ETF trades below NAV (buy signal)
 * 
 * @package Tests\Crypto
 */
class ETFPremiumDiscountTrackerTest extends TestCase
{
    private ETFPremiumDiscountTracker $tracker;
    private CryptoDataService $cryptoService;
    
    protected function setUp(): void
    {
        $this->cryptoService = new CryptoDataService();
        $this->tracker = new ETFPremiumDiscountTracker($this->cryptoService);
    }
    
    public function testItCalculatesPremium(): void
    {
        $result = $this->tracker->calculatePremiumDiscount(
            etfSymbol: 'BTCC.TO',
            marketPrice: 10.50,
            nav: 10.00
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('premium_discount_percent', $result);
        $this->assertArrayHasKey('premium_discount_amount', $result);
        $this->assertArrayHasKey('is_premium', $result);
        
        $this->assertEquals(5.0, $result['premium_discount_percent']);
        $this->assertEquals(0.50, $result['premium_discount_amount']);
        $this->assertTrue($result['is_premium']);
    }
    
    public function testItCalculatesDiscount(): void
    {
        $result = $this->tracker->calculatePremiumDiscount(
            etfSymbol: 'BTCC.TO',
            marketPrice: 9.50,
            nav: 10.00
        );
        
        $this->assertEquals(-5.0, $result['premium_discount_percent']);
        $this->assertEquals(-0.50, $result['premium_discount_amount']);
        $this->assertFalse($result['is_premium']);
    }
    
    public function testItTracksHistoricalPremiumDiscount(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.50, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.30, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 9.80, 10.00);
        
        $history = $this->tracker->getHistory('BTCC.TO', 7);
        
        $this->assertIsArray($history);
        $this->assertCount(3, $history);
        
        $this->assertEquals(5.0, $history[0]['premium_discount_percent']);
        $this->assertEquals(3.0, $history[1]['premium_discount_percent']);
        $this->assertEquals(-2.0, $history[2]['premium_discount_percent']);
    }
    
    public function testItCalculatesAveragePremiumDiscount(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.50, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.30, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 9.80, 10.00);
        
        $avg = $this->tracker->getAveragePremiumDiscount('BTCC.TO', 30);
        
        $this->assertIsFloat($avg);
        $this->assertEquals(2.0, $avg); // (5 + 3 - 2) / 3
    }
    
    public function testItIdentifiesExcessivePremium(): void
    {
        $result = $this->tracker->calculatePremiumDiscount('BTCC.TO', 11.00, 10.00);
        
        $isExcessive = $this->tracker->isExcessivePremium(
            $result['premium_discount_percent'],
            threshold: 2.0
        );
        
        $this->assertTrue($isExcessive); // 10% > 2%
    }
    
    public function testItIdentifiesExcessiveDiscount(): void
    {
        $result = $this->tracker->calculatePremiumDiscount('BTCC.TO', 9.00, 10.00);
        
        $isExcessive = $this->tracker->isExcessiveDiscount(
            $result['premium_discount_percent'],
            threshold: -2.0
        );
        
        $this->assertTrue($isExcessive); // -10% < -2%
    }
    
    public function testItGeneratesAlertForExcessivePremium(): void
    {
        $alert = $this->tracker->checkAlertConditions('BTCC.TO', 10.50, 10.00, threshold: 2.0);
        
        $this->assertIsArray($alert);
        $this->assertArrayHasKey('alert_type', $alert);
        $this->assertArrayHasKey('message', $alert);
        $this->assertArrayHasKey('severity', $alert);
        
        $this->assertEquals('EXCESSIVE_PREMIUM', $alert['alert_type']);
        $this->assertEquals('high', $alert['severity']);
    }
    
    public function testItGeneratesAlertForExcessiveDiscount(): void
    {
        $alert = $this->tracker->checkAlertConditions('BTCC.TO', 9.50, 10.00, threshold: 2.0);
        
        $this->assertEquals('EXCESSIVE_DISCOUNT', $alert['alert_type']);
        $this->assertEquals('high', $alert['severity']);
        $this->assertStringContainsString('discount', strtolower($alert['message']));
    }
    
    public function testItReturnsNullWhenNoAlert(): void
    {
        $alert = $this->tracker->checkAlertConditions('BTCC.TO', 10.10, 10.00, threshold: 2.0);
        
        $this->assertNull($alert); // 1% is within threshold
    }
    
    public function testItCalculatesPremiumVolatility(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.50, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.30, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 9.80, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.20, 10.00);
        
        $volatility = $this->tracker->getPremiumVolatility('BTCC.TO', 30);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThan(0, $volatility);
    }
    
    public function testItIdentifiesPremiumTrend(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 9.80, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.00, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.20, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.40, 10.00);
        
        $trend = $this->tracker->getPremiumTrend('BTCC.TO', 30);
        
        $this->assertIsString($trend);
        $this->assertEquals('widening', $trend); // Premium increasing
    }
    
    public function testItIdentifiesDiscountTrend(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.40, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.20, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.00, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 9.80, 10.00);
        
        $trend = $this->tracker->getPremiumTrend('BTCC.TO', 30);
        
        $this->assertEquals('narrowing', $trend); // Moving toward discount
    }
    
    public function testItCalculatesArbitrageOpportunity(): void
    {
        $opportunity = $this->tracker->evaluateArbitrageOpportunity('BTCC.TO', 9.00, 10.00);
        
        $this->assertIsArray($opportunity);
        $this->assertArrayHasKey('opportunity_score', $opportunity);
        $this->assertArrayHasKey('action', $opportunity);
        $this->assertArrayHasKey('expected_profit_percent', $opportunity);
        
        $this->assertEquals('BUY', $opportunity['action']); // Discount = buy opportunity
        $this->assertGreaterThan(0, $opportunity['opportunity_score']);
    }
    
    public function testItComparesMultipleETFs(): void
    {
        $comparison = $this->tracker->compareETFs([
            'BTCC.TO' => ['market_price' => 10.50, 'nav' => 10.00],
            'EBIT.TO' => ['market_price' => 9.50, 'nav' => 10.00],
            'BTCX.TO' => ['market_price' => 10.10, 'nav' => 10.00]
        ]);
        
        $this->assertIsArray($comparison);
        $this->assertCount(3, $comparison);
        
        // Should be sorted by opportunity (biggest discount first)
        $this->assertEquals('EBIT.TO', $comparison[0]['symbol']);
        $this->assertEquals(-5.0, $comparison[0]['premium_discount_percent']);
    }
    
    public function testItCalculatesBreakEvenPrice(): void
    {
        $breakeven = $this->tracker->calculateBreakEvenPrice(
            nav: 10.00,
            currentPremium: 5.0,
            fees: 0.25 // 0.25% trading fees
        );
        
        $this->assertIsFloat($breakeven);
        $this->assertGreaterThan(10.00, $breakeven);
    }
    
    public function testItTracksIntraDayPremiumChanges(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.50, 10.00);
        sleep(1);
        $this->tracker->recordSnapshot('BTCC.TO', 10.30, 10.00);
        sleep(1);
        $this->tracker->recordSnapshot('BTCC.TO', 10.40, 10.00);
        
        $changes = $this->tracker->getIntraDayChanges('BTCC.TO');
        
        $this->assertIsArray($changes);
        $this->assertArrayHasKey('max_premium', $changes);
        $this->assertArrayHasKey('min_premium', $changes);
        $this->assertArrayHasKey('current_premium', $changes);
        
        $this->assertEquals(5.0, $changes['max_premium']);
        $this->assertEquals(3.0, $changes['min_premium']);
    }
    
    public function testItGeneratesPremiumReport(): void
    {
        $this->tracker->recordSnapshot('BTCC.TO', 10.50, 10.00);
        $this->tracker->recordSnapshot('BTCC.TO', 10.30, 10.00);
        
        $report = $this->tracker->generateReport('BTCC.TO', 30);
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('symbol', $report);
        $this->assertArrayHasKey('current_premium_discount', $report);
        $this->assertArrayHasKey('average_premium_discount', $report);
        $this->assertArrayHasKey('premium_volatility', $report);
        $this->assertArrayHasKey('trend', $report);
        $this->assertArrayHasKey('recommendation', $report);
    }
}
