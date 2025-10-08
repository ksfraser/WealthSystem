<?php

namespace Tests\Unit\Finance\MarketFactors;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\MarketFactors\Entities\MarketFactor;

/**
 * Unit tests for MarketFactor entity
 */
class MarketFactorTest extends TestBaseSimple
{
    /**
     * Test basic MarketFactor creation and getters
     */
    public function testMarketFactorCreation(): void
    {
        $timestamp = new \DateTime('2023-01-01 12:00:00');
        $metadata = ['source' => 'test', 'category' => 'equity'];
        
        $factor = new MarketFactor(
            'AAPL',
            'Apple Inc.',
            'stock',
            150.25,
            2.15,
            1.45,
            $timestamp,
            $metadata
        );
        
        $this->assertEquals('AAPL', $factor->getSymbol());
        $this->assertEquals('Apple Inc.', $factor->getName());
        $this->assertEquals('stock', $factor->getType());
        $this->assertEquals(150.25, $factor->getValue());
        $this->assertEquals(2.15, $factor->getChange());
        $this->assertEquals(1.45, $factor->getChangePercent());
        $this->assertEquals($timestamp, $factor->getTimestamp());
        $this->assertEquals($metadata, $factor->getMetadata());
    }

    /**
     * Test MarketFactor with default timestamp
     */
    public function testMarketFactorDefaultTimestamp(): void
    {
        $factor = new MarketFactor('SPY', 'S&P 500', 'index', 4150.0);
        
        $this->assertInstanceOf(\DateTime::class, $factor->getTimestamp());
        $this->assertLessThan(60, time() - $factor->getTimestamp()->getTimestamp()); // Within last minute
    }

    /**
     * Test setters
     */
    public function testSetters(): void
    {
        $factor = new MarketFactor('TEST', 'Test Factor', 'test', 100.0);
        
        $newTimestamp = new \DateTime('2023-06-15 10:30:00');
        $newMetadata = ['updated' => true];
        
        $factor->setValue(105.5);
        $factor->setChange(5.5);
        $factor->setChangePercent(5.52);
        $factor->setTimestamp($newTimestamp);
        $factor->setMetadata($newMetadata);
        
        $this->assertEquals(105.5, $factor->getValue());
        $this->assertEquals(5.5, $factor->getChange());
        $this->assertEquals(5.52, $factor->getChangePercent());
        $this->assertEquals($newTimestamp, $factor->getTimestamp());
        $this->assertEquals($newMetadata, $factor->getMetadata());
    }

    /**
     * Test metadata operations
     */
    public function testMetadataOperations(): void
    {
        $factor = new MarketFactor('TEST', 'Test Factor', 'test', 100.0);
        
        $factor->addMetadata('key1', 'value1');
        $factor->addMetadata('key2', 42);
        
        $this->assertEquals('value1', $factor->getMetadataValue('key1'));
        $this->assertEquals(42, $factor->getMetadataValue('key2'));
        $this->assertEquals('default', $factor->getMetadataValue('nonexistent', 'default'));
        $this->assertNull($factor->getMetadataValue('nonexistent'));
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $timestamp = new \DateTime('2023-01-01 12:00:00');
        $metadata = ['source' => 'test'];
        
        $factor = new MarketFactor('AAPL', 'Apple Inc.', 'stock', 150.25, 2.15, 1.45, $timestamp, $metadata);
        $array = $factor->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('AAPL', $array['symbol']);
        $this->assertEquals('Apple Inc.', $array['name']);
        $this->assertEquals('stock', $array['type']);
        $this->assertEquals(150.25, $array['value']);
        $this->assertEquals(2.15, $array['change']);
        $this->assertEquals(1.45, $array['change_percent']);
        $this->assertEquals('2023-01-01 12:00:00', $array['timestamp']);
        $this->assertJsonStringEqualsJsonString(json_encode($metadata), $array['metadata']);
    }

    /**
     * Test fromArray method
     */
    public function testFromArray(): void
    {
        $data = [
            'symbol' => 'MSFT',
            'name' => 'Microsoft Corp.',
            'type' => 'stock',
            'value' => 280.50,
            'change' => -3.25,
            'change_percent' => -1.15,
            'timestamp' => '2023-01-01 15:30:00',
            'metadata' => '{"sector": "technology"}'
        ];
        
        $factor = MarketFactor::fromArray($data);
        
        $this->assertEquals('MSFT', $factor->getSymbol());
        $this->assertEquals('Microsoft Corp.', $factor->getName());
        $this->assertEquals('stock', $factor->getType());
        $this->assertEquals(280.50, $factor->getValue());
        $this->assertEquals(-3.25, $factor->getChange());
        $this->assertEquals(-1.15, $factor->getChangePercent());
        $this->assertEquals('2023-01-01 15:30:00', $factor->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(['sector' => 'technology'], $factor->getMetadata());
    }

    /**
     * Test isBullish method
     */
    public function testIsBullish(): void
    {
        $bullishFactor = new MarketFactor('BULL', 'Bullish Test', 'test', 100.0, 2.0, 2.0);
        $bearishFactor = new MarketFactor('BEAR', 'Bearish Test', 'test', 100.0, -2.0, -2.0);
        $neutralFactor = new MarketFactor('NEUTRAL', 'Neutral Test', 'test', 100.0, 0.0, 0.0);
        
        $this->assertTrue($bullishFactor->isBullish());
        $this->assertFalse($bearishFactor->isBullish());
        $this->assertFalse($neutralFactor->isBullish());
    }

    /**
     * Test isBearish method
     */
    public function testIsBearish(): void
    {
        $bullishFactor = new MarketFactor('BULL', 'Bullish Test', 'test', 100.0, 2.0, 2.0);
        $bearishFactor = new MarketFactor('BEAR', 'Bearish Test', 'test', 100.0, -2.0, -2.0);
        $neutralFactor = new MarketFactor('NEUTRAL', 'Neutral Test', 'test', 100.0, 0.0, 0.0);
        
        $this->assertFalse($bullishFactor->isBearish());
        $this->assertTrue($bearishFactor->isBearish());
        $this->assertFalse($neutralFactor->isBearish());
    }

    /**
     * Test signal strength calculation
     */
    public function testGetSignalStrength(): void
    {
        $veryStrong = new MarketFactor('VS', 'Very Strong', 'test', 100.0, 0.0, 6.0);
        $strong = new MarketFactor('S', 'Strong', 'test', 100.0, 0.0, 4.0);
        $moderate = new MarketFactor('M', 'Moderate', 'test', 100.0, 0.0, 2.5);
        $weak = new MarketFactor('W', 'Weak', 'test', 100.0, 0.0, 1.5);
        $veryWeak = new MarketFactor('VW', 'Very Weak', 'test', 100.0, 0.0, 0.7);
        $minimal = new MarketFactor('MIN', 'Minimal', 'test', 100.0, 0.0, 0.3);
        
        $this->assertEquals(1.0, $veryStrong->getSignalStrength());
        $this->assertEquals(0.8, $strong->getSignalStrength());
        $this->assertEquals(0.6, $moderate->getSignalStrength());
        $this->assertEquals(0.4, $weak->getSignalStrength());
        $this->assertEquals(0.2, $veryWeak->getSignalStrength());
        $this->assertEquals(0.1, $minimal->getSignalStrength());
    }

    /**
     * Test data age calculation
     */
    public function testGetDataAge(): void
    {
        $oldTimestamp = new \DateTime('-30 minutes');
        $factor = new MarketFactor('OLD', 'Old Factor', 'test', 100.0, 0.0, 0.0, $oldTimestamp);
        
        $age = $factor->getDataAge();
        $this->assertGreaterThanOrEqual(29, $age); // Should be around 30 minutes
        $this->assertLessThanOrEqual(31, $age);
    }

    /**
     * Test isStale method
     */
    public function testIsStale(): void
    {
        $freshTimestamp = new \DateTime('-10 minutes');
        $staleTimestamp = new \DateTime('-90 minutes');
        
        $freshFactor = new MarketFactor('FRESH', 'Fresh Factor', 'test', 100.0, 0.0, 0.0, $freshTimestamp);
        $staleFactor = new MarketFactor('STALE', 'Stale Factor', 'test', 100.0, 0.0, 0.0, $staleTimestamp);
        
        $this->assertFalse($freshFactor->isStale(60)); // Not stale (10 min < 60 min)
        $this->assertTrue($staleFactor->isStale(60));  // Stale (90 min > 60 min)
        
        // Test with different threshold
    $this->assertTrue($freshFactor->isStale(5));  // Stale with 5 min threshold
        $this->assertTrue($staleFactor->isStale(120)); // Still stale with 120 min threshold
    }

    /**
     * Test negative signal strength
     */
    public function testNegativeSignalStrength(): void
    {
        $negativeFactor = new MarketFactor('NEG', 'Negative Factor', 'test', 100.0, 0.0, -3.5);
        
        // Signal strength should use absolute value
        $this->assertEquals(0.8, $negativeFactor->getSignalStrength());
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Zero change
        $zeroFactor = new MarketFactor('ZERO', 'Zero Change', 'test', 100.0, 0.0, 0.0);
        $this->assertEquals(0.1, $zeroFactor->getSignalStrength()); // Minimal strength
        $this->assertFalse($zeroFactor->isBullish());
        $this->assertFalse($zeroFactor->isBearish());
        
        // Very small change
        $tinyFactor = new MarketFactor('TINY', 'Tiny Change', 'test', 100.0, 0.01, 0.01);
        $this->assertEquals(0.1, $tinyFactor->getSignalStrength());
        $this->assertTrue($tinyFactor->isBullish());
        
        // Large change
        $hugeFactor = new MarketFactor('HUGE', 'Huge Change', 'test', 100.0, 0.0, 10.0);
        $this->assertEquals(1.0, $hugeFactor->getSignalStrength());
    }

    /**
     * Test metadata with complex data types
     */
    public function testComplexMetadata(): void
    {
        $complexMetadata = [
            'array_data' => [1, 2, 3],
            'nested' => ['key' => 'value'],
            'boolean' => true,
            'null_value' => null
        ];
        
        $factor = new MarketFactor('COMPLEX', 'Complex Metadata', 'test', 100.0, 0.0, 0.0, null, $complexMetadata);
        
        $this->assertEquals($complexMetadata, $factor->getMetadata());
        $this->assertEquals([1, 2, 3], $factor->getMetadataValue('array_data'));
        $this->assertEquals(['key' => 'value'], $factor->getMetadataValue('nested'));
        $this->assertTrue($factor->getMetadataValue('boolean'));
        $this->assertNull($factor->getMetadataValue('null_value'));
    }
}
