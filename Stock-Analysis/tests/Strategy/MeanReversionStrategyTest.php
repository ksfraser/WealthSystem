<?php

declare(strict_types=1);

namespace Tests\Strategy;

use PHPUnit\Framework\TestCase;
use App\Strategy\MeanReversionStrategy;
use App\Strategy\StrategySignal;

/**
 * Test suite for MeanReversionStrategy
 * 
 * Tests mean reversion trading strategy including:
 * - Bollinger Bands calculation
 * - Price deviation from mean
 * - Oversold/overbought detection
 * - Volatility analysis
 * - Signal generation
 * 
 * @covers \App\Strategy\MeanReversionStrategy
 */
class MeanReversionStrategyTest extends TestCase
{
    private ?MeanReversionStrategy $strategy = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = [
            'period' => 20,
            'std_dev_multiplier' => 2.0,
            'oversold_threshold' => -2.0,
            'overbought_threshold' => 2.0
        ];
        
        $this->strategy = new MeanReversionStrategy($config);
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesBollingerBands(): void
    {
        $prices = $this->generatePriceData(30, 100);
        
        $bands = $this->strategy->calculateBollingerBands($prices);
        
        $this->assertArrayHasKey('middle', $bands);
        $this->assertArrayHasKey('upper', $bands);
        $this->assertArrayHasKey('lower', $bands);
        $this->assertGreaterThan($bands['middle'], $bands['upper']);
        $this->assertLessThan($bands['middle'], $bands['lower']);
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesStandardDeviation(): void
    {
        $prices = [100, 102, 98, 103, 97, 101, 99, 104, 96, 100];
        
        $stdDev = $this->strategy->calculateStandardDeviation($prices);
        
        $this->assertIsFloat($stdDev);
        $this->assertGreaterThan(0, $stdDev);
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesPriceDeviation(): void
    {
        $prices = array_merge(array_fill(0, 10, 100.0), array_fill(0, 10, 102.0));
        $currentPrice = 110.0;
        
        $deviation = $this->strategy->calculateDeviation($prices, $currentPrice);
        
        $this->assertIsFloat($deviation);
        $this->assertGreaterThan(0, $deviation); // Above mean
    }

    /**
     * @test
     * @group strategy
     */
    public function itDetectsOversoldCondition(): void
    {
        $prices = array_merge(array_fill(0, 10, 100.0), array_fill(0, 10, 102.0));
        $currentPrice = 90.0; // Significantly below mean
        
        $isOversold = $this->strategy->isOversold($prices, $currentPrice);
        
        $this->assertTrue($isOversold);
    }

    /**
     * @test
     * @group strategy
     */
    public function itDetectsOverboughtCondition(): void
    {
        $prices = array_merge(array_fill(0, 10, 100.0), array_fill(0, 10, 102.0));
        $currentPrice = 110.0; // Significantly above mean
        
        $isOverbought = $this->strategy->isOverbought($prices, $currentPrice);
        
        $this->assertTrue($isOverbought);
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesBuySignalWhenOversold(): void
    {
        $data = [
            'symbol' => 'MSFT',
            'prices' => array_merge(array_fill(0, 15, 100.0), array_fill(0, 15, 103.0)),
            'current_price' => 85.0, // Well below mean (101.5)
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('BUY', $signal->getAction());
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesSellSignalWhenOverbought(): void
    {
        $data = [
            'symbol' => 'MSFT',
            'prices' => array_merge(array_fill(0, 15, 100.0), array_fill(0, 15, 103.0)),
            'current_price' => 115.0, // Well above mean (101.5)
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('SELL', $signal->getAction());
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesHoldSignalNearMean(): void
    {
        $data = [
            'symbol' => 'MSFT',
            'prices' => array_fill(0, 30, 100.0),
            'current_price' => 101.0, // Near mean
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('HOLD', $signal->getAction());
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesVolatility(): void
    {
        $prices = $this->generateVolatilePrices(30, 100);
        
        $volatility = $this->strategy->calculateVolatility($prices);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThan(0, $volatility);
    }

    /**
     * @test
     * @group strategy
     */
    public function itAdjustsSignalStrengthByVolatility(): void
    {
        // Low volatility scenario (small stdDev)
        $lowVolData = [
            'symbol' => 'MSFT',
            'prices' => array_merge(array_fill(0, 15, 100.0), array_fill(0, 15, 101.0)), // Low volatility
            'current_price' => 85.0,
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        // High volatility scenario
        $highVolData = [
            'symbol' => 'MSFT',
            'prices' => $this->generateVolatilePrices(30, 100),
            'current_price' => 85.0,
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        $lowVolSignal = $this->strategy->analyze($lowVolData);
        $highVolSignal = $this->strategy->analyze($highVolData);
        
        // Low volatility should have higher confidence
        $this->assertGreaterThan($highVolSignal->getStrength(), $lowVolSignal->getStrength());
    }

    /**
     * @test
     * @group strategy
     */
    public function itIncludesReasonWithBollingerBands(): void
    {
        $data = [
            'symbol' => 'MSFT',
            'prices' => array_merge(array_fill(0, 15, 100.0), array_fill(0, 15, 103.0)),
            'current_price' => 85.0,
            'volumes' => array_fill(0, 30, 1000)
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $reason = $signal->getReason();
        $this->assertStringContainsString('Bollinger', $reason);
    }

    /**
     * @test
     * @group strategy
     */
    public function itValidatesMinimumDataPoints(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'symbol' => 'MSFT',
            'prices' => [100, 101, 102], // Too few
            'current_price' => 100,
            'volumes' => [1000, 1100, 1200]
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     * @group strategy
     */
    public function itHandlesCustomStdDevMultiplier(): void
    {
        $customStrategy = new MeanReversionStrategy([
            'period' => 20,
            'std_dev_multiplier' => 3.0 // Wider bands
        ]);
        
        $prices = array_merge(array_fill(0, 15, 100.0), array_fill(0, 15, 104.0));
        
        $normalBands = $this->strategy->calculateBollingerBands($prices);
        $wideBands = $customStrategy->calculateBollingerBands($prices);
        
        // Wider bands should have larger range
        $normalRange = $normalBands['upper'] - $normalBands['lower'];
        $wideRange = $wideBands['upper'] - $wideBands['lower'];
        
        $this->assertGreaterThan($normalRange, $wideRange);
    }

    /**
     * @test
     * @group strategy
     */
    public function itProvidesStrategyMetadata(): void
    {
        $metadata = $this->strategy->getMetadata();
        
        $this->assertArrayHasKey('name', $metadata);
        $this->assertArrayHasKey('description', $metadata);
        $this->assertArrayHasKey('category', $metadata);
        $this->assertEquals('Mean Reversion', $metadata['category']);
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesMeanCorrectly(): void
    {
        $prices = [100, 110, 90, 105, 95]; // Mean = 100
        
        $mean = $this->strategy->calculateMean($prices);
        
        $this->assertEquals(100.0, $mean);
    }

    // Helper methods

    private function generatePriceData(int $count, float $start): array
    {
        $prices = [];
        $price = $start;
        
        for ($i = 0; $i < $count; $i++) {
            $price += rand(-100, 100) / 100;
            $prices[] = max(1, $price);
        }
        
        return $prices;
    }

    private function generateVolatilePrices(int $count, float $mean): array
    {
        $prices = [];
        
        for ($i = 0; $i < $count; $i++) {
            $deviation = rand(-20, 20);
            $prices[] = $mean + $deviation;
        }
        
        return $prices;
    }
}
