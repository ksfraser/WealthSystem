<?php

declare(strict_types=1);

namespace Tests\Queue;

use PHPUnit\Framework\TestCase;
use App\Queue\QueueManager;
use App\Queue\QueueWorker;
use App\Queue\JobInterface;

class QueueWorkerTest extends TestCase
{
    private QueueManager $queue;
    private QueueWorker $worker;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = new QueueManager();
        $this->worker = new QueueWorker($this->queue);
    }
    
    public function testProcessJob(): void
    {
        $executed = false;
        
        $job = $this->createMockJob(function() use (&$executed) {
            $executed = true;
        });
        
        $this->queue->push('test', $job);
        
        $result = $this->worker->processJob('test');
        
        $this->assertTrue($result);
        $this->assertTrue($executed);
        $this->assertSame(1, $this->queue->getProcessedCount('test'));
    }
    
    public function testProcessJobEmptyQueue(): void
    {
        $result = $this->worker->processJob('empty');
        
        $this->assertFalse($result);
    }
    
    public function testProcessJobWithFailure(): void
    {
        $job = $this->createMockJob(function() {
            throw new \Exception('Job failed');
        }, 2);
        
        $this->queue->push('test', $job);
        
        // First attempt - should retry
        $this->worker->processJob('test');
        $this->assertSame(1, $this->queue->size('test')); // Back in queue
        $this->assertSame(0, $this->queue->getFailedCount('test'));
        
        // Second attempt - should fail permanently
        $this->worker->processJob('test');
        $this->assertSame(0, $this->queue->size('test')); // Not back in queue
        $this->assertSame(1, $this->queue->getFailedCount('test'));
    }
    
    public function testProcessQueue(): void
    {
        $count = 0;
        
        for ($i = 0; $i < 5; $i++) {
            $job = $this->createMockJob(function() use (&$count) {
                $count++;
            });
            $this->queue->push('test', $job);
        }
        
        $processed = $this->worker->processQueue('test');
        
        $this->assertSame(5, $processed);
        $this->assertSame(5, $count);
        $this->assertTrue($this->queue->isEmpty('test'));
    }
    
    public function testProcessQueueWithLimit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $job = $this->createMockJob(function() {});
            $this->queue->push('test', $job);
        }
        
        $processed = $this->worker->processQueue('test', 3);
        
        $this->assertSame(3, $processed);
        $this->assertSame(7, $this->queue->size('test'));
    }
    
    public function testStopWorker(): void
    {
        $this->assertFalse($this->worker->shouldStop());
        
        $this->worker->stop();
        
        $this->assertTrue($this->worker->shouldStop());
    }
    
    public function testProcessQueueStopsWhenSignaled(): void
    {
        $count = 0;
        
        for ($i = 0; $i < 10; $i++) {
            $job = $this->createMockJob(function() use (&$count) {
                $count++;
            });
            $this->queue->push('test', $job);
        }
        
        // Create a worker that processes one job then stops
        $worker = new QueueWorker($this->queue);
        
        // Process first job
        $worker->processJob('test');
        $this->assertSame(1, $count);
        
        // Now stop the worker
        $worker->stop();
        
        // Try to process remaining jobs - should stop immediately
        $processed = $worker->processQueue('test');
        
        $this->assertSame(0, $processed); // Should not process any more
        $this->assertSame(1, $count); // Still only 1 processed
    }
    
    private function createMockJob(callable $handler, int $maxAttempts = 3): JobInterface
    {
        return new class($handler, $maxAttempts) implements JobInterface {
            private string $id;
            private $handler;
            private int $maxAttempts;
            
            public function __construct(callable $handler, int $maxAttempts)
            {
                $this->id = uniqid('job_', true);
                $this->handler = $handler;
                $this->maxAttempts = $maxAttempts;
            }
            
            public function handle(): void
            {
                ($this->handler)();
            }
            
            public function getId(): string
            {
                return $this->id;
            }
            
            public function getType(): string
            {
                return 'test';
            }
            
            public function getPayload(): array
            {
                return [];
            }
            
            public function getMaxAttempts(): int
            {
                return $this->maxAttempts;
            }
        };
    }
}
