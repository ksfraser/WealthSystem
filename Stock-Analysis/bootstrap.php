<?php

/**
 * DI Container Bootstrap
 * 
 * Configures the Dependency Injection Container with all service bindings,
 * repository implementations, and third-party dependencies.
 * 
 * Usage:
 *   $container = require __DIR__ . '/bootstrap.php';
 *   $service = $container->get(StockAnalysisService::class);
 */

use App\Container\DIContainer;
use App\Repositories\AnalysisRepository;
use App\Repositories\AnalysisRepositoryInterface;
use App\Repositories\MarketDataRepository;
use App\Repositories\MarketDataRepositoryInterface;
use App\Services\StockAnalysisService;
use App\Services\MarketDataService;
use App\Services\PythonIntegrationService;
use App\DataAccess\Adapters\DynamicStockDataAccessAdapter;
use App\DataAccess\Interfaces\StockDataAccessInterface;

// Create container instance
$container = new DIContainer();

// ===== REPOSITORY BINDINGS (Singletons for shared state) =====

$container->singleton(AnalysisRepositoryInterface::class, function() {
    $storagePath = __DIR__ . '/storage/analysis';
    return new AnalysisRepository($storagePath);
});

$container->singleton(MarketDataRepositoryInterface::class, function() {
    $storagePath = __DIR__ . '/storage/market_data';
    return new MarketDataRepository($storagePath);
});

// ===== DATA ACCESS BINDINGS =====

$container->singleton(StockDataAccessInterface::class, function() {
    // Note: DynamicStockDataAccessAdapter requires DynamicStockDataAccess.php
    // In production, ensure all dependencies are available or use mock for testing
    return new DynamicStockDataAccessAdapter();
});

// ===== SERVICE BINDINGS =====

$container->singleton(PythonIntegrationService::class, function() {
    return new PythonIntegrationService();
});

$container->singleton(MarketDataService::class, function($container) {
    return new MarketDataService(
        $container->get(MarketDataRepositoryInterface::class),
        $container->get(StockDataAccessInterface::class),
        [
            'fundamentals_cache_ttl' => 86400, // 24 hours
            'price_cache_ttl' => 300,          // 5 minutes
        ]
    );
});

$container->singleton(StockAnalysisService::class, function($container) {
    return new StockAnalysisService(
        $container->get(MarketDataService::class),
        $container->get(AnalysisRepositoryInterface::class),
        $container->get(PythonIntegrationService::class),
        [
            'cache_ttl' => 3600,              // 1 hour cache for analysis
            'python_path' => 'python',        // Python executable path
            'weights' => [
                'fundamental' => 0.40,
                'technical' => 0.30,
                'momentum' => 0.20,
                'sentiment' => 0.10
            ]
        ]
    );
});

// ===== ADDITIONAL SERVICES (Add as needed) =====

// Example: Portfolio Service (if needed later)
// $container->singleton(PortfolioService::class, function($container) {
//     return new PortfolioService(
//         $container->get(PortfolioRepositoryInterface::class),
//         $container->get(MarketDataService::class),
//         // ... other dependencies
//     );
// });

// ===== TRADING STRATEGIES =====

$container->singleton(App\Services\Trading\TurtleStrategyService::class, function($container) {
    return new App\Services\Trading\TurtleStrategyService(
        $container->get(MarketDataService::class),
        $container->get(MarketDataRepositoryInterface::class)
    );
});

$container->singleton(App\Services\Trading\MACrossoverStrategyService::class, function($container) {
    return new App\Services\Trading\MACrossoverStrategyService(
        $container->get(MarketDataService::class),
        $container->get(MarketDataRepositoryInterface::class)
    );
});

return $container;
