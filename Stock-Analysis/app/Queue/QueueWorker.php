<?php

declare(strict_types=1);

namespace App\Queue;

use Exception;

/**
 * Queue worker that processes jobs from queues
 */
class QueueWorker
{
    private QueueManager $queue;
    
    /** @var array<string, int> */
    private array $attempts = [];
    
    private bool $shouldStop = false;
    
    public function __construct(QueueManager $queue)
    {
        $this->queue = $queue;
    }
    
    /**
     * Process a single job from the queue
     *
     * @param string $queueName Queue name to process from
     * @return bool True if a job was processed, false if queue is empty
     */
    public function processJob(string $queueName): bool
    {
        $job = $this->queue->pop($queueName);
        
        if ($job === null) {
            return false;
        }
        
        $jobId = $job->getId();
        
        try {
            $job->handle();
            $this->queue->markProcessed($queueName);
            unset($this->attempts[$jobId]);
            return true;
        } catch (Exception $e) {
            return $this->handleFailedJob($job, $queueName, $e);
        }
    }
    
    /**
     * Process all jobs in a queue
     *
     * @param string $queueName Queue name to process
     * @param int|null $limit Maximum number of jobs to process (null for unlimited)
     * @return int Number of jobs processed
     */
    public function processQueue(string $queueName, ?int $limit = null): int
    {
        $processed = 0;
        
        while (!$this->shouldStop) {
            if ($this->queue->isEmpty($queueName)) {
                break;
            }
            
            if ($limit !== null && $processed >= $limit) {
                break;
            }
            
            $this->processJob($queueName);
            $processed++;
        }
        
        return $processed;
    }
    
    /**
     * Stop the worker gracefully
     *
     * @return void
     */
    public function stop(): void
    {
        $this->shouldStop = true;
    }
    
    /**
     * Check if worker should stop
     *
     * @return bool
     */
    public function shouldStop(): bool
    {
        return $this->shouldStop;
    }
    
    /**
     * Get current attempt count for a job
     *
     * @param string $jobId Job identifier
     * @return int
     */
    public function getAttempts(string $jobId): int
    {
        return $this->attempts[$jobId] ?? 0;
    }
    
    /**
     * Handle a failed job
     *
     * @param JobInterface $job The failed job
     * @param string $queueName Queue name
     * @param Exception $exception The exception that caused failure
     * @return bool
     */
    private function handleFailedJob(JobInterface $job, string $queueName, Exception $exception): bool
    {
        $jobId = $job->getId();
        
        if (!isset($this->attempts[$jobId])) {
            $this->attempts[$jobId] = 0;
        }
        $this->attempts[$jobId]++;
        
        if ($this->attempts[$jobId] < $job->getMaxAttempts()) {
            // Retry: push back to queue
            $this->queue->push($queueName, $job);
            return false;
        }
        
        // Max attempts reached, mark as failed
        $this->queue->markFailed($queueName);
        unset($this->attempts[$jobId]);
        return false;
    }
}
