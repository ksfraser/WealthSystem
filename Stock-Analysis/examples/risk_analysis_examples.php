<?php

/**
 * Risk Analysis Examples
 * 
 * Demonstrates comprehensive risk analysis capabilities including:
 * - Correlation matrix analysis
 * - Beta and alpha calculations
 * - Portfolio risk assessment
 * - Stress testing
 * - Risk contribution analysis
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Risk\CorrelationMatrix;
use App\Risk\BetaCalculator;
use App\Risk\RiskAnalyzer;

// =============================================================================
// Example 1: Basic Correlation Matrix Analysis
// =============================================================================
echo "=== Example 1: Correlation Matrix Analysis ===\n\n";

$correlationMatrix = new CorrelationMatrix();

// Sample returns for three assets
$returns = [
    'AAPL' => [0.02, 0.03, -0.01, 0.04, -0.02, 0.05, 0.01, -0.03, 0.04, 0.02],
    'MSFT' => [0.025, 0.035, -0.005, 0.035, -0.015, 0.045, 0.015, -0.025, 0.045, 0.025],
    'GOOGL' => [0.03, 0.04, -0.02, 0.05, -0.03, 0.06, 0.02, -0.04, 0.05, 0.03],
];

// Calculate Pearson correlation matrix
$matrix = $correlationMatrix->calculate($returns, 'pearson');

echo "Correlation Matrix (Pearson):\n";
echo str_pad('', 12);
foreach (array_keys($returns) as $symbol) {
    echo str_pad($symbol, 10);
}
echo "\n";

foreach ($matrix as $symbol1 => $correlations) {
    echo str_pad($symbol1, 12);
    foreach ($correlations as $corr) {
        echo str_pad(number_format($corr, 4), 10);
    }
    echo "\n";
}

// Find highly correlated pairs
$highlyCorrelated = $correlationMatrix->findCorrelatedPairs($returns, 0.8);
echo "\nHighly Correlated Pairs (>0.8):\n";
foreach ($highlyCorrelated as $pair) {
    echo sprintf(
        "  %s - %s: %.4f (%s)\n",
        $pair['asset1'],
        $pair['asset2'],
        $pair['correlation'],
        $pair['strength']
    );
}

// Calculate diversification score
$divScore = $correlationMatrix->diversificationScore($returns);
echo sprintf(
    "\nDiversification Score: %.2f (%s)\n",
    $divScore['score'],
    $divScore['interpretation']
);
echo "Interpretation: " . $divScore['description'] . "\n\n";

// =============================================================================
// Example 2: Beta and Alpha Calculations (CAPM Analysis)
// =============================================================================
echo "=== Example 2: Beta and Alpha Calculations ===\n\n";

$betaCalculator = new BetaCalculator();

// Sample asset and market returns
$assetReturns = [0.03, 0.05, -0.01, 0.06, -0.02, 0.07, 0.02, -0.03, 0.05, 0.04];
$marketReturns = [0.02, 0.03, -0.01, 0.04, -0.02, 0.05, 0.01, -0.03, 0.04, 0.02];
$riskFreeRate = 0.02; // 2% annual risk-free rate

// Calculate all CAPM metrics
$capmMetrics = $betaCalculator->calculate($assetReturns, $marketReturns, $riskFreeRate);

echo "CAPM Analysis Results:\n";
echo sprintf("  Beta: %.4f (%s)\n", $capmMetrics['beta'], $capmMetrics['beta_interpretation']);
echo sprintf("  Alpha: %.4f (%s)\n", $capmMetrics['alpha'], $capmMetrics['alpha_interpretation']);
echo sprintf("  R-squared: %.4f (%.1f%% variance explained)\n", $capmMetrics['r_squared'], $capmMetrics['r_squared'] * 100);
echo sprintf("  Asset Return: %.2f%%\n", $capmMetrics['asset_return'] * 100);
echo sprintf("  Market Return: %.2f%%\n", $capmMetrics['market_return'] * 100);
echo sprintf("  Asset Volatility: %.2f%%\n", $capmMetrics['asset_volatility'] * 100);
echo sprintf("  Market Volatility: %.2f%%\n", $capmMetrics['market_volatility'] * 100);

echo "\nRisk Decomposition:\n";
echo sprintf("  Systematic Risk: %.4f (%.1f%%)\n", 
    $capmMetrics['systematic_risk'], 
    $capmMetrics['systematic_percent']
);
echo sprintf("  Unsystematic Risk: %.4f (%.1f%%)\n", 
    $capmMetrics['unsystematic_risk'], 
    $capmMetrics['unsystematic_percent']
);

// Calculate Treynor ratio
$treynor = $betaCalculator->treynorRatio($assetReturns, $marketReturns, $riskFreeRate);
echo sprintf("\nTreynor Ratio: %.4f (risk-adjusted return per unit of systematic risk)\n\n", $treynor);

// =============================================================================
// Example 3: Market Timing Analysis
// =============================================================================
echo "=== Example 3: Market Timing Analysis ===\n\n";

// Create returns with market timing ability (higher beta in up markets)
$timedAssetReturns = [0.05, 0.08, -0.01, -0.02, 0.09, 0.07, -0.015, -0.01, 0.06, 0.08];
$timedMarketReturns = [0.03, 0.04, -0.02, -0.03, 0.05, 0.04, -0.025, -0.02, 0.04, 0.05];

$timingAnalysis = $betaCalculator->marketTiming($timedAssetReturns, $timedMarketReturns);

echo "Market Timing Analysis:\n";
echo sprintf("  Beta (Up Markets): %.4f\n", $timingAnalysis['beta_up']);
echo sprintf("  Beta (Down Markets): %.4f\n", $timingAnalysis['beta_down']);
echo sprintf("  Timing Coefficient: %.4f\n", $timingAnalysis['timing_coefficient']);
echo sprintf("  Up Periods: %d\n", $timingAnalysis['up_periods']);
echo sprintf("  Down Periods: %d\n", $timingAnalysis['down_periods']);

if ($timingAnalysis['timing_coefficient'] > 0) {
    echo "\n  ✓ Positive timing ability detected (higher beta in up markets)\n\n";
} else {
    echo "\n  ✗ Poor timing ability (higher beta in down markets)\n\n";
}

// =============================================================================
// Example 4: Comprehensive Portfolio Risk Analysis
// =============================================================================
echo "=== Example 4: Comprehensive Portfolio Risk Analysis ===\n\n";

$riskAnalyzer = new RiskAnalyzer();

// Sample portfolio with multiple assets
$portfolio = [
    'AAPL' => [
        'returns' => [0.03, 0.05, -0.01, 0.06, -0.02, 0.07, 0.02, -0.03, 0.05, 0.04],
        'weight' => 0.3,
    ],
    'MSFT' => [
        'returns' => [0.025, 0.04, -0.015, 0.05, -0.025, 0.06, 0.015, -0.035, 0.045, 0.035],
        'weight' => 0.3,
    ],
    'GOOGL' => [
        'returns' => [0.035, 0.055, -0.005, 0.065, -0.015, 0.075, 0.025, -0.025, 0.055, 0.045],
        'weight' => 0.2,
    ],
    'BND' => [
        'returns' => [0.005, 0.008, 0.003, 0.007, 0.004, 0.006, 0.005, 0.003, 0.006, 0.005],
        'weight' => 0.2,
    ],
];

$marketReturns = [0.02, 0.03, -0.01, 0.04, -0.02, 0.05, 0.01, -0.03, 0.04, 0.02];
$riskFreeRate = 0.02;
$confidenceLevel = 0.95;

// Perform comprehensive risk analysis
$analysis = $riskAnalyzer->analyzePortfolio($portfolio, $marketReturns, $riskFreeRate, $confidenceLevel);

// Display risk score
echo "Risk Score:\n";
echo sprintf("  Total Risk Score: %.1f/100 (%s)\n", 
    $analysis['risk_score']['total'],
    $analysis['risk_score']['rating']
);
echo sprintf("  VaR Component: %.1f/25\n", $analysis['risk_score']['var_component']);
echo sprintf("  Diversification Component: %.1f/25\n", $analysis['risk_score']['diversification_component']);
echo sprintf("  Beta Component: %.1f/25\n", $analysis['risk_score']['beta_component']);
echo sprintf("  Performance Component: %.1f/25\n", $analysis['risk_score']['performance_component']);

// Display VaR analysis
echo "\nValue at Risk (VaR):\n";
echo sprintf("  Historical VaR (95%%): %.2f%%\n", $analysis['var_analysis']['historical_var_95'] * 100);
echo sprintf("  Parametric VaR (95%%): %.2f%%\n", $analysis['var_analysis']['parametric_var_95'] * 100);
echo sprintf("  Monte Carlo VaR (95%%): %.2f%%\n", $analysis['var_analysis']['monte_carlo_var_95'] * 100);
echo sprintf("  CVaR (95%%): %.2f%%\n", $analysis['var_analysis']['cvar_95'] * 100);
echo sprintf("  Historical VaR (99%%): %.2f%%\n", $analysis['var_analysis']['historical_var_99'] * 100);

// Display diversification
echo "\nDiversification Analysis:\n";
echo sprintf("  Diversification Score: %.2f\n", $analysis['correlation_analysis']['diversification']['score']);
echo sprintf("  Rating: %s\n", $analysis['correlation_analysis']['diversification']['interpretation']);

// Display performance metrics
echo "\nPerformance Metrics:\n";
echo sprintf("  Sharpe Ratio: %.4f\n", $analysis['performance_metrics']['sharpe_ratio']);
echo sprintf("  Sortino Ratio: %.4f\n", $analysis['performance_metrics']['sortino_ratio']);
echo sprintf("  Treynor Ratio: %.4f\n", $analysis['performance_metrics']['treynor_ratio']);
echo sprintf("  Information Ratio: %.4f\n", $analysis['performance_metrics']['information_ratio']);

// Display recommendations
echo "\nRecommendations:\n";
foreach ($analysis['recommendations'] as $rec) {
    $priority = strtoupper($rec['priority']);
    echo sprintf("  [%s] %s\n", $priority, $rec['message']);
}

// Display summary
echo "\nExecutive Summary:\n";
echo $analysis['summary'] . "\n\n";

// =============================================================================
// Example 5: Stress Testing
// =============================================================================
echo "=== Example 5: Stress Testing ===\n\n";

// Define stress test scenarios
$scenarios = [
    'market_crash' => [
        'description' => 'Market crashes 30%',
        'asset_shocks' => [
            'AAPL' => -0.35,
            'MSFT' => -0.32,
            'GOOGL' => -0.38,
            'BND' => -0.05,
        ],
    ],
    'tech_sector_collapse' => [
        'description' => 'Tech sector collapses 50%',
        'asset_shocks' => [
            'AAPL' => -0.50,
            'MSFT' => -0.48,
            'GOOGL' => -0.52,
            'BND' => 0.02, // Flight to safety
        ],
    ],
    'moderate_correction' => [
        'description' => 'Market corrects 10%',
        'asset_shocks' => [
            'AAPL' => -0.12,
            'MSFT' => -0.10,
            'GOOGL' => -0.13,
            'BND' => -0.01,
        ],
    ],
    'interest_rate_spike' => [
        'description' => 'Interest rates spike 2%',
        'asset_shocks' => [
            'AAPL' => -0.08,
            'MSFT' => -0.07,
            'GOOGL' => -0.09,
            'BND' => -0.15, // Bonds hit harder
        ],
    ],
];

$stressResults = $riskAnalyzer->stressTest($portfolio, $scenarios);

echo "Stress Test Results:\n\n";
foreach ($stressResults as $scenarioName => $result) {
    $scenario = $scenarios[$scenarioName];
    echo "Scenario: {$scenario['description']}\n";
    echo sprintf("  VaR (95%%): %.2f%%\n", $result['var_95'] * 100);
    echo sprintf("  VaR (99%%): %.2f%%\n", $result['var_99'] * 100);
    echo sprintf("  Expected Return: %.2f%%\n", $result['expected_return'] * 100);
    echo sprintf("  Maximum Loss: %.2f%%\n", $result['max_loss'] * 100);
    echo sprintf("  Severity: %s\n", $result['severity']);
    echo "\n";
}

// =============================================================================
// Example 6: Risk Contribution Analysis
// =============================================================================
echo "=== Example 6: Risk Contribution Analysis ===\n\n";

$riskContributions = $riskAnalyzer->riskContribution($portfolio);

echo "Risk Contribution by Asset:\n";
echo str_pad('Asset', 10) . str_pad('Weight', 12) . str_pad('Volatility', 15) . 
     str_pad('Marginal VaR', 15) . str_pad('Contribution', 15) . "\n";
echo str_repeat('-', 67) . "\n";

foreach ($riskContributions as $symbol => $contribution) {
    echo sprintf(
        "%-10s %10.1f%% %14.2f%% %14.2f%% %14.1f%%\n",
        $symbol,
        $portfolio[$symbol]['weight'] * 100,
        $contribution['volatility'] * 100,
        $contribution['marginal_var'] * 100,
        $contribution['contribution_percent']
    );
}

echo "\nInterpretation:\n";
echo "- Assets with high contribution relative to weight may warrant position reduction\n";
echo "- Assets with low contribution are good diversifiers\n";
echo "- Total contributions sum to 100%\n\n";

// =============================================================================
// Example 7: Rolling Beta Analysis (Time-Varying Risk)
// =============================================================================
echo "=== Example 7: Rolling Beta Analysis ===\n\n";

// Generate longer time series
$longAssetReturns = [];
$longMarketReturns = [];
for ($i = 0; $i < 100; $i++) {
    // Create time-varying beta (higher in later periods)
    if ($i < 50) {
        $longAssetReturns[] = 0.01 + (rand(-100, 100) / 1000);
        $longMarketReturns[] = 0.01 + (rand(-100, 100) / 1000);
    } else {
        $longAssetReturns[] = 0.02 + (rand(-100, 100) / 500); // Higher beta
        $longMarketReturns[] = 0.01 + (rand(-100, 100) / 1000);
    }
}

$window = 30;
$rollingBeta = $betaCalculator->rollingBeta($longAssetReturns, $longMarketReturns, $window);

echo "Rolling Beta Analysis (30-period window):\n";
echo sprintf("  Number of windows: %d\n", count($rollingBeta));
echo sprintf("  First beta: %.4f\n", $rollingBeta[0]);
echo sprintf("  Last beta: %.4f\n", $rollingBeta[count($rollingBeta) - 1]);
echo sprintf("  Average beta: %.4f\n", array_sum($rollingBeta) / count($rollingBeta));
echo sprintf("  Min beta: %.4f\n", min($rollingBeta));
echo sprintf("  Max beta: %.4f\n", max($rollingBeta));

echo "\nInterpretation:\n";
echo "- Beta changes over time reflect changing market conditions\n";
echo "- Increasing beta suggests rising systematic risk\n";
echo "- Wide range indicates significant regime changes\n\n";

// =============================================================================
// Example 8: Production Risk Monitoring Setup
// =============================================================================
echo "=== Example 8: Production Risk Monitoring Setup ===\n\n";

echo "Production Risk Monitoring Recommendations:\n\n";

echo "1. Daily Risk Dashboard:\n";
echo "   - Calculate portfolio VaR daily\n";
echo "   - Monitor diversification score\n";
echo "   - Track beta exposure to market\n";
echo "   - Alert on risk score > 70\n\n";

echo "2. Weekly Risk Reports:\n";
echo "   - Full risk analysis with all components\n";
echo "   - Stress test against key scenarios\n";
echo "   - Review risk contribution by asset\n";
echo "   - Trend analysis on rolling metrics\n\n";

echo "3. Risk Thresholds:\n";
echo "   - VaR (95%): Alert if > 15%\n";
echo "   - Diversification: Alert if < 0.5\n";
echo "   - Beta: Alert if > 1.5 or < 0.5\n";
echo "   - Correlation: Alert on pairs > 0.9\n\n";

echo "4. Rebalancing Triggers:\n";
echo "   - Risk score > 70 (very high risk)\n";
echo "   - Diversification < 0.4 (poor)\n";
echo "   - Single asset contribution > 40%\n";
echo "   - Stress test severity > extreme\n\n";

echo "5. Reporting Schedule:\n";
echo "   - Real-time: VaR breaches, extreme moves\n";
echo "   - Daily: Risk score, diversification\n";
echo "   - Weekly: Full risk analysis, recommendations\n";
echo "   - Monthly: Stress testing, historical performance\n\n";

// Example risk monitoring function
function monitorPortfolioRisk(array $portfolio, array $marketReturns, float $riskFreeRate): array
{
    $analyzer = new RiskAnalyzer();
    $analysis = $analyzer->analyzePortfolio($portfolio, $marketReturns, $riskFreeRate, 0.95);
    
    $alerts = [];
    
    // Check risk score
    if ($analysis['risk_score']['total'] > 70) {
        $alerts[] = [
            'severity' => 'HIGH',
            'message' => 'Portfolio risk score is very high: ' . round($analysis['risk_score']['total'], 1),
        ];
    }
    
    // Check VaR
    if (abs($analysis['var_analysis']['historical_var_95']) > 0.15) {
        $alerts[] = [
            'severity' => 'HIGH',
            'message' => 'VaR exceeds 15% threshold: ' . round($analysis['var_analysis']['historical_var_95'] * 100, 2) . '%',
        ];
    }
    
    // Check diversification
    if ($analysis['correlation_analysis']['diversification']['score'] < 0.5) {
        $alerts[] = [
            'severity' => 'MEDIUM',
            'message' => 'Poor diversification: ' . round($analysis['correlation_analysis']['diversification']['score'], 2),
        ];
    }
    
    return [
        'analysis' => $analysis,
        'alerts' => $alerts,
        'timestamp' => date('Y-m-d H:i:s'),
    ];
}

echo "Example monitoring function created: monitorPortfolioRisk()\n";
echo "Use this to continuously monitor portfolio risk and trigger alerts.\n\n";

echo "=== End of Risk Analysis Examples ===\n";
