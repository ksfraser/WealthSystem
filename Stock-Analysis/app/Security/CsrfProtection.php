<?php

namespace App\Security;

/**
 * CSRF Protection
 * 
 * Provides Cross-Site Request Forgery protection for all forms and AJAX requests.
 */
class CsrfProtection
{
    private const TOKEN_NAME = 'csrf_token';
    private const TOKEN_TIME_NAME = 'csrf_token_time';
    private const TOKEN_LIFETIME = 3600; // 1 hour
    
    /**
     * Generate a new CSRF token
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate cryptographically secure token
        $token = bin2hex(random_bytes(32));
        
        // Store in session with timestamp
        $_SESSION[self::TOKEN_NAME] = $token;
        $_SESSION[self::TOKEN_TIME_NAME] = time();
        
        return $token;
    }
    
    /**
     * Get current CSRF token (generate if doesn't exist)
     */
    public static function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists and is still valid
        if (!isset($_SESSION[self::TOKEN_NAME]) || !self::isTokenValid()) {
            return self::generateToken();
        }
        
        return $_SESSION[self::TOKEN_NAME];
    }
    
    /**
     * Validate CSRF token from request
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if token exists in session
        if (!isset($_SESSION[self::TOKEN_NAME])) {
            return false;
        }
        
        // Check if token has expired
        if (!self::isTokenValid()) {
            self::destroyToken();
            return false;
        }
        
        // Timing-safe comparison
        if (!hash_equals($_SESSION[self::TOKEN_NAME], $token ?? '')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate token from POST request
     */
    public static function validatePostToken(): bool
    {
        $token = $_POST[self::TOKEN_NAME] ?? null;
        return self::validateToken($token);
    }
    
    /**
     * Validate token from HTTP header (for AJAX)
     */
    public static function validateHeaderToken(): bool
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return self::validateToken($token);
    }
    
    /**
     * Validate token from either POST or header
     */
    public static function validateRequest(): bool
    {
        // Try POST first, then header (for AJAX)
        return self::validatePostToken() || self::validateHeaderToken();
    }
    
    /**
     * Check if current token is still valid (not expired)
     */
    private static function isTokenValid(): bool
    {
        if (!isset($_SESSION[self::TOKEN_TIME_NAME])) {
            return false;
        }
        
        $tokenAge = time() - $_SESSION[self::TOKEN_TIME_NAME];
        return $tokenAge < self::TOKEN_LIFETIME;
    }
    
    /**
     * Destroy current token
     */
    public static function destroyToken(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION[self::TOKEN_NAME]);
        unset($_SESSION[self::TOKEN_TIME_NAME]);
    }
    
    /**
     * Get HTML hidden input field for forms
     */
    public static function getFormField(): string
    {
        $token = self::getToken();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars(self::TOKEN_NAME, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Get token name (for JavaScript/AJAX)
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }
    
    /**
     * Verify request or throw exception
     * 
     * @throws \RuntimeException if validation fails
     */
    public static function verifyRequest(): void
    {
        if (!self::validateRequest()) {
            http_response_code(403);
            throw new \RuntimeException('CSRF token validation failed', 403);
        }
    }
    
    /**
     * Verify POST request or throw exception
     * 
     * @throws \RuntimeException if validation fails
     */
    public static function verifyPostRequest(): void
    {
        if (!self::validatePostToken()) {
            http_response_code(403);
            throw new \RuntimeException('CSRF token validation failed', 403);
        }
    }
    
    /**
     * Get JavaScript code to include token in AJAX requests
     */
    public static function getAjaxScript(): string
    {
        $token = self::getToken();
        $tokenName = self::TOKEN_NAME;
        
        return <<<JS
<script>
// Add CSRF token to all AJAX requests
(function() {
    const token = '{$token}';
    const tokenName = '{$tokenName}';
    
    // For fetch API
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        options.headers = options.headers || {};
        options.headers['X-CSRF-Token'] = token;
        return originalFetch(url, options);
    };
    
    // For XMLHttpRequest
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        originalOpen.apply(this, arguments);
        this.setRequestHeader('X-CSRF-Token', token);
    };
    
    // For jQuery if available
    if (typeof jQuery !== 'undefined') {
        jQuery.ajaxSetup({
            headers: { 'X-CSRF-Token': token }
        });
    }
})();
</script>
JS;
    }
}
