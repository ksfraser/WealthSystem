<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use App\Backtesting\BacktestEngine;
use App\Backtesting\PerformanceMetrics;
use App\Backtesting\StrategyComparator;
use App\Services\Trading\TradingStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * StrategyComparator Test Suite
 * 
 * Tests strategy comparison functionality including:
 * - Multiple strategy comparison
 * - Ranking by different metrics
 * - Comparison report generation
 * - Edge cases
 * 
 * @package Tests\Backtesting
 */
class StrategyComparatorTest extends TestCase
{
    private StrategyComparator $comparator;
    private BacktestEngine $engine;
    private PerformanceMetrics $metrics;
    
    protected function setUp(): void
    {
        $this->engine = new BacktestEngine([
            'initial_capital' => 10000.0,
            'commission' => 0.001,
            'slippage' => 0.0005
        ]);
        
        $this->metrics = new PerformanceMetrics();
        $this->comparator = new StrategyComparator($this->engine, $this->metrics);
    }
    
    /**
     * @test
     */
    public function itComparesMultipleStrategies(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD'),
            'Strategy C' => $this->createStrategy('BUY')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $results = $this->comparator->compare($strategies, 'AAPL', $historicalData);
        
        $this->assertCount(3, $results);
        $this->assertArrayHasKey('Strategy A', $results);
        $this->assertArrayHasKey('Strategy B', $results);
        $this->assertArrayHasKey('Strategy C', $results);
        
        foreach ($results as $name => $result) {
            $this->assertArrayHasKey('backtest', $result);
            $this->assertArrayHasKey('metrics', $result);
            $this->assertArrayHasKey('strategy_name', $result);
            $this->assertEquals($name, $result['strategy_name']);
        }
    }
    
    /**
     * @test
     */
    public function itRanksByTotalReturn(): void
    {
        $strategies = [
            'High Return' => $this->createStrategyWithSignals(['BUY', 'HOLD', 'SELL']),
            'Low Return' => $this->createStrategy('HOLD'),
            'Medium Return' => $this->createStrategyWithSignals(['BUY', 'HOLD', 'HOLD'])
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, 'total_return');
        
        $this->assertCount(3, $ranked);
        
        // Verify descending order
        $returns = array_column($ranked, 'metrics');
        $returns = array_column($returns, 'total_return');
        
        for ($i = 0; $i < count($returns) - 1; $i++) {
            $this->assertGreaterThanOrEqual($returns[$i + 1], $returns[$i]);
        }
        
        // Verify first has highest return
        $this->assertGreaterThan($ranked[1]['metrics']['total_return'], $ranked[0]['metrics']['total_return']);
    }
    
    /**
     * @test
     */
    public function itRanksBySharpeRatio(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD'),
            'Strategy C' => $this->createStrategyWithSignals(['BUY', 'HOLD', 'SELL'])
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, 'sharpe_ratio');
        
        $this->assertCount(3, $ranked);
        
        // Verify descending order
        $sharpes = array_column($ranked, 'metrics');
        $sharpes = array_column($sharpes, 'sharpe_ratio');
        
        for ($i = 0; $i < count($sharpes) - 1; $i++) {
            $this->assertGreaterThanOrEqual($sharpes[$i + 1], $sharpes[$i]);
        }
    }
    
    /**
     * @test
     */
    public function itRanksByMaxDrawdown(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        // Max drawdown is negative, so "best" is closest to zero (least negative)
        $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, 'max_drawdown');
        
        $this->assertCount(2, $ranked);
        
        // Verify ascending order (least negative first)
        $drawdowns = array_column($ranked, 'metrics');
        $drawdowns = array_column($drawdowns, 'max_drawdown');
        
        $this->assertLessThanOrEqual($drawdowns[1], $drawdowns[0]);
    }
    
    /**
     * @test
     */
    public function itRanksByWinRate(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategyWithSignals(['BUY', 'SELL', 'BUY', 'SELL']),
            'Strategy B' => $this->createStrategy('HOLD')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, 'win_rate');
        
        $this->assertCount(2, $ranked);
        
        // Verify descending order
        $winRates = array_column($ranked, 'metrics');
        $winRates = array_column($winRates, 'win_rate');
        
        $this->assertGreaterThanOrEqual($winRates[1], $winRates[0]);
    }
    
    /**
     * @test
     */
    public function itGeneratesComparisonReport(): void
    {
        $strategies = [
            'RSI Strategy' => $this->createStrategy('BUY'),
            'MACD Strategy' => $this->createStrategy('HOLD')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $report = $this->comparator->generateReport($strategies, 'AAPL', $historicalData, 'sharpe_ratio');
        
        $this->assertIsString($report);
        $this->assertStringContainsString('Strategy Comparison Report', $report);
        $this->assertStringContainsString('RSI Strategy', $report);
        $this->assertStringContainsString('MACD Strategy', $report);
        $this->assertStringContainsString('Sharpe Ratio', $report);
        $this->assertStringContainsString('Total Return', $report);
        $this->assertStringContainsString('Max Drawdown', $report);
    }
    
    /**
     * @test
     */
    public function itExportsToCSV(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $csv = $this->comparator->exportToCSV($strategies, 'AAPL', $historicalData);
        
        $this->assertIsString($csv);
        
        // Check CSV headers
        $lines = explode("\n", $csv);
        $this->assertGreaterThan(0, count($lines));
        
        $headers = str_getcsv($lines[0]);
        $this->assertContains('Strategy Name', $headers);
        $this->assertContains('Total Return', $headers);
        $this->assertContains('Sharpe Ratio', $headers);
        $this->assertContains('Max Drawdown', $headers);
        $this->assertContains('Win Rate', $headers);
        
        // Check data rows
        $this->assertGreaterThanOrEqual(3, count($lines)); // Header + 2 strategies
    }
    
    /**
     * @test
     */
    public function itHandlesSingleStrategy(): void
    {
        $strategies = [
            'Only Strategy' => $this->createStrategy('BUY')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $results = $this->comparator->compare($strategies, 'AAPL', $historicalData);
        
        $this->assertCount(1, $results);
        $this->assertArrayHasKey('Only Strategy', $results);
    }
    
    /**
     * @test
     */
    public function itHandlesEmptyStrategies(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one strategy required');
        
        $this->comparator->compare([], 'AAPL', $this->createHistoricalData());
    }
    
    /**
     * @test
     */
    public function itHandlesInvalidRankingMetric(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY')
        ];
        
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ranking metric');
        
        $this->comparator->rankBy($strategies, 'AAPL', $this->createHistoricalData(), 'invalid_metric');
    }
    
    /**
     * @test
     */
    public function itReturnsRankPosition(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD'),
            'Strategy C' => $this->createStrategyWithSignals(['BUY', 'HOLD', 'SELL'])
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, 'total_return');
        
        // Verify rank positions are assigned
        foreach ($ranked as $index => $result) {
            $this->assertArrayHasKey('rank', $result);
            $this->assertEquals($index + 1, $result['rank']);
        }
    }
    
    /**
     * @test
     */
    public function itIncludesSymbolInResults(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $results = $this->comparator->compare($strategies, 'AAPL', $historicalData);
        
        foreach ($results as $result) {
            $this->assertEquals('AAPL', $result['backtest']['symbol']);
        }
    }
    
    /**
     * @test
     */
    public function itComparesPerformanceMetrics(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY'),
            'Strategy B' => $this->createStrategy('HOLD')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $results = $this->comparator->compare($strategies, 'AAPL', $historicalData);
        
        foreach ($results as $result) {
            $metrics = $result['metrics'];
            
            // Verify all key metrics are present
            $this->assertArrayHasKey('total_return', $metrics);
            $this->assertArrayHasKey('sharpe_ratio', $metrics);
            $this->assertArrayHasKey('sortino_ratio', $metrics);
            $this->assertArrayHasKey('max_drawdown', $metrics);
            $this->assertArrayHasKey('win_rate', $metrics);
            $this->assertArrayHasKey('profit_factor', $metrics);
            $this->assertArrayHasKey('total_trades', $metrics);
        }
    }
    
    /**
     * @test
     */
    public function itSupportsDifferentRankingMetrics(): void
    {
        $strategies = [
            'Strategy A' => $this->createStrategy('BUY')
        ];
        
        $historicalData = $this->createHistoricalData();
        
        $validMetrics = [
            'total_return',
            'sharpe_ratio',
            'sortino_ratio',
            'max_drawdown',
            'win_rate',
            'profit_factor'
        ];
        
        foreach ($validMetrics as $metric) {
            $ranked = $this->comparator->rankBy($strategies, 'AAPL', $historicalData, $metric);
            $this->assertCount(1, $ranked);
        }
    }
    
    // Helper methods
    
    private function createStrategy(string $signal): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => $signal,
            'confidence' => 0.8,
            'reason' => 'Test signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createStrategyWithSignals(array $signals): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        
        $returns = [];
        foreach ($signals as $signal) {
            $returns[] = [
                'signal' => $signal,
                'confidence' => 0.8,
                'reason' => 'Test signal',
                'metadata' => []
            ];
        }
        
        // Add HOLD signals to fill remaining bars
        while (count($returns) < 10) {
            $returns[] = [
                'signal' => 'HOLD',
                'confidence' => 0.5,
                'reason' => 'Default signal',
                'metadata' => []
            ];
        }
        
        $strategy->method('analyze')->willReturnOnConsecutiveCalls(...$returns);
        
        return $strategy;
    }
    
    private function createHistoricalData(): array
    {
        return [
            ['date' => '2024-01-01', 'open' => 98.0, 'high' => 102.0, 'low' => 97.0, 'close' => 100.0, 'volume' => 1000000],
            ['date' => '2024-01-02', 'open' => 100.0, 'high' => 108.0, 'low' => 99.0, 'close' => 105.0, 'volume' => 1200000],
            ['date' => '2024-01-03', 'open' => 105.0, 'high' => 112.0, 'low' => 104.0, 'close' => 110.0, 'volume' => 1100000],
            ['date' => '2024-01-04', 'open' => 110.0, 'high' => 115.0, 'low' => 108.0, 'close' => 112.0, 'volume' => 1300000]
        ];
    }
}
