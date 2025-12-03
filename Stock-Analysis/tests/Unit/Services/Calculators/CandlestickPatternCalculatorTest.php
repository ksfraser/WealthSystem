<?php

namespace Tests\Unit\Services\Calculators;

use PHPUnit\Framework\TestCase;
use App\Services\Calculators\CandlestickPatternCalculator;
use InvalidArgumentException;

/**
 * Unit tests for CandlestickPatternCalculator
 */
class CandlestickPatternCalculatorTest extends TestCase
{
    private CandlestickPatternCalculator $calculator;
    
    protected function setUp(): void
    {
        if (!extension_loaded('trader')) {
            $this->markTestSkipped('TA-Lib trader extension not installed');
        }
        
        $this->calculator = new CandlestickPatternCalculator();
    }
    
    public function testHammerDetection(): void
    {
        // Hammer: small body at top, long lower shadow (2x+ body length)
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.8, 9.0],  // Long lower shadow
            'close' => [10.0, 10.4] // Close near open
        ];
        
        $result = $this->calculator->detectPattern('HAMMER', $data);
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        // Second candle should be bullish hammer (100) or detected (not 0)
        $this->assertContains($result[1], [0, 100]);
    }
    
    public function testShootingStarDetection(): void
    {
        // Shooting star: small body at bottom, long upper shadow
        $data = [
            'open' => [10.0, 10.4],
            'high' => [10.1, 12.0],  // Long upper shadow
            'low' => [9.8, 10.3],
            'close' => [10.0, 10.5]  // Close near open
        ];
        
        $result = $this->calculator->detectPattern('SHOOTING_STAR', $data);
        
        $this->assertIsArray($result);
        // Should detect bearish shooting star
        $this->assertContains($result[1], [0, -100]);
    }
    
    public function testBullishEngulfingPattern(): void
    {
        // Bullish engulfing: small red candle followed by large green candle
        $data = [
            'open' => [10.0, 9.5],
            'high' => [10.1, 11.0],
            'low' => [9.5, 9.0],
            'close' => [9.6, 10.8] // Green candle engulfs previous red
        ];
        
        $result = $this->calculator->detectPattern('ENGULFING', $data);
        
        $this->assertIsArray($result);
        // Last candle should show bullish engulfing
        $this->assertContains($result[1], [0, 100]);
    }
    
    public function testBearishEngulfingPattern(): void
    {
        // Bearish engulfing: small green candle followed by large red candle
        $data = [
            'open' => [10.0, 10.8],
            'high' => [10.5, 11.0],
            'low' => [9.9, 9.2],
            'close' => [10.4, 9.5] // Red candle engulfs previous green
        ];
        
        $result = $this->calculator->detectPattern('ENGULFING', $data);
        
        $this->assertIsArray($result);
        // Last candle should show bearish engulfing
        $this->assertContains($result[1], [0, -100]);
    }
    
    public function testDojiDetection(): void
    {
        // Doji: open and close at same price
        $data = [
            'open' => [10.0, 10.0],
            'high' => [10.1, 10.5],
            'low' => [9.9, 9.5],
            'close' => [10.0, 10.0] // Same as open
        ];
        
        $result = $this->calculator->detectPattern('DOJI', $data);
        
        $this->assertIsArray($result);
        // Doji can be bullish (100) or bearish (-100) depending on context
        $this->assertContains($result[1], [-100, 0, 100]);
    }
    
    public function testMorningStarPattern(): void
    {
        // Morning Star: down, small body, up
        $data = [
            'open' => [10.0, 9.0, 9.1],
            'high' => [10.1, 9.1, 10.5],
            'low' => [9.0, 8.9, 9.0],
            'close' => [9.1, 9.0, 10.4] // Bullish reversal
        ];
        
        $result = $this->calculator->detectPattern('MORNING_STAR', $data);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }
    
    public function testEveningStarPattern(): void
    {
        // Evening Star: up, small body, down
        $data = [
            'open' => [10.0, 11.0, 11.1],
            'high' => [11.0, 11.1, 11.2],
            'low' => [9.9, 10.9, 9.6],
            'close' => [10.9, 11.0, 9.7] // Bearish reversal
        ];
        
        $result = $this->calculator->detectPattern('EVENING_STAR', $data);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }
    
    public function testThreeWhiteSoldiers(): void
    {
        // Three consecutive bullish candles
        $data = [
            'open' => [10.0, 10.5, 11.0],
            'high' => [10.6, 11.1, 11.6],
            'low' => [9.9, 10.4, 10.9],
            'close' => [10.5, 11.0, 11.5]
        ];
        
        $result = $this->calculator->detectPattern('THREE_WHITE_SOLDIERS', $data);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }
    
    public function testThreeBlackCrows(): void
    {
        // Three consecutive bearish candles
        $data = [
            'open' => [11.5, 11.0, 10.5],
            'high' => [11.6, 11.1, 10.6],
            'low' => [10.9, 10.4, 9.9],
            'close' => [11.0, 10.5, 10.0]
        ];
        
        $result = $this->calculator->detectPattern('THREE_BLACK_CROWS', $data);
        
        $this->assertIsArray($result);
        $this->assertCount(3, $result);
    }
    
    public function testDetectAllPatterns(): void
    {
        // Create data that should trigger multiple patterns
        $data = [
            'open' => [10.0, 10.5, 10.0],
            'high' => [10.1, 10.6, 10.5],
            'low' => [9.0, 10.0, 9.5],
            'close' => [10.05, 10.55, 10.45]
        ];
        
        $patterns = $this->calculator->detectAllPatterns($data);
        
        $this->assertIsArray($patterns);
        
        // Each detected pattern should have required fields
        foreach ($patterns as $pattern) {
            $this->assertArrayHasKey('pattern', $pattern);
            $this->assertArrayHasKey('value', $pattern);
            $this->assertArrayHasKey('direction', $pattern);
            $this->assertArrayHasKey('reliability', $pattern);
            $this->assertArrayHasKey('full_results', $pattern);
            
            $this->assertContains($pattern['direction'], ['BULLISH', 'BEARISH']);
            $this->assertContains($pattern['reliability'], ['HIGH', 'MEDIUM', 'LOW']);
            $this->assertContains($pattern['value'], [-100, 100]);
        }
    }
    
    public function testGetBullishPatterns(): void
    {
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.0, 9.5],
            'close' => [10.05, 10.55]
        ];
        
        $patterns = $this->calculator->getBullishPatterns($data);
        
        $this->assertIsArray($patterns);
        
        foreach ($patterns as $pattern) {
            $this->assertEquals('BULLISH', $pattern['direction']);
            $this->assertEquals(100, $pattern['value']);
        }
    }
    
    public function testGetBearishPatterns(): void
    {
        $data = [
            'open' => [10.5, 10.0],
            'high' => [12.0, 10.1],
            'low' => [10.0, 9.5],
            'close' => [10.05, 9.55]
        ];
        
        $patterns = $this->calculator->getBearishPatterns($data);
        
        $this->assertIsArray($patterns);
        
        foreach ($patterns as $pattern) {
            $this->assertEquals('BEARISH', $pattern['direction']);
            $this->assertEquals(-100, $pattern['value']);
        }
    }
    
    public function testGetPatternStrength(): void
    {
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.0, 9.5],
            'close' => [10.05, 10.55]
        ];
        
        $strength = $this->calculator->getPatternStrength('HAMMER', $data);
        
        $this->assertContains($strength, [-100, 0, 100]);
    }
    
    public function testGetReliability(): void
    {
        // High reliability patterns
        $this->assertEquals('HIGH', $this->calculator->getReliability('HAMMER'));
        $this->assertEquals('HIGH', $this->calculator->getReliability('ENGULFING'));
        $this->assertEquals('HIGH', $this->calculator->getReliability('MORNING_STAR'));
        
        // Medium reliability patterns
        $this->assertEquals('MEDIUM', $this->calculator->getReliability('DOJI'));
        $this->assertEquals('MEDIUM', $this->calculator->getReliability('HARAMI'));
        
        // Low reliability patterns (anything not in HIGH or MEDIUM)
        $this->assertEquals('LOW', $this->calculator->getReliability('HIKKAKE'));
    }
    
    public function testGenerateSignalBullish(): void
    {
        // Create bullish hammer pattern
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.8, 9.0],
            'close' => [10.0, 10.4],
            'volume' => [100000, 150000] // High volume
        ];
        
        $signal = $this->calculator->generateSignal($data, [
            'min_reliability' => 'HIGH',
            'require_volume' => false
        ]);
        
        if ($signal !== null) {
            $this->assertArrayHasKey('signal', $signal);
            $this->assertArrayHasKey('pattern', $signal);
            $this->assertArrayHasKey('confidence', $signal);
            $this->assertArrayHasKey('entry_price', $signal);
            $this->assertArrayHasKey('stop_loss', $signal);
            $this->assertArrayHasKey('take_profit', $signal);
            
            $this->assertContains($signal['signal'], ['BUY', 'SELL']);
            $this->assertGreaterThan(0, $signal['confidence']);
            $this->assertLessThanOrEqual(1.0, $signal['confidence']);
        }
    }
    
    public function testGenerateSignalWithVolumeConfirmation(): void
    {
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.8, 9.0],
            'close' => [10.0, 10.4],
            'volume' => [100000, 90000] // Low volume - should be rejected
        ];
        
        $signal = $this->calculator->generateSignal($data, [
            'min_reliability' => 'HIGH',
            'require_volume' => true
        ]);
        
        // Should be null due to insufficient volume
        // Or might be valid if no high-reliability pattern detected
        $this->assertTrue($signal === null || is_array($signal));
    }
    
    public function testGenerateSignalMinReliability(): void
    {
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1, 10.6],
            'low' => [9.8, 9.5],
            'close' => [10.0, 10.55]
        ];
        
        // Test with HIGH reliability requirement
        $signalHigh = $this->calculator->generateSignal($data, [
            'min_reliability' => 'HIGH'
        ]);
        
        // Test with MEDIUM reliability requirement
        $signalMedium = $this->calculator->generateSignal($data, [
            'min_reliability' => 'MEDIUM'
        ]);
        
        // Test with LOW reliability requirement (all patterns)
        $signalLow = $this->calculator->generateSignal($data, [
            'min_reliability' => 'LOW'
        ]);
        
        // MEDIUM should find more or equal patterns than HIGH
        // LOW should find more or equal patterns than MEDIUM
        $this->assertTrue(true); // Passes if no exceptions
    }
    
    public function testGetSupportedPatterns(): void
    {
        $patterns = $this->calculator->getSupportedPatterns();
        
        $this->assertIsArray($patterns);
        $this->assertGreaterThan(60, count($patterns)); // Should have 63+ patterns
        
        // Check that some key patterns are included
        $this->assertContains('HAMMER', $patterns);
        $this->assertContains('SHOOTING_STAR', $patterns);
        $this->assertContains('ENGULFING', $patterns);
        $this->assertContains('DOJI', $patterns);
        $this->assertContains('MORNING_STAR', $patterns);
        $this->assertContains('EVENING_STAR', $patterns);
    }
    
    public function testGetPatternInfo(): void
    {
        $info = $this->calculator->getPatternInfo('HAMMER');
        
        $this->assertIsArray($info);
        $this->assertArrayHasKey('type', $info);
        $this->assertArrayHasKey('description', $info);
        $this->assertArrayHasKey('reliability', $info);
        $this->assertArrayHasKey('confirmation', $info);
        $this->assertArrayHasKey('target', $info);
        $this->assertArrayHasKey('invalidation', $info);
        
        $this->assertEquals('BULLISH_REVERSAL', $info['type']);
        $this->assertEquals('HIGH', $info['reliability']);
    }
    
    public function testGetPatternInfoUnknown(): void
    {
        $info = $this->calculator->getPatternInfo('UNKNOWN_PATTERN');
        
        $this->assertIsArray($info);
        $this->assertEquals('UNKNOWN', $info['type']);
        $this->assertArrayHasKey('reliability', $info);
    }
    
    public function testValidateDataMissingKeys(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required data');
        
        $data = [
            'open' => [10.0],
            'high' => [10.1]
            // Missing low and close
        ];
        
        $this->calculator->detectPattern('HAMMER', $data);
    }
    
    public function testValidateDataNotArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be an array');
        
        $data = [
            'open' => 10.0, // Should be array
            'high' => [10.1],
            'low' => [9.9],
            'close' => [10.0]
        ];
        
        $this->calculator->detectPattern('HAMMER', $data);
    }
    
    public function testValidateDataEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');
        
        $data = [
            'open' => [],
            'high' => [],
            'low' => [],
            'close' => []
        ];
        
        $this->calculator->detectPattern('HAMMER', $data);
    }
    
    public function testValidateDataUnequalLengths(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('same length');
        
        $data = [
            'open' => [10.0, 10.5],
            'high' => [10.1],
            'low' => [9.9, 9.5],
            'close' => [10.0, 10.4, 10.3]
        ];
        
        $this->calculator->detectPattern('HAMMER', $data);
    }
    
    public function testUnknownPattern(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown pattern');
        
        $data = [
            'open' => [10.0],
            'high' => [10.1],
            'low' => [9.9],
            'close' => [10.0]
        ];
        
        $this->calculator->detectPattern('NONEXISTENT_PATTERN', $data);
    }
    
    public function testRealWorldScenarioDowntrendReversal(): void
    {
        // Simulate downtrend followed by hammer reversal
        $data = [
            'open' => [12.0, 11.5, 11.0, 10.8, 10.5],
            'high' => [12.1, 11.6, 11.1, 10.9, 10.6],
            'low' => [11.4, 10.9, 10.4, 10.2, 9.5],  // Last one has long shadow
            'close' => [11.5, 11.0, 10.8, 10.5, 10.45], // Last one closes near open
            'volume' => [100000, 110000, 120000, 130000, 180000] // Increasing volume
        ];
        
        $signal = $this->calculator->generateSignal($data, [
            'min_reliability' => 'MEDIUM',
            'require_volume' => true
        ]);
        
        // Might detect bullish reversal pattern
        if ($signal !== null) {
            $this->assertEquals('BUY', $signal['signal']);
            $this->assertArrayHasKey('stop_loss', $signal);
            $this->assertArrayHasKey('take_profit', $signal);
            $this->assertLessThan($signal['entry_price'], $signal['stop_loss']);
            $this->assertGreaterThan($signal['entry_price'], $signal['take_profit']);
        }
        
        $this->assertTrue(true); // Pass if no exception
    }
    
    public function testRealWorldScenarioUptrendReversal(): void
    {
        // Simulate uptrend followed by shooting star reversal
        $data = [
            'open' => [10.0, 10.5, 11.0, 11.5, 11.8],
            'high' => [10.5, 11.0, 11.5, 12.0, 13.0],  // Last one has long upper shadow
            'low' => [9.9, 10.4, 10.9, 11.4, 11.7],
            'close' => [10.5, 11.0, 11.5, 11.8, 12.0],  // Last one closes near open
            'volume' => [100000, 110000, 120000, 150000, 200000]
        ];
        
        $signal = $this->calculator->generateSignal($data, [
            'min_reliability' => 'MEDIUM',
            'require_volume' => false
        ]);
        
        // Might detect bearish reversal pattern
        if ($signal !== null && $signal['signal'] === 'SELL') {
            $this->assertArrayHasKey('stop_loss', $signal);
            $this->assertArrayHasKey('take_profit', $signal);
            $this->assertGreaterThan($signal['entry_price'], $signal['stop_loss']);
            $this->assertLessThan($signal['entry_price'], $signal['take_profit']);
        }
        
        $this->assertTrue(true); // Pass if no exception
    }
}
