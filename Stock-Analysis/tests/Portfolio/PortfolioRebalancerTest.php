<?php

declare(strict_types=1);

namespace Tests\Portfolio;

use PHPUnit\Framework\TestCase;
use App\Portfolio\PortfolioRebalancer;

/**
 * Test suite for Portfolio Rebalancing functionality.
 * 
 * Rebalancing adjusts portfolio holdings to match target allocations,
 * accounting for drift, tax implications, and transaction costs.
 */
class PortfolioRebalancerTest extends TestCase
{
    private PortfolioRebalancer $rebalancer;

    protected function setUp(): void
    {
        $this->rebalancer = new PortfolioRebalancer();
    }

    /**
     * @test
     */
    public function itCalculatesCurrentAllocations(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 100, 'price' => 150.0],  // $15,000
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0],  // $5,000
            ['symbol' => 'MSFT', 'shares' => 80, 'price' => 250.0],   // $20,000
        ];  // Total: $40,000
        
        $allocations = $this->rebalancer->calculateCurrentAllocations($holdings);
        
        $this->assertEqualsWithDelta(37.5, $allocations['AAPL'], 0.1);  // 15k/40k = 37.5%
        $this->assertEqualsWithDelta(12.5, $allocations['GOOGL'], 0.1);  // 5k/40k = 12.5%
        $this->assertEqualsWithDelta(50.0, $allocations['MSFT'], 0.1);  // 20k/40k = 50%
    }

    /**
     * @test
     */
    public function itCalculatesAllocationDrift(): void
    {
        $current = [
            'AAPL' => 40.0,   // Currently 40%
            'GOOGL' => 30.0,  // Currently 30%
            'MSFT' => 30.0    // Currently 30%
        ];
        
        $target = [
            'AAPL' => 33.33,  // Target 33.33%
            'GOOGL' => 33.33, // Target 33.33%
            'MSFT' => 33.33   // Target 33.33%
        ];
        
        $drift = $this->rebalancer->calculateDrift($current, $target);
        
        $this->assertEqualsWithDelta(6.67, $drift['AAPL'], 0.1);   // +6.67% over target
        $this->assertEqualsWithDelta(-3.33, $drift['GOOGL'], 0.1);  // -3.33% under target
        $this->assertEqualsWithDelta(-3.33, $drift['MSFT'], 0.1);   // -3.33% under target
    }

    /**
     * @test
     */
    public function itIdentifiesRebalancingNeeds(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0],  // $22,500 = 56.25%
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0],  // $5,000 = 12.5%
            ['symbol' => 'MSFT', 'shares' => 50, 'price' => 250.0],   // $12,500 = 31.25%
        ];  // Total: $40,000
        
        $targetAllocations = [
            'AAPL' => 33.33,
            'GOOGL' => 33.33,
            'MSFT' => 33.33
        ];
        
        $needsRebalancing = $this->rebalancer->needsRebalancing($holdings, $targetAllocations, 5.0);
        
        // AAPL is 56.25% vs target 33.33% = 22.92% drift (exceeds 5% threshold)
        $this->assertTrue($needsRebalancing);
    }

    /**
     * @test
     */
    public function itDoesNotRebalanceWithinThreshold(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 100, 'price' => 150.0],  // $15,000 = 35%
            ['symbol' => 'GOOGL', 'shares' => 60, 'price' => 100.0],  // $6,000 = 14%
            ['symbol' => 'MSFT' , 'shares' => 88, 'price' => 250.0],  // $22,000 = 51%
        ];  // Total: $43,000
        
        $targetAllocations = [
            'AAPL' => 33.33,
            'GOOGL' => 33.33,
            'MSFT' => 33.33
        ];
        
        // Use 20% threshold - drifts are within threshold
        $needsRebalancing = $this->rebalancer->needsRebalancing($holdings, $targetAllocations, 20.0);
        
        $this->assertFalse($needsRebalancing);
    }

    /**
     * @test
     */
    public function itGeneratesRebalancingActions(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0],  // $22,500 = 56.25%
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0],  // $5,000 = 12.5%
            ['symbol' => 'MSFT', 'shares' => 50, 'price' => 250.0],   // $12,500 = 31.25%
        ];  // Total: $40,000
        
        $targetAllocations = [
            'AAPL' => 33.33,   // Target: $13,332
            'GOOGL' => 33.33,  // Target: $13,332
            'MSFT' => 33.33    // Target: $13,332
        ];
        
        $actions = $this->rebalancer->generateRebalancingActions($holdings, $targetAllocations);
        
        $this->assertIsArray($actions);
        $this->assertNotEmpty($actions);
        
        // Should have SELL action for AAPL (overweight)
        $aaplAction = array_filter($actions, fn($a) => $a['symbol'] === 'AAPL');
        $this->assertNotEmpty($aaplAction);
        $this->assertEquals('SELL', reset($aaplAction)['action']);
        
        // Should have BUY actions for GOOGL and MSFT (underweight)
        $googlAction = array_filter($actions, fn($a) => $a['symbol'] === 'GOOGL');
        $msftAction = array_filter($actions, fn($a) => $a['symbol'] === 'MSFT');
        $this->assertEquals('BUY', reset($googlAction)['action']);
        $this->assertEquals('BUY', reset($msftAction)['action']);
    }

    /**
     * @test
     */
    public function itCalculatesShareAmountsForActions(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0],  // $22,500 = 56.25%
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0],  // $5,000 = 12.5%
            ['symbol' => 'MSFT', 'shares' => 50, 'price' => 250.0],   // $12,500 = 31.25%
        ];
        
        $targetAllocations = [
            'AAPL' => 33.33,
            'GOOGL' => 33.33,
            'MSFT' => 33.33
        ];
        
        $actions = $this->rebalancer->generateRebalancingActions($holdings, $targetAllocations);
        
        foreach ($actions as $action) {
            $this->assertArrayHasKey('shares', $action);
            $this->assertGreaterThan(0, $action['shares']);
        }
    }

    /**
     * @test
     */
    public function itIncludesCurrentAndTargetValues(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0],
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0],
            ['symbol' => 'MSFT', 'shares' => 50, 'price' => 250.0],
        ];
        
        $targetAllocations = [
            'AAPL' => 33.33,
            'GOOGL' => 33.33,
            'MSFT' => 33.33
        ];
        
        $actions = $this->rebalancer->generateRebalancingActions($holdings, $targetAllocations);
        
        foreach ($actions as $action) {
            $this->assertArrayHasKey('current_value', $action);
            $this->assertArrayHasKey('target_value', $action);
            $this->assertArrayHasKey('difference', $action);
        }
    }

    /**
     * @test
     */
    public function itCalculatesTransactionCosts(): void
    {
        $actions = [
            ['symbol' => 'AAPL', 'action' => 'SELL', 'shares' => 61, 'price' => 150.0],  // $9,150
            ['symbol' => 'GOOGL', 'action' => 'BUY', 'shares' => 83, 'price' => 100.0],  // $8,300
            ['symbol' => 'MSFT', 'action' => 'BUY', 'shares' => 3, 'price' => 250.0],    // $750
        ];
        
        $costs = $this->rebalancer->calculateTransactionCosts($actions, 0.01);  // 1% fee
        
        // Total value: $9,150 + $8,300 + $750 = $18,200
        // Cost at 1%: $182
        $this->assertEqualsWithDelta(182.0, $costs, 0.5);
    }

    /**
     * @test
     */
    public function itEstimatesTaxImpact(): void
    {
        $actions = [
            [
                'symbol' => 'AAPL',
                'action' => 'SELL',
                'shares' => 61,
                'price' => 150.0,
                'cost_basis' => 100.0  // Bought at $100, selling at $150
            ]
        ];
        
        $taxImpact = $this->rebalancer->estimateTaxImpact($actions, 0.20);  // 20% capital gains tax
        
        // Capital gain: (150 - 100) * 61 = $3,050
        // Tax at 20%: $610
        $this->assertEqualsWithDelta(610.0, $taxImpact, 0.5);
    }

    /**
     * @test
     */
    public function itIgnoresTaxOnBuyActions(): void
    {
        $actions = [
            ['symbol' => 'GOOGL', 'action' => 'BUY', 'shares' => 83, 'price' => 100.0]
        ];
        
        $taxImpact = $this->rebalancer->estimateTaxImpact($actions, 0.20);
        
        // No tax on purchases
        $this->assertEquals(0.0, $taxImpact);
    }

    /**
     * @test
     */
    public function itGeneratesRebalancingSummary(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0, 'cost_basis' => 120.0],
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0, 'cost_basis' => 90.0],
            ['symbol' => 'MSFT', 'shares' => 50, 'price' => 250.0, 'cost_basis' => 200.0],
        ];
        
        $targetAllocations = [
            'AAPL' => 33.33,
            'GOOGL' => 33.33,
            'MSFT' => 33.33
        ];
        
        $summary = $this->rebalancer->generateSummary($holdings, $targetAllocations, 0.01, 0.20);
        
        $this->assertArrayHasKey('actions', $summary);
        $this->assertArrayHasKey('transaction_costs', $summary);
        $this->assertArrayHasKey('estimated_tax', $summary);
        $this->assertArrayHasKey('total_cost', $summary);
        $this->assertArrayHasKey('portfolio_value', $summary);
        $this->assertArrayHasKey('recommendations', $summary);
    }

    /**
     * @test
     */
    public function itProvidesRecommendations(): void
    {
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 150, 'price' => 150.0, 'cost_basis' => 120.0],
            ['symbol' => 'GOOGL', 'shares' => 50, 'price' => 100.0, 'cost_basis' => 90.0],
        ];
        
        $targetAllocations = [
            'AAPL' => 50.0,
            'GOOGL' => 50.0
        ];
        
        $summary = $this->rebalancer->generateSummary($holdings, $targetAllocations, 0.01, 0.20);
        
        $this->assertIsArray($summary['recommendations']);
        $this->assertNotEmpty($summary['recommendations']);
    }

    /**
     * @test
     */
    public function itHandlesEmptyHoldings(): void
    {
        $holdings = [];
        $targetAllocations = [
            'AAPL' => 50.0,
            'GOOGL' => 50.0
        ];
        
        $allocations = $this->rebalancer->calculateCurrentAllocations($holdings);
        
        $this->assertEmpty($allocations);
    }

    /**
     * @test
     */
    public function itValidatesTargetAllocations(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Target allocations must sum to 100%');
        
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 100, 'price' => 150.0],
        ];
        
        $invalidTargets = [
            'AAPL' => 50.0,  // Only 50%, should be 100%
        ];
        
        $this->rebalancer->generateRebalancingActions($holdings, $invalidTargets);
    }

    /**
     * @test
     */
    public function itAllowsCustomThreshold(): void
    {
        $rebalancer = new PortfolioRebalancer([
            'drift_threshold' => 10.0  // 10% threshold instead of default 5%
        ]);
        
        $config = $rebalancer->getConfiguration();
        
        $this->assertEquals(10.0, $config['drift_threshold']);
    }

    /**
     * @test
     */
    public function itOptimizesForTaxEfficiency(): void
    {
        // High cost basis position (small gain) vs low cost basis (large gain)
        $holdings = [
            ['symbol' => 'AAPL', 'shares' => 100, 'price' => 150.0, 'cost_basis' => 145.0],  // Small gain
            ['symbol' => 'GOOGL', 'shares' => 100, 'price' => 150.0, 'cost_basis' => 50.0],  // Large gain
        ];
        
        $targetAllocations = [
            'AAPL' => 45.0,  // Need to sell some
            'GOOGL' => 55.0
        ];
        
        $summary = $this->rebalancer->generateSummary(
            $holdings,
            $targetAllocations,
            0.01,
            0.20,
            ['tax_efficient' => true]
        );
        
        // Should prefer selling AAPL (smaller tax impact) over GOOGL
        $aaplAction = array_filter($summary['actions'], fn($a) => $a['symbol'] === 'AAPL');
        $this->assertNotEmpty($aaplAction);
        $this->assertEquals('SELL', reset($aaplAction)['action']);
    }
}
