<?php
/**
 * Stock Analysis Dashboard
 * 
 * Main interface for viewing comprehensive stock analysis results
 */

require_once __DIR__ . '/UserAuthDAO.php';
require_once __DIR__ . '/StockDAO.php';
require_once __DIR__ . '/StockPriceService.php';
require_once __DIR__ . '/LLMAnalysisService.php';
require_once __DIR__ . '/models/StockModels.php';

// Check authentication using UserAuthDAO
$userAuth = new UserAuthDAO();
if (!$userAuth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get database connection
$pdo = $userAuth->getPDO();

// Load configuration
$config = require_once __DIR__ . '/config/stock_analysis.php';

$stockDAO = new StockDAO($pdo, $config['database'] ?? []);
$priceService = new StockPriceService($stockDAO, $config['price_service'] ?? []);
$analysisService = new LLMAnalysisService($stockDAO, $config['llm'] ?? []);

// Get symbol from URL parameter
$symbol = strtoupper($_GET['symbol'] ?? '');

// Validate symbol
if (empty($symbol)) {
    $error = 'Stock symbol is required';
} else {
    // Get comprehensive stock data
    $stockSummary = $stockDAO->getStockSummary($symbol);
    
    if (!$stockSummary) {
        // Try to initialize stock if it doesn't exist
        if ($stockDAO->initializeStock($symbol)) {
            $stockSummary = $stockDAO->getStockSummary($symbol);
        }
    }
    
    // Get real-time quote
    $realTimeQuote = $priceService->getRealTimeQuote($symbol);
    
    // Get recent price history for chart
    $recentPrices = $stockDAO->getPriceData($symbol, date('Y-m-d', strtotime('-30 days')), null, 30);
    
    // Get latest AI analysis
    $latestAnalysis = $stockDAO->getLatestAnalysis($symbol);
    
    // Get fundamentals
    $fundamentals = $stockDAO->getFundamentals($symbol);
    
    // Get recent news
    $recentNews = $stockDAO->getNews($symbol, 10);
}

// Use NavigationService for consistent navigation
require_once 'NavigationService.php';
$navigationService = new NavigationService();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $symbol ? "Stock Analysis - {$symbol}" : "Stock Analysis" ?> - AI Stock Analysis System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
    </style>
</head>
<body>

<?php
echo $navigationService->renderNavigationHeader('Stock Analysis Dashboard', 'stock_analysis');
?>

<div class="container-fluid">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5>Enter Stock Symbol</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="symbol" class="form-control form-control-lg" 
                               placeholder="Enter stock symbol (e.g., AAPL, MSFT, TSLA)" 
                               style="text-transform: uppercase;" required>
                        <button class="btn btn-primary btn-lg" type="submit">
                            <i class="fas fa-search"></i> Analyze Stock
                        </button>
                    </div>
                    <small class="text-muted">Enter any valid stock symbol to view comprehensive analysis</small>
                </form>
            </div>
        </div>
    <?php else: ?>
        
        <!-- Stock Header with Current Price -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 class="mb-1"><?= htmlspecialchars($symbol) ?>
                                    <?php if ($realTimeQuote && isset($realTimeQuote['price'])): ?>
                                        <span class="ml-3">$<?= number_format($realTimeQuote['price'], 2) ?></span>
                                        <?php if (isset($realTimeQuote['change'])): ?>
                                            <span class="badge badge-<?= $realTimeQuote['change'] >= 0 ? 'success' : 'danger' ?> ml-2">
                                                <?= $realTimeQuote['change'] >= 0 ? '+' : '' ?><?= number_format($realTimeQuote['change'], 2) ?>
                                                (<?= $realTimeQuote['change'] >= 0 ? '+' : '' ?><?= number_format($realTimeQuote['change_percent'] ?? 0, 2) ?>%)
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </h2>
                                <?php if ($realTimeQuote && isset($realTimeQuote['timestamp'])): ?>
                                    <small class="text-white-50">
                                        Last updated: <?= date('M j, Y g:i A', strtotime($realTimeQuote['timestamp'])) ?>
                                        <span class="badge badge-light text-dark ml-2"><?= $realTimeQuote['market_state'] ?? 'UNKNOWN' ?></span>
                                    </small>
                                <?php endif; ?>
                            </div>
                            <div class="text-right">
                                <button class="btn btn-light btn-sm" onclick="updateStockData()" id="updateBtn">
                                    <i class="fas fa-sync" id="refresh-icon"></i> Update Data
                                </button>
                                <button class="btn btn-success btn-sm" onclick="generateAIAnalysis()" id="aiBtn">
                                    <i class="fas fa-brain"></i> AI Analysis
                                </button>
                                <a href="stock_search.php" class="btn btn-outline-light btn-sm">
                                    <i class="fas fa-search"></i> Search Other
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Messages -->
        <div id="statusMessages"></div>
        
        <!-- Main Analysis Dashboard -->
        <div class="row">
            
            <!-- Left Column: Price & Chart -->
            <div class="col-md-8">
                
                <!-- Price Summary Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-line"></i> Price Analysis</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($realTimeQuote): ?>
                            <div class="row">
                                <div class="col-md-3 text-center">
                                    <h6 class="text-muted">Current Price</h6>
                                    <h4 class="text-primary">$<?= number_format($realTimeQuote['price'], 2) ?></h4>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6 class="text-muted">Volume</h6>
                                    <h5><?= isset($realTimeQuote['volume']) ? number_format($realTimeQuote['volume']) : 'N/A' ?></h5>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6 class="text-muted">Market Cap</h6>
                                    <h5><?= isset($realTimeQuote['market_cap']) ? '$' . number_format($realTimeQuote['market_cap'] / 1e9, 1) . 'B' : 'N/A' ?></h5>
                                </div>
                                <div class="col-md-3 text-center">
                                    <h6 class="text-muted">P/E Ratio</h6>
                                    <h5><?= isset($realTimeQuote['pe_ratio']) ? number_format($realTimeQuote['pe_ratio'], 1) : 'N/A' ?></h5>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <!-- Mini Price Chart -->
                            <div class="mt-3">
                                <h6>30-Day Price Trend</h6>
                                <canvas id="priceChart" style="height: 200px;"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                <h5>Price Data Not Available</h5>
                                <p>Unable to retrieve current price information</p>
                                <button class="btn btn-primary" onclick="updateStockData()">
                                    <i class="fas fa-sync"></i> Try Refresh
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- News Analysis -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-newspaper"></i> Recent News & Sentiment</h5>
                            <button class="btn btn-outline-success btn-sm" onclick="analyzeSentiment()">
                                <i class="fas fa-brain"></i> Analyze Sentiment
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentNews)): ?>
                            <?php foreach (array_slice($recentNews, 0, 5) as $news): ?>
                                <?php $newsItem = new NewsItem($news); ?>
                                <div class="media mb-3">
                                    <div class="mr-3 text-center">
                                        <div class="sentiment-indicator-large">
                                            <?= $newsItem->getSentimentIndicator() ?>
                                        </div>
                                        <?php if ($news['sentiment_score'] !== null): ?>
                                            <small class="text-muted d-block">
                                                <?= number_format($news['sentiment_score'] * 100, 0) ?>%
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="media-body">
                                        <h6 class="mt-0">
                                            <a href="<?= htmlspecialchars($news['source_url'] ?? '#') ?>" target="_blank" class="text-decoration-none">
                                                <?= htmlspecialchars($news['headline']) ?>
                                            </a>
                                        </h6>
                                        <?php if ($news['summary']): ?>
                                            <p class="text-muted small"><?= htmlspecialchars(substr($news['summary'], 0, 150)) ?>...</p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="fas fa-source"></i> <?= htmlspecialchars($news['source']) ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-clock"></i> <?= $newsItem->getTimeAgo() ?>
                                            <?php if ($news['category'] !== 'GENERAL'): ?>
                                                <span class="mx-2">•</span>
                                                <span class="badge badge-secondary"><?= $news['category'] ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-info-circle fa-2x mb-2"></i>
                                <h6>No Recent News</h6>
                                <p>No news articles found for this stock</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>
            
            <!-- Right Column: AI Analysis & Fundamentals -->
            <div class="col-md-4">
                
                <!-- AI Analysis Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-robot"></i> AI Analysis</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($latestAnalysis): ?>
                            <?php $analysisObj = new AnalysisResults($latestAnalysis); ?>
                            
                            <!-- Overall Recommendation -->
                            <div class="text-center mb-3">
                                <div class="recommendation-badge" style="background-color: <?= $analysisObj->getRecommendationColor() ?>">
                                    <?= str_replace('_', ' ', $latestAnalysis['recommendation'] ?? 'N/A') ?>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Confidence: <?= $analysisObj->getConfidenceIndicator() ?>
                                </small>
                            </div>
                            
                            <!-- Analysis Scores -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Overall Score</span>
                                    <span class="font-weight-bold"><?= $latestAnalysis['overall_score'] ? number_format($latestAnalysis['overall_score'], 0) . '/100' : 'N/A' ?></span>
                                </div>
                                <?php if ($latestAnalysis['overall_score']): ?>
                                    <div class="progress mb-2" style="height: 8px;">
                                        <div class="progress-bar" style="width: <?= $latestAnalysis['overall_score'] ?>%"></div>
                                    </div>
                                <?php endif; ?>
                                
                                <small class="text-muted">
                                    Technical: <?= $latestAnalysis['technical_score'] ?? 'N/A' ?> | 
                                    Fundamental: <?= $latestAnalysis['fundamental_score'] ?? 'N/A' ?> | 
                                    Sentiment: <?= $latestAnalysis['sentiment_score'] ?? 'N/A' ?>
                                </small>
                            </div>
                            
                            <!-- Investment Metrics -->
                            <?php if ($latestAnalysis['target_price']): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Target Price</span>
                                    <span class="text-success font-weight-bold">$<?= number_format($latestAnalysis['target_price'], 2) ?></span>
                                </div>
                                
                                <?php if ($realTimeQuote && $realTimeQuote['price']): ?>
                                    <?php
                                    $currentPrice = $realTimeQuote['price'];
                                    $upside = (($latestAnalysis['target_price'] - $currentPrice) / $currentPrice) * 100;
                                    ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span>Upside Potential</span>
                                        <span class="<?= $upside >= 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $upside >= 0 ? '+' : '' ?><?= number_format($upside, 1) ?>%
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($latestAnalysis['risk_level']): ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Risk Level</span>
                                    <span class="badge" style="background-color: <?= $analysisObj->getRiskLevelColor() ?>">
                                        <?= $latestAnalysis['risk_level'] ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- AI Analysis Text -->
                            <?php if ($latestAnalysis['llm_analysis']): ?>
                                <div class="alert alert-light">
                                    <strong>AI Insight:</strong><br>
                                    <?= nl2br(htmlspecialchars(substr($latestAnalysis['llm_analysis'], 0, 300))) ?>
                                    <?php if (strlen($latestAnalysis['llm_analysis']) > 300): ?>
                                        <span id="moreText" style="display: none;"><?= nl2br(htmlspecialchars(substr($latestAnalysis['llm_analysis'], 300))) ?></span>
                                        <a href="#" onclick="toggleMoreText(); return false;" class="text-primary">... read more</a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    Last updated: <?= date('M j, g:i A', strtotime($latestAnalysis['updated_at'] ?? $latestAnalysis['created_at'])) ?>
                                </small>
                            </div>
                            
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-robot fa-3x mb-3"></i>
                                <h6>No AI Analysis Available</h6>
                                <p>Generate AI-powered analysis for comprehensive insights</p>
                                <button class="btn btn-primary" onclick="generateAIAnalysis()">
                                    <i class="fas fa-brain"></i> Generate Analysis
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Quick Fundamentals -->
                <?php if ($fundamentals): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-calculator"></i> Key Fundamentals</h5>
                        </div>
                        <div class="card-body">
                            <?php $fundData = new FundamentalData($fundamentals); ?>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h6 class="text-muted">P/E Ratio</h6>
                                    <h5><?= $fundamentals['pe_ratio'] ? number_format($fundamentals['pe_ratio'], 1) : 'N/A' ?></h5>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-muted">P/B Ratio</h6>
                                    <h5><?= $fundamentals['price_to_book'] ? number_format($fundamentals['price_to_book'], 1) : 'N/A' ?></h5>
                                </div>
                            </div>
                            
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <h6 class="text-muted">ROE</h6>
                                    <h5><?= $fundamentals['return_on_equity'] ? number_format($fundamentals['return_on_equity'], 1) . '%' : 'N/A' ?></h5>
                                </div>
                                <div class="col-6">
                                    <h6 class="text-muted">Revenue Growth</h6>
                                    <h5 class="<?= ($fundamentals['revenue_growth_yoy'] ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $fundamentals['revenue_growth_yoy'] ? number_format($fundamentals['revenue_growth_yoy'], 1) . '%' : 'N/A' ?>
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="alert alert-info text-center">
                                <strong>Health Score:</strong> <?= number_format($fundData->getFinancialHealthScore(), 0) ?>/100<br>
                                <small><?= $fundData->getValuationCategory() ?></small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Analysis Tools</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-block" onclick="generateAIAnalysis()">
                                <i class="fas fa-brain"></i> Full AI Analysis
                            </button>
                            <button class="btn btn-outline-primary btn-block" onclick="analyzeSentiment()">
                                <i class="fas fa-newspaper"></i> Analyze News Sentiment
                            </button>
                            <button class="btn btn-outline-secondary btn-block" onclick="updateStockData()">
                                <i class="fas fa-sync"></i> Refresh All Data
                            </button>
                            <a href="stock_search.php" class="btn btn-outline-info btn-block">
                                <i class="fas fa-search"></i> Search Other Stocks
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Detailed Analysis Modal -->
        <div class="modal fade" id="detailedAnalysisModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detailed AI Analysis - <?= htmlspecialchars($symbol) ?></h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" id="detailedAnalysisContent">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin fa-2x"></i><br>
                            Loading detailed analysis...
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endif; ?>
</div>

<!-- CSS Styles -->
<style>
.recommendation-badge {
    color: white;
    padding: 8px 20px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sentiment-indicator-large {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
}

.progress {
    background-color: #e9ecef;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0, 0, 0, 0.125);
}

.alert {
    border: 0;
    border-radius: 0.375rem;
}

.btn {
    border-radius: 0.375rem;
}
</style>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const symbol = '<?= htmlspecialchars($symbol) ?>';
let priceChart;

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    if (symbol) {
        initializePriceChart();
    }
});

// Initialize price chart
function initializePriceChart() {
    const ctx = document.getElementById('priceChart');
    if (!ctx) return;
    
    const recentPrices = <?= json_encode(array_reverse($recentPrices ?? [])) ?>;
    
    if (recentPrices.length === 0) {
        ctx.parentElement.innerHTML = '<p class="text-muted text-center">No price history available</p>';
        return;
    }
    
    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: recentPrices.map(p => new Date(p.date).toLocaleDateString()),
            datasets: [{
                label: 'Close Price',
                data: recentPrices.map(p => parseFloat(p.close)),
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toFixed(2);
                        }
                    }
                }
            },
            elements: {
                point: {
                    radius: 3
                }
            }
        }
    });
}

// Update stock data
function updateStockData() {
    showStatus('Updating stock data...', 'info');
    setButtonLoading('updateBtn', true);
    
    fetch('/api/stock_analysis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=update_price_data&symbol=${encodeURIComponent(symbol)}`
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading('updateBtn', false);
        if (data.success) {
            showStatus('Data updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showStatus('Failed to update data: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        setButtonLoading('updateBtn', false);
        showStatus('Error updating data: ' + error.message, 'danger');
    });
}

// Generate AI analysis
function generateAIAnalysis() {
    showStatus('Generating comprehensive AI analysis...', 'info');
    setButtonLoading('aiBtn', true);
    
    fetch('/api/stock_analysis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=full_analysis_update&symbol=${encodeURIComponent(symbol)}`
    })
    .then(response => response.json())
    .then(data => {
        setButtonLoading('aiBtn', false);
        if (data.success) {
            showStatus('AI analysis completed successfully!', 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showStatus('Analysis failed: ' + (data.error || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        setButtonLoading('aiBtn', false);
        showStatus('Error generating analysis: ' + error.message, 'danger');
    });
}

// Analyze sentiment
function analyzeSentiment() {
    showStatus('Analyzing news sentiment...', 'info');
    
    fetch('/api/stock_analysis.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=analyze_sentiment&symbol=${encodeURIComponent(symbol)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showStatus('Sentiment analysis completed!', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showStatus('Sentiment analysis failed', 'warning');
        }
    })
    .catch(error => {
        showStatus('Error analyzing sentiment', 'danger');
    });
}

// Show status message
function showStatus(message, type = 'info') {
    const statusContainer = document.getElementById('statusMessages');
    const alertClass = type === 'info' ? 'alert-info' : 
                      type === 'success' ? 'alert-success' : 
                      type === 'warning' ? 'alert-warning' : 'alert-danger';
    
    const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : type === 'danger' ? 'times-circle' : 'info-circle'}"></i>
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    statusContainer.innerHTML = alert;
    
    // Auto-dismiss after 5 seconds for non-error messages
    if (type !== 'danger') {
        setTimeout(() => {
            const alertElement = statusContainer.querySelector('.alert');
            if (alertElement) {
                alertElement.remove();
            }
        }, 5000);
    }
}

// Set button loading state
function setButtonLoading(buttonId, loading) {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    
    if (loading) {
        btn.disabled = true;
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.add('fa-spin');
        }
    } else {
        btn.disabled = false;
        const icon = btn.querySelector('i');
        if (icon) {
            icon.classList.remove('fa-spin');
        }
    }
}

// Toggle more text
function toggleMoreText() {
    const moreText = document.getElementById('moreText');
    const link = event.target;
    
    if (moreText.style.display === 'none') {
        moreText.style.display = 'inline';
        link.textContent = ' show less';
    } else {
        moreText.style.display = 'none';
        link.textContent = '... read more';
    }
}

// Auto-refresh price every 5 minutes
setInterval(function() {
    if (symbol) {
        updateStockData();
    }
}, 300000); // 5 minutes
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php include 'includes/footer.php'; ?>