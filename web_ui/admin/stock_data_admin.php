<?php
require_once '../UserAuthDAO.php';
require_once '../StockDAO.php';
require_once '../../UserPortfolioJobManager.php';

// Check authentication and admin status
$auth = new UserAuthDAO();
$auth->requireAdmin();

// Get database connection from UserAuthDAO
$db = $auth->getPdo();

// Initialize StockDAO and Portfolio Job Manager
$stockDAO = new StockDAO($db);

// Initialize Portfolio Job Manager with fallback config
$portfolioJobManager = null;
try {
    $configFile = '../../stock_job_processor.yml';
    if (file_exists($configFile) && function_exists('yaml_parse_file')) {
        $config = yaml_parse_file($configFile);
    } else {
        // Fallback configuration
        $config = [
            'job_processor' => [
                'stock_jobs' => [
                    'portfolio_priority' => ['data_staleness_threshold' => 30],
                    'analysis' => ['cache_ttl' => 360]
                ],
                'jobs' => [
                    'priority_rules' => [
                        'user_request' => 3,
                        'scheduled_update' => 5
                    ]
                ]
            ]
        ];
    }
    
    // Simple logger
    $logger = new class {
        public function info($message) { error_log("INFO: " . $message); }
        public function warning($message) { error_log("WARNING: " . $message); }
        public function error($message) { error_log("ERROR: " . $message); }
        public function debug($message) { error_log("DEBUG: " . $message); }
    };
    
    $portfolioJobManager = new UserPortfolioJobManager($config['job_processor'], $logger, $db);
} catch (Exception $e) {
    error_log("Could not initialize Portfolio Job Manager: " . $e->getMessage());
}
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'fetch_single':
                $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
                if (!empty($symbol)) {
                    if ($portfolioJobManager) {
                        // Use job queue system
                        $jobId = $portfolioJobManager->queueManualFetch($symbol, $_SESSION['user_id'] ?? null, 1);
                        if ($jobId) {
                            $message = "Queued priority fetch job for $symbol (Job ID: $jobId)";
                        } else {
                            $error = "Failed to queue fetch job for $symbol";
                        }
                    } else {
                        // Fallback to direct fetch
                        $result = fetchSingleStockData($symbol);
                        if ($result['success']) {
                            $message = "Successfully fetched data for $symbol";
                        } else {
                            $error = "Failed to fetch data for $symbol: " . $result['error'];
                        }
                    }
                } else {
                    $error = "Please enter a valid stock symbol";
                }
                break;
                
            case 'fetch_portfolio':
                if ($portfolioJobManager) {
                    // Use job queue system for batch portfolio fetch
                    $jobIds = $portfolioJobManager->queueScheduledBatchFetch();
                    if (!empty($jobIds)) {
                        $message = "Queued " . count($jobIds) . " batch fetch jobs for portfolio stocks";
                    } else {
                        $error = "Failed to queue portfolio fetch jobs";
                    }
                } else {
                    // Fallback to direct fetch
                    $result = fetchPortfolioData();
                    if ($result['success']) {
                        $message = "Successfully fetched data for all portfolio stocks: " . implode(', ', $result['symbols']);
                    } else {
                        $error = "Portfolio fetch failed: " . $result['error'];
                    }
                }
                break;
                
            case 'populate_historical':
                $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
                $days = intval($_POST['days'] ?? 365);
                if (!empty($symbol)) {
                    if ($portfolioJobManager) {
                        // Use job queue system for historical data
                        $jobId = $portfolioJobManager->queueManualFetch($symbol, $_SESSION['user_id'] ?? null, $days);
                        if ($jobId) {
                            $message = "Queued historical data job for $symbol ($days days) - Job ID: $jobId";
                        } else {
                            $error = "Failed to queue historical data job for $symbol";
                        }
                    } else {
                        // Fallback to direct population
                        $result = populateHistoricalData($symbol, $days);
                        if ($result['success']) {
                            $message = "Successfully populated $days days of historical data for $symbol";
                        } else {
                            $error = "Historical data population failed for $symbol: " . $result['error'];
                        }
                    }
                } else {
                    $error = "Please enter a valid stock symbol";
                }
                break;
                
            case 'enable_auto_fetch':
                $result = enableAutoFetch();
                if ($result['success']) {
                    $message = "Auto-fetch enabled. Daily data will be fetched when users log in.";
                } else {
                    $error = "Failed to enable auto-fetch: " . $result['error'];
                }
                break;
                
            case 'disable_auto_fetch':
                $result = disableAutoFetch();
                if ($result['success']) {
                    $message = "Auto-fetch disabled.";
                } else {
                    $error = "Failed to disable auto-fetch: " . $result['error'];
                }
                break;
        }
    }
}

// Check auto-fetch status
$autoFetchEnabled = isAutoFetchEnabled();

function fetchSingleStockData($symbol) {
    global $stockDAO;
    
    try {
        // Execute Python script to fetch data
        $command = "cd \"c:\\Users\\prote\\Documents\\ChatGPT-Micro-Cap-Experiment\" && python fetch_historical_data.py \"$symbol\" 1";
        $output = shell_exec($command);
        
        if ($output === null) {
            return ['success' => false, 'error' => 'Failed to execute Python script'];
        }
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
            return ['success' => false, 'error' => 'Invalid JSON response from Python script'];
        }
        
        // Store the data using StockDAO
        foreach ($data['data'] as $record) {
            $stockDAO->upsertPriceData($symbol, [
                'date' => $record['Date'],
                'open' => $record['Open'],
                'high' => $record['High'],
                'low' => $record['Low'],
                'close' => $record['Close'],
                'volume' => $record['Volume']
            ]);
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function fetchPortfolioData() {
    global $stockDAO;
    
    // Get list of symbols from portfolio
    $symbols = getPortfolioSymbols();
    $successful = [];
    $errors = [];
    
    foreach ($symbols as $symbol) {
        $result = fetchSingleStockData($symbol);
        if ($result['success']) {
            $successful[] = $symbol;
        } else {
            $errors[] = "$symbol: " . $result['error'];
        }
    }
    
    if (empty($errors)) {
        return ['success' => true, 'symbols' => $successful];
    } else {
        return ['success' => false, 'error' => implode('; ', $errors)];
    }
}

function populateHistoricalData($symbol, $days) {
    global $stockDAO;
    
    try {
        // Execute Python script to fetch historical data
        $command = "cd \"c:\\Users\\prote\\Documents\\ChatGPT-Micro-Cap-Experiment\" && python fetch_historical_data.py \"$symbol\" $days";
        $output = shell_exec($command);
        
        if ($output === null) {
            return ['success' => false, 'error' => 'Failed to execute Python script'];
        }
        
        $data = json_decode($output, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['data'])) {
            return ['success' => false, 'error' => 'Invalid JSON response from Python script'];
        }
        
        // Store the historical data using StockDAO
        foreach ($data['data'] as $record) {
            $stockDAO->upsertPriceData($symbol, [
                'date' => $record['Date'],
                'open' => $record['Open'],
                'high' => $record['High'],
                'low' => $record['Low'],
                'close' => $record['Close'],
                'volume' => $record['Volume']
            ]);
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function getPortfolioSymbols() {
    // Read from portfolio CSV file
    $portfolioFile = '../Scripts and CSV Files/chatgpt_portfolio_update.csv';
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
    
    return array_unique($symbols);
}

function enableAutoFetch() {
    try {
        // Create/update auto-fetch configuration file
        $config = ['auto_fetch_enabled' => true, 'last_fetch_date' => null];
        file_put_contents('../data/auto_fetch_config.json', json_encode($config));
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function disableAutoFetch() {
    try {
        // Update auto-fetch configuration file
        $config = ['auto_fetch_enabled' => false, 'last_fetch_date' => null];
        file_put_contents('../data/auto_fetch_config.json', json_encode($config));
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function isAutoFetchEnabled() {
    $configFile = '../data/auto_fetch_config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        return isset($config['auto_fetch_enabled']) && $config['auto_fetch_enabled'] === true;
    }
    return false;
}

// Get current portfolio symbols for display
$portfolioSymbols = getPortfolioSymbols();

// Get job queue statistics if available
$jobStats = null;
if ($portfolioJobManager) {
    try {
        $jobStats = $portfolioJobManager->getPortfolioJobStats();
    } catch (Exception $e) {
        error_log("Could not get job statistics: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Data Management - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section h3 {
            margin-top: 0;
            color: #007bff;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-width: 300px;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
            margin-top: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }
        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-enabled {
            background-color: #d4edda;
            color: #155724;
        }
        .status-disabled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .portfolio-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        .symbol-tag {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .back-link {
            margin-bottom: 20px;
        }
        .back-link a {
            color: #007bff;
            text-decoration: none;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="index.php">← Back to Admin Dashboard</a>
        </div>
        
        <div class="header">
            <h1>Stock Data Management</h1>
            <p>Manage stock data fetching and historical data population</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Auto-Fetch Status -->
        <div class="section">
            <h3>Automatic Data Fetching</h3>
            <p>Status: 
                <span class="status-badge <?php echo $autoFetchEnabled ? 'status-enabled' : 'status-disabled'; ?>">
                    <?php echo $autoFetchEnabled ? 'ENABLED' : 'DISABLED'; ?>
                </span>
            </p>
            <p>When enabled, daily stock data will be automatically fetched when users log in.</p>
            
            <?php if ($autoFetchEnabled): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="disable_auto_fetch">
                    <button type="submit" class="btn btn-danger">Disable Auto-Fetch</button>
                </form>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="enable_auto_fetch">
                    <button type="submit" class="btn btn-success">Enable Auto-Fetch</button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Fetch Single Stock -->
        <div class="section">
            <h3>Fetch Single Stock Data</h3>
            <p>Fetch the latest price data for a specific stock symbol.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="fetch_single">
                <div class="form-group">
                    <label for="symbol">Stock Symbol:</label>
                    <input type="text" id="symbol" name="symbol" placeholder="e.g., AAPL" required>
                </div>
                <button type="submit" class="btn">Fetch Data</button>
            </form>
        </div>

        <!-- Fetch Portfolio Data -->
        <div class="section">
            <h3>Fetch All Portfolio Data</h3>
            <p>Fetch latest data for all stocks in your portfolio.</p>
            
            <?php if (!empty($portfolioSymbols)): ?>
                <p><strong>Current Portfolio Symbols:</strong></p>
                <div class="portfolio-list">
                    <?php foreach ($portfolioSymbols as $symbol): ?>
                        <span class="symbol-tag"><?php echo htmlspecialchars($symbol); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><em>No portfolio symbols found. Check your portfolio CSV file.</em></p>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="fetch_portfolio">
                <button type="submit" class="btn">Fetch Portfolio Data</button>
            </form>
        </div>

        <!-- Populate Historical Data -->
        <div class="section">
            <h3>Populate Historical Data</h3>
            <p>Populate historical price data for a specific stock symbol.</p>
            
            <div style="background-color: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 10px; border-radius: 4px; margin-bottom: 15px;">
                <strong>💡 Need more than 5 years?</strong> 
                <a href="progressive_data_loader.php" style="color: #0c5460; font-weight: bold;">Use Progressive Historical Loader</a> 
                to load complete historical data by overcoming Yahoo Finance limitations.
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="populate_historical">
                <div class="form-group">
                    <label for="hist_symbol">Stock Symbol:</label>
                    <input type="text" id="hist_symbol" name="symbol" placeholder="e.g., AAPL" required>
                </div>
                <div class="form-group">
                    <label for="days">Number of Days:</label>
                    <input type="number" id="days" name="days" value="365" min="1" max="1825" required>
                    <small>Maximum 1825 days (5 years)</small>
                </div>
                <button type="submit" class="btn">Populate Historical Data</button>
            </form>
        </div>

        <!-- Job Queue Status -->
        <?php if ($jobStats): ?>
        <div class="section">
            <h3>📊 Job Queue Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div>
                    <strong>Portfolio Symbols:</strong><br>
                    <span class="status-badge status-online"><?php echo $jobStats['total_portfolio_symbols']; ?></span>
                </div>
                <div>
                    <strong>Active Users (24h):</strong><br>
                    <span class="status-badge status-online"><?php echo $jobStats['active_users_with_portfolios']; ?></span>
                </div>
                <div>
                    <strong>Avg Data Age:</strong><br>
                    <span class="status-badge <?php echo ($jobStats['avg_data_age'] ?? 0) < 60 ? 'status-online' : 'status-offline'; ?>">
                        <?php echo ($jobStats['avg_data_age'] ?? 'N/A') . ' min'; ?>
                    </span>
                </div>
                <div>
                    <strong>Queue System:</strong><br>
                    <span class="status-badge status-online">MQTT Active</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Data Management Info -->
        <div class="section">
            <h3>System Information</h3>
            <ul>
                <li><strong>Database:</strong> microcap_trading</li>
                <li><strong>Data Source:</strong> Yahoo Finance API</li>
                <li><strong>Storage:</strong> Individual tables per stock symbol</li>
                <li><strong>Portfolio Source:</strong> Scripts and CSV Files/chatgpt_portfolio_update.csv</li>
                <li><strong>Job Processing:</strong> <?php echo $portfolioJobManager ? 'MQTT Priority Queue System' : 'Direct Processing (Fallback)'; ?></li>
                <li><strong>User Login Trigger:</strong> Portfolio priority jobs queued automatically</li>
            </ul>
        </div>
    </div>
</body>
</html>