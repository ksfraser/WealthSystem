<?php

declare(strict_types=1);

namespace App\Queue;

/**
 * Job interface that all queue jobs must implement
 */
interface JobInterface
{
    /**
     * Execute the job
     *
     * @return void
     */
    public function handle(): void;
    
    /**
     * Get the job identifier
     *
     * @return string
     */
    public function getId(): string;
    
    /**
     * Get the job type/name
     *
     * @return string
     */
    public function getType(): string;
    
    /**
     * Get job payload data
     *
     * @return array
     */
    public function getPayload(): array;
    
    /**
     * Get maximum number of retry attempts
     *
     * @return int
     */
    public function getMaxAttempts(): int;
}
