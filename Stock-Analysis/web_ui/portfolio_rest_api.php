<?php
// REST API for PortfolioDAO (all portfolio types)
// OpenAPI/Swagger spec will be provided separately
require_once __DIR__ . '/PortfolioDAO.php';
require_once __DIR__ . '/DbConfigClasses.php';
header('Content-Type: application/json');

function getPortfolioInfo($type) {
    $type = strtolower($type);
    switch ($type) {
        case 'micro':
            return [
                'csv' => '../data_micro_cap/micro_cap_portfolio.csv',
                'table' => 'portfolio_data',
                'dbclass' => 'MicroCapDatabaseConfig',
            ];
        case 'blue-chip':
            return [
                'csv' => '../data_blue_chip/blue_chip_cap_portfolio.csv',
                'table' => 'portfolios_blue_chip',
                'dbclass' => 'LegacyDatabaseConfig',
            ];
        case 'small':
        case 'small-cap':
            return [
                'csv' => '../data_small_cap/small_cap_portfolio.csv',
                'table' => 'portfolios_small_cap',
                'dbclass' => 'LegacyDatabaseConfig',
            ];
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown portfolio type']);
            exit;
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? $_POST['type'] ?? null;
if (!$type) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing portfolio type']);
    exit;
}
$info = getPortfolioInfo($type);
$dao = new PortfolioDAO($info['csv'], $info['table'], $info['dbclass']);

switch ($method) {
    case 'GET':
        $rows = $dao->readPortfolio();
        echo json_encode(['data' => $rows, 'errors' => $dao->getErrors()]);
        break;
    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        $rows = $input['rows'] ?? [];
        $ok = $dao->writePortfolio($rows);
        echo json_encode(['success' => $ok, 'errors' => $dao->getErrors()]);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}
