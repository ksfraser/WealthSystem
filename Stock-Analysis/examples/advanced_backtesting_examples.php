<?php

/**
 * Advanced Backtesting Examples
 * 
 * Demonstrates Sprint 20 capabilities:
 * - Position sizing strategies
 * - Short selling
 * - Multi-symbol portfolio backtesting
 * - Margin and leverage management
 */

require_once __DIR__ . '/../vendor/autoload.php';

use WealthSystem\Backtesting\PositionSizer;
use WealthSystem\Backtesting\ShortSellingBacktestEngine;
use WealthSystem\Backtesting\MultiSymbolBacktestEngine;

// ============================================================================
// Example 1: Position Sizing Comparison
// ============================================================================
echo "=== Example 1: Position Sizing Methods ===\n\n";

$sizer = new PositionSizer();
$portfolioValue = 100000;
$currentPrice = 150;

echo "Portfolio: $100,000 | Stock Price: $150\n\n";

// Fixed Dollar
$result = $sizer->fixedDollar($portfolioValue, 10000, $currentPrice);
echo "Fixed Dollar ($10,000):\n";
echo "  Shares: {$result['shares']} | Value: \${$result['value']} | Percent: " . 
     round($result['percent'] * 100, 2) . "%\n\n";

// Fixed Percent
$result = $sizer->fixedPercent($portfolioValue, 0.15, $currentPrice);
echo "Fixed Percent (15%):\n";
echo "  Shares: {$result['shares']} | Value: \${$result['value']} | Percent: " . 
     round($result['percent'] * 100, 2) . "%\n\n";

// Kelly Criterion
$result = $sizer->kellyCriterion($portfolioValue, 0.6, 1.2, 0.9, $currentPrice, 0.5);
echo "Kelly Criterion (60% win rate, 20% avg win, 10% avg loss):\n";
echo "  Shares: {$result['shares']} | Value: \${$result['value']} | Percent: " . 
     round($result['percent'] * 100, 2) . "%\n";
echo "  Kelly %: " . round($result['kelly_percent'] * 100, 2) . "% | Adjusted: " . 
     round($result['adjusted_percent'] * 100, 2) . "%\n\n";

// Volatility-Based (ATR)
$result = $sizer->volatilityBased($portfolioValue, 0.01, 3.0, $currentPrice, 2.0);
echo "Volatility-Based (1% risk, $3 ATR):\n";
echo "  Shares: {$result['shares']} | Value: \${$result['value']} | Percent: " . 
     round($result['percent'] * 100, 2) . "%\n";
echo "  Stop Loss: \${$result['stop_loss_price']} | Distance: \${$result['stop_loss_distance']}\n\n";

// Risk Parity
$assets = [
    ['symbol' => 'AAPL', 'volatility' => 0.02, 'price' => 150],
    ['symbol' => 'MSFT', 'volatility' => 0.015, 'price' => 300],
    ['symbol' => 'BND', 'volatility' => 0.005, 'price' => 80]
];
$result = $sizer->riskParity($portfolioValue, $assets);
echo "Risk Parity (3 assets with different volatilities):\n";
foreach ($result['positions'] as $symbol => $position) {
    echo "  $symbol: {$position['shares']} shares | Weight: " . 
         round($position['target_weight'] * 100, 2) . "% | Vol: " . 
         round($position['volatility'] * 100, 2) . "%\n";
}
echo "\n";

// ============================================================================
// Example 2: Short Selling with Margin
// ============================================================================
echo "=== Example 2: Short Selling ===\n\n";

$engine = new ShortSellingBacktestEngine([
    'initial_capital' => 100000,
    'margin_requirement' => 1.5,
    'short_interest_rate' => 0.03
]);

echo "Initial capital: $100,000\n\n";

// Enter short position
echo "Day 1: Short 100 shares of AAPL at $150\n";
$result = $engine->enterShortPosition('AAPL', 100, 150.0, '2025-01-01');
echo "  Margin required: \${$result['margin_required']}\n";
echo "  Proceeds: \${$result['proceeds']}\n\n";

$state = $engine->getState();
echo "Portfolio state:\n";
echo "  Cash: \${$state['cash']}\n";
echo "  Margin balance: \${$state['margin_balance']}\n";
echo "  Short positions: " . count($state['short_positions']) . "\n\n";

// Check margin after price increases
echo "Day 10: Price rises to $165 - Check margin\n";
$marginCalls = $engine->checkMarginRequirements(['AAPL' => 165.0], '2025-01-10');
if (!empty($marginCalls)) {
    echo "  ⚠️ MARGIN CALL!\n";
    echo "  Current margin ratio: " . round($marginCalls[0]['current_margin_ratio'], 3) . "\n";
    echo "  Unrealized loss: \${$marginCalls[0]['unrealized_loss']}\n\n";
}

// Cover short at a profit
echo "Day 30: Cover short at $140 (profit)\n";
$result = $engine->exitShortPosition('AAPL', null, 140.0, '2025-01-30');
echo "  Profit: \${$result['profit']} (" . round($result['profit_percent'] * 100, 2) . "%)\n";
echo "  Short interest paid: \${$result['short_interest']}\n";
echo "  Days held: {$result['days_held']}\n\n";

$value = $engine->calculatePortfolioValue([]);
echo "Final portfolio value: \${$value['net_worth']}\n";
echo "Total return: $" . ($value['net_worth'] - 100000) . "\n\n";

// ============================================================================
// Example 3: Multi-Symbol Portfolio Backtest
// ============================================================================
echo "=== Example 3: Multi-Symbol Portfolio Backtest ===\n\n";

$portfolioEngine = new MultiSymbolBacktestEngine([
    'initial_capital' => 100000,
    'max_position_size' => 0.15,
    'max_sector_exposure' => 0.40,
    'max_positions' => 5,
    'correlation_limit' => 0.7
]);

// Create sample market data
$dates = ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04', '2025-01-05'];
$marketData = [
    'AAPL' => array_map(fn($i) => [
        'date' => $dates[$i],
        'open' => 150 + $i * 2,
        'high' => 152 + $i * 2,
        'low' => 149 + $i * 2,
        'close' => 151 + $i * 2,
        'volume' => 1000000
    ], array_keys($dates)),
    'MSFT' => array_map(fn($i) => [
        'date' => $dates[$i],
        'open' => 300 + $i * 3,
        'high' => 302 + $i * 3,
        'low' => 299 + $i * 3,
        'close' => 301 + $i * 3,
        'volume' => 800000
    ], array_keys($dates))
];

// Register simple momentum strategies
$momentumStrategy = function($symbol, $historicalData, $currentPrice) {
    if (count($historicalData) < 2) {
        return ['action' => 'HOLD', 'confidence' => 0.5];
    }
    
    $prevClose = $historicalData[count($historicalData) - 2]['close'];
    $return = ($currentPrice - $prevClose) / $prevClose;
    
    if ($return > 0.02) {
        return ['action' => 'BUY', 'confidence' => 0.7];
    } elseif ($return < -0.02) {
        return ['action' => 'SELL', 'confidence' => 0.7];
    }
    
    return ['action' => 'HOLD', 'confidence' => 0.5];
};

$portfolioEngine->registerStrategy('AAPL', $momentumStrategy, [
    'sector' => 'Technology',
    'industry' => 'Consumer Electronics'
]);

$portfolioEngine->registerStrategy('MSFT', $momentumStrategy, [
    'sector' => 'Technology',
    'industry' => 'Software'
]);

echo "Running multi-symbol backtest...\n";
echo "Symbols: AAPL, MSFT\n";
echo "Strategy: Simple momentum (>2% move triggers signal)\n";
echo "Period: {$dates[0]} to {$dates[count($dates)-1]}\n\n";

$result = $portfolioEngine->runBacktest($marketData, $dates[0], $dates[count($dates)-1]);

echo "Results:\n";
echo "  Initial capital: \${$result['initial_capital']}\n";
echo "  Final value: \${$result['final_value']}\n";
echo "  Total return: \$" . ($result['final_value'] - $result['initial_capital']) . " (" . 
     round($result['metrics']['total_return_pct'], 2) . "%)\n\n";

echo "Signals:\n";
echo "  Generated: {$result['signals_stats']['generated']}\n";
echo "  Executed: {$result['signals_stats']['executed']}\n";
echo "  Rejected: {$result['signals_stats']['rejected']}\n";

if (!empty($result['signals_stats']['rejection_reasons'])) {
    echo "  Rejection reasons:\n";
    foreach ($result['signals_stats']['rejection_reasons'] as $reason => $count) {
        echo "    - $reason: $count\n";
    }
}
echo "\n";

echo "Portfolio Metrics:\n";
$metrics = $result['metrics'];
echo "  Sharpe Ratio: " . round($metrics['sharpe_ratio'], 3) . "\n";
echo "  Sortino Ratio: " . round($metrics['sortino_ratio'], 3) . "\n";
echo "  Max Drawdown: " . round($metrics['max_drawdown'] * 100, 2) . "%\n";
echo "  Win Rate: " . round($metrics['win_rate'] * 100, 2) . "%\n";
echo "  Volatility: " . round($metrics['annualized_volatility'] * 100, 2) . "%\n\n";

// ============================================================================
// Example 4: Margin and Leverage Management
// ============================================================================
echo "=== Example 4: Margin and Leverage ===\n\n";

$sizer = new PositionSizer();

echo "Scenario: Trade with leverage\n";
echo "Portfolio: $100,000 | Available cash: $50,000 | Stock price: $100\n\n";

// Calculate max position with 50% margin
$result = $sizer->maxPositionWithMargin(
    portfolioValue: 100000,
    availableCash: 50000,
    marginRequirement: 0.5,
    maxLeverage: 2.0,
    currentPrice: 100
);

echo "Max position with 50% margin requirement:\n";
echo "  Shares: {$result['shares']}\n";
echo "  Position value: \${$result['value']}\n";
echo "  Margin used: \${$result['margin_used']}\n";
echo "  Leverage: {$result['leverage']}x\n";
echo "  Buying power used: \${$result['buying_power_used']}\n\n";

// Calculate with higher leverage limit
$result2 = $sizer->maxPositionWithMargin(
    portfolioValue: 100000,
    availableCash: 50000,
    marginRequirement: 0.3,
    maxLeverage: 4.0,
    currentPrice: 100
);

echo "Max position with 30% margin, 4x leverage limit:\n";
echo "  Shares: {$result2['shares']}\n";
echo "  Position value: \${$result2['value']}\n";
echo "  Margin used: \${$result2['margin_used']}\n";
echo "  Leverage: {$result2['leverage']}x\n\n";

echo "=== Examples Complete ===\n";
