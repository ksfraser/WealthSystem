<?php

namespace App\Services;

use App\Interfaces\RedisInterface;
use RuntimeException;

/**
 * Cache Service
 * 
 * Provides caching functionality using Redis.
 * Handles serialization, key generation, and TTL management.
 * 
 * @package App\Services
 */
class CacheService
{
    private const DEFAULT_TTL = 600; // 10 minutes
    
    private RedisInterface $redis;

    /**
     * Constructor
     * 
     * @param RedisInterface $redis Redis client instance
     */
    public function __construct(RedisInterface $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Get value from cache
     * 
     * Automatically deserializes JSON arrays.
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     * @throws RuntimeException If Redis operation fails
     */
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);

        if ($value === false) {
            return null;
        }

        // Try to decode JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return $value;
    }

    /**
     * Set value in cache with TTL
     * 
     * Automatically serializes arrays and objects to JSON.
     * 
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @param int $ttl Time to live in seconds (default: 600)
     * @return bool True on success
     * @throws RuntimeException If Redis operation fails
     */
    public function set(string $key, mixed $value, int $ttl = self::DEFAULT_TTL): bool
    {
        // Serialize arrays and objects
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
            if ($value === false) {
                throw new RuntimeException('Failed to serialize value for caching');
            }
        }

        return $this->redis->setex($key, $ttl, (string)$value);
    }

    /**
     * Delete key from cache
     * 
     * @param string $key Cache key
     * @return bool True if key was deleted, false if not found
     * @throws RuntimeException If Redis operation fails
     */
    public function delete(string $key): bool
    {
        $deleted = $this->redis->del($key);
        return $deleted > 0;
    }

    /**
     * Check if key exists in cache
     * 
     * @param string $key Cache key
     * @return bool True if key exists
     * @throws RuntimeException If Redis operation fails
     */
    public function exists(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    /**
     * Clear all cache keys
     * 
     * WARNING: This flushes the entire Redis database!
     * Use with caution in production.
     * 
     * @return bool True on success
     * @throws RuntimeException If Redis operation fails
     */
    public function flush(): bool
    {
        return $this->redis->flushDB();
    }

    /**
     * Generate consistent cache key
     * 
     * Creates a key from prefix and parameters.
     * Parameters are sorted to ensure consistency.
     * 
     * Example:
     *   generateKey('sector', ['user_id' => 1])
     *   => "sector:user_id_1"
     * 
     * @param string $prefix Key prefix (e.g., 'sector', 'index_benchmark')
     * @param array $params Associative array of parameters
     * @return string Generated cache key
     */
    public function generateKey(string $prefix, array $params = []): string
    {
        ksort($params); // Sort for consistency

        $parts = [$prefix];
        foreach ($params as $key => $value) {
            $parts[] = "{$key}_{$value}";
        }

        return implode(':', $parts);
    }

    /**
     * Get time to live for key
     * 
     * @param string $key Cache key
     * @return int Seconds remaining, -1 if no expiry, -2 if not exists
     * @throws RuntimeException If Redis operation fails
     */
    public function getTTL(string $key): int
    {
        return $this->redis->ttl($key);
    }
}
