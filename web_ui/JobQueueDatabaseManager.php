<?php
/**
 * Job Queue Database Manager
 * Handles creation and management of job queue tables for clustering support
 */

class JobQueueDatabaseManager {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Create all job queue related tables
     */
    public function createJobQueueTables() {
        $this->createJobQueuesTable();
        $this->createJobProcessorsTable();
        $this->createJobResultsTable();
        $this->createJobLockTable();
        echo "Job queue tables created successfully.\n";
    }
    
    /**
     * Create main job queues table
     */
    private function createJobQueuesTable() {
        $sql = "CREATE TABLE IF NOT EXISTS job_queues (
            id INT AUTO_INCREMENT PRIMARY KEY,
            queue_name ENUM('foreground', 'background-fetch', 'background-analyze') NOT NULL,
            job_type VARCHAR(50) NOT NULL,
            priority INT DEFAULT 50 COMMENT 'Lower number = higher priority',
            payload JSON NOT NULL COMMENT 'Job parameters and data',
            status ENUM('pending', 'processing', 'completed', 'failed', 'retrying') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            processor_id VARCHAR(100) NULL COMMENT 'ID of processor handling this job',
            user_id INT NULL COMMENT 'User who triggered this job (for priority)',
            stock_symbol VARCHAR(10) NULL COMMENT 'Stock symbol for indexing',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            error_message TEXT NULL,
            
            INDEX idx_queue_status (queue_name, status),
            INDEX idx_priority (priority, created_at),
            INDEX idx_stock_symbol (stock_symbol),
            INDEX idx_user_id (user_id),
            INDEX idx_processor (processor_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Create job processors table for clustering
     */
    private function createJobProcessorsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS job_processors (
            id VARCHAR(100) PRIMARY KEY,
            hostname VARCHAR(255) NOT NULL,
            queue_name ENUM('foreground', 'background-fetch', 'background-analyze') NOT NULL,
            status ENUM('active', 'idle', 'offline') DEFAULT 'idle',
            last_heartbeat TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            jobs_processed INT DEFAULT 0,
            jobs_failed INT DEFAULT 0,
            max_concurrent_jobs INT DEFAULT 5,
            current_jobs INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_queue_status (queue_name, status),
            INDEX idx_heartbeat (last_heartbeat),
            INDEX idx_hostname (hostname)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Create job results table for storing job outputs
     */
    private function createJobResultsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS job_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            result_type VARCHAR(50) NOT NULL COMMENT 'data_fetched, analysis_completed, etc.',
            result_data JSON NULL COMMENT 'Job output data',
            metadata JSON NULL COMMENT 'Additional metadata',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (job_id) REFERENCES job_queues(id) ON DELETE CASCADE,
            INDEX idx_job_id (job_id),
            INDEX idx_result_type (result_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Create job lock table for preventing duplicate processing
     */
    private function createJobLockTable() {
        $sql = "CREATE TABLE IF NOT EXISTS job_locks (
            lock_key VARCHAR(255) PRIMARY KEY,
            processor_id VARCHAR(100) NOT NULL,
            job_id INT NULL,
            locked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            lock_type ENUM('stock_fetch', 'stock_analyze', 'user_portfolio') NOT NULL,
            
            FOREIGN KEY (processor_id) REFERENCES job_processors(id) ON DELETE CASCADE,
            INDEX idx_expires (expires_at),
            INDEX idx_processor (processor_id),
            INDEX idx_lock_type (lock_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Create user portfolio priority tracking table
     */
    public function createUserPortfolioPriorityTable() {
        $sql = "CREATE TABLE IF NOT EXISTS user_portfolio_priority (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            stock_symbol VARCHAR(10) NOT NULL,
            last_data_fetch TIMESTAMP NULL,
            last_analysis_update TIMESTAMP NULL,
            priority_score INT DEFAULT 100 COMMENT 'Higher score = higher priority',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            UNIQUE KEY unique_user_stock (user_id, stock_symbol),
            INDEX idx_user_id (user_id),
            INDEX idx_stock_symbol (stock_symbol),
            INDEX idx_priority (priority_score DESC),
            INDEX idx_last_fetch (last_data_fetch),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->db->exec($sql);
    }
    
    /**
     * Clean up old completed/failed jobs
     */
    public function cleanupOldJobs($daysToKeep = 7) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        // Clean up completed/failed jobs older than cutoff
        $sql = "DELETE FROM job_queues 
                WHERE status IN ('completed', 'failed') 
                AND completed_at < ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cutoffDate]);
        
        $deletedJobs = $stmt->rowCount();
        
        // Clean up expired locks
        $sql = "DELETE FROM job_locks WHERE expires_at < NOW()";
        $this->db->exec($sql);
        
        // Clean up offline processors (no heartbeat for 1 hour)
        $sql = "DELETE FROM job_processors 
                WHERE last_heartbeat < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $this->db->exec($sql);
        
        return $deletedJobs;
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        $sql = "SELECT 
                    queue_name,
                    status,
                    COUNT(*) as count
                FROM job_queues 
                GROUP BY queue_name, status
                ORDER BY queue_name, status";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get processor statistics
     */
    public function getProcessorStats() {
        $sql = "SELECT 
                    queue_name,
                    status,
                    COUNT(*) as processor_count,
                    SUM(current_jobs) as total_active_jobs,
                    SUM(jobs_processed) as total_processed,
                    SUM(jobs_failed) as total_failed
                FROM job_processors 
                WHERE last_heartbeat > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY queue_name, status
                ORDER BY queue_name, status";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}