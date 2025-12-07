# Redis Caching Guide

High-performance caching infrastructure using Redis for the WealthSystem trading platform.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Cache Interface](#cache-interface)
5. [Redis Implementation](#redis-implementation)
6. [Service Decorators](#service-decorators)
7. [Cache Warming](#cache-warming)
8. [Best Practices](#best-practices)
9. [Monitoring](#monitoring)
10. [Troubleshooting](#troubleshooting)

## Overview

### Why Redis Caching?

**Problem**: API calls to Alpha Vantage are:
- Limited (500 calls/day on free tier)
- Slow (100-500ms per request)
- Unreliable (network issues, rate limits)

**Solution**: Redis caching provides:
- **Speed**: < 1ms response time (100-500x faster)
- **Reliability**: Cache available during API outages
- **Efficiency**: Dramatically reduces API calls
- **Cost savings**: Stay within free tier limits

### Performance Improvements

| Operation | Without Cache | With Cache | Improvement |
|-----------|---------------|------------|-------------|
| Fundamental data | 300-500ms | < 1ms | 300-500x faster |
| News sentiment | 200-400ms | < 1ms | 200-400x faster |
| Watchlist (10 stocks) | 3-5 seconds | 5-10ms | 300-1000x faster |
| Portfolio analysis | 5-10 seconds | 10-20ms | 250-1000x faster |

### Cache Strategy

We use the **cache-aside pattern**:

```
1. Request data
2. Check cache
   - HIT: Return cached data (fast path)
   - MISS: Fetch from API, store in cache, return data
3. Subsequent requests hit cache (instant)
```

## Installation

### 1. Install Redis Server

**Windows**:
```powershell
# Download from GitHub
https://github.com/microsoftarchive/redis/releases
# Or use Chocolatey
choco install redis-64
```

**Linux (Ubuntu/Debian)**:
```bash
sudo apt-get update
sudo apt-get install redis-server
sudo systemctl start redis
sudo systemctl enable redis
```

**macOS**:
```bash
brew install redis
brew services start redis
```

**Verify Installation**:
```bash
redis-cli ping
# Should return: PONG
```

### 2. Install PHP Redis Extension

**Using PECL**:
```bash
pecl install redis
```

**Add to php.ini**:
```ini
extension=redis.so
```

**Verify**:
```bash
php -m | grep redis
# Should show: redis
```

### 3. Test Connection

```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo $redis->ping(); // Should print: +PONG
```

## Quick Start

### Basic Usage

```php
use WealthSystem\StockAnalysis\Cache\RedisCache;

// Connect to Redis
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Create cache instance
$cache = new RedisCache($redis, [
    'prefix' => 'wealth:',
    'default_ttl' => 3600, // 1 hour
]);

// Store data
$cache->set('stock:AAPL', $stockData, 1800); // 30 minutes

// Retrieve data
$data = $cache->get('stock:AAPL');

// Check if exists
if ($cache->has('stock:AAPL')) {
    echo "Data cached!";
}

// Delete
$cache->delete('stock:AAPL');
```

### With Fundamental Data Service

```php
use WealthSystem\StockAnalysis\Cache\CachedFundamentalDataService;
use WealthSystem\StockAnalysis\Data\FundamentalDataService;

// Original service
$fundamentalService = new FundamentalDataService($apiClient);

// Wrap with caching
$cachedService = new CachedFundamentalDataService($fundamentalService, $cache);

// First call: fetches from API, caches result
$data = $cachedService->getFundamentalData('AAPL'); // ~300ms

// Second call: returns from cache (instant)
$data = $cachedService->getFundamentalData('AAPL'); // < 1ms
```

### With News Sentiment Service

```php
use WealthSystem\StockAnalysis\Cache\CachedNewsSentimentService;
use WealthSystem\StockAnalysis\Data\NewsSentimentService;

$newsService = new NewsSentimentService($apiClient);
$cachedNewsService = new CachedNewsSentimentService($newsService, $cache);

// Cached news sentiment
$sentiment = $cachedNewsService->getSentiment('MSFT');
$aggregated = $cachedNewsService->getAggregatedScore('MSFT');
```

## Cache Interface

Standard interface implemented by all cache backends.

### Core Methods

```php
interface CacheInterface
{
    // Get value
    public function get(string $key, mixed $default = null): mixed;
    
    // Set value with TTL
    public function set(string $key, mixed $value, ?int $ttl = null): bool;
    
    // Get multiple values
    public function getMultiple(array $keys, mixed $default = null): array;
    
    // Set multiple values
    public function setMultiple(array $values, ?int $ttl = null): bool;
    
    // Delete value
    public function delete(string $key): bool;
    
    // Delete multiple values
    public function deleteMultiple(array $keys): bool;
    
    // Check existence
    public function has(string $key): bool;
    
    // Clear all
    public function clear(): bool;
    
    // Get TTL
    public function getTtl(string $key): ?int;
    
    // Increment/decrement
    public function increment(string $key, int $value = 1): int|false;
    public function decrement(string $key, int $value = 1): int|false;
    
    // Statistics
    public function getStats(): array;
    public function isAvailable(): bool;
}
```

### Usage Examples

**Bulk Operations**:
```php
// Set multiple
$cache->setMultiple([
    'stock:AAPL' => $appleData,
    'stock:MSFT' => $msftData,
    'stock:GOOGL' => $googleData,
], 1800);

// Get multiple
$stocks = $cache->getMultiple(['stock:AAPL', 'stock:MSFT', 'stock:GOOGL']);
```

**Counters**:
```php
// API call counter
$cache->set('api:calls:today', 0, 86400); // Reset daily
$cache->increment('api:calls:today');

// Get count
$calls = $cache->get('api:calls:today');
echo "API calls today: {$calls}";
```

**TTL Management**:
```php
// Set with different TTLs based on data type
$cache->set('stock:price', $price, 60);         // 1 minute (real-time)
$cache->set('stock:fundamentals', $data, 14400); // 4 hours (quarterly data)
$cache->set('stock:company', $info, 86400);      // 24 hours (rarely changes)

// Check remaining TTL
$ttl = $cache->getTtl('stock:fundamentals');
echo "Expires in: {$ttl} seconds";
```

## Redis Implementation

### Configuration

```php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379, 2.5); // 2.5 second timeout

$cache = new RedisCache($redis, [
    'prefix' => 'wealth:',         // Namespace keys
    'default_ttl' => 3600,         // Default 1 hour expiration
]);
```

### Key Prefixing

All keys are automatically prefixed for namespacing:

```php
$cache->set('stock:AAPL', $data);
// Stored in Redis as: wealth:stock:AAPL
```

Benefits:
- Multiple applications can share Redis
- Easy to identify and clear application keys
- Prevents key collisions

### Serialization

Automatic serialization/deserialization:

```php
// Arrays
$cache->set('portfolio', ['AAPL' => 100, 'MSFT' => 50]);
$portfolio = $cache->get('portfolio'); // Returns array

// Objects
$cache->set('stock', new Stock('AAPL', 150.00));
$stock = $cache->get('stock'); // Returns object

// Strings (no serialization needed)
$cache->set('symbol', 'AAPL');
```

Uses JSON encoding for readability in Redis:
```bash
# View in Redis CLI
redis-cli
> GET wealth:portfolio
"{\"AAPL\":100,\"MSFT\":50}"
```

### Error Handling

Graceful degradation on Redis failures:

```php
try {
    $data = $cache->get('stock:AAPL');
} catch (RedisException $e) {
    // Logged but doesn't crash application
    // Returns default value
    $data = null;
}
```

All operations return sensible defaults:
- `get()` returns default value on error
- `set()` returns false on error
- `has()` returns false on error
- Application continues without cache

## Service Decorators

### CachedFundamentalDataService

Wraps `FundamentalDataService` with caching.

**Cache TTLs**:
- Fundamental data: 4 hours (earnings data quarterly)
- Company overview: 24 hours (company info rarely changes)
- Earnings: 4 hours
- Balance sheet: 4 hours
- Cash flow: 4 hours

**Usage**:
```php
$service = new FundamentalDataService($apiClient);
$cached = new CachedFundamentalDataService($service, $cache);

// All methods cached automatically
$data = $cached->getFundamentalData('AAPL');
$overview = $cached->getCompanyOverview('AAPL');
$earnings = $cached->getEarnings('AAPL');
$balance = $cached->getBalanceSheet('AAPL');
$cashflow = $cached->getCashFlow('AAPL');

// Invalidate cache when needed
$cached->invalidate('AAPL'); // Clear all AAPL data

// Warm cache for multiple symbols
$cached->warmCache(['AAPL', 'MSFT', 'GOOGL']);
```

### CachedNewsSentimentService

Wraps `NewsSentimentService` with caching.

**Cache TTLs**:
- News sentiment: 1 hour (news changes frequently)
- Topic sentiment: 30 minutes (market sentiment evolves quickly)
- Market sentiment: 30 minutes

**Usage**:
```php
$service = new NewsSentimentService($apiClient);
$cached = new CachedNewsSentimentService($service, $cache);

// All methods cached automatically
$sentiment = $cached->getSentiment('AAPL', 20);
$aggregated = $cached->getAggregatedScore('AAPL');
$topic = $cached->getTopicSentiment('technology');
$market = $cached->getMarketSentiment();

// Invalidate cache
$cached->invalidate('AAPL');

// Warm cache
$cached->warmCache(['AAPL', 'MSFT'], 20);
```

## Cache Warming

Preload frequently accessed data into cache.

### CacheWarmer

```php
use WealthSystem\StockAnalysis\Cache\CacheWarmer;

$warmer = new CacheWarmer([
    'fundamental' => $cachedFundamentalService,
    'news' => $cachedNewsService,
]);

// Warm single symbol
$result = $warmer->warm('AAPL', ['fundamental', 'news']);

// Warm watchlist
$result = $warmer->warmWatchlist(['AAPL', 'MSFT', 'GOOGL'], [
    'services' => ['fundamental', 'news'],
    'batch_size' => 5,
    'delay_ms' => 100,
]);

// Warm S&P 500 (top 100)
$result = $warmer->warmSP500([
    'services' => ['fundamental'],
    'batch_size' => 50,
]);
```

### Warming Schedule

Recommended schedule for different times of day:

**Pre-market (8:00 AM ET)**:
- Warm S&P 500 top 100
- All services (fundamental + news)
- Prepare for market open

**Market open (9:30 AM ET)**:
- Warm watchlist symbols
- Refresh news sentiment
- Quick updates for active trades

**Midday (12:00 PM ET)**:
- Update news sentiment
- Top 50 stocks only
- Light refresh

**Post-market (5:00 PM ET)**:
- Update fundamental data
- Full S&P 500 top 100
- Prepare for next day

**Implementation**:
```php
$schedule = $warmer->getWarmingSchedule();

// Example: Run with cron
// 0 8 * * 1-5 php warm_cache.php pre_market
// 30 9 * * 1-5 php warm_cache.php market_open
// 0 12 * * 1-5 php warm_cache.php midday
// 0 17 * * 1-5 php warm_cache.php post_market
```

## Best Practices

### 1. Choose Appropriate TTLs

**Short TTL** (minutes):
- Real-time prices: 1-5 minutes
- News sentiment: 30 minutes
- Intraday data: 15 minutes

**Medium TTL** (hours):
- Fundamental data: 4 hours
- Historical prices: 1-2 hours
- Company info: 12 hours

**Long TTL** (days):
- Company overview: 24 hours
- Historical data (old): 7 days
- Static reference data: 30 days

### 2. Key Naming Conventions

Use consistent, hierarchical keys:

```php
// Good
'stock:AAPL:price'
'stock:AAPL:fundamentals'
'stock:AAPL:news:sentiment'
'portfolio:user123:holdings'
'market:sp500:performance'

// Bad
'aapl_price'
'apple-fundamentals'
'newsAAPL'
```

### 3. Batch Operations

Use bulk operations when possible:

```php
// Good: Single network round-trip
$cache->setMultiple([
    'stock:AAPL' => $appleData,
    'stock:MSFT' => $msftData,
    'stock:GOOGL' => $googleData,
]);

// Bad: Three network round-trips
$cache->set('stock:AAPL', $appleData);
$cache->set('stock:MSFT', $msftData);
$cache->set('stock:GOOGL', $googleData);
```

### 4. Monitor Hit Rates

Track cache effectiveness:

```php
$stats = $cache->getStats();
$hitRate = $stats['hit_rate'];

// Target hit rates:
// > 80%: Excellent
// 60-80%: Good
// < 60%: Needs tuning (adjust TTLs or warming)

echo "Cache hit rate: {$hitRate}%\n";
```

### 5. Handle Cache Failures Gracefully

Never let cache failures crash application:

```php
try {
    $data = $cache->get('stock:AAPL');
    if ($data === null) {
        // Cache miss - fetch from source
        $data = $apiClient->getFundamentalData('AAPL');
        $cache->set('stock:AAPL', $data);
    }
} catch (RedisException $e) {
    // Redis down - continue without cache
    $logger->warning("Cache unavailable: " . $e->getMessage());
    $data = $apiClient->getFundamentalData('AAPL');
}

return $data;
```

### 6. Cache Invalidation

Invalidate cache when data changes:

```php
// After updating stock price
$cache->delete('stock:AAPL:price');

// After news article published
$cachedNewsService->invalidate('AAPL');

// After earnings release
$cachedFundamentalService->invalidate('AAPL');
```

### 7. Use Warming for Performance

Warm cache before heavy operations:

```php
// Before portfolio analysis
$symbols = ['AAPL', 'MSFT', 'GOOGL', 'AMZN'];
$warmer->warmWatchlist($symbols);

// Now analysis is instant
foreach ($symbols as $symbol) {
    $data = $cachedService->getFundamentalData($symbol); // < 1ms
    analyzeStock($data);
}
```

## Monitoring

### Cache Statistics

```php
$stats = $cache->getStats();

print_r($stats);
/*
Array (
    [hits] => 450
    [misses] => 50
    [sets] => 60
    [deletes] => 10
    [hit_rate] => 90.0
    [redis_version] => 6.2.6
    [used_memory] => 2.5M
    [connected_clients] => 3
    [total_commands] => 5420
    [keyspace] => Array (
        [keys] => 234
        [expires] => 180
        [avg_ttl] => 2400
    )
)
*/
```

### Service-Specific Stats

```php
// Fundamental data service
$fundStats = $cachedFundamentalService->getCacheStats();
echo "Fundamental hit rate: {$fundStats['hit_rate']}%\n";

// News service
$newsStats = $cachedNewsService->getCacheStats();
echo "News hit rate: {$newsStats['hit_rate']}%\n";
```

### Redis Monitoring

**Command Line**:
```bash
# Monitor commands in real-time
redis-cli monitor

# Get server info
redis-cli info

# Get memory usage
redis-cli info memory

# Get keyspace info
redis-cli info keyspace
```

**Logging**:
```php
// Log slow operations
$start = microtime(true);
$data = $cache->get('stock:AAPL');
$duration = (microtime(true) - $start) * 1000;

if ($duration > 10) { // > 10ms is slow for cache
    $logger->warning("Slow cache operation: {$duration}ms");
}
```

## Troubleshooting

### Connection Issues

**Problem**: Cannot connect to Redis

**Solutions**:
```bash
# Check if Redis is running
redis-cli ping

# Check if Redis is listening
netstat -an | grep 6379

# Start Redis
# Windows: redis-server
# Linux: sudo systemctl start redis
# macOS: brew services start redis

# Check Redis logs
# Linux: /var/log/redis/redis-server.log
# macOS: /usr/local/var/log/redis.log
```

### High Memory Usage

**Problem**: Redis using too much memory

**Solutions**:

1. **Set max memory limit**:
```bash
# In redis.conf
maxmemory 256mb
maxmemory-policy allkeys-lru
```

2. **Monitor memory**:
```php
$stats = $cache->getStats();
echo "Memory used: {$stats['used_memory']}\n";
```

3. **Clear old data**:
```php
$cache->clear(); // Clear all keys with prefix
```

4. **Reduce TTLs**:
```php
// Instead of 24 hours
$cache->set('key', $data, 86400);

// Use 4 hours
$cache->set('key', $data, 14400);
```

### Low Hit Rates

**Problem**: Cache hit rate < 60%

**Diagnosis**:
```php
$stats = $cache->getStats();
echo "Hit rate: {$stats['hit_rate']}%\n";
echo "Hits: {$stats['hits']}, Misses: {$stats['misses']}\n";
```

**Solutions**:

1. **Increase TTLs**:
```php
// Data expiring too quickly
$cache->set('stock:AAPL', $data, 7200); // 2 hours instead of 1
```

2. **Implement warming**:
```php
// Pre-warm frequently accessed data
$warmer->warmWatchlist($watchlist);
```

3. **Check access patterns**:
```php
// Log cache misses
$data = $cache->get('key');
if ($data === null) {
    $logger->info("Cache miss: key");
}
```

### Slow Performance

**Problem**: Cache operations slow (> 10ms)

**Solutions**:

1. **Check Redis CPU**:
```bash
redis-cli info cpu
```

2. **Use pipelining for bulk operations**:
```php
// Use bulk methods
$cache->setMultiple($data);
// Instead of loop
foreach ($data as $k => $v) $cache->set($k, $v);
```

3. **Reduce serialization overhead**:
```php
// Store simple data types when possible
$cache->set('count', 42); // Integer (fast)
// Instead of
$cache->set('count', ['value' => 42]); // Array (slower)
```

## Conclusion

Redis caching provides dramatic performance improvements for the WealthSystem trading platform:

- **300-500x faster** than API calls
- **Reduces API usage** by 80-95%
- **Improves reliability** during API outages
- **Better user experience** with instant responses

**Next Steps**:

1. Install Redis and PHP extension
2. Wrap services with cache decorators
3. Configure appropriate TTLs
4. Set up cache warming schedule
5. Monitor hit rates and adjust
6. Enjoy the speed boost! 🚀

**Resources**:

- Redis documentation: https://redis.io/documentation
- PHP Redis extension: https://github.com/phpredis/phpredis
- Cache best practices: https://redis.io/topics/lru-cache
