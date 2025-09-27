<?php
/**
 * Background Job Worker for Processing Queue
 * Can be run as cron job or continuously
 */

require_once __DIR__ . '/JobQueue.php';
require_once __DIR__ . '/web_ui/UserAuthDAO.php';

class JobWorker {
    private $jobQueue;
    private $logger;
    private $isRunning = false;
    private $maxExecutionTime;
    private $processCount = 0;
    
    public function __construct($maxExecutionTime = 25) {
        $auth = new UserAuthDAO();
        $db = $auth->getPdo();
        $this->jobQueue = new JobQueue($db);
        $this->logger = new SimpleLogger();
        $this->maxExecutionTime = $maxExecutionTime;
    }
    
    /**
     * Process jobs continuously until stopped
     */
    public function runContinuous($maxJobs = null) {
        $this->isRunning = true;
        $this->logger->info("Starting continuous job processing" . 
                           ($maxJobs ? " (max {$maxJobs} jobs)" : ""));
        
        // Handle graceful shutdown
        if (function_exists('pcntl_signal')) {
            pcntl_signal(15, [$this, 'stop']); // SIGTERM
            pcntl_signal(2, [$this, 'stop']);  // SIGINT
        }
        
        while ($this->isRunning) {
            $startTime = time();
            
            // Process one job
            $result = $this->jobQueue->processNextJob($this->maxExecutionTime);
            
            if ($result['status'] === 'no_jobs') {
                $this->logger->info("No pending jobs, waiting...");
                sleep(5); // Wait 5 seconds before checking again
                continue;
            }
            
            $this->processCount++;
            $this->logger->info("Processed job #{$result['job_id']} - Status: {$result['status']}");
            
            // Check if we've hit max jobs limit
            if ($maxJobs && $this->processCount >= $maxJobs) {
                $this->logger->info("Reached maximum job limit ({$maxJobs}), stopping");
                break;
            }
            
            // Small delay between jobs to prevent overload
            sleep(1);
            
            // Allow signal handling in PHP
            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }
        
        $this->logger->info("Job worker stopped after processing {$this->processCount} jobs");
    }
    
    /**
     * Process single job (for cron usage)
     */
    public function processSingleJob() {
        $result = $this->jobQueue->processNextJob($this->maxExecutionTime);
        
        if ($result['status'] !== 'no_jobs') {
            $this->logger->info("Processed job #{$result['job_id']} - Status: {$result['status']}");
        }
        
        return $result;
    }
    
    /**
     * Stop the worker
     */
    public function stop() {
        $this->logger->info("Received stop signal, finishing current job...");
        $this->isRunning = false;
    }
    
    /**
     * Get worker status
     */
    public function getStatus() {
        return [
            'is_running' => $this->isRunning,
            'process_count' => $this->processCount,
            'start_time' => $this->startTime ?? null
        ];
    }
}

// Simple logger class
class SimpleLogger {
    private $logFile;
    
    public function __construct($logFile = null) {
        $this->logFile = $logFile ?: __DIR__ . '/logs/job_worker.log';
        
        // Create logs directory if it doesn't exist
        $logDir = dirname($this->logFile);
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
    
    private function log($level, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
        
        // Output to console if CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
        
        // Write to log file
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// CLI interface
if (php_sapi_name() === 'cli' && basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $worker = new JobWorker();
    
    if ($argc < 2) {
        echo "Usage: php JobWorker.php <command> [options]\n";
        echo "Commands:\n";
        echo "  run                       - Process jobs continuously\n";
        echo "  run-limited <max_jobs>    - Process up to N jobs then stop\n";
        echo "  single                    - Process single job (for cron)\n";
        echo "\nExamples:\n";
        echo "  php JobWorker.php run                    # Run continuously\n";
        echo "  php JobWorker.php run-limited 10         # Process 10 jobs then stop\n";
        echo "  php JobWorker.php single                 # Process one job (cron)\n";
        exit(1);
    }
    
    $command = $argv[1];
    
    switch ($command) {
        case 'run':
            $worker->runContinuous();
            break;
            
        case 'run-limited':
            $maxJobs = isset($argv[2]) ? (int)$argv[2] : 10;
            $worker->runContinuous($maxJobs);
            break;
            
        case 'single':
            $result = $worker->processSingleJob();
            echo json_encode($result) . "\n";
            break;
            
        default:
            echo "Unknown command: {$command}\n";
            exit(1);
    }
}
?>