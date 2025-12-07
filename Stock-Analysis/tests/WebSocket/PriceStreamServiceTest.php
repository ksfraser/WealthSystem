<?php

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;
use App\WebSocket\PriceStreamService;
use App\WebSocket\WebSocketInterface;
use App\Services\EventDispatcher;
use Psr\Log\NullLogger;

/**
 * PriceStreamService Tests
 * 
 * Tests the real-time price streaming service with mocked WebSocket.
 */
class PriceStreamServiceTest extends TestCase
{
    private PriceStreamService $stream;
    private MockWebSocket $websocket;
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->websocket = new MockWebSocket();
        $this->dispatcher = new EventDispatcher();
        $this->stream = new PriceStreamService(
            $this->websocket,
            $this->dispatcher,
            new NullLogger()
        );
    }

    public function testSetAndCheckAlert(): void
    {
        $this->stream->setAlert('AAPL', [
            'above' => 200.00,
            'below' => 150.00,
        ]);

        // Trigger alert by simulating price update
        $alertTriggered = false;
        $this->dispatcher->on('price.alert', function ($data) use (&$alertTriggered) {
            $alertTriggered = true;
        });

        // Simulate price above threshold
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 201.00,
            'volume' => 1000000,
            'timestamp' => time(),
        ]);

        $this->assertTrue($alertTriggered);
    }

    public function testClearAlerts(): void
    {
        $this->stream->setAlert('AAPL', ['above' => 200.00]);
        $this->stream->clearAlerts('AAPL');

        $alertTriggered = false;
        $this->dispatcher->on('price.alert', function () use (&$alertTriggered) {
            $alertTriggered = true;
        });

        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 201.00,
            'timestamp' => time(),
        ]);

        $this->assertFalse($alertTriggered);
    }

    public function testGetLastPrice(): void
    {
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 150.00,
            'timestamp' => time(),
        ]);

        $lastPrice = $this->stream->getLastPrice('AAPL');
        $this->assertEquals(150.00, $lastPrice);
    }

    public function testGetAllPrices(): void
    {
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 150.00,
            'timestamp' => time(),
        ]);

        $this->websocket->simulateMessage([
            'symbol' => 'MSFT',
            'price' => 300.00,
            'timestamp' => time(),
        ]);

        $allPrices = $this->stream->getAllPrices();

        $this->assertCount(2, $allPrices);
        $this->assertEquals(150.00, $allPrices['AAPL']);
        $this->assertEquals(300.00, $allPrices['MSFT']);
    }

    public function testPriceChangeDetection(): void
    {
        $changes = [];

        $this->dispatcher->on('price.change', function ($data) use (&$changes) {
            $changes[] = $data;
        });

        // First price (no change yet)
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 150.00,
            'timestamp' => time(),
        ]);

        // Second price (change detected)
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 151.50,
            'timestamp' => time(),
        ]);

        $this->assertCount(1, $changes);
        $this->assertEquals('AAPL', $changes[0]['symbol']);
        $this->assertEquals(151.50, $changes[0]['price']);
        $this->assertEqualsWithDelta(1.0, $changes[0]['change_percent'], 0.01);
    }

    public function testPriceSpikeDetection(): void
    {
        $spikes = [];

        $this->dispatcher->on('price.spike', function ($data) use (&$spikes) {
            $spikes[] = $data;
        });

        $this->stream->setMinChangePercent(0.01);

        // First price
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 100.00,
            'timestamp' => time(),
        ]);

        // Large price jump (>5%)
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 107.00,
            'timestamp' => time(),
        ]);

        $this->assertCount(1, $spikes);
        $this->assertEqualsWithDelta(7.0, $spikes[0]['spike_percent'], 0.01);
    }

    public function testHistoryTracking(): void
    {
        $this->stream->setHistoryTracking(true, 10);

        for ($i = 1; $i <= 15; $i++) {
            $this->websocket->simulateMessage([
                'symbol' => 'AAPL',
                'price' => 100.00 + $i,
                'volume' => 1000000 * $i,
                'timestamp' => time() + $i,
            ]);
        }

        $history = $this->stream->getHistory('AAPL');

        // Should only keep last 10
        $this->assertCount(10, $history);

        // Should have most recent prices
        $this->assertEquals(115.00, $history[9]['price']);
    }

    public function testGetHistoryWithLimit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->websocket->simulateMessage([
                'symbol' => 'AAPL',
                'price' => 100.00 + $i,
                'timestamp' => time(),
            ]);
        }

        $history = $this->stream->getHistory('AAPL', 5);
        $this->assertCount(5, $history);
    }

    public function testSubscribeAndUnsubscribe(): void
    {
        $result = $this->stream->subscribe(['AAPL', 'MSFT']);
        $this->assertTrue($result);

        $subscriptions = $this->websocket->getSubscriptions();
        $this->assertContains('AAPL', $subscriptions);
        $this->assertContains('MSFT', $subscriptions);

        $result = $this->stream->unsubscribe(['AAPL']);
        $this->assertTrue($result);

        $subscriptions = $this->websocket->getSubscriptions();
        $this->assertNotContains('AAPL', $subscriptions);
        $this->assertContains('MSFT', $subscriptions);
    }

    public function testGetStats(): void
    {
        // Simulate some activity
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 150.00,
            'timestamp' => time(),
        ]);

        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 151.00,
            'timestamp' => time(),
        ]);

        $stats = $this->stream->getStats();

        $this->assertArrayHasKey('running', $stats);
        $this->assertArrayHasKey('updates_received', $stats);
        $this->assertArrayHasKey('changes_detected', $stats);
        $this->assertArrayHasKey('symbols_tracked', $stats);
        $this->assertEquals(1, $stats['symbols_tracked']);
    }

    public function testSetMinChangePercent(): void
    {
        $changes = [];

        $this->dispatcher->on('price.change', function ($data) use (&$changes) {
            $changes[] = $data;
        });

        // Set minimum change to 5%
        $this->stream->setMinChangePercent(5.0);

        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 100.00,
            'timestamp' => time(),
        ]);

        // Small change (< 5%) - should not trigger
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 102.00,
            'timestamp' => time(),
        ]);

        $this->assertCount(0, $changes);

        // Large change (>= 5%) - should trigger
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 107.00,
            'timestamp' => time(),
        ]);

        $this->assertCount(1, $changes);
    }

    public function testPriceUpdateEvent(): void
    {
        $updates = [];

        $this->dispatcher->on('price.update', function ($data) use (&$updates) {
            $updates[] = $data;
        });

        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 150.00,
            'volume' => 1000000,
            'timestamp' => time(),
        ]);

        $this->assertCount(1, $updates);
        $this->assertEquals('AAPL', $updates[0]['symbol']);
        $this->assertEquals(150.00, $updates[0]['price']);
        $this->assertEquals(1000000, $updates[0]['volume']);
    }

    public function testOneTimeAlert(): void
    {
        $alertCount = 0;

        $this->dispatcher->on('price.alert', function () use (&$alertCount) {
            $alertCount++;
        });

        $this->stream->setAlert('AAPL', [
            'above' => 200.00,
            'once' => true,
        ]);

        // Trigger alert first time
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 201.00,
            'timestamp' => time(),
        ]);

        // Try to trigger again
        $this->websocket->simulateMessage([
            'symbol' => 'AAPL',
            'price' => 202.00,
            'timestamp' => time(),
        ]);

        $this->assertEquals(1, $alertCount, 'One-time alert should only trigger once');
    }
}

/**
 * Mock WebSocket for testing
 */
class MockWebSocket implements WebSocketInterface
{
    private bool $connected = false;
    private array $subscriptions = [];
    private array $messageCallbacks = [];
    private array $connectionCallbacks = [];
    private array $errorCallbacks = [];

    public function connect(): bool
    {
        $this->connected = true;
        return true;
    }

    public function disconnect(): void
    {
        $this->connected = false;
        $this->subscriptions = [];
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function subscribe(array $symbols): bool
    {
        $this->subscriptions = array_unique(array_merge($this->subscriptions, $symbols));
        return true;
    }

    public function unsubscribe(array $symbols): bool
    {
        $this->subscriptions = array_diff($this->subscriptions, $symbols);
        return true;
    }

    public function getSubscriptions(): array
    {
        return $this->subscriptions;
    }

    public function listen(?int $timeout = null): void
    {
        // Mock implementation - does nothing
    }

    public function onMessage(callable $callback): void
    {
        $this->messageCallbacks[] = $callback;
    }

    public function onConnection(callable $callback): void
    {
        $this->connectionCallbacks[] = $callback;
    }

    public function onError(callable $callback): void
    {
        $this->errorCallbacks[] = $callback;
    }

    public function send(array $message): bool
    {
        return true;
    }

    public function getStats(): array
    {
        return [
            'connected' => $this->connected,
            'subscriptions' => count($this->subscriptions),
        ];
    }

    public function setReconnectionStrategy(array $config): void
    {
        // Mock implementation
    }

    public function setHeartbeat(bool $enabled, int $interval = 30): void
    {
        // Mock implementation
    }

    // Test helper method
    public function simulateMessage(array $message): void
    {
        foreach ($this->messageCallbacks as $callback) {
            $callback($message);
        }
    }
}
