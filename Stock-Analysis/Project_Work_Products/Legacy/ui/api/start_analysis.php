<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\StockInfo\TechnicalAnalysisJobs;
use Ksfraser\StockInfo\DatabaseFactory;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('POST method required');
    }

    $type = $_POST['type'] ?? 'all';
    $stockId = isset($_POST['stockId']) ? (int)$_POST['stockId'] : null;

    $legacyConfig = DatabaseConfig::getLegacyConfig();
    
    $database = DatabaseFactory::getInstance($legacyConfig);
    $jobsModel = new TechnicalAnalysisJobs($database->getConnection());
    
    // Create analysis job
    $jobData = [
        'job_type' => $type,
        'status' => 'pending',
        'parameters' => json_encode(['stockId' => $stockId]),
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $jobId = $jobsModel->create($jobData);
    
    if (!$jobId) {
        throw new Exception('Failed to create analysis job');
    }

    echo json_encode([
        'status' => 'success',
        'jobId' => $jobId,
        'message' => 'Analysis job started successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
