<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use PHPUnit\Framework\TestCase;
use App\Exceptions\TradingException;
use App\Exceptions\StrategyException;
use App\Exceptions\BacktestException;
use App\Exceptions\DataException;
use App\Exceptions\AlertException;

class TradingExceptionTest extends TestCase
{
    public function testBasicException(): void
    {
        $exception = new TradingException('Test error', 500);
        
        $this->assertSame('Test error', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
        $this->assertSame('error', $exception->getSeverity());
        $this->assertEmpty($exception->getContext());
    }
    
    public function testExceptionWithContext(): void
    {
        $context = ['symbol' => 'AAPL', 'price' => 150.00];
        $exception = new TradingException('Trade failed', 400, null, $context);
        
        $this->assertSame($context, $exception->getContext());
    }
    
    public function testExceptionToArray(): void
    {
        $context = ['action' => 'BUY', 'quantity' => 100];
        $exception = new TradingException('Order failed', 403, null, $context);
        
        $array = $exception->toArray();
        
        $this->assertSame(TradingException::class, $array['exception']);
        $this->assertSame('Order failed', $array['message']);
        $this->assertSame(403, $array['code']);
        $this->assertSame('error', $array['severity']);
        $this->assertSame($context, $array['context']);
        $this->assertArrayHasKey('file', $array);
        $this->assertArrayHasKey('line', $array);
    }
    
    public function testStrategyException(): void
    {
        $exception = new StrategyException('Strategy error');
        
        $this->assertInstanceOf(TradingException::class, $exception);
        $this->assertSame('error', $exception->getSeverity());
    }
    
    public function testBacktestException(): void
    {
        $exception = new BacktestException('Backtest failed');
        
        $this->assertInstanceOf(TradingException::class, $exception);
        $this->assertSame('error', $exception->getSeverity());
    }
    
    public function testDataException(): void
    {
        $exception = new DataException('Data fetch failed');
        
        $this->assertInstanceOf(TradingException::class, $exception);
        $this->assertSame('warning', $exception->getSeverity());
    }
    
    public function testAlertException(): void
    {
        $exception = new AlertException('Alert delivery failed');
        
        $this->assertInstanceOf(TradingException::class, $exception);
        $this->assertSame('warning', $exception->getSeverity());
    }
    
    public function testExceptionChaining(): void
    {
        $previous = new \Exception('Root cause');
        $exception = new TradingException('Wrapped error', 500, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
    
    public function testExceptionInTryCatch(): void
    {
        $this->expectException(StrategyException::class);
        $this->expectExceptionMessage('Strategy calculation failed');
        
        throw new StrategyException('Strategy calculation failed', 500, null, [
            'strategy' => 'MeanReversion',
            'symbol' => 'TSLA'
        ]);
    }
    
    public function testMultipleContextValues(): void
    {
        $context = [
            'user_id' => 123,
            'strategy' => 'Momentum',
            'symbol' => 'NVDA',
            'timestamp' => '2024-01-15 10:30:00',
            'market_status' => 'open'
        ];
        
        $exception = new TradingException('Complex error', 500, null, $context);
        
        $this->assertSame($context, $exception->getContext());
        $this->assertCount(5, $exception->getContext());
    }
}
