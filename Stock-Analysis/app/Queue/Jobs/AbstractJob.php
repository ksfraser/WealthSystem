<?php

declare(strict_types=1);

namespace App\Queue\Jobs;

use App\Queue\JobInterface;

/**
 * Abstract base class for queue jobs
 */
abstract class AbstractJob implements JobInterface
{
    protected string $id;
    protected array $payload;
    protected int $maxAttempts;
    
    public function __construct(array $payload = [], int $maxAttempts = 3)
    {
        $this->id = uniqid('job_', true);
        $this->payload = $payload;
        $this->maxAttempts = $maxAttempts;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }
}
