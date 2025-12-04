<?php
/**
 * Sector Analysis Page
 * 
 * Displays portfolio sector allocation, comparison with S&P 500,
 * and concentration risk metrics.
 * 
 * Features:
 * - Pie chart of sector allocation
 * - Bar chart comparing portfolio vs S&P 500
 * - Concentration risk indicators
 * - Diversification score
 * 
 * @version 1.0.0
 */

require_once 'web_ui/Navigation/RequireLogin.php';
require_once 'web_ui/Navigation/NavigationProvider.php';

$currentPage = 'sector_analysis';
$navManager = getNavManager();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sector Analysis - Stock Analysis</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }
        .metric-card {
            border-left: 4px solid #007bff;
            margin-bottom: 20px;
        }
        .risk-low { border-left-color: #28a745; }
        .risk-medium { border-left-color: #ffc107; }
        .risk-high { border-left-color: #dc3545; }
        .diversification-score {
            font-size: 3rem;
            font-weight: bold;
        }
        .score-excellent { color: #28a745; }
        .score-good { color: #17a2b8; }
        .score-fair { color: #ffc107; }
        .score-poor { color: #dc3545; }
    </style>
</head>
<body>
    <?php include 'web_ui/Navigation/navigation.php'; ?>
    
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1>📊 Portfolio Sector Analysis</h1>
                <p class="lead">Analyze your portfolio's sector allocation and concentration risk</p>
                <hr>
            </div>
        </div>
        
        <!-- Diversification Score Card -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">Diversification Score</h5>
                        <div id="diversificationScore" class="diversification-score">--</div>
                        <small class="text-muted">0-100 scale</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card metric-card" id="concentrationCard">
                    <div class="card-body text-center">
                        <h5 class="card-title">Concentration Risk</h5>
                        <div id="concentrationRisk" style="font-size: 2rem; font-weight: bold;">--</div>
                        <small class="text-muted" id="topSector">--</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card metric-card">
                    <div class="card-body text-center">
                        <h5 class="card-title">HHI Index</h5>
                        <div id="hhiIndex" style="font-size: 2rem; font-weight: bold;">--</div>
                        <small class="text-muted">Lower is better</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Sector Allocation</h5>
                        <div class="chart-container">
                            <canvas id="sectorPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Portfolio vs S&P 500</h5>
                        <div class="chart-container">
                            <canvas id="sectorComparisonChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overweight/Underweight Analysis -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Overweight Sectors (>5% vs S&P 500)</h5>
                    </div>
                    <div class="card-body">
                        <div id="overweightSectors">
                            <p class="text-muted">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">Underweight Sectors (<-5% vs S&P 500)</h5>
                    </div>
                    <div class="card-body">
                        <div id="underweightSectors">
                            <p class="text-muted">Loading...</p>
                        </div>
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
        let pieChart = null;
        let comparisonChart = null;
        
        async function loadSectorAnalysis() {
            try {
                // Get user ID from session (you may need to adjust this)
                const userId = <?php echo $_SESSION['user_id'] ?? 1; ?>;
                
                // Fetch sector analysis data
                const data = await ChartService.fetchSectorAnalysis(userId);
                
                // Update diversification score
                updateDiversificationScore(data.diversification_score);
                
                // Update concentration metrics
                updateConcentrationMetrics(data.concentration_risk);
                
                // Create pie chart
                if (pieChart) {
                    ChartService.destroyChart(pieChart);
                }
                pieChart = ChartService.createSectorPieChart('sectorPieChart', data.pie_chart);
                
                // Create comparison chart
                if (comparisonChart) {
                    ChartService.destroyChart(comparisonChart);
                }
                comparisonChart = ChartService.createSectorComparisonChart('sectorComparisonChart', data.comparison_chart);
                
                // Update overweight/underweight lists
                updateOverweightUnderweight(data.benchmark_comparison);
                
            } catch (error) {
                console.error('Error loading sector analysis:', error);
                ChartService.showError('sectorPieChart', 'Failed to load sector analysis data');
            }
        }
        
        function updateDiversificationScore(score) {
            const scoreElement = document.getElementById('diversificationScore');
            scoreElement.textContent = score.toFixed(0);
            
            // Color code based on score
            scoreElement.className = 'diversification-score ';
            if (score >= 80) {
                scoreElement.classList.add('score-excellent');
            } else if (score >= 60) {
                scoreElement.classList.add('score-good');
            } else if (score >= 40) {
                scoreElement.classList.add('score-fair');
            } else {
                scoreElement.classList.add('score-poor');
            }
        }
        
        function updateConcentrationMetrics(riskData) {
            const riskElement = document.getElementById('concentrationRisk');
            const topSectorElement = document.getElementById('topSector');
            const hhiElement = document.getElementById('hhiIndex');
            const card = document.getElementById('concentrationCard');
            
            riskElement.textContent = riskData.risk_level;
            topSectorElement.textContent = `Top: ${riskData.top_sector} (${riskData.top_weight.toFixed(1)}%)`;
            hhiElement.textContent = riskData.hhi.toFixed(0);
            
            // Update card border color
            card.classList.remove('risk-low', 'risk-medium', 'risk-high');
            if (riskData.risk_level === 'LOW') {
                card.classList.add('risk-low');
            } else if (riskData.risk_level === 'MEDIUM') {
                card.classList.add('risk-medium');
            } else {
                card.classList.add('risk-high');
            }
        }
        
        function updateOverweightUnderweight(comparison) {
            const overweightDiv = document.getElementById('overweightSectors');
            const underweightDiv = document.getElementById('underweightSectors');
            
            if (comparison.overweight.length === 0) {
                overweightDiv.innerHTML = '<p class="text-muted">None</p>';
            } else {
                let html = '<ul class="list-group">';
                comparison.overweight.forEach(sector => {
                    html += `<li class="list-group-item">
                        <strong>${sector.name}</strong>
                        <span class="badge bg-success float-end">+${sector.difference.toFixed(1)}%</span>
                    </li>`;
                });
                html += '</ul>';
                overweightDiv.innerHTML = html;
            }
            
            if (comparison.underweight.length === 0) {
                underweightDiv.innerHTML = '<p class="text-muted">None</p>';
            } else {
                let html = '<ul class="list-group">';
                comparison.underweight.forEach(sector => {
                    html += `<li class="list-group-item">
                        <strong>${sector.name}</strong>
                        <span class="badge bg-warning text-dark float-end">${sector.difference.toFixed(1)}%</span>
                    </li>`;
                });
                html += '</ul>';
                underweightDiv.innerHTML = html;
            }
        }
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', loadSectorAnalysis);
    </script>
</body>
</html>
