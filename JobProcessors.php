<?php

/**
 * Abstract Job Processor
 * Base class for all job processors
 */
abstract class AbstractJobProcessor
{
    protected $logger;
    protected $pdo;
    protected $stockDataAccess;
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection();
        $this->logger = new JobLogger('logs/job_processor.log');
        
        // Include dynamic data access
        require_once __DIR__ . '/DynamicStockDataAccess.php';
        $this->stockDataAccess = new DynamicStockDataAccess();
    }
    
    /**
     * Execute the job
     */
    abstract public function execute($jobData);
    
    /**
     * Update job progress
     */
    protected function updateProgress($jobId, $progress, $message = null)
    {
        $sql = "UPDATE ta_analysis_jobs SET progress = :progress";
        $params = ['job_id' => $jobId, 'progress' => $progress];
        
        if ($message) {
            $sql .= ", status_message = :message";
            $params['message'] = $message;
        }
        
        $sql .= " WHERE id = :job_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
}

/**
 * Technical Analysis Job Processor
 * Processes TA-Lib calculations for stocks
 */
class TechnicalAnalysisJobProcessor extends AbstractJobProcessor
{
    public function execute($jobData)
    {
        $jobId = $jobData['id'];
        $parameters = json_decode($jobData['parameters'] ?? '{}', true);
        $stockId = $parameters['stockId'] ?? null;
        
        $this->logger->info("Starting technical analysis job {$jobId} for stock {$stockId}");
        
        try {
            // Get stock data
            if ($stockId) {
                $result = $this->analyzeStock($stockId, $jobId);
            } else {
                $result = $this->analyzeAllStocks($jobId);
            }
            
            $this->updateProgress($jobId, 100, 'Analysis completed');
            
            return [
                'success' => true,
                'processed_stocks' => $result['processed_stocks'] ?? 0,
                'indicators_calculated' => $result['indicators_calculated'] ?? 0,
                'patterns_detected' => $result['patterns_detected'] ?? 0
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Technical analysis job {$jobId} failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Analyze a single stock
     */
    private function analyzeStock($stockId, $jobId)
    {
        require_once __DIR__ . '/Stock-Analysis-Extension/Legacy/vendor/autoload.php';

        $stockModel = new \Ksfraser\StockInfo\StockInfo($this->pdo);

        $stock = $stockModel->find($stockId);
        if (!$stock) {
            throw new Exception("Stock not found: {$stockId}");
        }

        $symbol = $stock['stocksymbol'];
        $this->updateProgress($jobId, 10, "Analyzing {$symbol}");

        // Get historical price data using dynamic table system
        $priceData = $this->stockDataAccess->getPriceDataForAnalysis($symbol, 200);

        if (empty($priceData)) {
            throw new Exception("No price data available for {$symbol}");
        }

        $indicatorsCalculated = 0;
        $patternsDetected = 0;

        // Calculate RSI
        $this->updateProgress($jobId, 20, "Calculating RSI for {$symbol}");
        $rsiValues = $this->calculateRSI($priceData);
        $indicatorsCalculated += $this->saveIndicators($symbol, 'RSI', $rsiValues);

        // Calculate MACD
        $this->updateProgress($jobId, 40, "Calculating MACD for {$symbol}");
        $macdValues = $this->calculateMACD($priceData);
        $indicatorsCalculated += $this->saveIndicators($symbol, 'MACD', $macdValues);

        // Calculate Moving Averages
        $this->updateProgress($jobId, 60, "Calculating Moving Averages for {$symbol}");
        $smaValues = $this->calculateSMA($priceData, 20);
        $indicatorsCalculated += $this->saveIndicators($symbol, 'SMA_20', $smaValues);

        // Detect Candlestick Patterns
        $this->updateProgress($jobId, 80, "Detecting Candlestick Patterns for {$symbol}");
        $patterns = $this->detectCandlestickPatterns($priceData);
        $patternsDetected = $this->savePatterns($symbol, $patterns);

        return [
            'processed_stocks' => 1,
            'indicators_calculated' => $indicatorsCalculated,
            'patterns_detected' => $patternsDetected
        ];
    }    /**
     * Analyze all active stocks
     */
    private function analyzeAllStocks($jobId)
    {
        $stockModel = new \Ksfraser\StockInfo\StockInfo($this->pdo);
        $stocks = $stockModel->getActiveStocks();
        
        $totalStocks = count($stocks);
        $processedStocks = 0;
        $totalIndicators = 0;
        $totalPatterns = 0;
        
        foreach ($stocks as $index => $stock) {
            try {
                $result = $this->analyzeStock($stock->idstockinfo, $jobId);
                $totalIndicators += $result['indicators_calculated'];
                $totalPatterns += $result['patterns_detected'];
                $processedStocks++;
                
                $progress = (($index + 1) / $totalStocks) * 90; // Leave 10% for completion
                $this->updateProgress($jobId, $progress, "Processed {$processedStocks}/{$totalStocks} stocks");
                
            } catch (Exception $e) {
                $this->logger->warning("Failed to analyze stock {$stock->stocksymbol}: " . $e->getMessage());
            }
        }
        
        return [
            'processed_stocks' => $processedStocks,
            'indicators_calculated' => $totalIndicators,
            'patterns_detected' => $totalPatterns
        ];
    }
    
    /**
     * Get historical price data for a stock
     * This is a placeholder - you would implement actual data fetching
     */
    /**
     * Calculate RSI (Relative Strength Index)
     * Simplified implementation - you would use TA-Lib for production
     */
    private function calculateRSI($priceData, $period = 14)
    {
        $rsiValues = [];
        
        if (count($priceData) < $period + 1) {
            return $rsiValues;
        }
        
        // Calculate price changes
        $gains = [];
        $losses = [];
        
        for ($i = 1; $i < count($priceData); $i++) {
            $change = $priceData[$i]['close'] - $priceData[$i-1]['close'];
            $gains[] = $change > 0 ? $change : 0;
            $losses[] = $change < 0 ? abs($change) : 0;
        }
        
        // Calculate initial averages
        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;
        
        for ($i = $period; $i < count($priceData); $i++) {
            if ($avgLoss != 0) {
                $rs = $avgGain / $avgLoss;
                $rsi = 100 - (100 / (1 + $rs));
            } else {
                $rsi = 100;
            }
            
            $rsiValues[] = [
                'date' => $priceData[$i]['date'],
                'value' => round($rsi, 2)
            ];
            
            // Update averages for next iteration
            if ($i < count($gains)) {
                $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
                $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
            }
        }
        
        return $rsiValues;
    }
    
    /**
     * Calculate MACD (Moving Average Convergence Divergence)
     */
    private function calculateMACD($priceData, $fastPeriod = 12, $slowPeriod = 26, $signalPeriod = 9)
    {
        // Simplified MACD calculation
        $macdValues = [];
        
        if (count($priceData) < $slowPeriod) {
            return $macdValues;
        }
        
        // This is a simplified implementation
        // In production, use TA-Lib: trader_macd()
        
        return $macdValues;
    }
    
    /**
     * Calculate Simple Moving Average
     */
    private function calculateSMA($priceData, $period)
    {
        $smaValues = [];
        
        for ($i = $period - 1; $i < count($priceData); $i++) {
            $sum = 0;
            for ($j = 0; $j < $period; $j++) {
                $sum += $priceData[$i - $j]['close'];
            }
            
            $smaValues[] = [
                'date' => $priceData[$i]['date'],
                'value' => round($sum / $period, 2)
            ];
        }
        
        return $smaValues;
    }
    
    /**
     * Detect candlestick patterns
     */
    private function detectCandlestickPatterns($priceData)
    {
        $patterns = [];
        
        // Simplified pattern detection
        // In production, use TA-Lib pattern functions
        
        foreach ($priceData as $candle) {
            $bodySize = abs($candle['close'] - $candle['open']);
            $upperShadow = $candle['high'] - max($candle['open'], $candle['close']);
            $lowerShadow = min($candle['open'], $candle['close']) - $candle['low'];
            
            // Simple Doji detection
            if ($bodySize < ($candle['high'] - $candle['low']) * 0.1) {
                $patterns[] = [
                    'date' => $candle['date'],
                    'pattern' => 'DOJI',
                    'strength' => 80,
                    'signal' => 'NEUTRAL'
                ];
            }
        }
        
        return $patterns;
    }
    
    /**
     * Save calculated indicators to database
     */
    /**
     * Save calculated indicators to database using dynamic table system
     */
    private function saveIndicators($symbol, $indicatorType, $values)
    {
        $saved = 0;
        
        foreach ($values as $date => $value) {
            if ($value !== null) {
                $this->stockDataAccess->insertTechnicalIndicator($symbol, [
                    'indicator_type' => $indicatorType,
                    'date' => $date,
                    'value' => $value,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                $saved++;
            }
        }
        
        return $saved;
    }

    /**
     * Save detected patterns to database using dynamic table system
     */
    private function savePatterns($symbol, $patterns)
    {
        $saved = 0;
        
        foreach ($patterns as $date => $pattern) {
            $this->stockDataAccess->insertCandlestickPattern($symbol, [
                'pattern_type' => $pattern['type'],
                'date' => $date,
                'confidence' => $pattern['confidence'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $saved++;
        }
        
        return $saved;
    }
}

/**
 * Price Update Job Processor
 * Updates stock prices from external sources
 */
class PriceUpdateJobProcessor extends AbstractJobProcessor
{
    public function execute($jobData)
    {
        $jobId = $jobData['id'];
        $parameters = json_decode($jobData['parameters'] ?? '{}', true);
        
        $this->logger->info("Starting price update job {$jobId}");
        
        // Implementation for price updates
        $this->updateProgress($jobId, 50, "Updating prices from external sources");
        
        // Mock implementation
        sleep(2); // Simulate work
        
        $this->updateProgress($jobId, 100, "Price update completed");
        
        return ['success' => true, 'updated_stocks' => 100];
    }
}

/**
 * Data Import Job Processor
 * Handles bulk data imports
 */
class DataImportJobProcessor extends AbstractJobProcessor
{
    public function execute($jobData)
    {
        $jobId = $jobData['id'];
        $parameters = json_decode($jobData['parameters'] ?? '{}', true);
        
        $this->logger->info("Starting data import job {$jobId}");
        
        // Implementation for data imports
        $this->updateProgress($jobId, 50, "Importing data");
        
        // Mock implementation
        sleep(3); // Simulate work
        
        $this->updateProgress($jobId, 100, "Data import completed");
        
        return ['success' => true, 'imported_records' => 500];
    }
}

/**
 * Portfolio Analysis Job Processor
 * Analyzes portfolio performance and risk
 */
class PortfolioAnalysisJobProcessor extends AbstractJobProcessor
{
    public function execute($jobData)
    {
        $jobId = $jobData['id'];
        $parameters = json_decode($jobData['parameters'] ?? '{}', true);
        
        $this->logger->info("Starting portfolio analysis job {$jobId}");
        
        // Implementation for portfolio analysis
        $this->updateProgress($jobId, 50, "Analyzing portfolio performance");
        
        // Mock implementation
        sleep(4); // Simulate work
        
        $this->updateProgress($jobId, 100, "Portfolio analysis completed");
        
        return ['success' => true, 'portfolios_analyzed' => 1];
    }
}
