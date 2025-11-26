<?php

namespace Ksfraser\Auth;

use Exception;

/**
 * Authentication Configuration Manager
 * 
 * Manages configuration for the Ksfraser Auth system
 * 
 * @package Ksfraser\Auth
 */
class AuthConfig
{
    private $config = [];
    private static $instance = null;
    
    /**
     * Default configuration values
     */
    private const DEFAULTS = [
        // Database settings
        'database' => [
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'auth_db',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'table_prefix' => '',
            'users_table' => 'users'
        ],
        
        // Session settings
        'session' => [
            'name' => 'KSFRASER_AUTH_SESSION',
            'lifetime' => 3600,
            'auto_start' => true,
            'cookie_secure' => null, // Auto-detect HTTPS
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax'
        ],
        
        // Password settings
        'password' => [
            'min_length' => 8,
            'max_length' => 128,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'cost' => 12,
            'max_repeating' => 3,
            'forbidden_sequences' => ['123', 'abc', 'qwerty', 'password']
        ],
        
        // Username settings
        'username' => [
            'min_length' => 3,
            'max_length' => 64,
            'allow_numbers' => true,
            'allow_underscore' => true,
            'allow_dash' => true,
            'require_letter' => true
        ],
        
        // Security settings
        'security' => [
            'csrf_protection' => true,
            'csrf_token_name' => 'csrf_token',
            'max_login_attempts' => 5,
            'lockout_duration' => 900, // 15 minutes
            'jwt_secret' => null, // Auto-generated if null
            'jwt_expiry' => 86400, // 24 hours
            'rate_limiting' => false,
            'audit_logging' => true
        ],
        
        // UI settings
        'ui' => [
            'login_url' => '/login.php',
            'register_url' => '/register.php',
            'logout_url' => '/logout.php',
            'dashboard_url' => '/dashboard.php',
            'profile_url' => '/profile.php',
            'unauthorized_url' => '/unauthorized.php',
            'default_redirect' => '/',
            'theme' => 'default'
        ],
        
        // Middleware settings
        'middleware' => [
            'excluded_paths' => ['/login.php', '/register.php', '/logout.php'],
            'public_paths' => ['/public/', '/css/', '/js/', '/images/'],
            'admin_paths' => ['/admin/'],
            'json_responses' => false
        ],
        
        // Email settings (for future use)
        'email' => [
            'enabled' => false,
            'verification_required' => false,
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_username' => '',
            'smtp_password' => '',
            'from_address' => 'noreply@localhost',
            'from_name' => 'Auth System'
        ],
        
        // Logging settings
        'logging' => [
            'enabled' => true,
            'log_dir' => null, // Auto-detected
            'log_level' => 'info',
            'rotate_logs' => true,
            'max_log_size' => 10485760, // 10MB
            'max_log_files' => 5
        ]
    ];
    
    /**
     * Initialize configuration
     */
    private function __construct(array $config = [])
    {
        $this->config = $this->mergeConfig(self::DEFAULTS, $config);
        $this->validateConfig();
        $this->setupAutoDetectedValues();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }
    
    /**
     * Get configuration value
     */
    public function get(string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        return $this->getNestedValue($this->config, $key, $default);
    }
    
    /**
     * Set configuration value
     */
    public function set(string $key, $value): void
    {
        $this->setNestedValue($this->config, $key, $value);
    }
    
    /**
     * Load configuration from file
     */
    public function loadFromFile(string $filepath): void
    {
        if (!file_exists($filepath)) {
            throw new Exception("Configuration file not found: {$filepath}");
        }
        
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'php':
                $config = require $filepath;
                break;
            case 'json':
                $config = json_decode(file_get_contents($filepath), true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception("Invalid JSON in configuration file: " . json_last_error_msg());
                }
                break;
            default:
                throw new Exception("Unsupported configuration file format: {$extension}");
        }
        
        if (!is_array($config)) {
            throw new Exception("Configuration file must return an array");
        }
        
        $this->config = $this->mergeConfig($this->config, $config);
        $this->validateConfig();
    }
    
    /**
     * Save configuration to file
     */
    public function saveToFile(string $filepath): void
    {
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'php':
                $content = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
                break;
            case 'json':
                $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                break;
            default:
                throw new Exception("Unsupported configuration file format: {$extension}");
        }
        
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        if (file_put_contents($filepath, $content) === false) {
            throw new Exception("Failed to save configuration to: {$filepath}");
        }
    }
    
    /**
     * Get database configuration
     */
    public function getDatabaseConfig(): array
    {
        return $this->get('database');
    }
    
    /**
     * Get session configuration
     */
    public function getSessionConfig(): array
    {
        return $this->get('session');
    }
    
    /**
     * Get security configuration
     */
    public function getSecurityConfig(): array
    {
        return $this->get('security');
    }
    
    /**
     * Get UI configuration
     */
    public function getUIConfig(): array
    {
        return $this->get('ui');
    }
    
    /**
     * Check if feature is enabled
     */
    public function isEnabled(string $feature): bool
    {
        return (bool)$this->get($feature, false);
    }
    
    /**
     * Create configuration template
     */
    public static function createTemplate(string $filepath): void
    {
        $template = [
            'database' => [
                'host' => 'localhost',
                'database' => 'your_database_name',
                'username' => 'your_username',
                'password' => 'your_password'
            ],
            'security' => [
                'jwt_secret' => 'your-secret-key-here',
                'csrf_protection' => true,
                'rate_limiting' => true
            ],
            'ui' => [
                'login_url' => '/login.php',
                'dashboard_url' => '/dashboard.php'
            ]
        ];
        
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'php':
                $content = "<?php\n\n// Ksfraser Auth Configuration\n\nreturn " . var_export($template, true) . ";\n";
                break;
            case 'json':
                $content = json_encode($template, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                break;
            default:
                throw new Exception("Unsupported configuration file format: {$extension}");
        }
        
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($filepath, $content);
    }
    
    /**
     * Merge configuration arrays recursively
     */
    private function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfig($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }
        return $base;
    }
    
    /**
     * Get nested configuration value
     */
    private function getNestedValue(array $array, string $key, $default = null)
    {
        $keys = explode('.', $key);
        $current = $array;
        
        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }
        
        return $current;
    }
    
    /**
     * Set nested configuration value
     */
    private function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }
    
    /**
     * Validate configuration
     */
    private function validateConfig(): void
    {
        // Validate required database fields
        $requiredDbFields = ['host', 'database', 'username'];
        foreach ($requiredDbFields as $field) {
            if (empty($this->get("database.{$field}"))) {
                throw new Exception("Required database configuration missing: {$field}");
            }
        }
        
        // Validate password settings
        $minLength = $this->get('password.min_length', 8);
        $maxLength = $this->get('password.max_length', 128);
        if ($minLength < 1 || $minLength > $maxLength) {
            throw new Exception("Invalid password length configuration");
        }
        
        // Validate session settings
        $sessionLifetime = $this->get('session.lifetime', 3600);
        if ($sessionLifetime < 60) {
            throw new Exception("Session lifetime must be at least 60 seconds");
        }
    }
    
    /**
     * Setup auto-detected configuration values
     */
    private function setupAutoDetectedValues(): void
    {
        // Auto-detect HTTPS for secure cookies
        if ($this->get('session.cookie_secure') === null) {
            $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            $this->set('session.cookie_secure', $isHttps);
        }
        
        // Auto-generate JWT secret if not provided
        if ($this->get('security.jwt_secret') === null) {
            $this->set('security.jwt_secret', bin2hex(random_bytes(32)));
        }
        
        // Auto-detect log directory
        if ($this->get('logging.log_dir') === null) {
            $logDir = __DIR__ . '/../../../logs';
            $this->set('logging.log_dir', $logDir);
        }
    }
    
    /**
     * Export configuration for debugging
     */
    public function export(bool $hideSensitive = true): array
    {
        $config = $this->config;
        
        if ($hideSensitive) {
            // Hide sensitive information
            $sensitiveKeys = [
                'database.password',
                'security.jwt_secret',
                'email.smtp_password'
            ];
            
            foreach ($sensitiveKeys as $key) {
                if ($this->getNestedValue($config, $key) !== null) {
                    $this->setNestedValue($config, $key, '***HIDDEN***');
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Reset configuration to defaults
     */
    public function reset(): void
    {
        $this->config = self::DEFAULTS;
        $this->setupAutoDetectedValues();
    }
    
    /**
     * Clone configuration instance
     */
    public function clone(): self
    {
        return new self($this->config);
    }
}
