<?php
/**
 * Analytics Dashboard - Simple Version (Debug)
 */

require_once __DIR__ . '/UserAuthDAO.php';

$auth = new UserAuthDAO();
$auth->requireLogin();
$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Enhanced Trading System</title>
    <?php require_once 'UiStyles.php'; ?>
    <?php UiStyles::render(); ?>
</head>
<body>

<?php
// Use centralized NavigationService
require_once 'NavigationService.php';
$navigationService = new NavigationService();
echo $navigationService->renderNavigationHeader('Analytics Dashboard - Enhanced Trading System', 'analytics');
?>

<div class="container">
    <?php require_once 'QuickActions.php'; ?>
    <?php QuickActions::render(); ?>
    
    <div class="header">
        <h1>Analytics Dashboard</h1>
        <p>Portfolio performance analysis and trading metrics</p>
    </div>
        
    <div class="card success">
        <h3>📊 Analytics Architecture</h3>
        <p>The enhanced database now includes dedicated analytics tables:</p>
        <ul>
            <li><strong>portfolio_performance:</strong> Daily performance metrics by market cap</li>
            <li><strong>llm_interactions:</strong> AI/LLM decision tracking</li>
            <li><strong>trading_sessions:</strong> Session-based performance analysis</li>
        </ul>
    </div>
    
    <div class="grid">
        <div class="card info">
            <h4>Performance Metrics</h4>
            <p>Available via Python analytics:</p>
            <ul>
                <li>Total Return</li>
                <li>Daily Returns</li>
                <li>Volatility</li>
                <li>Sharpe Ratio</li>
                <li>Max Drawdown</li>
            </ul>
        </div>
        
        <div class="card info">
            <h4>Risk Analytics</h4>
            <p>Enhanced risk management:</p>
            <ul>
                <li>Position Sizing</li>
                <li>Stop Loss Tracking</li>
                <li>Portfolio Concentration</li>
                <li>Risk Score by Ticker</li>
            </ul>
        </div>
        
        <div class="card info">
            <h4>LLM Analytics</h4>
            <p>AI decision tracking:</p>
            <ul>
                <li>Prompt Types</li>
                <li>Response Times</li>
                <li>Token Usage</li>
                <li>Cost Analysis</li>
            </ul>
        </div>
    </div>
    
    <div class="card warning">
        <h3>🐍 Python Analytics Access</h3>
        <p>Due to PHP MySQL limitations, advanced analytics are available via Python:</p>
        
        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; font-family: monospace;">
            <h4>Performance Analysis:</h4>
            <p># Run portfolio performance analysis<br>
            python -c "from enhanced_automation import *; engine = EnhancedAutomationEngine('micro'); print('Analytics available')"</p>
            
            <h4>Generate Reports:</h4>
            <p># Create performance charts and reports<br>
            python Generate_Graph.py</p>
            
            <h4>Database Analytics:</h4>
            <p># Query performance data directly<br>
            python -c "import mysql.connector; print('Direct database analysis available')"</p>
        </div>
    </div>
    
    <div class="card">
        <h3>📈 Chart Generation</h3>
        <p>Visual analytics are generated using Python matplotlib/plotly:</p>
        <ul>
            <li><strong>Performance Charts:</strong> Scripts/Generate_Graph.py</li>
            <li><strong>Risk Analysis:</strong> Enhanced automation reports</li>
            <li><strong>Trade Analysis:</strong> CSV and database querying</li>
        </ul>
    </div>
    
    <div class="card">
        <h3>Database Tables for Analytics</h3>
        <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 0.9em;">
            <p><strong>stock_market_2 (Master Database):</strong></p>
            <ul>
                <li>portfolio_performance - Daily metrics by portfolio type</li>
                <li>llm_interactions - AI decision tracking</li>
                <li>trading_sessions - Session performance</li>
                <li>trades_enhanced - Multi-market cap trade data</li>
            </ul>
        </div>
    </div>
    
    <?php require_once 'QuickActions.php'; ?>
    <?php QuickActions::render(); ?>
</div>
</body>
</html>
