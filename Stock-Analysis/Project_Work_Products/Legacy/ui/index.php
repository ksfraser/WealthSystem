<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

use Ksfraser\StockInfo\StockInfoManager;
use Ksfraser\Analysis\UnifiedAnalyzer;
use Ksfraser\StockInfo\TechnicalAnalysisJobs;

$currentPage = 'dashboard';
$pageTitle = 'Stock Analysis Dashboard';
$pageHeader = 'Dashboard Overview';
$pageDescription = 'Real-time stock analysis and market insights';

try {
    $stockManager = new StockInfoManager([
        'host' => 'localhost',
        'dbname' => 'stock_market_2',
        'username' => 'your_username',
        'password' => 'your_password'
    ]);
    $unifiedAnalyzer = new UnifiedAnalyzer();
    $jobsManager = new TechnicalAnalysisJobs();
    
    // Get summary statistics
    $totalStocks = $stockManager->getStockCount();
    $recentJobs = $jobsManager->getRecentJobs(5);
    $topStocks = $unifiedAnalyzer->getTopUnifiedStocks(10);
    
} catch (Exception $e) {
    $error = "Error loading dashboard data: " . $e->getMessage();
}

include 'components/header.php';
?>

<!-- Dashboard Metrics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card metric-card">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x text-primary mb-2"></i>
                <p class="metric-value"><?= $totalStocks ?? 0 ?></p>
                <p class="metric-label">Total Stocks</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card">
            <div class="card-body text-center">
                <i class="fas fa-brain fa-2x text-success mb-2"></i>
                <p class="metric-value"><?= count($topStocks ?? []) ?></p>
                <p class="metric-label">Analyzed Stocks</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card">
            <div class="card-body text-center">
                <i class="fas fa-tasks fa-2x text-warning mb-2"></i>
                <p class="metric-value"><?= count($recentJobs ?? []) ?></p>
                <p class="metric-label">Recent Jobs</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card metric-card">
            <div class="card-body text-center">
                <i class="fas fa-bell fa-2x text-info mb-2"></i>
                <p class="metric-value">0</p>
                <p class="metric-label">Active Alerts</p>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-rocket me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-primary w-100" onclick="startTechnicalAnalysis()">
                            <i class="fas fa-chart-bar me-2"></i>
                            Run Technical Analysis
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-success w-100" onclick="calculateIndicators()">
                            <i class="fas fa-calculator me-2"></i>
                            Calculate Indicators
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-info w-100" onclick="detectPatterns()">
                            <i class="fas fa-search me-2"></i>
                            Detect Patterns
                        </button>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button class="btn btn-warning w-100" onclick="updatePrices()">
                            <i class="fas fa-sync me-2"></i>
                            Update Prices
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Performing Stocks -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-trophy me-2"></i>Top Performing Stocks
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($topStocks)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Symbol</th>
                                <th>Name</th>
                                <th>Unified Score</th>
                                <th>Grade</th>
                                <th>Confidence</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topStocks as $stock): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($stock['stock_info']['symbol']) ?></strong></td>
                                <td><?= htmlspecialchars($stock['stock_info']['name'] ?? 'N/A') ?></td>
                                <td>
                                    <span class="badge bg-primary"><?= $stock['unified_score'] ?>/100</span>
                                </td>
                                <td>
                                    <span class="badge bg-success"><?= $stock['grade'] ?></span>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $stock['confidence'] ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick="viewStock(<?= $stock['stock_info']['idstockinfo'] ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-success" onclick="analyzeStock(<?= $stock['stock_info']['idstockinfo'] ?>)">
                                        <i class="fas fa-chart-line"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No analyzed stocks available. Run analysis to see results.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Jobs -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>Recent Analysis Jobs
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentJobs)): ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($recentJobs as $job): ?>
                    <div class="list-group-item px-0">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($job['analysis_type']) ?></h6>
                            <small class="text-muted"><?= date('M j, g:i A', strtotime($job['created_at'])) ?></small>
                        </div>
                        <p class="mb-1 small">
                            <?= $job['stock_symbol'] ? htmlspecialchars($job['stock_symbol']) : 'All Stocks' ?>
                        </p>
                        <div class="d-flex w-100 justify-content-between align-items-center">
                            <span class="badge bg-<?= $job['status'] === 'COMPLETED' ? 'success' : ($job['status'] === 'FAILED' ? 'danger' : 'primary') ?>">
                                <?= $job['status'] ?>
                            </span>
                            <?php if ($job['status'] === 'RUNNING'): ?>
                            <div class="progress flex-grow-1 mx-2" style="height: 4px;">
                                <div class="progress-bar" id="progress-<?= $job['id'] ?>" style="width: <?= $job['progress'] ?>%"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="fas fa-tasks fa-2x text-muted mb-2"></i>
                    <p class="text-muted small">No recent jobs</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Market Overview Chart Placeholder -->
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-area me-2"></i>Market Overview
                </h5>
            </div>
            <div class="card-body">
                <div id="market-chart" class="chart-container">
                    <canvas id="marketOverviewChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Spinner -->
<div class="loading-spinner">
    <div class="d-flex justify-content-center">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

<?php
$additionalScripts = '
<script>
    // Initialize market overview chart
    $(document).ready(function() {
        const ctx = document.getElementById("marketOverviewChart").getContext("2d");
        const marketChart = new Chart(ctx, {
            type: "line",
            data: {
                labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun"],
                datasets: [{
                    label: "Market Index",
                    data: [100, 105, 103, 110, 108, 115],
                    borderColor: "rgb(75, 192, 192)",
                    backgroundColor: "rgba(75, 192, 192, 0.1)",
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
    
    // Quick action functions
    function startTechnicalAnalysis() {
        $.post("/Legacy/ui/api/start_analysis.php", {type: "all"}, function(data) {
            if (data.status === "success") {
                showAlert("Technical analysis started successfully", "success");
                updateProgress(data.jobId, function() {
                    location.reload();
                });
            }
        });
    }
    
    function calculateIndicators() {
        $.post("/Legacy/ui/api/start_analysis.php", {type: "indicators"}, function(data) {
            if (data.status === "success") {
                showAlert("Indicator calculation started", "success");
                updateProgress(data.jobId);
            }
        });
    }
    
    function detectPatterns() {
        $.post("/Legacy/ui/api/start_analysis.php", {type: "patterns"}, function(data) {
            if (data.status === "success") {
                showAlert("Pattern detection started", "success");
                updateProgress(data.jobId);
            }
        });
    }
    
    function updatePrices() {
        $.post("/Legacy/ui/api/update_prices.php", function(data) {
            if (data.status === "success") {
                showAlert("Price update started", "success");
            }
        });
    }
    
    function viewStock(stockId) {
        window.location.href = "/Legacy/ui/pages/stock_detail.php?id=" + stockId;
    }
    
    function analyzeStock(stockId) {
        $.post("/Legacy/ui/api/start_analysis.php", {type: "all", stockId: stockId}, function(data) {
            if (data.status === "success") {
                showAlert("Stock analysis started", "success");
                updateProgress(data.jobId);
            }
        });
    }
</script>
';

include 'components/footer.php';
?>
