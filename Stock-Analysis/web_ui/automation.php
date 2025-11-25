<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Control - Enhanced Trading System</title>
    <?php require_once 'UiStyles.php'; ?>
    <?php UiStyles::render(); ?>
</head>
<body>
    <div class="container">
        <?php require_once 'QuickActions.php'; ?>
        <?php QuickActions::render(); ?>
        <div class="header">
            <h1>🤖 Automation Control Center</h1>
            <p>Enhanced trading automation with multi-market cap support</p>
        </div>
        
        <div class="card success">
            <h3>✅ System Status</h3>
            <p>Enhanced automation engine is fully operational with the following features:</p>
            <ul>
                <li><strong>Multi-Market Cap Support:</strong> Micro, Small, Mid-cap trading</li>
                <li><strong>Dual Storage:</strong> CSV backup + MySQL database</li>
                <li><strong>LLM Integration:</strong> AI-driven decision making</li>
                <li><strong>Session Management:</strong> Trading session tracking</li>
                <li><strong>Risk Management:</strong> Position sizing and stop losses</li>
            </ul>
        </div>
        
        <div class="grid">
            <div class="card info">
                <h4>🚀 Start Automation</h4>
                <p>Launch the enhanced automation engine:</p>
                <div class="code-block">
                    # Enhanced automation with database<br>
                    python enhanced_automation.py
                    <button class="btn" onclick="runPy('enhanced_automation.py', this)">Run</button><br><br>
                    # Original automation (CSV only)<br>
                    python simple_automation.py
                    <button class="btn" onclick="runPy('simple_automation.py', this)">Run</button>
                </div>
                <p><strong>Note:</strong> Enhanced version includes database logging and multi-market cap support.</p>
            </div>
            
            <div class="card warning">
                <h4>⚙️ Configuration</h4>
                <p>Available automation modes:</p>
                <ul>
                    <li><strong>Micro Cap:</strong> Original experiment format</li>
                    <li><strong>Small Cap:</strong> Enhanced small-cap trading</li>
                    <li><strong>Mid Cap:</strong> Mid-cap market automation</li>
                </ul>
                <p>Configure in <code>db_config_refactored.yml</code></p>
            </div>
            
            <div class="card info">
                <h4>📊 Monitoring</h4>
                <p>Real-time automation monitoring:</p>
                <ul>
                    <li>Portfolio performance tracking</li>
                    <li>Trade execution logging</li>
                    <li>LLM interaction analytics</li>
                    <li>Session-based reporting</li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <h3>🎛️ Control Commands</h3>
            <div class="grid">
                <div class="card info">
                    <h4>Market Cap Selection</h4>
                    <div class="code-block">
                        # Run micro-cap automation<br>
                        python enhanced_automation.py --market_cap micro
                        <button class="btn" onclick="runPy('enhanced_automation.py --market_cap micro', this)">Run</button><br><br>
                        # Run small-cap automation<br>
                        python enhanced_automation.py --market_cap small
                        <button class="btn" onclick="runPy('enhanced_automation.py --market_cap small', this)">Run</button><br><br>
                        # Run mid-cap automation<br>
                        python enhanced_automation.py --market_cap mid
                        <button class="btn" onclick="runPy('enhanced_automation.py --market_cap mid', this)">Run</button>
                    </div>
                </div>
                
                <div class="card warning">
                    <h4>Session Management</h4>
                    <div class="code-block">
                        # Start new trading session<br>
                        python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine('micro'); engine.start_session()"
                        <button class="btn" onclick="runPy('-c \"from enhanced_automation import *; engine = EnhancedAutomationEngine(\\'micro\\'); engine.start_session()\"', this)">Run</button><br><br>
                        # View active sessions<br>
                        python -c "from database_architect import *; arch = DatabaseArchitect(); arch.show_sessions()"
                        <button class="btn" onclick="runPy('-c \"from database_architect import *; arch = DatabaseArchitect(); arch.show_sessions()\"', this)">Run</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card danger">
            <h3>⚠️ Risk Controls</h3>
            <p>Built-in safety features:</p>
            <ul>
                <li><strong>Position Limits:</strong> Automatic position sizing based on portfolio value</li>
                <li><strong>Stop Losses:</strong> Configurable stop-loss percentages</li>
                <li><strong>Daily Limits:</strong> Maximum trades per day</li>
                <li><strong>Portfolio Limits:</strong> Maximum portfolio concentration</li>
            </ul>
            
            <div class="code-block">
                # Emergency stop all automation<br>
                python -c "import os; os.system('taskkill /f /im python.exe')"
                <button class="btn" onclick="runPy('-c \"import os; os.system(\\'taskkill /f /im python.exe\\')\"', this)">Run</button><br><br>
                # Check current positions<br>
                python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine('micro'); engine.show_positions()"
                <button class="btn" onclick="runPy('-c \"from enhanced_automation import *; engine = EnhancedAutomationEngine(\\'micro\\'); engine.show_positions()\"', this)">Run</button>
            </div>
        </div>
        
        <div class="card">
            <h3>📈 Performance Tracking</h3>
            <p>The automation system tracks comprehensive metrics:</p>
            <div class="grid">
                <div class="card info">
                    <h4>Trading Metrics</h4>
                    <ul>
                        <li>Win/Loss Ratios</li>
                        <li>Average Trade Duration</li>
                        <li>Profit/Loss per Trade</li>
                        <li>Market Cap Performance</li>
                    </ul>
                </div>
                
                <div class="card info">
                    <h4>LLM Analytics</h4>
                    <ul>
                        <li>Prompt Response Times</li>
                        <li>Token Usage Tracking</li>
                        <li>Decision Accuracy</li>
                        <li>Cost Analysis</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="card">
            <h3>🔧 Troubleshooting</h3>
            <div class="code-block">
                # Check system health<br>
                python -c "from database_architect import *; arch = DatabaseArchitect(); arch.test_connections()"
                <button class="btn" onclick="runPy('-c \"from database_architect import *; arch = DatabaseArchitect(); arch.test_connections()\"', this)">Run</button><br><br>
                # Validate configuration<br>
                python -c "import yaml; print(yaml.safe_load(open('db_config_refactored.yml')))"
                <button class="btn" onclick="runPy('-c \"import yaml; print(yaml.safe_load(open(\\'db_config_refactored.yml\\')))\"', this)">Run</button><br><br>
                # View recent trades<br>
                python -c "import pandas as pd; print(pd.read_csv('chatgpt_trade_log.csv').tail())"
                <button class="btn" onclick="runPy('-c \"import pandas as pd; print(pd.read_csv(\\'chatgpt_trade_log.csv\\').tail())\"', this)">Run</button>
            </div>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <a href="index.php" class="btn">Dashboard</a>
            <a href="portfolios.php" class="btn">View Portfolios</a>
            <a href="trades.php" class="btn">Trade History</a>
            <a href="analytics.php" class="btn">Analytics</a>
            <a href="database.php" class="btn">Database Manager</a>
        </div>
    <div id="py-output" class="card" style="display:none;"></div>
    <script>
    function runPy(cmd, btn) {
        var outputDiv = document.getElementById('py-output');
        outputDiv.style.display = 'block';
        outputDiv.innerHTML = '<em>Running: ' + cmd + ' ...</em>';
        btn.disabled = true;
        fetch('run_python_command.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'command=' + encodeURIComponent(cmd)
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            if (data.output !== undefined) {
                outputDiv.innerHTML = '<pre>' + data.output + '</pre>';
            } else {
                outputDiv.innerHTML = '<span style="color:red;">' + (data.error || 'Unknown error') + '</span>';
            }
        })
        .catch(e => {
            btn.disabled = false;
            outputDiv.innerHTML = '<span style="color:red;">Error: ' + e + '</span>';
        });
    }
    </script>
    </div>
</body>
</html>
