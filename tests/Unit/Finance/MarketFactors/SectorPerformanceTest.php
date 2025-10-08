<?php

namespace Tests\Unit\Finance\MarketFactors;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\MarketFactors\Entities\SectorPerformance;

/**
 * Unit tests for SectorPerformance entity
 */
class SectorPerformanceTest extends TestBaseSimple
{
    /**
     * Test SectorPerformance creation
     */
    public function testSectorPerformanceCreation(): void
    {
        $topStocks = [
            ['symbol' => 'AAPL', 'weight' => 5.2],
            ['symbol' => 'MSFT', 'weight' => 4.8]
        ];
        
        $sector = new SectorPerformance(
            'XLK',
            'Technology',
            2.5,
            1.8,
            2.1,
            $topStocks,
            22.5,
            'GICS'
        );
        
        $this->assertEquals('XLK', $sector->getSymbol());
        $this->assertEquals('Technology', $sector->getSectorName());
        $this->assertEquals('sector', $sector->getType());
        $this->assertEquals(2.5, $sector->getValue());
        $this->assertEquals(1.8, $sector->getChange());
        $this->assertEquals(2.1, $sector->getChangePercent());
        $this->assertEquals($topStocks, $sector->getTopStocks());
        $this->assertEquals(22.5, $sector->getMarketCapWeight());
        $this->assertEquals('GICS', $sector->getClassification());
    }

    /**
     * Test relative performance calculation
     */
    public function testRelativePerformance(): void
    {
        $sector = new SectorPerformance('XLE', 'Energy', 0.0, 0.0, 3.2);
        
        $marketPerformance = 1.5;
        $relativePerformance = $sector->getRelativePerformance($marketPerformance);
        
    $this->assertEqualsWithDelta(1.7, $relativePerformance, 0.00001); // 3.2 - 1.5 = 1.7
    }

    /**
     * Test outperformance check
     */
    public function testIsOutperforming(): void
    {
        $outperformingSector = new SectorPerformance('XLK', 'Technology', 0.0, 0.0, 3.0);
        $underperformingSector = new SectorPerformance('XLF', 'Financials', 0.0, 0.0, 0.5);
        
        $marketPerformance = 2.0;
        
        $this->assertTrue($outperformingSector->isOutperforming($marketPerformance));
        $this->assertFalse($underperformingSector->isOutperforming($marketPerformance));
    }

    /**
     * Test top stocks management
     */
    public function testTopStocksManagement(): void
    {
        $sector = new SectorPerformance('XLV', 'Healthcare', 0.0);
        
        $this->assertEquals([], $sector->getTopStocks());
        
        // Add individual stock
        $sector->addTopStock('JNJ', 3.5);
        $topStocks = $sector->getTopStocks();
        
        $this->assertCount(1, $topStocks);
        $this->assertEquals('JNJ', $topStocks[0]['symbol']);
        $this->assertEquals(3.5, $topStocks[0]['weight']);
        
        // Set multiple stocks
        $newStocks = [
            ['symbol' => 'PFE', 'weight' => 3.2],
            ['symbol' => 'UNH', 'weight' => 4.1]
        ];
        $sector->setTopStocks($newStocks);
        
        $this->assertEquals($newStocks, $sector->getTopStocks());
    }

    /**
     * Test GICS sectors list
     */
    public function testGICSSectors(): void
    {
        $gicsSectors = SectorPerformance::getGICSSectors();
        
        $this->assertIsArray($gicsSectors);
        $this->assertArrayHasKey('XLK', $gicsSectors);
        $this->assertEquals('Information Technology', $gicsSectors['XLK']);
        $this->assertArrayHasKey('XLE', $gicsSectors);
        $this->assertEquals('Energy', $gicsSectors['XLE']);
        $this->assertArrayHasKey('XLV', $gicsSectors);
        $this->assertEquals('Health Care', $gicsSectors['XLV']);
        
        // Should have 11 GICS sectors
        $this->assertCount(11, $gicsSectors);
    }

    /**
     * Test metadata inheritance
     */
    public function testMetadataInheritance(): void
    {
        $topStocks = [['symbol' => 'AAPL', 'weight' => 5.0]];
        
        $sector = new SectorPerformance(
            'XLK',
            'Technology',
            2.5,
            1.8,
            2.1,
            $topStocks,
            22.5,
            'GICS'
        );
        
        $metadata = $sector->getMetadata();
        
        $this->assertEquals('GICS', $metadata['classification']);
        $this->assertEquals(22.5, $metadata['market_cap_weight']);
        $this->assertEquals($topStocks, $metadata['top_stocks']);
    }

    /**
     * Test default values
     */
    public function testDefaultValues(): void
    {
        $sector = new SectorPerformance('TEST', 'Test Sector', 100.0);
        
        $this->assertEquals(0.0, $sector->getChange());
        $this->assertEquals(0.0, $sector->getChangePercent());
        $this->assertEquals([], $sector->getTopStocks());
        $this->assertEquals(0.0, $sector->getMarketCapWeight());
        $this->assertEquals('GICS', $sector->getClassification());
    }

    /**
     * Test signal strength inheritance
     */
    public function testSignalStrengthInheritance(): void
    {
        $strongSector = new SectorPerformance('XLK', 'Technology', 0.0, 0.0, 4.5);
        $weakSector = new SectorPerformance('XLU', 'Utilities', 0.0, 0.0, 0.8);
        
        $this->assertEquals(0.8, $strongSector->getSignalStrength()); // 4.5% change = strong
        $this->assertEquals(0.2, $weakSector->getSignalStrength());   // 0.8% change = very weak
    }

    /**
     * Test negative performance
     */
    public function testNegativePerformance(): void
    {
        $negativeSector = new SectorPerformance('XLE', 'Energy', 0.0, -2.5, -3.2);
        
        $this->assertTrue($negativeSector->isBearish());
        $this->assertFalse($negativeSector->isBullish());
        $this->assertEquals(0.8, $negativeSector->getSignalStrength()); // Uses absolute value
        
        $marketPerformance = 1.0;
        $this->assertEquals(-4.2, $negativeSector->getRelativePerformance($marketPerformance));
        $this->assertFalse($negativeSector->isOutperforming($marketPerformance));
    }

    /**
     * Test edge case with zero market performance
     */
    public function testZeroMarketPerformance(): void
    {
        $sector = new SectorPerformance('XLB', 'Materials', 0.0, 0.0, 2.5);
        
        $relativePerformance = $sector->getRelativePerformance(0.0);
        $this->assertEquals(2.5, $relativePerformance);
        $this->assertTrue($sector->isOutperforming(0.0));
    }

    /**
     * Test sector with no top stocks weight
     */
    public function testAddTopStockWithoutWeight(): void
    {
        $sector = new SectorPerformance('XLI', 'Industrials', 0.0);
        
        $sector->addTopStock('BA');
        $topStocks = $sector->getTopStocks();
        
        $this->assertCount(1, $topStocks);
        $this->assertEquals('BA', $topStocks[0]['symbol']);
        $this->assertEquals(0.0, $topStocks[0]['weight']);
    }
}
