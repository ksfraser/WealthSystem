<?php

declare(strict_types=1);

namespace Tests\Crypto;

use App\Crypto\CryptoETFAlertEngine;
use App\Crypto\CryptoDataService;
use PHPUnit\Framework\TestCase;

/**
 * CryptoETF Alert Engine Test Suite
 * 
 * Tests 24/7 crypto monitoring and alert generation.
 * Critical for managing risk during extended hours:
 * - Overnight crypto moves (market closed 4PM-9:30AM)
 * - Weekend monitoring (Fri 4PM - Mon 9:30AM)
 * - Pre-market gap predictions
 * 
 * @package Tests\Crypto
 */
class CryptoETFAlertEngineTest extends TestCase
{
    private CryptoETFAlertEngine $alertEngine;
    private CryptoDataService $cryptoService;
    
    protected function setUp(): void
    {
        $this->cryptoService = new CryptoDataService();
        $this->alertEngine = new CryptoETFAlertEngine($this->cryptoService);
    }
    
    public function testItDetectsOvernightCryptoMove(): void
    {
        $alert = $this->alertEngine->checkOvernightMove(
            cryptoSymbol: 'BTC',
            lastClosePrice: 45000,
            currentPrice: 47000,
            threshold: 2.0
        );
        
        $this->assertIsArray($alert);
        $this->assertArrayHasKey('alert_type', $alert);
        $this->assertArrayHasKey('change_percent', $alert);
        $this->assertArrayHasKey('severity', $alert);
        
        $this->assertEquals('OVERNIGHT_MOVE', $alert['alert_type']);
        $this->assertGreaterThan(2.0, abs($alert['change_percent']));
    }
    
    public function testItCalculatesExpectedETFOpeningPrice(): void
    {
        $expected = $this->alertEngine->calculateExpectedETFOpen(
            etfSymbol: 'BTCC.TO',
            lastETFClose: 10.00,
            cryptoLastClose: 45000,
            cryptoCurrent: 47000
        );
        
        $this->assertIsArray($expected);
        $this->assertArrayHasKey('expected_open_price', $expected);
        $this->assertArrayHasKey('expected_gap_percent', $expected);
        $this->assertArrayHasKey('crypto_change_percent', $expected);
        
        $this->assertGreaterThan(10.00, $expected['expected_open_price']);
    }
    
    public function testItGeneratesPreMarketAlert(): void
    {
        $alert = $this->alertEngine->generatePreMarketAlert(
            etfSymbol: 'BTCC.TO',
            cryptoSymbol: 'BTC',
            threshold: 3.0
        );
        
        if ($alert !== null) {
            $this->assertIsArray($alert);
            $this->assertArrayHasKey('alert_type', $alert);
            $this->assertArrayHasKey('message', $alert);
            $this->assertArrayHasKey('expected_gap', $alert);
        } else {
            $this->assertNull($alert); // No alert if movement under threshold
        }
    }
    
    public function testItDetectsWeekendMove(): void
    {
        $alert = $this->alertEngine->checkWeekendMove(
            cryptoSymbol: 'BTC',
            fridayClose: 45000,
            mondayPremarket: 48000,
            threshold: 5.0
        );
        
        $this->assertIsArray($alert);
        $this->assertEquals('WEEKEND_MOVE', $alert['alert_type']);
        $this->assertArrayHasKey('change_percent', $alert);
        $this->assertArrayHasKey('severity', $alert);
    }
    
    public function testItTracksMarketHours(): void
    {
        $status = $this->alertEngine->getMarketStatus();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('is_market_open', $status);
        $this->assertArrayHasKey('hours_until_open', $status);
        $this->assertArrayHasKey('hours_until_close', $status);
        $this->assertIsBool($status['is_market_open']);
    }
    
    public function testItCalculatesGapRisk(): void
    {
        $risk = $this->alertEngine->calculateGapRisk(
            cryptoSymbol: 'BTC',
            etfSymbol: 'BTCC.TO',
            currentCryptoPrice: 47000,
            lastETFClose: 10.00
        );
        
        $this->assertIsArray($risk);
        $this->assertArrayHasKey('risk_level', $risk);
        $this->assertArrayHasKey('expected_gap_percent', $risk);
        $this->assertArrayHasKey('recommendation', $risk);
        $this->assertContains($risk['risk_level'], ['low', 'medium', 'high']);
    }
    
    public function testItMonitorsMultipleCryptoETFs(): void
    {
        $alerts = $this->alertEngine->monitorMultipleETFs([
            'BTCC.TO' => ['crypto' => 'BTC', 'last_close' => 10.00],
            'EBIT.TO' => ['crypto' => 'BTC', 'last_close' => 9.50]
        ], threshold: 3.0);
        
        $this->assertIsArray($alerts);
        foreach ($alerts as $alert) {
            $this->assertArrayHasKey('etf_symbol', $alert);
            $this->assertArrayHasKey('alert_type', $alert);
        }
    }
    
    public function testItGeneratesHighSeverityAlert(): void
    {
        $alert = $this->alertEngine->checkOvernightMove(
            cryptoSymbol: 'BTC',
            lastClosePrice: 45000,
            currentPrice: 50000, // 11% move
            threshold: 2.0
        );
        
        $this->assertEquals('high', $alert['severity']);
        $this->assertGreaterThan(10.0, abs($alert['change_percent']));
    }
    
    public function testItGeneratesMediumSeverityAlert(): void
    {
        $alert = $this->alertEngine->checkOvernightMove(
            cryptoSymbol: 'BTC',
            lastClosePrice: 45000,
            currentPrice: 46800, // 4% move
            threshold: 2.0
        );
        
        $this->assertEquals('medium', $alert['severity']);
    }
    
    public function testItCalculatesTimeUntilMarketOpen(): void
    {
        $time = $this->alertEngine->getTimeUntilMarketOpen();
        
        $this->assertIsArray($time);
        $this->assertArrayHasKey('hours', $time);
        $this->assertArrayHasKey('minutes', $time);
        $this->assertArrayHasKey('next_open_time', $time);
    }
    
    public function testItIdentifiesExtendedHoursPeriod(): void
    {
        $period = $this->alertEngine->getExtendedHoursPeriod();
        
        $this->assertIsString($period);
        $this->assertContains($period, [
            'pre_market',
            'market_hours',
            'after_hours',
            'weekend'
        ]);
    }
    
    public function testItGeneratesAlertHistory(): void
    {
        $this->alertEngine->checkOvernightMove('BTC', 45000, 47000, 2.0);
        $this->alertEngine->checkOvernightMove('BTC', 47000, 48000, 2.0);
        
        $history = $this->alertEngine->getAlertHistory('BTC', 24);
        
        $this->assertIsArray($history);
        $this->assertGreaterThan(0, count($history));
    }
    
    public function testItCalculatesAveragOvernightVolatility(): void
    {
        $volatility = $this->alertEngine->calculateOvernightVolatility('BTC', 30);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThanOrEqual(0, $volatility);
    }
}
