<?php
/**
 * Basic Usage Example for Finance Package
 * 
 * Demonstrates how to use the SOLID Finance architecture.
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\Finance\DI\Container;

// Load configuration from existing DatabaseConfig system
try {
    $config = DatabaseConfig::getFinanceConfig();
} catch (Exception $e) {
    echo "Configuration Error: " . $e->getMessage() . "\n";
    echo "Please ensure your database configuration file exists.\n";
    exit(1);
}

// Create container
$container = new Container($config);

echo "=== Finance Package Example Usage ===\n\n";

try {
    // Get stock controller
    $stockController = $container->get('stock_controller');
    
    // Example 1: Update single stock
    echo "1. Updating single stock (AAPL)...\n";
    $result = $stockController->updateStock('AAPL');
    echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    if (!$result['success']) {
        echo "Error: " . $result['error'] . "\n";
    } else {
        echo "Message: " . $result['message'] . "\n";
    }
    echo "\n";

    // Example 2: Get market overview
    echo "2. Getting market overview...\n";
    $overview = $stockController->getMarketOverview();
    if ($overview['success']) {
        $data = $overview['data'];
        echo "Total symbols tracked: " . $data['total_symbols'] . "\n";
        echo "Symbols with data: " . $data['symbols_with_data'] . "\n";
        if (!empty($data['top_gainers'])) {
            echo "Top gainer: " . $data['top_gainers'][0]['symbol'] . " (+" . 
                 number_format($data['top_gainers'][0]['change_percent'], 2) . "%)\n";
        }
    } else {
        echo "Error: " . $overview['error'] . "\n";
    }
    echo "\n";

    // Example 3: Get AI analysis (if OpenAI is configured)
    echo "3. Getting AI analysis for AAPL...\n";
    $analysis = $stockController->getAnalysis('AAPL');
    if ($analysis['success']) {
        $data = $analysis['data'];
        echo "Recommendation: " . $data['recommendation'] . "\n";
        echo "Confidence: " . number_format($data['confidence'] * 100, 1) . "%\n";
        echo "Analysis preview: " . substr($data['analysis'], 0, 200) . "...\n";
    } else {
        echo "Error: " . $analysis['error'] . "\n";
    }
    echo "\n";

    // Example 4: Bulk update
    echo "4. Bulk updating multiple stocks...\n";
    $symbols = ['AAPL', 'GOOGL', 'MSFT', 'TSLA'];
    $bulkResult = $stockController->bulkUpdate($symbols);
    if ($bulkResult['success']) {
        $data = $bulkResult['data'];
        echo "Successfully updated: " . $data['successful_updates'] . "/" . $data['total_symbols'] . " symbols\n";
        echo "Success rate: " . number_format($data['success_rate'], 1) . "%\n";
    } else {
        echo "Error: " . $bulkResult['error'] . "\n";
    }
    echo "\n";

    // Example 5: Get historical data
    echo "5. Getting historical data for AAPL (last 7 days)...\n";
    $history = $stockController->getHistory('AAPL', 7);
    if ($history['success']) {
        $data = $history['data'];
        echo "Found " . count($data['prices']) . " price records\n";
        echo "Period: " . $data['start_date'] . " to " . $data['end_date'] . "\n";
    } else {
        echo "Error: " . $history['error'] . "\n";
    }
    echo "\n";

} catch (Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "=== Example completed ===\n";
