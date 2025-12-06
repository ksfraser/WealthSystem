<?php

declare(strict_types=1);

namespace Tests\Cache;

use PHPUnit\Framework\TestCase;
use App\Cache\CachedCryptoDataService;
use App\Cache\RedisCacheService;
use App\Crypto\CryptoDataService;

class CachedCryptoDataServiceTest extends TestCase
{
    private CachedCryptoDataService $cachedService;
    private CryptoDataService $dataService;
    private RedisCacheService $cache;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use mock data mode
        $this->dataService = new CryptoDataService(['use_mock_data' => true]);
        $this->cache = new RedisCacheService(['enabled' => false]);
        $this->cachedService = new CachedCryptoDataService(
            $this->dataService,
            $this->cache,
            60
        );
    }
    
    public function testGetCryptoPrice(): void
    {
        $priceData = $this->cachedService->getCryptoPrice('BTC');
        
        $this->assertIsArray($priceData);
        $this->assertArrayHasKey('price', $priceData);
        $this->assertIsFloat($priceData['price']);
        $this->assertGreaterThan(0, $priceData['price']);
    }
    
    public function testGetHistoricalPrices(): void
    {
        $prices = $this->cachedService->getHistoricalPrices('BTC', 7);
        
        $this->assertIsArray($prices);
        $this->assertNotEmpty($prices);
        $this->assertArrayHasKey('date', $prices[0]);
        $this->assertArrayHasKey('price', $prices[0]);
    }
    
    public function testGet24HourChange(): void
    {
        $changeData = $this->cachedService->get24HourChange('ETH');
        
        $this->assertIsArray($changeData);
        $this->assertArrayHasKey('change_percent', $changeData);
        $this->assertIsFloat($changeData['change_percent']);
        $this->assertArrayHasKey('high_24h', $changeData);
        $this->assertArrayHasKey('low_24h', $changeData);
    }
    
    public function testCalculateVolatility(): void
    {
        $volatility = $this->cachedService->calculateVolatility('BTC', 30);
        
        $this->assertIsFloat($volatility);
        $this->assertGreaterThanOrEqual(0, $volatility);
    }
    
    public function testGetETFNav(): void
    {
        $navData = $this->cachedService->getETFNav('IBIT');
        
        $this->assertIsArray($navData);
        $this->assertArrayHasKey('nav', $navData);
        $this->assertIsFloat($navData['nav']);
        $this->assertGreaterThan(0, $navData['nav']);
    }
    
    public function testInvalidateSymbol(): void
    {
        $result = $this->cachedService->invalidateSymbol('BTC');
        
        // With disabled cache, returns 0
        $this->assertSame(0, $result);
    }
    
    public function testWarmCache(): void
    {
        $symbols = ['BTC', 'ETH', 'SOL'];
        
        // Should not throw exception
        $this->cachedService->warmCache($symbols);
        
        $this->assertTrue(true);
    }
    
    public function testGetCacheStats(): void
    {
        $stats = $this->cachedService->getCacheStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
    }
}
