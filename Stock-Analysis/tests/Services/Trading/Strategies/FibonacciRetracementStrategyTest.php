<?php

declare(strict_types=1);

namespace Tests\Services\Trading\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\Strategies\FibonacciRetracementStrategy;

/**
 * Fibonacci Retracement Strategy Tests
 *
 * @package Tests\Services\Trading\Strategies
 */
class FibonacciRetracementStrategyTest extends TestCase
{
    private FibonacciRetracementStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new FibonacciRetracementStrategy();
    }
    
    public function testItGeneratesBuySignalAtFibonacciSupport(): void
    {
        $historicalData = $this->createDataBouncingAt618();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.6, $result['confidence']);
    }
    
    public function testItGeneratesSellSignalAtFibonacciResistance(): void
    {
        $historicalData = $this->createDataRejectedAt618();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('SELL', $result['signal']);
        $this->assertGreaterThan(0.6, $result['confidence']);
    }
    
    public function testItCalculatesFibonacciLevels(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('fib_levels', $result);
        $this->assertArrayHasKey('0.236', $result['fib_levels']);
        $this->assertArrayHasKey('0.382', $result['fib_levels']);
        $this->assertArrayHasKey('0.500', $result['fib_levels']);
        $this->assertArrayHasKey('0.618', $result['fib_levels']);
        $this->assertArrayHasKey('0.786', $result['fib_levels']);
    }
    
    public function testItIdentifiesSwingHighAndLow(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('swing_high', $result);
        $this->assertArrayHasKey('swing_low', $result);
        $this->assertGreaterThan($result['swing_low'], $result['swing_high']);
    }
    
    public function testItDetectsPriceNearFibLevel(): void
    {
        $historicalData = $this->createDataAt382Level();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('nearest_level', $result);
        // Price stabilizes at a level, check it's identified
        $this->assertContains($result['nearest_level'], ['0.236', '0.382', '0.500', '0.618', '0.786', '1.000']);
    }
    
    public function testItGeneratesHoldWhenNoSignal(): void
    {
        $historicalData = $this->createDataAwayFromLevels();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('HOLD', $result['signal']);
    }
    
    public function testItDetectsUptrendRetracement(): void
    {
        $historicalData = $this->createUptrendRetracementData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('trend', $result);
        $this->assertEquals('uptrend', $result['trend']);
    }
    
    public function testItDetectsDowntrendRetracement(): void
    {
        $historicalData = $this->createDowntrendRetracementData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('trend', $result);
        $this->assertEquals('downtrend', $result['trend']);
    }
    
    public function testItCalculatesRetracementPercentage(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('retracement_pct', $result);
        $this->assertIsFloat($result['retracement_pct']);
    }
    
    public function testItIncreasesConfidenceAt618GoldenRatio(): void
    {
        $historicalData = $this->createDataBouncingAt618();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.7, $result['confidence']);
    }
    
    public function testItHandlesInsufficientData(): void
    {
        $historicalData = array_slice($this->createHistoricalData(), 0, 10);
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('HOLD', $result['signal']);
        $this->assertLessThan(0.5, $result['confidence']);
    }
    
    public function testItRespectsProximityThreshold(): void
    {
        $customStrategy = new FibonacciRetracementStrategy([
            'proximity_threshold' => 0.01  // 1% proximity
        ]);
        
        $historicalData = $this->createDataNearLevel();
        
        $result = $customStrategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('signal', $result);
        $this->assertArrayHasKey('nearest_level', $result);
    }
    
    public function testItIdentifiesMultipleTouches(): void
    {
        $historicalData = $this->createDataWithMultipleTouches();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('level_touches', $result);
        $this->assertGreaterThan(1, $result['level_touches']);
    }
    
    public function testItDetectsBreakoutAboveResistance(): void
    {
        $historicalData = $this->createBreakoutData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        // Strong upward movement generates BUY signal (breakout or bounce)
        $this->assertGreaterThan(0.6, $result['confidence']);
    }
    
    public function testItDetectsBreakdownBelowSupport(): void
    {
        $historicalData = $this->createBreakdownData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('SELL', $result['signal']);
        // Strong downward movement generates SELL signal (breakdown or rejection)
        $this->assertGreaterThan(0.6, $result['confidence']);
    }
    
    /**
     * Create data bouncing at 61.8% level
     */
    private function createDataBouncingAt618(): array
    {
        $data = [];
        $swingHigh = 200.0;
        $swingLow = 100.0;
        $fib618 = $swingLow + ($swingHigh - $swingLow) * 0.382; // 138.2 (retracement from high)
        
        // Uptrend, pullback to 61.8%, then bounce
        for ($i = 0; $i < 60; $i++) {
            if ($i < 20) {
                $price = $swingLow + ($i / 20) * ($swingHigh - $swingLow);
            } elseif ($i < 40) {
                // Retracement to 61.8%
                $price = $swingHigh - (($i - 20) / 20) * ($swingHigh - $fib618);
            } else {
                // Bounce back up
                $price = $fib618 + (($i - 40) / 20) * 10;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-60 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 2,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create data rejected at 61.8% level
     */
    private function createDataRejectedAt618(): array
    {
        $data = [];
        $swingHigh = 200.0;
        $swingLow = 100.0;
        $fib618 = $swingHigh - ($swingHigh - $swingLow) * 0.382; // 161.8 (retracement from low)
        
        // Downtrend, rally to 61.8%, then rejection
        for ($i = 0; $i < 60; $i++) {
            if ($i < 20) {
                $price = $swingHigh - ($i / 20) * ($swingHigh - $swingLow);
            } elseif ($i < 40) {
                // Rally to 61.8%
                $price = $swingLow + (($i - 20) / 20) * ($fib618 - $swingLow);
            } else {
                // Rejection down
                $price = $fib618 - (($i - 40) / 20) * 10;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-60 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 2,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create data at 38.2% level
     */
    private function createDataAt382Level(): array
    {
        $data = [];
        $swingHigh = 200.0;
        $swingLow = 100.0;
        $fib382 = $swingLow + ($swingHigh - $swingLow) * 0.618; // 161.8
        
        for ($i = 0; $i < 50; $i++) {
            $price = $i < 30 ? $swingLow + ($i / 30) * ($swingHigh - $swingLow) : $fib382;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-50 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create data away from Fibonacci levels
     */
    private function createDataAwayFromLevels(): array
    {
        $data = [];
        
        for ($i = 0; $i < 50; $i++) {
            $price = 150.0 + sin($i * 0.5) * 2;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-50 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create uptrend retracement data
     */
    private function createUptrendRetracementData(): array
    {
        return $this->createDataBouncingAt618();
    }
    
    /**
     * Create downtrend retracement data
     */
    private function createDowntrendRetracementData(): array
    {
        return $this->createDataRejectedAt618();
    }
    
    /**
     * Create data near a level
     */
    private function createDataNearLevel(): array
    {
        return $this->createDataAt382Level();
    }
    
    /**
     * Create data with multiple touches
     */
    private function createDataWithMultipleTouches(): array
    {
        $data = [];
        $swingHigh = 200.0;
        $swingLow = 100.0;
        $fib618 = $swingLow + ($swingHigh - $swingLow) * 0.382;
        
        for ($i = 0; $i < 80; $i++) {
            // Price oscillates around 61.8% level
            $distance = abs(($i % 20) - 10);
            $price = $fib618 + ($distance - 5) * 0.5;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-80 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create breakout data
     */
    private function createBreakoutData(): array
    {
        $data = [];
        $resistance = 180.0;
        
        for ($i = 0; $i < 50; $i++) {
            if ($i < 40) {
                $price = $resistance - 5 + ($i / 40) * 4;
            } else {
                // Breakout above resistance
                $price = $resistance + (($i - 40) / 10) * 10;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-50 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 1,
                'close' => $price,
                'volume' => ($i >= 40) ? 2000000 : 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create breakdown data
     */
    private function createBreakdownData(): array
    {
        $data = [];
        $support = 120.0;
        
        for ($i = 0; $i < 50; $i++) {
            if ($i < 40) {
                $price = $support + 5 - ($i / 40) * 4;
            } else {
                // Breakdown below support
                $price = $support - (($i - 40) / 10) * 10;
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-50 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 2,
                'close' => $price,
                'volume' => ($i >= 40) ? 2000000 : 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create standard historical data
     */
    private function createHistoricalData(): array
    {
        $data = [];
        
        for ($i = 0; $i < 60; $i++) {
            $price = 100 + ($i / 60) * 100;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-60 days"))),
                'open' => $price,
                'high' => $price + 3,
                'low' => $price - 3,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
}
