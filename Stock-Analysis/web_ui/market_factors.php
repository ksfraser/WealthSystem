<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Market Factors Analysis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2.5em;
            text-align: center;
        }

        .header p {
            color: #7f8c8d;
            text-align: center;
            font-size: 1.1em;
        }

        .controls {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .control-group {
            display: flex;
            flex-direction: column;
        }

        .control-group label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }

        .control-group select,
        .control-group input {
            padding: 10px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .control-group select:focus,
        .control-group input:focus {
            outline: none;
            border-color: #3498db;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #229954);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }

        .content-area {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            min-height: 400px;
        }

        .factors-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .factors-table th,
        .factors-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .factors-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            position: sticky;
            top: 0;
        }

        .factors-table tbody tr:hover {
            background: #f8f9fa;
        }

        .change-positive {
            color: #27ae60;
            font-weight: 600;
        }

        .change-negative {
            color: #e74c3c;
            font-weight: 600;
        }

        .change-neutral {
            color: #7f8c8d;
        }

        .factor-type {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .factor-type.index {
            background: #e3f2fd;
            color: #1976d2;
        }

        .factor-type.forex {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .factor-type.economic {
            background: #e8f5e8;
            color: #388e3c;
        }

        .factor-type.sector {
            background: #fff3e0;
            color: #f57c00;
        }

        .factor-type.sentiment {
            background: #fce4ec;
            color: #c2185b;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #f5c6cb;
        }

        .search-box {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .search-box:focus {
            outline: none;
            border-color: #3498db;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2em;
            }

            .controls-grid {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .factors-table {
                font-size: 14px;
            }

            .factors-table th,
            .factors-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 Market Factors Analysis</h1>
            <p>Comprehensive analysis of market factors affecting stock prices including sectors, indices, forex rates, and economic indicators</p>
        </div>

        <!-- Statistics Overview -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-value" id="totalFactors">-</div>
                <div class="stat-label">Total Factors</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="marketSentiment">-</div>
                <div class="stat-label">Market Sentiment</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="topPerformer">-</div>
                <div class="stat-label">Top Performer</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="lastUpdate">-</div>
                <div class="stat-label">Last Update</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="controls-grid">
                <div class="control-group">
                    <label for="factorType">Factor Type</label>
                    <select id="factorType">
                        <option value="">All Types</option>
                        <option value="index">Market Indices</option>
                        <option value="forex">Forex Rates</option>
                        <option value="economic">Economic Indicators</option>
                        <option value="sector">Sector Performance</option>
                        <option value="sentiment">Market Sentiment</option>
                        <option value="commodity">Commodities</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="sortBy">Sort By</label>
                    <select id="sortBy">
                        <option value="name">Name</option>
                        <option value="value">Value</option>
                        <option value="change_percent">Change %</option>
                        <option value="timestamp">Last Update</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="sortOrder">Sort Order</label>
                    <select id="sortOrder">
                        <option value="asc">Ascending</option>
                        <option value="desc">Descending</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="limitResults">Show Results</label>
                    <select id="limitResults">
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                        <option value="0">All</option>
                    </select>
                </div>
            </div>
            <div class="action-buttons">
                <button class="btn btn-primary" onclick="loadFactors()">🔄 Refresh Data</button>
                <button class="btn btn-secondary" onclick="showCorrelations()">📈 View Correlations</button>
                <button class="btn btn-success" onclick="exportData()">📥 Export Data</button>
                <button class="btn btn-primary" onclick="showTopPerformers()">🏆 Top Performers</button>
                <button class="btn btn-secondary" onclick="showWorstPerformers()">📉 Worst Performers</button>
            </div>
        </div>

        <!-- Search -->
        <input type="text" class="search-box" id="searchBox" placeholder="Search factors by name or symbol..." onkeyup="filterFactors()">

        <!-- Content Area -->
        <div class="content-area">
            <div id="loadingIndicator" class="loading">
                <div>📡 Loading market factors data...</div>
            </div>
            <div id="errorContainer" style="display: none;"></div>
            <div id="contentContainer" style="display: none;">
                <table class="factors-table" id="factorsTable">
                    <thead>
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Change</th>
                            <th>Change %</th>
                            <th>Last Update</th>
                        </tr>
                    </thead>
                    <tbody id="factorsTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let allFactors = [];
        let filteredFactors = [];

        // API base URL
        const API_BASE = './api/market-factors.php';

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadStatistics();
            loadFactors();
        });

        // Load statistics
        async function loadStatistics() {
            try {
                const response = await fetch(`${API_BASE}/stats`);
                const data = await response.json();
                
                if (data.success) {
                    const stats = data.data;
                    document.getElementById('totalFactors').textContent = stats.total_factors || 0;
                    document.getElementById('marketSentiment').textContent = formatSentiment(stats.sentiment || 0);
                    document.getElementById('lastUpdate').textContent = formatTime(stats.last_updated);
                    
                    // Load top performer separately
                    loadTopPerformer();
                }
            } catch (error) {
                console.error('Error loading statistics:', error);
            }
        }

        // Load top performer
        async function loadTopPerformer() {
            try {
                const response = await fetch(`${API_BASE}/top-performers?limit=1`);
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    const topPerformer = data.data[0];
                    document.getElementById('topPerformer').textContent = 
                        `${topPerformer.symbol} (${formatChange(topPerformer.change_percent)}%)`;
                }
            } catch (error) {
                console.error('Error loading top performer:', error);
            }
        }

        // Load factors data
        async function loadFactors() {
            showLoading(true);
            hideError();
            
            try {
                const factorType = document.getElementById('factorType').value;
                const url = factorType ? `${API_BASE}/${factorType}` : API_BASE;
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.success) {
                    allFactors = data.data || [];
                    filterAndDisplayFactors();
                } else {
                    showError(data.error || 'Failed to load factors data');
                }
            } catch (error) {
                showError('Network error: ' + error.message);
            } finally {
                showLoading(false);
            }
        }

        // Filter and display factors
        function filterAndDisplayFactors() {
            filteredFactors = [...allFactors];
            
            // Apply search filter
            const searchTerm = document.getElementById('searchBox').value.toLowerCase();
            if (searchTerm) {
                filteredFactors = filteredFactors.filter(factor => 
                    factor.symbol.toLowerCase().includes(searchTerm) ||
                    factor.name.toLowerCase().includes(searchTerm)
                );
            }
            
            // Apply sorting
            const sortBy = document.getElementById('sortBy').value;
            const sortOrder = document.getElementById('sortOrder').value;
            
            filteredFactors.sort((a, b) => {
                let aVal = a[sortBy];
                let bVal = b[sortBy];
                
                if (typeof aVal === 'string') {
                    aVal = aVal.toLowerCase();
                    bVal = bVal.toLowerCase();
                }
                
                if (sortOrder === 'desc') {
                    return bVal > aVal ? 1 : -1;
                } else {
                    return aVal > bVal ? 1 : -1;
                }
            });
            
            // Apply limit
            const limit = parseInt(document.getElementById('limitResults').value);
            if (limit > 0) {
                filteredFactors = filteredFactors.slice(0, limit);
            }
            
            displayFactors();
        }

        // Display factors in table
        function displayFactors() {
            const tbody = document.getElementById('factorsTableBody');
            tbody.innerHTML = '';
            
            filteredFactors.forEach(factor => {
                const row = tbody.insertRow();
                
                row.innerHTML = `
                    <td><strong>${factor.symbol}</strong></td>
                    <td>${factor.name}</td>
                    <td><span class="factor-type ${factor.type}">${factor.type}</span></td>
                    <td>${formatValue(factor.value)}</td>
                    <td class="${getChangeClass(factor.change_amount)}">${formatChange(factor.change_amount)}</td>
                    <td class="${getChangeClass(factor.change_percent)}">${formatChange(factor.change_percent)}%</td>
                    <td>${formatDateTime(factor.timestamp)}</td>
                `;
            });
            
            document.getElementById('contentContainer').style.display = 'block';
        }

        // Filter factors based on search
        function filterFactors() {
            filterAndDisplayFactors();
        }

        // Show correlations
        async function showCorrelations() {
            try {
                const response = await fetch(`${API_BASE}/correlations`);
                const data = await response.json();
                
                if (data.success) {
                    alert('Correlations feature coming soon! Data loaded: ' + JSON.stringify(data.data, null, 2));
                } else {
                    alert('Error loading correlations: ' + data.error);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            }
        }

        // Show top performers
        async function showTopPerformers() {
            try {
                const response = await fetch(`${API_BASE}/top-performers?limit=10`);
                const data = await response.json();
                
                if (data.success) {
                    let message = 'Top 10 Performers:\n\n';
                    data.data.forEach((factor, index) => {
                        message += `${index + 1}. ${factor.symbol} (${factor.name}): ${formatChange(factor.change_percent)}%\n`;
                    });
                    alert(message);
                } else {
                    alert('Error loading top performers: ' + data.error);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            }
        }

        // Show worst performers
        async function showWorstPerformers() {
            try {
                const response = await fetch(`${API_BASE}/worst-performers?limit=10`);
                const data = await response.json();
                
                if (data.success) {
                    let message = 'Worst 10 Performers:\n\n';
                    data.data.forEach((factor, index) => {
                        message += `${index + 1}. ${factor.symbol} (${factor.name}): ${formatChange(factor.change_percent)}%\n`;
                    });
                    alert(message);
                } else {
                    alert('Error loading worst performers: ' + data.error);
                }
            } catch (error) {
                alert('Network error: ' + error.message);
            }
        }

        // Export data
        function exportData() {
            const csvContent = "data:text/csv;charset=utf-8," + 
                "Symbol,Name,Type,Value,Change,Change%,Timestamp\n" +
                filteredFactors.map(factor => 
                    `"${factor.symbol}","${factor.name}","${factor.type}",${factor.value},${factor.change_amount},${factor.change_percent},"${factor.timestamp}"`
                ).join("\n");
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "market_factors_" + new Date().toISOString().split('T')[0] + ".csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Utility functions
        function showLoading(show) {
            document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
        }

        function showError(message) {
            const errorContainer = document.getElementById('errorContainer');
            errorContainer.innerHTML = `<div class="error">❌ ${message}</div>`;
            errorContainer.style.display = 'block';
        }

        function hideError() {
            document.getElementById('errorContainer').style.display = 'none';
        }

        function formatValue(value) {
            if (typeof value === 'number') {
                return value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 6 });
            }
            return value;
        }

        function formatChange(value) {
            if (typeof value === 'number') {
                return (value >= 0 ? '+' : '') + value.toFixed(2);
            }
            return value;
        }

        function getChangeClass(value) {
            if (value > 0) return 'change-positive';
            if (value < 0) return 'change-negative';
            return 'change-neutral';
        }

        function formatDateTime(timestamp) {
            return new Date(timestamp).toLocaleString();
        }

        function formatTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString();
        }

        function formatSentiment(value) {
            if (value > 75) return '😄 Bullish';
            if (value > 25) return '😐 Neutral';
            return '😟 Bearish';
        }

        // Event listeners for controls
        document.getElementById('factorType').addEventListener('change', loadFactors);
        document.getElementById('sortBy').addEventListener('change', filterAndDisplayFactors);
        document.getElementById('sortOrder').addEventListener('change', filterAndDisplayFactors);
        document.getElementById('limitResults').addEventListener('change', filterAndDisplayFactors);
    </script>
</body>
</html>
