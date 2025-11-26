<?php

namespace Ksfraser\Auth;

use Exception;

/**
 * Authentication Middleware
 * 
 * Provides middleware functionality for authentication and authorization
 * 
 * @package Ksfraser\Auth
 */
class AuthMiddleware
{
    private $authManager;
    private $config;
    
    public function __construct(AuthManager $authManager, array $config = [])
    {
        $this->authManager = $authManager;
        $this->config = array_merge([
            'login_url' => '/login.php',
            'unauthorized_url' => '/unauthorized.php',
            'json_responses' => false,
            'excluded_paths' => ['/login.php', '/register.php', '/logout.php'],
            'public_paths' => ['/public/', '/css/', '/js/', '/images/'],
            'admin_paths' => ['/admin/'],
            'csrf_protection' => true,
            'rate_limiting' => false,
            'audit_logging' => true
        ], $config);
    }
    
    /**
     * Handle authentication requirement
     */
    public function requireAuth(callable $next = null): void
    {
        try {
            $this->authManager->requireLogin();
            
            if ($this->config['audit_logging']) {
                $this->logAccess('auth_required', ['success' => true]);
            }
            
            if ($next) {
                $next();
            }
        } catch (Exception $e) {
            if ($this->config['audit_logging']) {
                $this->logAccess('auth_required', ['success' => false, 'error' => $e->getMessage()]);
            }
            
            $this->handleUnauthorized('Authentication required: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle admin requirement
     */
    public function requireAdmin(callable $next = null): void
    {
        try {
            $this->authManager->requireAdmin();
            
            if ($this->config['audit_logging']) {
                $this->logAccess('admin_required', ['success' => true]);
            }
            
            if ($next) {
                $next();
            }
        } catch (Exception $e) {
            if ($this->config['audit_logging']) {
                $this->logAccess('admin_required', ['success' => false, 'error' => $e->getMessage()]);
            }
            
            $this->handleUnauthorized('Admin access required: ' . $e->getMessage());
        }
    }
    
    /**
     * Handle CSRF protection
     */
    public function requireCSRF(callable $next = null): void
    {
        if (!$this->config['csrf_protection']) {
            if ($next) $next();
            return;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if ($next) $next();
            return;
        }
        
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!$this->authManager->validateCSRFToken($token)) {
            if ($this->config['audit_logging']) {
                $this->logAccess('csrf_validation', ['success' => false, 'token' => substr($token, 0, 10) . '...']);
            }
            
            $this->handleUnauthorized('Invalid CSRF token');
        }
        
        if ($this->config['audit_logging']) {
            $this->logAccess('csrf_validation', ['success' => true]);
        }
        
        if ($next) {
            $next();
        }
    }
    
    /**
     * Route-based authentication middleware
     */
    public function routeGuard(string $requestUri = null): void
    {
        $requestUri = $requestUri ?? $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($requestUri, PHP_URL_PATH);
        
        // Skip public paths
        foreach ($this->config['public_paths'] as $publicPath) {
            if (strpos($path, $publicPath) === 0) {
                return;
            }
        }
        
        // Skip excluded paths
        if (in_array($path, $this->config['excluded_paths'])) {
            return;
        }
        
        // Check admin paths
        foreach ($this->config['admin_paths'] as $adminPath) {
            if (strpos($path, $adminPath) === 0) {
                $this->requireAdmin();
                return;
            }
        }
        
        // Default: require authentication
        $this->requireAuth();
    }
    
    /**
     * Process authentication for current request
     */
    public function processRequest(): void
    {
        // Rate limiting
        if ($this->config['rate_limiting']) {
            $this->checkRateLimit();
        }
        
        // Route-based guards
        $this->routeGuard();
        
        // CSRF protection for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireCSRF();
        }
    }
    
    /**
     * Handle unauthorized access
     */
    private function handleUnauthorized(string $message): void
    {
        if ($this->config['json_responses']) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized',
                'message' => $message,
                'login_url' => $this->config['login_url']
            ]);
            exit;
        }
        
        // HTML redirect
        if (!headers_sent()) {
            if ($this->authManager->isLoggedIn()) {
                // User is logged in but lacks permission
                header('Location: ' . $this->config['unauthorized_url']);
            } else {
                // User needs to log in
                $currentUrl = urlencode($_SERVER['REQUEST_URI'] ?? '/');
                $loginUrl = $this->config['login_url'];
                $separator = strpos($loginUrl, '?') !== false ? '&' : '?';
                header('Location: ' . $loginUrl . $separator . 'redirect=' . $currentUrl);
            }
            exit;
        }
        
        // Fallback if headers already sent
        echo '<script>window.location.href="' . htmlspecialchars($this->config['login_url']) . '";</script>';
        exit;
    }
    
    /**
     * Simple rate limiting check
     */
    private function checkRateLimit(): void
    {
        $ip = $this->getClientIP();
        $key = 'rate_limit_' . md5($ip);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $now = time();
        $window = 60; // 1 minute window
        $maxRequests = 60; // 60 requests per minute
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'window_start' => $now];
            return;
        }
        
        $data = $_SESSION[$key];
        
        // Reset window if expired
        if ($now - $data['window_start'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'window_start' => $now];
            return;
        }
        
        // Increment counter
        $_SESSION[$key]['count']++;
        
        // Check limit
        if ($_SESSION[$key]['count'] > $maxRequests) {
            if ($this->config['audit_logging']) {
                $this->logAccess('rate_limit_exceeded', ['ip' => $ip, 'requests' => $_SESSION[$key]['count']]);
            }
            
            http_response_code(429);
            if ($this->config['json_responses']) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Rate limit exceeded']);
            } else {
                echo 'Rate limit exceeded. Please try again later.';
            }
            exit;
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP(): string
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
     * Log access attempt
     */
    private function logAccess(string $action, array $details = []): void
    {
        try {
            // Simple file-based logging for now
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => $action,
                'user_id' => $this->authManager->getCurrentUserId(),
                'ip' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'details' => $details
            ];
            
            $logFile = __DIR__ . '/../../../logs/auth_access.log';
            $logDir = dirname($logFile);
            
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logLine = json_encode($logData) . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Silent fail for logging errors
            error_log("Auth logging failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create middleware instance with default configuration
     */
    public static function create(AuthManager $authManager, array $config = []): self
    {
        return new self($authManager, $config);
    }
    
    /**
     * Quick authentication check for API endpoints
     */
    public function apiAuth(): array
    {
        try {
            $this->authManager->requireLogin();
            return [
                'authenticated' => true,
                'user' => $this->authManager->getCurrentUser()
            ];
        } catch (Exception $e) {
            http_response_code(401);
            return [
                'authenticated' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Quick admin check for API endpoints
     */
    public function apiAdminAuth(): array
    {
        try {
            $this->authManager->requireAdmin();
            return [
                'authenticated' => true,
                'admin' => true,
                'user' => $this->authManager->getCurrentUser()
            ];
        } catch (Exception $e) {
            http_response_code(403);
            return [
                'authenticated' => $this->authManager->isLoggedIn(),
                'admin' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get current configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Update configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);
    }
}
