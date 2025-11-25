<?php

namespace App\Services;

/**
 * Stock Analysis Service
 * 
 * Orchestrates stock analysis by integrating data fetching with AI-powered analysis.
 * This service coordinates between:
 * - MarketDataService (PHP) - Data fetching and caching
 * - PythonIntegrationService (PHP) - Bridge to Python
 * - python_analysis/analysis.py (Python) - AI/statistical analysis
 * 
 * Architecture:
 * PHP handles:
 *   - Data fetching and caching
 *   - Business rules and validation
 *   - Database persistence
 *   - Result formatting and presentation
 * 
 * Python handles:
 *   - AI/Machine Learning analysis
 *   - Complex statistical calculations
 *   - Technical indicator computations
 */
class StockAnalysisService
{
    private MarketDataService $marketDataService;
    private PythonIntegrationService $pythonService;
    private array $config;
    
    // Default scoring weights for analysis dimensions
    private const DEFAULT_WEIGHTS = [
        'fundamental' => 0.40,  // 40% weight
        'technical' => 0.30,    // 30% weight
        'momentum' => 0.20,     // 20% weight
        'sentiment' => 0.10     // 10% weight
    ];
    
    public function __construct(
        MarketDataService $marketDataService,
        array $config = []
    ) {
        $this->marketDataService = $marketDataService;
        $this->config = $config;
        
        // Initialize Python integration service
        $pythonPath = $config['python_path'] ?? 'python';
        $this->pythonService = new PythonIntegrationService($pythonPath);
    }
    
    /**
     * Analyze a single stock
     * 
     * @param string $symbol Stock ticker symbol
     * @param array $options Analysis options
     * @return array Analysis results including scores and recommendation
     */
    public function analyzeStock(string $symbol, array $options = []): array
    {
        try {
            // Step 1: Fetch stock data (PHP MarketDataService)
            $stockData = $this->fetchStockData($symbol, $options);
            
            if (!$stockData['success']) {
                return [
                    'success' => false,
                    'error' => $stockData['error'] ?? 'Failed to fetch stock data',
                    'symbol' => $symbol
                ];
            }
            
            // Step 2: Prepare data for Python analysis
            $analysisInput = $this->prepareAnalysisInput($symbol, $stockData, $options);
            
            // Step 3: Call Python for AI/statistical analysis
            $analysisResult = $this->performAIAnalysis($analysisInput);
            
            if (!$analysisResult['success']) {
                return [
                    'success' => false,
                    'error' => $analysisResult['error'] ?? 'Analysis failed',
                    'symbol' => $symbol
                ];
            }
            
            // Step 4: Enhance with business rules and formatting (PHP)
            $finalResult = $this->enhanceAnalysisResult($analysisResult['data'], $options);
            
            // Step 5: Cache/persist results if configured (PHP)
            if ($options['persist'] ?? false) {
                $this->persistAnalysisResult($symbol, $finalResult);
            }
            
            return [
                'success' => true,
                'data' => $finalResult
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Analysis exception: ' . $e->getMessage(),
                'symbol' => $symbol
            ];
        }
    }
    
    /**
     * Analyze multiple stocks
     * 
     * @param array $symbols Array of stock ticker symbols
     * @param array $options Analysis options
     * @return array Array of analysis results
     */
    public function analyzeMultipleStocks(array $symbols, array $options = []): array
    {
        $results = [];
        
        foreach ($symbols as $symbol) {
            $results[$symbol] = $this->analyzeStock($symbol, $options);
        }
        
        return $results;
    }
    
    /**
     * Get quick analysis (cached or minimal)
     * 
     * @param string $symbol Stock ticker symbol
     * @return array Quick analysis results
     */
    public function getQuickAnalysis(string $symbol): array
    {
        // Check cache first
        $cached = $this->getCachedAnalysis($symbol);
        if ($cached) {
            return [
                'success' => true,
                'data' => $cached,
                'cached' => true
            ];
        }
        
        // Perform minimal analysis
        return $this->analyzeStock($symbol, [
            'quick_mode' => true,
            'persist' => true
        ]);
    }
    
    /**
     * Fetch stock data from MarketDataService
     * 
     * @param string $symbol Stock ticker
     * @param array $options Fetch options
     * @return array Stock data including prices and fundamentals
     */
    private function fetchStockData(string $symbol, array $options): array
    {
        try {
            // Fetch historical prices (default: 1 year)
            $period = $options['period'] ?? '1y';
            $historicalPrices = $this->marketDataService->getHistoricalPrices(
                $symbol,
                $this->calculateStartDate($period)
            );
            
            if (empty($historicalPrices)) {
                return [
                    'success' => false,
                    'error' => 'No price data available for ' . $symbol
                ];
            }
            
            // Fetch current price
            $currentPrice = $this->marketDataService->getCurrentPrice($symbol);
            
            // Fetch fundamentals (if available)
            $fundamentals = $this->marketDataService->getFundamentals($symbol) ?? [];
            
            return [
                'success' => true,
                'historical_prices' => $historicalPrices,
                'current_price' => $currentPrice,
                'fundamentals' => $fundamentals
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Data fetch error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Prepare data for Python analysis
     * 
     * @param string $symbol Stock ticker
     * @param array $stockData Stock data from MarketDataService
     * @param array $options Analysis options
     * @return array Formatted data for Python
     */
    private function prepareAnalysisInput(string $symbol, array $stockData, array $options): array
    {
        // Convert PHP price data to format Python expects
        $priceData = [];
        foreach ($stockData['historical_prices'] as $price) {
            $priceData[] = [
                'date' => $price['date'],
                'open' => (float)$price['open'],
                'high' => (float)$price['high'],
                'low' => (float)$price['low'],
                'close' => (float)$price['close'],
                'volume' => (int)$price['volume']
            ];
        }
        
        // Get scoring weights from options or use defaults
        $weights = $options['scoring_weights'] ?? self::DEFAULT_WEIGHTS;
        
        return [
            'symbol' => $symbol,
            'price_data' => $priceData,
            'fundamentals' => $stockData['fundamentals'],
            'scoring_weights' => $weights
        ];
    }
    
    /**
     * Perform AI/statistical analysis using Python
     * 
     * @param array $input Analysis input data
     * @return array Python analysis results
     */
    private function performAIAnalysis(array $input): array
    {
        try {
            // Build command to execute Python analysis
            $pythonScript = dirname(__DIR__, 2) . '/python_analysis/analysis.py';
            
            if (!file_exists($pythonScript)) {
                return [
                    'success' => false,
                    'error' => 'Python analysis module not found at: ' . $pythonScript
                ];
            }
            
            // Encode input as JSON
            $jsonInput = json_encode($input);
            
            // Execute Python script
            $command = sprintf(
                '%s "%s" analyze %s 2>&1',
                $this->config['python_path'] ?? 'python',
                $pythonScript,
                escapeshellarg($jsonInput)
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                return [
                    'success' => false,
                    'error' => 'Python execution failed: ' . implode("\n", $output)
                ];
            }
            
            // Parse JSON output from Python
            $outputJson = implode("\n", $output);
            $result = json_decode($outputJson, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'error' => 'Invalid JSON from Python: ' . $outputJson
                ];
            }
            
            // Check for errors in Python result
            if (isset($result['error'])) {
                return [
                    'success' => false,
                    'error' => 'Python analysis error: ' . $result['error']
                ];
            }
            
            return [
                'success' => true,
                'data' => $result
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Python integration error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Enhance Python analysis results with business rules
     * 
     * @param array $pythonResult Results from Python
     * @param array $options Enhancement options
     * @return array Enhanced results
     */
    private function enhanceAnalysisResult(array $pythonResult, array $options): array
    {
        // Add display-friendly formatting
        $enhanced = $pythonResult;
        
        // Format recommendation with color coding
        $enhanced['recommendation_display'] = $this->formatRecommendation(
            $pythonResult['recommendation']
        );
        
        // Add risk warning if needed
        if ($pythonResult['risk_level'] === 'HIGH' || $pythonResult['risk_level'] === 'VERY_HIGH') {
            $enhanced['risk_warning'] = 'This stock carries significant risk. Invest cautiously.';
        }
        
        // Calculate potential return
        if (isset($pythonResult['target_price']) && isset($pythonResult['current_price'])) {
            $enhanced['potential_return'] = (
                ($pythonResult['target_price'] - $pythonResult['current_price']) / 
                $pythonResult['current_price']
            ) * 100;
            $enhanced['potential_return_formatted'] = number_format($enhanced['potential_return'], 2) . '%';
        }
        
        // Add timestamp
        $enhanced['analyzed_at'] = date('Y-m-d H:i:s');
        
        return $enhanced;
    }
    
    /**
     * Format recommendation for display
     * 
     * @param string $recommendation Raw recommendation
     * @return array Formatted recommendation with metadata
     */
    private function formatRecommendation(string $recommendation): array
    {
        $formats = [
            'STRONG_BUY' => ['label' => 'Strong Buy', 'color' => 'success', 'icon' => '↑↑'],
            'BUY' => ['label' => 'Buy', 'color' => 'success', 'icon' => '↑'],
            'HOLD' => ['label' => 'Hold', 'color' => 'warning', 'icon' => '→'],
            'SELL' => ['label' => 'Sell', 'color' => 'danger', 'icon' => '↓'],
            'STRONG_SELL' => ['label' => 'Strong Sell', 'color' => 'danger', 'icon' => '↓↓']
        ];
        
        return $formats[$recommendation] ?? [
            'label' => $recommendation,
            'color' => 'secondary',
            'icon' => '?'
        ];
    }
    
    /**
     * Persist analysis result to database
     * 
     * @param string $symbol Stock ticker
     * @param array $result Analysis result
     * @return bool Success status
     */
    private function persistAnalysisResult(string $symbol, array $result): bool
    {
        // TODO: Implement database persistence via Repository
        // For now, just log
        error_log("Analysis result for {$symbol}: " . $result['recommendation']);
        return true;
    }
    
    /**
     * Get cached analysis if available
     * 
     * @param string $symbol Stock ticker
     * @return array|null Cached analysis or null
     */
    private function getCachedAnalysis(string $symbol): ?array
    {
        // TODO: Implement cache lookup via Repository
        // For now, return null (no cache)
        return null;
    }
    
    /**
     * Calculate start date based on period string
     * 
     * @param string $period Period string (e.g., '1y', '6m', '3m')
     * @return string Start date in Y-m-d format
     */
    private function calculateStartDate(string $period): string
    {
        $interval = match($period) {
            '1y' => '-1 year',
            '6m' => '-6 months',
            '3m' => '-3 months',
            '1m' => '-1 month',
            default => '-1 year'
        };
        
        return date('Y-m-d', strtotime($interval));
    }
}
