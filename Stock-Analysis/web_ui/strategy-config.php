<?php
/**
 * Strategy Configuration - Requires Authentication
 * 
 * Allows authenticated users to configure trading strategy parameters.
 * Admin access recommended for production systems.
 */

// Load DI Container
$container = require_once __DIR__ . '/bootstrap.php';

// Resolve authentication service
$auth = $container->get(UserAuthDAO::class);

// Require login with proper exception handling
try {
    $auth->requireLogin();
    $user = $auth->getCurrentUser();
} catch (\App\Auth\LoginRequiredException $e) {
    $returnUrl = urlencode($_SERVER['REQUEST_URI'] ?? 'strategy-config.php');
    header('Location: login.php?return_url=' . $returnUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Strategy Configuration - Stock Analysis</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Navigation Header */
        .nav-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .nav-title {
            font-size: 24px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }

        .nav-links a:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav-links a.btn-primary {
            background: white;
            color: #667eea;
        }

        .nav-links a.btn-primary:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #7f8c8d;
            font-size: 14px;
        }

        .strategy-selector {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .strategy-selector label {
            font-weight: 600;
            margin-right: 15px;
            color: #2c3e50;
        }

        .strategy-selector select {
            padding: 10px 15px;
            font-size: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            min-width: 350px;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .strategy-selector select:focus {
            outline: none;
            border-color: #3498db;
        }

        .config-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-section:last-child {
            margin-bottom: 0;
        }

        .category-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .parameter-group {
            display: grid;
            gap: 20px;
            margin-bottom: 15px;
        }

        .parameter-item {
            padding: 15px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            transition: border-color 0.3s, background-color 0.3s;
        }

        .parameter-item:hover {
            border-color: #3498db;
            background-color: #f8f9fa;
        }

        .parameter-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .parameter-description {
            font-size: 13px;
            color: #7f8c8d;
            margin-bottom: 12px;
        }

        .parameter-input-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .parameter-input {
            flex: 1;
            padding: 10px;
            font-size: 14px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            transition: border-color 0.3s;
        }

        .parameter-input:focus {
            outline: none;
            border-color: #3498db;
        }

        .parameter-input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .parameter-range {
            font-size: 12px;
            color: #95a5a6;
        }

        .parameter-value-display {
            min-width: 100px;
            text-align: right;
            font-weight: 600;
            color: #3498db;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .btn {
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
        }

        .btn-export {
            background: #27ae60;
            color: white;
        }

        .btn-export:hover {
            background: #229954;
        }

        .status-message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
            font-size: 16px;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #28a745;
            display: block;
        }

        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #dc3545;
            display: block;
        }

        .loading {
            text-align: center;
            padding: 60px;
            color: #7f8c8d;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stats-bar {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            flex: 1;
            background: #ecf0f1;
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }

            .nav-links {
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                padding: 6px 12px;
                font-size: 14px;
            }

            .stats-bar {
                flex-direction: column;
            }

            .parameter-grid {
                grid-template-columns: 1fr !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Header -->
    <div class="nav-header">
        <div class="nav-container">
            <div class="nav-title">
                ⚙️ Strategy Configuration
            </div>
            <div class="nav-links">
                <span style="margin-right: 15px; opacity: 0.9;">Welcome, <?php echo htmlspecialchars($user['username']); ?></span>
                <a href="dashboard.php">Dashboard</a>
                <a href="stock_analysis.php">Stock Analysis</a>
                <a href="job_manager.php">Job Manager</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <header>
            <h1>📊 Trading Strategy Configuration</h1>
            <p class="subtitle">Configure parameters for your trading strategies. Changes take effect immediately.</p>
            <?php if (!$user['is_admin']): ?>
            <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 12px; border-radius: 6px; margin-top: 15px;">
                ⚠️ <strong>Note:</strong> You are viewing as a standard user. In production, strategy configuration should typically be restricted to administrators only.
            </div>
            <?php endif; ?>
        </header>

        <div id="statusMessage" class="status-message"></div>

        <div class="strategy-selector">
            <label for="strategySelect">Select Strategy:</label>
            <select id="strategySelect">
                <option value="">Loading strategies...</option>
            </select>
        </div>

        <div id="configPanel">
            <div class="loading">
                <div class="spinner"></div>
                <p>Select a strategy to configure</p>
            </div>
        </div>
    </div>

    <script>
        let currentStrategy = '';
        let parameters = {};

        // Load available strategies on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadStrategies();
        });

        // Handle strategy selection change
        document.getElementById('strategySelect').addEventListener('change', function() {
            currentStrategy = this.value;
            if (currentStrategy) {
                loadStrategyConfig(currentStrategy);
            }
        });

        // Load list of available strategies
        async function loadStrategies() {
            try {
                const response = await fetch('api/strategy-config.php?action=list_strategies');
                const data = await response.json();
                
                const select = document.getElementById('strategySelect');
                select.innerHTML = '<option value="">-- Select a Strategy --</option>';
                
                data.strategies.forEach(strategy => {
                    const option = document.createElement('option');
                    option.value = strategy;
                    option.textContent = strategy;
                    select.appendChild(option);
                });
            } catch (error) {
                showMessage('Failed to load strategies: ' + error.message, 'error');
            }
        }

        // Load configuration for selected strategy
        async function loadStrategyConfig(strategyName) {
            document.getElementById('configPanel').innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Loading configuration...</p>
                </div>
            `;

            try {
                const response = await fetch(`api/strategy-config.php?action=get_config&strategy=${encodeURIComponent(strategyName)}`);
                const data = await response.json();
                
                parameters = data.parameters;
                renderConfig(data.parameters);
            } catch (error) {
                showMessage('Failed to load configuration: ' + error.message, 'error');
            }
        }

        // Render configuration form
        function renderConfig(params) {
            // Group by category
            const grouped = {};
            params.forEach(param => {
                const category = param.category || 'General';
                if (!grouped[category]) {
                    grouped[category] = [];
                }
                grouped[category].push(param);
            });

            // Count stats
            const totalParams = params.length;
            const categories = Object.keys(grouped).length;

            let html = `
                <div class="config-panel">
                    <div class="stats-bar">
                        <div class="stat-card">
                            <div class="stat-value">${totalParams}</div>
                            <div class="stat-label">Parameters</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${categories}</div>
                            <div class="stat-label">Categories</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value">${currentStrategy}</div>
                            <div class="stat-label">Strategy</div>
                        </div>
                    </div>
            `;

            // Render each category
            Object.keys(grouped).sort().forEach(category => {
                html += `
                    <div class="category-section">
                        <h2 class="category-title">${category}</h2>
                        <div class="parameter-group">
                `;

                grouped[category].forEach(param => {
                    html += renderParameter(param);
                });

                html += `
                        </div>
                    </div>
                `;
            });

            // Action buttons
            html += `
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="saveConfiguration()">💾 Save Changes</button>
                    <button class="btn btn-secondary" onclick="resetConfiguration()">🔄 Reset to Defaults</button>
                    <button class="btn btn-export" onclick="exportConfiguration()">📥 Export Config</button>
                </div>
            </div>
            `;

            document.getElementById('configPanel').innerHTML = html;
        }

        // Render individual parameter
        function renderParameter(param) {
            const type = param.parameter_type;
            const value = param.parameter_value;
            const min = param.min_value;
            const max = param.max_value;

            let inputHtml = '';
            
            if (type === 'bool' || type === 'boolean') {
                const checked = value === '1' || value === 'true' ? 'checked' : '';
                inputHtml = `
                    <input type="checkbox" 
                           id="param_${param.parameter_key}" 
                           class="parameter-input" 
                           ${checked}
                           onchange="updateParameter('${param.parameter_key}', this.checked ? 1 : 0)">
                `;
            } else if (type === 'int' || type === 'integer') {
                inputHtml = `
                    <input type="number" 
                           id="param_${param.parameter_key}" 
                           class="parameter-input" 
                           value="${value}"
                           min="${min || ''}"
                           max="${max || ''}"
                           step="1"
                           onchange="updateParameter('${param.parameter_key}', this.value)">
                    <span class="parameter-value-display">${value}</span>
                `;
            } else { // float/decimal
                inputHtml = `
                    <input type="number" 
                           id="param_${param.parameter_key}" 
                           class="parameter-input" 
                           value="${value}"
                           min="${min || ''}"
                           max="${max || ''}"
                           step="0.01"
                           onchange="updateParameter('${param.parameter_key}', this.value)">
                    <span class="parameter-value-display">${value}</span>
                `;
            }

            const rangeText = (min !== null && max !== null) ? 
                `<span class="parameter-range">(Range: ${min} - ${max})</span>` : '';

            return `
                <div class="parameter-item">
                    <div class="parameter-label">
                        ${param.display_name}
                        ${rangeText}
                    </div>
                    <div class="parameter-description">${param.description || ''}</div>
                    <div class="parameter-input-group">
                        ${inputHtml}
                    </div>
                </div>
            `;
        }

        // Update parameter value in memory
        function updateParameter(key, value) {
            const param = parameters.find(p => p.parameter_key === key);
            if (param) {
                param.parameter_value = value;
                // Update display value
                const display = document.querySelector(`#param_${key}`).parentElement.querySelector('.parameter-value-display');
                if (display) {
                    display.textContent = value;
                }
            }
        }

        // Save configuration to server
        async function saveConfiguration() {
            const saveBtn = document.querySelector('button[onclick="saveConfiguration()"]');
            const originalText = saveBtn.textContent;
            
            // Show saving state
            saveBtn.disabled = true;
            saveBtn.textContent = '💾 Saving...';
            saveBtn.style.opacity = '0.6';
            
            const updates = {};
            parameters.forEach(param => {
                updates[param.parameter_key] = param.parameter_value;
            });

            console.log('Saving configuration:', { strategy: currentStrategy, updates });

            try {
                const payload = {
                    action: 'save_config',
                    strategy: currentStrategy,
                    parameters: updates
                };
                
                console.log('Request payload:', payload);
                
                const response = await fetch('api/strategy-config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                console.log('Response status:', response.status);
                
                const data = await response.json();
                console.log('Response data:', data);
                
                if (data.success) {
                    // Show success on button
                    saveBtn.textContent = '✅ Saved!';
                    saveBtn.style.background = '#28a745';
                    
                    showMessage('✅ Configuration saved successfully!', 'success');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        saveBtn.textContent = originalText;
                        saveBtn.style.background = '';
                        saveBtn.style.opacity = '1';
                        saveBtn.disabled = false;
                    }, 2000);
                } else {
                    saveBtn.textContent = originalText;
                    saveBtn.style.opacity = '1';
                    saveBtn.disabled = false;
                    showMessage('❌ Failed to save configuration: ' + (data.error || 'Unknown error'), 'error');
                }
            } catch (error) {
                console.error('Save error:', error);
                saveBtn.textContent = originalText;
                saveBtn.style.opacity = '1';
                saveBtn.disabled = false;
                showMessage('❌ Error saving configuration: ' + error.message, 'error');
            }
        }

        // Reset to defaults
        async function resetConfiguration() {
            if (!confirm('Are you sure you want to reset all parameters to their default values?')) {
                return;
            }

            try {
                const response = await fetch('api/strategy-config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reset_config',
                        strategy: currentStrategy
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showMessage('✅ Configuration reset to defaults!', 'success');
                    loadStrategyConfig(currentStrategy); // Reload
                } else {
                    showMessage('❌ Failed to reset configuration', 'error');
                }
            } catch (error) {
                showMessage('❌ Error resetting configuration: ' + error.message, 'error');
            }
        }

        // Export configuration to JSON
        async function exportConfiguration() {
            try {
                const response = await fetch(`api/strategy-config.php?action=export&strategy=${encodeURIComponent(currentStrategy)}`);
                const json = await response.text();
                
                // Create download
                const blob = new Blob([json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${currentStrategy.replace(/[^a-z0-9]/gi, '_')}_config.json`;
                a.click();
                
                showMessage('✅ Configuration exported successfully!', 'success');
            } catch (error) {
                showMessage('❌ Error exporting configuration: ' + error.message, 'error');
            }
        }

        // Show status message
        function showMessage(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = `status-message ${type}`;
            statusDiv.style.display = 'block';
            
            // Scroll to top to show message
            window.scrollTo({ top: 0, behavior: 'smooth' });
            
            setTimeout(() => {
                statusDiv.style.display = 'none';
                statusDiv.className = 'status-message';
            }, 5000);
        }
    </script>
</body>
</html>
