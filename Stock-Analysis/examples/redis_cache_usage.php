<?php

/**
 * Redis Cache Usage Examples
 * 
 * Demonstrates Redis caching integration for improved performance.
 * 
 * Examples:
 * 1. Basic Redis cache operations
 * 2. Caching fundamental data service
 * 3. Caching news sentiment service
 * 4. Cache warming for multiple symbols
 * 5. Cache statistics and monitoring
 * 6. Production setup with error handling
 */

require_once __DIR__ . '/../vendor/autoload.php';

use WealthSystem\StockAnalysis\Cache\RedisCache;
use WealthSystem\StockAnalysis\Cache\CachedFundamentalDataService;
use WealthSystem\StockAnalysis\Cache\CachedNewsSentimentService;
use WealthSystem\StockAnalysis\Cache\CacheWarmer;
use WealthSystem\StockAnalysis\Data\FundamentalDataService;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Setup
$logger = new Logger('cache');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

echo "=== Redis Cache Usage Examples ===\n\n";

// ============================================================================
// EXAMPLE 1: Basic Redis Cache Operations
// ============================================================================
echo "--- Example 1: Basic Redis Cache Operations ---\n";

try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    
    $cache = new RedisCache($redis, [
        'prefix' => 'wealth:',
        'default_ttl' => 3600,
    ], $logger);
    
    if ($cache->isAvailable()) {
        echo "✅ Redis connection successful\n";
        
        // Simple get/set
        $cache->set('test:key', 'Hello, Redis!', 60);
        $value = $cache->get('test:key');
        echo "Cached value: {$value}\n";
        
        // Numeric operations
        $cache->set('counter', 0);
        $cache->increment('counter', 5);
        $cache->increment('counter', 3);
        $count = $cache->get('counter');
        echo "Counter value: {$count}\n";
        
        // TTL check
        $ttl = $cache->getTtl('test:key');
        echo "TTL remaining: {$ttl} seconds\n";
        
        // Check existence
        $exists = $cache->has('test:key');
        echo "Key exists: " . ($exists ? 'yes' : 'no') . "\n";
        
        // Cleanup
        $cache->delete('test:key');
        $cache->delete('counter');
    } else {
        echo "❌ Redis not available\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Make sure Redis is installed and running: redis-server\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 2: Caching Fundamental Data Service
// ============================================================================
echo "--- Example 2: Caching Fundamental Data Service ---\n";

if ($cache->isAvailable()) {
    // Original service (would make API calls)
    $fundamentalService = new FundamentalDataService($apiClient ?? null);
    
    // Wrap with caching
    $cachedService = new CachedFundamentalDataService($fundamentalService, $cache, $logger);
    
    $symbol = 'AAPL';
    
    echo "First call (cache miss - would fetch from API):\n";
    $startTime = microtime(true);
    try {
        $data = $cachedService->getFundamentalData($symbol);
        $duration1 = round((microtime(true) - $startTime) * 1000, 2);
        echo "  Duration: {$duration1}ms\n";
        echo "  Data keys: " . implode(', ', array_keys($data)) . "\n";
    } catch (Exception $e) {
        echo "  ⚠️ Note: Would fetch from API (Alpha Vantage key not configured)\n";
    }
    
    echo "\nSecond call (cache hit - instant):\n";
    $startTime = microtime(true);
    try {
        $data = $cachedService->getFundamentalData($symbol);
        $duration2 = round((microtime(true) - $startTime) * 1000, 2);
        echo "  Duration: {$duration2}ms\n";
        echo "  Speed improvement: " . round($duration1 / max($duration2, 0.01), 0) . "x faster\n";
    } catch (Exception $e) {
        echo "  ⚠️ Cache would contain data from first call\n";
    }
    
    echo "\nCache stats:\n";
    $stats = $cachedService->getCacheStats();
    echo "  Hit rate: {$stats['hit_rate']}%\n";
    echo "  Hits: {$stats['hits']}, Misses: {$stats['misses']}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 3: Caching News Sentiment Service
// ============================================================================
echo "--- Example 3: Caching News Sentiment Service ---\n";

if ($cache->isAvailable()) {
    // Original service
    $newsService = new NewsSentimentService($apiClient ?? null);
    
    // Wrap with caching
    $cachedNewsService = new CachedNewsSentimentService($newsService, $cache, $logger);
    
    $symbol = 'MSFT';
    
    echo "Fetching news sentiment for {$symbol}...\n";
    try {
        $sentiment = $cachedNewsService->getSentiment($symbol, 10);
        echo "  Sentiment score: " . ($sentiment['score'] ?? 'N/A') . "\n";
        echo "  Article count: " . ($sentiment['article_count'] ?? 0) . "\n";
        
        // Aggregated score
        $agg = $cachedNewsService->getAggregatedScore($symbol);
        echo "  Aggregated sentiment: " . ($agg['overall'] ?? 'N/A') . "\n";
    } catch (Exception $e) {
        echo "  ⚠️ Would fetch from Alpha Vantage News API\n";
    }
    
    echo "\nCache invalidation:\n";
    $cachedNewsService->invalidate($symbol);
    echo "  Cache cleared for {$symbol}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 4: Cache Warming for Multiple Symbols
// ============================================================================
echo "--- Example 4: Cache Warming ---\n";

if ($cache->isAvailable()) {
    $warmer = new CacheWarmer([
        'fundamental' => $cachedService ?? null,
        'news' => $cachedNewsService ?? null,
    ], $logger);
    
    // Warm watchlist
    $watchlist = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA'];
    
    echo "Warming cache for watchlist...\n";
    $result = $warmer->warmWatchlist($watchlist, [
        'services' => ['fundamental', 'news'],
        'batch_size' => 2,
        'delay_ms' => 100,
    ]);
    
    echo "Results:\n";
    echo "  Total symbols: {$result['total']}\n";
    echo "  Successful: {$result['success']}\n";
    echo "  Failed: {$result['failed']}\n";
    echo "  Success rate: {$result['success_rate']}%\n";
    echo "  Total duration: {$result['duration_ms']}ms\n";
    echo "  Avg per symbol: {$result['duration_per_symbol_ms']}ms\n";
    echo "  Batches: {$result['batches']}\n";
    
    echo "\nWarm single symbol:\n";
    $singleResult = $warmer->warm('TSLA', ['fundamental']);
    echo "  Symbol: {$singleResult['symbol']}\n";
    echo "  Success: " . ($singleResult['success'] ? 'yes' : 'no') . "\n";
    echo "  Duration: {$singleResult['duration_ms']}ms\n";
    
    echo "\nWarming schedule (recommended times):\n";
    foreach ($warmer->getWarmingSchedule() as $name => $schedule) {
        echo "  {$name}: {$schedule['time']} ET - {$schedule['description']}\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 5: Cache Statistics and Monitoring
// ============================================================================
echo "--- Example 5: Cache Statistics ---\n";

if ($cache->isAvailable()) {
    $stats = $cache->getStats();
    
    echo "Redis Server Info:\n";
    echo "  Version: {$stats['redis_version']}\n";
    echo "  Memory used: {$stats['used_memory']}\n";
    echo "  Connected clients: {$stats['connected_clients']}\n";
    echo "  Total commands: {$stats['total_commands']}\n";
    
    echo "\nCache Performance:\n";
    echo "  Hits: {$stats['hits']}\n";
    echo "  Misses: {$stats['misses']}\n";
    echo "  Sets: {$stats['sets']}\n";
    echo "  Deletes: {$stats['deletes']}\n";
    echo "  Hit rate: {$stats['hit_rate']}%\n";
    
    if (!empty($stats['keyspace'])) {
        echo "\nKeyspace Info:\n";
        foreach ($stats['keyspace'] as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
    }
    
    // Service-specific stats
    if (isset($cachedService)) {
        echo "\nFundamental Service Stats:\n";
        $fundStats = $cachedService->getCacheStats();
        echo "  Hit rate: {$fundStats['hit_rate']}%\n";
    }
    
    if (isset($cachedNewsService)) {
        echo "\nNews Service Stats:\n";
        $newsStats = $cachedNewsService->getCacheStats();
        echo "  Hit rate: {$newsStats['hit_rate']}%\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 6: Production Setup with Error Handling
// ============================================================================
echo "--- Example 6: Production Setup ---\n";

// Production configuration
$productionConfig = [
    'redis' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'timeout' => 2.5,
        'database' => 0,
        'prefix' => 'wealth:prod:',
    ],
    'cache' => [
        'default_ttl' => 3600,
        'fundamental_ttl' => 14400, // 4 hours
        'news_ttl' => 1800, // 30 minutes
    ],
];

echo "Production configuration:\n";
echo "  Redis host: {$productionConfig['redis']['host']}:{$productionConfig['redis']['port']}\n";
echo "  Key prefix: {$productionConfig['redis']['prefix']}\n";
echo "  Default TTL: {$productionConfig['cache']['default_ttl']}s\n";

try {
    $redis = new Redis();
    $connected = @$redis->connect(
        $productionConfig['redis']['host'],
        $productionConfig['redis']['port'],
        $productionConfig['redis']['timeout']
    );
    
    if ($connected) {
        $cache = new RedisCache($redis, [
            'prefix' => $productionConfig['redis']['prefix'],
            'default_ttl' => $productionConfig['cache']['default_ttl'],
        ], $logger);
        
        if ($cache->isAvailable()) {
            echo "✅ Production Redis connection successful\n";
            
            // Graceful degradation example
            echo "\nGraceful degradation:\n";
            $data = $cache->get('nonexistent:key', ['default' => 'value']);
            echo "  Missing key returned default: " . json_encode($data) . "\n";
            
            // Error handling
            echo "\nError handling:\n";
            try {
                $cache->set('test:key', 'value');
                echo "  ✅ Set operation succeeded\n";
            } catch (Exception $e) {
                echo "  ❌ Set operation failed: " . $e->getMessage() . "\n";
                echo "  Application continues without cache\n";
            }
            
            // Cleanup
            $cache->delete('test:key');
        }
    } else {
        echo "❌ Could not connect to Redis\n";
        echo "\nWithout cache, application will:\n";
        echo "  - Make direct API calls (slower)\n";
        echo "  - Continue functioning normally\n";
        echo "  - Log warnings for monitoring\n";
    }
} catch (Exception $e) {
    echo "❌ Redis setup error: " . $e->getMessage() . "\n";
    echo "\nTo install Redis:\n";
    echo "  Windows: Download from https://github.com/microsoftarchive/redis/releases\n";
    echo "  Linux: sudo apt-get install redis-server\n";
    echo "  macOS: brew install redis\n";
    echo "\nTo install PHP Redis extension:\n";
    echo "  pecl install redis\n";
    echo "  Add 'extension=redis.so' to php.ini\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Examples complete!\n";
echo "\nNext Steps:\n";
echo "1. Install Redis server\n";
echo "2. Install PHP Redis extension (phpredis)\n";
echo "3. Configure Redis connection in your application\n";
echo "4. Wrap services with caching decorators\n";
echo "5. Set up cache warming schedule\n";
echo "6. Monitor cache hit rates\n";
echo "7. Adjust TTLs based on usage patterns\n";
