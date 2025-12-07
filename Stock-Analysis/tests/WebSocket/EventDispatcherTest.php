<?php

namespace Tests\WebSocket;

use PHPUnit\Framework\TestCase;
use App\Services\EventDispatcher;

/**
 * EventDispatcher Tests
 * 
 * Tests the event dispatching system for real-time notifications.
 */
class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->dispatcher = new EventDispatcher();
    }

    public function testOnRegistersListener(): void
    {
        $called = false;

        $listenerId = $this->dispatcher->on('test.event', function () use (&$called) {
            $called = true;
        });

        $this->assertIsString($listenerId);
        $this->assertTrue($this->dispatcher->hasListeners('test.event'));
    }

    public function testDispatchCallsListeners(): void
    {
        $callCount = 0;
        $receivedData = null;

        $this->dispatcher->on('test.event', function ($data) use (&$callCount, &$receivedData) {
            $callCount++;
            $receivedData = $data;
        });

        $testData = ['value' => 123];
        $called = $this->dispatcher->dispatch('test.event', $testData);

        $this->assertEquals(1, $called);
        $this->assertEquals(1, $callCount);
        $this->assertEquals($testData, $receivedData);
    }

    public function testMultipleListenersForSameEvent(): void
    {
        $calls = [];

        $this->dispatcher->on('test.event', function () use (&$calls) {
            $calls[] = 'listener1';
        });

        $this->dispatcher->on('test.event', function () use (&$calls) {
            $calls[] = 'listener2';
        });

        $this->dispatcher->on('test.event', function () use (&$calls) {
            $calls[] = 'listener3';
        });

        $this->dispatcher->dispatch('test.event');

        $this->assertCount(3, $calls);
        $this->assertContains('listener1', $calls);
        $this->assertContains('listener2', $calls);
        $this->assertContains('listener3', $calls);
    }

    public function testPriorityExecution(): void
    {
        $order = [];

        $this->dispatcher->on('test.event', function () use (&$order) {
            $order[] = 'low';
        }, ['priority' => 1]);

        $this->dispatcher->on('test.event', function () use (&$order) {
            $order[] = 'high';
        }, ['priority' => 10]);

        $this->dispatcher->on('test.event', function () use (&$order) {
            $order[] = 'medium';
        }, ['priority' => 5]);

        $this->dispatcher->dispatch('test.event');

        $this->assertEquals(['high', 'medium', 'low'], $order);
    }

    public function testOnceListener(): void
    {
        $callCount = 0;

        $this->dispatcher->once('test.event', function () use (&$callCount) {
            $callCount++;
        });

        $this->dispatcher->dispatch('test.event');
        $this->dispatcher->dispatch('test.event');
        $this->dispatcher->dispatch('test.event');

        $this->assertEquals(1, $callCount, 'Once listener should only be called once');
    }

    public function testOffRemovesListener(): void
    {
        $called = false;

        $listenerId = $this->dispatcher->on('test.event', function () use (&$called) {
            $called = true;
        });

        $removed = $this->dispatcher->off($listenerId);
        $this->assertTrue($removed);

        $this->dispatcher->dispatch('test.event');
        $this->assertFalse($called, 'Removed listener should not be called');
    }

    public function testRemoveAllListeners(): void
    {
        $this->dispatcher->on('test.event', fn() => null);
        $this->dispatcher->on('test.event', fn() => null);
        $this->dispatcher->on('test.event', fn() => null);

        $removed = $this->dispatcher->removeAllListeners('test.event');

        $this->assertEquals(3, $removed);
        $this->assertFalse($this->dispatcher->hasListeners('test.event'));
    }

    public function testWildcardListener(): void
    {
        $events = [];

        $this->dispatcher->on('*', function ($data) use (&$events) {
            $events[] = $data['event'];
        });

        $this->dispatcher->dispatch('event1');
        $this->dispatcher->dispatch('event2');
        $this->dispatcher->dispatch('event3');

        $this->assertEquals(['event1', 'event2', 'event3'], $events);
    }

    public function testGetListenerCount(): void
    {
        $this->assertEquals(0, $this->dispatcher->getListenerCount('test.event'));

        $this->dispatcher->on('test.event', fn() => null);
        $this->dispatcher->on('test.event', fn() => null);

        $this->assertEquals(2, $this->dispatcher->getListenerCount('test.event'));
    }

    public function testGetEvents(): void
    {
        $this->dispatcher->on('event1', fn() => null);
        $this->dispatcher->on('event2', fn() => null);
        $this->dispatcher->on('event3', fn() => null);

        $events = $this->dispatcher->getEvents();

        $this->assertCount(3, $events);
        $this->assertContains('event1', $events);
        $this->assertContains('event2', $events);
        $this->assertContains('event3', $events);
    }

    public function testGetStats(): void
    {
        $this->dispatcher->on('event1', fn() => null);
        $this->dispatcher->on('event2', fn() => null);

        $this->dispatcher->dispatch('event1');
        $this->dispatcher->dispatch('event1');
        $this->dispatcher->dispatch('event2');

        $stats = $this->dispatcher->getStats();

        $this->assertEquals(3, $stats['total_dispatches']);
        $this->assertEquals(2, $stats['total_listeners']);
        $this->assertEquals(2, $stats['events_registered']);
        $this->assertEquals(2, $stats['event_counts']['event1']);
        $this->assertEquals(1, $stats['event_counts']['event2']);
    }

    public function testClear(): void
    {
        $this->dispatcher->on('event1', fn() => null);
        $this->dispatcher->on('event2', fn() => null);
        $this->dispatcher->dispatch('event1');

        $this->dispatcher->clear();

        $this->assertEquals([], $this->dispatcher->getEvents());
        $stats = $this->dispatcher->getStats();
        $this->assertEquals(0, $stats['total_dispatches']);
        $this->assertEquals(0, $stats['total_listeners']);
    }

    public function testListenerExceptionDoesNotStopOthers(): void
    {
        $calls = [];

        $this->dispatcher->on('test.event', function () use (&$calls) {
            $calls[] = 'first';
        });

        $this->dispatcher->on('test.event', function () {
            throw new \RuntimeException('Test error');
        });

        $this->dispatcher->on('test.event', function () use (&$calls) {
            $calls[] = 'third';
        });

        $this->dispatcher->dispatch('test.event');

        $this->assertCount(2, $calls);
        $this->assertContains('first', $calls);
        $this->assertContains('third', $calls);
    }

    public function testEventChain(): void
    {
        $result = [];

        $chain = $this->dispatcher->chain('test.event')
            ->then(function ($data) use (&$result) {
                $result[] = 'step1';
                $data['step1'] = true;
                return $data;
            })
            ->then(function ($data) use (&$result) {
                $result[] = 'step2';
                $data['step2'] = true;
                return $data;
            })
            ->then(function ($data) use (&$result) {
                $result[] = 'step3';
                return $data;
            });

        $listenerId = $chain->execute();
        $this->assertIsString($listenerId);

        $this->dispatcher->dispatch('test.event', ['initial' => true]);

        $this->assertEquals(['step1', 'step2', 'step3'], $result);
    }

    public function testEventChainWithError(): void
    {
        $errorCaught = false;

        $chain = $this->dispatcher->chain('test.event')
            ->then(function ($data) {
                throw new \RuntimeException('Chain error');
            })
            ->catch(function ($error) use (&$errorCaught) {
                $errorCaught = true;
            });

        $chain->execute();
        $this->dispatcher->dispatch('test.event');

        $this->assertTrue($errorCaught);
    }

    public function testEventChainStopsOnFalse(): void
    {
        $steps = [];

        $chain = $this->dispatcher->chain('test.event')
            ->then(function ($data) use (&$steps) {
                $steps[] = 'step1';
                return $data;
            })
            ->then(function ($data) use (&$steps) {
                $steps[] = 'step2';
                return false; // Stop chain
            })
            ->then(function ($data) use (&$steps) {
                $steps[] = 'step3'; // Should not execute
                return $data;
            });

        $chain->execute();
        $this->dispatcher->dispatch('test.event');

        $this->assertEquals(['step1', 'step2'], $steps);
    }
}
