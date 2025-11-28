<?php

/**
 * Example: Using the DI Container
 * 
 * Demonstrates how to use the configured container to get services
 * with all dependencies automatically resolved.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Get the configured container
$container = require __DIR__ . '/bootstrap.php';

// ===== EXAMPLE 1: Get Stock Analysis Service =====
echo "Example 1: Getting StockAnalysisService via Container\n";
echo str_repeat("=", 50) . "\n\n";

$stockAnalysisService = $container->get(\App\Services\StockAnalysisService::class);
echo "✅ StockAnalysisService resolved successfully\n";
echo "   - Dependencies injected automatically:\n";
echo "     • MarketDataService\n";
echo "     • AnalysisRepository\n";
echo "     • PythonIntegrationService\n\n";

// ===== EXAMPLE 2: Get Market Data Service =====
echo "Example 2: Getting MarketDataService via Container\n";
echo str_repeat("=", 50) . "\n\n";

$marketDataService = $container->get(\App\Services\MarketDataService::class);
echo "✅ MarketDataService resolved successfully\n";
echo "   - Dependencies injected automatically:\n";
echo "     • MarketDataRepository\n";
echo "     • StockDataAccessInterface\n\n";

// ===== EXAMPLE 3: Singleton Verification =====
echo "Example 3: Verifying Singleton Behavior\n";
echo str_repeat("=", 50) . "\n\n";

$service1 = $container->get(\App\Services\MarketDataService::class);
$service2 = $container->get(\App\Services\MarketDataService::class);

if ($service1 === $service2) {
    echo "✅ Singleton verified: Same instance returned\n";
    echo "   - Memory efficient: Services are reused\n";
    echo "   - State preserved: Shared cache across requests\n\n";
} else {
    echo "❌ ERROR: Different instances returned\n\n";
}

// ===== EXAMPLE 4: Check Container Capabilities =====
echo "Example 4: Container Capabilities\n";
echo str_repeat("=", 50) . "\n\n";

$capabilities = [
    \App\Services\StockAnalysisService::class => 'Stock Analysis Service',
    \App\Services\MarketDataService::class => 'Market Data Service',
    \App\Repositories\AnalysisRepositoryInterface::class => 'Analysis Repository',
    \App\Repositories\MarketDataRepositoryInterface::class => 'Market Data Repository',
];

echo "Available services in container:\n";
foreach ($capabilities as $abstract => $name) {
    $available = $container->has($abstract) ? '✅' : '❌';
    $singleton = $container->isSingleton($abstract) ? '(Singleton)' : '(Transient)';
    echo "  {$available} {$name} {$singleton}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Container bootstrap complete! Ready for use.\n";
echo str_repeat("=", 50) . "\n\n";

// ===== EXAMPLE 5: Actual Usage (commented out to avoid real API calls) =====
/*
echo "Example 5: Analyzing a Stock\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $result = $stockAnalysisService->analyzeStock('AAPL');
    
    if ($result['success']) {
        echo "✅ Analysis successful for AAPL\n";
        echo "   Recommendation: {$result['recommendation']}\n";
        echo "   Overall Score: {$result['overall_score']}\n";
        echo "   Confidence: {$result['confidence']}\n";
    } else {
        echo "❌ Analysis failed: {$result['error']}\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: {$e->getMessage()}\n";
}
*/
