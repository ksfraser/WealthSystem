<?php
require_once 'UiStyles.php';
require_once 'QuickActions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced Portfolio Charts</title>
    <?php UiStyles::render(); ?>
    
    <!-- Chart.js 4.x -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Chart.js Matrix Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix@2.0.0/dist/chartjs-chart-matrix.umd.min.js"></script>
    <!-- Chart.js Treemap Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap@2.3.0/dist/chartjs-chart-treemap.umd.min.js"></script>
    <!-- Chart.js Annotation Plugin -->
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@3.0.1/dist/chartjs-plugin-annotation.min.js"></script>
    
    <!-- Bootstrap 5 for tabs -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Advanced Chart Service -->
    <script src="js/advanced_chart_service.js"></script>
    
    <style>
        .chart-container {
            position: relative;
            height: 500px;
            margin: 20px 0;
        }
        .controls {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .tab-content {
            padding: 20px;
        }
        .loading {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        .error {
            padding: 15px;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            margin: 10px 0;
        }
        .rebalancing-table {
            margin: 20px 0;
        }
        .rebalancing-table td.positive {
            color: #28a745;
            font-weight: bold;
        }
        .rebalancing-table td.negative {
            color: #dc3545;
            font-weight: bold;
        }
        .nav-tabs .nav-link {
            color: #007bff;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <?php QuickActions::render(); ?>
    
    <div class="header">
        <h1>📊 Advanced Portfolio Charts</h1>
        <p>Interactive visualizations for portfolio analysis and optimization</p>
    </div>
    
    <!-- User Selection -->
    <div class="card">
        <h3>Select User</h3>
        <div class="controls">
            <label for="userId">User ID:</label>
            <input type="number" id="userId" value="1" min="1" class="form-control" style="width: 200px; display: inline-block;">
            <button onclick="loadAllCharts()" class="btn btn-primary ms-2">Load Charts</button>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <ul class="nav nav-tabs mt-4" id="chartTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="correlation-tab" data-bs-toggle="tab" data-bs-target="#correlation" type="button" role="tab">
                🔥 Correlation Heatmap
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="treemap-tab" data-bs-toggle="tab" data-bs-target="#treemap" type="button" role="tab">
                🌳 Portfolio Treemap
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="trends-tab" data-bs-toggle="tab" data-bs-target="#trends" type="button" role="tab">
                📈 Historical Trends
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="concentration-tab" data-bs-toggle="tab" data-bs-target="#concentration" type="button" role="tab">
                🎯 Concentration Risk
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="rebalancing-tab" data-bs-toggle="tab" data-bs-target="#rebalancing" type="button" role="tab">
                ⚖️ Rebalancing
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content card" id="chartTabContent">
        <!-- Correlation Heatmap Tab -->
        <div class="tab-pane fade show active" id="correlation" role="tabpanel">
            <h3>Sector Correlation Heatmap</h3>
            <p>Shows how different sectors move together. Values range from -1 (inverse correlation) to +1 (perfect correlation).</p>
            
            <div class="controls">
                <label for="correlationPeriod">Time Period:</label>
                <select id="correlationPeriod" class="form-select" style="width: 200px; display: inline-block;">
                    <option value="1w">1 Week</option>
                    <option value="1m">1 Month</option>
                    <option value="3m">3 Months</option>
                    <option value="6m">6 Months</option>
                    <option value="1y" selected>1 Year</option>
                    <option value="3y">3 Years</option>
                    <option value="5y">5 Years</option>
                </select>
                <button onclick="loadCorrelationHeatmap()" class="btn btn-primary ms-2">Update</button>
            </div>
            
            <div id="correlationChartContainer">
                <div class="chart-container">
                    <canvas id="correlationChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Portfolio Treemap Tab -->
        <div class="tab-pane fade" id="treemap" role="tabpanel">
            <h3>Portfolio Composition Treemap</h3>
            <p>Hierarchical view of your portfolio showing relative position sizes and performance.</p>
            
            <div id="treemapChartContainer">
                <div class="chart-container">
                    <canvas id="treemapChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Historical Trends Tab -->
        <div class="tab-pane fade" id="trends" role="tabpanel">
            <h3>Historical Sector Allocation Trends</h3>
            <p>Track how your sector allocations have changed over time.</p>
            
            <div class="controls">
                <label for="trendsStartDate">Start Date:</label>
                <input type="date" id="trendsStartDate" class="form-control" style="width: 200px; display: inline-block;">
                
                <label for="trendsEndDate" class="ms-3">End Date:</label>
                <input type="date" id="trendsEndDate" class="form-control" style="width: 200px; display: inline-block;">
                
                <button onclick="loadHistoricalTrends()" class="btn btn-primary ms-2">Update</button>
            </div>
            
            <div id="trendsChartContainer">
                <div class="chart-container">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Concentration Risk Tab -->
        <div class="tab-pane fade" id="concentration" role="tabpanel">
            <h3>Portfolio Concentration Risk (HHI)</h3>
            <p>Herfindahl-Hirschman Index (HHI) measures portfolio concentration. Lower values indicate better diversification.</p>
            
            <div class="alert alert-info">
                <strong>HHI Guidelines:</strong>
                <ul>
                    <li><strong>&lt; 1,500:</strong> Low concentration (well diversified)</li>
                    <li><strong>1,500 - 2,500:</strong> Moderate concentration</li>
                    <li><strong>&gt; 2,500:</strong> High concentration (consider diversifying)</li>
                </ul>
            </div>
            
            <div class="controls">
                <label for="concentrationStartDate">Start Date:</label>
                <input type="date" id="concentrationStartDate" class="form-control" style="width: 200px; display: inline-block;">
                
                <label for="concentrationEndDate" class="ms-3">End Date:</label>
                <input type="date" id="concentrationEndDate" class="form-control" style="width: 200px; display: inline-block;">
                
                <button onclick="loadConcentrationTrend()" class="btn btn-primary ms-2">Update</button>
            </div>
            
            <div id="concentrationChartContainer">
                <div class="chart-container">
                    <canvas id="concentrationChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Rebalancing Suggestions Tab -->
        <div class="tab-pane fade" id="rebalancing" role="tabpanel">
            <h3>Rebalancing Suggestions</h3>
            <p>Compares your current sector allocation to target allocation and suggests rebalancing actions.</p>
            
            <div id="rebalancingContainer">
                <div class="loading">Click "Load Charts" to view rebalancing suggestions</div>
            </div>
        </div>
    </div>
</div>

<script>
    // Global state
    let currentUserId = 1;
    
    // Initialize dates to last 6 months by default
    window.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const sixMonthsAgo = new Date();
        sixMonthsAgo.setMonth(sixMonthsAgo.getMonth() - 6);
        
        document.getElementById('trendsEndDate').valueAsDate = today;
        document.getElementById('trendsStartDate').valueAsDate = sixMonthsAgo;
        document.getElementById('concentrationEndDate').valueAsDate = today;
        document.getElementById('concentrationStartDate').valueAsDate = sixMonthsAgo;
    });
    
    /**
     * Load all charts for the selected user
     */
    function loadAllCharts() {
        currentUserId = parseInt(document.getElementById('userId').value);
        
        if (currentUserId <= 0) {
            alert('Please enter a valid user ID');
            return;
        }
        
        // Load all charts
        loadCorrelationHeatmap();
        loadPortfolioTreemap();
        loadHistoricalTrends();
        loadConcentrationTrend();
        loadRebalancingSuggestions();
    }
    
    /**
     * Load correlation heatmap
     */
    async function loadCorrelationHeatmap() {
        const container = document.getElementById('correlationChartContainer');
        AdvancedChartService.showLoading(container);
        
        try {
            const period = document.getElementById('correlationPeriod').value;
            const data = await AdvancedChartService.fetchCorrelationHeatmap(currentUserId, period);
            
            // Clear container
            container.innerHTML = '<div class="chart-container"><canvas id="correlationChart"></canvas></div>';
            
            // Create chart
            AdvancedChartService.createCorrelationHeatmap('correlationChart', data);
        } catch (error) {
            AdvancedChartService.showError(container, error.message);
        }
    }
    
    /**
     * Load portfolio treemap
     */
    async function loadPortfolioTreemap() {
        const container = document.getElementById('treemapChartContainer');
        AdvancedChartService.showLoading(container);
        
        try {
            const data = await AdvancedChartService.fetchTreemapData(currentUserId);
            
            // Clear container
            container.innerHTML = '<div class="chart-container"><canvas id="treemapChart"></canvas></div>';
            
            // Create chart
            AdvancedChartService.createPortfolioTreemap('treemapChart', data);
        } catch (error) {
            AdvancedChartService.showError(container, error.message);
        }
    }
    
    /**
     * Load historical trends
     */
    async function loadHistoricalTrends() {
        const container = document.getElementById('trendsChartContainer');
        AdvancedChartService.showLoading(container);
        
        try {
            const startDate = document.getElementById('trendsStartDate').value;
            const endDate = document.getElementById('trendsEndDate').value;
            
            if (!startDate || !endDate) {
                throw new Error('Please select both start and end dates');
            }
            
            const data = await AdvancedChartService.fetchHistoricalTrends(currentUserId, startDate, endDate);
            
            // Clear container
            container.innerHTML = '<div class="chart-container"><canvas id="trendsChart"></canvas></div>';
            
            // Create chart
            AdvancedChartService.createHistoricalTrendsChart('trendsChart', data);
        } catch (error) {
            AdvancedChartService.showError(container, error.message);
        }
    }
    
    /**
     * Load concentration trend
     */
    async function loadConcentrationTrend() {
        const container = document.getElementById('concentrationChartContainer');
        AdvancedChartService.showLoading(container);
        
        try {
            const startDate = document.getElementById('concentrationStartDate').value;
            const endDate = document.getElementById('concentrationEndDate').value;
            
            if (!startDate || !endDate) {
                throw new Error('Please select both start and end dates');
            }
            
            // Fetch concentration data
            const response = await fetch(`/api/advanced-charts.php?action=concentration&user_id=${currentUserId}&start_date=${startDate}&end_date=${endDate}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch concentration data');
            }
            
            const result = await response.json();
            
            if (!result.success) {
                throw new Error(result.error || 'Unknown error');
            }
            
            // Clear container
            container.innerHTML = '<div class="chart-container"><canvas id="concentrationChart"></canvas></div>';
            
            // Create chart
            AdvancedChartService.createConcentrationTrendChart('concentrationChart', result.data);
        } catch (error) {
            AdvancedChartService.showError(container, error.message);
        }
    }
    
    /**
     * Load rebalancing suggestions
     */
    async function loadRebalancingSuggestions() {
        const container = document.getElementById('rebalancingContainer');
        AdvancedChartService.showLoading(container);
        
        try {
            const data = await AdvancedChartService.fetchRebalancingSuggestions(currentUserId);
            AdvancedChartService.displayRebalancingSuggestions(container, data);
        } catch (error) {
            AdvancedChartService.showError(container, error.message);
        }
    }
</script>
</body>
</html>
