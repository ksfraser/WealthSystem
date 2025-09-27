<?php
/**
 * Updated ProgressiveHistoricalLoader with Job Queue Integration
 * Replaces timeout-prone direct loading with job queue system
 */

require_once __DIR__ . '/JobQueue.php';
require_once __DIR__ . '/web_ui/UserAuthDAO.php';

class ProgressiveHistoricalLoaderV2 {
    private $stockDataService;
    private $stockDAO;
    private $jobQueue;
    private $logger;
    
    public function __construct($useCache = true) {
        $this->stockDataService = new StockDataService($useCache);
        
        // Initialize database connection
        $auth = new UserAuthDAO();
        $db = $auth->getPdo();
        $this->stockDAO = new StockDAO($db);
        $this->jobQueue = new JobQueue($db);
        $this->logger = new SimpleLogger();
    }
    
    /**
     * Load multiple symbols using job queue (replaces old timeout-prone method)
     * Returns job ID for tracking progress instead of blocking execution
     */
    public function loadMultipleSymbols($symbols, $startDate = null, $priority = 0) {
        $this->logger->info("Queuing historical data load for " . count($symbols) . " symbols");
        
        // Validate symbols
        $validSymbols = array_filter(array_map('trim', $symbols));
        if (empty($validSymbols)) {
            throw new Exception("No valid symbols provided");
        }
        
        // Queue the portfolio load job
        $jobId = $this->jobQueue->queuePortfolioLoad($validSymbols, $startDate, $priority);
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'message' => "Queued background job #{$jobId} for " . count($validSymbols) . " symbols",
            'symbols' => $validSymbols,
            'estimated_chunks' => $this->estimateChunks($validSymbols, $startDate)
        ];
    }
    
    /**
     * Load single symbol using job queue
     */
    public function loadSymbol($symbol, $startDate = null, $priority = 0) {
        $symbol = strtoupper(trim($symbol));
        
        if (empty($symbol)) {
            throw new Exception("Symbol is required");
        }
        
        $this->logger->info("Queuing historical data load for symbol: {$symbol}");
        
        $jobId = $this->jobQueue->queueStockLoad($symbol, $startDate, $priority);
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'message' => "Queued background job #{$jobId} for {$symbol}",
            'symbol' => $symbol,
            'estimated_chunks' => $this->estimateChunks([$symbol], $startDate)
        ];
    }
    
    /**
     * Get job progress and status
     */
    public function getJobProgress($jobId) {
        $job = $this->jobQueue->getJobStatus($jobId);
        
        if (!$job) {
            throw new Exception("Job #{$jobId} not found");
        }
        
        $response = [
            'job_id' => $job['id'],
            'status' => $job['status'],
            'job_type' => $job['job_type'],
            'progress' => [
                'current' => (int)$job['progress_current'],
                'total' => (int)$job['progress_total'],
                'percentage' => (float)$job['progress_percentage']
            ],
            'created_at' => $job['created_at'],
            'updated_at' => $job['updated_at'],
            'data' => $job['data']
        ];
        
        // Add timing information
        if ($job['started_at']) {
            $response['started_at'] = $job['started_at'];
            
            if ($job['status'] === 'processing') {
                $startTime = new DateTime($job['started_at']);
                $now = new DateTime();
                $response['running_time'] = $now->diff($startTime)->format('%H:%I:%S');
            }
        }
        
        if ($job['completed_at']) {
            $response['completed_at'] = $job['completed_at'];
            
            $startTime = new DateTime($job['started_at'] ?: $job['created_at']);
            $endTime = new DateTime($job['completed_at']);
            $response['total_time'] = $startTime->diff($endTime)->format('%H:%I:%S');
        }
        
        // Add error information if failed
        if ($job['status'] === 'failed' && $job['error_message']) {
            $response['error'] = $job['error_message'];
            $response['attempts'] = $job['attempts'];
            $response['max_attempts'] = $job['max_attempts'];
        }
        
        // Add result information if completed
        if ($job['status'] === 'completed' && $job['result']) {
            $response['result'] = $job['result'];
        }
        
        return $response;
    }
    
    /**
     * Get all jobs for monitoring
     */
    public function getAllJobs($status = null, $limit = 50) {
        return $this->jobQueue->getJobs($status, null, $limit);
    }
    
    /**
     * Process next job manually (for testing)
     */
    public function processNextJob($maxExecutionTime = 25) {
        return $this->jobQueue->processNextJob($maxExecutionTime);
    }
    
    /**
     * Check if symbol has recent data
     */
    public function hasRecentData($symbol, $dayThreshold = 7) {
        try {
            $recentData = $this->stockDAO->getPriceDataOrdered($symbol, 'DESC', 1);
            
            if (empty($recentData)) {
                return false;
            }
            
            $lastDate = new DateTime($recentData[0]['date']);
            $threshold = new DateTime("-{$dayThreshold} days");
            
            return $lastDate >= $threshold;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get data coverage summary for symbol
     */
    public function getDataCoverage($symbol) {
        try {
            $symbol = strtoupper(trim($symbol));
            
            // Get oldest and newest data
            $oldestData = $this->stockDAO->getPriceDataOrdered($symbol, 'ASC', 1);
            $newestData = $this->stockDAO->getPriceDataOrdered($symbol, 'DESC', 1);
            
            if (empty($oldestData) || empty($newestData)) {
                return [
                    'symbol' => $symbol,
                    'has_data' => false,
                    'record_count' => 0,
                    'date_range' => null,
                    'coverage_days' => 0,
                    'gaps' => []
                ];
            }
            
            // Count total records
            $totalRecords = $this->stockDAO->countPriceData($symbol);
            
            $oldestDate = new DateTime($oldestData[0]['date']);
            $newestDate = new DateTime($newestData[0]['date']);
            $coverageDays = $newestDate->diff($oldestDate)->days + 1;
            
            // Check for significant gaps (more than 7 days)
            $gaps = $this->findDataGaps($symbol, 7);
            
            return [
                'symbol' => $symbol,
                'has_data' => true,
                'record_count' => $totalRecords,
                'date_range' => [
                    'start' => $oldestDate->format('Y-m-d'),
                    'end' => $newestDate->format('Y-m-d')
                ],
                'coverage_days' => $coverageDays,
                'business_days_estimated' => $this->estimateBusinessDays($oldestDate, $newestDate),
                'completeness_percentage' => $this->calculateCompleteness($totalRecords, $oldestDate, $newestDate),
                'gaps' => $gaps,
                'last_updated' => $newestDate->format('Y-m-d'),
                'needs_update' => !$this->hasRecentData($symbol, 2)
            ];
        } catch (Exception $e) {
            throw new Exception("Error analyzing data coverage for {$symbol}: " . $e->getMessage());
        }
    }
    
    /**
     * Get portfolio coverage summary
     */
    public function getPortfolioCoverage($symbols) {
        $coverage = [];
        $summary = [
            'total_symbols' => count($symbols),
            'symbols_with_data' => 0,
            'total_records' => 0,
            'symbols_needing_update' => 0,
            'symbols_with_gaps' => 0
        ];
        
        foreach ($symbols as $symbol) {
            try {
                $symbolCoverage = $this->getDataCoverage($symbol);
                $coverage[$symbol] = $symbolCoverage;
                
                if ($symbolCoverage['has_data']) {
                    $summary['symbols_with_data']++;
                    $summary['total_records'] += $symbolCoverage['record_count'];
                    
                    if ($symbolCoverage['needs_update']) {
                        $summary['symbols_needing_update']++;
                    }
                    
                    if (!empty($symbolCoverage['gaps'])) {
                        $summary['symbols_with_gaps']++;
                    }
                }
            } catch (Exception $e) {
                $coverage[$symbol] = [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'summary' => $summary,
            'symbols' => $coverage
        ];
    }
    
    /**
     * Estimate number of chunks for loading
     */
    private function estimateChunks($symbols, $startDate = null) {
        $chunksPerSymbol = 10; // Default estimate for 5 years in 6-month chunks
        
        if ($startDate) {
            $start = new DateTime($startDate);
            $now = new DateTime();
            $months = $start->diff($now)->m + ($start->diff($now)->y * 12);
            $chunksPerSymbol = max(1, ceil($months / 6));
        }
        
        return count($symbols) * $chunksPerSymbol;
    }
    
    /**
     * Find gaps in data coverage
     */
    private function findDataGaps($symbol, $gapThreshold = 7) {
        $stmt = $this->stockDAO->getConnection()->prepare("
            SELECT 
                date,
                LAG(date) OVER (ORDER BY date) as prev_date,
                DATEDIFF(date, LAG(date) OVER (ORDER BY date)) as gap_days
            FROM stock_data 
            WHERE symbol = ? 
            ORDER BY date
        ");
        
        // Handle SQLite syntax differences
        if (strpos($this->stockDAO->getConnection()->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $stmt = $this->stockDAO->getConnection()->prepare("
                SELECT 
                    date,
                    lag(date) OVER (ORDER BY date) as prev_date,
                    julianday(date) - julianday(lag(date) OVER (ORDER BY date)) as gap_days
                FROM stock_data 
                WHERE symbol = ? 
                ORDER BY date
            ");
        }
        
        $stmt->execute([$symbol]);
        $data = $stmt->fetchAll();
        
        $gaps = [];
        foreach ($data as $row) {
            if ($row['prev_date'] && $row['gap_days'] > $gapThreshold) {
                $gaps[] = [
                    'start_date' => $row['prev_date'],
                    'end_date' => $row['date'],
                    'gap_days' => (int)$row['gap_days']
                ];
            }
        }
        
        return $gaps;
    }
    
    /**
     * Estimate business days between dates
     */
    private function estimateBusinessDays($startDate, $endDate) {
        $totalDays = $endDate->diff($startDate)->days + 1;
        $weeks = floor($totalDays / 7);
        $remainingDays = $totalDays % 7;
        
        // Rough estimate: 5 business days per week
        return ($weeks * 5) + min($remainingDays, 5);
    }
    
    /**
     * Calculate data completeness percentage
     */
    private function calculateCompleteness($recordCount, $startDate, $endDate) {
        $estimatedBusinessDays = $this->estimateBusinessDays($startDate, $endDate);
        
        if ($estimatedBusinessDays == 0) {
            return 0;
        }
        
        return min(100, round(($recordCount / $estimatedBusinessDays) * 100, 1));
    }
}

// CLI interface for testing
if (php_sapi_name() === 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $loader = new ProgressiveHistoricalLoaderV2();
    
    if ($argc < 2) {
        echo "Usage: php ProgressiveHistoricalLoaderV2.php <command> [options]\n";
        echo "Commands:\n";
        echo "  load-symbol <SYMBOL> [start_date]     - Queue single symbol load\n";
        echo "  load-portfolio <SYMBOL1,SYMBOL2,...>  - Queue portfolio load\n";
        echo "  status <job_id>                      - Check job status\n";
        echo "  coverage <SYMBOL>                    - Show data coverage\n";
        echo "  portfolio-coverage <SYMBOL1,SYMBOL2> - Show portfolio coverage\n";
        echo "  process                              - Process next job\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    try {
        switch ($command) {
            case 'load-symbol':
                if ($argc < 3) {
                    echo "Usage: php ProgressiveHistoricalLoaderV2.php load-symbol <SYMBOL> [start_date]\n";
                    exit(1);
                }
                $symbol = strtoupper($argv[2]);
                $startDate = $argc > 3 ? $argv[3] : null;
                $result = $loader->loadSymbol($symbol, $startDate);
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'load-portfolio':
                if ($argc < 3) {
                    echo "Usage: php ProgressiveHistoricalLoaderV2.php load-portfolio SYMBOL1,SYMBOL2,...\n";
                    exit(1);
                }
                $symbols = array_map('trim', explode(',', strtoupper($argv[2])));
                $startDate = $argc > 3 ? $argv[3] : null;
                $result = $loader->loadMultipleSymbols($symbols, $startDate);
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'status':
                if ($argc < 3) {
                    echo "Usage: php ProgressiveHistoricalLoaderV2.php status <job_id>\n";
                    exit(1);
                }
                $jobId = $argv[2];
                $status = $loader->getJobProgress($jobId);
                echo json_encode($status, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'coverage':
                if ($argc < 3) {
                    echo "Usage: php ProgressiveHistoricalLoaderV2.php coverage <SYMBOL>\n";
                    exit(1);
                }
                $symbol = strtoupper($argv[2]);
                $coverage = $loader->getDataCoverage($symbol);
                echo json_encode($coverage, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'portfolio-coverage':
                if ($argc < 3) {
                    echo "Usage: php ProgressiveHistoricalLoaderV2.php portfolio-coverage SYMBOL1,SYMBOL2,...\n";
                    exit(1);
                }
                $symbols = array_map('trim', explode(',', strtoupper($argv[2])));
                $coverage = $loader->getPortfolioCoverage($symbols);
                echo json_encode($coverage, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'process':
                $result = $loader->processNextJob();
                echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            default:
                echo "Unknown command: {$command}\n";
                exit(1);
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>