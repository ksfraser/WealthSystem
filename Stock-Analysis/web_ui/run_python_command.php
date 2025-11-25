<?php
// run_python_command.php

// Accept either 'command_key' (preferred) or raw 'command' (legacy)
$command = null;
if (isset($_POST['command_key'])) {
    // Map command_key to actual script/args
    $map = [
    // Trade page custom commands
    'enhanced_trading_script_db_query' => '-c "from enhanced_trading_script import *; engine = create_trading_engine(\'micro\'); print(\'Trade data available via Python\')"',
        // Project root scripts
        'enhanced_trading_script' => 'enhanced_trading_script.py',
        'enhanced_automation' => 'enhanced_automation.py',
        'demo_simple_enhanced' => 'demo_simple_enhanced.py',
        'demo_enhanced_trading' => 'demo_enhanced_trading.py',
        'trading_script' => 'trading_script.py',
        'test_php_integration' => 'test_php_integration.py',
        'test_database_hosts' => 'test_database_hosts.py',
        'test_database_connection' => 'test_database_connection.py',
        'simple_automation' => 'simple_automation.py',
        'setup_database_tables' => 'setup_database_tables.py',
        'setup_database_config' => 'setup_database_config.py',
        'refactor_database_architecture' => 'refactor_database_architecture.py',
        'database_config' => 'database_config.py',
        'database_architect' => 'database_architect.py',

        // Scripts and CSV Files
        'scripts_and_csv_files_trading_script' => 'Scripts and CSV Files/Trading_Script.py',
        'scripts_and_csv_files_generate_graph' => 'Scripts and CSV Files/Generate_Graph.py',

        // Start Your Own
        'start_your_own_trading_script' => 'Start Your Own/Trading_Script.py',
        'start_your_own_generate_graph' => 'Start Your Own/Generate_Graph.py',

        // Stock-Analysis-Extension root
        'stock_analysis_extension_utils' => 'Stock-Analysis-Extension/utils.py',
        'stock_analysis_extension_setup' => 'Stock-Analysis-Extension/setup.py',
        'stock_analysis_extension_main' => 'Stock-Analysis-Extension/main.py',
        'stock_analysis_extension_dashboard' => 'Stock-Analysis-Extension/dashboard.py',

        // Stock-Analysis-Extension modules
        'stock_analysis_extension_modules_stock_data_fetcher' => 'Stock-Analysis-Extension/modules/stock_data_fetcher.py',
        'stock_analysis_extension_modules_stock_analyzer' => 'Stock-Analysis-Extension/modules/stock_analyzer.py',
        'stock_analysis_extension_modules_portfolio_manager' => 'Stock-Analysis-Extension/modules/portfolio_manager.py',
        'stock_analysis_extension_modules_front_accounting' => 'Stock-Analysis-Extension/modules/front_accounting.py',
        'stock_analysis_extension_modules_database_manager' => 'Stock-Analysis-Extension/modules/database_manager.py',

        // Stock-Analysis-Extension config
        'stock_analysis_extension_config_config_template' => 'Stock-Analysis-Extension/config/config_template.py',

        // scripts folder
        'scripts_import_csv_to_database' => 'scripts/import-csv-to-database.py',
    ];
    $command_key = $_POST['command_key'];
    if (!isset($map[$command_key])) {
        http_response_code(403);
        echo json_encode(['error' => 'Unknown command key']);
        exit;
    }
    $command = $map[$command_key];
} elseif (isset($_POST['command'])) {
    $command = $_POST['command'];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No command provided']);
    exit;
}

// Detect OS and adjust python command
if (stripos(PHP_OS, 'WIN') === 0) {
    $python = 'python';
} else {
    $python = 'python3';
}

    // Only allow safe commands (basic check)
    $allowed = [
    // Trade page custom commands
    '-c "from enhanced_trading_script import *; engine = create_trading_engine(\'micro\'); print(\'Trade data available via Python\')"',
    // Project root scripts
    'enhanced_trading_script.py',
    'enhanced_automation.py',
    'demo_simple_enhanced.py',
    'demo_enhanced_trading.py',
    'trading_script.py',
    'test_php_integration.py',
    'test_database_hosts.py',
    'test_database_connection.py',
    'simple_automation.py',
    'setup_database_tables.py',
    'setup_database_config.py',
    'refactor_database_architecture.py',
    'database_config.py',
    'database_architect.py',

    // Scripts and CSV Files
    'Scripts and CSV Files/Trading_Script.py',
    'Scripts and CSV Files/Generate_Graph.py',

    // Start Your Own
    'Start Your Own/Trading_Script.py',
    'Start Your Own/Generate_Graph.py',

    // Stock-Analysis-Extension root
    'Stock-Analysis-Extension/utils.py',
    'Stock-Analysis-Extension/setup.py',
    'Stock-Analysis-Extension/main.py',
    'Stock-Analysis-Extension/dashboard.py',

    // Stock-Analysis-Extension modules
    'Stock-Analysis-Extension/modules/stock_data_fetcher.py',
    'Stock-Analysis-Extension/modules/stock_analyzer.py',
    'Stock-Analysis-Extension/modules/portfolio_manager.py',
    'Stock-Analysis-Extension/modules/front_accounting.py',
    'Stock-Analysis-Extension/modules/database_manager.py',

    // Stock-Analysis-Extension config
    'Stock-Analysis-Extension/config/config_template.py',

    // scripts folder
    'scripts/import-csv-to-database.py',

    // Existing allowed commands
    'enhanced_automation.py --market_cap micro',
    'enhanced_automation.py --market_cap small',
    'enhanced_automation.py --market_cap mid',
    '-c "from enhanced_automation import *; engine = EnhancedAutomationEngine(\'micro\'); engine.start_session()"',
    '-c "from database_architect import *; arch = DatabaseArchitect(); arch.show_sessions()"',
    '-c "import os; os.system(\'taskkill /f /im python.exe\')"',
    '-c "from enhanced_automation import *; engine = EnhancedAutomationEngine(\'micro\'); engine.show_positions()"',
    '-c "from database_architect import *; arch = DatabaseArchitect(); arch.test_connections()"',
    '-c "import yaml; print(yaml.safe_load(open(\'db_config_refactored.yml\')))"',
    '-c "import pandas as pd; print(pd.read_csv(\'chatgpt_trade_log.csv\').tail())"',
];

$safe = false;
foreach ($allowed as $a) {
    if (trim($command) === $a) {
        $safe = true;
        break;
    }
}
if (!$safe) {
    http_response_code(403);
    echo json_encode(['error' => 'Command not allowed']);
    exit;
}

$projectRoot = realpath(__DIR__ . '/../');
if (!is_dir($projectRoot) || !file_exists($projectRoot . '/enhanced_trading_script.py')) {
    // Fallback: try to find the project root by searching upwards for enhanced_trading_script.py
    $try = __DIR__;
    while ($try !== dirname($try)) {
        if (file_exists($try . '/enhanced_trading_script.py')) {
            $projectRoot = $try;
            break;
        }
        $try = dirname($try);
    }
}
$full = $python . ' ' . $command;
// Change working directory to project root for script execution
$cwd = getcwd();
chdir($projectRoot);
$output = shell_exec($full . ' 2>&1');
chdir($cwd);
echo json_encode(['output' => $output]);
