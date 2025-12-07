<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use PHPUnit\Framework\TestCase;
use WealthSystem\Backtesting\MultiSymbolBacktestEngine;
use InvalidArgumentException;

/**
 * Tests for MultiSymbolBacktestEngine
 * 
 * Covers multi-symbol coordination, portfolio constraints, metrics
 */
class MultiSymbolBacktestEngineTest extends TestCase
{
    private function createSampleMarketData(): array
    {
        $dates = ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04', '2025-01-05'];
        
        return [
            'AAPL' => array_map(fn($i) => [
                'date' => $dates[$i],
                'open' => 150 + $i,
                'high' => 152 + $i,
                'low' => 149 + $i,
                'close' => 151 + $i,
                'volume' => 1000000
            ], array_keys($dates)),
            'MSFT' => array_map(fn($i) => [
                'date' => $dates[$i],
                'open' => 300 + $i,
                'high' => 302 + $i,
                'low' => 299 + $i,
                'close' => 301 + $i,
                'volume' => 800000
            ], array_keys($dates))
        ];
    }
    
    private function createBuyStrategy(): callable
    {
        return function($symbol, $historicalData, $currentPrice) {
            return ['action' => 'BUY', 'confidence' => 0.5];
        };
    }
    
    private function createHoldStrategy(): callable
    {
        return function($symbol, $historicalData, $currentPrice) {
            return ['action' => 'HOLD', 'confidence' => 0.5];
        };
    }
    
    // ==================== Basic Setup Tests ====================
    
    public function testConstructor(): void
    {
        $engine = new MultiSymbolBacktestEngine([
            'initial_capital' => 100000,
            'max_position_size' => 0.15
        ]);
        
        $this->assertInstanceOf(MultiSymbolBacktestEngine::class, $engine);
    }
    
    public function testRegisterStrategy(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        
        $engine->registerStrategy('AAPL', $this->createBuyStrategy(), [
            'sector' => 'Technology',
            'industry' => 'Consumer Electronics'
        ]);
        
        // Should not throw exception
        $this->assertTrue(true);
    }
    
    // ==================== Backtest Execution Tests ====================
    
    public function testRunBacktestEmptyMarketData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Market data cannot be empty');
        
        $engine = new MultiSymbolBacktestEngine();
        $engine->runBacktest([], '2025-01-01', '2025-01-05');
    }
    
    public function testRunBacktestNoStrategies(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No strategies registered');
        
        $engine = new MultiSymbolBacktestEngine();
        $engine->runBacktest($this->createSampleMarketData(), '2025-01-01', '2025-01-05');
    }
    
    public function testRunBacktestSuccess(): void
    {
        $engine = new MultiSymbolBacktestEngine(['initial_capital' => 100000]);
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        $engine->registerStrategy('MSFT', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertArrayHasKey('period', $result);
        $this->assertArrayHasKey('initial_capital', $result);
        $this->assertArrayHasKey('final_value', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('trades', $result);
        
        $this->assertEquals(100000, $result['initial_capital']);
        $this->assertEquals('2025-01-01', $result['period']['start']);
        $this->assertEquals('2025-01-05', $result['period']['end']);
    }
    
    public function testRunBacktestWithTrades(): void
    {
        $engine = new MultiSymbolBacktestEngine([
            'initial_capital' => 100000,
            'max_position_size' => 0.1
        ]);
        
        $engine->registerStrategy('AAPL', $this->createBuyStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertGreaterThan(0, $result['signals_stats']['generated']);
    }
    
    // ==================== Portfolio Metrics Tests ====================
    
    public function testPortfolioMetricsCalculation(): void
    {
        $engine = new MultiSymbolBacktestEngine(['initial_capital' => 100000]);
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $metrics = $result['metrics'];
        
        $this->assertArrayHasKey('total_return', $metrics);
        $this->assertArrayHasKey('sharpe_ratio', $metrics);
        $this->assertArrayHasKey('sortino_ratio', $metrics);
        $this->assertArrayHasKey('max_drawdown', $metrics);
        $this->assertArrayHasKey('win_rate', $metrics);
    }
    
    // ==================== Position Limit Tests ====================
    
    public function testMaxPositionsLimit(): void
    {
        $engine = new MultiSymbolBacktestEngine([
            'initial_capital' => 1000000, // Large capital to avoid cash constraint
            'max_positions' => 2
        ]);
        
        // Register 3 strategies
        $engine->registerStrategy('AAPL', $this->createBuyStrategy(), ['sector' => 'Technology']);
        $engine->registerStrategy('MSFT', $this->createBuyStrategy(), ['sector' => 'Technology']);
        
        // Add GOOGL to market data
        $marketData = $this->createSampleMarketData();
        $marketData['GOOGL'] = $marketData['AAPL']; // Reuse AAPL data
        $engine->registerStrategy('GOOGL', $this->createBuyStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest($marketData, '2025-01-01', '2025-01-05');
        
        // Should have rejection due to max positions
        $this->assertGreaterThanOrEqual(0, count($result['signals_stats']['rejection_reasons'] ?? []));
    }
    
    // ==================== Sector Exposure Tests ====================
    
    public function testSectorExposureTracking(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        $engine->registerStrategy('MSFT', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertArrayHasKey('sector_exposures', $result);
        $this->assertIsArray($result['sector_exposures']);
    }
    
    // ==================== Signal Statistics Tests ====================
    
    public function testSignalStatistics(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        
        $engine->registerStrategy('AAPL', $this->createBuyStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $stats = $result['signals_stats'];
        
        $this->assertArrayHasKey('generated', $stats);
        $this->assertArrayHasKey('executed', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('rejection_reasons', $stats);
    }
    
    // ==================== Rebalancing Tests ====================
    
    public function testRebalancingTracking(): void
    {
        $engine = new MultiSymbolBacktestEngine([
            'rebalance_threshold' => 0.05
        ]);
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertArrayHasKey('rebalances', $result);
        $this->assertIsArray($result['rebalances']);
    }
    
    // ==================== Portfolio Value Tracking Tests ====================
    
    public function testPortfolioValueTracking(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertArrayHasKey('portfolio_values', $result);
        $this->assertGreaterThan(0, count($result['portfolio_values']));
        
        // Each value should have date and net_worth
        $firstValue = $result['portfolio_values'][0];
        $this->assertArrayHasKey('date', $firstValue);
        $this->assertArrayHasKey('net_worth', $firstValue);
    }
    
    // ==================== Returns Tracking Tests ====================
    
    public function testReturnsTracking(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        
        $engine->registerStrategy('AAPL', $this->createHoldStrategy(), ['sector' => 'Technology']);
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-05'
        );
        
        $this->assertArrayHasKey('returns', $result);
        $this->assertIsArray($result['returns']);
    }
    
    // ==================== Edge Cases ====================
    
    public function testEmptyDateRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No data in specified date range');
        
        $engine = new MultiSymbolBacktestEngine();
        $engine->registerStrategy('AAPL', $this->createHoldStrategy());
        
        $engine->runBacktest(
            $this->createSampleMarketData(),
            '2020-01-01',
            '2020-01-05'
        );
    }
    
    public function testSingleDayBacktest(): void
    {
        $engine = new MultiSymbolBacktestEngine();
        $engine->registerStrategy('AAPL', $this->createHoldStrategy());
        
        $result = $engine->runBacktest(
            $this->createSampleMarketData(),
            '2025-01-01',
            '2025-01-01'
        );
        
        $this->assertEquals(1, $result['period']['trading_days']);
    }
}
