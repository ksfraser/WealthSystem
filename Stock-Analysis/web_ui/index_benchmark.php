<?php
/**
 * Index Benchmark Page
 * 
 * Compares portfolio/stock performance against major market indexes.
 * Displays alpha, beta, correlation, Sharpe ratio, and other risk-adjusted metrics.
 * 
 * Features:
 * - Performance line chart (portfolio vs index)
 * - Metrics comparison table
 * - Risk-adjusted return calculations
 * - Multiple index support (S&P 500, NASDAQ, Dow Jones, Russell 2000)
 * - Multiple time periods (1M, 3M, 6M, 1Y, 3Y, 5Y)
 * 
 * @version 1.0.0
 */

require_once 'web_ui/Navigation/RequireLogin.php';
require_once 'web_ui/Navigation/NavigationProvider.php';

$currentPage = 'index_benchmark';
$navManager = getNavManager();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Index Benchmark - Stock Analysis</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        .chart-container {
            position: relative;
            height: 450px;
            margin-bottom: 30px;
        }
        .metric-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .metric-positive { color: #28a745; font-weight: bold; }
        .metric-negative { color: #dc3545; font-weight: bold; }
        .metric-neutral { color: #6c757d; }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 10px 0;
        }
        .comparison-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .controls-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <?php include 'web_ui/Navigation/navigation.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1>📉 Index Benchmark Comparison</h1>
                <p class="lead">Compare your portfolio or stock performance against major market indexes</p>
                <hr>
            </div>
        </div>
        
        <!-- Controls Section -->
        <div class="controls-section">
            <div class="row">
                <div class="col-md-4">
                    <label for="symbolInput" class="form-label">Portfolio/Stock Symbol</label>
                    <input type="text" class="form-control" id="symbolInput" placeholder="e.g., AAPL, MSFT" value="PORTFOLIO">
                </div>
                <div class="col-md-4">
                    <label for="indexSelect" class="form-label">Benchmark Index</label>
                    <select class="form-select" id="indexSelect">
                        <option value="SPX" selected>S&P 500 (SPX)</option>
                        <option value="IXIC">NASDAQ Composite (IXIC)</option>
                        <option value="DJI">Dow Jones (DJI)</option>
                        <option value="RUT">Russell 2000 (RUT)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="periodSelect" class="form-label">Time Period</label>
                    <select class="form-select" id="periodSelect">
                        <option value="1M">1 Month</option>
                        <option value="3M">3 Months</option>
                        <option value="6M">6 Months</option>
                        <option value="1Y" selected>1 Year</option>
                        <option value="3Y">3 Years</option>
                        <option value="5Y">5 Years</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary" id="analyzeBtn">
                        <span id="analyzeBtnText">📊 Analyze Performance</span>
                        <span id="analyzeBtnSpinner" class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                    <button class="btn btn-outline-secondary" id="resetBtn">Reset</button>
                </div>
            </div>
        </div>
        
        <!-- Key Metrics Row -->
        <div class="row mb-4" id="metricsRow" style="display: none;">
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted">Total Return</h6>
                        <div id="totalReturn" class="metric-value">--</div>
                        <small id="returnComparison" class="text-muted">vs Index</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted">Beta (β)</h6>
                        <div id="betaValue" class="metric-value">--</div>
                        <small class="text-muted">Market sensitivity</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted">Alpha (α)</h6>
                        <div id="alphaValue" class="metric-value">--</div>
                        <small class="text-muted">Excess return</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h6 class="card-title text-muted">Sharpe Ratio</h6>
                        <div id="sharpeValue" class="metric-value">--</div>
                        <small class="text-muted">Risk-adjusted return</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Performance Chart -->
        <div class="row mb-4" id="chartRow" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Cumulative Performance</h5>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Metrics Comparison Table -->
        <div class="row mb-4" id="tableRow" style="display: none;">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Detailed Metrics Comparison</h5>
                    </div>
                    <div class="card-body">
                        <div id="metricsTable"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Risk Metrics -->
        <div class="row mb-4" id="riskRow" style="display: none;">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Risk Metrics</h5>
                    </div>
                    <div class="card-body">
                        <div id="riskMetrics"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Performance Summary</h5>
                    </div>
                    <div class="card-body">
                        <div id="performanceSummary"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart Service -->
    <script src="web_ui/js/chart_service.js"></script>
    
    <!-- Page Logic -->
    <script>
        let performanceChart = null;
        
        // Event listeners
        document.getElementById('analyzeBtn').addEventListener('click', analyzePerformance);
        document.getElementById('resetBtn').addEventListener('click', resetAnalysis);
        
        async function analyzePerformance() {
            const symbol = document.getElementById('symbolInput').value.trim().toUpperCase();
            const index = document.getElementById('indexSelect').value;
            const period = document.getElementById('periodSelect').value;
            
            if (!symbol) {
                alert('Please enter a portfolio or stock symbol');
                return;
            }
            
            // Show loading state
            setLoadingState(true);
            
            try {
                // Fetch benchmark data
                const data = await ChartService.fetchIndexBenchmark(symbol, index, period);
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load data');
                }
                
                // Display results
                displayMetrics(data.data);
                displayChart(data.data.performance_chart);
                displayMetricsTable(data.data.metrics_table);
                displayRiskMetrics(data.data.risk_metrics, data.data.relative_performance);
                
                // Show result sections
                showResultSections();
                
            } catch (error) {
                console.error('Error analyzing performance:', error);
                alert('Error: ' + error.message);
            } finally {
                setLoadingState(false);
            }
        }
        
        function setLoadingState(loading) {
            const btn = document.getElementById('analyzeBtn');
            const text = document.getElementById('analyzeBtnText');
            const spinner = document.getElementById('analyzeBtnSpinner');
            
            btn.disabled = loading;
            if (loading) {
                text.classList.add('d-none');
                spinner.classList.remove('d-none');
            } else {
                text.classList.remove('d-none');
                spinner.classList.add('d-none');
            }
        }
        
        function displayMetrics(data) {
            const relPerf = data.relative_performance;
            const risk = data.risk_metrics;
            
            // Total Return
            const totalReturnEl = document.getElementById('totalReturn');
            const portfolioReturn = relPerf.portfolio_return;
            totalReturnEl.textContent = portfolioReturn.toFixed(2) + '%';
            totalReturnEl.className = 'metric-value ' + (portfolioReturn >= 0 ? 'metric-positive' : 'metric-negative');
            
            // Return Comparison
            const excessReturn = relPerf.excess_return;
            const comparisonEl = document.getElementById('returnComparison');
            comparisonEl.innerHTML = `${excessReturn >= 0 ? '📈' : '📉'} ${Math.abs(excessReturn).toFixed(2)}% vs Index`;
            comparisonEl.className = excessReturn >= 0 ? 'metric-positive' : 'metric-negative';
            
            // Beta
            const betaEl = document.getElementById('betaValue');
            betaEl.textContent = risk.beta.toFixed(2);
            betaEl.className = 'metric-value metric-neutral';
            
            // Alpha
            const alphaEl = document.getElementById('alphaValue');
            alphaEl.textContent = risk.alpha.toFixed(2) + '%';
            alphaEl.className = 'metric-value ' + (risk.alpha >= 0 ? 'metric-positive' : 'metric-negative');
            
            // Sharpe Ratio
            const sharpeEl = document.getElementById('sharpeValue');
            sharpeEl.textContent = risk.sharpe_ratio.toFixed(2);
            sharpeEl.className = 'metric-value ' + (risk.sharpe_ratio > 1 ? 'metric-positive' : 'metric-neutral');
        }
        
        function displayChart(chartData) {
            if (performanceChart) {
                ChartService.destroyChart(performanceChart);
            }
            performanceChart = ChartService.createIndexPerformanceChart('performanceChart', chartData);
        }
        
        function displayMetricsTable(tableData) {
            const tableEl = document.getElementById('metricsTable');
            tableEl.innerHTML = ChartService.formatMetricsTable(tableData);
        }
        
        function displayRiskMetrics(riskData, perfData) {
            const riskEl = document.getElementById('riskMetrics');
            riskEl.innerHTML = `
                <table class="table">
                    <tr>
                        <td><strong>Correlation</strong></td>
                        <td>${riskData.correlation.toFixed(3)}</td>
                        <td><small class="text-muted">${getCorrelationDescription(riskData.correlation)}</small></td>
                    </tr>
                    <tr>
                        <td><strong>Sortino Ratio</strong></td>
                        <td>${riskData.sortino_ratio.toFixed(2)}</td>
                        <td><small class="text-muted">Downside risk focus</small></td>
                    </tr>
                    <tr>
                        <td><strong>Beta</strong></td>
                        <td>${riskData.beta.toFixed(3)}</td>
                        <td><small class="text-muted">${getBetaDescription(riskData.beta)}</small></td>
                    </tr>
                </table>
            `;
            
            const perfEl = document.getElementById('performanceSummary');
            perfEl.innerHTML = `
                <table class="table">
                    <tr>
                        <td><strong>Portfolio Return</strong></td>
                        <td class="${perfData.portfolio_return >= 0 ? 'metric-positive' : 'metric-negative'}">
                            ${perfData.portfolio_return.toFixed(2)}%
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Index Return</strong></td>
                        <td>${perfData.index_return.toFixed(2)}%</td>
                    </tr>
                    <tr>
                        <td><strong>Excess Return</strong></td>
                        <td class="${perfData.excess_return >= 0 ? 'metric-positive' : 'metric-negative'}">
                            ${perfData.excess_return >= 0 ? '+' : ''}${perfData.excess_return.toFixed(2)}%
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Outperformance Periods</strong></td>
                        <td>${perfData.outperformance_periods} months</td>
                    </tr>
                </table>
            `;
        }
        
        function getCorrelationDescription(corr) {
            if (corr > 0.8) return 'Strong positive';
            if (corr > 0.5) return 'Moderate positive';
            if (corr > 0.2) return 'Weak positive';
            if (corr > -0.2) return 'No correlation';
            if (corr > -0.5) return 'Weak negative';
            if (corr > -0.8) return 'Moderate negative';
            return 'Strong negative';
        }
        
        function getBetaDescription(beta) {
            if (beta > 1.2) return 'More volatile than market';
            if (beta > 0.8) return 'Similar to market';
            return 'Less volatile than market';
        }
        
        function showResultSections() {
            document.getElementById('metricsRow').style.display = 'flex';
            document.getElementById('chartRow').style.display = 'block';
            document.getElementById('tableRow').style.display = 'block';
            document.getElementById('riskRow').style.display = 'flex';
        }
        
        function resetAnalysis() {
            document.getElementById('symbolInput').value = 'PORTFOLIO';
            document.getElementById('indexSelect').value = 'SPX';
            document.getElementById('periodSelect').value = '1Y';
            
            document.getElementById('metricsRow').style.display = 'none';
            document.getElementById('chartRow').style.display = 'none';
            document.getElementById('tableRow').style.display = 'none';
            document.getElementById('riskRow').style.display = 'none';
            
            if (performanceChart) {
                ChartService.destroyChart(performanceChart);
                performanceChart = null;
            }
        }
    </script>
</body>
</html>
