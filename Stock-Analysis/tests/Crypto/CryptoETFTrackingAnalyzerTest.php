<?php

declare(strict_types=1);

namespace Tests\Crypto;

use App\Crypto\CryptoETFTrackingAnalyzer;
use App\Crypto\CryptoDataService;
use App\Enums\CryptoETFType;
use PHPUnit\Framework\TestCase;

/**
 * CryptoETF Tracking Analyzer Test Suite
 * 
 * Tests tracking error analysis for crypto ETFs.
 * Tracking error measures how well ETF follows underlying crypto.
 * 
 * @package Tests\Crypto
 */
class CryptoETFTrackingAnalyzerTest extends TestCase
{
    private CryptoETFTrackingAnalyzer $analyzer;
    private CryptoDataService $cryptoService;
    
    protected function setUp(): void
    {
        $this->cryptoService = new CryptoDataService();
        $this->analyzer = new CryptoETFTrackingAnalyzer($this->cryptoService);
    }
    
    public function testItCalculatesTrackingError(): void
    {
        $error = $this->analyzer->calculateTrackingError(
            etfSymbol: 'BTCC.TO',
            cryptoSymbol: 'BTC',
            days: 30
        );
        
        $this->assertIsArray($error);
        $this->assertArrayHasKey('tracking_error_percent', $error);
        $this->assertArrayHasKey('etf_return', $error);
        $this->assertArrayHasKey('crypto_return', $error);
        $this->assertArrayHasKey('correlation', $error);
    }
    
    public function testItDetectsAnomalousTracking(): void
    {
        $isAnomaly = $this->analyzer->isAnomalousTracking(
            etfType: CryptoETFType::FUTURES_BASED,
            trackingError: 12.5
        );
        
        $this->assertFalse($isAnomaly); // 12.5% within futures range (3-15%)
    }
    
    public function testItDetectsExcessiveTrackingError(): void
    {
        $isAnomaly = $this->analyzer->isAnomalousTracking(
            etfType: CryptoETFType::SPOT,
            trackingError: 5.0
        );
        
        $this->assertTrue($isAnomaly); // 5% exceeds spot range (0.5-2%)
    }
    
    public function testItComparesFuturesVsSpotTracking(): void
    {
        $comparison = $this->analyzer->compareETFTypes([
            'BTCC.TO' => ['type' => CryptoETFType::SPOT, 'crypto' => 'BTC'],
            'BITO' => ['type' => CryptoETFType::FUTURES_BASED, 'crypto' => 'BTC']
        ], days: 30);
        
        $this->assertIsArray($comparison);
        $this->assertCount(2, $comparison);
    }
    
    public function testItCalculatesRollingTrackingError(): void
    {
        $rolling = $this->analyzer->calculateRollingTrackingError(
            etfSymbol: 'BTCC.TO',
            cryptoSymbol: 'BTC',
            windowDays: 7,
            totalDays: 30
        );
        
        $this->assertIsArray($rolling);
        $this->assertGreaterThan(0, count($rolling));
    }
    
    public function testItIdentifiesTrackingTrend(): void
    {
        $trend = $this->analyzer->getTrackingTrend('BTCC.TO', 'BTC', 90);
        
        $this->assertIsString($trend);
        $this->assertContains($trend, ['improving', 'deteriorating', 'stable']);
    }
    
    public function testItGeneratesTrackingAlert(): void
    {
        $alert = $this->analyzer->checkTrackingAlert(
            etfSymbol: 'BTCC.TO',
            etfType: CryptoETFType::SPOT,
            cryptoSymbol: 'BTC',
            threshold: 2.0
        );
        
        if ($alert !== null) {
            $this->assertArrayHasKey('alert_type', $alert);
            $this->assertArrayHasKey('tracking_error', $alert);
            $this->assertArrayHasKey('severity', $alert);
        }
    }
    
    public function testItCalculatesCorrelation(): void
    {
        $correlation = $this->analyzer->calculateCorrelation('BTCC.TO', 'BTC', 30);
        
        $this->assertIsFloat($correlation);
        $this->assertGreaterThanOrEqual(-1.0, $correlation);
        $this->assertLessThanOrEqual(1.0, $correlation);
    }
    
    public function testItCalculatesBeta(): void
    {
        $beta = $this->analyzer->calculateBeta('BTCC.TO', 'BTC', 30);
        
        $this->assertIsFloat($beta);
        // Beta typically close to 1.0 for crypto ETFs, but can vary
        $this->assertGreaterThan(-2.0, $beta);
        $this->assertLessThan(2.0, $beta);
    }
    
    public function testItGeneratesTrackingReport(): void
    {
        $report = $this->analyzer->generateTrackingReport(
            etfSymbol: 'BTCC.TO',
            etfType: CryptoETFType::SPOT,
            cryptoSymbol: 'BTC',
            days: 30
        );
        
        $this->assertIsArray($report);
        $this->assertArrayHasKey('etf_symbol', $report);
        $this->assertArrayHasKey('tracking_error', $report);
        $this->assertArrayHasKey('quality_rating', $report);
        $this->assertArrayHasKey('recommendation', $report);
    }
}
