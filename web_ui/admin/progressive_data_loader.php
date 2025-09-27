<?php
/**
 * Progressive Historical Data Web Interface
 * Admin interface for loading complete historical data progressively
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../UserAuthDAO.php';
require_once '../StockDAO.php';
require_once '../../ProgressiveHistoricalLoader.php';

// Check authentication and admin status
$auth = new UserAuthDAO();
$auth->requireAdmin();

// Get database connection
$db = $auth->getPdo();

$message = '';
$error = '';
$progressInfo = null;
$loadResults = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'check_progress':
                $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
                if (!empty($symbol)) {
                    try {
                        error_log("Creating ProgressiveHistoricalLoader for symbol: {$symbol}");
                        $loader = new ProgressiveHistoricalLoader($db);
                        error_log("ProgressiveHistoricalLoader created, getting progress info");
                        $progressInfo = $loader->getProgressInfo($symbol);
                        error_log("Progress info retrieved: " . print_r($progressInfo, true));
                        $message = "Progress information retrieved for {$symbol}";
                    } catch (Exception $e) {
                        $errorMessage = "Failed to get progress info: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine();
                        error_log("Progress check error: " . $errorMessage);
                        error_log("Stack trace: " . $e->getTraceAsString());
                        $error = $errorMessage;
                    }
                } else {
                    $error = "Please enter a valid stock symbol";
                }
                break;
                
            case 'load_progressive':
                $symbol = trim(strtoupper($_POST['symbol'] ?? ''));
                $startDate = trim($_POST['start_date'] ?? '');
                
                if (!empty($symbol)) {
                    try {
                        // Use web logger that outputs to session for display
                        $logger = new WebProgressLogger();
                        $loader = new ProgressiveHistoricalLoader($db, $logger);
                        
                        $loadResults = $loader->loadAllHistoricalData($symbol, $startDate ?: null);
                        
                        if ($loadResults['success']) {
                            $message = "Successfully loaded historical data for {$symbol}. " .
                                      "Processed {$loadResults['chunks_processed']} chunks, " .
                                      "total {$loadResults['total_records']} records.";
                        } else {
                            $error = "Failed to load historical data: " . $loadResults['error'];
                        }
                        
                        // Get updated progress info
                        $progressInfo = $loader->getProgressInfo($symbol);
                        
                    } catch (Exception $e) {
                        $error = "Progressive load failed: " . $e->getMessage();
                    }
                } else {
                    $error = "Please enter a valid stock symbol";
                }
                break;
                
            case 'load_portfolio_progressive':
                try {
                    $portfolioSymbols = getPortfolioSymbols();
                    if (empty($portfolioSymbols)) {
                        $error = "No portfolio symbols found";
                        break;
                    }
                    
                    $logger = new WebProgressLogger();
                    $loader = new ProgressiveHistoricalLoader($db, $logger);
                    
                    $loadResults = $loader->loadMultipleSymbols($portfolioSymbols);
                    
                    $successCount = count(array_filter($loadResults, function($r) { return $r['success']; }));
                    $totalSymbols = count($portfolioSymbols);
                    
                    if ($successCount > 0) {
                        $message = "Portfolio progressive load completed: {$successCount}/{$totalSymbols} symbols successful";
                    } else {
                        $error = "All portfolio symbols failed to load";
                    }
                    
                } catch (Exception $e) {
                    $error = "Portfolio progressive load failed: " . $e->getMessage();
                }
                break;
                
            case 'process_csv':
                $csvDirectory = trim($_POST['csv_directory'] ?? '');
                
                if (empty($csvDirectory)) {
                    $error = "Please specify a CSV directory path";
                    break;
                }
                
                if (!is_dir($csvDirectory)) {
                    $error = "CSV directory does not exist: {$csvDirectory}";
                    break;
                }
                
                try {
                    $logger = new WebProgressLogger();
                    $loader = new ProgressiveHistoricalLoader($db, $logger);
                    
                    $csvResults = $loader->processCsvFiles($csvDirectory);
                    
                    if ($csvResults['success']) {
                        $successCount = 0;
                        $totalRecords = 0;
                        
                        foreach ($csvResults['results'] as $symbol => $result) {
                            if ($result['success']) {
                                $successCount++;
                                $totalRecords += $result['records'];
                            }
                        }
                        
                        $message = "CSV processing completed: {$successCount}/{$csvResults['processed_files']} files successful, {$totalRecords} total records imported";
                        $loadResults = $csvResults; // Show detailed results
                    } else {
                        $error = "CSV processing failed: " . $csvResults['error'];
                    }
                    
                } catch (Exception $e) {
                    $error = "CSV processing failed: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get portfolio symbols
function getPortfolioSymbols() {
    $portfolioFile = __DIR__ . '/../../Scripts and CSV Files/chatgpt_portfolio_update.csv';
    $symbols = [];
    
    if (file_exists($portfolioFile)) {
        $handle = fopen($portfolioFile, 'r');
        $header = fgetcsv($handle, 0, ',', '"', '\\'); // Skip header
        
        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
            if (!empty($data[0])) {
                $symbols[] = strtoupper(trim($data[0]));
            }
        }
        fclose($handle);
    }
    
    return array_unique($symbols);
}

/**
 * Web logger that stores messages for display
 */
class WebProgressLogger {
    private $messages = [];
    
    public function info($message) {
        $this->messages[] = ['level' => 'info', 'message' => $message, 'time' => date('H:i:s')];
    }
    
    public function warning($message) {
        $this->messages[] = ['level' => 'warning', 'message' => $message, 'time' => date('H:i:s')];
    }
    
    public function error($message) {
        $this->messages[] = ['level' => 'error', 'message' => $message, 'time' => date('H:i:s')];
    }
    
    public function debug($message) {
        $this->messages[] = ['level' => 'debug', 'message' => $message, 'time' => date('H:i:s')];
    }
    
    public function getMessages() {
        return $this->messages;
    }
}

$portfolioSymbols = getPortfolioSymbols();

// Get all stocks for dropdown (do this once)
$allStocks = [];
try {
    $stocksStmt = $db->query("SELECT symbol, name FROM stocks WHERE is_active = 1 ORDER BY symbol ASC");
    $allStocks = $stocksStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $allStocks = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progressive Historical Data Loader - Admin</title>
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
        input[type="text"], input[type="date"] {
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
        .btn-warning {
            background-color: #ffc107;
            color: #212529;
        }
        .btn-warning:hover {
            background-color: #e0a800;
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
        .progress-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-top: 15px;
        }
        .progress-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .progress-info th, .progress-info td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .progress-info th {
            background-color: #e9ecef;
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
        .info-box {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .portfolio-symbols {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        .symbol-tag {
            background-color: #e9ecef;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        /* Stock Input Group Styles */
        .stock-input-group {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }
        .stock-search-container {
            position: relative;
            flex: 1;
            min-width: 250px;
        }
        .stock-autocomplete {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            transition: border-color 0.3s;
        }
        .stock-dropdown {
            padding: 8px 12px;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            background: white;
            min-width: 200px;
            max-width: 300px;
            transition: border-color 0.3s;
        }
        .stock-dropdown:focus, .stock-autocomplete:focus {
            border-color: #007bff;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }

        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .autocomplete-item {
            padding: 10px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        .autocomplete-item:hover, .autocomplete-item.selected {
            background-color: #f8f9fa;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-symbol {
            font-weight: bold;
            color: #007bff;
        }
        .autocomplete-name {
            color: #666;
            font-size: 13px;
        }
        .autocomplete-sector {
            font-size: 11px;
            color: #999;
            margin-top: 2px;
        }
        .stock-actions {
            margin-top: 8px;
        }
        .add-stock-link {
            color: #28a745;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
        }
        .add-stock-link:hover {
            text-decoration: underline;
        }
        .loading-indicator {
            padding: 10px;
            text-align: center;
            color: #666;
            font-size: 13px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .stock-input-group {
                flex-direction: column;
                gap: 8px;
            }
            .stock-dropdown {
                min-width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="stock_data_admin.php">← Back to Stock Data Management</a>
        </div>
        
        <div class="header">
            <h1>Progressive Historical Data Loader</h1>
            <p>Load complete historical data by overcoming Yahoo Finance 5-year limitations</p>
        </div>

        <div class="info-box">
            <h4>📊 How It Works</h4>
            <p>Yahoo Finance limits historical data requests to approximately 5 years. This tool automatically:</p>
            <ul>
                <li>Detects existing data ranges in your database</li>
                <li>Breaks large date ranges into 5-year chunks</li>
                <li>Fetches data progressively from oldest to newest</li>
                <li>Skips chunks where data already exists</li>
                <li>Handles rate limiting with delays between requests</li>
            </ul>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Check Progress Section -->
        <div class="section">
            <h3>📈 Check Data Progress</h3>
            <p>Check current historical data coverage for a symbol</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="check_progress">
                <div class="form-group">
                    <label for="check_symbol">Stock Symbol:</label>
                    <div class="stock-input-group">
                        <div class="stock-search-container">
                            <input type="text" 
                                   id="check_symbol" 
                                   name="symbol" 
                                   class="stock-autocomplete" 
                                   placeholder="Start typing symbol or company name..."
                                   autocomplete="off"
                                   required>
                            <input type="hidden" id="check_symbol_value" name="symbol_confirmed">
                            <div id="check_symbol_results" class="autocomplete-results"></div>
                        </div>
                        <select id="check_symbol_dropdown" class="stock-dropdown" onchange="selectFromDropdown('check_symbol', this.value)">
                            <option value="">-- Select from list --</option>
                            <?php
                            if (!empty($allStocks)) {
                                foreach ($allStocks as $stock) {
                                    echo '<option value="' . htmlspecialchars($stock['symbol']) . '">';
                                    echo htmlspecialchars($stock['symbol'] . ' - ' . $stock['name']);
                                    echo '</option>';
                                }
                            } else {
                                echo '<option value="">No stocks available</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="stock-actions">
                        <a href="add_stock.php" class="add-stock-link">+ Add New Stock to Registry</a>
                    </div>
                </div>
                <button type="submit" class="btn">Check Progress</button>
            </form>

            <?php if ($progressInfo): ?>
            <div class="progress-info">
                <h4>Progress Information for <?php echo htmlspecialchars($progressInfo['symbol']); ?></h4>
                <table>
                    <tr>
                        <th>Has Data</th>
                        <td><?php echo $progressInfo['has_data'] ? '✅ Yes' : '❌ No'; ?></td>
                    </tr>
                    <?php if ($progressInfo['has_data']): ?>
                    <tr>
                        <th>Oldest Date</th>
                        <td><?php echo htmlspecialchars($progressInfo['oldest_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Latest Date</th>
                        <td><?php echo htmlspecialchars($progressInfo['latest_date']); ?></td>
                    </tr>
                    <tr>
                        <th>Total Records</th>
                        <td><?php echo number_format($progressInfo['total_records']); ?></td>
                    </tr>
                    <tr>
                        <th>Date Span</th>
                        <td><?php echo $progressInfo['date_span_years']; ?> years (<?php echo number_format($progressInfo['date_span_days']); ?> days)</td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <td colspan="2"><em>No historical data found for this symbol</em></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Progressive Load Single Symbol -->
        <div class="section">
            <h3>⏳ Progressive Load Single Symbol</h3>
            <p>Load all available historical data for a specific symbol using progressive 5-year chunks</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="load_progressive">
                <div class="form-group">
                    <label for="prog_symbol">Stock Symbol:</label>
                    <div class="stock-input-group">
                        <div class="stock-search-container">
                            <input type="text" 
                                   id="prog_symbol" 
                                   name="symbol" 
                                   class="stock-autocomplete" 
                                   placeholder="Start typing symbol or company name..."
                                   autocomplete="off"
                                   required>
                            <input type="hidden" id="prog_symbol_value" name="symbol_confirmed">
                            <div id="prog_symbol_results" class="autocomplete-results"></div>
                        </div>
                        <select id="prog_symbol_dropdown" class="stock-dropdown" onchange="selectFromDropdown('prog_symbol', this.value)">
                            <option value="">-- Select from list --</option>
                            <?php
                            if (!empty($allStocks)) {
                                foreach ($allStocks as $stock) {
                                    echo '<option value="' . htmlspecialchars($stock['symbol']) . '">';
                                    echo htmlspecialchars($stock['symbol'] . ' - ' . $stock['name']);
                                    echo '</option>';
                                }
                            } else {
                                echo '<option value="">No stocks available</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="stock-actions">
                        <a href="add_stock.php" class="add-stock-link">+ Add New Stock to Registry</a>
                    </div>
                </div>
                <div class="form-group">
                    <label for="start_date">Start From Date (optional):</label>
                    <input type="date" id="start_date" name="start_date">
                    <small>Leave blank to auto-detect start point</small>
                </div>
                <button type="submit" class="btn btn-warning">Start Progressive Load</button>
            </form>
        </div>

        <!-- Progressive Load Portfolio -->
        <div class="section">
            <h3>📊 Progressive Load Portfolio</h3>
            <p>Load complete historical data for all portfolio symbols</p>
            
            <?php if (!empty($portfolioSymbols)): ?>
                <p><strong>Portfolio Symbols (<?php echo count($portfolioSymbols); ?>):</strong></p>
                <div class="portfolio-symbols">
                    <?php foreach ($portfolioSymbols as $symbol): ?>
                        <span class="symbol-tag"><?php echo htmlspecialchars($symbol); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><em>No portfolio symbols found. Check your portfolio CSV file.</em></p>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return confirm('This will load historical data for all portfolio symbols. This may take a long time. Continue?');">
                <input type="hidden" name="action" value="load_portfolio_progressive">
                <button type="submit" class="btn btn-warning" <?php echo empty($portfolioSymbols) ? 'disabled' : ''; ?>>
                    Load All Portfolio History
                </button>
            </form>
        </div>

        <!-- Process CSV Files -->
        <div class="section">
            <h3>📄 Process CSV Files</h3>
            <p>Process historical stock data from CSV files downloaded from Yahoo Finance or other sources</p>
            
            <div class="info-box">
                <h4>📋 CSV Processing Instructions</h4>
                <p>To use the CSV processing feature:</p>
                <ul>
                    <li>Place CSV files in the main project directory or specify a path below</li>
                    <li>CSV files should contain columns: Date, Open, High, Low, Close, Volume</li>
                    <li>File names should include the stock symbol (e.g., IBM_data.csv, AAPL_2020.csv)</li>
                    <li>You can download CSV files using the Python script with --save-csv flag</li>
                </ul>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="process_csv">
                <div class="form-group">
                    <label for="csv_directory">CSV Directory Path:</label>
                    <input type="text" 
                           id="csv_directory" 
                           name="csv_directory" 
                           value="<?php echo htmlspecialchars(dirname(__DIR__, 2)); ?>"
                           placeholder="Full path to directory containing CSV files">
                    <div class="info-text">Default is the main project directory. Use full absolute path.</div>
                </div>
                <button type="submit" class="btn btn-warning">Process All CSV Files</button>
            </form>
            
            <?php
            // Show available CSV files
            $projectDir = dirname(__DIR__, 2);
            $csvFiles = glob($projectDir . '/*.csv');
            if (!empty($csvFiles)):
            ?>
            <div style="margin-top: 15px;">
                <h4>📁 Available CSV Files in Project Directory:</h4>
                <div class="recent-stocks">
                    <table style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>File Name</th>
                                <th>Size</th>
                                <th>Modified</th>
                                <th>Potential Symbol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($csvFiles, 0, 10) as $csvFile): ?>
                            <?php 
                                $filename = basename($csvFile);
                                $symbol = 'Unknown';
                                if (preg_match('/([A-Z]{1,5})/', $filename, $matches)) {
                                    $symbol = $matches[1];
                                }
                            ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($filename); ?></strong></td>
                                <td><?php echo number_format(filesize($csvFile) / 1024, 1); ?>KB</td>
                                <td><?php echo date('M j, Y H:i', filemtime($csvFile)); ?></td>
                                <td><?php echo htmlspecialchars($symbol); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (count($csvFiles) > 10): ?>
                        <p><em>...and <?php echo count($csvFiles) - 10; ?> more files</em></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($loadResults && isset($loadResults['chunks_processed'])): ?>
        <div class="section">
            <h3>📋 Load Results</h3>
            <div class="progress-info">
                <table>
                    <tr>
                        <th>Symbol</th>
                        <td><?php echo htmlspecialchars($loadResults['symbol']); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><?php echo $loadResults['success'] ? '✅ Success' : '❌ Failed'; ?></td>
                    </tr>
                    <tr>
                        <th>Chunks Processed</th>
                        <td><?php echo $loadResults['chunks_processed']; ?></td>
                    </tr>
                    <tr>
                        <th>Total Records</th>
                        <td><?php echo number_format($loadResults['total_records']); ?></td>
                    </tr>
                    <?php if (isset($loadResults['date_range'])): ?>
                    <tr>
                        <th>Date Range</th>
                        <td><?php echo $loadResults['date_range']['oldest']; ?> to <?php echo $loadResults['date_range']['newest']; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="section">
            <h3>ℹ️ System Information</h3>
            <ul>
                <li><strong>Chunk Size:</strong> 5 years (Yahoo Finance limitation)</li>
                <li><strong>Rate Limiting:</strong> 2 second delay between chunks</li>
                <li><strong>Data Source:</strong> Yahoo Finance via yfinance Python library</li>
                <li><strong>Duplicate Handling:</strong> Automatic upsert (insert or update)</li>
                <li><strong>Progress Tracking:</strong> Skips existing data automatically</li>
            </ul>
        </div>
    </div>

<script>
// Stock Autocomplete Functionality
class StockAutocomplete {
    constructor(inputId, resultsId, hiddenId) {
        this.input = document.getElementById(inputId);
        this.results = document.getElementById(resultsId);
        this.hidden = document.getElementById(hiddenId);
        this.selectedIndex = -1;
        this.currentResults = [];
        this.searchTimeout = null;
        
        this.init();
    }
    
    init() {
        if (!this.input || !this.results) return;
        
        // Input event listeners
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        this.input.addEventListener('focus', (e) => this.handleFocus(e));
        this.input.addEventListener('blur', (e) => this.handleBlur(e));
        
        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.results.contains(e.target)) {
                this.hideResults();
            }
        });
    }
    
    handleInput(e) {
        const query = e.target.value.trim();
        
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        if (query.length < 2) {
            this.hideResults();
            return;
        }
        
        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.search(query);
        }, 300);
    }
    
    handleKeydown(e) {
        if (!this.results.style.display || this.results.style.display === 'none') return;
        
        switch(e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.currentResults.length - 1);
                this.updateSelection();
                break;
                
            case 'ArrowUp':
                e.preventDefault();
                this.selectedIndex = Math.max(this.selectedIndex - 1, -1);
                this.updateSelection();
                break;
                
            case 'Enter':
                e.preventDefault();
                if (this.selectedIndex >= 0) {
                    this.selectItem(this.currentResults[this.selectedIndex]);
                }
                break;
                
            case 'Escape':
                this.hideResults();
                break;
        }
    }
    
    handleFocus(e) {
        if (this.currentResults.length > 0) {
            this.showResults();
        }
    }
    
    handleBlur(e) {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            if (!this.results.contains(document.activeElement)) {
                this.hideResults();
            }
        }, 200);
    }
    
    async search(query) {
        try {
            this.showLoading();
            
            const response = await fetch(`../api/stock_search.php?q=${encodeURIComponent(query)}&limit=15`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            this.currentResults = data.stocks || [];
            this.displayResults();
            
        } catch (error) {
            console.error('Stock search error:', error);
            this.showError('Search failed. Please try again.');
        }
    }
    
    displayResults() {
        if (this.currentResults.length === 0) {
            this.results.innerHTML = '<div class="autocomplete-item">No stocks found</div>';
        } else {
            this.results.innerHTML = this.currentResults.map((stock, index) => `
                <div class="autocomplete-item" data-index="${index}" onclick="stockAutocomplete_${this.input.id}.selectItem(${JSON.stringify(stock).replace(/"/g, '&quot;')})">
                    <div class="autocomplete-symbol">${stock.symbol}</div>
                    <div class="autocomplete-name">${stock.name}</div>
                    <div class="autocomplete-sector">${stock.sector} • ${stock.industry}</div>
                </div>
            `).join('');
        }
        
        this.selectedIndex = -1;
        this.showResults();
    }
    
    showLoading() {
        this.results.innerHTML = '<div class="loading-indicator">Searching...</div>';
        this.showResults();
    }
    
    showError(message) {
        this.results.innerHTML = `<div class="autocomplete-item" style="color: #dc3545;">${message}</div>`;
        this.showResults();
    }
    
    showResults() {
        this.results.style.display = 'block';
    }
    
    hideResults() {
        this.results.style.display = 'none';
        this.selectedIndex = -1;
    }
    
    updateSelection() {
        const items = this.results.querySelectorAll('.autocomplete-item');
        items.forEach((item, index) => {
            item.classList.toggle('selected', index === this.selectedIndex);
        });
    }
    
    selectItem(stock) {
        this.input.value = stock.symbol;
        if (this.hidden) {
            this.hidden.value = stock.symbol;
        }
        this.hideResults();
        
        // Sync with dropdown
        this.syncDropdown(stock.symbol);
        
        // Trigger change event for form validation
        this.input.dispatchEvent(new Event('change'));
    }
    
    syncDropdown(symbol) {
        // Find and sync the corresponding dropdown
        const dropdownId = this.input.id + '_dropdown';
        const dropdown = document.getElementById(dropdownId);
        if (dropdown) {
            dropdown.value = symbol;
        }
    }
}

// Global function for dropdown selection
function selectFromDropdown(inputId, symbol) {
    const input = document.getElementById(inputId);
    if (input && symbol) {
        input.value = symbol;
        
        // Set hidden value if exists
        const hiddenInput = document.getElementById(inputId + '_value');
        if (hiddenInput) {
            hiddenInput.value = symbol;
        }
        
        // Trigger change event for validation
        input.dispatchEvent(new Event('change'));
    }
}

// Function to sync input with dropdown when typing
function syncInputToDropdown(inputId) {
    const input = document.getElementById(inputId);
    const dropdown = document.getElementById(inputId + '_dropdown');
    
    if (input && dropdown) {
        const value = input.value.toUpperCase();
        
        // Find matching option in dropdown
        const options = dropdown.querySelectorAll('option');
        let found = false;
        
        for (let option of options) {
            if (option.value === value) {
                dropdown.value = value;
                found = true;
                break;
            }
        }
        
        // If no exact match found, reset dropdown to default
        if (!found && value !== '') {
            dropdown.value = '';
        }
    }
}

// Initialize autocomplete instances
document.addEventListener('DOMContentLoaded', function() {
    // Create global instances for onclick handlers
    window.stockAutocomplete_check_symbol = new StockAutocomplete('check_symbol', 'check_symbol_results', 'check_symbol_value');
    window.stockAutocomplete_prog_symbol = new StockAutocomplete('prog_symbol', 'prog_symbol_results', 'prog_symbol_value');
    
    // Add input event listeners to sync with dropdowns
    const checkInput = document.getElementById('check_symbol');
    const progInput = document.getElementById('prog_symbol');
    
    if (checkInput) {
        checkInput.addEventListener('input', function() {
            syncInputToDropdown('check_symbol');
        });
    }
    
    if (progInput) {
        progInput.addEventListener('input', function() {
            syncInputToDropdown('prog_symbol');
        });
    }
});

// Form validation to ensure valid stock is selected
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        const autocompleteInputs = this.querySelectorAll('.stock-autocomplete');
        let hasError = false;
        
        autocompleteInputs.forEach(input => {
            const value = input.value.trim();
            if (value && !/^[A-Z]{1,10}$/.test(value)) {
                alert('Please select a valid stock symbol from the dropdown.');
                hasError = true;
                input.focus();
                return false;
            }
        });
        
        if (hasError) {
            e.preventDefault();
            return false;
        }
    });
});
</script>
</body>
</html>