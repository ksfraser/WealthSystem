<?php

namespace Tests\Cache;

use PHPUnit\Framework\TestCase;
use WealthSystem\StockAnalysis\Cache\RedisCache;
use Redis;

/**
 * Tests for Redis Cache Implementation
 */
class RedisCacheTest extends TestCase
{
    private Redis $redis;
    private RedisCache $cache;
    
    protected function setUp(): void
    {
        // Skip tests if Redis is not available
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not available');
        }
        
        $this->redis = new Redis();
        try {
            $this->redis->connect('127.0.0.1', 6379, 1.0);
        } catch (\RedisException $e) {
            $this->markTestSkipped('Redis server not available: ' . $e->getMessage());
        }
        
        $this->cache = new RedisCache($this->redis, [
            'prefix' => 'test:',
            'default_ttl' => 60,
        ]);
        
        // Clear test keys
        $this->cache->clear();
    }
    
    protected function tearDown(): void
    {
        if (isset($this->cache)) {
            $this->cache->clear();
        }
    }
    
    public function testIsAvailable(): void
    {
        $this->assertTrue($this->cache->isAvailable());
    }
    
    public function testSetAndGet(): void
    {
        $this->assertTrue($this->cache->set('key1', 'value1'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }
    
    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', $this->cache->get('nonexistent', 'default'));
    }
    
    public function testSetWithTtl(): void
    {
        $this->cache->set('key2', 'value2', 1);
        $this->assertEquals('value2', $this->cache->get('key2'));
        
        sleep(2);
        $this->assertNull($this->cache->get('key2'));
    }
    
    public function testGetTtl(): void
    {
        $this->cache->set('key3', 'value3', 60);
        $ttl = $this->cache->getTtl('key3');
        
        $this->assertGreaterThan(55, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);
    }
    
    public function testHas(): void
    {
        $this->cache->set('key4', 'value4');
        
        $this->assertTrue($this->cache->has('key4'));
        $this->assertFalse($this->cache->has('nonexistent'));
    }
    
    public function testDelete(): void
    {
        $this->cache->set('key5', 'value5');
        $this->assertTrue($this->cache->has('key5'));
        
        $this->assertTrue($this->cache->delete('key5'));
        $this->assertFalse($this->cache->has('key5'));
    }
    
    public function testGetMultiple(): void
    {
        $this->cache->setMultiple([
            'multi1' => 'value1',
            'multi2' => 'value2',
            'multi3' => 'value3',
        ]);
        
        $values = $this->cache->getMultiple(['multi1', 'multi2', 'multi3', 'missing']);
        
        $this->assertEquals('value1', $values['multi1']);
        $this->assertEquals('value2', $values['multi2']);
        $this->assertEquals('value3', $values['multi3']);
        $this->assertNull($values['missing']);
    }
    
    public function testSetMultiple(): void
    {
        $this->assertTrue($this->cache->setMultiple([
            'batch1' => 'value1',
            'batch2' => 'value2',
        ], 30));
        
        $this->assertEquals('value1', $this->cache->get('batch1'));
        $this->assertEquals('value2', $this->cache->get('batch2'));
    }
    
    public function testDeleteMultiple(): void
    {
        $this->cache->setMultiple([
            'del1' => 'value1',
            'del2' => 'value2',
        ]);
        
        $this->assertTrue($this->cache->deleteMultiple(['del1', 'del2']));
        
        $this->assertFalse($this->cache->has('del1'));
        $this->assertFalse($this->cache->has('del2'));
    }
    
    public function testIncrement(): void
    {
        $this->cache->set('counter', 10);
        
        $this->assertEquals(15, $this->cache->increment('counter', 5));
        $this->assertEquals(16, $this->cache->increment('counter'));
        $this->assertEquals(16, $this->cache->get('counter'));
    }
    
    public function testDecrement(): void
    {
        $this->cache->set('countdown', 20);
        
        $this->assertEquals(15, $this->cache->decrement('countdown', 5));
        $this->assertEquals(14, $this->cache->decrement('countdown'));
        $this->assertEquals(14, $this->cache->get('countdown'));
    }
    
    public function testClear(): void
    {
        $this->cache->set('clear1', 'value1');
        $this->cache->set('clear2', 'value2');
        
        $this->assertTrue($this->cache->clear());
        
        $this->assertFalse($this->cache->has('clear1'));
        $this->assertFalse($this->cache->has('clear2'));
    }
    
    public function testGetStats(): void
    {
        $this->cache->set('stats1', 'value1');
        $this->cache->get('stats1');
        $this->cache->get('nonexistent');
        
        $stats = $this->cache->getStats();
        
        $this->assertArrayHasKey('hits', $stats);
        $this->assertArrayHasKey('misses', $stats);
        $this->assertArrayHasKey('sets', $stats);
        $this->assertArrayHasKey('hit_rate', $stats);
        $this->assertGreaterThanOrEqual(0, $stats['hit_rate']);
    }
    
    public function testSerializationOfArray(): void
    {
        $data = ['name' => 'AAPL', 'price' => 150.00];
        
        $this->cache->set('array_key', $data);
        $retrieved = $this->cache->get('array_key');
        
        $this->assertEquals($data, $retrieved);
    }
    
    public function testSerializationOfObject(): void
    {
        $data = (object)['name' => 'MSFT', 'price' => 300.00];
        
        $this->cache->set('object_key', $data);
        $retrieved = $this->cache->get('object_key');
        
        $this->assertEquals($data, $retrieved);
    }
    
    public function testKeyPrefixing(): void
    {
        $this->cache->set('prefixed', 'value');
        
        // Check Redis directly for prefixed key
        $this->assertTrue($this->redis->exists('test:prefixed') > 0);
        $this->assertFalse($this->redis->exists('prefixed') > 0);
    }
}
