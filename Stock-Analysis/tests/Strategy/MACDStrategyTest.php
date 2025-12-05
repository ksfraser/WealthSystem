<?php

declare(strict_types=1);

namespace Tests\Strategy;

use PHPUnit\Framework\TestCase;
use App\Strategy\MACDStrategy;
use App\Strategy\StrategySignal;

/**
 * Test suite for MACD (Moving Average Convergence Divergence) trading strategy.
 * 
 * MACD = 12-period EMA - 26-period EMA
 * Signal Line = 9-period EMA of MACD
 * Histogram = MACD - Signal Line
 */
class MACDStrategyTest extends TestCase
{
    private MACDStrategy $strategy;

    protected function setUp(): void
    {
        $this->strategy = new MACDStrategy();
    }

    /**
     * @test
     */
    public function itCalculatesEMA(): void
    {
        $prices = [100.0, 102.0, 104.0, 103.0, 105.0, 107.0, 106.0, 108.0, 110.0, 109.0];
        $period = 5;
        
        $ema = $this->strategy->calculateEMA($prices, $period);
        
        $this->assertIsFloat($ema);
        $this->assertGreaterThan(0, $ema);
        // EMA should be between min and max of recent prices
        $this->assertGreaterThan(105.0, $ema);
        $this->assertLessThan(112.0, $ema);
    }

    /**
     * @test
     */
    public function itCalculatesMACDLine(): void
    {
        $prices = array_merge(
            range(100, 130),  // 31 prices for sufficient history
            [132, 134, 133, 135]
        );
        
        $macd = $this->strategy->calculateMACDLine($prices);
        
        $this->assertIsFloat($macd);
        // MACD can be positive (bullish) or negative (bearish)
    }

    /**
     * @test
     */
    public function itCalculatesSignalLine(): void
    {
        $prices = array_merge(
            range(100, 130),
            [132, 134, 133, 135, 137, 136, 138, 140, 139, 141]
        );
        
        $signal = $this->strategy->calculateSignalLine($prices);
        
        $this->assertIsFloat($signal);
    }

    /**
     * @test
     */
    public function itCalculatesHistogram(): void
    {
        $prices = array_merge(
            range(100, 130),
            [132, 134, 133, 135, 137, 136, 138, 140, 139, 141]
        );
        
        $histogram = $this->strategy->calculateHistogram($prices);
        
        $this->assertIsFloat($histogram);
        // Histogram = MACD - Signal
        // Can be positive (MACD above signal) or negative (MACD below signal)
    }

    /**
     * @test
     */
    public function itDetectsBullishCrossover(): void
    {
        // Create uptrend: MACD crosses above signal line
        // Need 60+ data points for proper MACD calculation
        $prices = array_merge(
            array_fill(0, 35, 110.0),  // Extended flat period for baseline
            [109, 108, 107, 106, 105, 104, 103, 102, 101, 100, 99, 98, 97],  // Decline creates negative MACD
            [98, 100, 102, 105, 108, 112, 117, 123, 130, 138, 147, 157, 168]  // Strong rally crosses MACD above signal
        );
        
        // After strong rally, histogram should be strongly positive (MACD above signal)
        $histogram = $this->strategy->calculateHistogram($prices);
        
        $this->assertGreaterThan(1.0, $histogram, 'Histogram should be strongly positive after rally');
    }

    /**
     * @test
     */
    public function itDetectsBearishCrossover(): void
    {
        // Create downtrend: MACD crosses below signal line
        // Need 60+ data points for proper MACD calculation
        $prices = array_merge(
            array_fill(0, 35, 100.0),  // Extended flat period for baseline
            [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113],  // Rally creates positive MACD
            [112, 110, 107, 103, 98, 92, 85, 77, 68, 58, 47, 35, 22]  // Strong decline crosses MACD below signal
        );
        
        // After strong decline, histogram should be strongly negative (MACD below signal)
        $histogram = $this->strategy->calculateHistogram($prices);
        
        $this->assertLessThan(-1.0, $histogram, 'Histogram should be strongly negative after decline');
    }

    /**
     * @test
     */
    public function itGeneratesBuySignalOnBullishCrossover(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 168.0,
            'prices' => array_merge(
                array_fill(0, 35, 110.0),
                [109, 108, 107, 106, 105, 104, 103, 102, 101, 100, 99, 98, 97],
                [98, 100, 102, 105, 108, 112, 117, 123, 130, 138, 147, 157, 168]
            ),
            'volume' => 1000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('BUY', $signal->getAction());
        $this->assertGreaterThan(0.6, $signal->getStrength());
        $this->assertStringContainsString('MACD', $signal->getReason());
    }

    /**
     * @test
     */
    public function itGeneratesSellSignalOnBearishCrossover(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 22.0,
            'prices' => array_merge(
                array_fill(0, 35, 100.0),
                [101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113],
                [112, 110, 107, 103, 98, 92, 85, 77, 68, 58, 47, 35, 22]
            ),
            'volume' => 1000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('SELL', $signal->getAction());
        $this->assertGreaterThan(0.6, $signal->getStrength());
        $this->assertStringContainsString('MACD', $signal->getReason());
    }

    /**
     * @test
     */
    public function itGeneratesHoldSignalWithoutCrossover(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 110.0,
            'prices' => array_merge(
                array_fill(0, 30, 108.0),
                array_fill(0, 13, 110.0)  // Flat, no crossover
            ),
            'volume' => 1000000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('HOLD', $signal->getAction());
    }

    /**
     * @test
     */
    public function itAdjustsSignalStrengthByHistogram(): void
    {
        // Strong histogram (large MACD-Signal difference) = stronger signal
        $strongData = [
            'symbol' => 'AAPL',
            'price' => 185.0,
            'prices' => array_merge(
                array_fill(0, 35, 100.0),
                [99, 98, 97, 96, 95, 94, 93, 92, 91, 90, 89, 88, 87],
                [88, 91, 95, 100, 106, 113, 121, 130, 140, 151, 163, 176, 190]  // Very strong rally after decline
            ),
            'volume' => 1000000
        ];
        
        $weakData = [
            'symbol' => 'AAPL',
            'price' => 117.0,
            'prices' => array_merge(
                array_fill(0, 35, 100.0),
                [99, 98, 97, 96, 95, 94, 93, 92, 91, 90, 89, 88, 87],
                [88, 89, 91, 93, 95, 97, 100, 103, 106, 109, 112, 115, 118]  // Weak recovery
            ),
            'volume' => 1000000
        ];
        
        $strongSignal = $this->strategy->analyze($strongData);
        $weakSignal = $this->strategy->analyze($weakData);
        
        // Both should be BUY, but strong trend should have higher strength
        $this->assertEquals('BUY', $strongSignal->getAction());
        $this->assertEquals('BUY', $weakSignal->getAction());
        $this->assertGreaterThan($weakSignal->getStrength(), $strongSignal->getStrength());
    }

    /**
     * @test
     */
    public function itIncludesMACDMetadata(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'price' => 124.0,
            'prices' => array_merge(
                array_fill(0, 30, 100.0),
                [101, 102, 104, 106, 108, 110, 112, 114, 116, 118, 120, 122, 124]
            ),
            'volume' => 1000000
        ];
        
        $signal = $this->strategy->analyze($data);
        $metadata = $signal->getMetadata();
        
        $this->assertArrayHasKey('macd', $metadata);
        $this->assertArrayHasKey('signal', $metadata);
        $this->assertArrayHasKey('histogram', $metadata);
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
            'price' => 100.0,
            'prices' => [100, 101, 102],  // Only 3 points, needs 35+ for MACD calculation
            'volume' => 1000
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
            'price' => 100.0,
            'prices' => array_fill(0, 50, 100.0),
            'volume' => 1000
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     */
    public function itAllowsCustomPeriods(): void
    {
        $strategy = new MACDStrategy([
            'fast_period' => 8,   // Faster response (default 12)
            'slow_period' => 20,  // Faster response (default 26)
            'signal_period' => 7  // Faster response (default 9)
        ]);
        
        $config = $strategy->getConfiguration();
        
        $this->assertEquals(8, $config['fast_period']);
        $this->assertEquals(20, $config['slow_period']);
        $this->assertEquals(7, $config['signal_period']);
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
        $this->assertEquals('MACD Strategy', $metadata['name']);
        $this->assertContains('MACD', $metadata['indicators']);
        $this->assertContains('Signal Line', $metadata['indicators']);
        $this->assertContains('Histogram', $metadata['indicators']);
    }
}
