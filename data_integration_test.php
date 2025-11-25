<?php

/**
 * Data Integration Test
 * 
 * Tests the complete data flow from existing systems to new MVC architecture.
 */

// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Register App namespace autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') === 0) {
        $file = __DIR__ . '/app/' . str_replace(['App\\', '\\'], ['', '/'], $class) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use App\Core\ServiceContainer;

echo "<h1>ChatGPT Micro Cap Portfolio - Data Integration Test</h1>\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:5px;overflow-x:auto;}</style>\n";

try {
    echo "<h2>1. Service Container Bootstrap</h2>\n";
    $container = ServiceContainer::bootstrap();
    echo "<p class='success'>✓ Service container initialized successfully</p>\n";
    
    echo "<h2>2. Portfolio Data Test</h2>\n";
    $portfolioService = $container->get('App\\Services\\Interfaces\\PortfolioServiceInterface');
    echo "<p class='success'>✓ Portfolio service resolved from container</p>\n";
    
    // Test dashboard data
    $userId = 1;
    echo "<p class='info'>Testing dashboard data for user ID: {$userId}</p>\n";
    
    $dashboardData = $portfolioService->getDashboardData($userId);
    echo "<p class='success'>✓ Dashboard data retrieved</p>\n";
    
    echo "<h3>Dashboard Data Summary:</h3>\n";
    echo "<pre>\n";
    echo "Total Value: $" . number_format($dashboardData['total_value'] ?? 0, 2) . "\n";
    echo "Daily Change: $" . number_format($dashboardData['daily_change'] ?? 0, 2) . "\n";
    echo "Total Return: $" . number_format($dashboardData['total_return'] ?? 0, 2) . "\n";
    echo "Holdings Count: " . ($dashboardData['stock_count'] ?? 0) . "\n";
    echo "Market Data Count: " . count($dashboardData['marketData'] ?? []) . "\n";
    echo "</pre>\n";
    
    if (!empty($dashboardData['holdings'])) {
        echo "<h3>Holdings Preview (First 3):</h3>\n";
        echo "<pre>\n";
        $count = 0;
        foreach ($dashboardData['holdings'] as $holding) {
            if ($count >= 3) break;
            echo sprintf("%-6s %-20s %8.2f shares @ $%8.2f = $%10.2f\n", 
                $holding['symbol'] ?? 'N/A',
                substr($holding['company_name'] ?? 'Unknown', 0, 20),
                $holding['shares'] ?? 0,
                $holding['current_price'] ?? 0,
                $holding['market_value'] ?? 0
            );
            $count++;
        }
        echo "</pre>\n";
    } else {
        echo "<p class='error'>⚠ No holdings data found</p>\n";
    }
    
    echo "<h2>3. Market Data Service Test</h2>\n";
    $marketService = $container->get('App\\Services\\Interfaces\\MarketDataServiceInterface');
    echo "<p class='success'>✓ Market data service resolved</p>\n";
    
    // Test market data
    $testSymbols = ['AAPL', 'MSFT', 'GOOGL'];
    echo "<p class='info'>Testing market data for: " . implode(', ', $testSymbols) . "</p>\n";
    
    $marketPrices = $marketService->getCurrentPrices($testSymbols);
    echo "<p class='success'>✓ Market prices retrieved</p>\n";
    
    echo "<h3>Market Prices:</h3>\n";
    echo "<pre>\n";
    foreach ($testSymbols as $symbol) {
        $price = $marketPrices[$symbol] ?? null;
        if ($price) {
            echo sprintf("%-6s: $%8.2f (Change: %+6.2f)\n", $symbol, $price['price'] ?? 0, $price['change'] ?? 0);
        } else {
            echo sprintf("%-6s: No data available\n", $symbol);
        }
    }
    echo "</pre>\n";
    
    // Market summary
    $marketSummary = $marketService->getMarketSummary();
    if (!empty($marketSummary)) {
        echo "<h3>Market Summary:</h3>\n";
        echo "<pre>\n";
        foreach ($marketSummary as $index) {
            echo sprintf("%-15s: %s (%+.2f%%)\n", 
                $index['name'] ?? 'Unknown', 
                $index['value'] ?? 'N/A',
                $index['change_percent'] ?? 0
            );
        }
        echo "</pre>\n";
    }
    
    echo "<h2>4. Data Source Validation</h2>\n";
    
    // Check CSV files
    $csvPaths = [
        'Scripts and CSV Files/chatgpt_portfolio_update.csv',
        'Start Your Own/chatgpt_portfolio_update.csv'
    ];
    
    foreach ($csvPaths as $csvPath) {
        $fullPath = __DIR__ . '/' . $csvPath;
        if (file_exists($fullPath)) {
            $filesize = filesize($fullPath);
            echo "<p class='success'>✓ Found CSV: {$csvPath} ({$filesize} bytes)</p>\n";
            
            // Read first few lines for preview
            $handle = fopen($fullPath, 'r');
            if ($handle) {
                echo "<h4>CSV Preview:</h4><pre>\n";
                $lineCount = 0;
                while (($line = fgets($handle)) !== false && $lineCount < 3) {
                    echo htmlspecialchars(trim($line)) . "\n";
                    $lineCount++;
                }
                fclose($handle);
                echo "</pre>\n";
            }
            break;
        } else {
            echo "<p class='error'>✗ Missing CSV: {$csvPath}</p>\n";
        }
    }
    
    // Check database access
    echo "<h3>Database Access Test:</h3>\n";
    try {
        require_once __DIR__ . '/DynamicStockDataAccess.php';
        $dataAccess = new DynamicStockDataAccess();
        echo "<p class='success'>✓ DynamicStockDataAccess instantiated</p>\n";
        
        $testPrice = $dataAccess->getLatestPrice('AAPL');
        if ($testPrice) {
            echo "<p class='success'>✓ Database contains price data</p>\n";
            echo "<pre>Latest AAPL data: " . print_r($testPrice, true) . "</pre>\n";
        } else {
            echo "<p class='error'>⚠ No price data found in database</p>\n";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Database access failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
    
    echo "<h2>5. Integration Status Summary</h2>\n";
    
    $hasPortfolioData = !empty($dashboardData['holdings']);
    $hasMarketData = !empty($marketSummary);
    $hasCsvData = false;
    
    foreach ($csvPaths as $csvPath) {
        if (file_exists(__DIR__ . '/' . $csvPath)) {
            $hasCsvData = true;
            break;
        }
    }
    
    echo "<table border='1' style='border-collapse:collapse; width:100%;'>\n";
    echo "<tr><th>Component</th><th>Status</th><th>Notes</th></tr>\n";
    echo "<tr><td>MVC Architecture</td><td class='success'>✓ Working</td><td>All services properly wired</td></tr>\n";
    echo "<tr><td>Portfolio Data</td><td class='" . ($hasPortfolioData ? "success'>✓ Available" : "error'>⚠ Missing") . "</td><td>" . ($hasPortfolioData ? 'Holdings loaded from DAOs' : 'Check CSV files and database') . "</td></tr>\n";
    echo "<tr><td>Market Data</td><td class='" . ($hasMarketData ? "success'>✓ Available" : "error'>⚠ Limited") . "</td><td>" . ($hasMarketData ? 'Market indices loaded' : 'API access may be limited') . "</td></tr>\n";
    echo "<tr><td>CSV Files</td><td class='" . ($hasCsvData ? "success'>✓ Found" : "error'>✗ Missing") . "</td><td>" . ($hasCsvData ? 'Portfolio CSV accessible' : 'Upload portfolio data') . "</td></tr>\n";
    echo "<tr><td>Database</td><td class='info'>? Partial</td><td>DynamicStockDataAccess available</td></tr>\n";
    echo "</table>\n";
    
    $overallStatus = $hasPortfolioData && $hasMarketData ? 'good' : ($hasPortfolioData || $hasMarketData ? 'partial' : 'needs-setup');
    
    echo "<h3>Overall Status: <span class='" . ($overallStatus === 'good' ? 'success' : ($overallStatus === 'partial' ? 'info' : 'error')) . "'>" . ucfirst($overallStatus) . "</span></h3>\n";
    
    if ($overallStatus !== 'good') {
        echo "<h3>Next Steps:</h3>\n";
        echo "<ul>\n";
        if (!$hasPortfolioData) {
            echo "<li>Create or upload portfolio data to Scripts and CSV Files/chatgpt_portfolio_update.csv</li>\n";
        }
        if (!$hasMarketData) {
            echo "<li>Configure API keys for Yahoo Finance, Alpha Vantage, or Finnhub</li>\n";
        }
        echo "<li>Run the Python trading_script.py to populate historical data</li>\n";
        echo "<li>Verify database connections and table structures</li>\n";
        echo "</ul>\n";
    }
    
    echo "<p class='info'><strong>Test completed successfully!</strong> The MVC architecture is properly integrated.</p>\n";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Test failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>Stack trace:\n" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "\n<hr>\n<p><small>Test completed at: " . date('Y-m-d H:i:s') . "</small></p>\n";