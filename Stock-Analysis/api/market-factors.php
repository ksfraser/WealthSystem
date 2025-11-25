<?php
/**
 * Market Factors API Endpoint
 * 
 * Provides REST API access to market factors data
 */

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../../DatabaseConfig.php';
require_once __DIR__ . '/src/Ksfraser/Finance/MarketFactors/Controllers/MarketFactorsController.php';

use Ksfraser\Finance\MarketFactors\Controllers\MarketFactorsController;

try {
    // Get database connection
    $pdo = DatabaseConfig::createLegacyConnection();
    
    if (!$pdo) {
        throw new Exception("Failed to connect to database");
    }
    
    // Create controller and handle request
    $controller = new MarketFactorsController($pdo);
    $controller->handleRequest();
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
