<?php
/**
 * Background Job Queue System for Stock Data Loading
 * Handles long-running operations by breaking them into manageable chunks
 */

require_once __DIR__ . '/web_ui/StockDAO.php';
require_once __DIR__ . '/StockDataService.php';

class JobQueue {
    private $db;
    private $logger;
    
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PAUSED = 'paused';
    
    const JOB_TYPE_STOCK_LOAD = 'stock_load';
    const JOB_TYPE_PORTFOLIO_LOAD = 'portfolio_load';
    const JOB_TYPE_DATA_CHUNK = 'data_chunk';
    
    public function __construct($database, $logger = null) {
        $this->db = $database;
        $this->logger = $logger ?: new SimpleLogger();
        $this->initializeJobTables();
    }
    
    /**
     * Initialize job queue database tables
     */
    private function initializeJobTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS job_queue (
            id INTEGER PRIMARY KEY AUTO_INCREMENT,
            job_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            priority INTEGER DEFAULT 0,
            data JSON,
            result JSON NULL,
            error_message TEXT NULL,
            attempts INTEGER DEFAULT 0,
            max_attempts INTEGER DEFAULT 3,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            started_at DATETIME NULL,
            completed_at DATETIME NULL,
            parent_job_id INTEGER NULL,
            progress_current INTEGER DEFAULT 0,
            progress_total INTEGER DEFAULT 0,
            INDEX idx_status (status),
            INDEX idx_job_type (job_type),
            INDEX idx_created (created_at),
            INDEX idx_parent (parent_job_id)
        )";
        
        // Handle SQLite vs MySQL differences
        if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $sql = str_replace('AUTO_INCREMENT', 'AUTOINCREMENT', $sql);
            $sql = str_replace('JSON', 'TEXT', $sql);
            $sql = str_replace('ON UPDATE CURRENT_TIMESTAMP', '', $sql);
        }
        
        $this->db->exec($sql);
    }
    
    /**
     * Queue a stock loading job with automatic chunking
     */
    public function queueStockLoad($symbol, $startDate = null, $priority = 0) {
        // Create parent job
        $parentJobData = [
            'symbol' => $symbol,
            'start_date' => $startDate,
            'total_chunks' => 0,
            'completed_chunks' => 0
        ];
        
        $parentJobId = $this->createJob(
            self::JOB_TYPE_STOCK_LOAD, 
            $parentJobData, 
            $priority
        );
        
        // Calculate chunks needed
        $chunks = $this->calculateDataChunks($symbol, $startDate);
        
        // Update parent job with total chunks
        $this->updateJobData($parentJobId, array_merge($parentJobData, [
            'total_chunks' => count($chunks)
        ]));
        
        $this->updateJobProgress($parentJobId, 0, count($chunks));
        
        // Create child jobs for each chunk
        foreach ($chunks as $index => $chunk) {
            $chunkJobData = [
                'symbol' => $symbol,
                'start_date' => $chunk['start'],
                'end_date' => $chunk['end'],
                'chunk_index' => $index,
                'parent_job_id' => $parentJobId
            ];
            
            $this->createJob(
                self::JOB_TYPE_DATA_CHUNK, 
                $chunkJobData, 
                $priority - 1, // Lower priority than parent
                $parentJobId
            );
        }
        
        $this->logger->info("Queued stock load job for {$symbol} with " . count($chunks) . " chunks");
        
        return $parentJobId;
    }
    
    /**
     * Queue multiple stock symbols for loading
     */
    public function queuePortfolioLoad($symbols, $startDate = null, $priority = 0) {
        $parentJobData = [
            'symbols' => $symbols,
            'start_date' => $startDate,
            'total_symbols' => count($symbols),
            'completed_symbols' => 0
        ];
        
        $parentJobId = $this->createJob(
            self::JOB_TYPE_PORTFOLIO_LOAD, 
            $parentJobData, 
            $priority
        );
        
        $this->updateJobProgress($parentJobId, 0, count($symbols));
        
        // Create individual stock load jobs
        foreach ($symbols as $index => $symbol) {
            $stockJobId = $this->queueStockLoad($symbol, $startDate, $priority - 1);
            
            // Link to parent portfolio job
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET parent_job_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$parentJobId, $stockJobId]);
        }
        
        $this->logger->info("Queued portfolio load job for " . count($symbols) . " symbols");
        
        return $parentJobId;
    }
    
    /**
     * Process next available job
     */
    public function processNextJob($maxExecutionTime = 25) {
        $startTime = time();
        
        // Get next job
        $job = $this->getNextJob();
        
        if (!$job) {
            return ['status' => 'no_jobs', 'message' => 'No pending jobs found'];
        }
        
        $this->logger->info("Processing job #{$job['id']} ({$job['job_type']})");
        
        // Mark job as processing
        $this->updateJobStatus($job['id'], self::STATUS_PROCESSING);
        
        try {
            $result = null;
            
            switch ($job['job_type']) {
                case self::JOB_TYPE_DATA_CHUNK:
                    $result = $this->processDataChunk($job, $maxExecutionTime);
                    break;
                    
                case self::JOB_TYPE_STOCK_LOAD:
                    $result = $this->processStockLoadJob($job);
                    break;
                    
                case self::JOB_TYPE_PORTFOLIO_LOAD:
                    $result = $this->processPortfolioJob($job);
                    break;
                    
                default:
                    throw new Exception("Unknown job type: {$job['job_type']}");
            }
            
            // Mark as completed
            $this->updateJobStatus($job['id'], self::STATUS_COMPLETED, $result);
            
            // Update parent job progress if applicable
            if ($job['parent_job_id']) {
                $this->updateParentJobProgress($job['parent_job_id']);
            }
            
            $executionTime = time() - $startTime;
            $this->logger->info("Job #{$job['id']} completed in {$executionTime} seconds");
            
            return [
                'status' => 'completed',
                'job_id' => $job['id'],
                'execution_time' => $executionTime,
                'result' => $result
            ];
            
        } catch (Exception $e) {
            $this->handleJobError($job, $e);
            
            return [
                'status' => 'failed',
                'job_id' => $job['id'],
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process a data chunk job
     */
    private function processDataChunk($job, $maxExecutionTime) {
        $data = json_decode($job['data'], true);
        $symbol = $data['symbol'];
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        
        $stockDataService = new StockDataService(true);
        $stockDAO = new StockDAO($this->db);
        
        // Check if we already have this data
        if ($this->hasDataForPeriod($symbol, $startDate, $endDate)) {
            return [
                'status' => 'skipped',
                'message' => 'Data already exists for period',
                'records' => 0
            ];
        }
        
        // Fetch data from Yahoo Finance
        $jsonResult = $stockDataService->fetchHistoricalData($symbol, $startDate, $endDate);
        $result = json_decode($jsonResult, true);
        
        if (!$result['success']) {
            throw new Exception("Failed to fetch data: " . $result['error']);
        }
        
        // Store data in database
        $recordsStored = 0;
        foreach ($result['data'] as $record) {
            try {
                $stockDAO->insertPriceData($symbol, [
                    'date' => $record['Date'],
                    'open_price' => $record['Open'],
                    'high_price' => $record['High'],
                    'low_price' => $record['Low'],
                    'close_price' => $record['Close'],
                    'adj_close_price' => $record['Adj Close'],
                    'volume' => $record['Volume']
                ]);
                $recordsStored++;
            } catch (Exception $e) {
                // Skip duplicate records
                if (strpos($e->getMessage(), 'duplicate') === false) {
                    throw $e;
                }
            }
        }
        
        // Save CSV backup
        $csvPath = $stockDataService->saveToCSV($jsonResult);
        
        return [
            'status' => 'success',
            'records_processed' => count($result['data']),
            'records_stored' => $recordsStored,
            'csv_path' => $csvPath,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }
    
    /**
     * Process stock load job (parent job management)
     */
    private function processStockLoadJob($job) {
        $data = json_decode($job['data'], true);
        
        // Check if all child chunk jobs are complete
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM job_queue 
            WHERE parent_job_id = ? AND job_type = ?
        ");
        $stmt->execute([$job['id'], self::JOB_TYPE_DATA_CHUNK]);
        $progress = $stmt->fetch();
        
        if ($progress['completed'] == $progress['total']) {
            // All chunks complete - gather results
            $stmt = $this->db->prepare("
                SELECT result FROM job_queue 
                WHERE parent_job_id = ? AND job_type = ? AND status = 'completed'
            ");
            $stmt->execute([$job['id'], self::JOB_TYPE_DATA_CHUNK]);
            $chunkResults = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $totalRecords = 0;
            foreach ($chunkResults as $resultJson) {
                $result = json_decode($resultJson, true);
                if ($result && isset($result['records_stored'])) {
                    $totalRecords += $result['records_stored'];
                }
            }
            
            return [
                'status' => 'completed',
                'symbol' => $data['symbol'],
                'total_chunks' => $progress['total'],
                'total_records' => $totalRecords
            ];
        } else {
            // Still processing - update progress
            $this->updateJobProgress($job['id'], $progress['completed'], $progress['total']);
            
            return [
                'status' => 'processing',
                'progress' => [
                    'completed' => $progress['completed'],
                    'total' => $progress['total']
                ]
            ];
        }
    }
    
    /**
     * Calculate data chunks for a symbol
     */
    private function calculateDataChunks($symbol, $startDate = null) {
        $stockDAO = new StockDAO($this->db);
        
        // Determine starting point
        if ($startDate) {
            $targetDate = new DateTime($startDate);
        } else {
            // Start from 5 years ago or oldest existing data
            $oldestDate = $this->getOldestDataDate($symbol);
            if ($oldestDate) {
                $targetDate = clone $oldestDate;
                $targetDate->modify('-5 years');
            } else {
                $targetDate = new DateTime('-5 years');
            }
        }
        
        $currentDate = new DateTime();
        $chunks = [];
        
        // Create 6-month chunks to avoid timeouts
        $chunkEndDate = clone $targetDate;
        while ($chunkEndDate < $currentDate) {
            $chunkStartDate = clone $chunkEndDate;
            $chunkEndDate = clone $chunkStartDate;
            $chunkEndDate->modify('+6 months');
            
            if ($chunkEndDate > $currentDate) {
                $chunkEndDate = $currentDate;
            }
            
            $chunks[] = [
                'start' => $chunkStartDate->format('Y-m-d'),
                'end' => $chunkEndDate->format('Y-m-d')
            ];
            
            $chunkEndDate->modify('+1 day');
        }
        
        return $chunks;
    }
    
    /**
     * Create a new job
     */
    private function createJob($type, $data, $priority = 0, $parentJobId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO job_queue (job_type, data, priority, parent_job_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        // Handle SQLite datetime
        if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $stmt = $this->db->prepare("
                INSERT INTO job_queue (job_type, data, priority, parent_job_id, created_at)
                VALUES (?, ?, ?, ?, datetime('now'))
            ");
        }
        
        $stmt->execute([
            $type,
            json_encode($data),
            $priority,
            $parentJobId
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get next pending job
     */
    private function getNextJob() {
        $stmt = $this->db->prepare("
            SELECT * FROM job_queue 
            WHERE status = 'pending'
            ORDER BY priority DESC, created_at ASC
            LIMIT 1
        ");
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Update job status
     */
    private function updateJobStatus($jobId, $status, $result = null) {
        if ($result) {
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET status = ?, result = ?, updated_at = NOW(),
                    completed_at = CASE WHEN ? = 'completed' THEN NOW() ELSE completed_at END,
                    started_at = CASE WHEN ? = 'processing' AND started_at IS NULL THEN NOW() ELSE started_at END
                WHERE id = ?
            ");
            
            if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
                $stmt = $this->db->prepare("
                    UPDATE job_queue 
                    SET status = ?, result = ?, updated_at = datetime('now'),
                        completed_at = CASE WHEN ? = 'completed' THEN datetime('now') ELSE completed_at END,
                        started_at = CASE WHEN ? = 'processing' AND started_at IS NULL THEN datetime('now') ELSE started_at END
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$status, json_encode($result), $status, $status, $jobId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET status = ?, updated_at = NOW(),
                    started_at = CASE WHEN ? = 'processing' AND started_at IS NULL THEN NOW() ELSE started_at END
                WHERE id = ?
            ");
            
            if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
                $stmt = $this->db->prepare("
                    UPDATE job_queue 
                    SET status = ?, updated_at = datetime('now'),
                        started_at = CASE WHEN ? = 'processing' AND started_at IS NULL THEN datetime('now') ELSE started_at END
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$status, $status, $jobId]);
        }
    }
    
    /**
     * Update job progress
     */
    private function updateJobProgress($jobId, $current, $total) {
        $stmt = $this->db->prepare("
            UPDATE job_queue 
            SET progress_current = ?, progress_total = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET progress_current = ?, progress_total = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
        }
        
        $stmt->execute([$current, $total, $jobId]);
    }
    
    /**
     * Update job data
     */
    private function updateJobData($jobId, $data) {
        $stmt = $this->db->prepare("
            UPDATE job_queue 
            SET data = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET data = ?, updated_at = datetime('now')
                WHERE id = ?
            ");
        }
        
        $stmt->execute([json_encode($data), $jobId]);
    }
    
    /**
     * Update parent job progress
     */
    private function updateParentJobProgress($parentJobId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as total,
                   SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
            FROM job_queue 
            WHERE parent_job_id = ?
        ");
        $stmt->execute([$parentJobId]);
        $progress = $stmt->fetch();
        
        $this->updateJobProgress($parentJobId, $progress['completed'], $progress['total']);
        
        // If all children complete, mark parent as ready for completion
        if ($progress['completed'] == $progress['total']) {
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET status = 'pending' 
                WHERE id = ? AND status = 'processing'
            ");
            $stmt->execute([$parentJobId]);
        }
    }
    
    /**
     * Handle job errors
     */
    private function handleJobError($job, $exception) {
        $attempts = $job['attempts'] + 1;
        $errorMessage = $exception->getMessage();
        
        if ($attempts >= $job['max_attempts']) {
            // Max attempts reached - mark as failed
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET status = 'failed', attempts = ?, error_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
                $stmt = $this->db->prepare("
                    UPDATE job_queue 
                    SET status = 'failed', attempts = ?, error_message = ?, updated_at = datetime('now')
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$attempts, $errorMessage, $job['id']]);
            
            $this->logger->error("Job #{$job['id']} failed permanently after {$attempts} attempts: {$errorMessage}");
        } else {
            // Retry - reset to pending
            $stmt = $this->db->prepare("
                UPDATE job_queue 
                SET status = 'pending', attempts = ?, error_message = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
                $stmt = $this->db->prepare("
                    UPDATE job_queue 
                    SET status = 'pending', attempts = ?, error_message = ?, updated_at = datetime('now')
                    WHERE id = ?
                ");
            }
            
            $stmt->execute([$attempts, $errorMessage, $job['id']]);
            
            $this->logger->warning("Job #{$job['id']} failed (attempt {$attempts}), will retry: {$errorMessage}");
        }
    }
    
    /**
     * Get job status and progress
     */
    public function getJobStatus($jobId) {
        $stmt = $this->db->prepare("
            SELECT *, 
                   CASE 
                       WHEN progress_total > 0 THEN ROUND((progress_current / progress_total) * 100, 2)
                       ELSE 0 
                   END as progress_percentage
            FROM job_queue 
            WHERE id = ?
        ");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if ($job) {
            $job['data'] = json_decode($job['data'], true);
            if ($job['result']) {
                $job['result'] = json_decode($job['result'], true);
            }
        }
        
        return $job;
    }
    
    /**
     * Get all jobs with optional filtering
     */
    public function getJobs($status = null, $jobType = null, $limit = 50) {
        $where = [];
        $params = [];
        
        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        if ($jobType) {
            $where[] = "job_type = ?";
            $params[] = $jobType;
        }
        
        $whereClause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $this->db->prepare("
            SELECT *, 
                   CASE 
                       WHEN progress_total > 0 THEN ROUND((progress_current / progress_total) * 100, 2)
                       ELSE 0 
                   END as progress_percentage
            FROM job_queue 
            {$whereClause}
            ORDER BY created_at DESC 
            LIMIT {$limit}
        ");
        $stmt->execute($params);
        
        $jobs = $stmt->fetchAll();
        foreach ($jobs as &$job) {
            $job['data'] = json_decode($job['data'], true);
            if ($job['result']) {
                $job['result'] = json_decode($job['result'], true);
            }
        }
        
        return $jobs;
    }
    
    /**
     * Clean up old completed jobs
     */
    public function cleanupOldJobs($olderThanDays = 30) {
        $stmt = $this->db->prepare("
            DELETE FROM job_queue 
            WHERE status IN ('completed', 'failed') 
            AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        
        if (strpos($this->db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
            $stmt = $this->db->prepare("
                DELETE FROM job_queue 
                WHERE status IN ('completed', 'failed') 
                AND created_at < datetime('now', '-{$olderThanDays} days')
            ");
            $stmt->execute();
        } else {
            $stmt->execute([$olderThanDays]);
        }
        
        $deletedCount = $stmt->rowCount();
        $this->logger->info("Cleaned up {$deletedCount} old jobs");
        
        return $deletedCount;
    }
    
    /**
     * Check if data exists for period
     */
    private function hasDataForPeriod($symbol, $startDate, $endDate) {
        $stockDAO = new StockDAO($this->db);
        return $stockDAO->hasDataForPeriod($symbol, $startDate, $endDate);
    }
    
    /**
     * Get oldest data date for symbol
     */
    private function getOldestDataDate($symbol) {
        try {
            $stockDAO = new StockDAO($this->db);
            $data = $stockDAO->getPriceDataOrdered($symbol, 'ASC', 1);
            if (!empty($data)) {
                return new DateTime($data[0]['date']);
            }
        } catch (Exception $e) {
            // No data exists
        }
        return null;
    }
}

// CLI interface for job processing
if (php_sapi_name() === 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    require_once __DIR__ . '/web_ui/UserAuthDAO.php';
    
    $auth = new UserAuthDAO();
    $db = $auth->getPdo();
    $jobQueue = new JobQueue($db);
    
    if ($argc < 2) {
        echo "Usage: php JobQueue.php <command> [options]\n";
        echo "Commands:\n";
        echo "  process                    - Process next job\n";
        echo "  queue-symbol <SYMBOL>      - Queue symbol for loading\n";
        echo "  queue-portfolio <symbols>  - Queue multiple symbols\n";
        echo "  status [job_id]           - Show job status\n";
        echo "  list [status]             - List jobs\n";
        echo "  cleanup                   - Clean up old jobs\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    switch ($command) {
        case 'process':
            $result = $jobQueue->processNextJob();
            echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
            break;
            
        case 'queue-symbol':
            if ($argc < 3) {
                echo "Usage: php JobQueue.php queue-symbol <SYMBOL> [start_date]\n";
                exit(1);
            }
            $symbol = strtoupper($argv[2]);
            $startDate = $argc > 3 ? $argv[3] : null;
            $jobId = $jobQueue->queueStockLoad($symbol, $startDate);
            echo "Queued stock load job #{$jobId} for {$symbol}\n";
            break;
            
        case 'queue-portfolio':
            if ($argc < 3) {
                echo "Usage: php JobQueue.php queue-portfolio SYMBOL1,SYMBOL2,... [start_date]\n";
                exit(1);
            }
            $symbols = array_map('trim', explode(',', strtoupper($argv[2])));
            $startDate = $argc > 3 ? $argv[3] : null;
            $jobId = $jobQueue->queuePortfolioLoad($symbols, $startDate);
            echo "Queued portfolio load job #{$jobId} for " . count($symbols) . " symbols\n";
            break;
            
        case 'status':
            if ($argc < 3) {
                echo "Usage: php JobQueue.php status <job_id>\n";
                exit(1);
            }
            $jobId = $argv[2];
            $status = $jobQueue->getJobStatus($jobId);
            if ($status) {
                echo json_encode($status, JSON_PRETTY_PRINT) . "\n";
            } else {
                echo "Job #{$jobId} not found\n";
            }
            break;
            
        case 'list':
            $statusFilter = $argc > 2 ? $argv[2] : null;
            $jobs = $jobQueue->getJobs($statusFilter);
            foreach ($jobs as $job) {
                echo "#{$job['id']} {$job['job_type']} {$job['status']} {$job['progress_percentage']}% {$job['created_at']}\n";
            }
            break;
            
        case 'cleanup':
            $deleted = $jobQueue->cleanupOldJobs();
            echo "Deleted {$deleted} old jobs\n";
            break;
            
        default:
            echo "Unknown command: {$command}\n";
            exit(1);
    }
}
?>