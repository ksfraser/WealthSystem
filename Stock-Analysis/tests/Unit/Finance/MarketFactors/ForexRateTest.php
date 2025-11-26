<?php

namespace Tests\Unit\Finance\MarketFactors;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\MarketFactors\Entities\ForexRate;

/**
 * Unit tests for ForexRate entity
 */
class ForexRateTest extends TestBaseSimple
{
    /**
     * Test creating a ForexRate instance
     */
    public function testCreateForexRate(): void
    {
        $forex = new ForexRate('USD', 'CAD', 1.35);
        
        $this->assertEquals('USDCAD', $forex->getSymbol());
        $this->assertEquals('USD/CAD', $forex->getName());
        $this->assertEquals('forex', $forex->getType());
        $this->assertEquals('USD', $forex->getBaseCurrency());
        $this->assertEquals('CAD', $forex->getQuoteCurrency());
        $this->assertEquals(1.35, $forex->getValue());
        $this->assertEquals('USDCAD', $forex->getPair());
        $this->assertEquals(1.35, $forex->getBid()); // Default to rate when not specified
        $this->assertEquals(1.35, $forex->getAsk()); // Default to rate when not specified
        $this->assertEquals(0.0, $forex->getSpread()); // No spread when bid=ask
    }

    /**
     * Test creating with full parameters
     */
    public function testCreateWithFullParameters(): void
    {
        $forex = new ForexRate(
            'EUR',
            'USD',
            1.08,
            0.02,
            1.85,
            1.079,
            1.081
        );
        
        $this->assertEquals('EURUSD', $forex->getSymbol());
        $this->assertEquals('EUR/USD', $forex->getName());
        $this->assertEquals('EUR', $forex->getBaseCurrency());
        $this->assertEquals('USD', $forex->getQuoteCurrency());
        $this->assertEquals(1.08, $forex->getValue());
        $this->assertEquals(0.02, $forex->getChange());
        $this->assertEquals(1.85, $forex->getChangePercent());
        $this->assertEquals(1.079, $forex->getBid());
        $this->assertEquals(1.081, $forex->getAsk());
        $this->assertEquals(0.002, $forex->getSpread());
    }

    /**
     * Test inverse rate calculation
     */
    public function testInverseRate(): void
    {
        $usdCad = new ForexRate('USD', 'CAD', 1.35);
        $inverseRate = $usdCad->getInverseRate();
        
        $this->assertEqualsWithDelta(0.741, $inverseRate, 0.001);
        
        // Test with different rate
        $eurUsd = new ForexRate('EUR', 'USD', 1.08);
        $this->assertEqualsWithDelta(0.926, $eurUsd->getInverseRate(), 0.001);
    }

    /**
     * Test inverse rate with zero
     */
    public function testInverseRateWithZero(): void
    {
        $zeroRate = new ForexRate('TEST', 'TEST', 0.0);
        $this->assertEquals(0.0, $zeroRate->getInverseRate());
    }

    /**
     * Test cross rate calculation
     */
    public function testCrossRate(): void
    {
        $usdCad = new ForexRate('USD', 'CAD', 1.35);
        $eurUsd = new ForexRate('EUR', 'USD', 1.08);
        
        // EUR/CAD = EUR/USD * USD/CAD using static method
        $crossRate = ForexRate::calculateCrossRate($eurUsd, $usdCad);
        $this->assertEqualsWithDelta(1.458, $crossRate, 0.001); // 1.08 * 1.35 = 1.458
    }

    /**
     * Test cross rate with mismatched currencies
     */
    public function testCrossRateWithMismatchedCurrencies(): void
    {
        $usdCad = new ForexRate('USD', 'CAD', 1.35);
        $gbpJpy = new ForexRate('GBP', 'JPY', 150.0);
        
        // Should return null when currencies don't have USD in common
        $crossRate = ForexRate::calculateCrossRate($usdCad, $gbpJpy);
        $this->assertNull($crossRate);
    }

    /**
     * Test major currency pairs
     */
    public function testMajorCurrencyPairs(): void
    {
        $majorPairs = ForexRate::getMajorPairs();
        
        $this->assertIsArray($majorPairs);
        $this->assertArrayHasKey('EURUSD', $majorPairs);
        $this->assertArrayHasKey('USDJPY', $majorPairs);
        $this->assertArrayHasKey('GBPUSD', $majorPairs);
        $this->assertArrayHasKey('USDCHF', $majorPairs);
        $this->assertArrayHasKey('USDCAD', $majorPairs);
        $this->assertArrayHasKey('AUDUSD', $majorPairs);
        $this->assertArrayHasKey('NZDUSD', $majorPairs);
        
        // Check structure
        $this->assertArrayHasKey('name', $majorPairs['EURUSD']);
        $this->assertArrayHasKey('base', $majorPairs['EURUSD']);
        $this->assertArrayHasKey('quote', $majorPairs['EURUSD']);
        $this->assertEquals('Euro/US Dollar', $majorPairs['EURUSD']['name']);
        $this->assertEquals('EUR', $majorPairs['EURUSD']['base']);
        $this->assertEquals('USD', $majorPairs['EURUSD']['quote']);
    }

    /**
     * Test bullish/bearish detection
     */
    public function testBullishBearishDetection(): void
    {
        $bullishForex = new ForexRate('USD', 'CAD', 1.35, 0.02, 1.5);
        $bearishForex = new ForexRate('EUR', 'USD', 1.08, -0.01, -0.9);
        $neutralForex = new ForexRate('GBP', 'USD', 1.25, 0.0, 0.0);
        
        $this->assertTrue($bullishForex->isBullish());
        $this->assertFalse($bullishForex->isBearish());
        
        $this->assertFalse($bearishForex->isBullish());
        $this->assertTrue($bearishForex->isBearish());
        
        $this->assertFalse($neutralForex->isBullish());
        $this->assertFalse($neutralForex->isBearish());
    }

    /**
     * Test array conversion
     */
    public function testToArray(): void
    {
        $forex = new ForexRate('EUR', 'USD', 1.08, 0.02, 1.85);
        
        $array = $forex->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals('EURUSD', $array['symbol']);
        $this->assertEquals('EUR/USD', $array['name']);
        $this->assertEquals('forex', $array['type']);
        $this->assertEquals(1.08, $array['value']);
        $this->assertEquals(0.02, $array['change']);
        $this->assertEquals(1.85, $array['change_percent']);
        $this->assertTrue($array['is_bullish']);
        $this->assertFalse($array['is_bearish']);
        
        // Check metadata
        $this->assertArrayHasKey('metadata', $array);
        $this->assertEquals('EUR', $array['metadata']['base_currency']);
        $this->assertEquals('USD', $array['metadata']['quote_currency']);
        $this->assertEquals(1.08, $array['metadata']['bid']); // Default to rate
        $this->assertEquals(1.08, $array['metadata']['ask']); // Default to rate
        $this->assertEquals(0.0, $array['metadata']['spread']); // No spread when bid=ask
    }

    /**
     * Test signal strength calculation
     */
    public function testSignalStrength(): void
    {
        // Very strong signal (5%+ change)
        $veryStrong = new ForexRate('USD', 'JPY', 150.0, 7.5, 5.2);
        $this->assertEquals(1.0, $veryStrong->getSignalStrength());
        
        // Strong signal (3-5% change)
        $strong = new ForexRate('EUR', 'USD', 1.08, 0.04, 3.8);
        $this->assertEquals(0.8, $strong->getSignalStrength());
        
        // Moderate signal (2-3% change)
        $moderate = new ForexRate('GBP', 'USD', 1.25, 0.025, 2.1);
        $this->assertEquals(0.6, $moderate->getSignalStrength());
        
        // Weak signal (1-2% change)
        $weak = new ForexRate('USD', 'CAD', 1.35, 0.015, 1.2);
        $this->assertEquals(0.4, $weak->getSignalStrength());
        
        // Very weak signal (0.5-1% change)
        $veryWeak = new ForexRate('AUD', 'USD', 0.65, 0.005, 0.8);
        $this->assertEquals(0.2, $veryWeak->getSignalStrength());
        
        // Minimal signal (<0.5% change)
        $minimal = new ForexRate('NZD', 'USD', 0.60, 0.001, 0.2);
        $this->assertEquals(0.1, $minimal->getSignalStrength());
    }

    /**
     * Test data age calculation
     */
    public function testDataAge(): void
    {
        $oldTimestamp = new \DateTime('-30 minutes');
        $oldForex = new ForexRate('USD', 'CAD', 1.35, 0.0, 0.0, 0.0, 0.0, $oldTimestamp);
        $this->assertGreaterThanOrEqual(29, $oldForex->getDataAge());
        $this->assertLessThanOrEqual(31, $oldForex->getDataAge());
        
        $freshForex = new ForexRate('EUR', 'USD', 1.08);
        $this->assertLessThanOrEqual(1, $freshForex->getDataAge());
    }

    /**
     * Test stale data detection
     */
    public function testStaleDataDetection(): void
    {
        $staleTimestamp = new \DateTime('-2 hours');
        $staleForex = new ForexRate('USD', 'CAD', 1.35, 0.0, 0.0, 0.0, 0.0, $staleTimestamp);
        $freshForex = new ForexRate('EUR', 'USD', 1.08);
        
        $this->assertTrue($staleForex->isStale(60)); // Stale if older than 1 hour
        $this->assertFalse($freshForex->isStale(60));
        
        // Custom threshold
        $this->assertTrue($staleForex->isStale(30)); // Stale if older than 30 minutes
        $this->assertFalse($staleForex->isStale(180)); // Not stale if threshold is 3 hours
    }

    /**
     * Test metadata operations
     */
    public function testMetadataOperations(): void
    {
        $forex = new ForexRate('USD', 'CAD', 1.35);
        
        $metadata = $forex->getMetadata();
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('base_currency', $metadata);
        $this->assertArrayHasKey('quote_currency', $metadata);
        $this->assertArrayHasKey('bid', $metadata);
        $this->assertArrayHasKey('ask', $metadata);
        $this->assertArrayHasKey('spread', $metadata);
        
        $this->assertEquals('USD', $forex->getMetadataValue('base_currency'));
        $this->assertEquals('CAD', $forex->getMetadataValue('quote_currency'));
        $this->assertEquals(1.35, $forex->getMetadataValue('bid'));
        $this->assertEquals(1.35, $forex->getMetadataValue('ask'));
        $this->assertEquals(0.0, $forex->getMetadataValue('spread'));
        $this->assertNull($forex->getMetadataValue('nonexistent'));
        
        // Test with default value
        $this->assertEquals('default', $forex->getMetadataValue('nonexistent', 'default'));
    }

    /**
     * Test bid/ask/spread functionality
     */
    public function testBidAskSpread(): void
    {
        $forex = new ForexRate('EUR', 'USD', 1.08, 0.0, 0.0, 1.079, 1.081);
        
        $this->assertEquals(1.079, $forex->getBid());
        $this->assertEquals(1.081, $forex->getAsk());
        $this->assertEquals(0.002, $forex->getSpread());
        
        // Test spread in basis points
        $expectedBps = (0.002 / 1.08) * 10000;
        $this->assertEqualsWithDelta($expectedBps, $forex->getSpreadBps(), 0.1);
    }

    /**
     * Test currency conversion methods
     */
    public function testCurrencyConversion(): void
    {
        $usdCad = new ForexRate('USD', 'CAD', 1.35);
        
        // Convert 100 USD to CAD
        $cadAmount = $usdCad->convertToQuote(100.0);
        $this->assertEquals(135.0, $cadAmount);
        
        // Convert 135 CAD to USD
        $usdAmount = $usdCad->convertToBase(135.0);
        $this->assertEquals(100.0, $usdAmount);
    }

    /**
     * Test strengthening/weakening detection
     */
    public function testStrengtheningWeakening(): void
    {
        $strengtheningForex = new ForexRate('USD', 'CAD', 1.35, 0.02, 1.5);
        $weakeningForex = new ForexRate('EUR', 'USD', 1.08, -0.01, -0.9);
        $neutralForex = new ForexRate('GBP', 'USD', 1.25, 0.0, 0.0);
        
        $this->assertTrue($strengtheningForex->isStrengthening());
        $this->assertFalse($strengtheningForex->isWeakening());
        
        $this->assertFalse($weakeningForex->isStrengthening());
        $this->assertTrue($weakeningForex->isWeakening());
        
        $this->assertFalse($neutralForex->isStrengthening());
        $this->assertFalse($neutralForex->isWeakening());
    }

    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        // Test with very small rates
        $smallRate = new ForexRate('TEST1', 'TEST2', 0.0001);
        $this->assertEquals(0.0001, $smallRate->getValue());
        $this->assertEquals(10000.0, $smallRate->getInverseRate());
        
        // Test with large rates
        $largeRate = new ForexRate('TEST3', 'TEST4', 10000.0);
        $this->assertEquals(10000.0, $largeRate->getValue());
        $this->assertEquals(0.0001, $largeRate->getInverseRate());
        
        // Test with negative changes
        $negativeChange = new ForexRate('USD', 'CAD', 1.35, -0.05, -3.6);
        $this->assertTrue($negativeChange->isBearish());
        $this->assertEquals(0.8, $negativeChange->getSignalStrength()); // Strong signal due to 3.6% absolute change
    }

    /**
     * Test currency pair formatting
     */
    public function testCurrencyPairFormatting(): void
    {
        // Test normal pairs
        $usdCad = new ForexRate('USD', 'CAD', 1.35);
        $this->assertEquals('USDCAD', $usdCad->getSymbol());
        $this->assertEquals('USD/CAD', $usdCad->getName());
        $this->assertEquals('USDCAD', $usdCad->getPair());
        
        // Test with lowercase input (should be converted to uppercase)
        $eurUsd = new ForexRate('eur', 'usd', 1.08);
        $this->assertEquals('EURUSD', $eurUsd->getSymbol());
        $this->assertEquals('EUR/USD', $eurUsd->getName());
        $this->assertEquals('EUR', $eurUsd->getBaseCurrency());
        $this->assertEquals('USD', $eurUsd->getQuoteCurrency());
        
        // Test with same currency (should still work)
        $sameCurrency = new ForexRate('USD', 'USD', 1.0);
        $this->assertEquals('USDUSD', $sameCurrency->getSymbol());
        $this->assertEquals('USD/USD', $sameCurrency->getName());
    }

    /**
     * Test commodity currency pairs
     */
    public function testCommodityCurrencyPairs(): void
    {
        $commodityPairs = ForexRate::getCommodityPairs();
        
        $this->assertIsArray($commodityPairs);
        $this->assertArrayHasKey('USDCAD', $commodityPairs);
        $this->assertArrayHasKey('AUDUSD', $commodityPairs);
        $this->assertArrayHasKey('NZDUSD', $commodityPairs);
        
        // Check structure
        $this->assertArrayHasKey('commodity', $commodityPairs['USDCAD']);
        $this->assertArrayHasKey('correlation', $commodityPairs['USDCAD']);
        $this->assertEquals('Oil', $commodityPairs['USDCAD']['commodity']);
        $this->assertEquals('negative', $commodityPairs['USDCAD']['correlation']);
    }

    /**
     * Test volatility category
     */
    public function testVolatilityCategory(): void
    {
        // High volatility
        $highVol = new ForexRate('USD', 'JPY', 150.0, 3.0, 2.5);
        $this->assertEquals('High', $highVol->getVolatilityCategory());
        
        // Moderate volatility
        $moderateVol = new ForexRate('EUR', 'USD', 1.08, 0.015, 1.2);
        $this->assertEquals('Moderate', $moderateVol->getVolatilityCategory());
        
        // Low volatility
        $lowVol = new ForexRate('USD', 'CAD', 1.35, 0.008, 0.7);
        $this->assertEquals('Low', $lowVol->getVolatilityCategory());
        
        // Very low volatility
        $veryLowVol = new ForexRate('GBP', 'USD', 1.25, 0.002, 0.2);
        $this->assertEquals('Very Low', $veryLowVol->getVolatilityCategory());
    }

    /**
     * Test empty string handling
     */
    public function testEmptyStringHandling(): void
    {
        $emptyForex = new ForexRate('', '', 1.0);
        $this->assertEquals('', $emptyForex->getSymbol());
        $this->assertEquals('/', $emptyForex->getName());
        $this->assertEquals('', $emptyForex->getBaseCurrency());
        $this->assertEquals('', $emptyForex->getQuoteCurrency());
        $this->assertEquals('', $emptyForex->getPair());
    }
}
