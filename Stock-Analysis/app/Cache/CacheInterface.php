<?php

namespace WealthSystem\StockAnalysis\Cache;

/**
 * Cache Interface
 * 
 * Standard interface for caching implementations.
 * Supports multiple backends: Redis, Memcached, File, APCu
 * 
 * Key Features:
 * - Get/Set operations with TTL
 * - Bulk operations (getMultiple, setMultiple)
 * - Key existence checking
 * - TTL inspection
 * - Cache clearing
 * - Automatic serialization/deserialization
 * 
 * Example:
 * ```php
 * $cache = new RedisCache($redis);
 * 
 * // Simple operations
 * $cache->set('user:123', $userData, 3600); // 1 hour TTL
 * $user = $cache->get('user:123');
 * 
 * // Bulk operations
 * $cache->setMultiple([
 *     'stock:AAPL' => $appleData,
 *     'stock:MSFT' => $msftData,
 * ], 1800);
 * 
 * $stocks = $cache->getMultiple(['stock:AAPL', 'stock:MSFT']);
 * ```
 */
interface CacheInterface
{
    /**
     * Get value from cache
     *
     * @param string $key Cache key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed Cached value or default
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Set value in cache
     *
     * @param string $key Cache key
     * @param mixed $value Value to cache (will be serialized)
     * @param int|null $ttl Time to live in seconds (null = no expiration)
     * @return bool True on success
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool;

    /**
     * Get multiple values from cache
     *
     * @param array<string> $keys Array of cache keys
     * @param mixed $default Default value for missing keys
     * @return array<string, mixed> Key-value pairs
     */
    public function getMultiple(array $keys, mixed $default = null): array;

    /**
     * Set multiple values in cache
     *
     * @param array<string, mixed> $values Key-value pairs to cache
     * @param int|null $ttl Time to live in seconds
     * @return bool True if all succeeded
     */
    public function setMultiple(array $values, ?int $ttl = null): bool;

    /**
     * Delete value from cache
     *
     * @param string $key Cache key
     * @return bool True on success (also true if key didn't exist)
     */
    public function delete(string $key): bool;

    /**
     * Delete multiple values from cache
     *
     * @param array<string> $keys Array of cache keys
     * @return bool True if all succeeded
     */
    public function deleteMultiple(array $keys): bool;

    /**
     * Check if key exists in cache
     *
     * @param string $key Cache key
     * @return bool True if exists
     */
    public function has(string $key): bool;

    /**
     * Clear all cache entries
     *
     * @return bool True on success
     */
    public function clear(): bool;

    /**
     * Get remaining TTL for key
     *
     * @param string $key Cache key
     * @return int|null Seconds until expiration, null if no expiration, -1 if doesn't exist
     */
    public function getTtl(string $key): ?int;

    /**
     * Increment numeric value
     *
     * @param string $key Cache key
     * @param int $value Amount to increment by
     * @return int|false New value or false on failure
     */
    public function increment(string $key, int $value = 1): int|false;

    /**
     * Decrement numeric value
     *
     * @param string $key Cache key
     * @param int $value Amount to decrement by
     * @return int|false New value or false on failure
     */
    public function decrement(string $key, int $value = 1): int|false;

    /**
     * Get cache statistics
     *
     * @return array<string, mixed> Statistics (hits, misses, size, etc.)
     */
    public function getStats(): array;

    /**
     * Check if cache backend is available
     *
     * @return bool True if cache is operational
     */
    public function isAvailable(): bool;
}
