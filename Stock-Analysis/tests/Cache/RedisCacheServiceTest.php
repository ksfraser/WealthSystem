<?php

declare(strict_types=1);

namespace Tests\Cache;

use PHPUnit\Framework\TestCase;
use App\Cache\RedisCacheService;

class RedisCacheServiceTest extends TestCase
{
    private RedisCacheService $cache;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use disabled cache for tests (no Redis required)
        $this->cache = new RedisCacheService(['enabled' => false]);
    }
    
    public function testCacheDisabledReturnsNull(): void
    {
        $this->assertNull($this->cache->get('test_key'));
        $this->assertFalse($this->cache->set('test_key', 'value'));
        $this->assertFalse($this->cache->has('test_key'));
    }
    
    public function testCacheConfiguration(): void
    {
        $config = [
            'host' => 'localhost',
            'port' => 6379,
            'prefix' => 'test:',
            'default_ttl' => 7200,
            'enabled' => false
        ];
        
        $cache = new RedisCacheService($config);
        
        $this->assertInstanceOf(RedisCacheService::class, $cache);
    }
    
    public function testIsConnectedWhenDisabled(): void
    {
        $this->assertFalse($this->cache->isConnected());
    }
    
    public function testGetStatsWithDisabledCache(): void
    {
        $stats = $this->cache->getStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('sets', $stats);
        $this->assertArrayHasKey('deletes', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertSame(0, $stats['hit_rate']);
    }
    
    public function testDeleteWhenDisabled(): void
    {
        $this->assertFalse($this->cache->delete('test_key'));
    }
    
    public function testGetMultipleWhenDisabled(): void
    {
        $result = $this->cache->getMultiple(['key1', 'key2', 'key3']);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }
    
    public function testSetMultipleWhenDisabled(): void
    {
        $items = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];
        
        $this->assertFalse($this->cache->setMultiple($items));
    }
    
    public function testDeleteMultipleWhenDisabled(): void
    {
        $result = $this->cache->deleteMultiple(['key1', 'key2']);
        
        $this->assertSame(0, $result);
    }
    
    public function testDeletePatternWhenDisabled(): void
    {
        $result = $this->cache->deletePattern('test:*');
        
        $this->assertSame(0, $result);
    }
    
    public function testClearWhenDisabled(): void
    {
        $result = $this->cache->clear();
        
        $this->assertSame(0, $result);
    }
    
    public function testRememberWhenDisabled(): void
    {
        $callbackExecuted = false;
        
        $result = $this->cache->remember('test_key', function() use (&$callbackExecuted) {
            $callbackExecuted = true;
            return 'computed_value';
        });
        
        $this->assertTrue($callbackExecuted);
        $this->assertSame('computed_value', $result);
    }
    
    public function testIncrementWhenDisabled(): void
    {
        $result = $this->cache->increment('counter');
        
        $this->assertSame(0, $result);
    }
    
    public function testDecrementWhenDisabled(): void
    {
        $result = $this->cache->decrement('counter');
        
        $this->assertSame(0, $result);
    }
    
    public function testGetTtlWhenDisabled(): void
    {
        $this->assertNull($this->cache->getTtl('test_key'));
    }
    
    public function testGetClientWhenDisabled(): void
    {
        $this->assertNull($this->cache->getClient());
    }
}
