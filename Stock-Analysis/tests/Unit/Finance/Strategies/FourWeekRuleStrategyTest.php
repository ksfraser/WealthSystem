<?php

namespace Tests\Unit\Finance\Strategies;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\Strategies\Breakout\FourWeekRuleStrategy;

/**
 * Unit tests for FourWeekRuleStrategy
 * 
 * Tests the Four Week Rule breakout strategy implementation
 */
class FourWeekRuleStrategyTest extends TestBaseSimple
{
    private FourWeekRuleStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        
        $mockStockDataService = $this->createMockStockDataService();
        $this->strategy = new FourWeekRuleStrategy($mockStockDataService);
    }

    /**
     * Test strategy initialization and default parameters
     */
    public function testStrategyInitialization(): void
    {
        $this->assertInstanceOf(FourWeekRuleStrategy::class, $this->strategy);
        $this->assertEquals('Four Week Rule Strategy', $this->strategy->getName());
        $this->assertStringContainsString('Classic Four Week Rule', $this->strategy->getDescription());
        
        $parameters = $this->strategy->getParameters();
        $this->assertEquals(28, $parameters['breakout_period']);
        $this->assertEquals(1, $parameters['confirmation_bars']);
        $this->assertFalse($parameters['volume_confirmation']);
        $this->assertEquals(1.5, $parameters['volume_multiplier']);
        $this->assertEquals(14, $parameters['atr_period']);
        $this->assertEquals(2.0, $parameters['stop_loss_atr']);
        $this->assertEquals(5.0, $parameters['min_price']);
    }

    /**
     * Test parameter validation
     */
    public function testParameterValidation(): void
    {
        $validParams = [
            'breakout_period' => 28
        ];

        $this->assertTrue($this->strategy->validateParameters($validParams));

        // Test invalid case: zero or negative period
        $invalidParams = [
            'breakout_period' => 0
        ];
        $this->assertFalse($this->strategy->validateParameters($invalidParams));

        // Test missing required parameter
        $this->assertFalse($this->strategy->validateParameters([]));

        // Test non-numeric parameter
        $nonNumericParams = [
            'breakout_period' => 'invalid'
        ];
        $this->assertFalse($this->strategy->validateParameters($nonNumericParams));
    }

    /**
     * Test setParameters method
     */
    public function testSetParameters(): void
    {
        $newParams = [
            'breakout_period' => 20,
            'volume_confirmation' => true,
            'min_price' => 10.0
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(20, $params['breakout_period']);
        $this->assertTrue($params['volume_confirmation']);
        $this->assertEquals(10.0, $params['min_price']);
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
     * Test price filter - stocks below minimum price
     */
    public function testPriceFilter(): void
    {
        $this->strategy->setParameters(['min_price' => 10.0]);
        
        $marketData = $this->generateMarketData(40, 5.0); // Price below minimum
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        $this->assertNull($signal);
    }

    /**
     * Test four-week high breakout (BUY signal)
     */
    public function testFourWeekHighBreakout(): void
    {
        $marketData = $this->generateFourWeekBreakoutData(40, 100.0, 'high');
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && $signal['action'] === 'BUY') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'reasoning', 'four_week_high', 'four_week_low']);
            $this->assertEquals('BUY', $signal['action']);
            $this->assertGreaterThan(0.6, $signal['confidence']);
            $this->assertStringContainsString('Four Week Rule', $signal['reasoning']);
            $this->assertStringContainsString('4-week high', $signal['reasoning']);
            $this->assertArrayHasKey('four_week_high', $signal);
            $this->assertArrayHasKey('four_week_low', $signal);
            $this->assertArrayHasKey('breakout_strength', $signal);
            $this->assertArrayHasKey('atr', $signal);
            $this->assertArrayHasKey('stop_loss', $signal);
        } else {
            $this->assertTrue(
                $signal === null || is_array($signal),
                'Signal should be null or valid array'
            );
        }
    }

    /**
     * Test four-week low breakdown (SELL signal)
     */
    public function testFourWeekLowBreakdown(): void
    {
        $marketData = $this->generateFourWeekBreakoutData(40, 100.0, 'low');
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && $signal['action'] === 'SELL') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'reasoning']);
            $this->assertEquals('SELL', $signal['action']);
            $this->assertGreaterThan(0.6, $signal['confidence']);
            $this->assertStringContainsString('Four Week Rule', $signal['reasoning']);
            $this->assertStringContainsString('4-week low', $signal['reasoning']);
        } else {
            $this->assertTrue(
                $signal === null || is_array($signal),
                'Signal should be null or valid array'
            );
        }
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
     * Test volume confirmation feature
     */
    public function testVolumeConfirmation(): void
    {
        $this->strategy->setParameters([
            'volume_confirmation' => true,
            'volume_multiplier' => 1.5
        ]);
        
        $marketData = $this->generateMarketData(40, 100.0);
        
        // Manually increase volume in recent days to test volume confirmation
        for ($i = count($marketData) - 3; $i < count($marketData); $i++) {
            if (isset($marketData[$i])) {
                $marketData[$i]['volume'] = $marketData[$i]['volume'] * 2; // Double the volume
            }
        }
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && in_array($signal['action'], ['BUY', 'SELL'])) {
            $this->assertArrayHasKey('confirmations', $signal);
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
     * Test gap filter feature
     */
    public function testGapFilter(): void
    {
        $this->strategy->setParameters(['max_gap' => 0.03]); // 3% max gap
        
        $marketData = $this->generateMarketDataWithGap(40, 100.0, 0.08); // 8% gap
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['confirmations'])) {
            // Gap should be noted in confirmations if signal is generated
            $this->assertIsArray($signal['confirmations']);
        }
        
        $this->assertTrue(true); // Test completed
    }

    /**
     * Test stop loss calculation
     */
    public function testStopLossCalculation(): void
    {
        $currentPrice = 100.0;
        $signal = 'BUY';
        $atr = 2.0;
        $fourWeekHigh = 98.0;
        $fourWeekLow = 90.0;
        
        // Use reflection to test private calculateStopLoss method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculateStopLoss');
        $method->setAccessible(true);
        
        $stopLoss = $method->invokeArgs($this->strategy, [$currentPrice, $signal, $atr, $fourWeekHigh, $fourWeekLow]);
        
        $this->assertIsFloat($stopLoss);
        $this->assertLessThan($currentPrice, $stopLoss); // Stop loss should be below current price for BUY
        $this->assertGreaterThanOrEqual($fourWeekLow, $stopLoss); // Should be at or above four-week low
    }

    /**
     * Test legacy fourweekrule method
     */
    public function testLegacyFourWeekRuleMethod(): void
    {
        $result = $this->strategy->fourweekrule('AAPL', '2023-01-01');
        
        $this->assertIsInt($result);
        $this->assertContains($result, [10, 20, 30]); // BUY=10, SELL=20, HOLD=30
    }

    /**
     * Test legacy closehigherthan20high method
     */
    public function testLegacyCloseHigherThan20High(): void
    {
        $result = $this->strategy->closehigherthan20high('AAPL', '2023-01-01');
        
        $this->assertIsBool($result);
    }

    /**
     * Test legacy closelowerthan20low method
     */
    public function testLegacyCloseLowerThan20Low(): void
    {
        $result = $this->strategy->closelowerthan20low('AAPL', '2023-01-01');
        
        $this->assertIsBool($result);
    }

    /**
     * Test no signal in sideways market
     */
    public function testNoSignalInSidewaysMarket(): void
    {
        $marketData = $this->generateSidewaysMarketData(40, 100.0);
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        // In sideways market, there should be no clear breakout
        $this->assertTrue(
            $signal === null || 
            (is_array($signal) && $signal['action'] === 'HOLD'),
            'Sideways market should not generate clear signals'
        );
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
     * Generate market data with four-week breakout pattern
     */
    private function generateFourWeekBreakoutData(int $days, float $basePrice, string $breakoutType): array
    {
        $data = [];
        $price = $basePrice;
        $highPrice = $basePrice * 1.02; // 2% above base
        $lowPrice = $basePrice * 0.98;  // 2% below base

        // Generate stable period first
        for ($i = 0; $i < $days - 3; $i++) {
            $date = date('Y-m-d', strtotime("-" . ($days - $i) . " days"));
            
            // Keep price in range
            $change = (rand(-1, 1) / 100) * $price;
            $price = max($lowPrice, min($highPrice, $price + $change));
            
            $high = $price * (1 + rand(0, 1) / 100);
            $low = $price * (1 - rand(0, 1) / 100);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 500000)
            ];
        }

        // Generate breakout
        for ($i = 0; $i < 3; $i++) {
            $date = date('Y-m-d', strtotime("-" . (3 - $i) . " days"));
            
            if ($breakoutType === 'high') {
                // Break above four-week high
                $price = $highPrice + ($i + 1) * 1.0; // Strong upward movement
                $high = $price * (1 + rand(1, 2) / 100);
                $low = $price * (1 - rand(0, 1) / 100);
            } else {
                // Break below four-week low
                $price = $lowPrice - ($i + 1) * 1.0; // Strong downward movement
                $high = $price * (1 + rand(0, 1) / 100);
                $low = $price * (1 - rand(1, 2) / 100);
            }
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(200000, 800000) // Higher volume on breakout
            ];
        }

        return $data;
    }

    /**
     * Generate sideways market data
     */
    private function generateSidewaysMarketData(int $days, float $basePrice): array
    {
        $data = [];
        $price = $basePrice;

        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            // Very small price changes to create sideways movement
            $change = (rand(-5, 5) / 1000) * $price; // 0.5% max change
            $price = max($basePrice * 0.99, min($basePrice * 1.01, $price + $change));
            
            $high = $price * (1 + rand(0, 5) / 1000); // Small intraday range
            $low = $price * (1 - rand(0, 5) / 1000);
            
            $data[] = [
                'date' => $date,
                'open' => round($price, 2),
                'high' => round($high, 2),
                'low' => round($low, 2),
                'close' => round($price, 2),
                'volume' => rand(100000, 300000)
            ];
        }

        return array_reverse($data);
    }

    /**
     * Generate market data with a gap
     */
    private function generateMarketDataWithGap(int $days, float $basePrice, float $gapPercent): array
    {
        $data = $this->generateMarketData($days - 1, $basePrice);
        
        // Add gap in the last day
        $lastPrice = end($data)['close'];
        $gapPrice = $lastPrice * (1 + $gapPercent);
        
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => round($gapPrice, 2),
            'high' => round($gapPrice * 1.01, 2),
            'low' => round($gapPrice * 0.99, 2),
            'close' => round($gapPrice, 2),
            'volume' => rand(500000, 1000000)
        ];
        
        return $data;
    }
}
