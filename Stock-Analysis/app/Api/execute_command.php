<?php

require_once __DIR__ . '/../app/bootstrap.php';

use App\Security\CsrfProtection;
use App\Security\SessionManager;
use App\Security\InputValidator;
use App\Security\CommandExecutor;

// Secure session management
SessionManager::start();

// CSRF Protection
header('Content-Type: application/json');

try {
    // Validate CSRF token
    CsrfProtection::verifyRequest();
    
    // Check authentication
    if (!SessionManager::isAuthenticated()) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // Get command key from POST
    $commandKey = $_POST['command_key'] ?? null;
    
    if (!$commandKey) {
        http_response_code(400);
        echo json_encode(['error' => 'No command key provided']);
        exit;
    }
    
    // Validate command key format
    $commandKey = InputValidator::validateAlphanumeric($commandKey);
    if (!$commandKey) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid command key format']);
        exit;
    }
    
    // Map command keys to scripts (whitelist)
    $scriptMap = [
        // Enhanced trading
        'enhanced_automation' => [
            'script' => 'enhanced_automation.py',
            'allowed_args' => ['market_cap']
        ],
        'simple_automation' => [
            'script' => 'simple_automation.py',
            'allowed_args' => []
        ],
        'trading_script' => [
            'script' => 'trading_script.py',
            'allowed_args' => []
        ],
        
        // Database operations
        'setup_database_tables' => [
            'script' => 'setup_database_tables.py',
            'allowed_args' => []
        ],
        'test_database_connection' => [
            'script' => 'test_database_connection.py',
            'allowed_args' => []
        ],
        
        // Extension scripts
        'stock_data_fetcher' => [
            'script' => 'Stock-Analysis-Extension/modules/stock_data_fetcher.py',
            'allowed_args' => []
        ],
        
        // Scripts and CSV Files
        'generate_graph' => [
            'script' => 'Scripts and CSV Files/Generate_Graph.py',
            'allowed_args' => []
        ]
    ];
    
    // Validate command key exists
    if (!isset($scriptMap[$commandKey])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unknown command key']);
        exit;
    }
    
    $scriptConfig = $scriptMap[$commandKey];
    
    // Get and validate arguments
    $args = [];
    foreach ($scriptConfig['allowed_args'] as $argName) {
        if (isset($_POST[$argName])) {
            // Validate based on argument name
            switch ($argName) {
                case 'market_cap':
                    $value = InputValidator::validateWhitelist(
                        $_POST[$argName],
                        ['micro', 'small', 'mid']
                    );
                    if ($value) {
                        $args[$argName] = $value;
                    }
                    break;
                    
                default:
                    $args[$argName] = InputValidator::sanitizeString($_POST[$argName]);
            }
        }
    }
    
    // Execute command securely
    $executor = new CommandExecutor(dirname(__DIR__));
    $result = $executor->executePythonScript(
        $scriptConfig['script'],
        $args,
        timeout: 60
    );
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'output' => $result['output']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $result['error'] ?? 'Execution failed',
            'output' => $result['output'] ?? ''
        ]);
    }
    
} catch (\RuntimeException $e) {
    // CSRF validation failed
    http_response_code(403);
    echo json_encode([
        'error' => 'CSRF validation failed'
    ]);
    
} catch (\Exception $e) {
    // Log error (implement proper logging)
    error_log('Command execution error: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error'
    ]);
}
