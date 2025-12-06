<?php

declare(strict_types=1);

namespace App\Cache;

use Predis\Client;

/**
 * Redis Cache Service
 * 
 * Provides caching layer using Redis for improved performance.
 * 
 * Features:
 * - Key-value storage with TTL
 * - Automatic serialization
 * - Tag-based invalidation
 * - Cache warming
 * - Hit/miss statistics
 * 
 * @package App\Cache
 */
class RedisCacheService
{
    private Client $redis;
    private array $config;
    private bool $enabled;
    
    /** @var array Statistics tracking */
    private array $stats = [
        'hits' => 0,
        'misses' => 0,
        'sets' => 0,
        'deletes' => 0
    ];
    
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0,
            'prefix' => 'trading:',
            'default_ttl' => 3600,
            'enabled' => true
        ], $config);
        
        $this->enabled = $this->config['enabled'];
        
        if ($this->enabled) {
            $this->redis = new Client([
                'scheme' => 'tcp',
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'database' => $this->config['database']
            ]);
        }
    }
    
    /**
     * Get value from cache
     */
    public function get(string $key)
    {
        if (!$this->enabled) {
            return null;
        }
        
        $prefixedKey = $this->prefix($key);
        $value = $this->redis->get($prefixedKey);
        
        if ($value === null) {
            $this->stats['misses']++;
            return null;
        }
        
        $this->stats['hits']++;
        return $this->unserialize($value);
    }
    
    /**
     * Set value in cache with TTL
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $ttl = $ttl ?? $this->config['default_ttl'];
        $prefixedKey = $this->prefix($key);
        $serialized = $this->serialize($value);
        
        $result = $this->redis->setex($prefixedKey, $ttl, $serialized);
        
        if ($result) {
            $this->stats['sets']++;
        }
        
        return (bool)$result;
    }
    
    /**
     * Delete value from cache
     */
    public function delete(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $prefixedKey = $this->prefix($key);
        $result = $this->redis->del([$prefixedKey]);
        
        if ($result > 0) {
            $this->stats['deletes']++;
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if key exists in cache
     */
    public function has(string $key): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        $prefixedKey = $this->prefix($key);
        return (bool)$this->redis->exists($prefixedKey);
    }
    
    /**
     * Get multiple values at once
     */
    public function getMultiple(array $keys): array
    {
        if (!$this->enabled || empty($keys)) {
            return [];
        }
        
        $prefixedKeys = array_map([$this, 'prefix'], $keys);
        $values = $this->redis->mget($prefixedKeys);
        
        $result = [];
        foreach ($keys as $i => $key) {
            if ($values[$i] !== null) {
                $result[$key] = $this->unserialize($values[$i]);
                $this->stats['hits']++;
            } else {
                $this->stats['misses']++;
            }
        }
        
        return $result;
    }
    
    /**
     * Set multiple values at once
     */
    public function setMultiple(array $items, ?int $ttl = null): bool
    {
        if (!$this->enabled || empty($items)) {
            return false;
        }
        
        $ttl = $ttl ?? $this->config['default_ttl'];
        
        foreach ($items as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        
        return true;
    }
    
    /**
     * Delete multiple keys at once
     */
    public function deleteMultiple(array $keys): int
    {
        if (!$this->enabled || empty($keys)) {
            return 0;
        }
        
        $prefixedKeys = array_map([$this, 'prefix'], $keys);
        $deleted = $this->redis->del($prefixedKeys);
        
        $this->stats['deletes'] += $deleted;
        
        return $deleted;
    }
    
    /**
     * Delete all keys matching pattern
     */
    public function deletePattern(string $pattern): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $prefixedPattern = $this->prefix($pattern);
        $keys = $this->redis->keys($prefixedPattern);
        
        if (empty($keys)) {
            return 0;
        }
        
        $deleted = $this->redis->del($keys);
        $this->stats['deletes'] += $deleted;
        
        return $deleted;
    }
    
    /**
     * Clear all cache keys with prefix
     */
    public function clear(): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        return $this->deletePattern('*');
    }
    
    /**
     * Get or set cached value (lazy loading)
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Increment counter
     */
    public function increment(string $key, int $amount = 1): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $prefixedKey = $this->prefix($key);
        return $this->redis->incrby($prefixedKey, $amount);
    }
    
    /**
     * Decrement counter
     */
    public function decrement(string $key, int $amount = 1): int
    {
        if (!$this->enabled) {
            return 0;
        }
        
        $prefixedKey = $this->prefix($key);
        return $this->redis->decrby($prefixedKey, $amount);
    }
    
    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        $stats = $this->stats;
        
        if ($this->enabled) {
            $info = $this->redis->info();
            $stats['redis_version'] = $info['Server']['redis_version'] ?? 'unknown';
            $stats['used_memory'] = $info['Memory']['used_memory_human'] ?? 'unknown';
            $stats['connected_clients'] = $info['Clients']['connected_clients'] ?? 0;
        }
        
        if ($stats['hits'] + $stats['misses'] > 0) {
            $stats['hit_rate'] = round(
                $stats['hits'] / ($stats['hits'] + $stats['misses']) * 100,
                2
            );
        } else {
            $stats['hit_rate'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Check if cache is enabled and connected
     */
    public function isConnected(): bool
    {
        if (!$this->enabled) {
            return false;
        }
        
        try {
            return $this->redis->ping() === 'PONG';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get TTL for key
     */
    public function getTtl(string $key): ?int
    {
        if (!$this->enabled) {
            return null;
        }
        
        $prefixedKey = $this->prefix($key);
        $ttl = $this->redis->ttl($prefixedKey);
        
        return $ttl > 0 ? $ttl : null;
    }
    
    /**
     * Add prefix to key
     */
    private function prefix(string $key): string
    {
        return $this->config['prefix'] . $key;
    }
    
    /**
     * Serialize value for storage
     */
    private function serialize($value): string
    {
        return serialize($value);
    }
    
    /**
     * Unserialize value from storage
     */
    private function unserialize(string $value)
    {
        return unserialize($value);
    }
    
    /**
     * Get underlying Redis client
     */
    public function getClient(): ?Client
    {
        return $this->enabled ? $this->redis : null;
    }
}
