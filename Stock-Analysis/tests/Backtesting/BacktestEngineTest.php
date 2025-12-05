<?php

declare(strict_types=1);

namespace Tests\Backtesting;

use App\Backtesting\BacktestEngine;
use App\Services\Trading\TradingStrategyInterface;
use PHPUnit\Framework\TestCase;

/**
 * BacktestEngine Test Suite
 * 
 * Tests backtesting engine including:
 * - Strategy execution on historical data
 * - Trade simulation (buy/sell based on signals)
 * - Position management
 * - Portfolio value tracking
 * - Trade log generation
 * - Performance calculation
 * 
 * @package Tests\Backtesting
 */
class BacktestEngineTest extends TestCase
{
    private BacktestEngine $engine;
    
    protected function setUp(): void
    {
        $this->engine = new BacktestEngine([
            'initial_capital' => 10000.0,
            'commission' => 0.001, // 0.1% commission
            'slippage' => 0.0005   // 0.05% slippage
        ]);
    }
    
    /**
     * @test
     */
    public function itInitializesWithConfiguration(): void
    {
        $config = $this->engine->getConfiguration();
        
        $this->assertEquals(10000.0, $config['initial_capital']);
        $this->assertEquals(0.001, $config['commission']);
        $this->assertEquals(0.0005, $config['slippage']);
    }
    
    /**
     * @test
     */
    public function itRunsBacktestWithStrategy(): void
    {
        $strategy = $this->createMockStrategy();
        
        $historicalData = $this->createHistoricalData();
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertArrayHasKey('symbol', $result);
        $this->assertArrayHasKey('trades', $result);
        $this->assertArrayHasKey('final_value', $result);
        $this->assertArrayHasKey('return_pct', $result);
    }
    
    /**
     * @test
     */
    public function itExecutesBuySignal(): void
    {
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 105.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertNotEmpty($result['trades']);
        $this->assertEquals('BUY', $result['trades'][0]['action']);
    }
    
    /**
     * @test
     */
    public function itExecutesSellSignal(): void
    {
        // First buy, then sell
        $strategy = $this->createBuySellStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0], // BUY
            ['date' => '2024-01-02', 'close' => 110.0], // SELL
            ['date' => '2024-01-03', 'close' => 105.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertCount(2, $result['trades']);
        $this->assertEquals('BUY', $result['trades'][0]['action']);
        $this->assertEquals('SELL', $result['trades'][1]['action']);
    }
    
    /**
     * @test
     */
    public function itCalculatesPortfolioValue(): void
    {
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 110.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertGreaterThan(10000.0, $result['final_value']);
    }
    
    /**
     * @test
     */
    public function itCalculatesReturnPercentage(): void
    {
        $strategy = $this->createBuyHoldStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 120.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertGreaterThan(0, $result['return_pct']);
    }
    
    /**
     * @test
     */
    public function itAppliesCommission(): void
    {
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertArrayHasKey('total_commission', $result);
        $this->assertGreaterThan(0, $result['total_commission']);
    }
    
    /**
     * @test
     */
    public function itAppliesSlippage(): void
    {
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        // Buy price should be higher than close due to slippage
        $actualPrice = $result['trades'][0]['price'];
        $this->assertGreaterThan(100.0, $actualPrice);
    }
    
    /**
     * @test
     */
    public function itTracksPositionSize(): void
    {
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertArrayHasKey('shares', $result['trades'][0]);
        $this->assertGreaterThan(0, $result['trades'][0]['shares']);
    }
    
    /**
     * @test
     */
    public function itPreventsShortSelling(): void
    {
        // Try to sell without position
        $strategy = $this->createSellStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        // Should have no trades
        $this->assertEmpty($result['trades']);
    }
    
    /**
     * @test
     */
    public function itIgnoresHoldSignals(): void
    {
        $strategy = $this->createHoldStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 105.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertEmpty($result['trades']);
    }
    
    /**
     * @test
     */
    public function itCalculatesMaxDrawdown(): void
    {
        $strategy = $this->createVolatileStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 110.0],
            ['date' => '2024-01-03', 'close' => 90.0],
            ['date' => '2024-01-04', 'close' => 120.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertArrayHasKey('max_drawdown', $result);
        $this->assertLessThan(0, $result['max_drawdown']);
    }
    
    /**
     * @test
     */
    public function itTracksEquityCurve(): void
    {
        $strategy = $this->createBuyHoldStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 100.0],
            ['date' => '2024-01-02', 'close' => 105.0],
            ['date' => '2024-01-03', 'close' => 110.0]
        ];
        
        $result = $this->engine->run($strategy, 'AAPL', $historicalData);
        
        $this->assertArrayHasKey('equity_curve', $result);
        $this->assertCount(3, $result['equity_curve']);
    }
    
    /**
     * @test
     */
    public function itRequiresSymbol(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $strategy = $this->createMockStrategy();
        $this->engine->run($strategy, '', []);
    }
    
    /**
     * @test
     */
    public function itRequiresHistoricalData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $strategy = $this->createMockStrategy();
        $this->engine->run($strategy, 'AAPL', []);
    }
    
    /**
     * @test
     */
    public function itHandlesInsufficientCapital(): void
    {
        $engine = new BacktestEngine(['initial_capital' => 10.0]);
        $strategy = $this->createBuyStrategy();
        
        $historicalData = [
            ['date' => '2024-01-01', 'close' => 1000.0]
        ];
        
        $result = $engine->run($strategy, 'AAPL', $historicalData);
        
        // Should skip trade due to insufficient capital
        $this->assertEmpty($result['trades']);
    }
    
    // Helper methods
    
    private function createMockStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => 'HOLD',
            'confidence' => 0.5,
            'reason' => 'Mock signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createBuyStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => 'BUY',
            'confidence' => 0.8,
            'reason' => 'Buy signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createSellStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => 'SELL',
            'confidence' => 0.8,
            'reason' => 'Sell signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createHoldStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturn([
            'signal' => 'HOLD',
            'confidence' => 0.5,
            'reason' => 'Hold signal',
            'metadata' => []
        ]);
        
        return $strategy;
    }
    
    private function createBuySellStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturnOnConsecutiveCalls(
            ['signal' => 'BUY', 'confidence' => 0.8, 'reason' => 'Buy signal', 'metadata' => []],
            ['signal' => 'SELL', 'confidence' => 0.8, 'reason' => 'Sell signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []]
        );
        
        return $strategy;
    }
    
    private function createBuyHoldStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturnOnConsecutiveCalls(
            ['signal' => 'BUY', 'confidence' => 0.8, 'reason' => 'Buy signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []]
        );
        
        return $strategy;
    }
    
    private function createVolatileStrategy(): TradingStrategyInterface
    {
        $strategy = $this->createMock(TradingStrategyInterface::class);
        $strategy->method('analyze')->willReturnOnConsecutiveCalls(
            ['signal' => 'BUY', 'confidence' => 0.8, 'reason' => 'Buy signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []],
            ['signal' => 'HOLD', 'confidence' => 0.5, 'reason' => 'Hold signal', 'metadata' => []]
        );
        
        return $strategy;
    }
    
    private function createHistoricalData(): array
    {
        return [
            ['date' => '2024-01-01', 'open' => 98.0, 'high' => 102.0, 'low' => 97.0, 'close' => 100.0, 'volume' => 1000000],
            ['date' => '2024-01-02', 'open' => 100.0, 'high' => 108.0, 'low' => 99.0, 'close' => 105.0, 'volume' => 1200000],
            ['date' => '2024-01-03', 'open' => 105.0, 'high' => 112.0, 'low' => 104.0, 'close' => 110.0, 'volume' => 1100000]
        ];
    }
}
