<?php

declare(strict_types=1);

namespace Tests\Strategy;

use PHPUnit\Framework\TestCase;
use App\Strategy\VWAPStrategy;
use App\Strategy\StrategySignal;

/**
 * Test suite for VWAP (Volume-Weighted Average Price) trading strategy.
 * 
 * VWAP is calculated as: Σ(Price × Volume) / Σ(Volume)
 * Used to determine if current price is above/below average trading price.
 */
class VWAPStrategyTest extends TestCase
{
    private VWAPStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new VWAPStrategy();
    }

    /**
     * @test
     */
    public function itCalculatesVWAP(): void
    {
        $prices = [100.0, 101.0, 102.0, 103.0, 104.0];
        $volumes = [1000, 2000, 1500, 2500, 1000];
        
        $vwap = $this->strategy->calculateVWAP($prices, $volumes);
        
        // VWAP = (100*1000 + 101*2000 + 102*1500 + 103*2500 + 104*1000) / (1000+2000+1500+2500+1000)
        // VWAP = (100000 + 202000 + 153000 + 257500 + 104000) / 8000
        // VWAP = 816500 / 8000 = 102.0625
        $this->assertEqualsWithDelta(102.0625, $vwap, 0.01);
    }

    /**
     * @test
     */
    public function itCalculatesTypicalPrice(): void
    {
        $high = 105.0;
        $low = 95.0;
        $close = 100.0;
        
        $typical = $this->strategy->calculateTypicalPrice($high, $low, $close);
        
        // Typical Price = (High + Low + Close) / 3
        $expected = (105.0 + 95.0 + 100.0) / 3;
        $this->assertEqualsWithDelta($expected, $typical, 0.01);
    }

    /**
     * @test
     */
    public function itCalculatesPriceDeviationFromVWAP(): void
    {
        $currentPrice = 105.0;
        $vwap = 100.0;
        
        $deviation = $this->strategy->calculateDeviation($currentPrice, $vwap);
        
        // Deviation = ((CurrentPrice - VWAP) / VWAP) * 100
        // Deviation = ((105 - 100) / 100) * 100 = 5.0%
        $this->assertEqualsWithDelta(5.0, $deviation, 0.01);
    }

    /**
     * @test
     */
    public function itDetectsPriceBelowVWAP(): void
    {
        $currentPrice = 95.0;
        $vwap = 100.0;
        
        $isBelowVWAP = $this->strategy->isBelowVWAP($currentPrice, $vwap, 2.0);
        
        // Price is 5% below VWAP (threshold: 2%), should return true
        $this->assertTrue($isBelowVWAP);
    }

    /**
     * @test
     */
    public function itDetectsPriceAboveVWAP(): void
    {
        $currentPrice = 105.0;
        $vwap = 100.0;
        
        $isAboveVWAP = $this->strategy->isAboveVWAP($currentPrice, $vwap, 2.0);
        
        // Price is 5% above VWAP (threshold: 2%), should return true
        $this->assertTrue($isAboveVWAP);
    }

    /**
     * @test
     */
    public function itGeneratesBuySignalWhenBelowVWAP(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 145.0,
            'high' => 147.0,
            'low' => 143.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('BUY', $signal->getAction());
        $this->assertGreaterThan(0.5, $signal->getStrength());
        $this->assertStringContainsString('VWAP', $signal->getReason());
    }

    /**
     * @test
     */
    public function itGeneratesSellSignalWhenAboveVWAP(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 165.0,
            'high' => 166.0,
            'low' => 164.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('SELL', $signal->getAction());
        $this->assertGreaterThan(0.5, $signal->getStrength());
        $this->assertStringContainsString('VWAP', $signal->getReason());
    }

    /**
     * @test
     */
    public function itGeneratesHoldSignalNearVWAP(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 155.5,
            'high' => 156.0,
            'low' => 155.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('HOLD', $signal->getAction());
    }

    /**
     * @test
     */
    public function itAdjustsSignalStrengthByDeviation(): void
    {
        // Strong deviation should produce stronger signal
        $farBelowData = [
            'symbol' => 'AAPL',
            'price' => 140.0,  // Far below VWAP
            'high' => 141.0,
            'low' => 139.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $slightlyBelowData = [
            'symbol' => 'AAPL',
            'price' => 152.0,  // Slightly below VWAP
            'high' => 153.0,
            'low' => 151.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $farSignal = $this->strategy->analyze($farBelowData);
        $slightSignal = $this->strategy->analyze($slightlyBelowData);
        
        $this->assertGreaterThan($slightSignal->getStrength(), $farSignal->getStrength());
    }

    /**
     * @test
     */
    public function itIncludesVolumeInformation(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 145.0,
            'high' => 147.0,
            'low' => 143.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $signal = $this->strategy->analyze($data);
        $metadata = $signal->getMetadata();
        
        $this->assertArrayHasKey('vwap', $metadata);
        $this->assertArrayHasKey('deviation', $metadata);
        $this->assertArrayHasKey('current_price', $metadata);
    }

    /**
     * @test
     */
    public function itValidatesMinimumDataPoints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient data');
        
        $data = [
            'symbol' => 'AAPL',
            'price' => 150.0,
            'high' => 151.0,
            'low' => 149.0,
            'prices' => [150.0, 151.0],  // Only 2 points, needs 5+
            'volumes' => [1000, 1100],
            'highs' => [151.0, 152.0],
            'lows' => [149.0, 150.0],
            'volume' => 1200
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     */
    public function itRequiresSymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('symbol');
        
        $data = [
            'price' => 150.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0],
            'volumes' => [1000, 1100, 1200, 1300, 1400],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0],
            'volume' => 1500
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     */
    public function itAllowsCustomThreshold(): void
    {
        $strategy = new VWAPStrategy([
            'deviation_threshold' => 1.0  // More sensitive (1% instead of 2%)
        ]);
        
        $data = [
            'symbol' => 'AAPL',
            'price' => 153.0,  // 1.5% below VWAP - triggers with 1% threshold, not 2%
            'high' => 154.0,
            'low' => 152.0,
            'prices' => [150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0],
            'volumes' => [1000000, 1100000, 1200000, 1300000, 1400000, 1500000, 1600000, 1700000, 1800000, 1900000],
            'highs' => [151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0, 159.0, 160.0],
            'lows' => [149.0, 150.0, 151.0, 152.0, 153.0, 154.0, 155.0, 156.0, 157.0, 158.0],
            'volume' => 2000000
        ];
        
        $signal = $strategy->analyze($data);
        
        // With 1% threshold, should generate BUY signal
        $this->assertEquals('BUY', $signal->getAction());
    }

    /**
     * @test
     */
    public function itProvidesStrategyMetadata(): void
    {
        $metadata = $this->strategy->getMetadata();
        
        $this->assertIsArray($metadata);
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('indicators', $metadata);
        $this->assertEquals('VWAP Strategy', $metadata['name']);
    }

    /**
     * @test
     */
    public function itHandlesEqualPricesAndVolumes(): void
    {
        // Edge case: all prices and volumes the same
        $prices = [100.0, 100.0, 100.0, 100.0, 100.0];
        $volumes = [1000, 1000, 1000, 1000, 1000];
        
        $vwap = $this->strategy->calculateVWAP($prices, $volumes);
        
        // VWAP should equal the constant price
        $this->assertEqualsWithDelta(100.0, $vwap, 0.01);
    }
}
