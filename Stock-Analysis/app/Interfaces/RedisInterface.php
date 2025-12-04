<?php

namespace App\Interfaces;

use RuntimeException;

/**
 * Redis Interface
 * 
 * Wrapper interface for Redis client operations.
 * Provides type hints and documentation for Redis methods.
 */
interface RedisInterface
{
    /**
     * Get value from Redis
     * 
     * @param string $key Cache key
     * @return string|false Value or false if not found
     * @throws RuntimeException If connection fails
     */
    public function get(string $key): string|false;

    /**
     * Set key with expiration time
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param string $value Value to store
     * @return bool True on success
     * @throws RuntimeException If connection fails
     */
    public function setex(string $key, int $ttl, string $value): bool;

    /**
     * Delete key
     * 
     * @param string $key Cache key
     * @return int Number of keys deleted
     * @throws RuntimeException If connection fails
     */
    public function del(string $key): int;

    /**
     * Check if key exists
     * 
     * @param string $key Cache key
     * @return int 1 if exists, 0 if not
     * @throws RuntimeException If connection fails
     */
    public function exists(string $key): int;

    /**
     * Flush all keys from current database
     * 
     * @return bool True on success
     * @throws RuntimeException If connection fails
     */
    public function flushDB(): bool;

    /**
     * Get time to live for key
     * 
     * @param string $key Cache key
     * @return int Seconds remaining, -1 if no expiry, -2 if not exists
     * @throws RuntimeException If connection fails
     */
    public function ttl(string $key): int;
}
