<?php

namespace WealthSystem\StockAnalysis\Data;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * News Sentiment Service
 * 
 * Multi-provider aggregation service with caching and fallback support.
 * Similar architecture to FundamentalDataService.
 * 
 * Features:
 * - Multiple provider support with fallback
 * - In-memory caching to respect rate limits
 * - Configurable cache TTL
 * - Batch sentiment fetching
 * - Provider availability checking
 * 
 * Example usage:
 * ```php
 * $service = new NewsSentimentService([
 *     new AlphaVantageNewsProvider($apiKey),
 *     // Add more providers for redundancy
 * ]);
 * 
 * // Single ticker
 * $sentiment = $service->getSentiment('AAPL');
 * 
 * // Batch fetch with caching
 * $sentiments = $service->getBatchSentiment(['AAPL', 'MSFT', 'GOOGL']);
 * ```
 */
class NewsSentimentService
{
    /** @var NewsSentimentProviderInterface[] */
    private array $providers = [];

    /** @var array<string, NewsSentiment> */
    private array $cache = [];

    private int $defaultCacheTTL = 1800; // 30 minutes (news changes faster than fundamentals)

    private readonly LoggerInterface $logger;

    /**
     * @param NewsSentimentProviderInterface[] $providers Priority-ordered list of providers
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(
        array $providers = [],
        ?LoggerInterface $logger = null
    ) {
        $this->logger = $logger ?? new NullLogger();
        foreach ($providers as $provider) {
            $this->addProvider($provider);
        }
    }

    /**
     * Add a provider to the service
     * 
     * Providers are used in the order they're added (first = highest priority)
     */
    public function addProvider(NewsSentimentProviderInterface $provider): void
    {
        $this->providers[] = $provider;
        $this->logger->info("Added provider: " . $provider->getProviderName());
    }

    /**
     * Get news sentiment for a ticker
     * 
     * Tries each provider in order until one succeeds. Uses cache if available.
     * 
     * @param string $ticker Stock ticker symbol
     * @param array $options Provider-specific options (time_from, time_to, limit, etc.)
     * @param bool $useCache Whether to use cached data (default: true)
     * 
     * @return NewsSentiment
     */
    public function getSentiment(string $ticker, array $options = [], bool $useCache = true): NewsSentiment
    {
        $cacheKey = $this->getCacheKey($ticker, $options);

        // Check cache first
        if ($useCache && $this->isCached($cacheKey)) {
            $cached = $this->cache[$cacheKey];
            if (!$cached->isStale($this->defaultCacheTTL)) {
                $this->logger->debug("Cache hit for {$ticker}");
                return $cached;
            }
            $this->logger->debug("Cache stale for {$ticker}, fetching fresh data");
        }

        // Try each provider
        $errors = [];
        foreach ($this->providers as $provider) {
            if (!$provider->isAvailable()) {
                $this->logger->debug("Skipping unavailable provider: " . $provider->getProviderName());
                continue;
            }

            try {
                $this->logger->info("Trying provider: " . $provider->getProviderName() . " for {$ticker}");
                $sentiment = $provider->getSentiment($ticker, $options);

                if ($sentiment->isValid()) {
                    $this->cache[$cacheKey] = $sentiment;
                    $this->logger->info("Successfully fetched sentiment for {$ticker} from " . $provider->getProviderName());
                    return $sentiment;
                }

                // Provider returned error
                $errors[$provider->getProviderName()] = $sentiment->error;
                $this->logger->warning("Provider {$provider->getProviderName()} returned error for {$ticker}: {$sentiment->error}");

            } catch (\Exception $e) {
                $errors[$provider->getProviderName()] = $e->getMessage();
                $this->logger->error("Provider {$provider->getProviderName()} threw exception for {$ticker}", [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        // All providers failed
        $errorMsg = empty($errors)
            ? 'No providers available'
            : 'All providers failed: ' . json_encode($errors);

        $this->logger->error("Failed to fetch sentiment for {$ticker}", ['errors' => $errors]);

        return new NewsSentiment(
            ticker: $ticker,
            provider: 'none',
            error: $errorMsg
        );
    }

    /**
     * Get news sentiment for multiple tickers
     * 
     * Fetches sentiment for each ticker, using cache when available.
     * 
     * @param array<string> $tickers Array of ticker symbols
     * @param array $options Provider-specific options
     * @param bool $useCache Whether to use cached data (default: true)
     * 
     * @return array<string, NewsSentiment> Ticker => NewsSentiment mapping
     */
    public function getBatchSentiment(array $tickers, array $options = [], bool $useCache = true): array
    {
        $this->logger->info("Batch fetching sentiment for " . count($tickers) . " tickers");

        $results = [];
        foreach ($tickers as $ticker) {
            $results[$ticker] = $this->getSentiment($ticker, $options, $useCache);
        }

        return $results;
    }

    /**
     * Check if sentiment is cached for a ticker
     */
    public function isCached(string $cacheKey): bool
    {
        return isset($this->cache[$cacheKey]);
    }

    /**
     * Clear cache for a specific ticker or all tickers
     * 
     * @param string|null $cacheKey Specific cache key to clear, or null for all
     */
    public function clearCache(?string $cacheKey = null): void
    {
        if ($cacheKey === null) {
            $this->cache = [];
            $this->logger->info("Cleared all sentiment cache");
        } else {
            unset($this->cache[$cacheKey]);
            $this->logger->info("Cleared cache for: {$cacheKey}");
        }
    }

    /**
     * Set cache TTL (time to live) in seconds
     * 
     * Default: 1800 seconds (30 minutes)
     * 
     * @param int $seconds Cache TTL in seconds
     */
    public function setCacheTTL(int $seconds): void
    {
        $this->defaultCacheTTL = $seconds;
        $this->logger->info("Set cache TTL to {$seconds} seconds");
    }

    /**
     * Get cache statistics
     * 
     * @return array{total: int, valid: int, stale: int, invalid: int}
     */
    public function getCacheStats(): array
    {
        $total = count($this->cache);
        $valid = 0;
        $stale = 0;
        $invalid = 0;

        foreach ($this->cache as $sentiment) {
            if (!$sentiment->isValid()) {
                $invalid++;
            } elseif ($sentiment->isStale($this->defaultCacheTTL)) {
                $stale++;
            } else {
                $valid++;
            }
        }

        return [
            'total' => $total,
            'valid' => $valid,
            'stale' => $stale,
            'invalid' => $invalid,
        ];
    }

    /**
     * Get list of available provider names
     * 
     * @return array<string>
     */
    public function getAvailableProviders(): array
    {
        $available = [];
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                $available[] = $provider->getProviderName();
            }
        }
        return $available;
    }

    /**
     * Check if at least one provider is available
     */
    public function hasAvailableProvider(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->isAvailable()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get rate limit information for all providers
     * 
     * @return array<string, array{calls_per_day: int, calls_per_minute: int, tier: string}>
     */
    public function getProviderRateLimits(): array
    {
        $limits = [];
        foreach ($this->providers as $provider) {
            $limits[$provider->getProviderName()] = $provider->getRateLimits();
        }
        return $limits;
    }

    /**
     * Generate cache key from ticker and options
     */
    private function getCacheKey(string $ticker, array $options): string
    {
        if (empty($options)) {
            return $ticker;
        }
        return $ticker . '_' . md5(json_encode($options));
    }
}
