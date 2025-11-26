<?php

namespace Ksfraser\Auth;

use Exception;

/**
 * Authentication Utilities
 * 
 * Helper functions and utilities for authentication system
 * 
 * @package Ksfraser\Auth
 */
class AuthUtils
{
    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Generate URL-safe token
     */
    public static function generateUrlSafeToken(int $length = 32): string
    {
        return rtrim(strtr(base64_encode(random_bytes($length)), '+/', '-_'), '=');
    }
    
    /**
     * Hash password with secure defaults
     */
    public static function hashPassword(string $password, array $options = []): string
    {
        $options = array_merge(['cost' => 12], $options);
        return password_hash($password, PASSWORD_DEFAULT, $options);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function needsRehash(string $hash, array $options = []): bool
    {
        $options = array_merge(['cost' => 12], $options);
        return password_needs_rehash($hash, PASSWORD_DEFAULT, $options);
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate username format
     */
    public static function validateUsername(string $username, array $options = []): bool
    {
        $options = array_merge([
            'min_length' => 3,
            'max_length' => 64,
            'allow_numbers' => true,
            'allow_underscore' => true,
            'allow_dash' => true,
            'require_letter' => true
        ], $options);
        
        $length = strlen($username);
        if ($length < $options['min_length'] || $length > $options['max_length']) {
            return false;
        }
        
        // Build regex pattern
        $pattern = '/^[a-zA-Z';
        if ($options['allow_numbers']) $pattern .= '0-9';
        if ($options['allow_underscore']) $pattern .= '_';
        if ($options['allow_dash']) $pattern .= '-';
        $pattern .= ']+$/';
        
        if (!preg_match($pattern, $username)) {
            return false;
        }
        
        // Require at least one letter
        if ($options['require_letter'] && !preg_match('/[a-zA-Z]/', $username)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePasswordStrength(string $password, array $options = []): array
    {
        $options = array_merge([
            'min_length' => 8,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'forbidden_sequences' => ['123', 'abc', 'qwerty', 'password'],
            'max_repeating' => 3
        ], $options);
        
        $errors = [];
        $score = 0;
        
        // Length check
        $length = strlen($password);
        if ($length < $options['min_length']) {
            $errors[] = "Password must be at least {$options['min_length']} characters long";
        } elseif ($length >= $options['min_length']) {
            $score += min($length - $options['min_length'] + 1, 5);
        }
        
        if ($length > $options['max_length']) {
            $errors[] = "Password must not exceed {$options['max_length']} characters";
        }
        
        // Character type checks
        if ($options['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        } else {
            $score += 2;
        }
        
        if ($options['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        } else {
            $score += 2;
        }
        
        if ($options['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        } else {
            $score += 2;
        }
        
        if ($options['require_symbols'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        } else {
            $score += 3;
        }
        
        // Check for forbidden sequences
        $passwordLower = strtolower($password);
        foreach ($options['forbidden_sequences'] as $sequence) {
            if (strpos($passwordLower, strtolower($sequence)) !== false) {
                $errors[] = "Password cannot contain common sequences like '{$sequence}'";
                $score -= 2;
            }
        }
        
        // Check for repeating characters
        if ($options['max_repeating'] > 0) {
            $pattern = '/(.)\1{' . ($options['max_repeating'] - 1) . ',}/';
            if (preg_match($pattern, $password)) {
                $errors[] = "Password cannot have more than {$options['max_repeating']} repeating characters";
                $score -= 2;
            }
        }
        
        // Calculate strength
        $score = max(0, min(100, $score * 7)); // Scale to 0-100
        
        $strength = 'Very Weak';
        if ($score >= 80) $strength = 'Very Strong';
        elseif ($score >= 60) $strength = 'Strong';
        elseif ($score >= 40) $strength = 'Moderate';
        elseif ($score >= 20) $strength = 'Weak';
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'score' => $score,
            'strength' => $strength
        ];
    }
    
    /**
     * Sanitize user input
     */
    public static function sanitizeInput(string $input, array $options = []): string
    {
        $options = array_merge([
            'trim' => true,
            'strip_tags' => true,
            'html_entities' => true,
            'max_length' => null
        ], $options);
        
        if ($options['trim']) {
            $input = trim($input);
        }
        
        if ($options['strip_tags']) {
            $input = strip_tags($input);
        }
        
        if ($options['html_entities']) {
            $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        }
        
        if ($options['max_length'] && strlen($input) > $options['max_length']) {
            $input = substr($input, 0, $options['max_length']);
        }
        
        return $input;
    }
    
    /**
     * Generate JWT payload
     */
    public static function createJWTPayload(int $userId, array $data = [], int $expiresIn = 3600): array
    {
        $now = time();
        
        return array_merge([
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost', // Issuer
            'aud' => $_SERVER['HTTP_HOST'] ?? 'localhost', // Audience
            'iat' => $now,                                  // Issued at
            'nbf' => $now,                                  // Not before
            'exp' => $now + $expiresIn,                     // Expires
            'sub' => $userId                                // Subject (user ID)
        ], $data);
    }
    
    /**
     * Check if request is AJAX
     */
    public static function isAjaxRequest(): bool
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get client IP address with proxy support
     */
    public static function getClientIP(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Generate secure session ID
     */
    public static function generateSessionId(): string
    {
        return session_create_id();
    }
    
    /**
     * Create secure cookie parameters
     */
    public static function getSecureCookieParams(): array
    {
        return [
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ];
    }
    
    /**
     * Time constant string comparison
     */
    public static function hashEquals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }
    
    /**
     * Generate password reset token with expiry
     */
    public static function generatePasswordResetToken(): array
    {
        return [
            'token' => self::generateToken(32),
            'expires_at' => date('Y-m-d H:i:s', time() + 3600) // 1 hour
        ];
    }
    
    /**
     * Generate email verification token with expiry
     */
    public static function generateEmailVerificationToken(): array
    {
        return [
            'token' => self::generateToken(32),
            'expires_at' => date('Y-m-d H:i:s', time() + 86400) // 24 hours
        ];
    }
    
    /**
     * Format user data for safe output
     */
    public static function formatUserData(array $user, bool $includePrivate = false): array
    {
        $publicFields = ['id', 'username', 'email', 'is_admin', 'created_at', 'last_login'];
        $privateFields = ['email_verified', 'is_active', 'login_attempts', 'locked_until'];
        
        $fields = $includePrivate ? array_merge($publicFields, $privateFields) : $publicFields;
        
        $result = [];
        foreach ($fields as $field) {
            if (isset($user[$field])) {
                $result[$field] = $user[$field];
            }
        }
        
        // Convert boolean flags
        if (isset($result['is_admin'])) {
            $result['is_admin'] = (bool)$result['is_admin'];
        }
        if (isset($result['is_active'])) {
            $result['is_active'] = (bool)$result['is_active'];
        }
        if (isset($result['email_verified'])) {
            $result['email_verified'] = (bool)$result['email_verified'];
        }
        
        return $result;
    }
    
    /**
     * Create pagination data
     */
    public static function createPagination(int $currentPage, int $totalItems, int $itemsPerPage = 10): array
    {
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        return [
            'current_page' => $currentPage,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null
        ];
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        try {
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'event' => $event,
                'ip' => self::getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'data' => $data
            ];
            
            $logFile = __DIR__ . '/../../../logs/security.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logLine = json_encode($logData) . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log("Security logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Validate configuration array
     */
    public static function validateConfig(array $config, array $required = [], array $defaults = []): array
    {
        // Check required fields
        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new Exception("Required configuration field missing: {$field}");
            }
        }
        
        // Merge with defaults
        return array_merge($defaults, $config);
    }
    
    /**
     * Create safe redirect URL
     */
    public static function createSafeRedirectUrl(string $url, string $defaultUrl = '/'): string
    {
        // Parse URL
        $parsed = parse_url($url);
        
        // Only allow relative URLs or same-host URLs
        if (isset($parsed['host'])) {
            $currentHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
            if ($parsed['host'] !== $currentHost) {
                return $defaultUrl;
            }
        }
        
        // Remove dangerous schemes
        if (isset($parsed['scheme']) && !in_array($parsed['scheme'], ['http', 'https'])) {
            return $defaultUrl;
        }
        
        // Return sanitized URL
        return filter_var($url, FILTER_SANITIZE_URL) ?: $defaultUrl;
    }
}
