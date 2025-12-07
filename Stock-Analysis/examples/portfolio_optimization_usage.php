<?php

/**
 * Portfolio Optimization Usage Examples
 * 
 * Demonstrates Modern Portfolio Theory (MPT) implementation for optimal
 * asset allocation and risk-adjusted returns.
 * 
 * Examples:
 * 1. Maximize Sharpe Ratio - Best risk-adjusted returns
 * 2. Minimize Variance - Lowest risk portfolio
 * 3. Target Return - Specific return with minimum risk
 * 4. Efficient Frontier - Optimal portfolio curve
 * 5. Risk Analysis - Comprehensive risk metrics
 * 6. Full AI Integration - Recommendations with optimization
 */

require_once __DIR__ . '/../vendor/autoload.php';

use WealthSystem\StockAnalysis\Portfolio\ModernPortfolioTheoryOptimizer;
use WealthSystem\StockAnalysis\Portfolio\PortfolioRiskAnalyzer;
use WealthSystem\StockAnalysis\AI\LLMTradingAssistantWithOptimization;
use WealthSystem\StockAnalysis\AI\AnthropicProvider;
use WealthSystem\StockAnalysis\AI\AIClient;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Setup
$logger = new Logger('portfolio');
$logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));

echo "=== Portfolio Optimization Examples ===\n\n";

// Example tickers
$tickers = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA'];

// ============================================================================
// EXAMPLE 1: Maximize Sharpe Ratio (Best Risk-Adjusted Returns)
// ============================================================================
echo "--- Example 1: Maximize Sharpe Ratio ---\n";

$optimizer = new ModernPortfolioTheoryOptimizer($logger);

$result = $optimizer->maximizeSharpeRatio($tickers, [
    'lookback_days' => 252,        // 1 year of trading days
    'risk_free_rate' => 0.02,      // 2% risk-free rate
]);

if ($result->isValid()) {
    echo "Optimization successful!\n";
    echo "Method: {$result->method}\n";
    echo "Expected Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
    echo "Volatility (Risk): " . number_format($result->volatility * 100, 2) . "%\n";
    echo "Sharpe Ratio: " . number_format($result->sharpeRatio, 2) . "\n\n";
    
    echo "Optimal Weights:\n";
    foreach ($result->getSortedTickers() as $ticker) {
        $weight = $result->getWeight($ticker);
        if ($weight >= 0.01) { // Show weights >= 1%
            echo "  {$ticker}: " . number_format($weight * 100, 1) . "%\n";
        }
    }
    
    echo "\nFor a $100,000 portfolio:\n";
    $allocation = $result->getAllocation(100000);
    foreach ($result->getSortedTickers() as $ticker) {
        if ($allocation[$ticker] >= 1000) { // Show allocations >= $1,000
            echo "  {$ticker}: $" . number_format($allocation[$ticker], 2) . "\n";
        }
    }
} else {
    echo "Optimization failed: {$result->error}\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 2: Minimize Variance (Lowest Risk Portfolio)
// ============================================================================
echo "--- Example 2: Minimize Variance ---\n";

$result = $optimizer->minimizeVariance($tickers, [
    'risk_free_rate' => 0.02,
]);

if ($result->isValid()) {
    echo "Minimum variance portfolio found!\n";
    echo "Expected Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
    echo "Volatility: " . number_format($result->volatility * 100, 2) . "% (LOWEST RISK)\n";
    echo "Sharpe Ratio: " . number_format($result->sharpeRatio, 2) . "\n\n";
    
    echo "Conservative Allocation:\n";
    foreach ($result->getSortedTickers() as $ticker) {
        $weight = $result->getWeight($ticker);
        if ($weight >= 0.01) {
            echo "  {$ticker}: " . number_format($weight * 100, 1) . "%\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 3: Target Return (Specific Return with Minimum Risk)
// ============================================================================
echo "--- Example 3: Target Return ---\n";

$targetReturn = 0.12; // 12% annual return
$result = $optimizer->targetReturn($tickers, $targetReturn, [
    'risk_free_rate' => 0.02,
]);

if ($result->isValid()) {
    echo "Target return portfolio found!\n";
    echo "Target Return: " . number_format($targetReturn * 100, 2) . "%\n";
    echo "Actual Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
    echo "Volatility: " . number_format($result->volatility * 100, 2) . "% (MINIMIZED FOR TARGET)\n";
    echo "Sharpe Ratio: " . number_format($result->sharpeRatio, 2) . "\n\n";
    
    echo "Weights to Achieve {$targetReturn}% Return:\n";
    foreach ($result->getSortedTickers() as $ticker) {
        $weight = $result->getWeight($ticker);
        if ($weight >= 0.01) {
            echo "  {$ticker}: " . number_format($weight * 100, 1) . "%\n";
        }
    }
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 4: Efficient Frontier (Optimal Portfolio Curve)
// ============================================================================
echo "--- Example 4: Efficient Frontier ---\n";

$frontierPoints = $optimizer->calculateEfficientFrontier($tickers, 10, [
    'risk_free_rate' => 0.02,
]);

echo "Efficient Frontier (" . count($frontierPoints) . " points):\n";
echo "Risk (σ)  | Return | Sharpe | Sample Weight\n";
echo str_repeat("-", 60) . "\n";

foreach ($frontierPoints as $point) {
    // Find ticker with highest weight for display
    $maxWeight = 0;
    $maxTicker = '';
    foreach ($point->weights as $ticker => $weight) {
        if ($weight > $maxWeight) {
            $maxWeight = $weight;
            $maxTicker = $ticker;
        }
    }
    
    printf(
        "%5.1f%% | %5.1f%% | %5.2f | %s: %.1f%%\n",
        $point->volatility * 100,
        $point->expectedReturn * 100,
        $point->sharpeRatio,
        $maxTicker,
        $maxWeight * 100
    );
}

echo "\nInterpretation:\n";
echo "- Lower risk portfolios: Conservative, lower returns\n";
echo "- Higher risk portfolios: Aggressive, higher returns\n";
echo "- ALL points are optimal (best return for given risk)\n";
echo "- Choose based on risk tolerance\n";

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 5: Risk Analysis (Comprehensive Metrics)
// ============================================================================
echo "--- Example 5: Risk Analysis ---\n";

$riskAnalyzer = new PortfolioRiskAnalyzer($logger);

// Use optimal Sharpe portfolio from Example 1
$result = $optimizer->maximizeSharpeRatio($tickers);

if ($result->isValid()) {
    // Generate sample returns for risk analysis
    // In production, use real historical price data
    $returns = [];
    foreach ($tickers as $ticker) {
        $returns[$ticker] = array_map(
            fn() => (rand(-300, 300) / 10000), // -3% to +3% daily
            range(1, 252) // 1 year of trading days
        );
    }
    
    $riskMetrics = $riskAnalyzer->analyzePortfolio(
        $result->weights,
        $returns,
        ['risk_free_rate' => 0.02]
    );
    
    echo $riskAnalyzer->formatRiskMetrics($riskMetrics);
    
    echo "\nMetric Interpretations:\n";
    echo "- Expected Return: Average annual return\n";
    echo "- Volatility: Risk level (higher = more volatile)\n";
    echo "- Sharpe Ratio: Risk-adjusted return (higher = better)\n";
    echo "- Sortino Ratio: Downside risk only (higher = better)\n";
    echo "- Max Drawdown: Largest peak-to-trough loss\n";
    echo "- VaR 95%: Maximum loss at 95% confidence\n";
    echo "- VaR 99%: Maximum loss at 99% confidence\n";
    echo "- Beta: Market correlation (1.0 = market, <1 = less volatile)\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

// ============================================================================
// EXAMPLE 6: Full AI Integration (Recommendations with Optimization)
// ============================================================================
echo "--- Example 6: Full AI Integration ---\n";

// Check if API key is available
$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) {
    echo "Skipping AI integration example (ANTHROPIC_API_KEY not set)\n";
    echo "Set environment variable to test:\n";
    echo "  \$env:ANTHROPIC_API_KEY = \"your-key\"\n";
} else {
    echo "Creating AI assistant with full integration...\n";
    
    $provider = new AnthropicProvider($apiKey, 'claude-sonnet-4-20250514');
    $aiClient = new AIClient($provider, $logger);
    
    $assistant = new LLMTradingAssistantWithOptimization(
        $aiClient,
        null, // Add FundamentalDataService if available
        null, // Add NewsSentimentService if available
        $optimizer,
        $logger
    );
    
    // Sample portfolio
    $holdings = [
        ['ticker' => 'AAPL', 'quantity' => 50, 'cost_basis' => 150.00],
        ['ticker' => 'MSFT', 'quantity' => 30, 'cost_basis' => 300.00],
    ];
    
    $recommendations = $assistant->getRecommendations(
        $holdings,
        50000, // $50k cash
        100000, // $100k total equity
        [
            'use_fundamentals' => false,
            'use_sentiment' => false,
            'use_optimization' => true,
            'optimization_method' => 'sharpe',
            'risk_tolerance' => 'moderate',
            'watchlist' => ['GOOGL', 'AMZN', 'NVDA'],
        ]
    );
    
    echo "\nAI Recommendations with Optimization:\n";
    echo $recommendations->reasoning . "\n\n";
    
    foreach ($recommendations->actions as $action) {
        echo "Action: {$action->action}\n";
        echo "Ticker: {$action->ticker}\n";
        if ($action->quantity !== null) {
            echo "Quantity: {$action->quantity}\n";
        }
        echo "Reasoning: {$action->reasoning}\n\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "Examples complete!\n";
echo "\nNext Steps:\n";
echo "1. Replace simulated returns with real Alpha Vantage data\n";
echo "2. Run optimizer on your actual portfolio\n";
echo "3. Compare current allocation to optimal weights\n";
echo "4. Execute rebalancing trades to improve efficiency\n";
echo "5. Monitor risk metrics over time\n";
