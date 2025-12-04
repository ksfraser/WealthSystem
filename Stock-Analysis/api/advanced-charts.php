<?php
/**
 * Advanced Charts API Endpoint
 * 
 * Provides REST API access to advanced chart data including:
 * - Correlation heatmaps
 * - Portfolio treemaps
 * - Historical sector trends
 * - Concentration trends (HHI)
 * - Rebalancing suggestions
 * 
 * @package API
 */

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../config/cache.php';

use App\Services\AdvancedChartService;
use App\DAO\SectorAnalysisDAOImpl;

try {
    // Get database connection
    $pdo = DatabaseConfig::createLegacyConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to connect to database");
    }
    
    // Get action parameter
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new InvalidArgumentException('Action parameter is required');
    }
    
    // Initialize services
    $dao = new SectorAnalysisDAOImpl($pdo);
    $chartService = new AdvancedChartService($dao);
    $cacheService = getCacheService(); // Returns CacheService or null if Redis unavailable
    
    // Route to appropriate handler
    switch ($action) {
        case 'correlation':
            handleCorrelationRequest($chartService, $cacheService);
            break;
            
        case 'treemap':
            handleTreemapRequest($chartService, $cacheService);
            break;
            
        case 'trends':
            handleTrendsRequest($chartService, $cacheService);
            break;
            
        case 'concentration':
            handleConcentrationRequest($chartService, $cacheService);
            break;
            
        case 'rebalancing':
            handleRebalancingRequest($chartService, $cacheService);
            break;
            
        default:
            throw new InvalidArgumentException("Unknown action: {$action}");
    }
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

/**
 * Handle correlation heatmap request
 */
function handleCorrelationRequest(AdvancedChartService $service, $cache): void
{
    $userId = (int)($_GET['user_id'] ?? 0);
    $period = $_GET['period'] ?? '1y'; // 1w, 1m, 3m, 6m, 1y, 3y, 5y
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user_id is required');
    }
    
    // Check cache first (5 minute TTL for correlation data)
    $cacheKey = "correlation_heatmap_{$userId}_{$period}";
    $cachedData = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Calculate date range based on period
    $endDate = new DateTime();
    $startDate = clone $endDate;
    
    switch ($period) {
        case '1w': $startDate->modify('-1 week'); break;
        case '1m': $startDate->modify('-1 month'); break;
        case '3m': $startDate->modify('-3 months'); break;
        case '6m': $startDate->modify('-6 months'); break;
        case '1y': $startDate->modify('-1 year'); break;
        case '3y': $startDate->modify('-3 years'); break;
        case '5y': $startDate->modify('-5 years'); break;
        default: $startDate->modify('-1 year');
    }
    
    // Get sector returns data
    // Note: This would need to be implemented in the DAO
    // For now, using placeholder data structure
    $sectorReturns = getSectorReturnsForPeriod($userId, $startDate, $endDate);
    
    // Generate heatmap
    $heatmapData = $service->generateCorrelationHeatmap($sectorReturns);
    
    // Cache the result
    if ($cache) {
        $cache->set($cacheKey, $heatmapData, 300); // 5 minutes
    }
    
    echo json_encode([
        'success' => true,
        'data' => $heatmapData,
        'cached' => false
    ], JSON_PRETTY_PRINT);
}

/**
 * Handle portfolio treemap request
 */
function handleTreemapRequest(AdvancedChartService $service, $cache): void
{
    $userId = (int)($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user_id is required');
    }
    
    // Check cache first (2 minute TTL for portfolio data)
    $cacheKey = "portfolio_treemap_{$userId}";
    $cachedData = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Get current holdings
    $holdings = getCurrentHoldings($userId);
    
    // Generate treemap
    $treemapData = $service->generatePortfolioTreemap($holdings);
    
    // Cache the result
    if ($cache) {
        $cache->set($cacheKey, $treemapData, 120); // 2 minutes
    }
    
    echo json_encode([
        'success' => true,
        'data' => $treemapData,
        'cached' => false
    ], JSON_PRETTY_PRINT);
}

/**
 * Handle historical trends request
 */
function handleTrendsRequest(AdvancedChartService $service, $cache): void
{
    $userId = (int)($_GET['user_id'] ?? 0);
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user_id is required');
    }
    
    if (empty($startDate) || empty($endDate)) {
        throw new InvalidArgumentException('start_date and end_date are required');
    }
    
    // Validate date format
    if (!validateDate($startDate) || !validateDate($endDate)) {
        throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
    }
    
    // Check cache (10 minute TTL for historical data)
    $cacheKey = "historical_trends_{$userId}_{$startDate}_{$endDate}";
    $cachedData = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Generate trends
    $trendsData = $service->generateHistoricalSectorTrends($userId, $startDate, $endDate);
    
    // Cache the result
    if ($cache) {
        $cache->set($cacheKey, $trendsData, 600); // 10 minutes
    }
    
    echo json_encode([
        'success' => true,
        'data' => $trendsData,
        'cached' => false
    ], JSON_PRETTY_PRINT);
}

/**
 * Handle concentration trend request
 */
function handleConcentrationRequest(AdvancedChartService $service, $cache): void
{
    $userId = (int)($_GET['user_id'] ?? 0);
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user_id is required');
    }
    
    if (empty($startDate) || empty($endDate)) {
        throw new InvalidArgumentException('start_date and end_date are required');
    }
    
    // Validate date format
    if (!validateDate($startDate) || !validateDate($endDate)) {
        throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD');
    }
    
    // Check cache (10 minute TTL)
    $cacheKey = "concentration_trend_{$userId}_{$startDate}_{$endDate}";
    $cachedData = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Generate concentration trend
    $concentrationData = $service->calculateSectorConcentrationTrend($userId, $startDate, $endDate);
    
    // Cache the result
    if ($cache) {
        $cache->set($cacheKey, $concentrationData, 600); // 10 minutes
    }
    
    echo json_encode([
        'success' => true,
        'data' => $concentrationData,
        'cached' => false
    ], JSON_PRETTY_PRINT);
}

/**
 * Handle rebalancing suggestions request
 */
function handleRebalancingRequest(AdvancedChartService $service, $cache): void
{
    $userId = (int)($_GET['user_id'] ?? 0);
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Valid user_id is required');
    }
    
    // Check cache (5 minute TTL)
    $cacheKey = "rebalancing_suggestions_{$userId}";
    $cachedData = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedData !== null) {
        echo json_encode([
            'success' => true,
            'data' => $cachedData,
            'cached' => true
        ], JSON_PRETTY_PRINT);
        return;
    }
    
    // Get current and target allocations
    $currentAllocation = getCurrentSectorAllocation($userId);
    $targetAllocation = getTargetSectorAllocation($userId);
    
    // Generate suggestions
    $suggestions = $service->generateRebalancingSuggestions($currentAllocation, $targetAllocation);
    
    // Cache the result
    if ($cache) {
        $cache->set($cacheKey, $suggestions, 300); // 5 minutes
    }
    
    echo json_encode([
        'success' => true,
        'data' => $suggestions,
        'cached' => false
    ], JSON_PRETTY_PRINT);
}

/**
 * Get sector returns for a given period
 * 
 * @param int $userId User ID
 * @param DateTime $startDate Start date
 * @param DateTime $endDate End date
 * @return array Sector returns data
 */
function getSectorReturnsForPeriod(int $userId, DateTime $startDate, DateTime $endDate): array
{
    // TODO: Implement actual database query
    // This would query historical price data and calculate returns by sector
    
    // Placeholder implementation
    return [
        'Technology' => [0.05, 0.03, -0.02, 0.04, 0.06, -0.01, 0.02, 0.05, 0.03, 0.04],
        'Healthcare' => [0.02, 0.04, 0.01, 0.03, 0.02, 0.03, -0.01, 0.02, 0.04, 0.03],
        'Finance' => [0.03, -0.01, 0.02, 0.04, 0.01, 0.02, 0.03, -0.02, 0.03, 0.02],
        'Consumer' => [0.04, 0.02, 0.03, -0.01, 0.03, 0.04, 0.02, 0.03, 0.01, 0.04],
        'Industrial' => [0.01, 0.03, 0.02, 0.03, -0.02, 0.01, 0.04, 0.02, 0.03, 0.01],
    ];
}

/**
 * Get current holdings for user
 * 
 * @param int $userId User ID
 * @return array Holdings data
 */
function getCurrentHoldings(int $userId): array
{
    // TODO: Implement actual database query
    
    // Placeholder implementation
    return [
        ['symbol' => 'AAPL', 'name' => 'Apple Inc.', 'sector' => 'Technology', 'value' => 50000, 'return' => 0.15],
        ['symbol' => 'MSFT', 'name' => 'Microsoft Corp.', 'sector' => 'Technology', 'value' => 45000, 'return' => 0.12],
        ['symbol' => 'JNJ', 'name' => 'Johnson & Johnson', 'sector' => 'Healthcare', 'value' => 30000, 'return' => 0.08],
        ['symbol' => 'JPM', 'name' => 'JPMorgan Chase', 'sector' => 'Finance', 'value' => 25000, 'return' => 0.10],
        ['symbol' => 'WMT', 'name' => 'Walmart Inc.', 'sector' => 'Consumer', 'value' => 20000, 'return' => 0.06],
    ];
}

/**
 * Get current sector allocation
 * 
 * @param int $userId User ID
 * @return array Current allocation percentages
 */
function getCurrentSectorAllocation(int $userId): array
{
    // TODO: Implement actual database query
    
    // Placeholder implementation
    return [
        'Technology' => 55.88,
        'Healthcare' => 17.65,
        'Finance' => 14.71,
        'Consumer' => 11.76,
    ];
}

/**
 * Get target sector allocation
 * 
 * @param int $userId User ID
 * @return array Target allocation percentages
 */
function getTargetSectorAllocation(int $userId): array
{
    // TODO: Implement actual database query or user preferences
    
    // Placeholder implementation (example balanced allocation)
    return [
        'Technology' => 40.00,
        'Healthcare' => 20.00,
        'Finance' => 20.00,
        'Consumer' => 20.00,
    ];
}

/**
 * Validate date format (YYYY-MM-DD)
 * 
 * @param string $date Date string
 * @return bool True if valid
 */
function validateDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
