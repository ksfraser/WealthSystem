<?php

declare(strict_types=1);

namespace Tests\Optimization;

use PHPUnit\Framework\TestCase;
use App\Optimization\BacktestEngine;
use App\Services\Trading\TradingStrategyInterface;

class BacktestTest extends TestCase
{
    public function testBacktestBasicRun(): void
    {
        $engine = new BacktestEngine();
        
        $strategy = $this->createMockStrategy([
            ['signal' => 'BUY', 'confidence' => 0.8],
            ['signal' => 'HOLD', 'confidence' => 0.5],
            ['signal' => 'SELL', 'confidence' => 0.8],
        ]);
        
        $historicalData = [
            ['symbol' => 'AAPL', 'close' => 100],
            ['symbol' => 'AAPL', 'close' => 105],
            ['symbol' => 'AAPL', 'close' => 110],
        ];
        
        $result = $engine->run($strategy, $historicalData, 10000.0);
        
        $this->assertArrayHasKey('initial_capital', $result);
        $this->assertArrayHasKey('final_value', $result);
        $this->assertArrayHasKey('returns', $result);
        $this->assertArrayHasKey('total_trades', $result);
        $this->assertSame(10000.0, $result['initial_capital']);
    }
    
    public function testBacktestProfitableTrade(): void
    {
        $engine = new BacktestEngine();
        
        $strategy = $this->createMockStrategy([
            ['signal' => 'BUY', 'confidence' => 0.9],
            ['signal' => 'SELL', 'confidence' => 0.9],
        ]);
        
        $historicalData = [
            ['symbol' => 'TEST', 'close' => 100],
            ['symbol' => 'TEST', 'close' => 120],
        ];
        
        $result = $engine->run($strategy, $historicalData, 10000.0);
        
        $this->assertSame(12000.0, $result['final_value']);
        $this->assertSame(0.2, $result['returns']);
        $this->assertSame(20.0, $result['returns_percent']);
        $this->assertSame(2, $result['total_trades']);
    }
    
    public function testBacktestLosingTrade(): void
    {
        $engine = new BacktestEngine();
        
        $strategy = $this->createMockStrategy([
            ['signal' => 'BUY', 'confidence' => 0.9],
            ['signal' => 'SELL', 'confidence' => 0.9],
        ]);
        
        $historicalData = [
            ['symbol' => 'TEST', 'close' => 100],
            ['symbol' => 'TEST', 'close' => 80],
        ];
        
        $result = $engine->run($strategy, $historicalData, 10000.0);
        
        $this->assertSame(8000.0, $result['final_value']);
        $this->assertSame(-0.2, $result['returns']);
        $this->assertSame(-20.0, $result['returns_percent']);
    }
    
    public function testBacktestNoTrades(): void
    {
        $engine = new BacktestEngine();
        
        $strategy = $this->createMockStrategy([
            ['signal' => 'HOLD', 'confidence' => 0.5],
            ['signal' => 'HOLD', 'confidence' => 0.5],
        ]);
        
        $historicalData = [
            ['symbol' => 'TEST', 'close' => 100],
            ['symbol' => 'TEST', 'close' => 110],
        ];
        
        $result = $engine->run($strategy, $historicalData, 10000.0);
        
        $this->assertSame(10000.0, $result['final_value']);
        $this->assertSame(0.0, $result['returns']);
        $this->assertSame(0, $result['total_trades']);
    }
    
    public function testBacktestMultipleTrades(): void
    {
        $engine = new BacktestEngine();
        
        $strategy = $this->createMockStrategy([
            ['signal' => 'BUY', 'confidence' => 0.9],
            ['signal' => 'SELL', 'confidence' => 0.9],
            ['signal' => 'BUY', 'confidence' => 0.9],
            ['signal' => 'SELL', 'confidence' => 0.9],
        ]);
        
        $historicalData = [
            ['symbol' => 'TEST', 'close' => 100],
            ['symbol' => 'TEST', 'close' => 110],
            ['symbol' => 'TEST', 'close' => 105],
            ['symbol' => 'TEST', 'close' => 120],
        ];
        
        $result = $engine->run($strategy, $historicalData, 10000.0);
        
        $this->assertSame(4, $result['total_trades']);
        $this->assertGreaterThan(10000.0, $result['final_value']);
    }
    
    public function testBacktestSharpeRatio(): void
    {
        $engine = new BacktestEngine();
        
        $trades = [
            ['type' => 'BUY', 'price' => 100],
            ['type' => 'SELL', 'price' => 110],
            ['type' => 'BUY', 'price' => 105],
            ['type' => 'SELL', 'price' => 115],
        ];
        
        $sharpe = $engine->calculateSharpeRatio($trades, [], 0.02);
        
        $this->assertIsFloat($sharpe);
    }
    
    public function testBacktestSharpeRatioNoTrades(): void
    {
        $engine = new BacktestEngine();
        
        $sharpe = $engine->calculateSharpeRatio([], [], 0.02);
        
        $this->assertSame(0.0, $sharpe);
    }
    
    public function testBacktestSharpeRatioSingleTrade(): void
    {
        $engine = new BacktestEngine();
        
        $trades = [
            ['type' => 'BUY', 'price' => 100],
        ];
        
        $sharpe = $engine->calculateSharpeRatio($trades, [], 0.02);
        
        $this->assertSame(0.0, $sharpe);
    }
    
    private function createMockStrategy(array $signals): TradingStrategyInterface
    {
        return new class($signals) implements TradingStrategyInterface {
            private array $signals;
            private int $index = 0;
            
            public function __construct(array $signals)
            {
                $this->signals = $signals;
            }
            
            public function analyze(string $symbol, string $date = 'today'): array
            {
                $signal = $this->signals[$this->index] ?? ['signal' => 'HOLD', 'confidence' => 0.0];
                $this->index++;
                return $signal;
            }
            
            public function getName(): string
            {
                return 'MockStrategy';
            }
            
            public function getDescription(): string
            {
                return 'Mock strategy for testing';
            }
            
            public function getParameters(): array
            {
                return [];
            }
            
            public function setParameters(array $parameters): void
            {
            }
            
            public function canExecute(string $symbol): bool
            {
                return true;
            }
            
            public function getRequiredHistoricalDays(): int
            {
                return 1;
            }
        };
    }
}
