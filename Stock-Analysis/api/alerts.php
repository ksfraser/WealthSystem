<?php
/**
 * Alerts API Endpoint
 * 
 * Provides alert system functionality for portfolio monitoring
 * 
 * @package API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../config/cache.php';

use App\Services\AlertService;
use App\DAO\SectorAnalysisDAOImpl;
use App\Security\InputValidator;

try {
    // Validate input parameters
    $validator = new InputValidator($_GET);
    
    $action = $validator->required('action')
        ->string()
        ->in(['generate', 'get_active', 'mark_read', 'dismiss', 'clear_all'])
        ->getValue();
    
    $userId = $validator->required('user_id')
        ->int()
        ->min(1)
        ->getValue();
    
    // Check for validation errors
    if ($validator->hasErrors()) {
        throw new InvalidArgumentException($validator->getFirstError());
    }
    
    // Initialize services
    $pdo = DatabaseConfig::createLegacyConnection();
    $dao = new SectorAnalysisDAOImpl($pdo);
    $alertService = new AlertService($dao);
    $cacheService = getCacheService();
    
    // Handle request
    switch ($action) {
        case 'generate':
            $alerts = handleGenerateAlerts($alertService, $cacheService, $userId);
            echo json_encode([
                'success' => true,
                'data' => $alerts,
                'count' => count($alerts),
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'get_active':
            $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            $alerts = $alertService->getActiveAlerts($userId, $unreadOnly);
            echo json_encode([
                'success' => true,
                'data' => $alerts,
                'count' => count($alerts),
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'mark_read':
            $alertValidator = new InputValidator($_GET);
            $alertId = $alertValidator->required('alert_id')->int()->min(1)->getValue();
            if ($alertValidator->hasErrors()) {
                throw new InvalidArgumentException($alertValidator->getFirstError());
            }
            $success = $alertService->markAlertAsRead($alertId);
            echo json_encode([
                'success' => $success,
                'message' => 'Alert marked as read',
            ], JSON_PRETTY_PRINT);
            break;
            
        case 'dismiss':
            $alertId = (int)($_GET['alert_id'] ?? 0);
            if ($alertId <= 0) {
                throw new InvalidArgumentException('Valid alert_id is required');
            }
            $success = $alertService->dismissAlert($alertId);
            echo json_encode([
                'success' => $success,
                'message' => 'Alert dismissed',
            ], JSON_PRETTY_PRINT);
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
 * Handle generate alerts request with caching
 * 
 * @param AlertService $service Alert service
 * @param mixed $cache Cache service (or null)
 * @param int $userId User ID
 * @return array Alerts
 */
function handleGenerateAlerts(AlertService $service, $cache, int $userId): array
{
    // Check cache (2 minute TTL for alerts)
    $cacheKey = "alerts_{$userId}";
    $cachedAlerts = $cache ? $cache->get($cacheKey) : null;
    
    if ($cachedAlerts !== null) {
        return $cachedAlerts;
    }
    
    // Generate fresh alerts
    $alerts = $service->generateAlerts($userId);
    
    // Save alerts to database
    foreach ($alerts as $alert) {
        $service->saveAlert($userId, $alert);
    }
    
    // Cache the results
    if ($cache) {
        $cache->set($cacheKey, $alerts, 120); // 2 minutes
    }
    
    return $alerts;
}
