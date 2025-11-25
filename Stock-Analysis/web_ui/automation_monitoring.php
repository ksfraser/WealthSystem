<?php
require_once 'UiStyles.php';
require_once 'QuickActions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Automation Monitoring</title>
    <?php UiStyles::render(); ?>
</head>
<body>
<div class="container">
    <?php QuickActions::render(); ?>
    <div class="header">
        <h1>Automation Monitoring</h1>
        <p>Real-time and historical monitoring of automation activity</p>
    </div>
    <div class="card info">
        <h3>Portfolio Tracking</h3>
        <p>Displays current and historical portfolio values, allocations, and performance by market cap.</p>
        <ul>
            <li><a href="#" onclick="loadSection('portfolio');return false;">View Portfolio Performance</a></li>
        </ul>
        <div id="portfolio-section" style="display:none;"></div>
    </div>
    <div class="card info">
        <h3>Trade Execution Logs</h3>
        <p>Shows all trades executed by the automation engine, including timestamps, tickers, and results.</p>
        <ul>
            <li><a href="#" onclick="loadSection('trades');return false;">View Trade Log</a></li>
        </ul>
        <div id="trades-section" style="display:none;"></div>
    </div>
    <div class="card info">
        <h3>LLM Interaction Analytics</h3>
        <p>Tracks AI/LLM prompt/response activity, token usage, and decision logs.</p>
        <ul>
            <li><a href="#" onclick="loadSection('llm');return false;">View LLM Analytics</a></li>
        </ul>
        <div id="llm-section" style="display:none;"></div>
    </div>
    <div class="card info">
        <h3>Session-Based Reporting</h3>
        <p>Summarizes trading sessions, including start/end times, P&L, and session notes.</p>
        <ul>
            <li><a href="#" onclick="loadSection('sessions');return false;">View Session Reports</a></li>
        </ul>
        <div id="sessions-section" style="display:none;"></div>
    </div>
</div>
<script>
function loadSection(section) {
    // For demo, just show a placeholder. In production, use AJAX to load real data.
    document.getElementById('portfolio-section').style.display = 'none';
    document.getElementById('trades-section').style.display = 'none';
    document.getElementById('llm-section').style.display = 'none';
    document.getElementById('sessions-section').style.display = 'none';
    var sectionDiv = document.getElementById(section+'-section');
    sectionDiv.style.display = 'block';
    sectionDiv.innerHTML = '<em>Loading ' + section.replace(/^(.)/, function(a){return a.toUpperCase();}) + ' data...</em>';
    // TODO: AJAX call to load real data
}
</script>
</body>
</html>
