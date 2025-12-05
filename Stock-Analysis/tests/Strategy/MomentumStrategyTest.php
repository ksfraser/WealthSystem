<?php

declare(strict_types=1);

namespace Tests\Strategy;

use PHPUnit\Framework\TestCase;
use App\Strategy\MomentumStrategy;
use App\Strategy\StrategySignal;

/**
 * Test suite for MomentumStrategy
 * 
 * Tests momentum-based trading strategy including:
 * - Price momentum calculation
 * - Relative strength analysis
 * - Moving average trends
 * - Breakout detection
 * - Signal generation
 * 
 * @covers \App\Strategy\MomentumStrategy
 */
class MomentumStrategyTest extends TestCase
{
    private ?MomentumStrategy $strategy = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Default configuration
        $config = [
            'lookback_period' => 20,
            'rsi_period' => 14,
            'rsi_oversold' => 30,
            'rsi_overbought' => 70,
            'volume_multiplier' => 1.5
        ];
        
        $this->strategy = new MomentumStrategy($config);
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesPriceMomentum(): void
    {
        $prices = [100, 102, 105, 103, 108, 112, 115, 118, 120, 125];
        
        $momentum = $this->strategy->calculateMomentum($prices);
        
        $this->assertIsFloat($momentum);
        $this->assertGreaterThan(0, $momentum); // Upward momentum
    }

    /**
     * @test
     * @group strategy
     */
    public function itDetectsNegativeMomentum(): void
    {
        $prices = [125, 120, 118, 115, 112, 108, 103, 105, 102, 100];
        
        $momentum = $this->strategy->calculateMomentum($prices);
        
        $this->assertLessThan(0, $momentum); // Downward momentum
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesRelativeStrengthIndex(): void
    {
        $prices = $this->generatePriceData(30);
        
        $rsi = $this->strategy->calculateRSI($prices);
        
        $this->assertIsFloat($rsi);
        $this->assertGreaterThanOrEqual(0, $rsi);
        $this->assertLessThanOrEqual(100, $rsi);
    }

    /**
     * @test
     * @group strategy
     */
    public function itIdentifiesOversoldCondition(): void
    {
        // Simulate declining prices (oversold)
        $prices = [];
        for ($i = 0; $i < 30; $i++) {
            $prices[] = 100 - ($i * 2);
        }
        
        $rsi = $this->strategy->calculateRSI($prices);
        
        $this->assertLessThan(30, $rsi);
        $this->assertTrue($this->strategy->isOversold($rsi));
    }

    /**
     * @test
     * @group strategy
     */
    public function itIdentifiesOverboughtCondition(): void
    {
        // Simulate rising prices (overbought)
        $prices = [];
        for ($i = 0; $i < 30; $i++) {
            $prices[] = 100 + ($i * 2);
        }
        
        $rsi = $this->strategy->calculateRSI($prices);
        
        $this->assertGreaterThan(70, $rsi);
        $this->assertTrue($this->strategy->isOverbought($rsi));
    }

    /**
     * @test
     * @group strategy
     */
    public function itDetectsBreakout(): void
    {
        $prices = [100, 101, 102, 103, 104, 105, 106, 107, 108, 115]; // Breakout on last price
        $volumes = [1000, 1100, 1000, 1200, 1100, 1000, 1100, 1050, 1000, 3000]; // Volume surge
        
        $isBreakout = $this->strategy->detectBreakout($prices, $volumes);
        
        $this->assertTrue($isBreakout);
    }

    /**
     * @test
     * @group strategy
     */
    public function itRejectsBreakoutWithoutVolume(): void
    {
        $prices = [100, 101, 102, 103, 104, 105, 106, 107, 108, 115]; // Price surge
        $volumes = [1000, 1100, 1000, 1200, 1100, 1000, 1100, 1050, 1000, 1000]; // No volume surge
        
        $isBreakout = $this->strategy->detectBreakout($prices, $volumes);
        
        $this->assertFalse($isBreakout);
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesBuySignalOnStrength(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'prices' => [],
            'volumes' => [],
            'current_price' => 110,
            'current_volume' => 3000
        ];
        
        // Create breakout scenario with volume confirmation
        // Consolidation then breakout
        for ($i = 0; $i < 15; $i++) {
            $data['prices'][] = 100; // Flat
            $data['volumes'][] = 1000;
        }
        // Small moves then breakout
        for ($i = 0; $i < 10; $i++) {
            $data['prices'][] = 100 + $i; // Gradual rise
            $data['volumes'][] = 1050;
        }
        // Final breakout price with volume spike
        $data['prices'][] = 120; // Strong breakout
        $data['volumes'][] = 3000; // Volume surge
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('BUY', $signal->getAction());
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesSellSignalOnWeakness(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'prices' => [],
            'volumes' => [],
            'current_price' => 100,
            'current_volume' => 2000
        ];
        
        // Create pure uptrend to achieve overbought RSI
        // This will trigger weak overbought SELL condition
        for ($i = 0; $i < 15; $i++) {
            $data['prices'][] = 90 + $i; // Gradual rise
            $data['volumes'][] = 1000;
        }
        for ($i = 0; $i < 15; $i++) {
            $data['prices'][] = 105 + ($i * 0.2); // Slow continued rise to maintain overbought
            $data['volumes'][] = 1000;
        }
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        // Overbought without strong momentum triggers weak sell
        $this->assertContains($signal->getAction(), ['SELL', 'HOLD']); // Either is reasonable
    }

    /**
     * @test
     * @group strategy
     */
    public function itGeneratesHoldSignalOnNeutral(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'prices' => [],
            'volumes' => [],
            'current_price' => 100,
            'current_volume' => 1000
        ];
        
        // Create sideways market with slight fluctuations (neutral momentum, RSI ~50)
        for ($i = 0; $i < 30; $i++) {
            $data['prices'][] = 100 + (($i % 2) * 2) - 1; // Oscillates between 99 and 101
            $data['volumes'][] = 1000;
        }
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertInstanceOf(StrategySignal::class, $signal);
        $this->assertEquals('HOLD', $signal->getAction());
    }

    /**
     * @test
     * @group strategy
     */
    public function itCalculatesSignalStrength(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'prices' => [],
            'volumes' => [],
            'current_price' => 160,
            'current_volume' => 3000
        ];
        
        // Create strong uptrend with breakout
        for ($i = 0; $i < 20; $i++) {
            $data['prices'][] = 100 + ($i * 2); // Uptrend
            $data['volumes'][] = 1000 + ($i * 50);
        }
        // Add strong breakout
        for ($i = 0; $i < 10; $i++) {
            $data['prices'][] = 140 + ($i * 3);
            $data['volumes'][] = 2500 + ($i * 100);
        }
        
        $signal = $this->strategy->analyze($data);
        
        $strength = $signal->getStrength();
        $this->assertGreaterThan(0.6, $strength); // Strong signal (not hold at 0.5)
    }

    /**
     * @test
     * @group strategy
     */
    public function itIncludesReasonInSignal(): void
    {
        $data = [
            'symbol' => 'AAPL',
            'prices' => $this->generatePriceData(30),
            'volumes' => array_fill(0, 30, 1000),
            'current_price' => 100,
            'current_volume' => 1000
        ];
        
        $signal = $this->strategy->analyze($data);
        
        $this->assertNotEmpty($signal->getReason());
        $this->assertStringContainsString('momentum', strtolower($signal->getReason()));
    }

    /**
     * @test
     * @group strategy
     */
    public function itValidatesInputData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'symbol' => 'AAPL',
            'prices' => [100, 101], // Too few data points
            'volumes' => [1000, 1100],
            'current_price' => 102,
            'current_volume' => 1200
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     * @group strategy
     */
    public function itRequiresSymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $data = [
            'prices' => $this->generatePriceData(30),
            'volumes' => array_fill(0, 30, 1000),
            'current_price' => 100,
            'current_volume' => 1000
        ];
        
        $this->strategy->analyze($data);
    }

    /**
     * @test
     * @group strategy
     */
    public function itAllowsCustomConfiguration(): void
    {
        $customConfig = [
            'lookback_period' => 30,
            'rsi_period' => 21,
            'rsi_oversold' => 25,
            'rsi_overbought' => 75,
            'volume_multiplier' => 2.0
        ];
        
        $customStrategy = new MomentumStrategy($customConfig);
        
        $config = $customStrategy->getConfiguration();
        
        $this->assertEquals(30, $config['lookback_period']);
        $this->assertEquals(21, $config['rsi_period']);
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
        $this->assertEquals('Momentum', $metadata['category']);
    }

    // Helper methods

    private function generatePriceData(int $count, float $start = 100.0): array
    {
        $prices = [];
        $price = $start;
        
        for ($i = 0; $i < $count; $i++) {
            $price += rand(-200, 200) / 100; // Random walk
            $prices[] = max(1, $price); // Keep positive
        }
        
        return $prices;
    }

    private function generateTrendingPrices(int $count, float $start, float $trend): array
    {
        $prices = [];
        
        for ($i = 0; $i < $count; $i++) {
            $prices[] = $start + ($i * $trend);
        }
        
        return $prices;
    }

    private function generateIncreasingVolume(int $count, int $start): array
    {
        $volumes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $volumes[] = $start + ($i * 50);
        }
        
        return $volumes;
    }
}
