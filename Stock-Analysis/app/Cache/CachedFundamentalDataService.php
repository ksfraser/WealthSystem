<?php

namespace WealthSystem\StockAnalysis\Cache;

use WealthSystem\StockAnalysis\Data\FundamentalDataService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cached Fundamental Data Service
 * 
 * Wraps FundamentalDataService with caching layer using cache-aside pattern.
 * 
 * Cache Strategy:
 * - Fundamental data: 4 hours TTL (earnings data doesn't change frequently)
 * - Company overview: 24 hours TTL (company info rarely changes)
 * - Cache keys: "fundamental:{symbol}:{metric}"
 * 
 * Benefits:
 * - Reduces API calls to Alpha Vantage (limited to 500/day free tier)
 * - Faster response times (Redis < 1ms vs API 100-500ms)
 * - Improved reliability (cache available during API outages)
 * 
 * Example:
 * ```php
 * $fundamentalService = new FundamentalDataService($apiClient);
 * $cache = new RedisCache($redis);
 * 
 * $cachedService = new CachedFundamentalDataService($fundamentalService, $cache);
 * 
 * // First call: fetches from API, caches result
 * $data = $cachedService->getFundamentalData('AAPL');
 * 
 * // Second call: returns from cache (instant)
 * $data = $cachedService->getFundamentalData('AAPL');
 * ```
 */
class CachedFundamentalDataService extends FundamentalDataService
{
    private const CACHE_TTL = 14400; // 4 hours
    private const OVERVIEW_TTL = 86400; // 24 hours
    private const KEY_PREFIX = 'fundamental:';

    public function __construct(
        private readonly FundamentalDataService $service,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        // Don't call parent constructor - we're wrapping, not extending
    }

    /**
     * Get fundamental data with caching
     *
     * {@inheritdoc}
     */
    public function getFundamentalData(string $symbol): array
    {
        $cacheKey = self::KEY_PREFIX . $symbol;

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Fundamental data cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Fundamental data cache miss: {$symbol}");
        $data = $this->service->getFundamentalData($symbol);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Get company overview with caching
     *
     * {@inheritdoc}
     */
    public function getCompanyOverview(string $symbol): array
    {
        $cacheKey = self::KEY_PREFIX . $symbol . ':overview';

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Company overview cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Company overview cache miss: {$symbol}");
        $data = $this->service->getCompanyOverview($symbol);

        // Store in cache with longer TTL (company info changes rarely)
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::OVERVIEW_TTL);
        }

        return $data;
    }

    /**
     * Get earnings data with caching
     *
     * {@inheritdoc}
     */
    public function getEarnings(string $symbol): array
    {
        $cacheKey = self::KEY_PREFIX . $symbol . ':earnings';

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Earnings data cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Earnings data cache miss: {$symbol}");
        $data = $this->service->getEarnings($symbol);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Get balance sheet with caching
     *
     * {@inheritdoc}
     */
    public function getBalanceSheet(string $symbol): array
    {
        $cacheKey = self::KEY_PREFIX . $symbol . ':balance_sheet';

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Balance sheet cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Balance sheet cache miss: {$symbol}");
        $data = $this->service->getBalanceSheet($symbol);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Get cash flow with caching
     *
     * {@inheritdoc}
     */
    public function getCashFlow(string $symbol): array
    {
        $cacheKey = self::KEY_PREFIX . $symbol . ':cash_flow';

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Cash flow cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Cash flow cache miss: {$symbol}");
        $data = $this->service->getCashFlow($symbol);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Invalidate cache for symbol
     *
     * @param string $symbol Stock symbol
     * @return void
     */
    public function invalidate(string $symbol): void
    {
        $keys = [
            self::KEY_PREFIX . $symbol,
            self::KEY_PREFIX . $symbol . ':overview',
            self::KEY_PREFIX . $symbol . ':earnings',
            self::KEY_PREFIX . $symbol . ':balance_sheet',
            self::KEY_PREFIX . $symbol . ':cash_flow',
        ];

        $this->cache->deleteMultiple($keys);
        $this->logger->info("Invalidated fundamental data cache for {$symbol}");
    }

    /**
     * Warm cache for multiple symbols
     *
     * @param array<string> $symbols Array of stock symbols
     * @return array<string, bool> Symbol => success status
     */
    public function warmCache(array $symbols): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            try {
                $this->getFundamentalData($symbol);
                $this->getCompanyOverview($symbol);
                $results[$symbol] = true;
            } catch (\Exception $e) {
                $this->logger->error("Failed to warm cache for {$symbol}: " . $e->getMessage());
                $results[$symbol] = false;
            }
        }

        return $results;
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed>
     */
    public function getCacheStats(): array
    {
        return $this->cache->getStats();
    }
}
