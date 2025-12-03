<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Repositories\StrategyParametersRepository;
use App\Services\StrategyConfigurationService;

try {
    $databasePath = __DIR__ . '/storage/database/stock_analysis.db';
    
    echo "Testing Strategy Configuration API\n";
    echo "===================================\n\n";
    
    echo "Database path: $databasePath\n";
    echo "Database exists: " . (file_exists($databasePath) ? "YES" : "NO") . "\n\n";
    
    // Test repository
    $repo = new StrategyParametersRepository($databasePath);
    echo "Repository initialized successfully\n";
    
    // Test getting strategies
    $strategies = $repo->getAvailableStrategies();
    echo "Available strategies: " . count($strategies) . "\n";
    echo json_encode($strategies, JSON_PRETTY_PRINT) . "\n\n";
    
    // Test configuration service
    $configService = new StrategyConfigurationService($repo);
    echo "Configuration service initialized successfully\n";
    
    // Test getting metadata for first strategy
    if (!empty($strategies)) {
        $firstStrategy = $strategies[0];
        echo "\nTesting with strategy: $firstStrategy\n";
        $metadata = $configService->getConfigurationMetadata($firstStrategy);
        echo "Parameters found: " . count($metadata) . "\n";
        
        // Show first 3 parameters
        $count = 0;
        foreach ($metadata as $param) {
            if ($count++ >= 3) break;
            echo "  - {$param['parameter_key']}: {$param['parameter_value']} ({$param['parameter_type']})\n";
        }
    }
    
    echo "\n✅ All tests passed!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
