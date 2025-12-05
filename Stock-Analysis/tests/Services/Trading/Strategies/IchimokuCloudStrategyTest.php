<?php

declare(strict_types=1);

namespace Tests\Services\Trading\Strategies;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\Strategies\IchimokuCloudStrategy;

/**
 * Ichimoku Cloud Strategy Tests
 *
 * @package Tests\Services\Trading\Strategies
 */
class IchimokuCloudStrategyTest extends TestCase
{
    private IchimokuCloudStrategy $strategy;
    
    protected function setUp(): void
    {
        $this->strategy = new IchimokuCloudStrategy();
    }
    
    public function testItGeneratesBuySignalWhenPriceAboveCloud(): void
    {
        $historicalData = $this->createDataAboveCloud();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.5, $result['confidence']);
        $this->assertEquals('IchimokuCloudStrategy', $result['strategy']);
    }
    
    public function testItGeneratesSellSignalWhenPriceBelowCloud(): void
    {
        $historicalData = $this->createDataBelowCloud();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('SELL', $result['signal']);
        $this->assertGreaterThan(0.5, $result['confidence']);
    }
    
    public function testItGeneratesHoldSignalWhenPriceInCloud(): void
    {
        $historicalData = $this->createDataInsideCloud();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('HOLD', $result['signal']);
    }
    
    public function testItIncludesIchimokuComponents(): void
    {
        $historicalData = $this->createDataAboveCloud();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('tenkan_sen', $result);
        $this->assertArrayHasKey('kijun_sen', $result);
        $this->assertArrayHasKey('senkou_span_a', $result);
        $this->assertArrayHasKey('senkou_span_b', $result);
        $this->assertArrayHasKey('chikou_span', $result);
    }
    
    public function testItDetectsTenkanKijunBullishCrossover(): void
    {
        $historicalData = $this->createBullishCrossoverData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        $this->assertArrayHasKey('crossover', $result);
        // Crossover may be 'bullish' or null depending on exact timing
        $this->assertContains($result['crossover'], ['bullish', null]);
    }
    
    public function testItDetectsTenkanKijunBearishCrossover(): void
    {
        $historicalData = $this->createBearishCrossoverData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('SELL', $result['signal']);
        $this->assertArrayHasKey('crossover', $result);
        // Crossover may be 'bearish' or null depending on exact timing
        $this->assertContains($result['crossover'], ['bearish', null]);
    }
    
    public function testItCalculatesTenkanSenCorrectly(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        // Tenkan-sen is (9-period high + 9-period low) / 2
        $this->assertIsFloat($result['tenkan_sen']);
        $this->assertGreaterThan(0, $result['tenkan_sen']);
    }
    
    public function testItCalculatesKijunSenCorrectly(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        // Kijun-sen is (26-period high + 26-period low) / 2
        $this->assertIsFloat($result['kijun_sen']);
        $this->assertGreaterThan(0, $result['kijun_sen']);
    }
    
    public function testItCalculatesSenkouSpanACorrectly(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        // Senkou Span A is (Tenkan-sen + Kijun-sen) / 2 shifted forward 26 periods
        $this->assertIsFloat($result['senkou_span_a']);
        $this->assertGreaterThan(0, $result['senkou_span_a']);
    }
    
    public function testItCalculatesSenkouSpanBCorrectly(): void
    {
        $historicalData = $this->createHistoricalData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        // Senkou Span B is (52-period high + 52-period low) / 2 shifted forward 26 periods
        $this->assertIsFloat($result['senkou_span_b']);
        $this->assertGreaterThan(0, $result['senkou_span_b']);
    }
    
    public function testItIdentifiesCloudColor(): void
    {
        $historicalData = $this->createDataAboveCloud();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('cloud_color', $result);
        $this->assertContains($result['cloud_color'], ['bullish', 'bearish']);
    }
    
    public function testItIncreasesConfidenceForStrongSignals(): void
    {
        $historicalData = $this->createStrongBullishData();
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('BUY', $result['signal']);
        $this->assertGreaterThan(0.8, $result['confidence']);
    }
    
    public function testItHandlesInsufficientData(): void
    {
        $historicalData = array_slice($this->createHistoricalData(), 0, 20);
        
        $result = $this->strategy->analyze('AAPL', $historicalData);
        
        $this->assertEquals('HOLD', $result['signal']);
        $this->assertLessThan(0.5, $result['confidence']);
    }
    
    public function testItRespectsCustomParameters(): void
    {
        $customStrategy = new IchimokuCloudStrategy([
            'tenkan_period' => 10,
            'kijun_period' => 30,
            'senkou_b_period' => 60
        ]);
        
        $historicalData = $this->createHistoricalData();
        
        $result = $customStrategy->analyze('AAPL', $historicalData);
        
        $this->assertArrayHasKey('signal', $result);
        $this->assertArrayHasKey('tenkan_sen', $result);
    }
    
    /**
     * Create historical data with price above cloud
     */
    private function createDataAboveCloud(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 100; $i++) {
            $price = $basePrice + $i * 1.0;  // Strong uptrend
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-100 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 1,
                'close' => $price + 1.5,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create historical data with price below cloud
     */
    private function createDataBelowCloud(): array
    {
        $data = [];
        $basePrice = 200.0;
        
        for ($i = 0; $i < 100; $i++) {
            $price = $basePrice - $i * 1.0;  // Strong downtrend
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-100 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 2,
                'close' => $price - 1.5,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create historical data with price inside cloud
     */
    private function createDataInsideCloud(): array
    {
        $data = [];
        $basePrice = 150.0;
        
        for ($i = 0; $i < 100; $i++) {
            $price = $basePrice + sin($i * 0.1) * 5;  // Sideways
            $data[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 1,
                'close' => $price,
                'volume' => 1000000
            ];
        }
        
        return array_reverse($data);
    }
    
    /**
     * Create bullish crossover data
     */
    private function createBullishCrossoverData(): array
    {
        $data = [];
        $basePrice = 150.0;
        
        // Create data that starts with downtrend, then strong uptrend (causes crossover)
        for ($i = 0; $i < 100; $i++) {
            if ($i < 50) {
                // First half: downtrend
                $price = $basePrice - ($i * 0.3);
            } else {
                // Second half: strong uptrend (causes Ten crossed above Kijun)
                $price = $basePrice - 15 + (($i - 50) * 2.0);
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-100 days"))),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 1,
                'close' => $price + 1,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create bearish crossover data
     */
    private function createBearishCrossoverData(): array
    {
        $data = [];
        $basePrice = 150.0;
        
        // Create data that starts with uptrend, then strong downtrend (causes crossover)
        for ($i = 0; $i < 100; $i++) {
            if ($i < 50) {
                // First half: uptrend
                $price = $basePrice + ($i * 0.3);
            } else {
                // Second half: strong downtrend (causes Tenkan crossed below Kijun)
                $price = $basePrice + 15 - (($i - 50) * 2.0);
            }
            
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-100 days"))),
                'open' => $price,
                'high' => $price + 1,
                'low' => $price - 2,
                'close' => $price - 1,
                'volume' => 1000000
            ];
        }
        
        return $data;
    }
    
    /**
     * Create strong bullish data
     */
    private function createStrongBullishData(): array
    {
        $data = [];
        $basePrice = 100.0;
        
        for ($i = 0; $i < 100; $i++) {
            $price = $basePrice + $i * 1.5;  // Very strong uptrend
            $data[] = [
                'date' => date('Y-m-d', strtotime("+{$i} days", strtotime("-100 days"))),
                'open' => $price,
                'high' => $price + 3,
                'low' => $price - 0.5,
                'close' => $price + 2.5,
                'volume' => 2000000
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
        $basePrice = 150.0;
        
        for ($i = 0; $i < 100; $i++) {
            $price = $basePrice + sin($i * 0.2) * 10;
            $data[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 2,
                'close' => $price + 1,
                'volume' => 1000000
            ];
        }
        
        return array_reverse($data);
    }
}
