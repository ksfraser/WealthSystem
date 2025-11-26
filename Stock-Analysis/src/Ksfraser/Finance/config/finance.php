<?php
/**
 * Finance Configuration
 * 
 * Configuration settings for the Finance package.
 * Uses environment variables for sensitive data like API keys.
 */

return [
    'database' => [
        'dsn' => sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'] ?? 'localhost',
            $_ENV['DB_NAME'] ?? 'stock_market'
        ),
        'username' => $_ENV['DB_USERNAME'] ?? 'root',
        'password' => $_ENV['DB_PASSWORD'] ?? '',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
        ]
    ],
    
    'alphavantage' => [
        'api_key' => $_ENV['ALPHAVANTAGE_API_KEY'] ?? '',
        'base_url' => 'https://www.alphavantage.co/query',
        'rate_limit' => 5, // requests per minute for free tier
        'timeout' => 30
    ],
    
    'openai' => [
        'api_key' => $_ENV['OPENAI_API_KEY'] ?? '',
        'base_url' => 'https://api.openai.com/v1/chat/completions',
        'model' => $_ENV['OPENAI_MODEL'] ?? 'gpt-4',
        'max_tokens' => 1500,
        'temperature' => 0.3,
        'timeout' => 60
    ],
    
    'rate_limiting' => [
        'delay_between_requests' => 500000, // microseconds (500ms)
        'delay_between_symbols' => 200000,  // microseconds (200ms)
        'max_concurrent_requests' => 3
    ],
    
    'general' => [
        'max_retries' => 3,
        'timeout' => 30,
        'bulk_update_limit' => 100,
        'historical_data_days' => 365
    ],
    
    'logging' => [
        'enabled' => true,
        'level' => $_ENV['LOG_LEVEL'] ?? 'info',
        'file' => $_ENV['LOG_FILE'] ?? null // null = use error_log
    ],
    
    'cache' => [
        'enabled' => false, // Can be extended later with Redis/Memcached
        'ttl' => 300, // 5 minutes
        'prefix' => 'finance_'
    ],
    
    'features' => [
        'llm_analysis' => true,
        'historical_data' => true,
        'bulk_updates' => true,
        'market_overview' => true
    ]
];
