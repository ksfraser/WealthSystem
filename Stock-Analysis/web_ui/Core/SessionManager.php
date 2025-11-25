<?php

namespace App\Core;

/**
 * Modern Session Manager using namespaces and dependency injection
 */
class SessionManager
{
    /** @var SessionManager|null */
    private static $instance = null;
    
    /** @var string|null */
    private $initializationError = null;
    
    /** @var bool */
    private $sessionActive = false;

    private function __construct()
    {
        $this->initializeSession();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function initializeSession(): void
    {
        // Skip if CLI or session already active
        if (php_sapi_name() === 'cli') {
            $this->sessionActive = false;
            return;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $this->sessionActive = true;
            return;
        }

        // Check if headers already sent
        if (headers_sent($file, $line)) {
            $this->initializationError = "Headers already sent at $file:$line";
            return;
        }

        try {
            // Ensure session save path exists
            $this->ensureSessionPath();
            
            // Start session
            if (session_start()) {
                $this->sessionActive = true;
            } else {
                $this->initializationError = 'Failed to start session';
            }
        } catch (\Exception $e) {
            $this->initializationError = 'Session start exception: ' . $e->getMessage();
        }
    }

    private function ensureSessionPath(): void
    {
        $savePath = session_save_path();
        
        if (empty($savePath)) {
            $savePath = sys_get_temp_dir();
            session_save_path($savePath);
        }

        if (!is_dir($savePath)) {
            $this->createSessionPath($savePath);
        }
    }

    private function createSessionPath(string $path): void
    {
        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            throw new \RuntimeException("Cannot create session directory: $path");
        }
    }

    public function isSessionActive(): bool
    {
        return $this->sessionActive;
    }

    public function getInitializationError(): ?string
    {
        return $this->initializationError;
    }

    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, $value): void
    {
        if ($this->sessionActive) {
            $_SESSION[$key] = $value;
        }
    }

    public function destroy(): bool
    {
        if ($this->sessionActive) {
            session_destroy();
            $this->sessionActive = false;
            return true;
        }
        return false;
    }
}
