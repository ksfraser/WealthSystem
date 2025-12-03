<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\SectorAnalysisService;
use App\Services\MarketDataService;
use App\DAOs\SectorPerformanceDAO;
use App\Models\SectorPerformance;

/**
 * Sector Analysis Service Tests
 * 
 * Tests comprehensive sector classification, comparison, and performance analysis.
 */
class SectorAnalysisServiceTest extends TestCase
{
    private SectorAnalysisService $service;
    private $mockMarketDataService;
    private $mockSectorDAO;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->mockMarketDataService = $this->createMock(MarketDataService::class);
        $this->mockSectorDAO = $this->createMock(SectorPerformanceDAO::class);
        
        // Create service with mocks
        $this->service = new SectorAnalysisService(
            $this->mockSectorDAO,
            $this->mockMarketDataService
        );
    }
    
    /**
     * Test stock classification by GICS sector
     */
    public function testClassifyStockReturnsCorrectSectorInformation(): void
    {
        // Mock fundamentals data
        $this->mockMarketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with('AAPL')
            ->willReturn([
                'sector' => 'Information Technology',
                'industry' => 'Consumer Electronics',
                'market_cap' => 3000000000000,
                'country' => 'US'
            ]);
        
        $result = $this->service->classifyStock('AAPL');
        
        $this->assertEquals('AAPL', $result['symbol']);
        $this->assertEquals('Information Technology', $result['sector']);
        $this->assertEquals('Consumer Electronics', $result['industry']);
        $this->assertEquals('45', $result['sector_code']);
        $this->assertEquals('GICS', $result['classification']);
        $this->assertEquals(3000000000000, $result['market_cap']);
    }
    
    /**
     * Test classification handles unknown stocks gracefully
     */
    public function testClassifyStockHandlesUnknownSymbol(): void
    {
        $this->mockMarketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with('UNKNOWN')
            ->willReturn(null);
        
        $result = $this->service->classifyStock('UNKNOWN');
        
        $this->assertEquals('UNKNOWN', $result['symbol']);
        $this->assertEquals('Unknown', $result['sector']);
        $this->assertEquals('Unknown', $result['industry']);
        $this->assertNull($result['sector_code']);
    }
    
    /**
     * Test stock vs sector performance comparison
     */
    public function testCompareToSectorCalculatesRelativePerformance(): void
    {
        // Mock classification
        $this->mockMarketDataService
            ->expects($this->once())
            ->method('getFundamentals')
            ->with('NVDA')
            ->willReturn([
                'sector' => 'Information Technology',
                'industry' => 'Semiconductors',
                'market_cap' => 2000000000000
            ]);
        
        // Mock price data (stock up 30%)
        $this->mockMarketDataService
            ->expects($this->once())
            ->method('getHistoricalPrices')
            ->with('NVDA', '2024-01-01', '2024-03-31')
            ->willReturn([
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-02-01', 'close' => 115.00],
                ['date' => '2024-03-31', 'close' => 130.00]
            ]);
        
        // Mock sector performance (sector up 15%)
        $this->mockSectorDAO
            ->expects($this->once())
            ->method('getSectorPerformance')
            ->with('Information Technology', '2024-01-01', '2024-03-31')
            ->willReturn([
                'sector_name' => 'Information Technology',
                'change_percent' => 15.0,
                'constituents_count' => 75
            ]);
        
        $result = $this->service->compareToSector('NVDA', '2024-01-01', '2024-03-31');
        
        $this->assertEquals('NVDA', $result['symbol']);
        $this->assertEquals('Information Technology', $result['sector']);
        $this->assertEquals(30.0, $result['stock_performance']['total_return']);
        $this->assertEquals(15.0, $result['sector_performance']['return']);
        $this->assertEquals(15.0, $result['relative_performance']); // 30% - 15%
        $this->assertTrue($result['outperformance']);
    }
    
    /**
     * Test comparison when stock underperforms sector
     */
    public function testCompareToSectorDetectsUnderperformance(): void
    {
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn(['sector' => 'Energy', 'industry' => 'Oil & Gas']);
        
        // Stock down 10%
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([
                ['date' => '2024-01-01', 'close' => 50.00],
                ['date' => '2024-03-31', 'close' => 45.00]
            ]);
        
        // Sector up 5%
        $this->mockSectorDAO
            ->method('getSectorPerformance')
            ->willReturn([
                'sector_name' => 'Energy',
                'change_percent' => 5.0
            ]);
        
        $result = $this->service->compareToSector('XOM', '2024-01-01', '2024-03-31');
        
        $this->assertEquals(-10.0, $result['stock_performance']['total_return']);
        $this->assertEquals(5.0, $result['sector_performance']['return']);
        $this->assertEquals(-15.0, $result['relative_performance']); // -10% - 5%
        $this->assertFalse($result['outperformance']);
    }
    
    /**
     * Test sector rotation detection
     */
    public function testDetectSectorRotationIdentifiesLeadersAndLaggards(): void
    {
        // Mock sector performances
        $this->mockSectorDAO
            ->expects($this->exactly(11)) // 11 GICS sectors
            ->method('getSectorPerformance')
            ->willReturnCallback(function($sector, $startDate, $endDate) {
                $performances = [
                    'Information Technology' => ['change_percent' => 12.5],
                    'Communication Services' => ['change_percent' => 8.3],
                    'Consumer Discretionary' => ['change_percent' => 5.1],
                    'Health Care' => ['change_percent' => 3.2],
                    'Financials' => ['change_percent' => 1.5],
                    'Industrials' => ['change_percent' => 0.8],
                    'Materials' => ['change_percent' => -0.5],
                    'Real Estate' => ['change_percent' => -2.1],
                    'Consumer Staples' => ['change_percent' => -3.5],
                    'Utilities' => ['change_percent' => -5.2],
                    'Energy' => ['change_percent' => -7.8]
                ];
                
                return $performances[$sector] ?? null;
            });
        
        $result = $this->service->detectSectorRotation(30);
        
        $this->assertCount(11, $result['all_sectors']);
        $this->assertCount(3, $result['leaders']);
        $this->assertCount(3, $result['laggards']);
        
        // Check leaders
        $this->assertEquals('Information Technology', $result['leaders'][0]['sector']);
        $this->assertEquals(12.5, $result['leaders'][0]['return']);
        
        // Check laggards
        $this->assertEquals('Energy', $result['laggards'][2]['sector']);
        $this->assertEquals(-7.8, $result['laggards'][2]['return']);
        
        // Rotation detected (spread > 10%)
        $this->assertTrue($result['rotation_detected']);
    }
    
    /**
     * Test relative strength calculation
     */
    public function testCalculateRelativeStrengthRatio(): void
    {
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn(['sector' => 'Financials', 'industry' => 'Banks']);
        
        // Stock up 20%
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([
                ['date' => '2023-10-01', 'close' => 50.00],
                ['date' => '2024-01-01', 'close' => 60.00]
            ]);
        
        // Sector up 10%
        $this->mockSectorDAO
            ->method('getSectorPerformance')
            ->willReturn([
                'sector_name' => 'Financials',
                'change_percent' => 10.0
            ]);
        
        $result = $this->service->calculateRelativeStrength('JPM', 90);
        
        $this->assertEquals('JPM', $result['symbol']);
        $this->assertEquals('Financials', $result['sector']);
        $this->assertEquals(20.0, $result['stock_return']);
        $this->assertEquals(10.0, $result['sector_return']);
        $this->assertEquals(2.0, $result['relative_strength_ratio']); // 20/10 = 2.0
        $this->assertEquals('Significantly outperforming sector', $result['interpretation']);
        $this->assertTrue($result['outperforming']);
    }
    
    /**
     * Test relative strength when stock is in line with sector
     */
    public function testCalculateRelativeStrengthInLineWithSector(): void
    {
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn(['sector' => 'Materials', 'industry' => 'Chemicals']);
        
        // Stock up 8%
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([
                ['date' => '2023-10-01', 'close' => 100.00],
                ['date' => '2024-01-01', 'close' => 108.00]
            ]);
        
        // Sector up 8%
        $this->mockSectorDAO
            ->method('getSectorPerformance')
            ->willReturn([
                'sector_name' => 'Materials',
                'change_percent' => 8.0
            ]);
        
        $result = $this->service->calculateRelativeStrength('DOW', 90);
        
        $this->assertEquals(1.0, $result['relative_strength_ratio']); // 8/8 = 1.0
        $this->assertEquals('In line with sector', $result['interpretation']);
    }
    
    /**
     * Test sector ranking by performance
     */
    public function testRankSectorPerformanceOrdersByReturn(): void
    {
        $symbols = ['MSFT', 'GOOGL', 'META', 'NFLX'];
        
        $priceDataMap = [
            'MSFT' => [
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-03-31', 'close' => 125.00] // +25%
            ],
            'GOOGL' => [
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-03-31', 'close' => 115.00] // +15%
            ],
            'META' => [
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-03-31', 'close' => 140.00] // +40%
            ],
            'NFLX' => [
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-03-31', 'close' => 105.00] // +5%
            ]
        ];
        
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturnCallback(function($symbol) use ($priceDataMap) {
                return $priceDataMap[$symbol] ?? [];
            });
        
        $result = $this->service->rankSectorPerformance(
            'Information Technology',
            $symbols,
            '2024-01-01',
            '2024-03-31'
        );
        
        $this->assertEquals('Information Technology', $result['sector']);
        $this->assertEquals(4, $result['total_stocks']);
        
        // Check ranking order (META, MSFT, GOOGL, NFLX)
        $this->assertEquals('META', $result['rankings'][0]['symbol']);
        $this->assertEquals(1, $result['rankings'][0]['rank']);
        $this->assertEquals(40.0, $result['rankings'][0]['return']);
        
        $this->assertEquals('MSFT', $result['rankings'][1]['symbol']);
        $this->assertEquals(2, $result['rankings'][1]['rank']);
        
        $this->assertEquals('NFLX', $result['rankings'][3]['symbol']);
        $this->assertEquals(4, $result['rankings'][3]['rank']);
        $this->assertEquals(5.0, $result['rankings'][3]['return']);
    }
    
    /**
     * Test updating sector performance data
     */
    public function testUpdateSectorPerformanceSavesData(): void
    {
        $this->mockSectorDAO
            ->expects($this->once())
            ->method('save')
            ->willReturnCallback(function(SectorPerformance $sector) {
                $this->assertEquals('45', $sector->getSectorCode());
                $this->assertEquals('Information Technology', $sector->getSectorName());
                $this->assertEquals(105.5, $sector->getPerformanceValue());
                $this->assertEquals(5.5, $sector->getChangePercent());
                return true;
            });
        
        $result = $this->service->updateSectorPerformance(
            'Information Technology',
            105.5,
            5.5,
            0.28
        );
        
        $this->assertTrue($result);
    }
    
    /**
     * Test getting all GICS sectors
     */
    public function testGetAllSectorsReturnsCompleteList(): void
    {
        $sectors = $this->service->getAllSectors();
        
        $this->assertCount(11, $sectors);
        $this->assertArrayHasKey('10', $sectors);
        $this->assertEquals('Energy', $sectors['10']);
        $this->assertArrayHasKey('45', $sectors);
        $this->assertEquals('Information Technology', $sectors['45']);
    }
    
    /**
     * Test performance calculation includes volatility and drawdown
     */
    public function testCompareToSectorCalculatesVolatilityMetrics(): void
    {
        $this->mockMarketDataService
            ->method('getFundamentals')
            ->willReturn(['sector' => 'Energy', 'industry' => 'Oil & Gas']);
        
        // Volatile price movement
        $this->mockMarketDataService
            ->method('getHistoricalPrices')
            ->willReturn([
                ['date' => '2024-01-01', 'close' => 100.00],
                ['date' => '2024-01-08', 'close' => 110.00],
                ['date' => '2024-01-15', 'close' => 95.00],
                ['date' => '2024-01-22', 'close' => 105.00],
                ['date' => '2024-01-31', 'close' => 108.00]
            ]);
        
        $this->mockSectorDAO
            ->method('getSectorPerformance')
            ->willReturn(['sector_name' => 'Energy', 'change_percent' => 5.0]);
        
        $result = $this->service->compareToSector('XOM', '2024-01-01', '2024-01-31');
        
        $this->assertArrayHasKey('volatility', $result['stock_performance']);
        $this->assertArrayHasKey('max_drawdown', $result['stock_performance']);
        $this->assertGreaterThan(0, $result['stock_performance']['volatility']);
        $this->assertGreaterThan(0, $result['stock_performance']['max_drawdown']);
    }
}
