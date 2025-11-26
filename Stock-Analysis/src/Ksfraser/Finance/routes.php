<?php
/**
 * Finance System Router
 * 
 * Routes requests for the trading system UI and API endpoints
 */

require_once __DIR__ . '/../Container.php';
require_once __DIR__ . '/../../FinanceIntegration.php';

use Ksfraser\Finance\Container;
use Ksfraser\Finance\Controllers\TradingDashboardController;
use Ksfraser\Finance\Integration\StrategyIntegration;

// Initialize the container
$container = new Container();

// Get controller from container
$dashboardController = $container->get(TradingDashboardController::class);
$strategyIntegration = $container->get(StrategyIntegration::class);

// Handle the request
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Set content type for API responses
if (strpos($path, '/api/') !== false) {
    header('Content-Type: application/json');
}

try {
    switch ($path) {
        // Dashboard page
        case '/finance/dashboard':
            if ($requestMethod === 'GET') {
                include __DIR__ . '/../Views/dashboard.php';
            }
            break;

        // API Endpoints
        case '/finance/api/dashboard':
            if ($requestMethod === 'GET') {
                $result = $dashboardController->dashboard();
                echo json_encode($result);
            }
            break;

        case '/finance/api/strategies':
            if ($requestMethod === 'GET') {
                $result = $dashboardController->strategiesList();
                echo json_encode($result);
            }
            break;

        case '/finance/api/execute-strategy':
            if ($requestMethod === 'POST') {
                $result = $dashboardController->executeStrategy();
                echo json_encode($result);
            }
            break;

        case '/finance/api/backtest':
            if ($requestMethod === 'POST') {
                $result = $dashboardController->backtestStrategy();
                echo json_encode($result);
            }
            break;

        case '/finance/api/update-strategy':
            if ($requestMethod === 'POST') {
                $result = $dashboardController->updateStrategySettings();
                echo json_encode($result);
            }
            break;

        case '/finance/api/market-data':
            if ($requestMethod === 'GET') {
                $result = $dashboardController->getMarketData();
                echo json_encode($result);
            }
            break;

        // Strategy Management Page
        case '/finance/strategies':
            include __DIR__ . '/../Views/strategies.php';
            break;

        // Backtesting Page
        case '/finance/backtest':
            include __DIR__ . '/../Views/backtest.php';
            break;

        // Portfolio Page
        case '/finance/portfolio':
            include __DIR__ . '/../Views/portfolio.php';
            break;

        // Market Data Page
        case '/finance/market-data':
            include __DIR__ . '/../Views/market-data.php';
            break;

        // Strategy Integration API
        case '/finance/api/scan-market':
            if ($requestMethod === 'POST') {
                $symbols = json_decode(file_get_contents('php://input'), true)['symbols'] ?? [];
                $strategyIds = json_decode(file_get_contents('php://input'), true)['strategy_ids'] ?? [];
                
                $result = $strategyIntegration->scanMarket($symbols, $strategyIds);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        // Default redirect to dashboard
        case '/finance':
        case '/finance/':
            header('Location: /finance/dashboard');
            break;

        default:
            http_response_code(404);
            if (strpos($path, '/api/') !== false) {
                echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
            } else {
                echo '<h1>404 - Page Not Found</h1>';
            }
            break;
    }

} catch (Exception $e) {
    error_log("Finance System Error: " . $e->getMessage());
    
    if (strpos($path, '/api/') !== false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Internal server error: ' . $e->getMessage()
        ]);
    } else {
        http_response_code(500);
        echo '<h1>500 - Internal Server Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    }
}
