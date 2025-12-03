<?php

use PHPUnit\Framework\TestCase;
use App\Services\Trading\StrategyPerformanceAnalyzer;

class StrategyPerformanceAnalyzerTest extends TestCase
{
    private StrategyPerformanceAnalyzer $analyzer;
    
    protected function setUp(): void
    {
        $this->analyzer = new StrategyPerformanceAnalyzer();
    }
    
    // Test 1: Record single trade
    public function testRecordTrade(): void
    {
        $this->analyzer->recordTrade('AAPL', 'MomentumQuality', [
            'entry_date' => '2024-01-01',
            'entry_price' => 100.0,
            'exit_date' => '2024-02-01',
            'exit_price' => 110.0,
            'action' => 'BUY',
            'confidence' => 85
        ]);
        
        $history = $this->analyzer->getTradeHistory();
        
        $this->assertCount(1, $history);
        $this->assertEquals('AAPL', $history[0]['symbol']);
        $this->assertEquals('MomentumQuality', $history[0]['strategy']);
        $this->assertEquals(0.10, $history[0]['return']); // 10% return
        $this->assertEquals(10.0, $history[0]['profit_loss']);
        $this->assertEquals(31, $history[0]['holding_days']);
    }
    
    // Test 2: Load multiple trades
    public function testLoadTradeHistory(): void
    {
        $trades = [
            [
                'symbol' => 'AAPL',
                'strategy' => 'MomentumQuality',
                'entry_date' => '2024-01-01',
                'entry_price' => 100.0,
                'exit_date' => '2024-02-01',
                'exit_price' => 110.0
            ],
            [
                'symbol' => 'GOOGL',
                'strategy' => 'Contrarian',
                'entry_date' => '2024-01-15',
                'entry_price' => 150.0,
                'exit_date' => '2024-03-01',
                'exit_price' => 140.0
            ]
        ];
        
        $this->analyzer->loadTradeHistory($trades);
        
        $history = $this->analyzer->getTradeHistory();
        $this->assertCount(2, $history);
    }
    
    // Test 3: Win rate calculation - all wins
    public function testWinRateAllWins(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => 0.15],
            ['return' => 0.08]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        $this->assertEquals(1.0, $metrics['win_rate']);
        $this->assertEquals(3, $metrics['winning_trades']);
        $this->assertEquals(0, $metrics['losing_trades']);
    }
    
    // Test 4: Win rate calculation - all losses
    public function testWinRateAllLosses(): void
    {
        $this->loadSampleTrades([
            ['return' => -0.10],
            ['return' => -0.15],
            ['return' => -0.08]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        $this->assertEquals(0.0, $metrics['win_rate']);
        $this->assertEquals(0, $metrics['winning_trades']);
        $this->assertEquals(3, $metrics['losing_trades']);
    }
    
    // Test 5: Win rate calculation - mixed
    public function testWinRateMixed(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => 0.08],
            ['return' => -0.03],
            ['return' => 0.12]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        $this->assertEquals(0.60, $metrics['win_rate']); // 3/5 = 60%
        $this->assertEquals(3, $metrics['winning_trades']);
        $this->assertEquals(2, $metrics['losing_trades']);
    }
    
    // Test 6: Average return calculation
    public function testAverageReturn(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => 0.15],
            ['return' => -0.08],
            ['return' => 0.20]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // (0.10 - 0.05 + 0.15 - 0.08 + 0.20) / 5 = 0.064
        $this->assertEquals(0.064, $metrics['average_return']);
    }
    
    // Test 7: Average win calculation
    public function testAverageWin(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => 0.20],
            ['return' => 0.15]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // (0.10 + 0.20 + 0.15) / 3 = 0.15
        $this->assertEquals(0.15, $metrics['average_win']);
    }
    
    // Test 8: Average loss calculation
    public function testAverageLoss(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => -0.08],
            ['return' => 0.15]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // abs((-0.05 - 0.08) / 2) = 0.065
        $this->assertEquals(0.065, $metrics['average_loss']);
    }
    
    // Test 9: Profit factor calculation
    public function testProfitFactor(): void
    {
        $trades = [
            ['entry_price' => 100, 'exit_price' => 120], // +20
            ['entry_price' => 100, 'exit_price' => 90],  // -10
            ['entry_price' => 100, 'exit_price' => 130], // +30
            ['entry_price' => 100, 'exit_price' => 85]   // -15
        ];
        
        $this->loadSampleTrades($trades);
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // Total wins: 50, Total losses: 25
        // Profit factor = 50 / 25 = 2.0
        $this->assertEquals(2.0, $metrics['profit_factor']);
    }
    
    // Test 10: Sharpe ratio calculation
    public function testSharpeRatioCalculation(): void
    {
        // High consistent returns -> high Sharpe
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => 0.11],
            ['return' => 0.09],
            ['return' => 0.12],
            ['return' => 0.10]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // Should have positive Sharpe ratio (exact value depends on std dev)
        $this->assertGreaterThan(0, $metrics['sharpe_ratio']);
    }
    
    // Test 11: Sharpe ratio - volatile returns
    public function testSharpeRatioVolatile(): void
    {
        // Same average but high volatility -> lower Sharpe than consistent returns
        $this->loadSampleTrades([
            ['return' => 0.30],
            ['return' => -0.20],
            ['return' => 0.25],
            ['return' => -0.15],
            ['return' => 0.32]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // Average return ~10.4%, high volatility still produces positive Sharpe
        $this->assertGreaterThan(0, $metrics['sharpe_ratio']);
        $this->assertLessThan(15, $metrics['sharpe_ratio']); // Reasonable upper bound
    }
    
    // Test 12: Max drawdown calculation
    public function testMaxDrawdown(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],  // 1.0 -> 1.10
            ['return' => 0.15],  // 1.10 -> 1.265 (new peak)
            ['return' => -0.20], // 1.265 -> 1.012 (drawdown 20%)
            ['return' => -0.10], // 1.012 -> 0.911 (drawdown 28% from peak)
            ['return' => 0.30]   // 0.911 -> 1.184 (recovery)
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // Max drawdown should be ~0.28 (28%)
        $this->assertGreaterThan(0.25, $metrics['max_drawdown']);
        $this->assertLessThan(0.30, $metrics['max_drawdown']);
    }
    
    // Test 13: No drawdown when always rising
    public function testNoDrawdown(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => 0.15],
            ['return' => 0.08],
            ['return' => 0.12]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        $this->assertEquals(0.0, $metrics['max_drawdown']);
    }
    
    // Test 14: Expectancy calculation
    public function testExpectancy(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.20],
            ['return' => 0.15],
            ['return' => -0.05],
            ['return' => 0.18],
            ['return' => -0.08]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // Win rate: 3/5 = 0.6
        // Avg win: (0.20 + 0.15 + 0.18) / 3 = 0.1767
        // Avg loss: (0.05 + 0.08) / 2 = 0.065
        // Expectancy: 0.6 * 0.1767 - 0.4 * 0.065 = 0.106 - 0.026 = 0.08
        $this->assertGreaterThan(0.05, $metrics['expectancy']);
        $this->assertLessThan(0.12, $metrics['expectancy']);
    }
    
    // Test 15: Holding days calculation
    public function testHoldingDaysCalculation(): void
    {
        $this->analyzer->recordTrade('AAPL', 'TestStrategy', [
            'entry_date' => '2024-01-01',
            'entry_price' => 100.0,
            'exit_date' => '2024-01-31',
            'exit_price' => 110.0
        ]);
        
        $history = $this->analyzer->getTradeHistory();
        
        $this->assertEquals(30, $history[0]['holding_days']);
    }
    
    // Test 16: Average holding days
    public function testAverageHoldingDays(): void
    {
        $this->analyzer->loadTradeHistory([
            [
                'symbol' => 'AAPL',
                'strategy' => 'TestStrategy',
                'entry_date' => '2024-01-01',
                'entry_price' => 100,
                'exit_date' => '2024-01-31',
                'exit_price' => 110
            ],
            [
                'symbol' => 'GOOGL',
                'strategy' => 'TestStrategy',
                'entry_date' => '2024-02-01',
                'entry_price' => 150,
                'exit_date' => '2024-03-01',
                'exit_price' => 160
            ]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // (30 + 29) / 2 = 29.5 -> 30 rounded
        $this->assertEquals(30, $metrics['average_holding_days']);
    }
    
    // Test 17: Strategy comparison
    public function testCompareStrategies(): void
    {
        // Load trades for multiple strategies
        $this->loadMultiStrategyTrades();
        
        $comparison = $this->analyzer->compareStrategies();
        
        $this->assertCount(2, $comparison);
        
        // Should be sorted by Sharpe ratio descending
        $this->assertGreaterThanOrEqual(
            $comparison[1]['sharpe_ratio'],
            $comparison[0]['sharpe_ratio']
        );
    }
    
    // Test 18: Empty strategy analysis
    public function testEmptyStrategyAnalysis(): void
    {
        $metrics = $this->analyzer->analyzeStrategy('NonExistent');
        
        $this->assertEquals(0, $metrics['total_trades']);
        $this->assertEquals(0, $metrics['win_rate']);
        $this->assertEquals(0, $metrics['sharpe_ratio']);
    }
    
    // Test 19: Performance time series
    public function testPerformanceTimeSeries(): void
    {
        $this->analyzer->loadTradeHistory([
            [
                'symbol' => 'AAPL',
                'strategy' => 'TestStrategy',
                'entry_date' => '2024-01-01',
                'entry_price' => 100,
                'exit_date' => '2024-01-15',
                'exit_price' => 110
            ],
            [
                'symbol' => 'GOOGL',
                'strategy' => 'TestStrategy',
                'entry_date' => '2024-01-20',
                'entry_price' => 150,
                'exit_date' => '2024-02-01',
                'exit_price' => 165
            ]
        ]);
        
        $series = $this->analyzer->getPerformanceTimeSeries('TestStrategy');
        
        $this->assertCount(2, $series);
        $this->assertEquals(0.10, $series[0]['return']);
        $this->assertEquals(0.10, $series[0]['cumulative_return']);
        $this->assertEquals(1.10, $series[0]['cumulative_value']);
        
        // Second trade: 1.10 * 1.10 = 1.21
        $this->assertEquals(0.21, $series[1]['cumulative_return']);
    }
    
    // Test 20: Strategy correlations
    public function testStrategyCorrelations(): void
    {
        $this->loadMultiStrategyTrades();
        
        $correlations = $this->analyzer->calculateStrategyCorrelations();
        
        $this->assertArrayHasKey('Strategy1', $correlations);
        $this->assertArrayHasKey('Strategy2', $correlations);
        
        // Correlation with self should be 1.0
        $this->assertEquals(1.0, $correlations['Strategy1']['Strategy1']);
        $this->assertEquals(1.0, $correlations['Strategy2']['Strategy2']);
    }
    
    // Test 21: Optimal combination finder
    public function testFindOptimalCombination(): void
    {
        $this->loadMultiStrategyTrades();
        
        $optimal = $this->analyzer->findOptimalCombination(2);
        
        $this->assertArrayHasKey('recommended_strategies', $optimal);
        $this->assertArrayHasKey('weights', $optimal);
        $this->assertArrayHasKey('expected_sharpe', $optimal);
        $this->assertArrayHasKey('diversification_benefit', $optimal);
        
        // Weights should sum to ~1.0
        $weightSum = array_sum($optimal['weights']);
        $this->assertEqualsWithDelta(1.0, $weightSum, 0.01);
    }
    
    // Test 22: Best and worst trade tracking
    public function testBestWorstTrade(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => -0.15],
            ['return' => 0.25],
            ['return' => -0.05]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        $this->assertEquals(0.25, $metrics['best_trade']);
        $this->assertEquals(-0.15, $metrics['worst_trade']);
    }
    
    // Test 23: Total return calculation
    public function testTotalReturn(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => 0.15],
            ['return' => -0.05],
            ['return' => 0.08]
        ]);
        
        $metrics = $this->analyzer->analyzeStrategy('TestStrategy');
        
        // 0.10 + 0.15 - 0.05 + 0.08 = 0.28
        $this->assertEquals(0.28, $metrics['total_return']);
    }
    
    // Test 24: Clear history
    public function testClearHistory(): void
    {
        $this->loadSampleTrades([
            ['return' => 0.10],
            ['return' => 0.15]
        ]);
        
        $this->assertCount(2, $this->analyzer->getTradeHistory());
        
        $this->analyzer->clearHistory();
        
        $this->assertCount(0, $this->analyzer->getTradeHistory());
    }
    
    // Test 25: Analyze 'all' strategies combined
    public function testAnalyzeAllStrategies(): void
    {
        $this->loadMultiStrategyTrades();
        
        $metrics = $this->analyzer->analyzeStrategy('all');
        
        // Should combine trades from both strategies
        $this->assertGreaterThan(3, $metrics['total_trades']);
        $this->assertEquals('all', $metrics['strategy']);
    }
    
    // Helper methods
    
    private function loadSampleTrades(array $trades): void
    {
        foreach ($trades as $trade) {
            $entryPrice = $trade['entry_price'] ?? 100.0;
            $exitPrice = $trade['exit_price'] ?? ($entryPrice * (1 + $trade['return']));
            
            $this->analyzer->recordTrade('TEST', 'TestStrategy', [
                'entry_date' => '2024-01-01',
                'entry_price' => $entryPrice,
                'exit_date' => '2024-02-01',
                'exit_price' => $exitPrice,
                'action' => 'BUY',
                'confidence' => 75
            ]);
        }
    }
    
    private function loadMultiStrategyTrades(): void
    {
        // Strategy 1 - higher returns
        $this->analyzer->recordTrade('AAPL', 'Strategy1', [
            'entry_date' => '2024-01-01',
            'entry_price' => 100,
            'exit_date' => '2024-02-01',
            'exit_price' => 115
        ]);
        
        $this->analyzer->recordTrade('GOOGL', 'Strategy1', [
            'entry_date' => '2024-02-01',
            'entry_price' => 150,
            'exit_date' => '2024-03-01',
            'exit_price' => 165
        ]);
        
        // Strategy 2 - moderate returns
        $this->analyzer->recordTrade('MSFT', 'Strategy2', [
            'entry_date' => '2024-01-01',
            'entry_price' => 200,
            'exit_date' => '2024-02-01',
            'exit_price' => 210
        ]);
        
        $this->analyzer->recordTrade('TSLA', 'Strategy2', [
            'entry_date' => '2024-02-01',
            'entry_price' => 180,
            'exit_date' => '2024-03-01',
            'exit_price' => 185
        ]);
    }
}
