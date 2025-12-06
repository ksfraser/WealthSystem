<?php

declare(strict_types=1);

namespace App\Data;

/**
 * Data fetch result with source tracking
 * 
 * Contains the fetched data and metadata about the fetch operation
 */
class DataFetchResult
{
    /**
     * @param array<string, mixed> $data Price data with OHLCV columns
     * @param DataSource $source Which provider returned the data
     * @param string|null $error Error message if fetch failed
     * @param float $fetchTime Time taken to fetch data in seconds
     */
    public function __construct(
        public readonly array $data,
        public readonly DataSource $source,
        public readonly ?string $error = null,
        public readonly float $fetchTime = 0.0
    ) {
    }

    /**
     * Check if fetch was successful
     */
    public function isSuccess(): bool
    {
        return $this->source !== DataSource::EMPTY && $this->error === null && !empty($this->data);
    }

    /**
     * Check if data is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * Get data as array
     * 
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'source' => $this->source->value,
            'error' => $this->error,
            'fetch_time' => $this->fetchTime,
            'success' => $this->isSuccess(),
        ];
    }
}
