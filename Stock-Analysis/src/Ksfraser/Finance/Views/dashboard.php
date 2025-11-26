<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trading Dashboard - Finance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .metric-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        .strategy-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .strategy-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .signal-badge {
            font-size: 0.8rem;
            padding: 4px 8px;
        }
        .status-active {
            color: #28a745;
        }
        .status-inactive {
            color: #6c757d;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><i class="fas fa-chart-line"></i> Finance System</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="/finance/dashboard">Dashboard</a>
                <a class="nav-link" href="/finance/strategies">Strategies</a>
                <a class="nav-link" href="/finance/backtest">Backtest</a>
                <a class="nav-link" href="/finance/portfolio">Portfolio</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Portfolio Summary -->
            <div class="col-md-3">
                <div class="metric-card">
                    <div class="metric-value" id="total-value">$0</div>
                    <div class="metric-label">Total Portfolio Value</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="metric-value" id="total-pnl">$0</div>
                    <div class="metric-label">Total P&L</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <div class="metric-value" id="open-positions">0</div>
                    <div class="metric-label">Open Positions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="metric-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <div class="metric-value" id="win-rate">0%</div>
                    <div class="metric-label">Win Rate</div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Active Strategies -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-cogs"></i> Active Strategies</h5>
                        <a href="/finance/strategies" class="btn btn-sm btn-primary">Manage</a>
                    </div>
                    <div class="card-body" id="strategies-list">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Signals -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5><i class="fas fa-signal"></i> Recent Trading Signals</h5>
                    </div>
                    <div class="card-body" id="signals-list">
                        <div class="text-center">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Performance Chart -->
            <div class="col-md-8">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5><i class="fas fa-chart-area"></i> Portfolio Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="performance-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-header">
                        <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-success" onclick="showExecuteStrategyModal()">
                                <i class="fas fa-play"></i> Execute Strategy
                            </button>
                            <button class="btn btn-info" onclick="showBacktestModal()">
                                <i class="fas fa-history"></i> Run Backtest
                            </button>
                            <button class="btn btn-warning" onclick="updatePrices()">
                                <i class="fas fa-sync"></i> Update Prices
                            </button>
                            <a href="/finance/market-data" class="btn btn-secondary">
                                <i class="fas fa-chart-bar"></i> Market Data
                            </a>
                        </div>

                        <!-- Market Status -->
                        <div class="mt-4">
                            <h6>Market Status</h6>
                            <div class="d-flex justify-content-between">
                                <span>Status:</span>
                                <span class="badge bg-success" id="market-status">Open</span>
                            </div>
                            <div class="d-flex justify-content-between mt-1">
                                <span>Last Update:</span>
                                <small id="last-update" class="text-muted">Loading...</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Execute Strategy Modal -->
    <div class="modal fade" id="executeStrategyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Execute Trading Strategy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="execute-strategy-form">
                        <div class="mb-3">
                            <label for="strategy-select" class="form-label">Strategy</label>
                            <select class="form-select" id="strategy-select" required>
                                <option value="">Select a strategy...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="symbol-input" class="form-label">Symbol</label>
                            <input type="text" class="form-control" id="symbol-input" placeholder="e.g., AAPL" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Custom Parameters</label>
                            <textarea class="form-control" id="parameters-input" rows="3" placeholder='{"risk_percentage": 2.0}'></textarea>
                            <small class="text-muted">JSON format for custom parameters</small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="executeStrategy()">Execute</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Backtest Modal -->
    <div class="modal fade" id="backtestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Run Backtest</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="backtest-form">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="backtest-strategy" class="form-label">Strategy</label>
                                    <select class="form-select" id="backtest-strategy" required>
                                        <option value="">Select a strategy...</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="backtest-symbol" class="form-label">Symbol</label>
                                    <input type="text" class="form-control" id="backtest-symbol" placeholder="e.g., AAPL">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="start-date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start-date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="end-date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end-date" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="initial-capital" class="form-label">Initial Capital</label>
                            <input type="number" class="form-control" id="initial-capital" value="100000" min="1000" step="1000">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="runBacktest()">Run Backtest</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            loadStrategiesForModals();
            setDefaultDates();
        });

        async function loadDashboardData() {
            try {
                const response = await fetch('/finance/api/dashboard');
                const data = await response.json();
                
                if (data.success) {
                    updateDashboardMetrics(data.data);
                    updateStrategiesList(data.data.strategies);
                    updateSignalsList(data.data.recent_signals);
                    updatePerformanceChart(data.data.performance);
                } else {
                    console.error('Failed to load dashboard data:', data.error);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function updateDashboardMetrics(data) {
            const portfolio = data.portfolio.summary || {};
            const performance = data.performance || {};
            
            document.getElementById('total-value').textContent = 
                '$' + (portfolio.total_portfolio_value || 0).toLocaleString();
            document.getElementById('total-pnl').textContent = 
                '$' + ((portfolio.total_realized_pnl || 0) + (portfolio.total_unrealized_pnl || 0)).toLocaleString();
            document.getElementById('open-positions').textContent = portfolio.open_positions || 0;
            document.getElementById('win-rate').textContent = 
                (performance.win_rate || 0).toFixed(1) + '%';
        }

        function updateStrategiesList(strategies) {
            const container = document.getElementById('strategies-list');
            if (!strategies || strategies.length === 0) {
                container.innerHTML = '<p class="text-muted">No active strategies found.</p>';
                return;
            }

            const html = strategies.map(strategy => `
                <div class="strategy-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">${strategy.name}</h6>
                            <small class="text-muted">${strategy.strategy_type}</small>
                        </div>
                        <div class="text-end">
                            <span class="status-${strategy.is_active ? 'active' : 'inactive'}">
                                <i class="fas fa-circle"></i> ${strategy.is_active ? 'Active' : 'Inactive'}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');
            
            container.innerHTML = html;
        }

        function updateSignalsList(signals) {
            const container = document.getElementById('signals-list');
            if (!signals || signals.length === 0) {
                container.innerHTML = '<p class="text-muted">No recent signals.</p>';
                return;
            }

            const html = signals.map(signal => {
                const badgeClass = signal.action === 'BUY' ? 'bg-success' : 
                                 signal.action === 'SELL' ? 'bg-danger' : 'bg-warning';
                return `
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong>${signal.symbol}</strong>
                            <small class="text-muted d-block">${signal.strategy_name}</small>
                        </div>
                        <div class="text-end">
                            <span class="badge signal-badge ${badgeClass}">${signal.action}</span>
                            <small class="text-muted d-block">${new Date(signal.execution_date).toLocaleDateString()}</small>
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = html;
        }

        function updatePerformanceChart(performance) {
            // This would typically show equity curve data
            // For now, showing a placeholder chart
            const ctx = document.getElementById('performance-chart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Portfolio Value',
                        data: [100000, 102000, 98000, 105000, 107000, 110000],
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        async function loadStrategiesForModals() {
            try {
                const response = await fetch('/finance/api/strategies');
                const data = await response.json();
                
                if (data.success) {
                    const strategies = data.data.strategies;
                    const executeSelect = document.getElementById('strategy-select');
                    const backtestSelect = document.getElementById('backtest-strategy');
                    
                    const options = strategies.map(s => 
                        `<option value="${s.id}">${s.name}</option>`
                    ).join('');
                    
                    executeSelect.innerHTML = '<option value="">Select a strategy...</option>' + options;
                    backtestSelect.innerHTML = '<option value="">Select a strategy...</option>' + options;
                }
            } catch (error) {
                console.error('Error loading strategies:', error);
            }
        }

        function setDefaultDates() {
            const endDate = new Date();
            const startDate = new Date();
            startDate.setFullYear(endDate.getFullYear() - 1);
            
            document.getElementById('end-date').value = endDate.toISOString().split('T')[0];
            document.getElementById('start-date').value = startDate.toISOString().split('T')[0];
        }

        function showExecuteStrategyModal() {
            new bootstrap.Modal(document.getElementById('executeStrategyModal')).show();
        }

        function showBacktestModal() {
            new bootstrap.Modal(document.getElementById('backtestModal')).show();
        }

        async function executeStrategy() {
            const form = document.getElementById('execute-strategy-form');
            const formData = new FormData();
            
            formData.append('strategy_id', document.getElementById('strategy-select').value);
            formData.append('symbol', document.getElementById('symbol-input').value.toUpperCase());
            formData.append('parameters', document.getElementById('parameters-input').value || '{}');
            
            try {
                const response = await fetch('/finance/api/execute-strategy', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Strategy executed successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('executeStrategyModal')).hide();
                    loadDashboardData(); // Refresh dashboard
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error executing strategy: ' + error.message);
            }
        }

        async function runBacktest() {
            const formData = new FormData();
            
            formData.append('strategy_id', document.getElementById('backtest-strategy').value);
            formData.append('symbol', document.getElementById('backtest-symbol').value.toUpperCase());
            formData.append('start_date', document.getElementById('start-date').value);
            formData.append('end_date', document.getElementById('end-date').value);
            formData.append('initial_capital', document.getElementById('initial-capital').value);
            
            try {
                const response = await fetch('/finance/api/backtest', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Backtest completed! Check the Backtest page for results.');
                    bootstrap.Modal.getInstance(document.getElementById('backtestModal')).hide();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Error running backtest: ' + error.message);
            }
        }

        async function updatePrices() {
            // This would trigger a price update for all symbols
            alert('Price update feature would be implemented here');
        }

        // Update market status
        function updateMarketStatus() {
            const now = new Date();
            const hours = now.getHours();
            const day = now.getDay();
            
            const isMarketHours = (day >= 1 && day <= 5) && (hours >= 9 && hours <= 16);
            const statusElement = document.getElementById('market-status');
            
            if (isMarketHours) {
                statusElement.textContent = 'Open';
                statusElement.className = 'badge bg-success';
            } else {
                statusElement.textContent = 'Closed';
                statusElement.className = 'badge bg-secondary';
            }
            
            document.getElementById('last-update').textContent = now.toLocaleString();
        }

        // Update market status on load and every minute
        updateMarketStatus();
        setInterval(updateMarketStatus, 60000);
    </script>
</body>
</html>
