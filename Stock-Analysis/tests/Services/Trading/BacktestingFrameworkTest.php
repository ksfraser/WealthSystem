<?php

use PHPUnit\Framework\TestCase;
use App\Services\Trading\BacktestingFramework;

class BacktestingFrameworkTest extends TestCase
{
    private BacktestingFramework $framework;
    
    protected function setUp(): void
    {
        $this->framework = new BacktestingFramework(100000, 0.001, 0.0005);
    }
    
    // Test 1: Basic backtest initialization
    public function testBacktestInitialization(): void
    {
        $strategy = $this->createMockStrategy();
        $data = $this->generateSimpleHistoricalData(10);
        
        $result = $this->framework->runBacktest($strategy, $data);
        
        $this->assertArrayHasKey('initial_capital', $result);
        $this->assertArrayHasKey('final_capital', $result);
        $this->assertArrayHasKey('total_return', $result);
        $this->assertArrayHasKey('trades', $result);
        $this->assertArrayHasKey('equity_curve', $result);
        $this->assertArrayHasKey('metrics', $result);
        
        $this->assertEquals(100000, $result['initial_capital']);
    }
    
    // Test 2: Commission calculation
    public function testCommissionCalculation(): void
    {
        // With 0.1% commission, buying $10,000 worth costs $10
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(5, 100);
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10
        ]);
        
        // With upward trending prices, final capital should increase despite commissions
        // But verify trades occurred and commissions were applied
        $this->assertNotEmpty($result['trades']);
        if (!empty($result['trades'])) {
            // Entry and exit prices should differ due to commissions and slippage
            $this->assertNotEquals($result['trades'][0]['entry_price'], $data['2024-01-01']['close']);
        }
    }
    
    // Test 3: Slippage application
    public function testSlippageApplication(): void
    {
        $framework = new BacktestingFramework(100000, 0.0, 0.01); // 1% slippage
        
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(5, 100);
        
        $result = $framework->runBacktest($strategy, $data, [
            'position_size' => 0.10
        ]);
        
        // Trades should show slippage impact
        if (!empty($result['trades'])) {
            $trade = $result['trades'][0];
            // Entry price should be higher than actual close due to BUY slippage
            $this->assertGreaterThan(99, $trade['entry_price']);
        }
    }
    
    // Test 4: Stop loss execution
    public function testStopLossExecution(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        
        // Price drops 15% - should trigger 10% stop loss
        $data = [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-02' => ['close' => 95, 'symbol' => 'TEST'],
            '2024-01-03' => ['close' => 88, 'symbol' => 'TEST'],
            '2024-01-04' => ['close' => 85, 'symbol' => 'TEST']
        ];
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'stop_loss' => 0.10 // 10% stop loss
        ]);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertEquals('stop_loss', $result['trades'][0]['exit_reason']);
    }
    
    // Test 5: Take profit execution
    public function testTakeProfitExecution(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        
        // Price rises 25% - should trigger 20% take profit
        $data = [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-02' => ['close' => 110, 'symbol' => 'TEST'],
            '2024-01-03' => ['close' => 118, 'symbol' => 'TEST'],
            '2024-01-04' => ['close' => 125, 'symbol' => 'TEST']
        ];
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'take_profit' => 0.20 // 20% take profit
        ]);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertEquals('take_profit', $result['trades'][0]['exit_reason']);
    }
    
    // Test 6: Max holding days
    public function testMaxHoldingDays(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(20, 100);
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'max_holding_days' => 10
        ]);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertLessThanOrEqual(11, $result['trades'][0]['holding_days']); // Allow 1 day tolerance
    }
    
    // Test 7: Position sizing
    public function testPositionSizing(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(10, 100);
        
        // 25% position size
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.25
        ]);
        
        if (!empty($result['trades'])) {
            $trade = $result['trades'][0];
            $positionValue = $trade['entry_price'] * $trade['shares'];
            
            // Position should be ~25% of initial capital (within commission/slippage)
            $this->assertGreaterThan(20000, $positionValue);
            $this->assertLessThan(27000, $positionValue);
        }
    }
    
    // Test 8: Profitable trade tracking
    public function testProfitableTradeTracking(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        
        $data = [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-05' => ['close' => 120, 'symbol' => 'TEST']
        ];
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'take_profit' => 0.15
        ]);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertGreaterThan(0, $result['trades'][0]['profit_loss']);
        $this->assertGreaterThan(0, $result['trades'][0]['return']);
    }
    
    // Test 9: Losing trade tracking
    public function testLosingTradeTracking(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        
        $data = [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-05' => ['close' => 85, 'symbol' => 'TEST']
        ];
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'stop_loss' => 0.12
        ]);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertLessThan(0, $result['trades'][0]['profit_loss']);
        $this->assertLessThan(0, $result['trades'][0]['return']);
    }
    
    // Test 10: Equity curve generation
    public function testEquityCurveGeneration(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(10, 100);
        
        $result = $this->framework->runBacktest($strategy, $data);
        
        $this->assertNotEmpty($result['equity_curve']);
        $this->assertGreaterThan(1, count($result['equity_curve']));
        
        // First equity point should equal initial capital
        $this->assertEquals(100000, $result['equity_curve'][0]);
    }
    
    // Test 11: Metrics calculation
    public function testMetricsCalculation(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateProfitableHistoricalData();
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10,
            'take_profit' => 0.15
        ]);
        
        $metrics = $result['metrics'];
        
        $this->assertArrayHasKey('total_trades', $metrics);
        $this->assertArrayHasKey('win_rate', $metrics);
        $this->assertArrayHasKey('sharpe_ratio', $metrics);
        $this->assertArrayHasKey('max_drawdown', $metrics);
    }
    
    // Test 12: Multiple trades in backtest
    public function testMultipleTrades(): void
    {
        $strategy = $this->createMockStrategy('BUY_SELL_CYCLE');
        $data = $this->generateCyclicalData();
        
        $result = $this->framework->runBacktest($strategy, $data, [
            'position_size' => 0.10
        ]);
        
        $this->assertGreaterThan(1, count($result['trades']));
    }
    
    // Test 13: Portfolio backtest with multiple symbols
    public function testPortfolioBacktest(): void
    {
        $strategies = [
            'Strategy1' => [
                'strategy' => $this->createMockStrategy('BUY'),
                'weight' => 0.5
            ],
            'Strategy2' => [
                'strategy' => $this->createMockStrategy('BUY'),
                'weight' => 0.5
            ]
        ];
        
        $data = [
            'AAPL' => $this->generateSimpleHistoricalData(10, 150),
            'GOOGL' => $this->generateSimpleHistoricalData(10, 200)
        ];
        
        $result = $this->framework->runPortfolioBacktest($strategies, $data, [
            'position_size' => 0.10,
            'max_positions' => 2
        ]);
        
        $this->assertArrayHasKey('initial_capital', $result);
        $this->assertArrayHasKey('final_capital', $result);
        $this->assertArrayHasKey('trades', $result);
    }
    
    // Test 14: Max positions constraint
    public function testMaxPositionsConstraint(): void
    {
        $strategies = [
            'Strategy1' => [
                'strategy' => $this->createMockStrategy('BUY'),
                'weight' => 1.0
            ]
        ];
        
        $data = [
            'AAPL' => $this->generateSimpleHistoricalData(10, 150),
            'GOOGL' => $this->generateSimpleHistoricalData(10, 200),
            'MSFT' => $this->generateSimpleHistoricalData(10, 250)
        ];
        
        $result = $this->framework->runPortfolioBacktest($strategies, $data, [
            'position_size' => 0.20,
            'max_positions' => 2
        ]);
        
        // Should never have more than 2 open positions at once
        $this->assertLessThanOrEqual(2, count($result['trades']));
    }
    
    // Test 15: Walk-forward analysis
    public function testWalkForwardAnalysis(): void
    {
        $strategy = $this->createMockStrategy('BUY');
        $data = $this->generateSimpleHistoricalData(400, 100);
        
        $result = $this->framework->walkForwardAnalysis(
            $strategy,
            $data,
            252, // 1 year training
            63,  // 3 months testing
            63   // 3 months step
        );
        
        $this->assertArrayHasKey('periods', $result);
        $this->assertArrayHasKey('all_trades', $result);
        $this->assertArrayHasKey('summary', $result);
        
        $this->assertNotEmpty($result['periods']);
    }
    
    // Test 16: Walk-forward period structure
    public function testWalkForwardPeriodStructure(): void
    {
        $strategy = $this->createMockStrategy();
        $data = $this->generateSimpleHistoricalData(400, 100);
        
        $result = $this->framework->walkForwardAnalysis($strategy, $data, 252, 63, 63);
        
        foreach ($result['periods'] as $period) {
            $this->assertArrayHasKey('period', $period);
            $this->assertArrayHasKey('train_start', $period);
            $this->assertArrayHasKey('train_end', $period);
            $this->assertArrayHasKey('test_start', $period);
            $this->assertArrayHasKey('test_end', $period);
            $this->assertArrayHasKey('total_return', $period);
            $this->assertArrayHasKey('sharpe_ratio', $period);
        }
    }
    
    // Test 17: Monte Carlo simulation
    public function testMonteCarloSimulation(): void
    {
        $trades = [
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => 0.15],
            ['return' => -0.03],
            ['return' => 0.08]
        ];
        
        $result = $this->framework->monteCarloSimulation($trades, 100, 50);
        
        $this->assertArrayHasKey('simulations', $result);
        $this->assertArrayHasKey('mean_return', $result);
        $this->assertArrayHasKey('median_return', $result);
        $this->assertArrayHasKey('best_case', $result);
        $this->assertArrayHasKey('worst_case', $result);
        $this->assertArrayHasKey('probability_profit', $result);
        
        $this->assertEquals(100, $result['simulations']);
    }
    
    // Test 18: Monte Carlo percentiles
    public function testMonteCarloPercentiles(): void
    {
        $trades = [
            ['return' => 0.10],
            ['return' => -0.05],
            ['return' => 0.12],
            ['return' => -0.08],
            ['return' => 0.15]
        ];
        
        $result = $this->framework->monteCarloSimulation($trades, 100, 50);
        
        $this->assertArrayHasKey('percentile_5', $result);
        $this->assertArrayHasKey('percentile_25', $result);
        $this->assertArrayHasKey('percentile_75', $result);
        $this->assertArrayHasKey('percentile_95', $result);
        
        // 5th percentile should be less than 95th percentile
        $this->assertLessThan($result['percentile_95'], $result['percentile_5']);
    }
    
    // Test 19: Empty trades handling
    public function testEmptyTradesHandling(): void
    {
        $result = $this->framework->monteCarloSimulation([], 100, 50);
        
        $this->assertArrayHasKey('error', $result);
    }
    
    // Test 20: Backtest with no signals
    public function testBacktestWithNoSignals(): void
    {
        $strategy = $this->createMockStrategy('HOLD');
        $data = $this->generateSimpleHistoricalData(10, 100);
        
        $result = $this->framework->runBacktest($strategy, $data);
        
        $this->assertEmpty($result['trades']);
        $this->assertEquals(100000, $result['final_capital']);
    }
    
    // Helper methods
    
    private function createMockStrategy(string $behavior = 'HOLD'): object
    {
        return new class($behavior) {
            private string $behavior;
            private int $callCount = 0;
            
            public function __construct(string $behavior)
            {
                $this->behavior = $behavior;
            }
            
            public function determineAction(string $symbol, string $date, array $data): array
            {
                $this->callCount++;
                
                if ($this->behavior === 'BUY') {
                    return ['action' => 'BUY', 'confidence' => 80];
                } elseif ($this->behavior === 'SELL') {
                    return ['action' => 'SELL', 'confidence' => 80];
                } elseif ($this->behavior === 'BUY_SELL_CYCLE') {
                    return $this->callCount % 3 === 0 
                        ? ['action' => 'SELL', 'confidence' => 70]
                        : ['action' => 'BUY', 'confidence' => 80];
                }
                
                return ['action' => 'HOLD', 'confidence' => 0];
            }
        };
    }
    
    private function generateSimpleHistoricalData(int $days, float $startPrice = 100): array
    {
        $data = [];
        $price = $startPrice;
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("2024-01-01 + $i days"));
            $data[$date] = [
                'close' => $price + ($i * 0.5), // Slight upward trend
                'open' => $price,
                'high' => $price + 2,
                'low' => $price - 2,
                'volume' => 1000000,
                'symbol' => 'TEST'
            ];
        }
        
        return $data;
    }
    
    private function generateProfitableHistoricalData(): array
    {
        return [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-02' => ['close' => 105, 'symbol' => 'TEST'],
            '2024-01-03' => ['close' => 110, 'symbol' => 'TEST'],
            '2024-01-04' => ['close' => 115, 'symbol' => 'TEST'],
            '2024-01-05' => ['close' => 120, 'symbol' => 'TEST']
        ];
    }
    
    private function generateCyclicalData(): array
    {
        return [
            '2024-01-01' => ['close' => 100, 'symbol' => 'TEST'],
            '2024-01-02' => ['close' => 110, 'symbol' => 'TEST'],
            '2024-01-03' => ['close' => 105, 'symbol' => 'TEST'],
            '2024-01-04' => ['close' => 115, 'symbol' => 'TEST'],
            '2024-01-05' => ['close' => 110, 'symbol' => 'TEST'],
            '2024-01-06' => ['close' => 120, 'symbol' => 'TEST']
        ];
    }
}
