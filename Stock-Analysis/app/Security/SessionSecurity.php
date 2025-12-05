<?php

declare(strict_types=1);

namespace App\Security;

/**
 * Session Security Manager
 * 
 * Provides comprehensive session security including:
 * - Secure session configuration
 * - Session hijacking prevention
 * - Session fixation prevention
 * - Session timeout management
 * - Fingerprinting
 * 
 * Follows SOLID principles:
 * - Single Responsibility: Session security only
 * - Open/Closed: Extensible through configuration
 * 
 * @package App\Security
 */
class SessionSecurity
{
    private const DEFAULT_TIMEOUT = 3600; // 1 hour
    private const SESSION_NAME = 'WEALTHSYS_SESSID';

    private array $config;

    /**
     * Constructor
     * 
     * @param array $config Configuration options
     *                     - timeout: Session timeout in seconds (default: 3600)
     *                     - check_ip: Check IP address for hijacking (default: false)
     *                     - regenerate_interval: Regenerate ID interval in seconds (default: 300)
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'timeout' => self::DEFAULT_TIMEOUT,
            'check_ip' => false,
            'regenerate_interval' => 300,
        ], $config);
    }

    /**
     * Initialize secure session
     * 
     * Sets secure cookie parameters and starts session with security hardening.
     * 
     * @return void
     */
    public function initialize(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Set session name (avoid default PHPSESSID)
        session_name(self::SESSION_NAME);

        // Configure session parameters
        $this->configureSession();

        // Set secure cookie parameters
        $this->setSecureCookieParams();

        // Start session
        session_start();

        // Initialize session fingerprint on first access
        if (!isset($_SESSION['_created_at'])) {
            $this->initializeFingerprint();
        } else {
            // Validate existing session
            if (!$this->validate()) {
                $this->destroy();
                $this->initialize(); // Start fresh
            }
        }
    }

    /**
     * Configure PHP session settings
     * 
     * @return void
     */
    private function configureSession(): void
    {
        // Set garbage collection lifetime
        ini_set('session.gc_maxlifetime', (string) $this->config['timeout']);
        
        // Use strict session ID mode
        ini_set('session.use_strict_mode', '1');
        
        // Use cookies only (no URL rewriting)
        ini_set('session.use_only_cookies', '1');
        
        // Use cookies for session ID
        ini_set('session.use_cookies', '1');
        
        // Prevent JavaScript access to session cookie
        ini_set('session.cookie_httponly', '1');
    }

    /**
     * Set secure cookie parameters
     * 
     * @return void
     */
    private function setSecureCookieParams(): void
    {
        $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        
        session_set_cookie_params([
            'lifetime' => 0, // Session cookie (deleted on browser close)
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => $isHttps, // Only HTTPS in production
            'httponly' => true,
            'samesite' => 'Strict', // CSRF protection
        ]);
    }

    /**
     * Initialize session fingerprint
     * 
     * @return void
     */
    private function initializeFingerprint(): void
    {
        $_SESSION['_user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['_created_at'] = time();
        $_SESSION['_last_activity'] = time();
        $_SESSION['_last_regeneration'] = time();
    }

    /**
     * Validate session
     * 
     * Checks for:
     * - Session timeout
     * - User agent mismatch (hijacking)
     * - IP address mismatch (hijacking, if enabled)
     * 
     * @return bool True if valid
     */
    public function validate(): bool
    {
        // Check if session is initialized
        if (!isset($_SESSION['_created_at'])) {
            return false;
        }

        // Check timeout
        if (isset($_SESSION['_last_activity'])) {
            $inactive = time() - $_SESSION['_last_activity'];
            
            if ($inactive > $this->config['timeout']) {
                return false;
            }
        }

        // Check user agent (hijacking prevention)
        if (isset($_SESSION['_user_agent'])) {
            $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if ($_SESSION['_user_agent'] !== $currentUserAgent) {
                return false;
            }
        }

        // Check IP address (optional, strict hijacking prevention)
        if ($this->config['check_ip'] && isset($_SESSION['_ip_address'])) {
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
            
            if ($_SESSION['_ip_address'] !== $currentIp) {
                return false;
            }
        }

        // Regenerate ID periodically
        if (isset($_SESSION['_last_regeneration'])) {
            $timeSinceRegeneration = time() - $_SESSION['_last_regeneration'];
            
            if ($timeSinceRegeneration > $this->config['regenerate_interval']) {
                $this->regenerate();
            }
        }

        return true;
    }

    /**
     * Regenerate session ID
     * 
     * Prevents session fixation attacks.
     * 
     * @param bool $deleteOldSession Delete old session data
     * @return void
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id($deleteOldSession);
            $_SESSION['_last_regeneration'] = time();
        }
    }

    /**
     * Regenerate session ID on privilege change
     * 
     * Should be called after login, role change, or any privilege escalation.
     * 
     * @return void
     */
    public function regenerateOnPrivilegeChange(): void
    {
        $this->regenerate(true);
    }

    /**
     * Update last activity timestamp
     * 
     * Call this on each request to maintain session activity.
     * 
     * @return void
     */
    public function updateActivity(): void
    {
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Destroy session
     * 
     * Completely destroys session and clears all data.
     * 
     * @return void
     */
    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Clear session variables
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
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

            // Destroy session
            session_destroy();
        }
    }

    /**
     * Get session configuration
     * 
     * @return array Configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
