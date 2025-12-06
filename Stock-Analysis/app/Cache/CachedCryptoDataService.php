<?php

declare(strict_types=1);

namespace App\Cache;

use App\Crypto\CryptoDataService;

/**
 * Cached Crypto Data Service
 * 
 * Wraps CryptoDataService with Redis caching for improved performance.
 * 
 * @package App\Cache
 */
class CachedCryptoDataService
{
    private CryptoDataService $dataService;
    private RedisCacheService $cache;
    private int $defaultTtl;
    
    public function __construct(
        CryptoDataService $dataService,
        RedisCacheService $cache,
        int $defaultTtl = 60
    ) {
        $this->dataService = $dataService;
        $this->cache = $cache;
        $this->defaultTtl = $defaultTtl;
    }
    
    /**
     * Get crypto price with caching
     */
    public function getCryptoPrice(string $symbol): array
    {
        $key = "crypto:price:{$symbol}";
        
        return $this->cache->remember($key, function() use ($symbol) {
            return $this->dataService->getCryptoPrice($symbol);
        }, $this->defaultTtl);
    }
    
    /**
     * Get historical prices with caching
     */
    public function getHistoricalPrices(string $symbol, int $days = 30): array
    {
        $key = "crypto:historical:{$symbol}:{$days}";
        
        return $this->cache->remember($key, function() use ($symbol, $days) {
            return $this->dataService->getHistoricalPrices($symbol, $days);
        }, 300); // 5 minute cache for historical data
    }
    
    /**
     * Get 24-hour change with caching
     */
    public function get24HourChange(string $symbol): array
    {
        $key = "crypto:change24h:{$symbol}";
        
        return $this->cache->remember($key, function() use ($symbol) {
            return $this->dataService->get24HourChange($symbol);
        }, $this->defaultTtl);
    }
    
    /**
     * Calculate volatility with caching
     */
    public function calculateVolatility(string $symbol, int $days = 30): float
    {
        $key = "crypto:volatility:{$symbol}:{$days}";
        
        return $this->cache->remember($key, function() use ($symbol, $days) {
            return $this->dataService->calculateVolatility($symbol, $days);
        }, 600); // 10 minute cache for volatility
    }
    
    /**
     * Get ETF NAV with caching
     */
    public function getETFNav(string $etfSymbol): array
    {
        $key = "etf:nav:{$etfSymbol}";
        
        return $this->cache->remember($key, function() use ($etfSymbol) {
            return $this->dataService->getETFNav($etfSymbol);
        }, $this->defaultTtl);
    }
    
    /**
     * Invalidate all cache for a symbol
     */
    public function invalidateSymbol(string $symbol): int
    {
        return $this->cache->deletePattern("*{$symbol}*");
    }
    
    /**
     * Warm cache for multiple symbols
     */
    public function warmCache(array $symbols): void
    {
        foreach ($symbols as $symbol) {
            $this->getCryptoPrice($symbol);
            $this->get24HourChange($symbol);
        }
    }
    
    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return $this->cache->getStats();
    }
}
