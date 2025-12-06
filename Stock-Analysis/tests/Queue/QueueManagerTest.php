<?php

declare(strict_types=1);

namespace Tests\Queue;

use PHPUnit\Framework\TestCase;
use App\Queue\QueueManager;
use App\Queue\Jobs\FetchPriceJob;
use App\Queue\Jobs\SendNotificationJob;

class QueueManagerTest extends TestCase
{
    private QueueManager $queue;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new QueueManager();
    }
    
    public function testPushAndPop(): void
    {
        $job = new FetchPriceJob(['symbol' => 'BTC']);
        
        $this->queue->push('default', $job);
        
        $poppedJob = $this->queue->pop('default');
        
        $this->assertSame($job, $poppedJob);
    }
    
    public function testPopEmptyQueue(): void
    {
        $result = $this->queue->pop('empty');
        
        $this->assertNull($result);
    }
    
    public function testQueueSize(): void
    {
        $this->assertSame(0, $this->queue->size('test'));
        
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'ETH']));
        $this->assertSame(1, $this->queue->size('test'));
        
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'BTC']));
        $this->assertSame(2, $this->queue->size('test'));
    }
    
    public function testIsEmpty(): void
    {
        $this->assertTrue($this->queue->isEmpty('test'));
        
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'DOGE']));
        
        $this->assertFalse($this->queue->isEmpty('test'));
    }
    
    public function testGetQueues(): void
    {
        $this->queue->push('high', new FetchPriceJob(['symbol' => 'BTC']));
        $this->queue->push('low', new SendNotificationJob(['message' => 'test', 'recipient' => 'user']));
        $this->queue->push('normal', new FetchPriceJob(['symbol' => 'ETH']));
        
        $queues = $this->queue->getQueues();
        
        $this->assertCount(3, $queues);
        $this->assertContains('high', $queues);
        $this->assertContains('low', $queues);
        $this->assertContains('normal', $queues);
    }
    
    public function testClearQueue(): void
    {
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'BTC']));
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'ETH']));
        
        $this->assertSame(2, $this->queue->size('test'));
        
        $this->queue->clear('test');
        
        $this->assertSame(0, $this->queue->size('test'));
    }
    
    public function testClearAll(): void
    {
        $this->queue->push('queue1', new FetchPriceJob(['symbol' => 'BTC']));
        $this->queue->push('queue2', new FetchPriceJob(['symbol' => 'ETH']));
        
        $this->queue->clearAll();
        
        $this->assertTrue($this->queue->isEmpty('queue1'));
        $this->assertTrue($this->queue->isEmpty('queue2'));
    }
    
    public function testMarkProcessed(): void
    {
        $this->assertSame(0, $this->queue->getProcessedCount('test'));
        
        $this->queue->markProcessed('test');
        $this->assertSame(1, $this->queue->getProcessedCount('test'));
        
        $this->queue->markProcessed('test');
        $this->assertSame(2, $this->queue->getProcessedCount('test'));
    }
    
    public function testMarkFailed(): void
    {
        $this->assertSame(0, $this->queue->getFailedCount('test'));
        
        $this->queue->markFailed('test');
        $this->assertSame(1, $this->queue->getFailedCount('test'));
    }
    
    public function testGetStatistics(): void
    {
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'BTC']));
        $this->queue->push('test', new FetchPriceJob(['symbol' => 'ETH']));
        $this->queue->markProcessed('test');
        $this->queue->markFailed('test');
        
        $stats = $this->queue->getStatistics();
        
        $this->assertArrayHasKey('test', $stats);
        $this->assertSame(2, $stats['test']['pending']);
        $this->assertSame(1, $stats['test']['processed']);
        $this->assertSame(1, $stats['test']['failed']);
    }
}
