<?php

namespace App\Security;

/**
 * Session Manager
 * 
 * Provides secure session management with proper configuration.
 */
class SessionManager
{
    private static bool $initialized = false;
    
    /**
     * Initialize secure session
     */
    public static function start(): void
    {
        if (self::$initialized || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        
        // Secure session configuration
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_lifetime', '0'); // Session cookie
        ini_set('session.gc_maxlifetime', '3600'); // 1 hour
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        
        // Start session
        session_start();
        
        // Regenerate session ID periodically
        self::regenerateIfNeeded();
        
        self::$initialized = true;
    }
    
    /**
     * Regenerate session ID to prevent fixation attacks
     */
    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        session_regenerate_id(true);
        $_SESSION['_regenerated_at'] = time();
    }
    
    /**
     * Regenerate session ID if needed (every 30 minutes)
     */
    private static function regenerateIfNeeded(): void
    {
        if (!isset($_SESSION['_regenerated_at'])) {
            self::regenerate();
            return;
        }
        
        $age = time() - $_SESSION['_regenerated_at'];
        if ($age > 1800) { // 30 minutes
            self::regenerate();
        }
    }
    
    /**
     * Destroy session completely
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            // Delete session cookie
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
        }
        
        self::$initialized = false;
    }
    
    /**
     * Set session value
     */
    public static function set(string $key, $value): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        $_SESSION[$key] = $value;
    }
    
    /**
     * Get session value
     */
    public static function get(string $key, $default = null)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Check if session has key
     */
    public static function has(string $key): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public static function remove(string $key): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::start();
        }
        
        unset($_SESSION[$key]);
    }
    
    /**
     * Flash message - set value that will be removed after next read
     */
    public static function flash(string $key, $value): void
    {
        self::set("_flash_{$key}", $value);
    }
    
    /**
     * Get flash message (auto-removes after reading)
     */
    public static function getFlash(string $key, $default = null)
    {
        $flashKey = "_flash_{$key}";
        $value = self::get($flashKey, $default);
        self::remove($flashKey);
        return $value;
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::has('user_id') && self::has('authenticated');
    }
    
    /**
     * Set user authentication
     */
    public static function setAuthenticated(int $userId, array $userData = []): void
    {
        self::regenerate(); // Prevent session fixation
        self::set('user_id', $userId);
        self::set('authenticated', true);
        self::set('user_data', $userData);
        self::set('auth_time', time());
    }
    
    /**
     * Clear authentication
     */
    public static function clearAuthentication(): void
    {
        self::remove('user_id');
        self::remove('authenticated');
        self::remove('user_data');
        self::remove('auth_time');
    }
    
    /**
     * Get authenticated user ID
     */
    public static function getUserId(): ?int
    {
        return self::isAuthenticated() ? self::get('user_id') : null;
    }
    
    /**
     * Get user data
     */
    public static function getUserData(): ?array
    {
        return self::isAuthenticated() ? self::get('user_data') : null;
    }
    
    /**
     * Check if session has timed out
     */
    public static function hasTimedOut(int $maxLifetime = 3600): bool
    {
        if (!self::has('auth_time')) {
            return false;
        }
        
        $age = time() - self::get('auth_time');
        return $age > $maxLifetime;
    }
}
