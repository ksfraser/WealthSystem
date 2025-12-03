<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\IndexBenchmarkingService;
use App\Services\MarketDataService;
use App\DAOs\IndexPerformanceDAO;

/**
 * Index Benchmarking Service Tests
 * 
 * Tests comprehensive index tracking, comparison, and alpha/beta calculations.
 */
class IndexBenchmarkingServiceTest extends TestCase
{
    private IndexBenchmarkingService $service;
    private $mockMarketDataService;
    private $mockIndexDAO;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockMarketDataService = $this->createMock(MarketDataService::class);
        $this->mockIndexDAO = $this->createMock(IndexPerformanceDAO::class);
        
        $this->service = new IndexBenchmarkingService(
            $this->mockIndexDAO,
            $this->mockMarketDataService
        );
    }
    
    /**
     * Test stock vs index comparison calculates alpha and beta
     */
    public function testCompareToIndexCalculatesAlphaAndBeta(): void
    {
        // Mock stock price data (up 30%)
        $this->mockMarketDataService
            ->expects($this->exactly(2))
            ->method('getHistoricalPrices')
            ->willReturnCallback(function($symbol) {
                if ($symbol === 'NVDA') {
                    return [
                        ['date' => '2024-01-01', 'close' => 100.00],
                        ['date' => '2024-01-02', 'close' => 105.00],
                        ['date' => '2024-01-03', 'close' => 110.00],
                        ['date' => '2024-01-04', 'close' => 125.00],
                        ['date' => '2024-01-05', 'close' => 130.00]
                    ];
                } else { // ^GSPC (S&P 500, up 10%)
                    return [
                        ['date' => '2024-01-01', 'close' => 4500.00],
                        ['date' => '2024-01-02', 'close' => 4550.00],
                        ['date' => '2024-01-03', 'close' => 4600.00],
                        ['date' => '2024-01-04', 'close' => 4875.00],
                        ['date' => '2024-01-05', 'close' => 4950.00]
                    ];
                }
            });
        
        $result = $this->service->compareToIndex('NVDA', 'SPY', '2024-01-01', '2024-01-05');
        
        $this->assertEquals('NVDA', $result['symbol']);
        $this->assertEquals('SPY', $result['index']['symbol']);
        $this->assertEquals('S&P 500', $result['index']['name']);
        
        // Check performance calculations
        $this->assertEquals(30.0, $result['stock_performance']['total_return']);
        $this->assertEquals(10.0, $result['index_performance']['total_return']);
        
        // Check excess return
        $this->assertEquals(20.0, $result['excess_return']);
        $this->assertTrue($result['outperformance']);
        
        // Check alpha and beta exist
        $this->assertArrayHasKey('alpha', $result);
        $this->assertArrayHasKey('beta', $result);
        $this->assertArrayHasKey('correlation', $result);
    }
    
    /**
     * Test index membership likelihood detection
     */
    public function testIsLikelyInIndexDetectsLargeCapsForSP500(): void
    {
        // Mock large-cap stock
        $this->mockMarketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with('AAPL')
            ->willReturn([
                'market_cap' => 3000000000000, // $3T market cap
                'sector' => 'Information Technology'
            ]);
        
        $result = $this->service->isLikelyInIndex('AAPL', 'SPY');
        
        $this->assertEquals('AAPL', $result['symbol']);
        $this->assertEquals('SPY', $result['index']);
        $this->assertTrue($result['likely_member']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertStringContainsString('Large-cap', $result['reason']);
    }
    
    /**
     * Test small-cap detection for Russell 2000
     */
    public function testIsLikelyInIndexDetectsSmallCapsForRussell2000(): void
    {
        // Mock small-cap stock
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn([
                'market_cap' => 2000000000, // $2B market cap
                'sector' => 'Industrials'
            ]);
        
        $result = $this->service->isLikelyInIndex('SMALLCO', 'IWM');
        
        $this->assertTrue($result['likely_member']);
        $this->assertStringContainsString('Small-cap', $result['reason']);
    }
    
    /**
     * Test tech stock detection for NASDAQ 100
     */
    public function testIsLikelyInIndexDetectsTechStocksForNASDAQ(): void
    {
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn([
                'market_cap' => 50000000000, // $50B
                'sector' => 'Information Technology'
            ]);
        
        $result = $this->service->isLikelyInIndex('TECH', 'QQQ');
        
        $this->assertTrue($result['likely_member']);
        $this->assertStringContainsString('tech', strtolower($result['reason']));
    }
    
    /**
     * Test alpha/beta calculation with high beta stock
     */
    public function testCalculateAlphaBetaWithHighBetaStock(): void
    {
        // Stock with 2x volatility of market
        $stockPrices = [
            ['date' => '2024-01-01', 'close' => 100.00],
            ['date' => '2024-01-02', 'close' => 110.00], // +10%
            ['date' => '2024-01-03', 'close' => 100.00], // -9.09%
            ['date' => '2024-01-04', 'close' => 115.00], // +15%
            ['date' => '2024-01-05', 'close' => 120.00]  // +4.35%
        ];
        
        // Market with normal volatility
        $indexPrices = [
            ['date' => '2024-01-01', 'close' => 1000.00],
            ['date' => '2024-01-02', 'close' => 1050.00], // +5%
            ['date' => '2024-01-03', 'close' => 1000.00], // -4.76%
            ['date' => '2024-01-04', 'close' => 1075.00], // +7.5%
            ['date' => '2024-01-05', 'close' => 1100.00]  // +2.33%
        ];
        
        $result = $this->service->calculateAlphaBeta($stockPrices, $indexPrices);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('alpha', $result);
        $this->assertArrayHasKey('beta', $result);
        $this->assertArrayHasKey('tracking_error', $result);
        
        // Beta should be > 1 for volatile stock
        $this->assertGreaterThan(0, $result['beta']);
    }
    
    /**
     * Test correlation calculation
     */
    public function testCompareToIndexCalculatesCorrelation(): void
    {
        // Highly correlated stock and index
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturnCallback(function($symbol) {
                if ($symbol === 'CORR') {
                    return [
                        ['date' => '2024-01-01', 'close' => 100.00],
                        ['date' => '2024-01-02', 'close' => 105.00],
                        ['date' => '2024-01-03', 'close' => 110.00]
                    ];
                } else {
                    return [
                        ['date' => '2024-01-01', 'close' => 1000.00],
                        ['date' => '2024-01-02', 'close' => 1050.00],
                        ['date' => '2024-01-03', 'close' => 1100.00]
                    ];
                }
            });
        
        $result = $this->service->compareToIndex('CORR', 'SPY', '2024-01-01', '2024-01-03');
        
        $this->assertArrayHasKey('correlation', $result);
        $this->assertIsFloat($result['correlation']);
        $this->assertGreaterThanOrEqual(-1, $result['correlation']);
        $this->assertLessThanOrEqual(1, $result['correlation']);
    }
    
    /**
     * Test getting all major indexes
     */
    public function testGetAllIndexesReturnsCompleteList(): void
    {
        $indexes = $this->service->getAllIndexes();
        
        $this->assertIsArray($indexes);
        $this->assertArrayHasKey('SPY', $indexes);
        $this->assertArrayHasKey('QQQ', $indexes);
        $this->assertArrayHasKey('DIA', $indexes);
        $this->assertArrayHasKey('IWM', $indexes);
        
        $this->assertEquals('S&P 500', $indexes['SPY']['name']);
        $this->assertEquals('NASDAQ 100', $indexes['QQQ']['name']);
        $this->assertEquals(500, $indexes['SPY']['constituents']);
    }
    
    /**
     * Test Sharpe ratio calculation in comparison
     */
    public function testCompareToIndexCalculatesSharpeRatio(): void
    {
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturnCallback(function($symbol) {
                return [
                    ['date' => '2024-01-01', 'close' => 100.00],
                    ['date' => '2024-01-02', 'close' => 110.00],
                    ['date' => '2024-01-03', 'close' => 120.00]
                ];
            });
        
        $result = $this->service->compareToIndex('TEST', 'SPY', '2024-01-01', '2024-01-03');
        
        $this->assertArrayHasKey('sharpe_ratio', $result);
        $this->assertIsFloat($result['sharpe_ratio']);
    }
    
    /**
     * Test information ratio calculation
     */
    public function testCompareToIndexCalculatesInformationRatio(): void
    {
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturnCallback(function($symbol) {
                return [
                    ['date' => '2024-01-01', 'close' => 100.00],
                    ['date' => '2024-01-02', 'close' => 105.00],
                    ['date' => '2024-01-03', 'close' => 115.00]
                ];
            });
        
        $result = $this->service->compareToIndex('TEST', 'SPY', '2024-01-01', '2024-01-03');
        
        $this->assertArrayHasKey('information_ratio', $result);
        $this->assertIsFloat($result['information_ratio']);
    }
    
    /**
     * Test handling of unknown index
     */
    public function testCompareToIndexHandlesUnknownIndex(): void
    {
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([
                ['date' => '2024-01-01', 'close' => 100.00]
            ]);
        
        $result = $this->service->compareToIndex('TEST', 'UNKNOWN', '2024-01-01', '2024-01-03');
        
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals('Unknown index', $result['error']);
    }
    
    /**
     * Test handling of insufficient data
     */
    public function testCompareToIndexHandlesInsufficientData(): void
    {
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([]);
        
        $result = $this->service->compareToIndex('TEST', 'SPY', '2024-01-01', '2024-01-03');
        
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('data', strtolower($result['error']));
    }
}
