<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../../../DatabaseConfig.php';

use Ksfraser\StockInfo\StockInfoManager;
use Ksfraser\StockInfo\StockInfo;
use Ksfraser\StockInfo\TechnicalIndicators;
use Ksfraser\StockInfo\CandlestickPatterns;
use Ksfraser\StockInfo\TechnicalAnalysisJobs;
use Ksfraser\StockInfo\DatabaseFactory;

$currentPage = 'technical_analysis';
$pageTitle = 'Technical Analysis';
$pageHeader = 'Technical Analysis & TA-Lib Integration';
$pageDescription = 'View and calculate technical indicators, candlestick patterns, and analysis jobs';

// Initialize database and models using centralized config
try {
    $legacyConfig = DatabaseConfig::getLegacyConfig();
    
    $database = DatabaseFactory::getInstance($legacyConfig);
    $pdo = $database->getConnection();
    
    $stockModel = new StockInfo($pdo);
    $indicatorsModel = new TechnicalIndicators($pdo);
    $patternsModel = new CandlestickPatterns($pdo);
    $jobsModel = new TechnicalAnalysisJobs($pdo);
} catch (Exception $e) {
    $error = "Database connection failed: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'calculate_indicators':
            try {
                $stockId = (int)$_POST['stock_id'];
                $indicators = $_POST['indicators'] ?? [];
                
                if (empty($indicators)) {
                    throw new Exception('Please select at least one indicator');
                }
                
                // Create analysis job
                $jobData = [
                    'idstockinfo' => $stockId,
                    'analysis_type' => 'INDICATORS',
                    'status' => 'PENDING',
                    'start_date' => date('Y-m-d H:i:s'),
                    'progress' => 0
                ];
                
                $jobId = $jobsModel->createJob($jobData);
                
                // TODO: Queue job for processing
                $_SESSION['success'] = "Technical analysis job created (ID: {$jobId})";
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error creating analysis job: " . $e->getMessage();
            }
            break;
            
        case 'detect_patterns':
            try {
                $stockId = (int)$_POST['stock_id'];
                $patterns = $_POST['patterns'] ?? [];
                
                if (empty($patterns)) {
                    throw new Exception('Please select at least one pattern');
                }
                
                // Create pattern detection job
                $jobData = [
                    'idstockinfo' => $stockId,
                    'analysis_type' => 'PATTERNS',
                    'status' => 'PENDING',
                    'start_date' => date('Y-m-d H:i:s'),
                    'progress' => 0
                ];
                
                $jobId = $jobsModel->createJob($jobData);
                $_SESSION['success'] = "Pattern detection job created (ID: {$jobId})";
                
            } catch (Exception $e) {
                $_SESSION['error'] = "Error creating pattern job: " . $e->getMessage();
            }
            break;
    }
    
    header('Location: technical_analysis.php');
    exit;
}

// Get data for display
$selectedStock = null;
if (isset($_GET['stock_id'])) {
    $selectedStock = $stockModel->find((int)$_GET['stock_id']);
}

try {
    // Get recent indicators
    $recentIndicators = $indicatorsModel->getRecentIndicators(20);
    
    // Get recent patterns
    $recentPatterns = $patternsModel->getRecentPatterns(20);
    
    // Get recent jobs
    $recentJobs = $jobsModel->getRecentJobs(10);
    
    // Get all stocks for selection
    $stocks = $stockModel->getActiveStocks();
    
} catch (Exception $e) {
    $recentIndicators = [];
    $recentPatterns = [];
    $recentJobs = [];
    $stocks = [];
}

include '../components/header.php';
?>

<!-- Stock Selection -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-search me-2"></i>Select Stock for Analysis
                </h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-8">
                        <select class="form-select" name="stock_id" id="stockSelect">
                            <option value="">Select a stock...</option>
                            <?php foreach ($stocks as $stock): ?>
                            <option value="<?= $stock->idstockinfo ?>" <?= $selectedStock && $selectedStock['idstockinfo'] == $stock->idstockinfo ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stock->stocksymbol) ?> - <?= htmlspecialchars($stock->corporatename) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-chart-line me-2"></i>Analyze Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedStock): ?>
<!-- Stock Information -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($selectedStock['stocksymbol']) ?> - Stock Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Company:</strong><br>
                        <?= htmlspecialchars($selectedStock['corporatename']) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Current Price:</strong><br>
                        $<?= number_format($selectedStock['currentprice'] ?? 0, 2) ?>
                    </div>
                    <div class="col-md-3">
                        <strong>Daily Change:</strong><br>
                        <span class="badge bg-<?= ($selectedStock['dailychange'] ?? 0) >= 0 ? 'success' : 'danger' ?>">
                            <?= number_format($selectedStock['dailychange'] ?? 0, 2) ?>
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Market Cap:</strong><br>
                        $<?= number_format($selectedStock['marketcap'] ?? 0) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Technical Analysis Actions -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Calculate Indicators
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="calculate_indicators">
                    <input type="hidden" name="stock_id" value="<?= $selectedStock['idstockinfo'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Indicators:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="RSI" id="rsi">
                            <label class="form-check-label" for="rsi">RSI (Relative Strength Index)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="MACD" id="macd">
                            <label class="form-check-label" for="macd">MACD (Moving Average Convergence Divergence)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="SMA" id="sma">
                            <label class="form-check-label" for="sma">SMA (Simple Moving Average)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="EMA" id="ema">
                            <label class="form-check-label" for="ema">EMA (Exponential Moving Average)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="BBANDS" id="bbands">
                            <label class="form-check-label" for="bbands">Bollinger Bands</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="indicators[]" value="STOCH" id="stoch">
                            <label class="form-check-label" for="stoch">Stochastic Oscillator</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i>Calculate Indicators
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-eye me-2"></i>Detect Patterns
                </h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="detect_patterns">
                    <input type="hidden" name="stock_id" value="<?= $selectedStock['idstockinfo'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Patterns:</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="DOJI" id="doji">
                            <label class="form-check-label" for="doji">Doji</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="HAMMER" id="hammer">
                            <label class="form-check-label" for="hammer">Hammer</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="ENGULFING" id="engulfing">
                            <label class="form-check-label" for="engulfing">Engulfing Pattern</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="SHOOTING_STAR" id="shooting">
                            <label class="form-check-label" for="shooting">Shooting Star</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="MORNING_STAR" id="morning">
                            <label class="form-check-label" for="morning">Morning Star</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="patterns[]" value="EVENING_STAR" id="evening">
                            <label class="form-check-label" for="evening">Evening Star</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-search me-2"></i>Detect Patterns
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Jobs -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tasks me-2"></i>Recent Analysis Jobs
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentJobs)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Stock</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th>Started</th>
                                <th>Completed</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentJobs as $job): ?>
                            <tr>
                                <td><?= $job['id'] ?></td>
                                <td>
                                    <?php if (isset($job['stock_symbol'])): ?>
                                        <strong><?= htmlspecialchars($job['stock_symbol']) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">All</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($job['analysis_type']) ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($job['status']) {
                                        'PENDING' => 'warning',
                                        'RUNNING' => 'primary',
                                        'COMPLETED' => 'success',
                                        'FAILED' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($job['status']) ?></span>
                                </td>
                                <td>
                                    <div class="progress" style="width: 100px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?= $job['progress'] ?? 0 ?>%"
                                             aria-valuenow="<?= $job['progress'] ?? 0 ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            <?= $job['progress'] ?? 0 ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small><?= date('M j, H:i', strtotime($job['start_date'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($job['end_date']): ?>
                                        <small><?= date('M j, H:i', strtotime($job['end_date'])) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-info" onclick="viewJobDetails(<?= $job['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if ($job['status'] === 'COMPLETED'): ?>
                                        <button type="button" class="btn btn-sm btn-outline-success" onclick="viewResults(<?= $job['id'] ?>)">
                                            <i class="fas fa-chart-line"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No analysis jobs found. Select a stock and start an analysis.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Indicators -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>Recent Indicators
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentIndicators)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Stock</th>
                                <th>Indicator</th>
                                <th>Value</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentIndicators as $indicator): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($indicator['symbol']) ?></small></td>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($indicator['indicator_name']) ?></span></td>
                                <td><?= number_format($indicator['value'], 2) ?></td>
                                <td><small><?= date('M j', strtotime($indicator['calculation_date'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No indicators calculated yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-eye me-2"></i>Recent Patterns
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentPatterns)): ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Stock</th>
                                <th>Pattern</th>
                                <th>Strength</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPatterns as $pattern): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($pattern['symbol']) ?></small></td>
                                <td><span class="badge bg-success"><?= htmlspecialchars($pattern['pattern_name']) ?></span></td>
                                <td>
                                    <?php
                                    $strengthClass = match(true) {
                                        $pattern['strength'] >= 80 => 'success',
                                        $pattern['strength'] >= 60 => 'warning',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $strengthClass ?>"><?= $pattern['strength'] ?>%</span>
                                </td>
                                <td><small><?= date('M j', strtotime($pattern['detection_date'])) ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No patterns detected yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$additionalScripts = '
<script>
    function viewJobDetails(jobId) {
        // TODO: Implement job details modal
        alert("Job details for ID: " + jobId);
    }
    
    function viewResults(jobId) {
        // TODO: Implement results viewer
        window.location.href = "/Legacy/ui/pages/analysis_results.php?job_id=" + jobId;
    }
    
    // Auto-refresh job statuses every 30 seconds
    setInterval(function() {
        $(".progress-bar").each(function() {
            // TODO: Update progress bars via AJAX
        });
    }, 30000);
</script>
';

include '../components/footer.php';
?>
