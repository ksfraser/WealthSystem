<?php
/**
 * Stock Analysis API Endpoint
 * 
 * Handles AJAX requests for stock analysis, LLM integration, and data updates
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/StockPriceService.php';
require_once __DIR__ . '/LLMAnalysisService.php';

// Check authentication for API calls
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

try {
    // Load configuration
    $config = require_once __DIR__ . '/config/stock_analysis.php';
    
    // Initialize services
    $stockDAO = new StockDAO($pdo, $config['database']);
    $priceService = new StockPriceService($stockDAO, $config['price_service']);
    $analysisService = new LLMAnalysisService($stockDAO, $config['llm']);
    
    $action = $_POST['action'] ?? '';
    $symbol = strtoupper(trim($_POST['symbol'] ?? ''));
    
    if (empty($symbol) && !in_array($action, ['service_status', 'bulk_analysis'])) {
        throw new Exception('Stock symbol is required');
    }
    
    switch ($action) {
        
        case 'generate_analysis':
            // Generate comprehensive LLM analysis
            $analysis = $analysisService->generateStockAnalysis($symbol);
            if ($analysis) {
                echo json_encode([
                    'success' => true,
                    'data' => $analysis,
                    'message' => 'Analysis generated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to generate analysis'
                ]);
            }
            break;
            
        case 'analyze_sentiment':
            // Analyze news sentiment
            $newsItems = $stockDAO->getNews($symbol, 10);
            $sentiment = $analysisService->analyzeNewsSentiment($symbol, $newsItems);
            
            echo json_encode([
                'success' => true,
                'data' => $sentiment,
                'message' => 'Sentiment analysis completed'
            ]);
            break;
            
        case 'news_impact':
            // Analyze news impact
            $impact = $analysisService->analyzeNewsImpact($symbol);
            
            echo json_encode([
                'success' => true,
                'data' => $impact,
                'message' => 'News impact analysis completed'
            ]);
            break;
            
        case 'investment_thesis':
            // Generate investment thesis
            $thesis = $analysisService->generateInvestmentThesis($symbol);
            
            if ($thesis) {
                echo json_encode([
                    'success' => true,
                    'data' => $thesis,
                    'message' => 'Investment thesis generated'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to generate investment thesis'
                ]);
            }
            break;
            
        case 'update_price_data':
            // Update price data using Python service
            $result = $priceService->fetchCurrentPrice($symbol);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Price data updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to update price data'
                ]);
            }
            break;
            
        case 'fetch_historical':
            // Fetch historical price data
            $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            
            $historical = $priceService->fetchHistoricalPrices($symbol, $startDate, $endDate);
            
            echo json_encode([
                'success' => true,
                'data' => $historical,
                'count' => count($historical),
                'message' => 'Historical data fetched successfully'
            ]);
            break;
            
        case 'calculate_technicals':
            // Calculate technical indicators using Python service
            $period = intval($_POST['period'] ?? 100);
            $indicators = $priceService->calculateTechnicalIndicators($symbol, $period);
            
            if ($indicators) {
                echo json_encode([
                    'success' => true,
                    'data' => $indicators,
                    'message' => 'Technical indicators calculated'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to calculate technical indicators'
                ]);
            }
            break;
            
        case 'get_realtime_quote':
            // Get real-time quote
            $quote = $priceService->getRealTimeQuote($symbol);
            
            if ($quote) {
                echo json_encode([
                    'success' => true,
                    'data' => $quote,
                    'message' => 'Real-time quote retrieved'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to get real-time quote'
                ]);
            }
            break;
            
        case 'full_analysis_update':
            // Complete analysis update (price + news + analysis)
            $results = [
                'price_update' => false,
                'sentiment_analysis' => false,
                'llm_analysis' => false,
                'news_impact' => false
            ];
            
            // Update price data
            $priceResult = $priceService->fetchCurrentPrice($symbol);
            $results['price_update'] = $priceResult !== null;
            
            // Analyze sentiment
            $newsItems = $stockDAO->getNews($symbol, 15);
            if (!empty($newsItems)) {
                $sentiment = $analysisService->analyzeNewsSentiment($symbol, $newsItems);
                $results['sentiment_analysis'] = !empty($sentiment['sentiment_score']);
                
                $impact = $analysisService->analyzeNewsImpact($symbol);
                $results['news_impact'] = !empty($impact['impact_score']);
            }
            
            // Generate LLM analysis
            $analysis = $analysisService->generateStockAnalysis($symbol);
            $results['llm_analysis'] = $analysis !== null;
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'message' => 'Full analysis update completed',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'bulk_analysis':
            // Bulk analysis for multiple stocks
            $symbols = array_map('strtoupper', array_map('trim', explode(',', $_POST['symbols'] ?? '')));
            $symbols = array_filter($symbols);
            
            if (empty($symbols)) {
                throw new Exception('No symbols provided');
            }
            
            if (count($symbols) > $config['security']['max_symbols_per_request']) {
                throw new Exception('Too many symbols requested');
            }
            
            $results = [];
            foreach ($symbols as $sym) {
                try {
                    $analysis = $analysisService->generateStockAnalysis($sym);
                    $results[$sym] = [
                        'success' => $analysis !== null,
                        'data' => $analysis
                    ];
                } catch (Exception $e) {
                    $results[$sym] = [
                        'success' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $results,
                'message' => 'Bulk analysis completed',
                'processed' => count($symbols)
            ]);
            break;
            
        case 'service_status':
            // Get service status for all components
            $status = [
                'price_service' => $priceService->getServiceStatus(),
                'llm_service' => $analysisService->getServiceStatus(),
                'database' => [
                    'connected' => $pdo !== null,
                    'tables_available' => true // Could add actual table checks
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $status,
                'message' => 'Service status retrieved'
            ]);
            break;
            
        case 'validate_symbol':
            // Validate if a symbol is tradeable
            $quote = $priceService->getRealTimeQuote($symbol);
            $isValid = $quote !== null && isset($quote['price']);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'symbol' => $symbol,
                    'is_valid' => $isValid,
                    'quote' => $quote
                ],
                'message' => $isValid ? 'Symbol is valid' : 'Symbol not found or invalid'
            ]);
            break;
            
        case 'get_analysis_history':
            // Get analysis history for a stock
            try {
                $sql = "SELECT * FROM `{$symbol}_analysis` ORDER BY analysis_date DESC LIMIT 10";
                $stmt = $pdo->query($sql);
                $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $history,
                    'count' => count($history),
                    'message' => 'Analysis history retrieved'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => 'No analysis history found'
                ]);
            }
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    error_log("Stock Analysis API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Helper function to log API requests
 */
function logApiRequest($action, $symbol, $success, $executionTime = null) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'symbol' => $symbol,
        'success' => $success,
        'execution_time' => $executionTime,
        'user_id' => $_SESSION['user']['id'] ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
    ];
    
    error_log("API Request: " . json_encode($logData));
}