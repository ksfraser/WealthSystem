<?php

namespace App\Adapters;

use App\Interfaces\RedisInterface;
use Redis;
use RuntimeException;

/**
 * Redis Adapter
 * 
 * Wraps the native Redis extension class to implement RedisInterface.
 * Provides connection management and error handling.
 * 
 * @package App\Adapters
 */
class RedisAdapter implements RedisInterface
{
    private Redis $redis;
    private bool $connected = false;

    /**
     * Constructor
     * 
     * @param string $host Redis host (default: 127.0.0.1)
     * @param int $port Redis port (default: 6379)
     * @param float $timeout Connection timeout in seconds (default: 2.5)
     * @param string|null $password Redis password (optional)
     * @param int $database Database number (default: 0)
     * @throws RuntimeException If connection fails
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 2.5,
        ?string $password = null,
        int $database = 0
    ) {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis extension not loaded');
        }

        $this->redis = new Redis();
        
        try {
            $this->connected = $this->redis->connect($host, $port, $timeout);
            
            if (!$this->connected) {
                throw new RuntimeException("Failed to connect to Redis at {$host}:{$port}");
            }
            
            if ($password !== null) {
                if (!$this->redis->auth($password)) {
                    throw new RuntimeException('Redis authentication failed');
                }
            }
            
            if ($database !== 0) {
                if (!$this->redis->select($database)) {
                    throw new RuntimeException("Failed to select Redis database {$database}");
                }
            }
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis connection error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get value from Redis
     * 
     * @param string $key Cache key
     * @return string|false Value or false if not found
     * @throws RuntimeException If connection fails
     */
    public function get(string $key): string|false
    {
        try {
            return $this->redis->get($key);
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis get failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Set key with expiration time
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param string $value Value to store
     * @return bool True on success
     * @throws RuntimeException If connection fails
     */
    public function setex(string $key, int $ttl, string $value): bool
    {
        try {
            return $this->redis->setex($key, $ttl, $value);
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis setex failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Delete key
     * 
     * @param string $key Cache key
     * @return int Number of keys deleted
     * @throws RuntimeException If connection fails
     */
    public function del(string $key): int
    {
        try {
            return $this->redis->del($key);
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis del failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if key exists
     * 
     * @param string $key Cache key
     * @return int 1 if exists, 0 if not
     * @throws RuntimeException If connection fails
     */
    public function exists(string $key): int
    {
        try {
            return $this->redis->exists($key);
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis exists failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Flush all keys from current database
     * 
     * @return bool True on success
     * @throws RuntimeException If connection fails
     */
    public function flushDB(): bool
    {
        try {
            return $this->redis->flushDB();
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis flushDB failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get time to live for key
     * 
     * @param string $key Cache key
     * @return int Seconds remaining, -1 if no expiry, -2 if not exists
     * @throws RuntimeException If connection fails
     */
    public function ttl(string $key): int
    {
        try {
            return $this->redis->ttl($key);
        } catch (\RedisException $e) {
            throw new RuntimeException('Redis ttl failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if connected to Redis
     * 
     * @return bool True if connected
     */
    public function isConnected(): bool
    {
        return $this->connected;
    }

    /**
     * Close Redis connection
     */
    public function close(): void
    {
        if ($this->connected) {
            $this->redis->close();
            $this->connected = false;
        }
    }

    /**
     * Destructor - close connection
     */
    public function __destruct()
    {
        $this->close();
    }
}
