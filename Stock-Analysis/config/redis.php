<?php
/**
 * Redis Configuration
 * 
 * Configuration settings for Redis cache connection.
 * Supports environment variables for production deployment.
 * 
 * @version 1.0.0
 */

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    foreach ($env as $key => $value) {
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
    }
}

return [
    // Redis connection settings
    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
    'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 2.5),
    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
    'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
    
    // Cache TTL settings (in seconds)
    'ttl' => [
        'sector_analysis' => (int) ($_ENV['CACHE_TTL_SECTOR'] ?? 600),      // 10 minutes
        'index_benchmark' => (int) ($_ENV['CACHE_TTL_INDEX'] ?? 900),       // 15 minutes
        'default' => (int) ($_ENV['CACHE_TTL_DEFAULT'] ?? 600)              // 10 minutes
    ],
    
    // Cache enabled flag
    'enabled' => filter_var(
        $_ENV['CACHE_ENABLED'] ?? 'true',
        FILTER_VALIDATE_BOOLEAN
    )
];
