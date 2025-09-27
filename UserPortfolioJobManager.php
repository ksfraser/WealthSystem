<?php

require_once __DIR__ . '/MQTTJobBackend.php';
require_once __DIR__ . '/web_ui/StockDAO.php';

/**
 * User Portfolio Job Manager
 * Handles prioritized job queuing for user portfolios using existing MQTT system
 */
class UserPortfolioJobManager {
    private $mqttBackend;
    private $stockDAO;
    private $config;
    private $logger;
    private $db;
    
    public function __construct($config, $logger, $database) {
        $this->config = $config;
        $this->logger = $logger;
        $this->db = $database;
        
        // Initialize MQTT backend using existing system
        $this->mqttBackend = new MQTTJobBackend($config, $logger);
        
        // Initialize StockDAO for data checking
        $this->stockDAO = new StockDAO($database);
    }
    
    /**
     * Process user login - queue priority jobs for their portfolio
     */
    public function processUserLogin($userId) {
        try {
            $this->logger->info("Processing user login for portfolio priority: User {$userId}");
            
            // Get user's portfolio symbols
            $portfolioSymbols = $this->getUserPortfolioSymbols($userId);
            
            if (empty($portfolioSymbols)) {
                $this->logger->info("No portfolio symbols found for user {$userId}");
                return ['success' => true, 'message' => 'No portfolio symbols to update'];
            }
            
            // Analyze each symbol for data freshness and queue jobs as needed
            $queuedJobs = [];
            $skippedSymbols = [];
            
            foreach ($portfolioSymbols as $symbol) {
                $jobsForSymbol = $this->processSymbolForUser($userId, $symbol);
                if (!empty($jobsForSymbol)) {
                    $queuedJobs = array_merge($queuedJobs, $jobsForSymbol);
                } else {
                    $skippedSymbols[] = $symbol;
                }
            }
            
            // Update user portfolio priority tracking
            $this->updateUserPortfolioPriority($userId, $portfolioSymbols);
            
            return [
                'success' => true,
                'message' => sprintf(
                    'Queued %d jobs for %d symbols. Skipped %d symbols with fresh data.',
                    count($queuedJobs),
                    count($portfolioSymbols) - count($skippedSymbols),
                    count($skippedSymbols)
                ),
                'queued_jobs' => $queuedJobs,
                'skipped_symbols' => $skippedSymbols
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Error processing user login portfolio jobs: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process a single symbol for a user - check freshness and queue if needed
     */
    private function processSymbolForUser($userId, $symbol) {
        $queuedJobs = [];
        
        // Check data freshness
        $dataAge = $this->getDataAge($symbol);
        $stalenessThreshold = $this->config['stock_jobs']['portfolio_priority']['data_staleness_threshold'] ?? 30;
        
        if ($dataAge === null || $dataAge > $stalenessThreshold) {
            // Data is stale or missing - queue fetch job
            $fetchJobId = $this->queuePortfolioFetchJob($userId, $symbol, $dataAge);
            if ($fetchJobId) {
                $queuedJobs[] = $fetchJobId;
                $this->logger->info("Queued priority fetch job for {$symbol} (age: {$dataAge} min)");
            }
        }
        
        // Check if analysis is needed
        $analysisAge = $this->getAnalysisAge($symbol);
        $analysisThreshold = $this->config['stock_jobs']['analysis']['cache_ttl'] ?? 360; // 6 hours in minutes
        
        if ($analysisAge === null || $analysisAge > $analysisThreshold) {
            // Analysis is stale - queue analysis job (lower priority)
            $analysisJobId = $this->queuePortfolioAnalysisJob($userId, $symbol, $analysisAge);
            if ($analysisJobId) {
                $queuedJobs[] = $analysisJobId;
                $this->logger->info("Queued priority analysis job for {$symbol} (age: {$analysisAge} min)");
            }
        }
        
        return $queuedJobs;
    }
    
    /**
     * Queue high-priority data fetch job for user's portfolio symbol
     */
    private function queuePortfolioFetchJob($userId, $symbol, $dataAge) {
        // Calculate priority based on data age and user activity
        $priority = $this->calculateFetchPriority($userId, $symbol, $dataAge);
        
        $jobData = [
            'job_type' => 'stock_fetch',
            'subtype' => 'portfolio_priority',
            'user_id' => $userId,
            'symbol' => $symbol,
            'priority' => $priority,
            'parameters' => [
                'symbol' => $symbol,
                'days' => 1, // Just get latest data
                'source' => 'yahoo_finance',
                'triggered_by' => 'user_login',
                'data_age_minutes' => $dataAge
            ],
            'metadata' => [
                'user_id' => $userId,
                'symbol' => $symbol,
                'request_source' => 'portfolio_priority',
                'created_by' => 'UserPortfolioJobManager'
            ]
        ];
        
        // Use foreground queue for user portfolio jobs
        return $this->mqttBackend->addJob($jobData);
    }
    
    /**
     * Queue analysis job for user's portfolio symbol
     */
    private function queuePortfolioAnalysisJob($userId, $symbol, $analysisAge) {
        $priority = $this->calculateAnalysisPriority($userId, $symbol, $analysisAge);
        
        $jobData = [
            'job_type' => 'stock_analysis',
            'subtype' => 'portfolio_priority',
            'user_id' => $userId,
            'symbol' => $symbol,
            'priority' => $priority,
            'parameters' => [
                'symbol' => $symbol,
                'analysis_types' => ['sentiment', 'technical'],
                'triggered_by' => 'user_login',
                'analysis_age_minutes' => $analysisAge
            ],
            'metadata' => [
                'user_id' => $userId,
                'symbol' => $symbol,
                'request_source' => 'portfolio_priority',
                'created_by' => 'UserPortfolioJobManager'
            ]
        ];
        
        return $this->mqttBackend->addJob($jobData);
    }
    
    /**
     * Calculate fetch job priority based on various factors
     */
    private function calculateFetchPriority($userId, $symbol, $dataAge) {
        $basePriority = $this->config['jobs']['priority_rules']['user_login'] ?? 1;
        
        // Factors from configuration
        $factors = $this->config['portfolio']['priority_factors'] ?? [];
        $userActivityWeight = $factors['user_activity'] ?? 0.4;
        $dataAgeWeight = $factors['data_age'] ?? 0.2;
        
        // User activity boost (recently active users get higher priority)
        $userActivityScore = $this->getUserActivityScore($userId);
        
        // Data age penalty (older data gets higher priority)
        $dataAgeScore = min(1.0, ($dataAge ?? 0) / 60); // Normalize to 0-1 over 1 hour
        
        // Calculate final priority (lower number = higher priority)
        $priority = $basePriority + 
                   (1 - $userActivityScore) * $userActivityWeight +
                   (1 - $dataAgeScore) * $dataAgeWeight;
        
        return max(1, min(10, round($priority)));
    }
    
    /**
     * Calculate analysis job priority
     */
    private function calculateAnalysisPriority($userId, $symbol, $analysisAge) {
        $basePriority = $this->config['jobs']['priority_rules']['background_analysis'] ?? 8;
        
        // Portfolio analysis gets higher priority than general analysis
        $portfolioBoost = $this->config['stock_jobs']['portfolio_priority']['portfolio_priority_boost'] ?? 2;
        
        return max(3, $basePriority - $portfolioBoost);
    }
    
    /**
     * Get user's portfolio symbols
     */
    private function getUserPortfolioSymbols($userId) {
        // First try to get from user-specific portfolio (if implemented)
        $symbols = $this->getUserSpecificPortfolio($userId);
        
        // Fallback to shared portfolio CSV
        if (empty($symbols)) {
            $symbols = $this->getSharedPortfolioSymbols();
        }
        
        return array_unique(array_filter($symbols));
    }
    
    /**
     * Get user-specific portfolio symbols (future enhancement)
     */
    private function getUserSpecificPortfolio($userId) {
        // TODO: Implement user-specific portfolios
        // For now, return empty to use shared portfolio
        return [];
    }
    
    /**
     * Get symbols from shared portfolio CSV
     */
    private function getSharedPortfolioSymbols() {
        $portfolioFile = __DIR__ . '/Scripts and CSV Files/chatgpt_portfolio_update.csv';
        $symbols = [];
        
        if (file_exists($portfolioFile)) {
            $handle = fopen($portfolioFile, 'r');
            $header = fgetcsv($handle); // Skip header
            
            while (($data = fgetcsv($handle)) !== FALSE) {
                if (!empty($data[0])) {
                    $symbols[] = strtoupper(trim($data[0]));
                }
            }
            fclose($handle);
        }
        
        return $symbols;
    }
    
    /**
     * Get data age in minutes for a symbol
     */
    private function getDataAge($symbol) {
        try {
            $latestData = $this->stockDAO->getLatestPriceData($symbol);
            if (!$latestData || !isset($latestData['date'])) {
                return null; // No data available
            }
            
            $dataTime = strtotime($latestData['date']);
            $currentTime = time();
            $ageSeconds = $currentTime - $dataTime;
            
            return round($ageSeconds / 60); // Convert to minutes
            
        } catch (Exception $e) {
            $this->logger->warning("Could not determine data age for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get analysis age in minutes for a symbol
     */
    private function getAnalysisAge($symbol) {
        try {
            $latestAnalysis = $this->stockDAO->getLatestAnalysis($symbol);
            if (!$latestAnalysis || !isset($latestAnalysis['created_at'])) {
                return null; // No analysis available
            }
            
            $analysisTime = strtotime($latestAnalysis['created_at']);
            $currentTime = time();
            $ageSeconds = $currentTime - $analysisTime;
            
            return round($ageSeconds / 60); // Convert to minutes
            
        } catch (Exception $e) {
            $this->logger->warning("Could not determine analysis age for {$symbol}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user activity score (0-1, higher = more active)
     */
    private function getUserActivityScore($userId) {
        // Simple implementation - could be enhanced with actual user activity tracking
        // For now, return moderate activity score
        return 0.7;
    }
    
    /**
     * Update user portfolio priority tracking
     */
    private function updateUserPortfolioPriority($userId, $symbols) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_portfolio_priority (user_id, stock_symbol, priority_score, updated_at)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE 
                priority_score = priority_score + 10,
                updated_at = NOW()
            ");
            
            foreach ($symbols as $symbol) {
                $basePriority = 100;
                $stmt->execute([$userId, $symbol, $basePriority]);
            }
            
        } catch (Exception $e) {
            $this->logger->warning("Could not update portfolio priority tracking: " . $e->getMessage());
        }
    }
    
    /**
     * Queue manual fetch job (triggered by admin or user request)
     */
    public function queueManualFetch($symbol, $userId = null, $days = 1) {
        $priority = $this->config['jobs']['priority_rules']['user_request'] ?? 3;
        
        $jobData = [
            'job_type' => 'stock_fetch',
            'subtype' => 'manual_request',
            'user_id' => $userId,
            'symbol' => $symbol,
            'priority' => $priority,
            'parameters' => [
                'symbol' => $symbol,
                'days' => $days,
                'source' => 'yahoo_finance',
                'triggered_by' => 'manual_request'
            ],
            'metadata' => [
                'user_id' => $userId,
                'symbol' => $symbol,
                'request_source' => 'manual',
                'created_by' => 'UserPortfolioJobManager'
            ]
        ];
        
        return $this->mqttBackend->addJob($jobData);
    }
    
    /**
     * Queue batch portfolio fetch (for scheduled updates)
     */
    public function queueScheduledBatchFetch($symbols = null) {
        if ($symbols === null) {
            $symbols = $this->getSharedPortfolioSymbols();
        }
        
        $priority = $this->config['jobs']['priority_rules']['scheduled_update'] ?? 5;
        $batchSize = $this->config['stock_jobs']['data_fetch']['batch_size'] ?? 10;
        
        $queuedJobs = [];
        $batches = array_chunk($symbols, $batchSize);
        
        foreach ($batches as $batch) {
            $jobData = [
                'job_type' => 'stock_batch_fetch',
                'subtype' => 'scheduled_update',
                'priority' => $priority,
                'parameters' => [
                    'symbols' => $batch,
                    'days' => 1,
                    'source' => 'yahoo_finance',
                    'triggered_by' => 'scheduled_update'
                ],
                'metadata' => [
                    'batch_size' => count($batch),
                    'request_source' => 'scheduled',
                    'created_by' => 'UserPortfolioJobManager'
                ]
            ];
            
            $jobId = $this->mqttBackend->addJob($jobData);
            if ($jobId) {
                $queuedJobs[] = $jobId;
            }
        }
        
        return $queuedJobs;
    }
    
    /**
     * Get portfolio job statistics
     */
    public function getPortfolioJobStats() {
        $stats = $this->mqttBackend->getQueueStats();
        
        // Add portfolio-specific statistics
        $portfolioStats = [
            'total_portfolio_symbols' => count($this->getSharedPortfolioSymbols()),
            'active_users_with_portfolios' => $this->getActiveUsersCount(),
            'avg_data_age' => $this->getAverageDataAge(),
            'queue_stats' => $stats
        ];
        
        return $portfolioStats;
    }
    
    /**
     * Get count of active users with portfolios
     */
    private function getActiveUsersCount() {
        try {
            $stmt = $this->db->query("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM user_portfolio_priority 
                WHERE updated_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Get average data age across portfolio symbols
     */
    private function getAverageDataAge() {
        $symbols = $this->getSharedPortfolioSymbols();
        $ages = [];
        
        foreach ($symbols as $symbol) {
            $age = $this->getDataAge($symbol);
            if ($age !== null) {
                $ages[] = $age;
            }
        }
        
        return empty($ages) ? null : round(array_sum($ages) / count($ages));
    }
}