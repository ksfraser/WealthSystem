<?php

/**
 * Strategy Configuration API
 * 
 * Handles AJAX requests for strategy parameter configuration.
 * Provides endpoints for listing, getting, setting, and exporting parameters.
 */

// Load Composer autoloader only
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Services\StrategyConfigurationService;
use App\Repositories\StrategyParametersRepository;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Initialize services
    $databasePath = __DIR__ . '/../../storage/database/stock_analysis.db';
    $parametersRepo = new StrategyParametersRepository($databasePath);
    $configService = new StrategyConfigurationService($parametersRepo);

    // Get action from GET or POST JSON body
    $action = $_GET['action'] ?? '';
    $postInput = null;
    
    if (empty($action) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $postInput = json_decode(file_get_contents('php://input'), true);
        $action = $postInput['action'] ?? '';
    }

    switch ($action) {
        case 'list_strategies':
            // Get list of all available strategies
            $strategies = $configService->getAvailableStrategies();
            echo json_encode(['success' => true, 'strategies' => $strategies]);
            break;

        case 'get_config':
            // Get configuration for a specific strategy
            $strategy = $_GET['strategy'] ?? '';
            
            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            $parameters = $configService->getConfigurationMetadata($strategy);
            echo json_encode(['success' => true, 'parameters' => $parameters]);
            break;

        case 'save_config':
            // Save/update configuration
            $input = $postInput ?? json_decode(file_get_contents('php://input'), true);
            $strategy = $input['strategy'] ?? '';
            $parameters = $input['parameters'] ?? [];

            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            if (empty($parameters)) {
                throw new Exception('No parameters provided');
            }

            // Validate each parameter
            $metadata = $configService->getConfigurationMetadata($strategy);
            $metadataMap = [];
            foreach ($metadata as $meta) {
                $metadataMap[$meta['parameter_key']] = $meta;
            }

            foreach ($parameters as $key => $value) {
                if (!isset($metadataMap[$key])) {
                    continue; // Skip unknown parameters
                }

                $validation = $configService->validateParameter($metadataMap[$key], $value);
                if (!$validation['valid']) {
                    throw new Exception("Invalid value for {$key}: {$validation['message']}");
                }
            }

            $success = $configService->updateConfiguration($strategy, $parameters);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Configuration saved']);
            } else {
                throw new Exception('Failed to save configuration');
            }
            break;

        case 'reset_config':
            // Reset to defaults (currently not implemented in repo)
            $input = $postInput ?? json_decode(file_get_contents('php://input'), true);
            $strategy = $input['strategy'] ?? '';

            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            // For now, return success (would need to implement actual reset logic)
            echo json_encode([
                'success' => false, 
                'message' => 'Reset functionality not yet implemented. Please re-run database migration.'
            ]);
            break;

        case 'export':
            // Export configuration as JSON
            $strategy = $_GET['strategy'] ?? '';
            
            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            $json = $configService->exportToJson($strategy);
            
            // Return as downloadable JSON
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="strategy_config.json"');
            echo $json;
            break;

        case 'import':
            // Import configuration from JSON
            $input = $postInput ?? json_decode(file_get_contents('php://input'), true);
            $strategy = $input['strategy'] ?? '';
            $json = $input['config_json'] ?? '';

            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            if (empty($json)) {
                throw new Exception('Configuration JSON is required');
            }

            $success = $configService->importFromJson($strategy, $json);
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Configuration imported']);
            } else {
                throw new Exception('Failed to import configuration');
            }
            break;

        case 'get_categories':
            // Get parameters grouped by category
            $strategy = $_GET['strategy'] ?? '';
            
            if (empty($strategy)) {
                throw new Exception('Strategy name is required');
            }

            $grouped = $configService->getConfigurationByCategory($strategy);
            echo json_encode(['success' => true, 'categories' => $grouped]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
