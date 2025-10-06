<?php
// DEBUG: Show all errors for troubleshooting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
/**
 * Job Manager API Endpoint
 * Provides REST API for job queue management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../JobQueue.php';
require_once __DIR__ . '/UserAuthDAO.php';

try {
    $auth = new UserAuthDAO();
    $db = $auth->getPdo();
    $jobQueue = new JobQueue($db);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if ($method === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
    
    switch ($action) {
        case 'list':
            handleListJobs($jobQueue);
            break;
            
        case 'details':
            handleJobDetails($jobQueue);
            break;
            
        case 'create':
            handleCreateJob($jobQueue);
            break;
            
        case 'process':
            handleProcessJob($jobQueue);
            break;
            
        case 'delete':
            handleDeleteJob($jobQueue);
            break;
            
        case 'stats':
            handleJobStats($jobQueue);
            break;
            
        default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle list jobs request
 */
function handleListJobs($jobQueue) {
    $status = $_GET['status'] ?? null;
    $jobType = $_GET['job_type'] ?? null;
    $limit = (int)($_GET['limit'] ?? 50);
    
    $jobs = $jobQueue->getJobs($status, $jobType, $limit);
    
    echo json_encode([
        'success' => true,
        'jobs' => $jobs,
        'count' => count($jobs)
    ]);
}

/**
 * Handle job details request
 */
function handleJobDetails($jobQueue) {
    $jobId = $_GET['job_id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    $job = $jobQueue->getJobStatus($jobId);
    
    if (!$job) {
        throw new Exception('Job not found');
    }
    
    echo json_encode([
        'success' => true,
        'job' => $job
    ]);
}

/**
 * Handle create job request
 */
function handleCreateJob($jobQueue) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST method required');
    }
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $jobType = $input['job_type'] ?? '';
    $symbols = $input['symbols'] ?? '';
    $startDate = $input['start_date'] ?? null;
    $priority = (int)($input['priority'] ?? 0);
    
    if (!$jobType || !$symbols) {
        throw new Exception('Job type and symbols are required');
    }
    
    $jobId = null;
    
    switch ($jobType) {
        case 'stock_load':
            // Single stock
            $symbol = strtoupper(trim($symbols));
            $jobId = $jobQueue->queueStockLoad($symbol, $startDate, $priority);
            break;
            
        case 'portfolio_load':
            // Multiple stocks
            $symbolList = array_map('trim', array_map('strtoupper', explode(',', $symbols)));
            $symbolList = array_filter($symbolList); // Remove empty values
            
            if (empty($symbolList)) {
                throw new Exception('At least one symbol is required');
            }
            
            $jobId = $jobQueue->queuePortfolioLoad($symbolList, $startDate, $priority);
            break;
            
        default:
            throw new Exception('Invalid job type');
    }
    
    echo json_encode([
        'success' => true,
        'job_id' => $jobId,
        'message' => 'Job created successfully'
    ]);
}

/**
 * Handle process job request
 */
function handleProcessJob($jobQueue) {
    $result = $jobQueue->processNextJob(25); // 25 second timeout
    
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
}

/**
 * Handle delete job request
 */
function handleDeleteJob($jobQueue) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST method required');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $jobId = $input['job_id'] ?? null;
    
    if (!$jobId) {
        throw new Exception('Job ID is required');
    }
    
    // Get job details first
    $job = $jobQueue->getJobStatus($jobId);
    if (!$job) {
        throw new Exception('Job not found');
    }
    
    // Only allow deletion of pending or failed jobs
    if (!in_array($job['status'], ['pending', 'failed'])) {
        throw new Exception('Cannot delete job with status: ' . $job['status']);
    }
    
    // Delete the job
    $auth = new UserAuthDAO();
    $db = $auth->getPdo();
    
    $stmt = $db->prepare("DELETE FROM job_queue WHERE id = ?");
    $stmt->execute([$jobId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Job deleted successfully'
    ]);
}

/**
 * Handle job statistics request
 */
function handleJobStats($jobQueue) {
    $auth = new UserAuthDAO();
    $db = $auth->getPdo();
    
    // Get statistics
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM job_queue 
        GROUP BY status
    ");
    $stmt->execute();
    $statusStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get recent activity
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as jobs_created
        FROM job_queue 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    
    if (strpos($db->getAttribute(PDO::ATTR_DRIVER_NAME), 'sqlite') !== false) {
        $stmt = $db->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as jobs_created
            FROM job_queue 
            WHERE created_at >= datetime('now', '-7 days')
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
    }
    
    $stmt->execute();
    $recentActivity = $stmt->fetchAll();
    
    // Get job type distribution
    $stmt = $db->prepare("
        SELECT 
            job_type,
            COUNT(*) as count
        FROM job_queue 
        GROUP BY job_type
    ");
    $stmt->execute();
    $jobTypeStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'status_distribution' => $statusStats,
            'job_type_distribution' => $jobTypeStats,
            'recent_activity' => $recentActivity,
            'total_jobs' => array_sum($statusStats)
        ]
    ]);
}
?>