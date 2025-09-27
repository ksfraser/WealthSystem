#!/usr/bin/env php
<?php
/**
 * Stock Data Job Worker
 * Processes jobs from the MQTT priority queue system
 */

require_once __DIR__ . '/JobProcessor.php';
require_once __DIR__ . '/UserPortfolioJobManager.php';
require_once __DIR__ . '/web_ui/StockDAO.php';
require_once __DIR__ . '/web_ui/includes/config.php';

// Configuration
$configFile = __DIR__ . '/stock_job_processor.yml';
$workerName = 'StockDataWorker-' . gethostname();

echo "Starting Stock Data Job Worker: {$workerName}\n";
echo "Configuration: {$configFile}\n";

// Load configuration
if (!file_exists($configFile)) {
    die("Configuration file not found: {$configFile}\n");
}

$config = null;
if (function_exists('yaml_parse_file')) {
    $config = yaml_parse_file($configFile);
} else {
    die("YAML extension required. Install with: sudo apt-get install php-yaml\n");
}

// Initialize logger
class StockJobLogger {
    private $logFile;
    
    public function __construct($logFile) {
        $this->logFile = $logFile;
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    public function info($message) {
        $this->log('INFO', $message);
    }
    
    public function warning($message) {
        $this->log('WARNING', $message);
    }
    
    public function error($message) {
        $this->log('ERROR', $message);
    }
    
    public function debug($message) {
        $this->log('DEBUG', $message);
    }
    
    private function log($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logLine = "[{$timestamp}] [{$level}] {$message}\n";
        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
        echo $logLine; // Also output to console
    }
}

$logger = new StockJobLogger($config['job_processor']['logging']['file'] ?? 'logs/stock_worker.log');

try {
    // Initialize components
    $stockDAO = new StockDAO($db);
    $portfolioJobManager = new UserPortfolioJobManager($config['job_processor'], $logger, $db);
    
    // Initialize job processor
    $jobProcessor = new JobProcessor($configFile);
    
    // Register job handlers
    registerJobHandlers($jobProcessor, $stockDAO, $portfolioJobManager, $logger);
    
    $logger->info("Stock Data Job Worker started successfully");
    
    // Start processing jobs
    $jobProcessor->start();
    
} catch (Exception $e) {
    $logger->error("Failed to start worker: " . $e->getMessage());
    exit(1);
}

/**
 * Register job handlers for different job types
 */
function registerJobHandlers($jobProcessor, $stockDAO, $portfolioJobManager, $logger) {
    
    // Stock data fetch job handler
    $jobProcessor->registerJobHandler('stock_fetch', function($job) use ($stockDAO, $logger) {
        $symbol = $job['parameters']['symbol'] ?? null;
        $days = $job['parameters']['days'] ?? 1;
        $source = $job['parameters']['source'] ?? 'yahoo_finance';
        
        if (!$symbol) {
            throw new Exception('Stock symbol is required for fetch job');
        }
        
        $logger->info("Processing stock fetch job for {$symbol} ({$days} days)");
        
        // Execute Python fetch script
        $command = sprintf(
            'cd "%s" && python fetch_historical_data.py "%s" %d 2>&1',
            dirname(__DIR__),
            escapeshellarg($symbol),
            $days
        );
        
        $output = shell_exec($command);
        
        if ($output === null) {
            throw new Exception('Failed to execute Python fetch script');
        }
        
        // Check for errors in output
        if (strpos($output, 'Error:') !== false || strpos($output, 'Traceback') !== false) {
            throw new Exception('Python script error: ' . trim($output));
        }
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
            throw new Exception('Invalid JSON response from Python script');
        }
        
        // Store data using StockDAO
        $recordCount = 0;
        foreach ($data['data'] as $record) {
            $stockDAO->upsertPriceData($symbol, [
                'date' => $record['Date'],
                'open' => $record['Open'],
                'high' => $record['High'],
                'low' => $record['Low'],
                'close' => $record['Close'],
                'volume' => $record['Volume']
            ]);
            $recordCount++;
        }
        
        $logger->info("Successfully stored {$recordCount} price records for {$symbol}");
        
        return [
            'symbol' => $symbol,
            'records_stored' => $recordCount,
            'days_fetched' => $days,
            'source' => $source
        ];
    });
    
    // Batch stock fetch job handler
    $jobProcessor->registerJobHandler('stock_batch_fetch', function($job) use ($stockDAO, $logger) {
        $symbols = $job['parameters']['symbols'] ?? [];
        $days = $job['parameters']['days'] ?? 1;
        
        if (empty($symbols)) {
            throw new Exception('Symbols array is required for batch fetch job');
        }
        
        $logger->info("Processing batch fetch job for " . count($symbols) . " symbols");
        
        $results = [];
        foreach ($symbols as $symbol) {
            try {
                // Execute fetch for each symbol
                $command = sprintf(
                    'cd "%s" && python fetch_historical_data.py "%s" %d 2>&1',
                    dirname(__DIR__),
                    escapeshellarg($symbol),
                    $days
                );
                
                $output = shell_exec($command);
                
                if ($output !== null && strpos($output, 'Error:') === false) {
                    $data = json_decode($output, true);
                    if ($data && isset($data['data'])) {
                        $recordCount = 0;
                        foreach ($data['data'] as $record) {
                            $stockDAO->upsertPriceData($symbol, [
                                'date' => $record['Date'],
                                'open' => $record['Open'],
                                'high' => $record['High'],
                                'low' => $record['Low'],
                                'close' => $record['Close'],
                                'volume' => $record['Volume']
                            ]);
                            $recordCount++;
                        }
                        $results[$symbol] = ['success' => true, 'records' => $recordCount];
                        $logger->info("Batch: Successfully processed {$symbol} ({$recordCount} records)");
                    } else {
                        $results[$symbol] = ['success' => false, 'error' => 'Invalid JSON response'];
                    }
                } else {
                    $results[$symbol] = ['success' => false, 'error' => 'Script execution failed'];
                }
                
                // Small delay between symbols to respect rate limits
                usleep(100000); // 100ms
                
            } catch (Exception $e) {
                $results[$symbol] = ['success' => false, 'error' => $e->getMessage()];
                $logger->warning("Batch: Failed to process {$symbol}: " . $e->getMessage());
            }
        }
        
        $successCount = count(array_filter($results, function($r) { return $r['success']; }));
        $logger->info("Batch fetch completed: {$successCount}/" . count($symbols) . " symbols successful");
        
        return [
            'symbols_processed' => count($symbols),
            'successful_symbols' => $successCount,
            'results' => $results
        ];
    });
    
    // Stock analysis job handler
    $jobProcessor->registerJobHandler('stock_analysis', function($job) use ($stockDAO, $logger) {
        $symbol = $job['parameters']['symbol'] ?? null;
        $analysisTypes = $job['parameters']['analysis_types'] ?? ['sentiment'];
        
        if (!$symbol) {
            throw new Exception('Stock symbol is required for analysis job');
        }
        
        $logger->info("Processing stock analysis job for {$symbol}");
        
        // Get recent price data for analysis
        $priceData = $stockDAO->getPriceData($symbol, 30); // Last 30 days
        
        if (empty($priceData)) {
            throw new Exception("No price data available for {$symbol}");
        }
        
        // Simple technical analysis
        $analysis = performTechnicalAnalysis($priceData, $analysisTypes);
        
        // Store analysis results
        $stockDAO->saveAnalysis($symbol, [
            'analysis_type' => 'technical',
            'analysis_data' => json_encode($analysis),
            'confidence_score' => $analysis['confidence'] ?? 0.5,
            'recommendation' => $analysis['recommendation'] ?? 'HOLD'
        ]);
        
        $logger->info("Successfully completed analysis for {$symbol}");
        
        return [
            'symbol' => $symbol,
            'analysis_types' => $analysisTypes,
            'recommendation' => $analysis['recommendation'] ?? 'HOLD',
            'confidence' => $analysis['confidence'] ?? 0.5
        ];
    });
    
    $logger->info("Job handlers registered successfully");
}

/**
 * Perform simple technical analysis on price data
 */
function performTechnicalAnalysis($priceData, $analysisTypes) {
    $analysis = [
        'confidence' => 0.5,
        'recommendation' => 'HOLD',
        'indicators' => []
    ];
    
    if (count($priceData) < 5) {
        return $analysis;
    }
    
    // Calculate simple moving average
    if (in_array('technical', $analysisTypes)) {
        $closes = array_column($priceData, 'close');
        $recentCloses = array_slice($closes, -5);
        $sma5 = array_sum($recentCloses) / count($recentCloses);
        
        $currentPrice = end($closes);
        $priceVsSMA = ($currentPrice - $sma5) / $sma5;
        
        $analysis['indicators']['sma5'] = $sma5;
        $analysis['indicators']['price_vs_sma'] = $priceVsSMA;
        
        // Simple recommendation logic
        if ($priceVsSMA > 0.02) {
            $analysis['recommendation'] = 'BUY';
            $analysis['confidence'] = 0.7;
        } elseif ($priceVsSMA < -0.02) {
            $analysis['recommendation'] = 'SELL';
            $analysis['confidence'] = 0.6;
        }
    }
    
    return $analysis;
}

// Handle shutdown gracefully
function handleShutdown($signal) {
    global $logger, $jobProcessor;
    
    $logger->info("Received shutdown signal: {$signal}");
    
    if ($jobProcessor) {
        $jobProcessor->stop();
    }
    
    $logger->info("Stock Data Job Worker shutdown complete");
    exit(0);
}

// Register signal handlers
pcntl_signal(SIGTERM, 'handleShutdown');
pcntl_signal(SIGINT, 'handleShutdown');
pcntl_signal(SIGHUP, 'handleShutdown');

echo "Stock Data Job Worker ready. Press Ctrl+C to stop.\n";