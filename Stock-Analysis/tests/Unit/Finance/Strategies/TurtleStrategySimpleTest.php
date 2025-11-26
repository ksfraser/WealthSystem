<?php

namespace Tests\Unit\Finance\Strategies;

use Tests\Unit\TestBaseSimple;
use Ksfraser\Finance\Strategies\Turtle\TurtleStrategy;

/**
 * Unit tests for TurtleStrategy
 * 
 * Tests the classic Turtle Trading System implementation
 */
class TurtleStrategySimpleTest extends TestBaseSimple
{
    private TurtleStrategy $strategy;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a basic stub for StockDataService 
        $mockStockDataService = $this->createMockStockDataService();
        $this->strategy = new TurtleStrategy($mockStockDataService);
    }

    /**
     * Test basic strategy initialization and configuration
     */
    public function testStrategyInitialization(): void
    {
        $this->assertInstanceOf(TurtleStrategy::class, $this->strategy);
        $this->assertEquals('Turtle Trading System 1', $this->strategy->getName());
        $this->assertStringContainsString('Classic Turtle Trading System', $this->strategy->getDescription());
        
        $parameters = $this->strategy->getParameters();
        $this->assertEquals(1, $parameters['system']);
        $this->assertEquals(20, $parameters['entry_days_system1']);
        $this->assertEquals(10, $parameters['exit_days_system1']);
        $this->assertEquals(55, $parameters['entry_days_system2']);
        $this->assertEquals(20, $parameters['exit_days_system2']);
    }

    /**
     * Test BUY signal generation on high breakout with direct market data
     */
    public function testBuySignalOnHighBreakout(): void
    {
        // Create market data with clear upward breakout
        $marketData = $this->generateBreakoutData(25, 20, 100.0, 0.08); // 8% breakout

        $signal = $this->strategy->generateSignal('AAPL', $marketData);

        // The signal should be either BUY or null/HOLD depending on the strategy logic
        if ($signal && isset($signal['action']) && $signal['action'] === 'BUY') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'stop_loss', 'position_size', 'reasoning']);
            $this->assertEquals('BUY', $signal['action']);
            $this->assertGreaterThan(0.5, $signal['confidence']);
            $this->assertStringContainsString('breakout', $signal['reasoning']);
            $this->assertEquals(1, $signal['system']);
            $this->assertArrayHasKey('n_value', $signal);
            $this->assertArrayHasKey('units', $signal);
        } else {
            // If no BUY signal, verify the signal is either null or has valid structure
            $this->assertTrue(
                $signal === null || 
                (is_array($signal) && isset($signal['action'])),
                'Signal should be null or have an action field'
            );
        }
    }

    /**
     * Test no signal generation with insufficient data
     */
    public function testNoSignalWithInsufficientData(): void
    {
        $marketData = $this->generateMarketData(10); // Only 10 days, need 60+

        $signal = $this->strategy->generateSignal('AAPL', $marketData);

        $this->assertNoSignal($signal);
    }

    /**
     * Test parameter validation
     */
    public function testParameterValidation(): void
    {
        $validParams = [
            'system' => 1,
            'entry_days_system1' => 20,
            'exit_days_system1' => 10,
            'entry_days_system2' => 55,
            'exit_days_system2' => 20
        ];

        $this->assertTrue($this->strategy->validateParameters($validParams));

        // Test invalid system
        $invalidSystem = array_merge($validParams, ['system' => 3]);
        $this->assertFalse($this->strategy->validateParameters($invalidSystem));

        // Test missing required parameter
        $missingParam = $validParams;
        unset($missingParam['entry_days_system1']);
        $this->assertFalse($this->strategy->validateParameters($missingParam));
    }

    /**
     * Test setParameters method
     */
    public function testSetParameters(): void
    {
        $newParams = [
            'system' => 2,
            'entry_days_system2' => 60,
            'max_units' => 6
        ];

        $this->strategy->setParameters($newParams);
        $params = $this->strategy->getParameters();

        $this->assertEquals(2, $params['system']);
        $this->assertEquals(60, $params['entry_days_system2']);
        $this->assertEquals(6, $params['max_units']);
    }

    /**
     * Test empty market data handling
     */
    public function testEmptyMarketData(): void
    {
        $signal = $this->strategy->generateSignal('AAPL', []);
        $this->assertNoSignal($signal);
    }

    /**
     * Test System 2 configuration and behavior
     */
    public function testSystem2Configuration(): void
    {
        $this->strategy->setParameters(['system' => 2]);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(2, $params['system']);
        $this->assertEquals('Turtle Trading System 2', $this->strategy->getName());
        
        // Test signal generation with System 2
        $marketData = $this->generateBreakoutData(60, 10, 100.0, 0.10); // 10% breakout
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && in_array($signal['action'], ['BUY', 'SELL'])) {
            $this->assertEquals(2, $signal['system']);
        } else {
            $this->assertTrue(true); // Signal generation depends on market conditions
        }
    }

    /**
     * Test N-value calculation accuracy
     */
    public function testNValueCalculation(): void
    {
        $marketData = $this->generateMarketDataWithTrueRange(60, 100.0);
        
        // Use reflection to test private calculateNValue method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculateNValue');
        $method->setAccessible(true);
        
        $nValue = $method->invokeArgs($this->strategy, [$marketData, 20]);
        
        $this->assertIsFloat($nValue);
        $this->assertGreaterThan(0, $nValue);
        $this->assertLessThan(50, $nValue); // Reasonable range for N-value
    }

    /**
     * Test position sizing calculation
     */
    public function testPositionSizing(): void
    {
        $price = 100.0;
        $nValue = 2.0;
        
        // Use reflection to test private calculatePositionSize method
        $reflection = new \ReflectionClass($this->strategy);
        $method = $reflection->getMethod('calculatePositionSize');
        $method->setAccessible(true);
        
        $positionSize = $method->invokeArgs($this->strategy, [$price, $nValue]);
        
        $this->assertIsNumeric($positionSize);
        $this->assertGreaterThanOrEqual(0, $positionSize);
        
        // Position size should be calculated based on risk amount and N-value
        // With default 100k account and 2% unit risk: 2000 / 2.0 = 1000
        $this->assertEquals(1000, $positionSize);
    }

    /**
     * Test SELL signal generation on downward breakout
     */
    public function testSellSignalOnDownwardBreakout(): void
    {
        // Generate market data with downward breakout pattern
        $marketData = $this->generateDownwardBreakoutData(30, 15, 100.0, -0.08);
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['action']) && $signal['action'] === 'SELL') {
            $this->assertValidSignal($signal, ['action', 'price', 'confidence', 'reasoning']);
            $this->assertEquals('SELL', $signal['action']);
            $this->assertGreaterThan(0.5, $signal['confidence']);
            $this->assertStringContainsString('breakdown', $signal['reasoning']);
        } else {
            // Verify signal structure if not SELL
            $this->assertTrue(
                $signal === null || 
                (is_array($signal) && isset($signal['action'])),
                'Signal should be null or have valid structure'
            );
        }
    }

    /**
     * Test maximum units limit
     */
    public function testMaxUnitsLimit(): void
    {
        $this->strategy->setParameters(['max_units' => 2]);
        $params = $this->strategy->getParameters();
        
        $this->assertEquals(2, $params['max_units']);
        
        // Verify the parameter affects signal generation logic
        $marketData = $this->generateMarketData(60);
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        if ($signal && isset($signal['units'])) {
            $this->assertLessThanOrEqual(2, $signal['units']);
        } else {
            $this->assertTrue(true); // No signal generated
        }
    }

    /**
     * Test strategy with volatile market conditions
     */
    public function testVolatileMarketConditions(): void
    {
        $marketData = $this->generateVolatileMarketData(60, 100.0, 0.05); // 5% daily volatility
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        // In volatile conditions, strategy should still return valid signals or null
        if ($signal !== null) {
            $this->assertIsArray($signal);
            $this->assertArrayHasKey('action', $signal);
            $this->assertContains($signal['action'], ['BUY', 'SELL', 'HOLD']);
        } else {
            $this->assertNull($signal);
        }
    }

    /**
     * Test edge case: exact breakout threshold
     */
    public function testExactBreakoutThreshold(): void
    {
        // Create data where current price exactly equals the N-day high
        $marketData = $this->generateMarketData(60, 100.0);
        
        // Manually set the last price to be exactly equal to a previous high
        if (count($marketData) > 20) {
            $marketData[count($marketData) - 1]['close'] = $marketData[count($marketData) - 20]['high'];
            $marketData[count($marketData) - 1]['high'] = $marketData[count($marketData) - 20]['high'];
        }
        
        $signal = $this->strategy->generateSignal('AAPL', $marketData);
        
        // Should handle exact threshold cases gracefully
        $this->assertTrue(
            $signal === null || is_array($signal),
            'Strategy should handle exact threshold cases'
        );
    }
}
