<?php

declare(strict_types=1);

namespace App\Queue\Jobs;

/**
 * Job to fetch and update cryptocurrency price data
 */
class FetchPriceJob extends AbstractJob
{
    public function getType(): string
    {
        return 'fetch_price';
    }
    
    public function handle(): void
    {
        $symbol = $this->payload['symbol'] ?? null;
        
        if ($symbol === null) {
            throw new \InvalidArgumentException('Symbol is required in payload');
        }
        
        // Simulated price fetch - in real implementation would call API
        // For now, just mark as handled
    }
    
    public function getSymbol(): string
    {
        return $this->payload['symbol'] ?? '';
    }
}
