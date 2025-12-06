<?php

declare(strict_types=1);

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;
use App\WebSocket\LiveStrategyMonitor;
use App\WebSocket\PriceStreamClient;

class LiveStrategyMonitorTest extends TestCase
{
    private LiveStrategyMonitor $monitor;
    private PriceStreamClient $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->client = new PriceStreamClient();
        $this->client->connect();
        
        $this->monitor = new LiveStrategyMonitor($this->client);
    }
    
    public function testMonitorCreation(): void
    {
        $this->assertInstanceOf(LiveStrategyMonitor::class, $this->monitor);
        $this->assertSame(0, $this->monitor->getStrategyCount());
    }
    
    public function testAddStrategy(): void
    {
        $this->monitor->addStrategy('AAPL', function($priceData) {
            return ['action' => 'NONE'];
        });
        
        $this->assertSame(1, $this->monitor->getStrategyCount());
        $this->assertContains('AAPL', $this->monitor->getMonitoredSymbols());
    }
    
    public function testRemoveStrategy(): void
    {
        $this->monitor->addStrategy('TSLA', function($priceData) {
            return ['action' => 'NONE'];
        });
        
        $this->monitor->removeStrategy('TSLA');
        
        $this->assertSame(0, $this->monitor->getStrategyCount());
        $this->assertNotContains('TSLA', $this->monitor->getMonitoredSymbols());
    }
    
    public function testStrategyExecution(): void
    {
        $executed = false;
        
        $this->monitor->addStrategy('NVDA', function($priceData) use (&$executed) {
            $executed = true;
            return [
                'action' => 'BUY',
                'confidence' => 0.8,
                'entry_price' => $priceData['price']
            ];
        });
        
        $this->client->simulatePriceUpdate('NVDA', [
            'price' => 500.00,
            'timestamp' => time()
        ]);
        
        $this->assertTrue($executed);
    }
    
    public function testGetLatestPrice(): void
    {
        $this->monitor->addStrategy('AMD', function($priceData) {
            return ['action' => 'NONE'];
        });
        
        $priceData = ['price' => 120.00, 'timestamp' => time()];
        $this->client->simulatePriceUpdate('AMD', $priceData);
        
        $latest = $this->monitor->getLatestPrice('AMD');
        
        $this->assertSame($priceData, $latest);
    }
    
    public function testGetLatestPriceForNonExistentSymbol(): void
    {
        $latest = $this->monitor->getLatestPrice('UNKNOWN');
        
        $this->assertNull($latest);
    }
    
    public function testMultipleStrategies(): void
    {
        $this->monitor->addStrategy('BTC', function($data) {
            return ['action' => 'NONE'];
        });
        $this->monitor->addStrategy('ETH', function($data) {
            return ['action' => 'NONE'];
        });
        $this->monitor->addStrategy('SOL', function($data) {
            return ['action' => 'NONE'];
        });
        
        $this->assertSame(3, $this->monitor->getStrategyCount());
        
        $symbols = $this->monitor->getMonitoredSymbols();
        $this->assertCount(3, $symbols);
    }
    
    public function testSignalGeneration(): void
    {
        $this->monitor->addStrategy('GOOG', function($priceData) {
            if ($priceData['price'] > 150) {
                return [
                    'action' => 'SELL',
                    'confidence' => 0.9,
                    'exit_price' => $priceData['price']
                ];
            }
            return ['action' => 'NONE'];
        });
        
        $this->client->simulatePriceUpdate('GOOG', ['price' => 160.00]);
        
        $messages = $this->client->getMessageBuffer();
        
        $this->assertNotEmpty($messages);
        
        $signalMessage = null;
        foreach ($messages as $msg) {
            if ($msg['type'] === 'signal') {
                $signalMessage = $msg;
                break;
            }
        }
        
        $this->assertNotNull($signalMessage);
        $this->assertSame('GOOG', $signalMessage['symbol']);
        $this->assertSame('SELL', $signalMessage['signal']['action']);
    }
}
