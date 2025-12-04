<?php
/**
 * Index Benchmark API Endpoint
 * 
 * Returns index benchmark comparison data in JSON format.
 * 
 * Query Parameters:
 * - symbol: Portfolio/stock symbol (required)
 * - index: Index symbol - SPX, IXIC, DJI, RUT (default: SPX)
 * - period: Time period - 1M, 3M, 6M, 1Y, 3Y, 5Y (default: 1Y)
 * 
 * Response Format:
 * {
 *   "success": true,
 *   "performance_chart": {...},
 *   "metrics_table": {...},
 *   "relative_performance": {...},
 *   "risk_metrics": {...}
 * }
 * 
 * @version 1.0.0
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/cache.php';

use App\Services\IndexBenchmarkService;
use App\DAO\IndexDataDAOImpl;

try {
    // Validate and get parameters
    if (!isset($_GET['symbol'])) {
        throw new InvalidArgumentException('symbol parameter is required');
    }
    
    $symbol = strtoupper(trim($_GET['symbol']));
    $indexSymbol = $_GET['index'] ?? 'SPX';
    $period = $_GET['period'] ?? '1Y';
    
    // Try to get from cache
    $cache = getCacheService();
    $cacheKey = null;
    $cachedData = null;
    
    if ($cache !== null) {
        $cacheKey = $cache->generateKey('index_benchmark', [
            'symbol' => $symbol,
            'index' => $indexSymbol,
            'period' => $period
        ]);
        $cachedData = $cache->get($cacheKey);
    }
    
    // If cached data exists, return it
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // Get database connection
    $pdo = getDbConnection();
    
    // Create DAO and service
    $dao = new IndexDataDAOImpl($pdo);
    $service = new IndexBenchmarkService($dao);
    
    // Fetch index data
    $indexData = $service->fetchIndexData($indexSymbol, $period);
    
    // For demo purposes, generate sample portfolio data
    // In production, this would fetch actual portfolio returns
    $portfolioReturns = generateSampleReturns(count($indexData));
    $indexReturns = array_column($indexData, 'return_pct');
    
    // Calculate performance metrics
    $relativePerf = $service->calculateRelativePerformance($portfolioReturns, $indexReturns);
    $beta = $service->calculateBeta($portfolioReturns, $indexReturns);
    $alpha = $service->calculateAlpha(
        $relativePerf['portfolio_return'],
        $relativePerf['index_return'],
        $beta,
        0.5 // Risk-free rate (0.5% per month)
    );
    $correlation = $service->calculateCorrelation($portfolioReturns, $indexReturns);
    $sharpe = $service->calculateSharpeRatio($portfolioReturns, 0.5);
    $sortino = $service->calculateSortinoRatio($portfolioReturns, 0);
    
    // Calculate cumulative returns for chart
    $portfolioCumulative = calculateCumulativeReturns($portfolioReturns);
    $indexCumulative = calculateCumulativeReturns($indexReturns);
    
    // Format for charts
    $performanceChart = $service->formatForPerformanceChart(
        array_map(function($val, $date) {
            return ['date' => $date, 'value' => $val];
        }, $portfolioCumulative, array_column($indexData, 'date')),
        array_map(function($val, $date) {
            return ['date' => $date, 'value' => $val];
        }, $indexCumulative, array_column($indexData, 'date')),
        'Portfolio vs ' . $indexSymbol
    );
    
    // Format metrics table
    $metricsTable = $service->formatForComparisonTable([
        'total_return' => $relativePerf['portfolio_return'],
        'beta' => $beta,
        'alpha' => $alpha,
        'correlation' => $correlation,
        'sharpe_ratio' => $sharpe,
        'sortino_ratio' => $sortino
    ], [
        'total_return' => $relativePerf['index_return'],
        'beta' => 1.0,
        'alpha' => 0.0,
        'correlation' => 1.0,
        'sharpe_ratio' => $service->calculateSharpeRatio($indexReturns, 0.5),
        'sortino_ratio' => $service->calculateSortinoRatio($indexReturns, 0)
    ]);
    
    // Prepare response data
    $responseData = [
        'performance_chart' => $performanceChart,
        'metrics_table' => $metricsTable,
        'relative_performance' => $relativePerf,
        'risk_metrics' => [
            'beta' => round($beta, 3),
            'alpha' => round($alpha, 2),
            'correlation' => round($correlation, 3),
            'sharpe_ratio' => round($sharpe, 2),
            'sortino_ratio' => round($sortino, 2)
        ]
    ];
    
    // Store in cache
    if ($cache !== null && $cacheKey !== null) {
        $ttl = getCacheTTL('index_benchmark');
        $cache->set($cacheKey, $responseData, $ttl);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $responseData,
        'cached' => false
    ], JSON_PRETTY_PRINT);
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request: ' . $e->getMessage()
    ]);
} catch (RuntimeException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Unexpected error: ' . $e->getMessage()
    ]);
}

/**
 * Generate sample returns for demonstration
 */
function generateSampleReturns($count) {
    $returns = [];
    for ($i = 0; $i < $count; $i++) {
        // Generate returns slightly higher than index
        $returns[] = (rand(-300, 800) / 100) + 0.5;
    }
    return $returns;
}

/**
 * Calculate cumulative returns from periodic returns
 */
function calculateCumulativeReturns($returns) {
    $cumulative = [100]; // Start at 100
    $value = 100;
    
    foreach ($returns as $return) {
        $value *= (1 + ($return / 100));
        $cumulative[] = $value;
    }
    
    return $cumulative;
}
