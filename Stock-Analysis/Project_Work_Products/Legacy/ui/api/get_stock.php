<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\StockInfo\StockInfo;
use Ksfraser\StockInfo\DatabaseFactory;

try {
    if (!isset($_GET['id'])) {
        throw new Exception('Stock ID is required');
    }

    $legacyConfig = DatabaseConfig::getLegacyConfig();
    
    $database = DatabaseFactory::getInstance($legacyConfig);
    $stockModel = new StockInfo($database->getConnection());
    
    $stock = $stockModel->find((int)$_GET['id']);
    
    if (!$stock) {
        throw new Exception('Stock not found');
    }

    echo json_encode([
        'status' => 'success',
        'stock' => $stock
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
