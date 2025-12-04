<?php
/**
 * Sector Analysis API Endpoint
 * 
 * Returns portfolio sector analysis data in JSON format.
 * 
 * Query Parameters:
 * - user_id: User ID (required)
 * 
 * Response Format:
 * {
 *   "success": true,
 *   "diversification_score": 75.5,
 *   "concentration_risk": {...},
 *   "pie_chart": {...},
 *   "comparison_chart": {...},
 *   "benchmark_comparison": {...}
 * }
 * 
 * @version 1.0.0
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use App\Services\SectorAnalysisChartService;
use App\DAO\SectorAnalysisDAOImpl;

try {
    // Validate user_id parameter
    if (!isset($_GET['user_id'])) {
        throw new InvalidArgumentException('user_id parameter is required');
    }
    
    $userId = (int) $_GET['user_id'];
    
    if ($userId <= 0) {
        throw new InvalidArgumentException('Invalid user_id');
    }
    
    // Get database connection
    $pdo = getDbConnection();
    
    // Create DAO and service
    $dao = new SectorAnalysisDAOImpl($pdo);
    $service = new SectorAnalysisChartService($dao);
    
    // Get complete sector analysis
    $analysis = $service->getPortfolioSectorAnalysis($userId);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $analysis
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
        'error' => 'Unexpected error occurred'
    ]);
}
