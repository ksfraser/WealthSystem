<?php

declare(strict_types=1);

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;
use App\WebSocket\PriceStreamClient;
use App\Exceptions\DataException;

class PriceStreamClientTest extends TestCase
{
    private PriceStreamClient $client;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new PriceStreamClient();
    }
    
    public function testClientCreation(): void
    {
        $this->assertInstanceOf(PriceStreamClient::class, $this->client);
        $this->assertFalse($this->client->isConnected());
    }
    
    public function testConnect(): void
    {
        $result = $this->client->connect();
        
        $this->assertTrue($result);
        $this->assertTrue($this->client->isConnected());
    }
    
    public function testDisconnect(): void
    {
        $this->client->connect();
        $this->client->disconnect();
        
        $this->assertFalse($this->client->isConnected());
    }
    
    public function testSubscribe(): void
    {
        $this->client->connect();
        
        $called = false;
        $this->client->subscribe('BTC', function($data) use (&$called) {
            $called = true;
        });
        
        $subscriptions = $this->client->getSubscriptions();
        
        $this->assertContains('BTC', $subscriptions);
    }
    
    public function testSubscribeWhenNotConnected(): void
    {
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Not connected to WebSocket server');
        
        $this->client->subscribe('BTC', function($data) {});
    }
    
    public function testUnsubscribe(): void
    {
        $this->client->connect();
        $this->client->subscribe('BTC', function($data) {});
        $this->client->unsubscribe('BTC');
        
        $subscriptions = $this->client->getSubscriptions();
        
        $this->assertNotContains('BTC', $subscriptions);
    }
    
    public function testSendMessage(): void
    {
        $this->client->connect();
        
        $message = ['type' => 'ping', 'timestamp' => time()];
        $result = $this->client->send($message);
        
        $this->assertTrue($result);
        
        $buffer = $this->client->getMessageBuffer();
        $this->assertCount(1, $buffer);
        $this->assertSame($message, $buffer[0]);
    }
    
    public function testSendWhenNotConnected(): void
    {
        $this->expectException(DataException::class);
        
        $this->client->send(['type' => 'test']);
    }
    
    public function testHandleMessage(): void
    {
        $this->client->connect();
        
        $receivedData = null;
        $this->client->subscribe('ETH', function($data) use (&$receivedData) {
            $receivedData = $data;
        });
        
        $priceData = ['price' => 3500.00, 'timestamp' => time()];
        $this->client->handleMessage([
            'type' => 'price_update',
            'symbol' => 'ETH',
            'data' => $priceData
        ]);
        
        $this->assertSame($priceData, $receivedData);
    }
    
    public function testSimulatePriceUpdate(): void
    {
        $this->client->connect();
        
        $receivedData = null;
        $this->client->subscribe('SOL', function($data) use (&$receivedData) {
            $receivedData = $data;
        });
        
        $priceData = ['price' => 150.00];
        $this->client->simulatePriceUpdate('SOL', $priceData);
        
        $this->assertSame($priceData, $receivedData);
    }
    
    public function testMultipleSubscriptions(): void
    {
        $this->client->connect();
        
        $this->client->subscribe('BTC', function($data) {});
        $this->client->subscribe('ETH', function($data) {});
        $this->client->subscribe('SOL', function($data) {});
        
        $subscriptions = $this->client->getSubscriptions();
        
        $this->assertCount(3, $subscriptions);
        $this->assertContains('BTC', $subscriptions);
        $this->assertContains('ETH', $subscriptions);
        $this->assertContains('SOL', $subscriptions);
    }
    
    public function testClearMessageBuffer(): void
    {
        $this->client->connect();
        
        $this->client->send(['type' => 'test1']);
        $this->client->send(['type' => 'test2']);
        
        $this->assertCount(2, $this->client->getMessageBuffer());
        
        $this->client->clearMessageBuffer();
        
        $this->assertEmpty($this->client->getMessageBuffer());
    }
    
    public function testReconnect(): void
    {
        $this->client->connect();
        $this->assertTrue($this->client->isConnected());
        
        $this->client->disconnect();
        $this->assertFalse($this->client->isConnected());
        
        $this->client->connect();
        $this->assertTrue($this->client->isConnected());
    }
}
