<?php

/**
 * Trading System Standalone Demo
 * Demonstrates performance analytics and backtesting capabilities
 * without requiring full strategy dependencies
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Services\Trading\StrategyPerformanceAnalyzer;
use App\Services\Trading\BacktestingFramework;

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    TRADING STRATEGY SYSTEM - PERFORMANCE ANALYTICS DEMO   ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ===================================================================
// DEMO 1: Performance Analysis with Multiple Strategies
// ===================================================================

echo str_repeat("=", 60) . "\n";
echo "DEMO 1: Strategy Performance Comparison\n";
echo str_repeat("=", 60) . "\n\n";

$analyzer = new StrategyPerformanceAnalyzer();

// Generate sample trades for demonstration
$sampleTrades = [
    // MomentumQuality Strategy - High Sharpe, Good Win Rate
    ['symbol' => 'AAPL', 'strategy' => 'MomentumQuality', 'entry_date' => '2024-01-15', 'entry_price' => 150, 'exit_date' => '2024-02-10', 'exit_price' => 165],
    ['symbol' => 'GOOGL', 'strategy' => 'MomentumQuality', 'entry_date' => '2024-02-20', 'entry_price' => 140, 'exit_date' => '2024-03-15', 'exit_price' => 152],
    ['symbol' => 'MSFT', 'strategy' => 'MomentumQuality', 'entry_date' => '2024-03-01', 'entry_price' => 380, 'exit_date' => '2024-04-01', 'exit_price' => 395],
    ['symbol' => 'NVDA', 'strategy' => 'MomentumQuality', 'entry_date' => '2024-04-15', 'entry_price' => 800, 'exit_date' => '2024-05-10', 'exit_price' => 780],
    
    // Contrarian Strategy - Lower Win Rate, Higher Average Win
    ['symbol' => 'TSLA', 'strategy' => 'Contrarian', 'entry_date' => '2024-01-20', 'entry_price' => 180, 'exit_date' => '2024-03-01', 'exit_price' => 210],
    ['symbol' => 'PYPL', 'strategy' => 'Contrarian', 'entry_date' => '2024-02-15', 'entry_price' => 55, 'exit_date' => '2024-03-20', 'exit_price' => 50],
    ['symbol' => 'SQ', 'strategy' => 'Contrarian', 'entry_date' => '2024-03-10', 'entry_price' => 70, 'exit_date' => '2024-04-25', 'exit_price' => 85],
    ['symbol' => 'HOOD', 'strategy' => 'Contrarian', 'entry_date' => '2024-04-01', 'entry_price' => 12, 'exit_date' => '2024-05-15', 'exit_price' => 11],
    ['symbol' => 'COIN', 'strategy' => 'Contrarian', 'entry_date' => '2024-05-01', 'entry_price' => 220, 'exit_date' => '2024-06-10', 'exit_price' => 200],
    
    // MeanReversion Strategy - Consistent Small Wins
    ['symbol' => 'JPM', 'strategy' => 'MeanReversion', 'entry_date' => '2024-01-10', 'entry_price' => 155, 'exit_date' => '2024-01-25', 'exit_price' => 160],
    ['symbol' => 'BAC', 'strategy' => 'MeanReversion', 'entry_date' => '2024-02-05', 'entry_price' => 32, 'exit_date' => '2024-02-20', 'exit_price' => 33.5],
    ['symbol' => 'WFC', 'strategy' => 'MeanReversion', 'entry_date' => '2024-03-15', 'entry_price' => 56, 'exit_date' => '2024-04-01', 'exit_price' => 57],
    ['symbol' => 'GS', 'strategy' => 'MeanReversion', 'entry_date' => '2024-04-10', 'entry_price' => 420, 'exit_date' => '2024-04-28', 'exit_price' => 415],
    ['symbol' => 'MS', 'strategy' => 'MeanReversion', 'entry_date' => '2024-05-05', 'entry_price' => 95, 'exit_date' => '2024-05-22', 'exit_price' => 98],
    ['symbol' => 'C', 'strategy' => 'MeanReversion', 'entry_date' => '2024-06-01', 'entry_price' => 62, 'exit_date' => '2024-06-18', 'exit_price' => 64],
];

$analyzer->loadTradeHistory($sampleTrades);

echo "Loaded " . count($sampleTrades) . " sample trades across 3 strategies\n\n";

$comparison = $analyzer->compareStrategies();

echo sprintf("%-20s %10s %12s %10s %12s %10s\n", 
    'Strategy', 'Trades', 'Win Rate', 'Avg Return', 'Sharpe', 'Expectancy'
);
echo str_repeat("-", 85) . "\n";

foreach ($comparison as $metrics) {
    echo sprintf("%-20s %10d %11.1f%% %10.1f%% %10.2f %10.2f%%\n",
        $metrics['strategy'],
        $metrics['total_trades'],
        $metrics['win_rate'] * 100,
        $metrics['average_return'] * 100,
        $metrics['sharpe_ratio'],
        $metrics['expectancy'] * 100
    );
}

echo "\n";

// ===================================================================
// DEMO 2: Strategy Correlation Analysis
// ===================================================================

echo str_repeat("=", 60) . "\n";
echo "DEMO 2: Strategy Correlation Matrix\n";
echo str_repeat("=", 60) . "\n\n";

$correlations = $analyzer->calculateStrategyCorrelations();

if (!empty($correlations)) {
    $strategies = array_keys($correlations);
    
    echo sprintf("%-20s", '');
    foreach ($strategies as $s) {
        echo sprintf("%15s", substr($s, 0, 14));
    }
    echo "\n" . str_repeat("-", 20 + (15 * count($strategies))) . "\n";
    
    foreach ($strategies as $s1) {
        echo sprintf("%-20s", substr($s1, 0, 19));
        foreach ($strategies as $s2) {
            $corr = $correlations[$s1][$s2] ?? 0;
            echo sprintf("%15.3f", $corr);
        }
        echo "\n";
    }
    
    echo "\nInterpretation:\n";
    echo "  • Values near  1.0: Strategies move together (high correlation)\n";
    echo "  • Values near  0.0: Strategies are independent\n";
    echo "  • Values near -1.0: Strategies move opposite (negative correlation)\n\n";
}

// ===================================================================
// DEMO 3: Optimal Portfolio Combination
// ===================================================================

echo str_repeat("=", 60) . "\n";
echo "DEMO 3: Optimal Strategy Combination\n";
echo str_repeat("=", 60) . "\n\n";

$optimal = $analyzer->findOptimalCombination(3);

if (!empty($optimal)) {
    echo "RECOMMENDED PORTFOLIO (Max 3 Strategies):\n\n";
    
    echo "Strategy Allocation:\n";
    arsort($optimal['weights']);
    foreach ($optimal['weights'] as $strategy => $weight) {
        $percentage = round($weight * 100, 1);
        $bar = str_repeat("█", (int)($percentage / 2));
        echo sprintf("  %-20s %6.1f%% %s\n", $strategy, $percentage, $bar);
    }
    
    echo "\nPortfolio Metrics:\n";
    echo "  Expected Sharpe Ratio:     {$optimal['expected_sharpe']}\n";
    echo "  Diversification Benefit:   {$optimal['diversification_benefit']}%\n\n";
    
    echo "Individual Strategy Performance:\n";
    foreach ($optimal['recommended_strategies'] as $i => $strategy) {
        echo sprintf("  %d. %-20s (Win Rate: %5.1f%%, Sharpe: %5.2f, Expectancy: %+5.2f%%)\n",
            $i + 1,
            $strategy['strategy'],
            $strategy['win_rate'] * 100,
            $strategy['sharpe_ratio'],
            $strategy['expectancy'] * 100
        );
    }
}

// ===================================================================
// DEMO 4: Performance Time Series
// ===================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "DEMO 4: Cumulative Performance Over Time\n";
echo str_repeat("=", 60) . "\n\n";

$timeSeries = $analyzer->getPerformanceTimeSeries('all');

if (!empty($timeSeries)) {
    echo "Date Range: {$timeSeries[0]['date']} to " . end($timeSeries)['date'] . "\n\n";
    
    echo sprintf("%-12s %-8s %12s %18s\n", 'Date', 'Symbol', 'Return', 'Cumulative Return');
    echo str_repeat("-", 60) . "\n";
    
    // Show first 5 and last 5 trades
    $toShow = array_merge(
        array_slice($timeSeries, 0, 5),
        count($timeSeries) > 10 ? [['date' => '...', 'symbol' => '...', 'return' => 0, 'cumulative_return' => 0]] : [],
        array_slice($timeSeries, -5)
    );
    
    foreach ($toShow as $point) {
        if ($point['date'] === '...') {
            echo "...\n";
            continue;
        }
        echo sprintf("%-12s %-8s %11.2f%% %17.2f%%\n",
            $point['date'],
            $point['symbol'],
            $point['return'] * 100,
            $point['cumulative_return'] * 100
        );
    }
    
    $finalReturn = end($timeSeries)['cumulative_return'];
    echo "\nFinal Portfolio Return: " . round($finalReturn * 100, 2) . "%\n";
}

// ===================================================================
// DEMO 5: Monte Carlo Risk Assessment
// ===================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "DEMO 5: Monte Carlo Simulation (Risk Assessment)\n";
echo str_repeat("=", 60) . "\n\n";

$framework = new BacktestingFramework(100000, 0.001, 0.0005);

echo "Running 1,000 simulations (50 trades each)...\n\n";

$allTrades = $analyzer->getTradeHistory();
$mcResults = $framework->monteCarloSimulation($allTrades, 1000, 50);

echo "RETURN DISTRIBUTION:\n\n";
echo sprintf("  95th Percentile (Best Case):    %+7.2f%%\n", $mcResults['percentile_95'] * 100);
echo sprintf("  75th Percentile:                %+7.2f%%\n", $mcResults['percentile_75'] * 100);
echo sprintf("  Median (50th):                  %+7.2f%%\n", $mcResults['median_return'] * 100);
echo sprintf("  Mean:                           %+7.2f%%\n", $mcResults['mean_return'] * 100);
echo sprintf("  25th Percentile:                %+7.2f%%\n", $mcResults['percentile_25'] * 100);
echo sprintf("  5th Percentile (Worst Case):    %+7.2f%%\n\n", $mcResults['percentile_5'] * 100);

echo "RISK METRICS:\n";
echo sprintf("  Probability of Profit:          %6.1f%%\n", $mcResults['probability_profit'] * 100);
echo sprintf("  Best Possible Outcome:          %+7.2f%%\n", $mcResults['best_case'] * 100);
echo sprintf("  Worst Possible Outcome:         %+7.2f%%\n\n", $mcResults['worst_case'] * 100);

// Distribution histogram
echo "Return Distribution Histogram:\n";
$bins = [
    ['min' => -1.0, 'max' => -0.3, 'label' => '< -30%'],
    ['min' => -0.3, 'max' => -0.1, 'label' => '-30% to -10%'],
    ['min' => -0.1, 'max' => 0.0, 'label' => '-10% to 0%'],
    ['min' => 0.0, 'max' => 0.1, 'label' => '0% to 10%'],
    ['min' => 0.1, 'max' => 0.3, 'label' => '10% to 30%'],
    ['min' => 0.3, 'max' => 2.0, 'label' => '> 30%']
];

foreach ($bins as $bin) {
    $count = count(array_filter($mcResults['distribution'], 
        fn($r) => $r >= $bin['min'] && $r < $bin['max']
    ));
    $percentage = ($count / $mcResults['simulations']) * 100;
    $bar = str_repeat("█", max(1, (int)($percentage / 2)));
    echo sprintf("  %-15s %5.1f%% %s\n", $bin['label'], $percentage, $bar);
}

// ===================================================================
// SUMMARY
// ===================================================================

echo "\n" . str_repeat("=", 60) . "\n";
echo "DEMO SUMMARY\n";
echo str_repeat("=", 60) . "\n\n";

echo "✓ Analyzed performance of " . count($comparison) . " trading strategies\n";
echo "✓ Calculated correlation matrix for diversification analysis\n";
echo "✓ Identified optimal portfolio combination\n";
echo "✓ Generated cumulative performance time series\n";
echo "✓ Ran Monte Carlo simulation for risk assessment\n\n";

echo "Key Insights:\n";

// Best strategy
$bestStrategy = $comparison[0];
echo "  • Best Strategy: {$bestStrategy['strategy']} ";
echo "(Sharpe: {$bestStrategy['sharpe_ratio']}, Win Rate: " . round($bestStrategy['win_rate'] * 100, 1) . "%)\n";

// Portfolio recommendation
if (!empty($optimal)) {
    $topWeight = array_keys($optimal['weights'])[0];
    echo "  • Recommended Focus: {$topWeight} (" . round($optimal['weights'][$topWeight] * 100, 1) . "% allocation)\n";
}

// Risk assessment
echo "  • Probability of Profit: " . round($mcResults['probability_profit'] * 100, 1) . "%\n";
echo "  • Expected Return (Median): " . round($mcResults['median_return'] * 100, 2) . "%\n\n";

echo "System Features Demonstrated:\n";
echo "  ✅ Multi-strategy performance comparison\n";
echo "  ✅ Sharpe ratio calculation (risk-adjusted returns)\n";
echo "  ✅ Win rate and expectancy analysis\n";
echo "  ✅ Strategy correlation analysis\n";
echo "  ✅ Optimal portfolio weighting\n";
echo "  ✅ Monte Carlo risk simulation\n";
echo "  ✅ Cumulative return tracking\n\n";

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║              Demo Completed Successfully! ✓               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";
