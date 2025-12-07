<?php

namespace WealthSystem\StockAnalysis\Cache;

use WealthSystem\StockAnalysis\Data\NewsSentimentService;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Cached News Sentiment Service
 * 
 * Wraps NewsSentimentService with caching layer using cache-aside pattern.
 * 
 * Cache Strategy:
 * - News sentiment: 1 hour TTL (news changes frequently)
 * - Topic sentiment: 30 minutes TTL (market sentiment evolves quickly)
 * - Cache keys: "news:{symbol}:{params_hash}"
 * 
 * Benefits:
 * - Reduces API calls to Alpha Vantage
 * - Faster response times (Redis < 1ms vs API 100-500ms)
 * - Improved reliability (cache available during API outages)
 * - Consistent data during rapid repeated requests
 * 
 * Example:
 * ```php
 * $newsService = new NewsSentimentService($apiClient);
 * $cache = new RedisCache($redis);
 * 
 * $cachedService = new CachedNewsSentimentService($newsService, $cache);
 * 
 * // First call: fetches from API, caches result
 * $sentiment = $cachedService->getSentiment('AAPL');
 * 
 * // Second call within 1 hour: returns from cache (instant)
 * $sentiment = $cachedService->getSentiment('AAPL');
 * ```
 */
class CachedNewsSentimentService extends NewsSentimentService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const TOPIC_TTL = 1800; // 30 minutes
    private const KEY_PREFIX = 'news:';

    public function __construct(
        private readonly NewsSentimentService $service,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        // Don't call parent constructor - we're wrapping, not extending
    }

    /**
     * Get news sentiment with caching
     *
     * {@inheritdoc}
     */
    public function getSentiment(string $symbol, ?int $limit = null, ?\DateTimeImmutable $since = null): array
    {
        $cacheKey = $this->generateCacheKey($symbol, ['limit' => $limit, 'since' => $since?->getTimestamp()]);

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("News sentiment cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("News sentiment cache miss: {$symbol}");
        $data = $this->service->getSentiment($symbol, $limit, $since);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Get aggregated sentiment score with caching
     *
     * {@inheritdoc}
     */
    public function getAggregatedScore(string $symbol, ?int $limit = null): array
    {
        $cacheKey = $this->generateCacheKey($symbol, ['limit' => $limit, 'type' => 'aggregated']);

        // Try cache first
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Aggregated sentiment cache hit: {$symbol}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Aggregated sentiment cache miss: {$symbol}");
        $data = $this->service->getAggregatedScore($symbol, $limit);

        // Store in cache
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::CACHE_TTL);
        }

        return $data;
    }

    /**
     * Get topic sentiment with caching
     *
     * {@inheritdoc}
     */
    public function getTopicSentiment(string $topic, ?int $limit = null): array
    {
        $cacheKey = self::KEY_PREFIX . 'topic:' . md5($topic . ($limit ?? 'all'));

        // Try cache first (shorter TTL for topics)
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Topic sentiment cache hit: {$topic}");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Topic sentiment cache miss: {$topic}");
        $data = $this->service->getTopicSentiment($topic, $limit);

        // Store in cache with shorter TTL (market topics evolve quickly)
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::TOPIC_TTL);
        }

        return $data;
    }

    /**
     * Get market sentiment with caching
     *
     * {@inheritdoc}
     */
    public function getMarketSentiment(?int $limit = null): array
    {
        $cacheKey = self::KEY_PREFIX . 'market:' . ($limit ?? 'all');

        // Try cache first (shorter TTL for market sentiment)
        $cached = $this->cache->get($cacheKey);
        if ($cached !== null) {
            $this->logger->debug("Market sentiment cache hit");
            return $cached;
        }

        // Cache miss - fetch from service
        $this->logger->debug("Market sentiment cache miss");
        $data = $this->service->getMarketSentiment($limit);

        // Store in cache with shorter TTL
        if (!empty($data)) {
            $this->cache->set($cacheKey, $data, self::TOPIC_TTL);
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
        // Redis doesn't have a built-in way to delete by pattern efficiently
        // For now, we'll delete specific known keys
        // In production, consider using Redis SCAN with pattern matching

        $baseKeys = [
            $this->generateCacheKey($symbol, []),
            $this->generateCacheKey($symbol, ['limit' => null]),
            $this->generateCacheKey($symbol, ['type' => 'aggregated']),
        ];

        $this->cache->deleteMultiple($baseKeys);
        $this->logger->info("Invalidated news sentiment cache for {$symbol}");
    }

    /**
     * Warm cache for multiple symbols
     *
     * @param array<string> $symbols Array of stock symbols
     * @param int|null $limit Number of articles to fetch
     * @return array<string, bool> Symbol => success status
     */
    public function warmCache(array $symbols, ?int $limit = 20): array
    {
        $results = [];

        foreach ($symbols as $symbol) {
            try {
                $this->getSentiment($symbol, $limit);
                $this->getAggregatedScore($symbol, $limit);
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

    /**
     * Generate cache key from symbol and parameters
     *
     * @param string $symbol Stock symbol
     * @param array<string, mixed> $params Additional parameters
     * @return string Cache key
     */
    private function generateCacheKey(string $symbol, array $params): string
    {
        // Create consistent hash from parameters
        ksort($params);
        $paramsHash = md5(json_encode($params));

        return self::KEY_PREFIX . $symbol . ':' . $paramsHash;
    }
}
