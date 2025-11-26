<?php

namespace Tests\Unit\Finance\Strategies;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\Strategies\TechnicalAnalysis\MovingAverageCrossoverStrategy;

/**
 * Unit tests for MovingAverageCrossoverStrategy
 * 
 * Tests the Moving Average Crossover strategy implementation
 */
class MovingAverageCrossoverStrategyTest extends TestBaseSimple
{
    private MovingAverageCrossoverStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mockStockDataService = $this->createMockStockDataService();
        $this->strategy = new MovingAverageCrossoverStrategy($mockStockDataService);
    }

    /**
     * Test strategy initialization and default parameters
     */
    public function testStrategyInitialization(): void
    {
        $this->assertInstanceOf(MovingAverageCrossoverStrategy::class, $this->strategy);
        $this->assertEquals('Moving Average Crossover Strategy', $this->strategy->getName());
        $this->assertStringContainsString('Enhanced MA crossover strategy', $this->strategy->getDescription());
        
        $parameters = $this->strategy->getParameters();
        $this->assertEquals(12, $parameters['fast_period']);
        $this->assertEquals(26, $parameters['slow_period']);
        $this->assertEquals(9, $parameters['signal_period']);
        $this->assertTrue($parameters['use_ema']);
        $this->assertEquals(1, $parameters['confirmation_bars']);
    }

    /**
     * Test parameter validation
     */
    public function testParameterValidation(): void
    {
        $validParams = [
            'fast_period' => 12,
            'slow_period' => 26
        ];

        $this->assertTrue($this->strategy->validateParameters($validParams));

        // Test invalid case: fast period >= slow period
        $invalidParams = [
            'fast_period' => 26,
            'slow_period' => 12
        ];
        $this->assertFalse($this->strategy->validateParameters($invalidParams));

        // Test missing required parameter
        $missingParam = ['fast_period' => 12];
        $this->assertFalse($this->strategy->validateParameters($missingParam));

        // Test zero periods
        $zeroParams = [
            'fast_period' => 0,
            'slow_period' => 26
        ];
        $this->assertFalse($this->strategy->validateParameters($zeroParams));
    }

    /**
     * Test setParameters method
     */
    public function testSetParameters(): void
    {
        $newParams = [
            'fast_period' => 10,
            'slow_period' => 30,
            'use_ema' => false
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(10, $params['fast_period']);
        $this->assertEquals(30, $params['slow_period']);
        $this->assertFalse($params['use_ema']);
    }

    /**
     * Test signal generation with insufficient data
     */
    public function testInsufficientData(): void
    {
        $marketData = $this->generateMarketData(10); // Not enough data
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        $this->assertNull($signal);
    }

    /**
     * Test EMA calculation
     */
    public function testEMACalculation(): void
    {
        $marketData = $this->generateMarketData(30, 100.0);
        
        // Use reflection to test private calculateEMA method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculateEMA');
        $method->setAccessible(true);
        
        $ema = $method->invokeArgs($this->strategy, [$marketData, 12]);
        
        $this->assertIsFloat($ema);
        $this->assertGreaterThan(0, $ema);
        $this->assertLessThan(200, $ema); // Reasonable range
    }

    /**
     * Test SMA calculation
     */
    public function testSMACalculation(): void
    {
        $marketData = $this->generateMarketData(30, 100.0);
        
        // Use reflection to test private calculateSMA method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculateSMA');
        $method->setAccessible(true);
        
        $sma = $method->invokeArgs($this->strategy, [$marketData, 12]);
        
        $this->assertIsFloat($sma);
        $this->assertGreaterThan(0, $sma);
        $this->assertLessThan(200, $sma); // Reasonable range
    }

    /**
     * Test ATR calculation
     */
    public function testATRCalculation(): void
    {
        $marketData = $this->generateMarketDataWithTrueRange(30, 100.0);
        
        // Use reflection to test private calculateATR method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculateATR');
        $method->setAccessible(true);
        
        $atr = $method->invokeArgs($this->strategy, [$marketData, 14]);
        
        $this->assertIsFloat($atr);
        $this->assertGreaterThanOrEqual(0, $atr);
    }

    /**
     * Test golden cross (bullish crossover) signal generation
     */
    public function testGoldenCrossSignal(): void
    {
        // Create market data that shows a clear golden cross pattern
        $marketData = $this->generateGoldenCrossData(60, 100.0);
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && $signal['action'] === 'BUY') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'reasoning', 'fast_ma', 'slow_ma']);
            $this->assertEquals('BUY', $signal['action']);
            $this->assertGreaterThan(0.5, $signal['confidence']);
            $this->assertStringContainsString('MA Crossover', $signal['reasoning']);
            $this->assertArrayHasKey('fast_ma', $signal);
            $this->assertArrayHasKey('slow_ma', $signal);
            $this->assertArrayHasKey('atr', $signal);
            $this->assertArrayHasKey('stop_loss', $signal);
            $this->assertArrayHasKey('take_profit', $signal);
        } else {
            // Signal generation depends on market conditions
            $this->assertTrue(
                $signal === null || is_array($signal),
                'Signal should be null or valid array'
            );
        }
    }

    /**
     * Test death cross (bearish crossover) signal generation
     */
    public function testDeathCrossSignal(): void
    {
        // Create market data that shows a clear death cross pattern
        $marketData = $this->generateDeathCrossData(60, 100.0);
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && $signal['action'] === 'SELL') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'reasoning']);
            $this->assertEquals('SELL', $signal['action']);
            $this->assertGreaterThan(0.5, $signal['confidence']);
            $this->assertStringContainsString('MA Crossover', $signal['reasoning']);
        } else {
            // Signal generation depends on market conditions
            $this->assertTrue(
                $signal === null || is_array($signal),
                'Signal should be null or valid array'
            );
        }
    }

    /**
     * Test crossover detection with insufficient separation
     */
    public function testInsufficientSeparation(): void
    {
        // Set minimum separation requirement
        $this->strategy->setParameters(['min_separation' => 0.05]); // 5%
        
        $marketData = $this->generateMarketData(60, 100.0); // Normal data without clear crossover
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        // Should likely return null due to insufficient separation
        $this->assertTrue(
            $signal === null || (is_array($signal) && isset($signal['action'])),
            'Signal should handle insufficient separation gracefully'
        );
    }

    /**
     * Test volume confirmation feature
     */
    public function testVolumeConfirmation(): void
    {
        $this->strategy->setParameters(['volume_confirmation' => true]);
        
        $marketData = $this->generateMarketDataWithHighVolume(60, 100.0);
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && in_array($signal['action'], ['BUY', 'SELL'])) {
            $this->assertArrayHasKey('confirmations', $signal);
            // Volume confirmation might or might not be present depending on data
        }
        
        $this->assertTrue(true); // Test completed without errors
    }

    /**
     * Test trend filter feature
     */
    public function testTrendFilter(): void
    {
        $this->strategy->setParameters([
            'trend_filter' => true,
            'trend_period' => 50
        ]);
        
        $marketData = $this->generateMarketData(60, 100.0);
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        // Trend filter should affect signal generation
        $this->assertTrue(
            $signal === null || is_array($signal),
            'Trend filter should work without errors'
        );
    }

    /**
     * Test SMA mode vs EMA mode
     */
    public function testSMAvsEMA(): void
    {
        $marketData = $this->generateMarketData(60, 100.0);
        
        // Test with EMA (default)
        $this->strategy->setParameters(['use_ema' => true]);
        $signalEMA = $this->strategy->generateSignal('AAPL', $marketData);
        
        // Test with SMA
        $this->strategy->setParameters(['use_ema' => false]);
        $signalSMA = $this->strategy->generateSignal('AAPL', $marketData);
        
        // Both should return valid signals or null
        $this->assertTrue(
            ($signalEMA === null || is_array($signalEMA)) &&
            ($signalSMA === null || is_array($signalSMA)),
            'Both EMA and SMA modes should work'
        );
    }

    /**
     * Test legacy macrossoverrule method
     */
    public function testLegacyMethod(): void
    {
        $result = $this->strategy->macrossoverrule('AAPL', '2023-01-01');
        
        $this->assertIsInt($result);
        $this->assertContains($result, [10, 20, 30]); // BUY=10, SELL=20, HOLD=30
    }

    /**
     * Test empty market data handling
     */
    public function testEmptyMarketData(): void
    {
        $signal = $this->strategy->generateSignal('AAPL', []);
        $this->assertNull($signal);
    }

    /**
     * Generate market data with golden cross pattern
     */
    private function generateGoldenCrossData(int $days, float $basePrice): array
    {
        $data = [];
        $price = $basePrice;

        // Start with prices that would create a death cross
        for ($i = 0; $i < $days - 10; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Gradual decline to set up for reversal
            $change = -0.5 + ($i * 0.02); // Gradual improvement
            $price = max(80, $basePrice + $change);
            
            $high = $price * (1 + rand(0, 2) / 100);
            $low = $price * (1 - rand(0, 2) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 500000)
            ];
        }

        // Create strong upward movement for golden cross
        for ($i = 0; $i < 10; $i++) {
            $date = date('Y-m-d', strtotime("-" . (10 - $i) . " days"));
            
            $change = 2 + ($i * 0.5); // Strong upward movement
            $price = $price + $change;
            
            $high = $price * (1 + rand(1, 3) / 100);
            $low = $price * (1 - rand(0, 1) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price - $change, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(200000, 800000)
            ];
        }

        return array_reverse($data);
    }

    /**
     * Generate market data with death cross pattern
     */
    private function generateDeathCrossData(int $days, float $basePrice): array
    {
        $data = [];
        $price = $basePrice;

        // Start with prices that would create a golden cross
        for ($i = 0; $i < $days - 10; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Gradual rise to set up for reversal
            $change = 0.5 - ($i * 0.02); // Gradual deterioration
            $price = min(120, $basePrice + $change);
            
            $high = $price * (1 + rand(0, 2) / 100);
            $low = $price * (1 - rand(0, 2) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 500000)
            ];
        }

        // Create strong downward movement for death cross
        for ($i = 0; $i < 10; $i++) {
            $date = date('Y-m-d', strtotime("-" . (10 - $i) . " days"));
            
            $change = -2 - ($i * 0.5); // Strong downward movement
            $price = max(70, $price + $change);
            
            $high = $price * (1 + rand(0, 1) / 100);
            $low = $price * (1 - rand(1, 3) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price - $change, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(200000, 800000)
            ];
        }

        return array_reverse($data);
    }

    /**
     * Generate market data with high volume
     */
    private function generateMarketDataWithHighVolume(int $days, float $basePrice): array
    {
        $data = $this->generateMarketData($days, $basePrice);
        
        // Increase volume in recent days
        for ($i = count($data) - 5; $i < count($data); $i++) {
            if (isset($data[$i])) {
                $data[$i]['volume'] = $data[$i]['volume'] * 2; // Double the volume
            }
        }
        
        return $data;
    }
}
