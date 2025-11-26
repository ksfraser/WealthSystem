<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

/**
 * Session Management Service using Symfony Session Component
 * 
 * Provides secure session handling with session fixation prevention,
 * user authentication tracking, and proper session lifecycle management.
 * Implements singleton pattern for consistent session access.
 * 
 * @package App\Security
 */
class SessionService
{
    /**
     * @var Session|null Singleton session instance
     */
    private static ?Session $session = null;
    
    /**
     * @var string Key for storing user authentication status
     */
    private const AUTH_KEY = '_authenticated';
    
    /**
     * @var string Key for storing user ID
     */
    private const USER_ID_KEY = '_user_id';
    
    /**
     * @var string Key for storing user data
     */
    private const USER_DATA_KEY = '_user_data';
    
    /**
     * @var string Key for storing authentication time
     */
    private const AUTH_TIME_KEY = 'auth_time';
    
    /**
     * @var string Key for storing session regeneration time
     */
    private const REGENERATED_AT_KEY = '_regenerated_at';
    
    /**
     * Start or get existing session
     * 
     * Creates a new session if none exists, or returns the existing session.
     * Uses Symfony's Session component for secure session management.
     * 
     * @param bool|null $testMode Use mock storage for testing (default: auto-detect)
     * @return Session The active session instance
     * 
     * @example
     * ```php
     * $session = SessionService::start();
     * $session->set('key', 'value');
     * ```
     */
    public static function start(?bool $testMode = null): Session
    {
        if (self::$session === null) {
            // Auto-detect test mode if not explicitly set
            if ($testMode === null) {
                $testMode = defined('PHPUNIT_COMPOSER_INSTALL') || 
                           (function_exists('class_exists') && class_exists('\PHPUnit\Framework\TestCase', false));
            }
            
            // Use mock storage in test mode, native storage otherwise
            $storage = $testMode 
                ? new MockArraySessionStorage() 
                : new NativeSessionStorage([
                    'cookie_secure' => true, // HTTPS only
                    'cookie_httponly' => true, // No JavaScript access
                    'cookie_samesite' => 'Lax', // CSRF protection
                    'use_strict_mode' => true, // Reject uninitialized session IDs
                    'sid_length' => 48, // Longer session IDs
                    'sid_bits_per_character' => 6,
                ]);
            
            self::$session = new Session($storage);
            
            if (!self::$session->isStarted()) {
                self::$session->start();
                // Track when session was created/regenerated
                self::$session->set(self::REGENERATED_AT_KEY, time());
            }
        }
        
        return self::$session;
    }
    
    /**
     * Get the active session (starts one if needed)
     * 
     * Convenience method that ensures a session exists.
     * 
     * @param bool|null $testMode Use mock storage for testing (default: auto-detect)
     * @return Session The active session instance
     * 
     * @example
     * ```php
     * $value = SessionService::get()->get('key');
     * ```
     */
    public static function get(?bool $testMode = null): Session
    {
        return self::start($testMode);
    }
    
    /**
     * Check if user is authenticated
     * 
     * @return bool True if user is authenticated, false otherwise
     * 
     * @example
     * ```php
     * if (SessionService::isAuthenticated()) {
     *     // Show user dashboard
     * }
     * ```
     */
    public static function isAuthenticated(): bool
    {
        return self::get()->get(self::AUTH_KEY, false) === true;
    }
    
    /**
     * Set user as authenticated
     * 
     * Stores user authentication status and regenerates session ID
     * to prevent session fixation attacks.
     * 
     * @param int $userId The user's ID
     * @param array<string, mixed> $userData Additional user data to store
     * 
     * @example
     * ```php
     * SessionService::setAuthenticated(123, [
     *     'name' => 'John Doe',
     *     'email' => 'john@example.com',
     *     'role' => 'admin'
     * ]);
     * ```
     */
    public static function setAuthenticated(int $userId, array $userData = []): void
    {
        $session = self::get();
        
        // Regenerate session ID to prevent session fixation
        $session->migrate(true);
        
        // Track regeneration time
        $session->set(self::REGENERATED_AT_KEY, time());
        
        $session->set(self::AUTH_KEY, true);
        $session->set(self::USER_ID_KEY, $userId);
        $session->set(self::USER_DATA_KEY, $userData);
        $session->set(self::AUTH_TIME_KEY, time());
    }
    
    /**
     * Get authenticated user's ID
     * 
     * @return int|null User ID if authenticated, null otherwise
     * 
     * @example
     * ```php
     * $userId = SessionService::getUserId();
     * if ($userId) {
     *     $user = $userRepository->find($userId);
     * }
     * ```
     */
    public static function getUserId(): ?int
    {
        return self::isAuthenticated() 
            ? self::get()->get(self::USER_ID_KEY) 
            : null;
    }
    
    /**
     * Get authenticated user's data
     * 
     * @return array<string, mixed> User data if authenticated, empty array otherwise
     * 
     * @example
     * ```php
     * $userData = SessionService::getUserData();
     * echo "Welcome, " . ($userData['name'] ?? 'Guest');
     * ```
     */
    public static function getUserData(): array
    {
        return self::isAuthenticated() 
            ? self::get()->get(self::USER_DATA_KEY, []) 
            : [];
    }
    
    /**
     * Logout user
     * 
     * Clears ALL session data and regenerates session ID.
     * Session remains active but user is no longer authenticated.
     * 
     * @example
     * ```php
     * SessionService::logout();
     * header('Location: /login');
     * ```
     */
    public static function logout(): void
    {
        $session = self::get();
        
        // Clear ALL session data
        $session->clear();
        
        // Regenerate session ID for security
        $session->migrate(true);
        
        // Track regeneration time
        $session->set(self::REGENERATED_AT_KEY, time());
    }
    
    /**
     * Destroy session completely
     * 
     * Invalidates the session and clears all data.
     * Use this when user logs out or session expires.
     * 
     * @example
     * ```php
     * SessionService::destroy();
     * ```
     */
    public static function destroy(): void
    {
        $session = self::get();
        $session->invalidate();
        self::$session = null;
    }
    
    /**
     * Reset singleton instance (for testing only)
     * 
     * @internal This method should only be used in tests
     */
    public static function reset(): void
    {
        if (self::$session !== null && self::$session->isStarted()) {
            self::$session->clear();
        }
        self::$session = null;
    }
    
    /**
     * Set a flash message
     * 
     * Flash messages are shown once then cleared automatically.
     * 
     * @param string $type Message type (success, error, warning, info)
     * @param string $message The message text
     * 
     * @example
     * ```php
     * SessionService::setFlash('success', 'Profile updated successfully');
     * ```
     */
    public static function setFlash(string $type, string $message): void
    {
        self::get()->getFlashBag()->add($type, $message);
    }
    
    /**
     * Get flash messages of a specific type
     * 
     * @param string $type Message type to retrieve
     * @return array<string> Array of flash messages
     * 
     * @example
     * ```php
     * foreach (SessionService::getFlashes('error') as $error) {
     *     echo "<div class='error'>$error</div>";
     * }
     * ```
     */
    public static function getFlashes(string $type): array
    {
        return self::get()->getFlashBag()->get($type);
    }
    
    /**
     * Check if user has a specific role
     * 
     * @param string $role Role to check
     * @return bool True if user has the role
     * 
     * @example
     * ```php
     * if (SessionService::hasRole('admin')) {
     *     // Show admin panel
     * }
     * ```
     */
    public static function hasRole(string $role): bool
    {
        if (!self::isAuthenticated()) {
            return false;
        }
        
        $userData = self::getUserData();
        $roles = $userData['roles'] ?? [$userData['role'] ?? ''];
        
        return in_array($role, $roles, true);
    }
    
    /**
     * Require authentication (redirect if not authenticated)
     * 
     * @param string $redirectUrl URL to redirect to if not authenticated
     * 
     * @example
     * ```php
     * SessionService::requireAuth('/login.php');
     * // Code here only executes if authenticated
     * ```
     */
    public static function requireAuth(string $redirectUrl = '/login.php'): void
    {
        if (!self::isAuthenticated()) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
    
    /**
     * Require specific role (redirect if not authorized)
     * 
     * @param string $role Required role
     * @param string $redirectUrl URL to redirect to if not authorized
     * 
     * @example
     * ```php
     * SessionService::requireRole('admin', '/dashboard.php');
     * // Code here only executes if user has admin role
     * ```
     */
    public static function requireRole(string $role, string $redirectUrl = '/dashboard.php'): void
    {
        self::requireAuth();
        
        if (!self::hasRole($role)) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}
