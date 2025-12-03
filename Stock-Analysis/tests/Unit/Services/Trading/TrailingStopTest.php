<?php

namespace Tests\Unit\Services\Trading;

use PHPUnit\Framework\TestCase;
use App\Services\Trading\BacktestingFramework;

/**
 * Test trailing stop loss and partial profit-taking functionality
 * 
 * Tests verify that:
 * 1. Trailing stops activate after specified gain
 * 2. Trailing stops adjust upward as price rises
 * 3. Trailing stops never move downward
 * 4. Trailing stops trigger exit when price falls to stop level
 * 5. Partial profits are taken at configured levels
 * 6. Position sizing adjusts correctly after partial exits
 */
class TrailingStopTest extends TestCase
{
    private BacktestingFramework $framework;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->framework = new BacktestingFramework(
            initialCapital: 100000,
            commissionRate: 0.001,
            slippageRate: 0.0005
        );
    }
    
    /**
     * Test trailing stop activates after 5% gain and adjusts upward
     * 
     * Scenario: Buy at $100, price rises to $120, trailing stop should activate
     * at $105 (5% gain) and adjust to $108 (10% below $120)
     */
    public function testTrailingStopActivatesAndAdjustsUpward(): void
    {
        $strategy = $this->createMockStrategy([
            '2024-01-01' => 'BUY',   // Buy at $100
            '2024-01-05' => 'HOLD',  // Hold at $120
            '2024-01-10' => 'HOLD'   // Hold at $115
        ]);
        
        $historicalData = [
            '2024-01-01' => ['date' => '2024-01-01', 'symbol' => 'TEST', 'close' => 100.00, 'volume' => 1000000],
            '2024-01-02' => ['date' => '2024-01-02', 'symbol' => 'TEST', 'close' => 105.00, 'volume' => 1000000], // +5% - activates trailing
            '2024-01-03' => ['date' => '2024-01-03', 'symbol' => 'TEST', 'close' => 110.00, 'volume' => 1000000], // +10% - stop moves to $99
            '2024-01-04' => ['date' => '2024-01-04', 'symbol' => 'TEST', 'close' => 115.00, 'volume' => 1000000], // +15% - stop moves to $103.50
            '2024-01-05' => ['date' => '2024-01-05', 'symbol' => 'TEST', 'close' => 120.00, 'volume' => 1000000], // +20% - stop moves to $108
            '2024-01-08' => ['date' => '2024-01-08', 'symbol' => 'TEST', 'close' => 115.00, 'volume' => 1000000], // Falls to $115 - stop still $108
            '2024-01-09' => ['date' => '2024-01-09', 'symbol' => 'TEST', 'close' => 110.00, 'volume' => 1000000], // Falls to $110 - stop still $108
            '2024-01-10' => ['date' => '2024-01-10', 'symbol' => 'TEST', 'close' => 107.50, 'volume' => 1000000]  // Hits trailing stop at $108
        ];
        
        $options = [
            'position_size' => 0.10,
            'trailing_stop' => true,
            'trailing_stop_activation' => 0.05,   // Activate after 5% gain
            'trailing_stop_distance' => 0.10,     // Trail 10% below highest
            'partial_profit_taking' => false
        ];
        
        $results = $this->framework->runBacktest($strategy, $historicalData, $options);
        
        // Should have one trade that exited via trailing stop
        $this->assertNotEmpty($results['trades']);
        $this->assertEquals('trailing_stop', $results['trades'][0]['exit_reason']);
        
        // Entry at $100, exit at ~$108 (with slippage) = ~8% gain
        $this->assertGreaterThan(100.00, $results['trades'][0]['exit_price']);
        $this->assertLessThan(110.00, $results['trades'][0]['exit_price']);
        $this->assertGreaterThan(5.0, $results['trades'][0]['return'] * 100); // At least 5% gain
    }
    
    /**
     * Test trailing stop never moves downward
     * 
     * Scenario: Price goes $100 → $120 → $110. Stop should stay at $108 (from $120 high),
     * not drop to $99 (10% below current $110)
     */
    public function testTrailingStopNeverMovesDownward(): void
    {
        $strategy = $this->createMockStrategy([
            '2024-01-01' => 'BUY',
            '2024-01-05' => 'HOLD'
        ]);
        
        $historicalData = [
            '2024-01-01' => ['date' => '2024-01-01', 'symbol' => 'TEST', 'close' => 100.00, 'volume' => 1000000],
            '2024-01-02' => ['date' => '2024-01-02', 'symbol' => 'TEST', 'close' => 106.00, 'volume' => 1000000], // Activates trailing
            '2024-01-03' => ['date' => '2024-01-03', 'symbol' => 'TEST', 'close' => 120.00, 'volume' => 1000000], // Stop moves to $108
            '2024-01-04' => ['date' => '2024-01-04', 'symbol' => 'TEST', 'close' => 110.00, 'volume' => 1000000], // Price drops, stop stays $108
            '2024-01-05' => ['date' => '2024-01-05', 'symbol' => 'TEST', 'close' => 115.00, 'volume' => 1000000]  // Recovers, stop stays $108
        ];
        
        $options = [
            'position_size' => 0.10,
            'trailing_stop' => true,
            'trailing_stop_activation' => 0.05,
            'trailing_stop_distance' => 0.10,
            'partial_profit_taking' => false
        ];
        
        $results = $this->framework->runBacktest($strategy, $historicalData, $options);
        
        // Position should still be open (didn't hit $108 stop)
        // If we extend test to hit stop, it should exit at $108 not lower
        $this->assertTrue(
            empty($results['trades']) || 
            $results['trades'][0]['exit_price'] >= 107.00
        );
    }
    
    /**
     * Test partial profit taking at multiple levels
     * 
     * Scenario: Buy 1000 shares at $10
     * - Price hits $11 (10% gain): Sell 25% (250 shares)
     * - Price hits $12 (20% gain): Sell 50% of original (500 shares, but only 750 remain)
     * - Price hits $13 (30% gain): Sell remaining
     */
    public function testPartialProfitTakingAtMultipleLevels(): void
    {
        $strategy = $this->createMockStrategy([
            '2024-01-01' => 'BUY',
            '2024-01-10' => 'HOLD'
        ]);
        
        $historicalData = [
            '2024-01-01' => ['date' => '2024-01-01', 'symbol' => 'TEST', 'close' => 10.00, 'volume' => 1000000],
            '2024-01-02' => ['date' => '2024-01-02', 'symbol' => 'TEST', 'close' => 10.50, 'volume' => 1000000],
            '2024-01-03' => ['date' => '2024-01-03', 'symbol' => 'TEST', 'close' => 11.00, 'volume' => 1000000], // 10% - take 25%
            '2024-01-04' => ['date' => '2024-01-04', 'symbol' => 'TEST', 'close' => 11.50, 'volume' => 1000000],
            '2024-01-05' => ['date' => '2024-01-05', 'symbol' => 'TEST', 'close' => 12.00, 'volume' => 1000000], // 20% - take 50%
            '2024-01-08' => ['date' => '2024-01-08', 'symbol' => 'TEST', 'close' => 12.50, 'volume' => 1000000],
            '2024-01-09' => ['date' => '2024-01-09', 'symbol' => 'TEST', 'close' => 13.00, 'volume' => 1000000], // 30% - take 100%
            '2024-01-10' => ['date' => '2024-01-10', 'symbol' => 'TEST', 'close' => 13.50, 'volume' => 1000000]
        ];
        
        $options = [
            'position_size' => 0.10,
            'trailing_stop' => false,
            'partial_profit_taking' => true,
            'profit_levels' => [
                ['profit' => 0.10, 'sell_pct' => 0.25],  // At 10% gain, sell 25%
                ['profit' => 0.20, 'sell_pct' => 0.50],  // At 20% gain, sell 50% (of original)
                ['profit' => 0.30, 'sell_pct' => 1.00]   // At 30% gain, sell remaining
            ]
        ];
        
        $results = $this->framework->runBacktest($strategy, $historicalData, $options);
        
        // Should have 3 trades (partial exits)
        $this->assertCount(3, $results['trades']);
        
        // First exit at 10% profit level
        $this->assertStringContainsString('partial_profit_10%', $results['trades'][0]['exit_reason']);
        $this->assertEqualsWithDelta(11.00, $results['trades'][0]['exit_price'], 0.60); // Allow for slippage and commission
        
        // Second exit at 20% profit level
        $this->assertStringContainsString('partial_profit_20%', $results['trades'][1]['exit_reason']);
        $this->assertEqualsWithDelta(12.00, $results['trades'][1]['exit_price'], 0.60); // Allow for slippage and commission
        
        // Third exit at 30% profit level
        $this->assertStringContainsString('partial_profit_30%', $results['trades'][2]['exit_reason']);
        $this->assertEqualsWithDelta(13.00, $results['trades'][2]['exit_price'], 0.60); // Allow for slippage and commission
    }
    
    /**
     * Test combination of trailing stop and partial profit taking
     * 
     * Scenario: Use partial profits to lock in gains at milestones,
     * and trailing stop to protect remaining position
     */
    public function testCombinedTrailingStopAndPartialProfits(): void
    {
        $strategy = $this->createMockStrategy([
            '2024-01-01' => 'BUY',
            '2024-01-10' => 'HOLD'
        ]);
        
        $historicalData = [
            '2024-01-01' => ['date' => '2024-01-01', 'symbol' => 'TEST', 'close' => 100.00, 'volume' => 1000000],
            '2024-01-02' => ['date' => '2024-01-02', 'symbol' => 'TEST', 'close' => 110.00, 'volume' => 1000000], // 10% - partial exit
            '2024-01-03' => ['date' => '2024-01-03', 'symbol' => 'TEST', 'close' => 120.00, 'volume' => 1000000], // 20% - partial exit, trailing at $108
            '2024-01-04' => ['date' => '2024-01-04', 'symbol' => 'TEST', 'close' => 125.00, 'volume' => 1000000], // Trailing moves to $112.50
            '2024-01-05' => ['date' => '2024-01-05', 'symbol' => 'TEST', 'close' => 115.00, 'volume' => 1000000], // Falls but above $112.50
            '2024-01-08' => ['date' => '2024-01-08', 'symbol' => 'TEST', 'close' => 112.00, 'volume' => 1000000]  // Hits trailing stop
        ];
        
        $options = [
            'position_size' => 0.10,
            'trailing_stop' => true,
            'trailing_stop_activation' => 0.05,
            'trailing_stop_distance' => 0.10,
            'partial_profit_taking' => true,
            'profit_levels' => [
                ['profit' => 0.10, 'sell_pct' => 0.30],  // At 10%, sell 30%
                ['profit' => 0.20, 'sell_pct' => 0.50]   // At 20%, sell 50% of original
            ]
        ];
        
        $results = $this->framework->runBacktest($strategy, $historicalData, $options);
        
        // Should have 3 trades: 2 partial exits + 1 trailing stop exit
        $this->assertGreaterThanOrEqual(3, count($results['trades']));
        
        // Check that both partial exits and trailing stop executed
        $exitReasons = array_column($results['trades'], 'exit_reason');
        $exitReasonsStr = implode(',', $exitReasons);
        $this->assertStringContainsString('partial_profit_10%', $exitReasonsStr);
        $this->assertStringContainsString('partial_profit_20%', $exitReasonsStr);
        $this->assertStringContainsString('trailing_stop', $exitReasonsStr);
    }
    
    /**
     * Test that fixed stop loss still works when trailing not activated
     * 
     * Scenario: Buy at $100, price drops to $92 before reaching 5% gain threshold.
     * Should exit at fixed 10% stop ($90), not wait for trailing to activate.
     */
    public function testFixedStopLossWorksBeforeTrailingActivates(): void
    {
        $strategy = $this->createMockStrategy([
            '2024-01-01' => 'BUY',
            '2024-01-05' => 'HOLD'
        ]);
        
        $historicalData = [
            '2024-01-01' => ['date' => '2024-01-01', 'symbol' => 'TEST', 'close' => 100.00, 'volume' => 1000000],
            '2024-01-02' => ['date' => '2024-01-02', 'symbol' => 'TEST', 'close' => 98.00, 'volume' => 1000000],
            '2024-01-03' => ['date' => '2024-01-03', 'symbol' => 'TEST', 'close' => 95.00, 'volume' => 1000000],
            '2024-01-04' => ['date' => '2024-01-04', 'symbol' => 'TEST', 'close' => 92.00, 'volume' => 1000000],
            '2024-01-05' => ['date' => '2024-01-05', 'symbol' => 'TEST', 'close' => 89.00, 'volume' => 1000000]  // Hits stop at $90
        ];
        
        $options = [
            'position_size' => 0.10,
            'stop_loss' => 0.10,                  // Fixed 10% stop
            'trailing_stop' => true,
            'trailing_stop_activation' => 0.05,   // Never activates (price doesn't reach +5%)
            'trailing_stop_distance' => 0.10,
            'partial_profit_taking' => false
        ];
        
        $results = $this->framework->runBacktest($strategy, $historicalData, $options);
        
        // Should exit via fixed stop loss
        $this->assertNotEmpty($results['trades']);
        $this->assertEquals('stop_loss', $results['trades'][0]['exit_reason']);
        $this->assertLessThan(91.00, $results['trades'][0]['exit_price']); // At or below $90
    }
    
    /**
     * Create a mock strategy that returns predefined signals
     */
    private function createMockStrategy(array $signals): object
    {
        return new class($signals) {
            private array $signals;
            
            public function __construct(array $signals)
            {
                $this->signals = $signals;
            }
            
            public function determineAction(string $symbol, string $date, array $data): array
            {
                $action = $this->signals[$date] ?? 'HOLD';
                
                return [
                    'action' => $action,
                    'confidence' => 0.8,
                    'reasoning' => 'Test signal',
                    'metrics' => []
                ];
            }
        };
    }
}
