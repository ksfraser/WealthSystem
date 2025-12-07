<?php

namespace WealthSystem\StockAnalysis\Cache;

use Redis;
use RedisException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Redis Cache Implementation
 * 
 * High-performance caching using Redis with phpredis extension.
 * 
 * Features:
 * - Automatic serialization/deserialization
 * - Key prefixing for namespacing
 * - Connection health monitoring
 * - Error handling with graceful degradation
 * - Statistics tracking
 * 
 * Requirements:
 * - PHP Redis extension (phpredis)
 * - Redis server 5.0+
 * 
 * Example:
 * ```php
 * $redis = new Redis();
 * $redis->connect('127.0.0.1', 6379);
 * 
 * $cache = new RedisCache($redis, [
 *     'prefix' => 'wealth:',
 *     'default_ttl' => 3600,
 * ]);
 * 
 * $cache->set('stock:AAPL', $data, 1800);
 * $data = $cache->get('stock:AAPL');
 * ```
 */
class RedisCache implements CacheInterface
{
    private readonly string $prefix;
    private readonly int $defaultTtl;
    private int $hits = 0;
    private int $misses = 0;
    private int $sets = 0;
    private int $deletes = 0;

    public function __construct(
        private readonly Redis $redis,
        private readonly array $config = [],
        private readonly LoggerInterface $logger = new NullLogger()
    ) {
        $this->prefix = $config['prefix'] ?? '';
        $this->defaultTtl = $config['default_ttl'] ?? 3600; // 1 hour default
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $value = $this->redis->get($prefixedKey);

            if ($value === false) {
                $this->misses++;
                $this->logger->debug("Cache miss: {$key}");
                return $default;
            }

            $this->hits++;
            $this->logger->debug("Cache hit: {$key}");

            return $this->unserialize($value);
        } catch (RedisException $e) {
            $this->logger->error("Redis get error for key {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $serialized = $this->serialize($value);
            $ttl = $ttl ?? $this->defaultTtl;

            if ($ttl > 0) {
                $result = $this->redis->setex($prefixedKey, $ttl, $serialized);
            } else {
                $result = $this->redis->set($prefixedKey, $serialized);
            }

            if ($result) {
                $this->sets++;
                $this->logger->debug("Cache set: {$key} (TTL: {$ttl}s)");
            }

            return $result;
        } catch (RedisException $e) {
            $this->logger->error("Redis set error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMultiple(array $keys, mixed $default = null): array
    {
        if (empty($keys)) {
            return [];
        }

        try {
            $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
            $values = $this->redis->mGet($prefixedKeys);

            $result = [];
            foreach ($keys as $i => $key) {
                if ($values[$i] === false) {
                    $this->misses++;
                    $result[$key] = $default;
                } else {
                    $this->hits++;
                    $result[$key] = $this->unserialize($values[$i]);
                }
            }

            $this->logger->debug("Cache getMultiple: " . count($keys) . " keys");

            return $result;
        } catch (RedisException $e) {
            $this->logger->error("Redis mGet error: " . $e->getMessage());
            return array_fill_keys($keys, $default);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(array $values, ?int $ttl = null): bool
    {
        if (empty($values)) {
            return true;
        }

        try {
            $ttl = $ttl ?? $this->defaultTtl;

            // Redis mSet doesn't support TTL, so use pipeline
            $this->redis->multi(Redis::PIPELINE);

            foreach ($values as $key => $value) {
                $prefixedKey = $this->prefixKey($key);
                $serialized = $this->serialize($value);

                if ($ttl > 0) {
                    $this->redis->setex($prefixedKey, $ttl, $serialized);
                } else {
                    $this->redis->set($prefixedKey, $serialized);
                }
            }

            $results = $this->redis->exec();
            $success = !in_array(false, $results, true);

            if ($success) {
                $this->sets += count($values);
                $this->logger->debug("Cache setMultiple: " . count($values) . " keys");
            }

            return $success;
        } catch (RedisException $e) {
            $this->logger->error("Redis mSet error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $key): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $result = $this->redis->del($prefixedKey) > 0;

            if ($result) {
                $this->deletes++;
                $this->logger->debug("Cache delete: {$key}");
            }

            return true; // Return true even if key didn't exist
        } catch (RedisException $e) {
            $this->logger->error("Redis delete error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMultiple(array $keys): bool
    {
        if (empty($keys)) {
            return true;
        }

        try {
            $prefixedKeys = array_map([$this, 'prefixKey'], $keys);
            $deleted = $this->redis->del($prefixedKeys);

            $this->deletes += $deleted;
            $this->logger->debug("Cache deleteMultiple: {$deleted}/" . count($keys) . " keys");

            return true;
        } catch (RedisException $e) {
            $this->logger->error("Redis deleteMultiple error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->exists($prefixedKey) > 0;
        } catch (RedisException $e) {
            $this->logger->error("Redis exists error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): bool
    {
        try {
            if (empty($this->prefix)) {
                // Clear entire database if no prefix
                $result = $this->redis->flushDB();
            } else {
                // Clear only prefixed keys
                $keys = $this->redis->keys($this->prefix . '*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
                $result = true;
            }

            $this->logger->info("Cache cleared" . ($this->prefix ? " (prefix: {$this->prefix})" : ""));

            return $result;
        } catch (RedisException $e) {
            $this->logger->error("Redis clear error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getTtl(string $key): ?int
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            $ttl = $this->redis->ttl($prefixedKey);

            // Redis returns -2 if key doesn't exist, -1 if no expiration
            return match ($ttl) {
                -2 => -1,  // Key doesn't exist
                -1 => null, // No expiration
                default => $ttl,
            };
        } catch (RedisException $e) {
            $this->logger->error("Redis TTL error for key {$key}: " . $e->getMessage());
            return -1;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function increment(string $key, int $value = 1): int|false
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->incrBy($prefixedKey, $value);
        } catch (RedisException $e) {
            $this->logger->error("Redis increment error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function decrement(string $key, int $value = 1): int|false
    {
        try {
            $prefixedKey = $this->prefixKey($key);
            return $this->redis->decrBy($prefixedKey, $value);
        } catch (RedisException $e) {
            $this->logger->error("Redis decrement error for key {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStats(): array
    {
        try {
            $info = $this->redis->info();

            return [
                'hits' => $this->hits,
                'misses' => $this->misses,
                'sets' => $this->sets,
                'deletes' => $this->deletes,
                'hit_rate' => $this->hits + $this->misses > 0
                    ? round($this->hits / ($this->hits + $this->misses) * 100, 2)
                    : 0.0,
                'redis_version' => $info['redis_version'] ?? 'unknown',
                'used_memory' => $info['used_memory_human'] ?? 'unknown',
                'connected_clients' => $info['connected_clients'] ?? 0,
                'total_commands' => $info['total_commands_processed'] ?? 0,
                'keyspace' => $this->getKeyspaceStats(),
            ];
        } catch (RedisException $e) {
            $this->logger->error("Redis stats error: " . $e->getMessage());
            return [
                'hits' => $this->hits,
                'misses' => $this->misses,
                'sets' => $this->sets,
                'deletes' => $this->deletes,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        try {
            return $this->redis->ping() !== false;
        } catch (RedisException $e) {
            $this->logger->error("Redis availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get keyspace statistics
     */
    private function getKeyspaceStats(): array
    {
        try {
            $dbIndex = $this->redis->getDbNum();
            $info = $this->redis->info('keyspace');

            if (isset($info["db{$dbIndex}"])) {
                // Parse "keys=123,expires=45,avg_ttl=3600"
                $stats = [];
                $pairs = explode(',', $info["db{$dbIndex}"]);
                foreach ($pairs as $pair) {
                    [$key, $value] = explode('=', $pair);
                    $stats[$key] = $value;
                }
                return $stats;
            }

            return [];
        } catch (RedisException $e) {
            return [];
        }
    }

    /**
     * Add prefix to cache key
     */
    private function prefixKey(string $key): string
    {
        return $this->prefix . $key;
    }

    /**
     * Serialize value for storage
     */
    private function serialize(mixed $value): string
    {
        // Already a string? Return as-is
        if (is_string($value)) {
            return $value;
        }

        // Use JSON for better readability in Redis
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * Unserialize value from storage
     */
    private function unserialize(string $value): mixed
    {
        // Try JSON decode first
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return $decoded;
        } catch (\JsonException $e) {
            // Not JSON, return as string
            return $value;
        }
    }

    /**
     * Get Redis instance (for advanced operations)
     */
    public function getRedis(): Redis
    {
        return $this->redis;
    }

    /**
     * Reset statistics
     */
    public function resetStats(): void
    {
        $this->hits = 0;
        $this->misses = 0;
        $this->sets = 0;
        $this->deletes = 0;
    }
}
