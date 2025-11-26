<?php
/**
 * Web API Example for Finance Package
 * 
 * Simple web interface demonstrating the Finance package integration.
 * This can be integrated into the existing web_ui system.
 */

require_once __DIR__ . '/../../../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\Finance\DI\Container;

// Load configuration from existing DatabaseConfig system
try {
    $config = DatabaseConfig::getFinanceConfig();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Configuration error: ' . $e->getMessage()
    ]);
    exit;
}

// Create container
$container = new Container($config);

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathSegments = explode('/', trim($path, '/'));

try {
    $stockController = $container->get('stock_controller');
    $response = ['success' => false, 'error' => 'Invalid request'];

    // Route handling
    switch ($method) {
        case 'GET':
            if (isset($pathSegments[0])) {
                switch ($pathSegments[0]) {
                    case 'overview':
                        $response = $stockController->getMarketOverview();
                        break;
                        
                    case 'stock':
                        if (isset($pathSegments[1])) {
                            $symbol = strtoupper($pathSegments[1]);
                            if (isset($pathSegments[2]) && $pathSegments[2] === 'analysis') {
                                $response = $stockController->getAnalysis($symbol);
                            } elseif (isset($pathSegments[2]) && $pathSegments[2] === 'history') {
                                $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
                                $response = $stockController->getHistory($symbol, $days);
                            } else {
                                $response = ['success' => false, 'error' => 'Invalid stock endpoint'];
                            }
                        }
                        break;
                        
                    default:
                        $response = ['success' => false, 'error' => 'Endpoint not found'];
                }
            }
            break;
            
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (isset($pathSegments[0])) {
                switch ($pathSegments[0]) {
                    case 'update':
                        if (isset($input['symbol'])) {
                            $response = $stockController->updateStock($input['symbol']);
                        } elseif (isset($input['symbols'])) {
                            $response = $stockController->bulkUpdate($input['symbols']);
                        } else {
                            $response = ['success' => false, 'error' => 'Symbol or symbols required'];
                        }
                        break;
                        
                    default:
                        $response = ['success' => false, 'error' => 'POST endpoint not found'];
                }
            }
            break;
            
        default:
            $response = ['success' => false, 'error' => 'Method not allowed'];
            http_response_code(405);
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ];
    http_response_code(500);
}

// Output response
echo json_encode($response, JSON_PRETTY_PRINT);

/*
API Endpoints:

GET /overview - Get market overview
GET /stock/{SYMBOL}/analysis - Get AI analysis for stock
GET /stock/{SYMBOL}/history?days=30 - Get historical data
POST /update - Update single stock: {"symbol": "AAPL"}
POST /update - Bulk update: {"symbols": ["AAPL", "GOOGL", "MSFT"]}

Examples:
GET /overview
GET /stock/AAPL/analysis
GET /stock/AAPL/history?days=7
POST /update with body: {"symbol": "AAPL"}
POST /update with body: {"symbols": ["AAPL", "GOOGL"]}
*/
