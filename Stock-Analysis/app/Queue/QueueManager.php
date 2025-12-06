<?php

declare(strict_types=1);

namespace App\Queue;

/**
 * Simple in-memory queue implementation
 */
class QueueManager
{
    /** @var array<string, array<JobInterface>> */
    private array $queues = [];
    
    /** @var array<string, int> */
    private array $processed = [];
    
    /** @var array<string, int> */
    private array $failed = [];
    
    /**
     * Push a job onto a queue
     *
     * @param string $queue Queue name
     * @param JobInterface $job Job to enqueue
     * @return void
     */
    public function push(string $queue, JobInterface $job): void
    {
        if (!isset($this->queues[$queue])) {
            $this->queues[$queue] = [];
        }
        
        $this->queues[$queue][] = $job;
    }
    
    /**
     * Pop the next job from a queue
     *
     * @param string $queue Queue name
     * @return JobInterface|null
     */
    public function pop(string $queue): ?JobInterface
    {
        if (empty($this->queues[$queue])) {
            return null;
        }
        
        return array_shift($this->queues[$queue]);
    }
    
    /**
     * Get the size of a queue
     *
     * @param string $queue Queue name
     * @return int
     */
    public function size(string $queue): int
    {
        return count($this->queues[$queue] ?? []);
    }
    
    /**
     * Check if a queue is empty
     *
     * @param string $queue Queue name
     * @return bool
     */
    public function isEmpty(string $queue): bool
    {
        return $this->size($queue) === 0;
    }
    
    /**
     * Get all queue names
     *
     * @return array<string>
     */
    public function getQueues(): array
    {
        return array_keys($this->queues);
    }
    
    /**
     * Clear a specific queue
     *
     * @param string $queue Queue name
     * @return void
     */
    public function clear(string $queue): void
    {
        $this->queues[$queue] = [];
    }
    
    /**
     * Clear all queues
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->queues = [];
        $this->processed = [];
        $this->failed = [];
    }
    
    /**
     * Mark a job as processed
     *
     * @param string $queue Queue name
     * @return void
     */
    public function markProcessed(string $queue): void
    {
        if (!isset($this->processed[$queue])) {
            $this->processed[$queue] = 0;
        }
        $this->processed[$queue]++;
    }
    
    /**
     * Mark a job as failed
     *
     * @param string $queue Queue name
     * @return void
     */
    public function markFailed(string $queue): void
    {
        if (!isset($this->failed[$queue])) {
            $this->failed[$queue] = 0;
        }
        $this->failed[$queue]++;
    }
    
    /**
     * Get processed count for a queue
     *
     * @param string $queue Queue name
     * @return int
     */
    public function getProcessedCount(string $queue): int
    {
        return $this->processed[$queue] ?? 0;
    }
    
    /**
     * Get failed count for a queue
     *
     * @param string $queue Queue name
     * @return int
     */
    public function getFailedCount(string $queue): int
    {
        return $this->failed[$queue] ?? 0;
    }
    
    /**
     * Get statistics for all queues
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $stats = [];
        
        foreach ($this->getQueues() as $queue) {
            $stats[$queue] = [
                'pending' => $this->size($queue),
                'processed' => $this->getProcessedCount($queue),
                'failed' => $this->getFailedCount($queue),
            ];
        }
        
        return $stats;
    }
}
