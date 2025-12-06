<?php

declare(strict_types=1);

namespace Tests\Events;

use PHPUnit\Framework\TestCase;
use App\Events\EventDispatcher;

class EventDispatcherTest extends TestCase
{
    private EventDispatcher $dispatcher;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->dispatcher = new EventDispatcher();
    }
    
    public function testDispatcherCreation(): void
    {
        $this->assertInstanceOf(EventDispatcher::class, $this->dispatcher);
    }
    
    public function testListenAndDispatch(): void
    {
        $called = false;
        
        $this->dispatcher->listen('test.event', function($payload) use (&$called) {
            $called = true;
        });
        
        $this->dispatcher->dispatch('test.event');
        
        $this->assertTrue($called);
    }
    
    public function testDispatchWithPayload(): void
    {
        $receivedPayload = null;
        
        $this->dispatcher->listen('data.event', function($payload) use (&$receivedPayload) {
            $receivedPayload = $payload;
        });
        
        $payload = ['key' => 'value', 'number' => 123];
        $this->dispatcher->dispatch('data.event', $payload);
        
        $this->assertSame($payload, $receivedPayload);
    }
    
    public function testMultipleListeners(): void
    {
        $count = 0;
        
        $this->dispatcher->listen('multi.event', function() use (&$count) {
            $count++;
        });
        $this->dispatcher->listen('multi.event', function() use (&$count) {
            $count++;
        });
        $this->dispatcher->listen('multi.event', function() use (&$count) {
            $count++;
        });
        
        $this->dispatcher->dispatch('multi.event');
        
        $this->assertSame(3, $count);
    }
    
    public function testHasListeners(): void
    {
        $this->assertFalse($this->dispatcher->hasListeners('unknown.event'));
        
        $this->dispatcher->listen('known.event', function() {});
        
        $this->assertTrue($this->dispatcher->hasListeners('known.event'));
    }
    
    public function testGetListenerCount(): void
    {
        $this->assertSame(0, $this->dispatcher->getListenerCount('test.event'));
        
        $this->dispatcher->listen('test.event', function() {});
        $this->assertSame(1, $this->dispatcher->getListenerCount('test.event'));
        
        $this->dispatcher->listen('test.event', function() {});
        $this->assertSame(2, $this->dispatcher->getListenerCount('test.event'));
    }
    
    public function testForgetEvent(): void
    {
        $this->dispatcher->listen('temp.event', function() {});
        $this->assertTrue($this->dispatcher->hasListeners('temp.event'));
        
        $this->dispatcher->forget('temp.event');
        $this->assertFalse($this->dispatcher->hasListeners('temp.event'));
    }
    
    public function testGetEvents(): void
    {
        $this->dispatcher->listen('event1', function() {});
        $this->dispatcher->listen('event2', function() {});
        $this->dispatcher->listen('event3', function() {});
        
        $events = $this->dispatcher->getEvents();
        
        $this->assertCount(3, $events);
        $this->assertContains('event1', $events);
        $this->assertContains('event2', $events);
        $this->assertContains('event3', $events);
    }
    
    public function testDispatchNonExistentEvent(): void
    {
        // Should not throw exception
        $this->dispatcher->dispatch('nonexistent.event', ['data' => 'test']);
        
        $this->assertTrue(true);
    }
    
    public function testHistoryRecording(): void
    {
        $dispatcher = new EventDispatcher(true);
        
        $dispatcher->dispatch('event1', ['data' => 1]);
        $dispatcher->dispatch('event2', ['data' => 2]);
        
        $history = $dispatcher->getHistory();
        
        $this->assertCount(2, $history);
        $this->assertSame('event1', $history[0]['event']);
        $this->assertSame('event2', $history[1]['event']);
    }
    
    public function testClearHistory(): void
    {
        $dispatcher = new EventDispatcher(true);
        
        $dispatcher->dispatch('test', null);
        $this->assertNotEmpty($dispatcher->getHistory());
        
        $dispatcher->clearHistory();
        $this->assertEmpty($dispatcher->getHistory());
    }
    
    public function testClearAll(): void
    {
        $dispatcher = new EventDispatcher(true);
        
        $dispatcher->listen('event1', function() {});
        $dispatcher->listen('event2', function() {});
        $dispatcher->dispatch('event1');
        
        $this->assertNotEmpty($dispatcher->getEvents());
        $this->assertNotEmpty($dispatcher->getHistory());
        
        $dispatcher->clearAll();
        
        $this->assertEmpty($dispatcher->getEvents());
        $this->assertEmpty($dispatcher->getHistory());
    }
}
