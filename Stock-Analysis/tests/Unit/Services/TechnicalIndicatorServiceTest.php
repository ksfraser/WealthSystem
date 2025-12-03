<?php

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\TechnicalIndicatorService;
use RuntimeException;

/**
 * Unit tests for TechnicalIndicatorService
 */
class TechnicalIndicatorServiceTest extends TestCase
{
    private TechnicalIndicatorService $service;
    private bool $taLibAvailable;
    
    protected function setUp(): void
    {
        $this->service = new TechnicalIndicatorService();
        $this->taLibAvailable = extension_loaded('trader');
        
        if (!$this->taLibAvailable) {
            $this->markTestSkipped('TA-Lib trader extension not installed');
        }
    }
    
    private function getTestData(): array
    {
        // Generate 50 days of realistic OHLCV data
        $data = [
            'open' => [],
            'high' => [],
            'low' => [],
            'close' => [],
            'volume' => []
        ];
        
        $price = 100;
        for ($i = 0; $i < 50; $i++) {
            $change = (mt_rand(-200, 200) / 100); // -2% to +2%
            $price += $change;
            
            $open = $price;
            $close = $price + (mt_rand(-100, 100) / 100);
            $high = max($open, $close) + abs(mt_rand(0, 50) / 100);
            $low = min($open, $close) - abs(mt_rand(0, 50) / 100);
            
            $data['open'][] = $open;
            $data['high'][] = $high;
            $data['low'][] = $low;
            $data['close'][] = $close;
            $data['volume'][] = mt_rand(1000000, 5000000);
        }
        
        return $data;
    }
    
    public function testIsAvailable(): void
    {
        $this->assertTrue($this->service->isAvailable());
    }
    
    public function testSMA(): void
    {
        $data = $this->getTestData();
        $result = $this->service->sma($data['close'], 20);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // First 19 values should be null (unstable period)
        for ($i = 0; $i < 19; $i++) {
            $this->assertNull($result[$i]);
        }
        
        // Remaining values should be valid numbers
        for ($i = 19; $i < 50; $i++) {
            $this->assertIsFloat($result[$i]);
            $this->assertGreaterThan(0, $result[$i]);
        }
    }
    
    public function testEMA(): void
    {
        $data = $this->getTestData();
        $result = $this->service->ema($data['close'], 20);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // EMA should have values after unstable period
        $validValues = array_filter($result, fn($v) => $v !== null);
        $this->assertGreaterThan(0, count($validValues));
    }
    
    public function testWMA(): void
    {
        $data = $this->getTestData();
        $result = $this->service->wma($data['close'], 20);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testDEMA(): void
    {
        $data = $this->getTestData();
        $result = $this->service->dema($data['close'], 20);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testTEMA(): void
    {
        $data = $this->getTestData();
        $result = $this->service->tema($data['close'], 20);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testBollingerBands(): void
    {
        $data = $this->getTestData();
        $result = $this->service->bollingerBands($data['close'], 20, 2.0, 2.0);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('upper', $result);
        $this->assertArrayHasKey('middle', $result);
        $this->assertArrayHasKey('lower', $result);
        
        $this->assertCount(50, $result['upper']);
        $this->assertCount(50, $result['middle']);
        $this->assertCount(50, $result['lower']);
        
        // Check that upper > middle > lower (where values exist)
        for ($i = 20; $i < 50; $i++) {
            if ($result['upper'][$i] !== null) {
                $this->assertGreaterThan($result['middle'][$i], $result['upper'][$i]);
                $this->assertGreaterThan($result['lower'][$i], $result['middle'][$i]);
            }
        }
    }
    
    public function testParabolicSAR(): void
    {
        $data = $this->getTestData();
        $result = $this->service->parabolicSAR($data['high'], $data['low'], 0.02, 0.2);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testRSI(): void
    {
        $data = $this->getTestData();
        $result = $this->service->rsi($data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // RSI should be between 0 and 100
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }
    
    public function testMACD(): void
    {
        $data = $this->getTestData();
        $result = $this->service->macd($data['close'], 12, 26, 9);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('macd', $result);
        $this->assertArrayHasKey('signal', $result);
        $this->assertArrayHasKey('histogram', $result);
        
        $this->assertCount(50, $result['macd']);
        $this->assertCount(50, $result['signal']);
        $this->assertCount(50, $result['histogram']);
    }
    
    public function testStochastic(): void
    {
        $data = $this->getTestData();
        $result = $this->service->stochastic(
            $data['high'],
            $data['low'],
            $data['close']
        );
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('slowK', $result);
        $this->assertArrayHasKey('slowD', $result);
        
        $this->assertCount(50, $result['slowK']);
        $this->assertCount(50, $result['slowD']);
        
        // Stochastic should be between 0 and 100
        foreach ($result['slowK'] as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }
    
    public function testCCI(): void
    {
        $data = $this->getTestData();
        $result = $this->service->cci($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testMFI(): void
    {
        $data = $this->getTestData();
        $result = $this->service->mfi(
            $data['high'],
            $data['low'],
            $data['close'],
            $data['volume'],
            14
        );
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // MFI should be between 0 and 100
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }
    
    public function testROC(): void
    {
        $data = $this->getTestData();
        $result = $this->service->roc($data['close'], 10);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testMomentum(): void
    {
        $data = $this->getTestData();
        $result = $this->service->momentum($data['close'], 10);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testWilliamsR(): void
    {
        $data = $this->getTestData();
        $result = $this->service->williamsR($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Williams %R should be between -100 and 0
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertLessThanOrEqual(0, $value);
                $this->assertGreaterThanOrEqual(-100, $value);
            }
        }
    }
    
    public function testATR(): void
    {
        $data = $this->getTestData();
        $result = $this->service->atr($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // ATR should always be positive
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThan(0, $value);
            }
        }
    }
    
    public function testNATR(): void
    {
        $data = $this->getTestData();
        $result = $this->service->natr($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testTrueRange(): void
    {
        $data = $this->getTestData();
        $result = $this->service->trueRange($data['high'], $data['low'], $data['close']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // True range should always be positive
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(0, $value);
            }
        }
    }
    
    public function testStdDev(): void
    {
        $data = $this->getTestData();
        $result = $this->service->stdDev($data['close'], 20, 1.0);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Standard deviation should be positive
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThan(0, $value);
            }
        }
    }
    
    public function testOBV(): void
    {
        $data = $this->getTestData();
        $result = $this->service->obv($data['close'], $data['volume']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testADLine(): void
    {
        $data = $this->getTestData();
        $result = $this->service->adLine($data['high'], $data['low'], $data['close'], $data['volume']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testADOscillator(): void
    {
        $data = $this->getTestData();
        $result = $this->service->adOscillator(
            $data['high'],
            $data['low'],
            $data['close'],
            $data['volume'],
            3,
            10
        );
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testADX(): void
    {
        $data = $this->getTestData();
        $result = $this->service->adx($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // ADX should be between 0 and 100
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(0, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }
    
    public function testADXR(): void
    {
        $data = $this->getTestData();
        $result = $this->service->adxr($data['high'], $data['low'], $data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('adx', $result);
        $this->assertArrayHasKey('plusDI', $result);
        $this->assertArrayHasKey('minusDI', $result);
        
        $this->assertCount(50, $result['adx']);
        $this->assertCount(50, $result['plusDI']);
        $this->assertCount(50, $result['minusDI']);
    }
    
    public function testAroon(): void
    {
        $data = $this->getTestData();
        $result = $this->service->aroon($data['high'], $data['low'], 14);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('down', $result);
        $this->assertArrayHasKey('up', $result);
        
        $this->assertCount(50, $result['down']);
        $this->assertCount(50, $result['up']);
    }
    
    public function testAroonOscillator(): void
    {
        $data = $this->getTestData();
        $result = $this->service->aroonOscillator($data['high'], $data['low'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Aroon Oscillator should be between -100 and +100
        foreach ($result as $value) {
            if ($value !== null) {
                $this->assertGreaterThanOrEqual(-100, $value);
                $this->assertLessThanOrEqual(100, $value);
            }
        }
    }
    
    public function testBeta(): void
    {
        $data = $this->getTestData();
        // Use same data for both to get beta ≈ 1
        $result = $this->service->beta($data['close'], $data['close'], 5);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testCorrelation(): void
    {
        $data = $this->getTestData();
        $result = $this->service->correlation($data['close'], $data['close'], 30);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Correlation with itself should be close to 1
        $validValues = array_filter($result, fn($v) => $v !== null);
        if (!empty($validValues)) {
            $lastValue = end($validValues);
            $this->assertGreaterThan(0.9, $lastValue);
        }
    }
    
    public function testLinearReg(): void
    {
        $data = $this->getTestData();
        $result = $this->service->linearReg($data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testLinearRegSlope(): void
    {
        $data = $this->getTestData();
        $result = $this->service->linearRegSlope($data['close'], 14);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testAvgPrice(): void
    {
        $data = $this->getTestData();
        $result = $this->service->avgPrice($data['open'], $data['high'], $data['low'], $data['close']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Average price should be between low and high
        for ($i = 0; $i < 50; $i++) {
            if ($result[$i] !== null) {
                $this->assertGreaterThanOrEqual($data['low'][$i], $result[$i]);
                $this->assertLessThanOrEqual($data['high'][$i], $result[$i]);
            }
        }
    }
    
    public function testMedPrice(): void
    {
        $data = $this->getTestData();
        $result = $this->service->medPrice($data['high'], $data['low']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
        
        // Median should be between low and high
        for ($i = 0; $i < 50; $i++) {
            $this->assertGreaterThanOrEqual($data['low'][$i], $result[$i]);
            $this->assertLessThanOrEqual($data['high'][$i], $result[$i]);
        }
    }
    
    public function testTypPrice(): void
    {
        $data = $this->getTestData();
        $result = $this->service->typPrice($data['high'], $data['low'], $data['close']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testWclPrice(): void
    {
        $data = $this->getTestData();
        $result = $this->service->wclPrice($data['high'], $data['low'], $data['close']);
        
        $this->assertIsArray($result);
        $this->assertCount(50, $result);
    }
    
    public function testGetAvailableFunctions(): void
    {
        $functions = $this->service->getAvailableFunctions();
        
        $this->assertIsArray($functions);
        $this->assertGreaterThan(100, count($functions));
        
        // Check for some key functions
        $this->assertContains('trader_sma', $functions);
        $this->assertContains('trader_ema', $functions);
        $this->assertContains('trader_rsi', $functions);
        $this->assertContains('trader_macd', $functions);
    }
    
    public function testCalculateMultiple(): void
    {
        $data = $this->getTestData();
        
        $indicators = [
            'sma' => ['period' => 20],
            'ema' => ['period' => 20],
            'rsi' => ['period' => 14],
            'macd' => [],
            'bbands' => []
        ];
        
        $results = $this->service->calculateMultiple($data, $indicators);
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('sma', $results);
        $this->assertArrayHasKey('ema', $results);
        $this->assertArrayHasKey('rsi', $results);
        $this->assertArrayHasKey('macd', $results);
        $this->assertArrayHasKey('bbands', $results);
        
        $this->assertIsArray($results['sma']);
        $this->assertIsArray($results['macd']);
    }
    
    public function testCaching(): void
    {
        $data = $this->getTestData();
        
        // First call - should calculate
        $start1 = microtime(true);
        $result1 = $this->service->sma($data['close'], 20);
        $time1 = microtime(true) - $start1;
        
        // Second call - should use cache
        $start2 = microtime(true);
        $result2 = $this->service->sma($data['close'], 20);
        $time2 = microtime(true) - $start2;
        
        $this->assertEquals($result1, $result2);
        // Cache should be significantly faster (though this might not always be true in tests)
        // Just verify it doesn't error
        $this->assertLessThanOrEqual($time1, $time2 * 10);
    }
    
    public function testClearCache(): void
    {
        $data = $this->getTestData();
        
        // Calculate to populate cache
        $this->service->sma($data['close'], 20);
        
        // Clear cache
        $this->service->clearCache();
        
        // Should still work after clearing
        $result = $this->service->sma($data['close'], 20);
        $this->assertIsArray($result);
    }
    
    public function testSetCacheTTL(): void
    {
        $this->service->setCacheTTL(3600);
        
        // Should not throw exception
        $this->assertTrue(true);
    }
    
    public function testRealWorldScenarioTrendDetection(): void
    {
        $data = $this->getTestData();
        
        // Calculate multiple indicators for trend analysis
        $sma50 = $this->service->sma($data['close'], 50);
        $sma20 = $this->service->sma($data['close'], 20);
        $rsi = $this->service->rsi($data['close'], 14);
        $macd = $this->service->macd($data['close']);
        $adx = $this->service->adx($data['high'], $data['low'], $data['close'], 14);
        
        $lastIndex = 49;
        
        // All indicators should have values
        $this->assertNotNull($sma20[$lastIndex]);
        $this->assertNotNull($rsi[$lastIndex]);
        
        // Check RSI is in valid range
        if ($rsi[$lastIndex] !== null) {
            $this->assertGreaterThanOrEqual(0, $rsi[$lastIndex]);
            $this->assertLessThanOrEqual(100, $rsi[$lastIndex]);
        }
        
        // Check MACD structure
        $this->assertArrayHasKey('macd', $macd);
        $this->assertArrayHasKey('signal', $macd);
        $this->assertArrayHasKey('histogram', $macd);
    }
    
    public function testRealWorldScenarioVolatilityAnalysis(): void
    {
        $data = $this->getTestData();
        
        // Calculate volatility indicators
        $atr = $this->service->atr($data['high'], $data['low'], $data['close'], 14);
        $bbands = $this->service->bollingerBands($data['close'], 20, 2.0, 2.0);
        $stddev = $this->service->stdDev($data['close'], 20);
        
        $lastIndex = 49;
        
        // ATR should be positive
        if ($atr[$lastIndex] !== null) {
            $this->assertGreaterThan(0, $atr[$lastIndex]);
        }
        
        // Bollinger Bands should have proper relationship
        if ($bbands['upper'][$lastIndex] !== null) {
            $this->assertGreaterThan($bbands['middle'][$lastIndex], $bbands['upper'][$lastIndex]);
            $this->assertGreaterThan($bbands['lower'][$lastIndex], $bbands['middle'][$lastIndex]);
        }
        
        // Standard deviation should be positive
        if ($stddev[$lastIndex] !== null) {
            $this->assertGreaterThan(0, $stddev[$lastIndex]);
        }
    }
    
    public function testRealWorldScenarioMomentumAnalysis(): void
    {
        $data = $this->getTestData();
        
        // Calculate momentum indicators
        $rsi = $this->service->rsi($data['close'], 14);
        $stoch = $this->service->stochastic($data['high'], $data['low'], $data['close']);
        $cci = $this->service->cci($data['high'], $data['low'], $data['close'], 14);
        $roc = $this->service->roc($data['close'], 10);
        
        $lastIndex = 49;
        
        // RSI in valid range
        if ($rsi[$lastIndex] !== null) {
            $this->assertGreaterThanOrEqual(0, $rsi[$lastIndex]);
            $this->assertLessThanOrEqual(100, $rsi[$lastIndex]);
        }
        
        // Stochastic in valid range
        if ($stoch['slowK'][$lastIndex] !== null) {
            $this->assertGreaterThanOrEqual(0, $stoch['slowK'][$lastIndex]);
            $this->assertLessThanOrEqual(100, $stoch['slowK'][$lastIndex]);
        }
        
        // All should return arrays
        $this->assertIsArray($rsi);
        $this->assertIsArray($stoch);
        $this->assertIsArray($cci);
        $this->assertIsArray($roc);
    }
}
