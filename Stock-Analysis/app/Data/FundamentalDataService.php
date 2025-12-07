<?php

declare(strict_types=1);

namespace App\Data;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Fundamental Data Service
 * 
 * Aggregates fundamental data from multiple providers with:
 * - Multi-provider fallback
 * - In-memory caching
 * - Batch fetching
 * - Rate limit management
 */
class FundamentalDataService
{
    private LoggerInterface $logger;
    
    /** @var array<string, FundamentalDataProviderInterface> */
    private array $providers = [];
    
    /** @var array<string, FundamentalData> */
    private array $cache = [];
    
    private int $defaultCacheTTL = 3600; // 1 hour

    /**
     * @param array<FundamentalDataProviderInterface> $providers List of providers (priority order)
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        array $providers = [],
        ?LoggerInterface $logger = null
    ) {
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
        
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * Add a data provider
     */
    public function addProvider(FundamentalDataProviderInterface $provider): void
    {
        $this->providers[$provider->getProviderName()] = $provider;
        $this->logger->info("Added fundamental data provider: " . $provider->getProviderName());
    }

    /**
     * Get fundamentals for a ticker
     * 
     * Tries providers in order until successful, with caching
     */
    public function getFundamentals(string $ticker, bool $useCache = true): FundamentalData
    {
        $ticker = strtoupper($ticker);
        
        // Check cache first
        if ($useCache && $this->isCached($ticker)) {
            $cached = $this->cache[$ticker];
            if (!$cached->isStale($this->defaultCacheTTL)) {
                $this->logger->debug("Using cached fundamentals for {$ticker}", [
                    'age_seconds' => $cached->getAge(),
                ]);
                return $cached;
            }
        }

        // Try each provider until success
        foreach ($this->providers as $providerName => $provider) {
            if (!$provider->isAvailable()) {
                $this->logger->debug("Provider {$providerName} not available, skipping");
                continue;
            }

            try {
                $this->logger->info("Fetching fundamentals for {$ticker} from {$providerName}");
                $data = $provider->getFundamentals($ticker);
                
                if ($data->isValid()) {
                    $this->cache[$ticker] = $data;
                    $this->logger->info("Successfully fetched fundamentals for {$ticker} from {$providerName}");
                    return $data;
                }
                
                $this->logger->warning("Provider {$providerName} returned invalid data for {$ticker}");
            } catch (\Exception $e) {
                $this->logger->error("Provider {$providerName} failed for {$ticker}: " . $e->getMessage());
                continue;
            }
        }

        // All providers failed
        $this->logger->error("All providers failed for {$ticker}");
        
        $failedData = new FundamentalData(
            ticker: $ticker,
            provider: 'none',
            error: 'All providers failed or unavailable'
        );
        
        // Cache the failure to avoid repeated attempts
        $this->cache[$ticker] = $failedData;
        
        return $failedData;
    }

    /**
     * Get fundamentals for multiple tickers
     * 
     * @param array<string> $tickers
     * @param bool $useCache
     * @return array<string, FundamentalData>
     */
    public function getBatchFundamentals(array $tickers, bool $useCache = true): array
    {
        $results = [];
        
        foreach ($tickers as $ticker) {
            $results[$ticker] = $this->getFundamentals($ticker, $useCache);
        }
        
        return $results;
    }

    /**
     * Check if ticker data is cached
     */
    public function isCached(string $ticker): bool
    {
        return isset($this->cache[strtoupper($ticker)]);
    }

    /**
     * Clear cache for specific ticker or all
     */
    public function clearCache(?string $ticker = null): void
    {
        if ($ticker !== null) {
            unset($this->cache[strtoupper($ticker)]);
            $this->logger->debug("Cleared cache for {$ticker}");
        } else {
            $this->cache = [];
            $this->logger->debug("Cleared all fundamental data cache");
        }
    }

    /**
     * Set cache TTL
     */
    public function setCacheTTL(int $seconds): void
    {
        $this->defaultCacheTTL = $seconds;
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        $valid = 0;
        $stale = 0;
        $invalid = 0;
        
        foreach ($this->cache as $data) {
            if (!$data->isValid()) {
                $invalid++;
            } elseif ($data->isStale($this->defaultCacheTTL)) {
                $stale++;
            } else {
                $valid++;
            }
        }
        
        return [
            'total_cached' => count($this->cache),
            'valid' => $valid,
            'stale' => $stale,
            'invalid' => $invalid,
            'ttl_seconds' => $this->defaultCacheTTL,
        ];
    }

    /**
     * Get list of available providers
     * 
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        $available = [];
        
        foreach ($this->providers as $name => $provider) {
            if ($provider->isAvailable()) {
                $available[] = $name;
            }
        }
        
        return $available;
    }

    /**
     * Check if service has any available providers
     */
    public function hasAvailableProvider(): bool
    {
        return !empty($this->getAvailableProviders());
    }

    /**
     * Get provider rate limits
     * 
     * @return array<string, array{calls_per_day: int, calls_per_minute: int}>
     */
    public function getProviderRateLimits(): array
    {
        $limits = [];
        
        foreach ($this->providers as $name => $provider) {
            $limits[$name] = $provider->getRateLimits();
        }
        
        return $limits;
    }
}
