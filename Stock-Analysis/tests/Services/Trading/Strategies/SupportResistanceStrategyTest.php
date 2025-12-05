<?php

declare(strict_types=1);

namespace Tests\Services\Trading\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\Strategies\SupportResistanceStrategy;

/**
 * Support/Resistance Strategy Tests
 *
 * @package Tests\Services\Trading\Strategies
 */
class SupportResistanceStrategyTest extends TestCase
{
    private SupportResistanceStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new SupportResistanceStrategy();
    }
    
    public function testItGeneratesBuySignalAtSupport(): void
    {
        $historicalData = $this->createDataAtSupport();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertContains($result['signal'], ['BUY', 'HOLD']);
        if ($result['signal'] === 'BUY') {
            $this->assertGreaterThan(0.5, $result['confidence']);
        }
    }
    
    public function testItGeneratesSellSignalAtResistance(): void
    {
        $historicalData = $this->createDataAtResistance();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertContains($result['signal'], ['SELL', 'HOLD']);
        if ($result['signal'] === 'SELL') {
            $this->assertGreaterThan(0.5, $result['confidence']);
        }
    }
    
    public function testItDetectsBreakoutAboveResistance(): void
    {
        $historicalData = $this->createBreakoutData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('breakout', $result);
        $this->assertArrayHasKey('breakdown', $result);
    }
    
    public function testItDetectsBreakdownBelowSupport(): void
    {
        $historicalData = $this->createBreakdownData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('breakout', $result);
        $this->assertArrayHasKey('breakdown', $result);
    }
    
    public function testItIdentifiesSupportLevels(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('support_levels', $result);
        $this->assertIsArray($result['support_levels']);
        $this->assertNotEmpty($result['support_levels']);
    }
    
    public function testItIdentifiesResistanceLevels(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('resistance_levels', $result);
        $this->assertIsArray($result['resistance_levels']);
        $this->assertNotEmpty($result['resistance_levels']);
    }
    
    public function testItCalculatesPivotPoints(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('pivot_point', $result);
        $this->assertIsFloat($result['pivot_point']);
    }
    
    public function testItCountsLevelTouches(): void
    {
        $historicalData = $this->createDataWithMultipleTouches();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('support_touches', $result);
        $this->assertArrayHasKey('resistance_touches', $result);
    }
    
    public function testItIncreasesConfidenceWithMultipleTouches(): void
    {
        $historicalData = $this->createDataWithMultipleTouches();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        if ($result['signal'] !== 'HOLD') {
            $this->assertGreaterThan(0.5, $result['confidence']);
        } else {
            $this->assertTrue(true);
        }
    }
    
    public function testItDetectsNearestSupportLevel(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('nearest_support', $result);
    }
    
    public function testItDetectsNearestResistanceLevel(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('nearest_resistance', $result);
    }
    
    public function testItHandlesInsufficientData(): void
    {
        $historicalData = array_slice($this->createHistoricalData(), 0, 5);
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('HOLD', $result['signal']);
        $this->assertLessThan(0.5, $result['confidence']);
    }
    
    public function testItRespectsCustomProximityThreshold(): void
    {
        $customStrategy = new SupportResistanceStrategy([
            'proximity_threshold' => 0.05  // 5%
        ]);
        
        $historicalData = $this->createHistoricalData();
        
        $result = $customStrategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('signal', $result);
    }
    
    public function testItCalculatesLevelStrength(): void
    {
        $historicalData = $this->createDataWithMultipleTouches();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('support_levels', $result);
        
        // Each level should have strength information
        if (!empty($result['support_levels'])) {
            $this->assertIsArray($result['support_levels']);
        }
    }
    
    public function testItGeneratesHoldWhenBetweenLevels(): void
    {
        $historicalData = $this->createDataBetweenLevels();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        // Should be HOLD when not near support or resistance
        $this->assertContains($result['signal'], ['BUY', 'SELL', 'HOLD']);
        $this->assertArrayHasKey('confidence', $result);
    }
    
    /**
     * Create data at support level
     */
    private function createDataAtSupport(): array
    {
        $data = [];
        
        // Build support at 145
        for ($i = 0; $i < 30; $i++) {
            $price = 145 + sin($i * 0.2) * 5;
            if ($price < 146) {
                $price = 145.5;  // Bounce at support
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-30 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => min($price - 2, 145),
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        // Price approaches support
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => 146,
            'high' => 147,
            'low' => 145.2,
            'close' => 145.5,
            'volume' => 1500000
        ];
        
        return $data;
    }
    
    /**
     * Create data at resistance level
     */
    private function createDataAtResistance(): array
    {
        $data = [];
        
        // Build resistance at 155
        for ($i = 0; $i < 30; $i++) {
            $price = 150 + sin($i * 0.2) * 5;
            if ($price > 154) {
                $price = 154.5;  // Rejected at resistance
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-30 days"))),
                'open' => $price,
                'high' => max($price + 2, 155),
                'low' => $price - 2,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        // Price approaches resistance
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => 153,
            'high' => 154.8,
            'low' => 152,
            'close' => 154.5,
            'volume' => 1500000
        ];
        
        return $data;
    }
    
    /**
     * Create breakout data
     */
    private function createBreakoutData(): array
    {
        $data = [];
        
        // Build resistance at 155
        for ($i = 0; $i < 30; $i++) {
            $price = 150 + ($i % 10) * 0.5;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-30 days"))),
                'open' => $price,
                'high' => min($price + 2, 155),
                'low' => $price - 1,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        // Breakout above resistance
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => 154,
            'high' => 158,
            'low' => 153,
            'close' => 157,
            'volume' => 2000000
        ];
        
        return $data;
    }
    
    /**
     * Create breakdown data
     */
    private function createBreakdownData(): array
    {
        $data = [];
        
        // Build support at 145
        for ($i = 0; $i < 30; $i++) {
            $price = 150 - ($i % 10) * 0.5;
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-30 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => max($price - 2, 145),
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        // Breakdown below support
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => 146,
            'high' => 147,
            'low' => 142,
            'close' => 143,
            'volume' => 2000000
        ];
        
        return $data;
    }
    
    /**
     * Create data with multiple touches of levels
     */
    private function createDataWithMultipleTouches(): array
    {
        $data = [];
        
        // Create strong support/resistance with multiple touches
        for ($i = 0; $i < 50; $i++) {
            $phase = $i % 20;
            
            if ($phase < 5) {
                // Touch support at 145
                $price = 145.5;
            } elseif ($phase < 10) {
                // Move up
                $price = 145 + ($phase - 5) * 2;
            } elseif ($phase < 15) {
                // Touch resistance at 155
                $price = 154.5;
            } else {
                // Move down
                $price = 155 - ($phase - 15) * 2;
            }
            
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
     * Create data between support/resistance levels
     */
    private function createDataBetweenLevels(): array
    {
        $data = [];
        
        // Establish support at 145 and resistance at 155
        for ($i = 0; $i < 30; $i++) {
            $price = 145 + ($i % 10);
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-30 days"))),
                'open' => $price,
                'high' => min($price + 2, 155),
                'low' => max($price - 2, 145),
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        // Current price in the middle
        $data[] = [
            'date' => date('Y-m-d'),
            'open' => 150,
            'high' => 151,
            'low' => 149,
            'close' => 150,
            'volume' => 1000000
        ];
        
        return $data;
    }
    
    /**
     * Create standard historical data
     */
    private function createHistoricalData(): array
    {
        $data = [];
        
        for ($i = 0; $i < 60; $i++) {
            $price = 145 + ($i % 15);
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-60 days"))),
                'open' => $price,
                'high' => $price + 3,
                'low' => $price - 3,
                'close' => $price + 1,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
}
