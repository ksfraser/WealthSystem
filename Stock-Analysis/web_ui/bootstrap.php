<?php
/**
 * Web UI Bootstrap with Dependency Injection
 * 
 * This bootstrap file sets up the DI Container for web UI pages.
 * It provides access to:
 * - Stock Analysis services (StockAnalysisService, MarketDataService)
 * - Repository layer (AnalysisRepository, MarketDataRepository)
 * - Authentication services (UserAuthDAO, SessionManager)
 * - Navigation and UI services
 * 
 * Usage in web pages:
 * ```php
 * require_once __DIR__ . '/bootstrap.php';
 * 
 * $analysisService = $container->get(StockAnalysisService::class);
 * $marketData = $container->get(MarketDataService::class);
 * $auth = $container->get(UserAuthDAO::class);
 * ```
 */

// Load Composer's autoloader first
require_once __DIR__ . '/../vendor/autoload.php';

// Load the Stock-Analysis DI Container bootstrap
$container = require __DIR__ . '/../bootstrap.php';

// Define application constants
if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('WEB_ROOT')) {
    define('WEB_ROOT', __DIR__);
}

if (!defined('STORAGE_ROOT')) {
    define('STORAGE_ROOT', APP_ROOT . '/storage');
}

// Error reporting for development
if (!defined('PRODUCTION')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Web-specific service bindings (for legacy compatibility)
// These services are used by existing web_ui pages that haven't been refactored yet

// SessionManager (web UI specific)
if (!$container->has(App\Core\SessionManager::class)) {
    require_once __DIR__ . '/SessionManager.php';
    $container->singleton(App\Core\SessionManager::class, function() {
        return new App\Core\SessionManager();
    });
}

// UserAuthDAO (web UI authentication)
if (!$container->has(UserAuthDAO::class)) {
    require_once __DIR__ . '/UserAuthDAO.php';
    $container->singleton(UserAuthDAO::class, function($c) {
        return new UserAuthDAO();
    });
}

// NavigationService (web UI navigation)
if (!$container->has(App\Services\NavigationService::class)) {
    $container->singleton(App\Services\NavigationService::class);
}

// Return the configured container
return $container;
