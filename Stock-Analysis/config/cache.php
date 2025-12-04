<?php
/**
 * Cache Factory Helper
 * 
 * Provides a helper function to create CacheService instances.
 * Handles Redis connection and configuration loading.
 * 
 * @version 1.0.0
 */

use App\Services\CacheService;
use App\Adapters\RedisAdapter;

/**
 * Get cache service instance
 * 
 * Creates and returns a configured CacheService instance.
 * Returns null if Redis is disabled or unavailable.
 * 
 * @return CacheService|null Cache service or null if disabled
 */
function getCacheService(): ?CacheService
{
    static $cache = null;
    static $initialized = false;
    
    if ($initialized) {
        return $cache;
    }
    
    $initialized = true;
    
    try {
        // Load Redis configuration
        $config = require __DIR__ . '/redis.php';
        
        // Check if caching is enabled
        if (!$config['enabled']) {
            return null;
        }
        
        // Check if Redis extension is loaded
        if (!extension_loaded('redis')) {
            error_log('Redis extension not loaded - caching disabled');
            return null;
        }
        
        // Create Redis adapter
        $redis = new RedisAdapter(
            $config['host'],
            $config['port'],
            $config['timeout'],
            $config['password'],
            $config['database']
        );
        
        // Create cache service
        $cache = new CacheService($redis);
        
        return $cache;
        
    } catch (RuntimeException $e) {
        // Log error but don't crash - gracefully degrade to no caching
        error_log('Failed to initialize cache service: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get cache TTL for specific type
 * 
 * @param string $type Cache type (sector_analysis, index_benchmark, default)
 * @return int TTL in seconds
 */
function getCacheTTL(string $type = 'default'): int
{
    static $config = null;
    
    if ($config === null) {
        $config = require __DIR__ . '/redis.php';
    }
    
    return $config['ttl'][$type] ?? $config['ttl']['default'];
}
