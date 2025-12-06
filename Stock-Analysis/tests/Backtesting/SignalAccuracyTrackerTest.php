<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use PHPUnit\Framework\TestCase;
use App\Backtesting\SignalAccuracyTracker;

/**
 * Signal Accuracy Tracker Tests
 *
 * Tests signal prediction accuracy tracking and analysis
 *
 * @package Tests\Backtesting
 */
class SignalAccuracyTrackerTest extends TestCase
{
    private SignalAccuracyTracker $tracker;
    
    protected function setUp(): void
    {
        $this->tracker = new SignalAccuracyTracker();
    }
    
    public function testItTracksCorrectBuySignal(): void
    {
        $this->tracker->recordSignal(
            'AAPL',
            'BUY',
            100.0,  // Signal price
            105.0,  // Actual future price (went up)
            0.75,   // Confidence
            5,      // Days forward
            'RSI Strategy'
        );
        
        $accuracy = $this->tracker->getAccuracy();
        
        $this->assertEquals(100.0, $accuracy);
    }
    
    public function testItTracksIncorrectBuySignal(): void
    {
        $this->tracker->recordSignal(
            'AAPL',
            'BUY',
            100.0,  // Signal price
            95.0,   // Actual future price (went down)
            0.65,
            5,
            'RSI Strategy'
        );
        
        $accuracy = $this->tracker->getAccuracy();
        
        $this->assertEquals(0.0, $accuracy);
    }
    
    public function testItTracksCorrectSellSignal(): void
    {
        $this->tracker->recordSignal(
            'AAPL',
            'SELL',
            100.0,  // Signal price
            95.0,   // Actual future price (went down as predicted)
            0.70,
            5,
            'MACD Strategy'
        );
        
        $accuracy = $this->tracker->getAccuracy();
        
        $this->assertEquals(100.0, $accuracy);
    }
    
    public function testItCalculatesOverallAccuracy(): void
    {
        // 3 correct, 1 incorrect = 75% accuracy
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 102.0, 0.65, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'SELL', 100.0, 95.0, 0.70, 5, 'MACD');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 98.0, 0.60, 5, 'RSI');  // Incorrect
        
        $accuracy = $this->tracker->getAccuracy();
        
        $this->assertEquals(75.0, $accuracy);
    }
    
    public function testItGetsAccuracyByStrategy(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 98.0, 0.65, 5, 'RSI');  // Incorrect
        $this->tracker->recordSignal('AAPL', 'SELL', 100.0, 95.0, 0.70, 5, 'MACD');
        $this->tracker->recordSignal('AAPL', 'SELL', 100.0, 105.0, 0.60, 5, 'MACD');  // Incorrect
        
        $byStrategy = $this->tracker->getAccuracyByStrategy();
        
        $this->assertEquals(50.0, $byStrategy['RSI']);
        $this->assertEquals(50.0, $byStrategy['MACD']);
    }
    
    public function testItGetsAccuracyBySymbol(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 102.0, 0.65, 5, 'RSI');
        $this->tracker->recordSignal('GOOGL', 'SELL', 150.0, 145.0, 0.70, 5, 'MACD');
        $this->tracker->recordSignal('GOOGL', 'BUY', 150.0, 148.0, 0.60, 5, 'RSI');  // Incorrect
        
        $bySymbol = $this->tracker->getAccuracyBySymbol();
        
        $this->assertEquals(100.0, $bySymbol['AAPL']);
        $this->assertEquals(50.0, $bySymbol['GOOGL']);
    }
    
    public function testItGetsAccuracyBySector(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI', 'Technology');
        $this->tracker->recordSignal('GOOGL', 'BUY', 150.0, 155.0, 0.70, 5, 'RSI', 'Technology');
        $this->tracker->recordSignal('JPM', 'SELL', 120.0, 115.0, 0.65, 5, 'MACD', 'Financial');
        $this->tracker->recordSignal('BAC', 'BUY', 30.0, 28.0, 0.60, 5, 'RSI', 'Financial');  // Incorrect
        
        $bySector = $this->tracker->getAccuracyBySector();
        
        $this->assertEquals(100.0, $bySector['Technology']);
        $this->assertEquals(50.0, $bySector['Financial']);
    }
    
    public function testItGetsAccuracyByIndex(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI', 'Technology', 'NASDAQ');
        $this->tracker->recordSignal('MSFT', 'BUY', 200.0, 205.0, 0.70, 5, 'RSI', 'Technology', 'NASDAQ');
        $this->tracker->recordSignal('JPM', 'SELL', 120.0, 115.0, 0.65, 5, 'MACD', 'Financial', 'S&P 500');
        $this->tracker->recordSignal('XOM', 'BUY', 60.0, 58.0, 0.60, 5, 'RSI', 'Energy', 'S&P 500');  // Incorrect
        
        $byIndex = $this->tracker->getAccuracyByIndex();
        
        $this->assertEquals(100.0, $byIndex['NASDAQ']);
        $this->assertEquals(50.0, $byIndex['S&P 500']);
    }
    
    public function testItCalculatesAveragePriceMovement(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');  // +5%
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 110.0, 0.70, 5, 'RSI');  // +10%
        
        $stats = $this->tracker->getDetailedStats();
        
        $this->assertEquals(7.5, $stats['avg_correct_move_percent']);
    }
    
    public function testItTracksConfidenceCorrelation(): void
    {
        // High confidence correct
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 110.0, 0.90, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 108.0, 0.85, 5, 'RSI');
        
        // Low confidence incorrect
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 95.0, 0.50, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 98.0, 0.55, 5, 'RSI');
        
        $stats = $this->tracker->getDetailedStats();
        
        // High confidence should have better accuracy
        $this->assertGreaterThan(0.5, $stats['high_confidence_accuracy']);
        $this->assertArrayHasKey('confidence_correlation', $stats);
    }
    
    public function testItHandlesHoldSignals(): void
    {
        // HOLD signals are not tracked (no prediction to validate)
        $this->tracker->recordSignal('AAPL', 'HOLD', 100.0, 102.0, 0.50, 5, 'RSI');
        
        $accuracy = $this->tracker->getAccuracy();
        
        // Should have no signals tracked
        $this->assertEquals(0.0, $accuracy);
    }
    
    public function testItGeneratesAccuracyReport(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI', 'Technology');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 102.0, 0.65, 5, 'MACD', 'Technology');
        $this->tracker->recordSignal('GOOGL', 'SELL', 150.0, 145.0, 0.70, 5, 'RSI', 'Technology');
        
        $report = $this->tracker->generateReport();
        
        $this->assertIsString($report);
        $this->assertStringContainsString('Overall Accuracy', $report);
        $this->assertStringContainsString('By Strategy', $report);
        $this->assertStringContainsString('By Symbol', $report);
    }
    
    public function testItExportsToCSV(): void
    {
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');
        $this->tracker->recordSignal('GOOGL', 'SELL', 150.0, 145.0, 0.70, 5, 'MACD');
        
        $csv = $this->tracker->exportToCSV();
        
        $this->assertIsString($csv);
        $this->assertStringContainsString('Symbol,Signal,Signal Price,Actual Price', $csv);
        $this->assertStringContainsString('AAPL,BUY', $csv);
        $this->assertStringContainsString('GOOGL,SELL', $csv);
    }
    
    public function testItCalculatesTimeFrameAccuracy(): void
    {
        // 5-day predictions
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 105.0, 0.75, 5, 'RSI');
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 102.0, 0.65, 5, 'RSI');
        
        // 10-day predictions
        $this->tracker->recordSignal('AAPL', 'BUY', 100.0, 95.0, 0.70, 10, 'RSI');
        
        $byTimeframe = $this->tracker->getAccuracyByTimeframe();
        
        $this->assertEquals(100.0, $byTimeframe[5]);
        $this->assertEquals(0.0, $byTimeframe[10]);
    }
    
    public function testItReturnsZeroAccuracyWhenNoSignals(): void
    {
        $accuracy = $this->tracker->getAccuracy();
        
        $this->assertEquals(0.0, $accuracy);
    }
}
