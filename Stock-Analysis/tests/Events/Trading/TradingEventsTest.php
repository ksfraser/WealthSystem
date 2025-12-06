<?php

declare(strict_types=1);

namespace Tests\Events\Trading;

use PHPUnit\Framework\TestCase;
use App\Events\Trading\TradeExecutedEvent;
use App\Events\Trading\SignalGeneratedEvent;
use App\Events\Trading\AlertGeneratedEvent;

class TradingEventsTest extends TestCase
{
    public function testTradeExecutedEvent(): void
    {
        $event = new TradeExecutedEvent(
            'AAPL',
            'BUY',
            100.0,
            150.00,
            ['order_id' => 'ORD123']
        );
        
        $this->assertSame('AAPL', $event->getSymbol());
        $this->assertSame('BUY', $event->getAction());
        $this->assertSame(100.0, $event->getQuantity());
        $this->assertSame(150.00, $event->getPrice());
        $this->assertSame(15000.0, $event->getTotalValue());
        $this->assertGreaterThan(0, $event->getTimestamp());
        $this->assertSame(['order_id' => 'ORD123'], $event->getMetadata());
    }
    
    public function testTradeExecutedEventToArray(): void
    {
        $event = new TradeExecutedEvent('TSLA', 'SELL', 50.0, 250.00);
        
        $array = $event->toArray();
        
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('quantity', $array);
        $this->assertArrayHasKey('price', $array);
        $this->assertArrayHasKey('total_value', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertSame(12500.0, $array['total_value']);
    }
    
    public function testSignalGeneratedEvent(): void
    {
        $signal = [
            'action' => 'BUY',
            'confidence' => 0.85,
            'entry_price' => 45.00
        ];
        
        $event = new SignalGeneratedEvent('MeanReversion', 'NVDA', $signal);
        
        $this->assertSame('MeanReversion', $event->getStrategy());
        $this->assertSame('NVDA', $event->getSymbol());
        $this->assertSame($signal, $event->getSignal());
        $this->assertSame('BUY', $event->getAction());
        $this->assertSame(0.85, $event->getConfidence());
        $this->assertTrue($event->isActionable());
    }
    
    public function testSignalGeneratedEventNonActionable(): void
    {
        $signal = ['action' => 'NONE', 'confidence' => 0.0];
        
        $event = new SignalGeneratedEvent('Momentum', 'AMD', $signal);
        
        $this->assertFalse($event->isActionable());
    }
    
    public function testSignalGeneratedEventToArray(): void
    {
        $signal = ['action' => 'SELL', 'confidence' => 0.9];
        $event = new SignalGeneratedEvent('Breakout', 'GOOG', $signal);
        
        $array = $event->toArray();
        
        $this->assertArrayHasKey('strategy', $array);
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('signal', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }
    
    public function testAlertGeneratedEvent(): void
    {
        $event = new AlertGeneratedEvent(
            'price_spike',
            'BTC',
            'Bitcoin price spiked 10% in 1 hour',
            'high',
            ['change_percent' => 10.5]
        );
        
        $this->assertSame('price_spike', $event->getType());
        $this->assertSame('BTC', $event->getSymbol());
        $this->assertSame('Bitcoin price spiked 10% in 1 hour', $event->getMessage());
        $this->assertSame('high', $event->getSeverity());
        $this->assertTrue($event->isHighSeverity());
        $this->assertSame(['change_percent' => 10.5], $event->getData());
    }
    
    public function testAlertGeneratedEventDefaultSeverity(): void
    {
        $event = new AlertGeneratedEvent(
            'volume_increase',
            'ETH',
            'Volume increased significantly'
        );
        
        $this->assertSame('medium', $event->getSeverity());
        $this->assertFalse($event->isHighSeverity());
    }
    
    public function testAlertGeneratedEventToArray(): void
    {
        $event = new AlertGeneratedEvent(
            'gap_down',
            'TSLA',
            'Gap down detected',
            'medium',
            ['gap_percent' => -5.2]
        );
        
        $array = $event->toArray();
        
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('symbol', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('severity', $array);
        $this->assertArrayHasKey('data', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }
}
