<?php

declare(strict_types=1);

namespace Tests\Services\Trading\Strategies;

use App\Services\Trading\Strategies\CryptoETFArbitrageStrategy;
use App\Crypto\CryptoDataService;
use App\Crypto\ETFPremiumDiscountTracker;
use PHPUnit\Framework\TestCase;

/**
 * Crypto ETF Arbitrage Strategy Test Suite
 * 
 * Tests arbitrage strategy for crypto ETFs:
 * - Buy when ETF trades at discount to NAV
 * - Sell when premium narrows or reverses
 * - Confidence scoring based on premium/discount magnitude
 * 
 * @package Tests\Services\Trading\Strategies
 */
class CryptoETFArbitrageStrategyTest extends TestCase
{
    private CryptoETFArbitrageStrategy $strategy;
    private CryptoDataService $cryptoService;
    private ETFPremiumDiscountTracker $tracker;
    
    protected function setUp(): void
    {
        $this->cryptoService = new CryptoDataService();
        $this->tracker = new ETFPremiumDiscountTracker($this->cryptoService);
        $this->strategy = new CryptoETFArbitrageStrategy($this->tracker);
    }
    
    public function testItGeneratesBuySignalOnDiscount(): void
    {
        $signal = $this->strategy->analyze([
            'etf_symbol' => 'BTCC.TO',
            'market_price' => 9.00,
            'nav' => 10.00,
            'premium_discount_percent' => -10.0
        ]);
        
        $this->assertEquals('BUY', $signal['action']);
        $this->assertGreaterThan(50, $signal['confidence']);
    }
    
    public function testItGeneratesSellSignalOnPremium(): void
    {
        $signal = $this->strategy->analyze([
            'etf_symbol' => 'BTCC.TO',
            'market_price' => 11.00,
            'nav' => 10.00,
            'premium_discount_percent' => 10.0
        ]);
        
        $this->assertEquals('SELL', $signal['action']);
        $this->assertGreaterThan(50, $signal['confidence']);
    }
    
    public function testItGeneratesHoldSignalOnNeutral(): void
    {
        $signal = $this->strategy->analyze([
            'etf_symbol' => 'BTCC.TO',
            'market_price' => 10.10,
            'nav' => 10.00,
            'premium_discount_percent' => 1.0
        ]);
        
        $this->assertEquals('HOLD', $signal['action']);
    }
    
    public function testItCalculatesConfidenceScore(): void
    {
        $confidence = $this->strategy->calculateConfidence(-8.0);
        
        $this->assertIsInt($confidence);
        $this->assertGreaterThan(50, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }
    
    public function testItCalculatesEntryPrice(): void
    {
        $entry = $this->strategy->calculateEntryPrice(
            nav: 10.00,
            targetDiscount: -5.0,
            fees: 0.25
        );
        
        $this->assertIsFloat($entry);
        $this->assertLessThan(10.00, $entry);
    }
    
    public function testItCalculatesExitPrice(): void
    {
        $exit = $this->strategy->calculateExitPrice(
            nav: 10.00,
            targetPremium: 2.0,
            fees: 0.25
        );
        
        $this->assertIsFloat($exit);
        $this->assertGreaterThan(10.00, $exit);
    }
    
    public function testItCalculatesExpectedProfit(): void
    {
        $profit = $this->strategy->calculateExpectedProfit(
            entryPrice: 9.50,
            exitPrice: 10.20,
            fees: 0.25
        );
        
        $this->assertIsArray($profit);
        $this->assertArrayHasKey('profit_percent', $profit);
        $this->assertArrayHasKey('profit_after_fees', $profit);
        $this->assertGreaterThan(0, $profit['profit_percent']);
    }
    
    public function testItIdentifiesRiskLevel(): void
    {
        $lowRisk = $this->strategy->assessRisk(-10.0, 0.95);
        $this->assertEquals('low', $lowRisk);
        
        $highRisk = $this->strategy->assessRisk(-3.0, 0.70);
        $this->assertEquals('high', $highRisk);
    }
    
    public function testItGeneratesBacktestableSignals(): void
    {
        $signals = $this->strategy->generateBacktestSignals([
            ['date' => '2024-01-01', 'market_price' => 9.00, 'nav' => 10.00],
            ['date' => '2024-01-02', 'market_price' => 9.50, 'nav' => 10.00],
            ['date' => '2024-01-03', 'market_price' => 10.50, 'nav' => 10.00]
        ]);
        
        $this->assertIsArray($signals);
        $this->assertGreaterThan(0, count($signals));
    }
    
    public function testItValidatesMinimumDiscount(): void
    {
        $isValid = $this->strategy->meetsEntryThreshold(-2.5);
        $this->assertFalse($isValid); // Below 3% threshold
        
        $isValid = $this->strategy->meetsEntryThreshold(-5.0);
        $this->assertTrue($isValid); // Above 3% threshold
    }
    
    public function testItValidatesExitConditions(): void
    {
        $shouldExit = $this->strategy->shouldExit(
            entryDiscount: -8.0,
            currentPremiumDiscount: 1.0
        );
        
        $this->assertTrue($shouldExit); // Moved from -8% to +1%
    }
    
    public function testItCalculatesPositionSize(): void
    {
        $size = $this->strategy->calculatePositionSize(
            portfolioValue: 100000,
            confidence: 75,
            riskPercent: 2.0
        );
        
        $this->assertIsFloat($size);
        $this->assertGreaterThan(0, $size);
        $this->assertLessThanOrEqual(100000, $size);
    }
    
    public function testItGeneratesStrategyReport(): void
    {
        $report = $this->strategy->generateReport([
            ['date' => '2024-01-01', 'action' => 'BUY', 'price' => 9.00],
            ['date' => '2024-01-05', 'action' => 'SELL', 'price' => 10.20]
        ]);
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('total_trades', $report);
        $this->assertArrayHasKey('win_rate', $report);
        $this->assertArrayHasKey('average_return', $report);
    }
    
    public function testItIntegratesWithBacktestingEngine(): void
    {
        $config = $this->strategy->getBacktestConfig();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('strategy_name', $config);
        $this->assertArrayHasKey('parameters', $config);
        $this->assertEquals('CryptoETFArbitrage', $config['strategy_name']);
    }
}
