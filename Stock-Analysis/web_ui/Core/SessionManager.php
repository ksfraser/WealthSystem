<?php

namespace App\Core;

// Load the canonical SessionManager from global namespace
require_once __DIR__ . '/../SessionManager.php';

/**
 * Namespaced wrapper for the global SessionManager
 * This provides compatibility for code expecting App\Core\SessionManager
 * while using the single canonical SessionManager implementation
 */
class SessionManager
{
    /** @var \SessionManager|null */
    private static $globalInstance = null;

    /**
     * Get the global SessionManager instance
     */
    public static function getInstance(): \SessionManager
    {
        if (self::$globalInstance === null) {
            self::$globalInstance = \SessionManager::getInstance();
        }
        return self::$globalInstance;
    }
    
    /**
     * Check if session is active
     */
    public function isSessionActive(): bool
    {
        return self::getInstance()->isSessionActive();
    }
    
    /**
     * Get initialization error if any
     */
    public function getInitializationError(): ?string
    {
        return self::getInstance()->getInitializationError();
    }
    
    /**
     * Set session value
     */
    public function set(string $key, $value): bool
    {
        return self::getInstance()->set($key, $value);
    }
    
    /**
     * Get session value
     */
    public function get(string $key, $default = null)
    {
        return self::getInstance()->get($key, $default);
    }
    
    /**
     * Check if session key exists
     */
    public function has(string $key): bool
    {
        return self::getInstance()->has($key);
    }
    
    /**
     * Remove session key
     */
    public function remove(string $key): bool
    {
        return self::getInstance()->remove($key);
    }
    
    /**
     * Destroy session
     */
    public function destroy(): void
    {
        self::getInstance()->destroy();
    }
}
