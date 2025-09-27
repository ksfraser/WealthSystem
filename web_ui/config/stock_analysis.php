<?php
/**
 * Stock Analysis Configuration
 * 
 * Configuration settings for LLM integration and analysis services
 */

return [
    // LLM API Configuration
    'llm' => [
        'openai_api_key' => getenv('OPENAI_API_KEY') ?: '',
        'claude_api_key' => getenv('CLAUDE_API_KEY') ?: '',
        'openai_model' => 'gpt-4-turbo-preview',
        'claude_model' => 'claude-3-sonnet-20240229',
        'default_provider' => 'openai', // 'openai' or 'claude'
        'max_tokens' => 2000,
        'temperature' => 0.3,
        'timeout' => 30,
        'cache_timeout' => 3600, // 1 hour
        'analysis_interval' => 86400, // 24 hours between full analysis
        'sentiment_threshold' => 0.1,
        'debug_mode' => false
    ],
    
    // Price Service Configuration
    'price_service' => [
        'python_script' => __DIR__ . '/../trading_script.py',
        'python_executable' => 'python',
        'cache_timeout' => 300, // 5 minutes
        'batch_size' => 50,
        'max_retries' => 3,
        'yahoo_finance_enabled' => true,
        'stooq_enabled' => false,
        'debug_mode' => false
    ],
    
    // Database Configuration
    'database' => [
        'prefix' => '',
        'individual_stock_tables' => true,
        'cleanup_old_data_days' => 730, // 2 years
        'max_price_records_per_stock' => 10000,
        'max_news_records_per_stock' => 1000
    ],
    
    // Analysis Configuration
    'analysis' => [
        'fundamental_weight' => 0.25,
        'technical_weight' => 0.20,
        'momentum_weight' => 0.15,
        'sentiment_weight' => 0.20,
        'news_weight' => 0.20,
        'min_confidence_threshold' => 60,
        'auto_analysis_enabled' => true,
        'analysis_schedule' => [
            'daily_update' => '02:00', // 2 AM
            'weekly_deep_analysis' => 'Sunday 03:00',
            'earnings_season_boost' => true
        ]
    ],
    
    // News Configuration
    'news' => [
        'sources' => [
            'yahoo_finance' => true,
            'reuters' => false,
            'bloomberg' => false,
            'marketwatch' => false
        ],
        'categories' => [
            'EARNINGS' => 'Earnings Reports',
            'GENERAL' => 'General News', 
            'MERGER' => 'Mergers & Acquisitions',
            'REGULATORY' => 'Regulatory News',
            'ANALYST' => 'Analyst Reports',
            'PRODUCT' => 'Product News',
            'MANAGEMENT' => 'Management Changes'
        ],
        'importance_levels' => [
            'LOW' => 'Low Impact',
            'MEDIUM' => 'Medium Impact',
            'HIGH' => 'High Impact',
            'CRITICAL' => 'Critical Impact'
        ]
    ],
    
    // Technical Analysis Configuration
    'technical' => [
        'indicators' => [
            'sma' => [20, 50, 200],
            'ema' => [12, 26],
            'rsi' => [14, 30],
            'macd' => [12, 26, 9],
            'bollinger' => [20, 2],
            'stochastic' => [14, 3, 3],
            'atr' => [14],
            'adx' => [14],
            'cci' => [14]
        ],
        'timeframes' => [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly'
        ],
        'chart_periods' => [
            '1M' => 30,
            '3M' => 90, 
            '6M' => 180,
            '1Y' => 365,
            '2Y' => 730,
            '5Y' => 1825
        ]
    ],
    
    // UI Configuration
    'ui' => [
        'default_chart_period' => '6M',
        'max_search_results' => 100,
        'news_per_page' => 20,
        'price_data_points' => 252, // Trading days in a year
        'real_time_updates' => true,
        'chart_library' => 'chartjs', // 'chartjs' or 'tradingview'
        'theme' => 'default'
    ],
    
    // Caching Configuration
    'cache' => [
        'enabled' => true,
        'driver' => 'file', // 'file', 'redis', 'memcached'
        'path' => __DIR__ . '/cache',
        'default_ttl' => 900, // 15 minutes
        'price_data_ttl' => 300, // 5 minutes
        'news_data_ttl' => 1800, // 30 minutes
        'analysis_ttl' => 3600, // 1 hour
        'compression' => false
    ],
    
    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => 'INFO', // DEBUG, INFO, WARNING, ERROR
        'file' => __DIR__ . '/logs/stock_analysis.log',
        'max_file_size' => '10MB',
        'max_files' => 5,
        'log_api_calls' => false,
        'log_performance' => true
    ],
    
    // Security Configuration
    'security' => [
        'api_rate_limit' => 100, // requests per minute
        'max_symbols_per_request' => 50,
        'allowed_file_uploads' => false,
        'csrf_protection' => true,
        'session_timeout' => 3600, // 1 hour
        'password_requirements' => [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => false
        ]
    ]
];