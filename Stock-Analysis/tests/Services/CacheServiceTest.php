<?php

namespace Tests\Services;

use PHPUnit\Framework\TestCase;
use App\Services\CacheService;
use App\Interfaces\RedisInterface;
use RuntimeException;

/**
 * Test suite for CacheService
 * 
 * Tests Redis caching functionality including connection, get/set operations,
 * expiration, serialization, and error handling.
 */
class CacheServiceTest extends TestCase
{
    private CacheService $service;
    private RedisInterface $mockRedis;

    protected function setUp(): void
    {
        $this->mockRedis = $this->createMock(RedisInterface::class);
        $this->service = new CacheService($this->mockRedis);
    }

    /**
     * Test service instantiation
     */
    public function testServiceInstantiation(): void
    {
        $this->assertInstanceOf(CacheService::class, $this->service);
    }

    /**
     * Test get() with existing key
     */
    public function testGetExistingKey(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn('test_value');

        $result = $this->service->get('test_key');
        $this->assertEquals('test_value', $result);
    }

    /**
     * Test get() with non-existent key
     */
    public function testGetNonExistentKey(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('get')
            ->with('missing_key')
            ->willReturn(false);

        $result = $this->service->get('missing_key');
        $this->assertNull($result);
    }

    /**
     * Test set() with string value
     */
    public function testSetStringValue(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('setex')
            ->with('test_key', 300, 'test_value')
            ->willReturn(true);

        $result = $this->service->set('test_key', 'test_value', 300);
        $this->assertTrue($result);
    }

    /**
     * Test set() with array value (serialization)
     */
    public function testSetArrayValue(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $serialized = json_encode($data);

        $this->mockRedis
            ->expects($this->once())
            ->method('setex')
            ->with('test_key', 300, $serialized)
            ->willReturn(true);

        $result = $this->service->set('test_key', $data, 300);
        $this->assertTrue($result);
    }

    /**
     * Test get() with serialized array value
     */
    public function testGetArrayValue(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $serialized = json_encode($data);

        $this->mockRedis
            ->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willReturn($serialized);

        $result = $this->service->get('test_key');
        $this->assertEquals($data, $result);
    }

    /**
     * Test delete() removes key
     */
    public function testDelete(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->willReturn(1);

        $result = $this->service->delete('test_key');
        $this->assertTrue($result);
    }

    /**
     * Test delete() with non-existent key
     */
    public function testDeleteNonExistentKey(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('del')
            ->with('missing_key')
            ->willReturn(0);

        $result = $this->service->delete('missing_key');
        $this->assertFalse($result);
    }

    /**
     * Test exists() for existing key
     */
    public function testExistsTrue(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('exists')
            ->with('test_key')
            ->willReturn(1);

        $result = $this->service->exists('test_key');
        $this->assertTrue($result);
    }

    /**
     * Test exists() for non-existent key
     */
    public function testExistsFalse(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('exists')
            ->with('missing_key')
            ->willReturn(0);

        $result = $this->service->exists('missing_key');
        $this->assertFalse($result);
    }

    /**
     * Test flush() clears all keys
     */
    public function testFlush(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('flushDB')
            ->willReturn(true);

        $result = $this->service->flush();
        $this->assertTrue($result);
    }

    /**
     * Test generateKey() creates consistent keys
     */
    public function testGenerateKey(): void
    {
        $key1 = $this->service->generateKey('sector', ['user_id' => 1]);
        $key2 = $this->service->generateKey('sector', ['user_id' => 1]);
        $key3 = $this->service->generateKey('sector', ['user_id' => 2]);

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertStringContainsString('sector', $key1);
    }

    /**
     * Test generateKey() with multiple parameters
     */
    public function testGenerateKeyMultipleParams(): void
    {
        $params = [
            'symbol' => 'AAPL',
            'index' => 'SPX',
            'period' => '1Y'
        ];

        $key = $this->service->generateKey('index_benchmark', $params);

        $this->assertStringContainsString('index_benchmark', $key);
        $this->assertStringContainsString('AAPL', $key);
        $this->assertStringContainsString('SPX', $key);
        $this->assertStringContainsString('1Y', $key);
    }

    /**
     * Test error handling for Redis connection failure
     */
    public function testConnectionError(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('get')
            ->with('test_key')
            ->willThrowException(new RuntimeException('Connection failed'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Connection failed');

        $this->service->get('test_key');
    }

    /**
     * Test TTL default value
     */
    public function testDefaultTTL(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('setex')
            ->with('test_key', 600, 'test_value')
            ->willReturn(true);

        // Should use default TTL of 600 seconds
        $result = $this->service->set('test_key', 'test_value');
        $this->assertTrue($result);
    }

    /**
     * Test getTTL() returns remaining time
     */
    public function testGetTTL(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('ttl')
            ->with('test_key')
            ->willReturn(300);

        $result = $this->service->getTTL('test_key');
        $this->assertEquals(300, $result);
    }

    /**
     * Test getTTL() for non-existent key
     */
    public function testGetTTLNonExistent(): void
    {
        $this->mockRedis
            ->expects($this->once())
            ->method('ttl')
            ->with('missing_key')
            ->willReturn(-2);

        $result = $this->service->getTTL('missing_key');
        $this->assertEquals(-2, $result);
    }
}
