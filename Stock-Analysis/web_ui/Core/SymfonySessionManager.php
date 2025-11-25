<?php
/**
 * Symfony Session Wrapper
 * Replaces our custom SessionManager with Symfony's battle-tested Session component
 * Maintains the same interface for easy migration
 */

namespace App\Core;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionManager 
{
    private static $instance = null;
    private $session;
    
    /** @var string|null */
    private $initializationError = null;

    private function __construct() 
    {
        $this->initializeSymfonySession();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeSymfonySession(): void
    {
        try {
            // Configure Symfony session storage with secure defaults
            $storage = new NativeSessionStorage([
                'cookie_lifetime' => 3600,      // 1 hour
                'cookie_secure' => false,       // Set to true in production with HTTPS
                'cookie_httponly' => true,
                'cookie_samesite' => 'lax',
                'use_strict_mode' => true,
                'sid_length' => 48,
                'sid_bits_per_character' => 6,
            ]);

            $this->session = new Session($storage);
            
            // Start session safely - Symfony handles headers_sent automatically
            if (!$this->session->isStarted() && php_sapi_name() !== 'cli') {
                $this->session->start();
            }
            
        } catch (\Exception $e) {
            $this->initializationError = 'Symfony session initialization failed: ' . $e->getMessage();
            error_log($this->initializationError);
        }
    }

    /**
     * Check if session is active
     * @return bool
     */
    public function isSessionActive(): bool
    {
        return $this->session && $this->session->isStarted();
    }

    /**
     * Get initialization error if any
     * @return string|null
     */
    public function getInitializationError(): ?string
    {
        return $this->initializationError;
    }

    /**
     * Get session value
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (!$this->session) {
            return $default;
        }
        return $this->session->get($key, $default);
    }

    /**
     * Set session value
     * @param string $key
     * @param mixed $value
     */
    public function set(string $key, $value): void
    {
        if ($this->session && $this->session->isStarted()) {
            $this->session->set($key, $value);
        }
    }

    /**
     * Remove session value
     * @param string $key
     */
    public function remove(string $key): void
    {
        if ($this->session && $this->session->isStarted()) {
            $this->session->remove($key);
        }
    }

    /**
     * Check if session has key
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->session) {
            return false;
        }
        return $this->session->has($key);
    }

    /**
     * Destroy session
     * @return bool
     */
    public function destroy(): bool
    {
        if ($this->session && $this->session->isStarted()) {
            $this->session->invalidate();
            return true;
        }
        return false;
    }

    /**
     * Get session ID
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->session ? $this->session->getId() : null;
    }

    /**
     * Get session name
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->session ? $this->session->getName() : null;
    }

    /**
     * Add flash message (bonus feature from Symfony!)
     * @param string $type
     * @param string $message
     */
    public function addFlash(string $type, string $message): void
    {
        if ($this->session && $this->session->isStarted()) {
            $this->session->getFlashBag()->add($type, $message);
        }
    }

    /**
     * Get flash messages (bonus feature from Symfony!)
     * @param string $type
     * @return array
     */
    public function getFlashes(string $type): array
    {
        if (!$this->session || !$this->session->isStarted()) {
            return [];
        }
        return $this->session->getFlashBag()->get($type);
    }

    /**
     * Get all flash messages
     * @return array
     */
    public function getAllFlashes(): array
    {
        if (!$this->session || !$this->session->isStarted()) {
            return [];
        }
        return $this->session->getFlashBag()->all();
    }

    // Legacy compatibility methods for existing code
    
    /**
     * Legacy method - use get() instead
     * @deprecated
     */
    public function getSessionValue(string $key, $default = null)
    {
        return $this->get($key, $default);
    }

    /**
     * Legacy method - use set() instead  
     * @deprecated
     */
    public function setSessionValue(string $key, $value): void
    {
        $this->set($key, $value);
    }
}
