<?php

namespace Tests\Unit\Finance\MarketFactors;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\MarketFactors\Entities\IndexPerformance;

/**
 * Unit tests for IndexPerformance entity
 */
class IndexPerformanceTest extends TestBaseSimple
{
    /**
     * Test creating an IndexPerformance instance
     */
    public function testCreateIndexPerformance(): void
    {
        $index = new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0);
        
        $this->assertEquals('SPY', $index->getSymbol());
        $this->assertEquals('S&P 500', $index->getName());
        $this->assertEquals('S&P 500', $index->getIndexName());
        $this->assertEquals('index', $index->getType());
        $this->assertEquals('US', $index->getRegion());
        $this->assertEquals(4150.0, $index->getValue());
        $this->assertEquals('equity', $index->getAssetClass());
        $this->assertEquals(0, $index->getConstituents());
        $this->assertEquals(0.0, $index->getMarketCap());
        $this->assertEquals('USD', $index->getCurrency());
    }

    /**
     * Test creating with full parameters
     */
    public function testCreateWithFullParameters(): void
    {
        $index = new IndexPerformance(
            'NIKKEI',
            'Nikkei 225',
            'Asia',
            28500.0,
            150.0,
            0.53,
            'equity',
            225,
            6500000000000.0,
            'JPY'
        );
        
        $this->assertEquals('NIKKEI', $index->getSymbol());
        $this->assertEquals('Nikkei 225', $index->getName());
        $this->assertEquals('Nikkei 225', $index->getIndexName());
        $this->assertEquals('Asia', $index->getRegion());
        $this->assertEquals(28500.0, $index->getValue());
        $this->assertEquals(150.0, $index->getChange());
        $this->assertEquals(0.53, $index->getChangePercent());
        $this->assertEquals('equity', $index->getAssetClass());
        $this->assertEquals(225, $index->getConstituents());
        $this->assertEquals(6500000000000.0, $index->getMarketCap());
        $this->assertEquals('JPY', $index->getCurrency());
    }

    /**
     * Test bond index creation
     */
    public function testCreateBondIndex(): void
    {
        $bondIndex = new IndexPerformance(
            'BND',
            'Total Bond Market',
            'US',
            80.5,
            -0.25,
            -0.31,
            'bond',
            8000,
            2500000000000.0,
            'USD'
        );
        
        $this->assertEquals('BND', $bondIndex->getSymbol());
        $this->assertEquals('Total Bond Market', $bondIndex->getIndexName());
        $this->assertEquals('bond', $bondIndex->getAssetClass());
        $this->assertEquals(8000, $bondIndex->getConstituents());
        $this->assertEquals(-0.31, $bondIndex->getChangePercent());
    }

    /**
     * Test commodity index creation
     */
    public function testCreateCommodityIndex(): void
    {
        $commodityIndex = new IndexPerformance(
            'GLD',
            'Gold',
            'Global',
            180.0,
            2.5,
            1.41,
            'commodity',
            1,
            45000000000.0,
            'USD'
        );
        
        $this->assertEquals('GLD', $commodityIndex->getSymbol());
        $this->assertEquals('commodity', $commodityIndex->getAssetClass());
        $this->assertEquals('Global', $commodityIndex->getRegion());
        $this->assertEquals(1.41, $commodityIndex->getChangePercent());
    }

    /**
     * Test relative performance calculation
     */
    public function testRelativePerformance(): void
    {
        $spyIndex = new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0, 20.0, 0.48);
        $nasdaqIndex = new IndexPerformance('QQQ', 'NASDAQ 100', 'US', 350.0, 2.1, 0.60);
        
        // NASDAQ is outperforming S&P by 0.12%
        $relativePerformance = $nasdaqIndex->getRelativePerformance($spyIndex);
        $this->assertEqualsWithDelta(0.12, $relativePerformance, 0.01);
        
        // NASDAQ is outperforming S&P
        $this->assertTrue($nasdaqIndex->isOutperforming($spyIndex));
        $this->assertFalse($spyIndex->isOutperforming($nasdaqIndex));
    }

    /**
     * Test relative performance with negative changes
     */
    public function testRelativePerformanceNegative(): void
    {
        $spyIndex = new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0, -10.0, -0.24);
        $bondIndex = new IndexPerformance('BND', 'Total Bond Market', 'US', 80.0, 0.5, 0.62);
        
        // Bonds are outperforming stocks by 0.86%
        $relativePerformance = $bondIndex->getRelativePerformance($spyIndex);
        $this->assertEqualsWithDelta(0.86, $relativePerformance, 0.01);
        
        $this->assertTrue($bondIndex->isOutperforming($spyIndex));
    }

    /**
     * Test getting major indices
     */
    public function testGetMajorIndices(): void
    {
        $majorIndices = IndexPerformance::getMajorIndices();
        
        $this->assertIsArray($majorIndices);
        $this->assertArrayHasKey('SPY', $majorIndices);
        $this->assertArrayHasKey('QQQ', $majorIndices);
        $this->assertArrayHasKey('IWM', $majorIndices);
        $this->assertArrayHasKey('EFA', $majorIndices);
        $this->assertArrayHasKey('EEM', $majorIndices);
        $this->assertArrayHasKey('GLD', $majorIndices);
        $this->assertArrayHasKey('BND', $majorIndices);
        
        // Check structure of index data
        $this->assertArrayHasKey('name', $majorIndices['SPY']);
        $this->assertArrayHasKey('region', $majorIndices['SPY']);
        $this->assertArrayHasKey('type', $majorIndices['SPY']);
        
        $this->assertEquals('S&P 500', $majorIndices['SPY']['name']);
        $this->assertEquals('US', $majorIndices['SPY']['region']);
        $this->assertEquals('Large Cap', $majorIndices['SPY']['type']);
    }

    /**
     * Test volatility category calculation
     */
    public function testVolatilityCategory(): void
    {
        // Very Low volatility
        $veryLowVol = new IndexPerformance('STABLE', 'Stable Index', 'US', 100.0, 0.5, 0.5);
        $this->assertEquals('Very Low', $veryLowVol->getVolatilityCategory());
        
        // Low volatility
        $lowVol = new IndexPerformance('LOW', 'Low Vol Index', 'US', 100.0, 1.5, 1.5);
        $this->assertEquals('Low', $lowVol->getVolatilityCategory());
        
        // Moderate volatility
        $moderateVol = new IndexPerformance('MOD', 'Moderate Index', 'US', 100.0, 2.5, 2.5);
        $this->assertEquals('Moderate', $moderateVol->getVolatilityCategory());
        
        // High volatility
        $highVol = new IndexPerformance('HIGH', 'High Vol Index', 'US', 100.0, 4.0, 4.0);
        $this->assertEquals('High', $highVol->getVolatilityCategory());
        
        // Test with negative changes
        $negativeVol = new IndexPerformance('NEG', 'Negative Index', 'US', 100.0, -3.5, -3.5);
        $this->assertEquals('High', $negativeVol->getVolatilityCategory());
    }

    /**
     * Test volatility category edge cases
     */
    public function testVolatilityCategoryEdgeCases(): void
    {
        // Exactly at boundaries
        $exactLow = new IndexPerformance('EXACT_LOW', 'Exact Low', 'US', 100.0, 1.0, 1.0);
        $this->assertEquals('Low', $exactLow->getVolatilityCategory());
        
        $exactModerate = new IndexPerformance('EXACT_MOD', 'Exact Moderate', 'US', 100.0, 2.0, 2.0);
        $this->assertEquals('Moderate', $exactModerate->getVolatilityCategory());
        
        $exactHigh = new IndexPerformance('EXACT_HIGH', 'Exact High', 'US', 100.0, 3.0, 3.0);
        $this->assertEquals('High', $exactHigh->getVolatilityCategory());
        
        // Zero change
        $noChange = new IndexPerformance('ZERO', 'No Change', 'US', 100.0, 0.0, 0.0);
        $this->assertEquals('Very Low', $noChange->getVolatilityCategory());
    }

    /**
     * Test bullish/bearish detection
     */
    public function testBullishBearishDetection(): void
    {
        $bullishIndex = new IndexPerformance('BULL', 'Bullish Index', 'US', 100.0, 2.5, 2.5);
        $bearishIndex = new IndexPerformance('BEAR', 'Bearish Index', 'US', 100.0, -1.8, -1.8);
        $neutralIndex = new IndexPerformance('NEUTRAL', 'Neutral Index', 'US', 100.0, 0.0, 0.0);
        
        $this->assertTrue($bullishIndex->isBullish());
        $this->assertFalse($bullishIndex->isBearish());
        
        $this->assertFalse($bearishIndex->isBullish());
        $this->assertTrue($bearishIndex->isBearish());
        
        $this->assertFalse($neutralIndex->isBullish());
        $this->assertFalse($neutralIndex->isBearish());
    }

    /**
     * Test array conversion
     */
    public function testToArray(): void
    {
        $index = new IndexPerformance(
            'SPY',
            'S&P 500',
            'US',
            4150.0,
            25.5,
            0.62,
            'equity',
            500,
            45000000000000.0,
            'USD'
        );
        
        $array = $index->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('SPY', $array['symbol']);
        $this->assertEquals('S&P 500', $array['name']);
        $this->assertEquals('index', $array['type']);
        $this->assertEquals(4150.0, $array['value']);
        $this->assertEquals(25.5, $array['change']);
        $this->assertEquals(0.62, $array['change_percent']);
        $this->assertTrue($array['is_bullish']);
        $this->assertFalse($array['is_bearish']);
        
        // Check metadata includes IndexPerformance specific fields
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('US', $array['metadata']['region']);
        $this->assertEquals('equity', $array['metadata']['asset_class']);
        $this->assertEquals(500, $array['metadata']['constituents']);
        $this->assertEquals(45000000000000.0, $array['metadata']['market_cap']);
        $this->assertEquals('USD', $array['metadata']['currency']);
    }

    /**
     * Test metadata operations
     */
    public function testMetadataOperations(): void
    {
        $index = new IndexPerformance('SPY', 'S&P 500', 'US', 4150.0);
        
        // Test setting and getting metadata
        $index->setMetadata('exchange', 'NYSE');
        $index->setMetadata('inception_date', '1993-01-22');
        
        $this->assertEquals('NYSE', $index->getMetadata('exchange'));
        $this->assertEquals('1993-01-22', $index->getMetadata('inception_date'));
        $this->assertNull($index->getMetadata('nonexistent'));
        
        // Test getting all metadata (should include constructor metadata)
        $allMetadata = $index->getAllMetadata();
        $this->assertGreaterThanOrEqual(7, count($allMetadata)); // 5 from constructor + 2 added
        $this->assertArrayHasKey('region', $allMetadata);
        $this->assertArrayHasKey('asset_class', $allMetadata);
        $this->assertArrayHasKey('exchange', $allMetadata);
        $this->assertArrayHasKey('inception_date', $allMetadata);
        
        // Test removing metadata
        $index->removeMetadata('inception_date');
        $this->assertNull($index->getMetadata('inception_date'));
    }

    /**
     * Test edge cases for numeric values
     */
    public function testNumericEdgeCases(): void
    {
        // Test zero values
        $index = new IndexPerformance('TEST', 'Test Index', 'US', 0.0, 0.0, 0.0, 'equity', 0, 0.0, 'USD');
        
        $this->assertEquals(0.0, $index->getValue());
        $this->assertEquals(0.0, $index->getChange());
        $this->assertEquals(0.0, $index->getChangePercent());
        $this->assertEquals(0, $index->getConstituents());
        $this->assertEquals(0.0, $index->getMarketCap());
        $this->assertEquals('Very Low', $index->getVolatilityCategory());
        
        // Test negative values
        $negativeIndex = new IndexPerformance('NEG', 'Negative Index', 'US', -100.0, -10.0, -5.0);
        $this->assertEquals(-100.0, $negativeIndex->getValue());
        $this->assertEquals(-10.0, $negativeIndex->getChange());
        $this->assertEquals(-5.0, $negativeIndex->getChangePercent());
        $this->assertTrue($negativeIndex->isBearish());
    }

    /**
     * Test international indices
     */
    public function testInternationalIndices(): void
    {
        $canadianIndex = new IndexPerformance('VTI.TO', 'TSX Composite', 'Canada', 22000.0);
        $europeanIndex = new IndexPerformance('EFA', 'EAFE', 'Europe', 75.0);
        $emergingIndex = new IndexPerformance('EEM', 'Emerging Markets', 'Emerging', 45.0);
        
        $this->assertEquals('Canada', $canadianIndex->getRegion());
        $this->assertEquals('Europe', $europeanIndex->getRegion());
        $this->assertEquals('Emerging', $emergingIndex->getRegion());
        
        // Check they're all properly classified as indices
        $this->assertEquals('index', $canadianIndex->getType());
        $this->assertEquals('index', $europeanIndex->getType());
        $this->assertEquals('index', $emergingIndex->getType());
    }

    /**
     * Test empty string handling
     */
    public function testEmptyStringHandling(): void
    {
        $index = new IndexPerformance('TEST', '', '', 100.0, 0.0, 0.0, '', 0, 0.0, '');
        
        $this->assertEquals('', $index->getName());
        $this->assertEquals('', $index->getIndexName());
        $this->assertEquals('', $index->getRegion());
        $this->assertEquals('', $index->getAssetClass());
        $this->assertEquals('', $index->getCurrency());
    }
}
