# Portfolio Optimization Guide

Modern Portfolio Theory (MPT) implementation for optimal asset allocation and risk-adjusted returns.

## Table of Contents

1. [Overview](#overview)
2. [Mathematical Background](#mathematical-background)
3. [Getting Started](#getting-started)
4. [Optimization Methods](#optimization-methods)
5. [Risk Metrics](#risk-metrics)
6. [API Reference](#api-reference)
7. [Usage Examples](#usage-examples)
8. [Integration with AI Assistant](#integration-with-ai-assistant)
9. [Best Practices](#best-practices)
10. [Limitations & Future Enhancements](#limitations--future-enhancements)

## Overview

### What is Modern Portfolio Theory?

Modern Portfolio Theory (MPT), developed by Harry Markowitz in 1952 (Nobel Prize 1990), is a framework for constructing portfolios that maximize expected return for a given level of risk, or minimize risk for a given level of expected return.

**Key Concepts:**

- **Diversification**: Combining assets with low correlation reduces overall portfolio risk
- **Efficient Frontier**: Curve of optimal portfolios offering highest return for each risk level
- **Sharpe Ratio**: Measures risk-adjusted return (higher is better)
- **Mean-Variance Optimization**: Balance between expected return (mean) and risk (variance)

### Why Use Portfolio Optimization?

1. **Scientific Position Sizing**: Move beyond gut feel to quantitative allocation
2. **Risk Management**: Quantify and control portfolio risk exposure
3. **Maximize Efficiency**: Get best possible return for your risk tolerance
4. **Complement AI Analysis**: Combine qualitative AI insights with quantitative optimization

### System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                   LLM Trading Assistant                      │
│                  (AI Recommendations)                        │
└────────────────────┬────────────────────────────────────────┘
                     │
    ┌────────────────┼────────────────┐
    │                │                │
    ▼                ▼                ▼
┌─────────┐  ┌──────────────┐  ┌──────────────┐
│Fundamen-│  │  News        │  │  Portfolio   │
│tal Data │  │  Sentiment   │  │  Optimization│
└─────────┘  └──────────────┘  └──────────────┘
                                       │
                        ┌──────────────┼──────────────┐
                        │              │              │
                        ▼              ▼              ▼
                ┌──────────┐  ┌──────────┐  ┌──────────┐
                │   MPT    │  │  Risk    │  │Efficient │
                │Optimizer │  │ Analyzer │  │ Frontier │
                └──────────┘  └──────────┘  └──────────┘
```

## Mathematical Background

### Expected Return

Portfolio expected return is the weighted average of individual asset returns:

```
E(Rp) = Σ wi * E(Ri)
```

Where:
- `E(Rp)` = Expected portfolio return
- `wi` = Weight of asset i
- `E(Ri)` = Expected return of asset i

### Portfolio Variance

Portfolio variance accounts for correlations between assets:

```
σp² = Σi Σj wi * wj * Cov(Ri, Rj)
```

Or in matrix notation:
```
σp² = W^T * Σ * W
```

Where:
- `σp²` = Portfolio variance
- `W` = Weight vector [w1, w2, ..., wn]
- `Σ` = Covariance matrix
- `Cov(Ri, Rj)` = Covariance between assets i and j

### Sharpe Ratio

Risk-adjusted return metric:

```
Sharpe Ratio = (E(Rp) - Rf) / σp
```

Where:
- `E(Rp)` = Expected portfolio return
- `Rf` = Risk-free rate (e.g., 2% for T-bills)
- `σp` = Portfolio volatility (standard deviation)

**Interpretation:**
- **< 1.0**: Poor risk-adjusted returns
- **1.0 - 2.0**: Good
- **2.0 - 3.0**: Very good
- **> 3.0**: Excellent

### Covariance Matrix

Measures how assets move together:

```
Cov(X,Y) = E[(X - μx)(Y - μy)]
```

**Example 3x3 Covariance Matrix:**
```
         AAPL    MSFT    GOOGL
AAPL   [ 0.04    0.02    0.025 ]
MSFT   [ 0.02    0.03    0.020 ]
GOOGL  [ 0.025   0.020   0.035 ]
```

### Correlation Coefficient

Normalized covariance (-1 to +1):

```
ρ(X,Y) = Cov(X,Y) / (σx * σy)
```

**Interpretation:**
- **+1.0**: Perfect positive correlation
- **0.0**: No correlation
- **-1.0**: Perfect negative correlation

## Getting Started

### Installation

Portfolio optimization is included in the Stock Analysis system:

```bash
cd Stock-Analysis
composer install
```

### Basic Usage

```php
use WealthSystem\StockAnalysis\Portfolio\ModernPortfolioTheoryOptimizer;

$optimizer = new ModernPortfolioTheoryOptimizer($logger);

$tickers = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA'];

$result = $optimizer->maximizeSharpeRatio($tickers);

echo "Expected Return: " . ($result->expectedReturn * 100) . "%\n";
echo "Volatility: " . ($result->volatility * 100) . "%\n";
echo "Sharpe Ratio: " . $result->sharpeRatio . "\n\n";

echo "Optimal Weights:\n";
foreach ($result->weights as $ticker => $weight) {
    echo "  {$ticker}: " . ($weight * 100) . "%\n";
}
```

## Optimization Methods

### 1. Maximize Sharpe Ratio

**Purpose**: Find portfolio with best risk-adjusted returns

**When to use:**
- General purpose optimization
- Balancing risk and return
- Moderate risk tolerance

**Implementation:**
```php
$result = $optimizer->maximizeSharpeRatio($tickers, [
    'lookback_days' => 252,        // 1 year of trading days
    'risk_free_rate' => 0.02,      // 2% risk-free rate
]);
```

**Algorithm:**
1. Calculate historical returns for each ticker
2. Calculate covariance matrix (asset correlations)
3. Calculate expected returns (annualized)
4. Run Monte Carlo simulation (10,000 iterations)
5. For each iteration:
   - Generate random portfolio weights (sum = 1.0)
   - Calculate portfolio return, volatility, Sharpe ratio
   - Track best Sharpe ratio
6. Return optimal weights

### 2. Minimize Variance

**Purpose**: Find portfolio with lowest risk (volatility)

**When to use:**
- Conservative investors
- Capital preservation
- Risk-averse strategies

**Implementation:**
```php
$result = $optimizer->minimizeVariance($tickers, [
    'risk_free_rate' => 0.02,
]);
```

**Algorithm:**
1. Calculate covariance matrix
2. Run Monte Carlo simulation
3. For each iteration:
   - Generate random weights
   - Calculate portfolio variance: W^T * Σ * W
   - Track minimum variance
4. Return lowest risk portfolio

**Note**: This is the **minimum variance portfolio (MVP)** on the efficient frontier.

### 3. Target Return

**Purpose**: Achieve specific return with minimum risk

**When to use:**
- Specific return goals (e.g., 12% annual)
- Retirement planning
- Goal-based investing

**Implementation:**
```php
$targetReturn = 0.12; // 12% annual
$result = $optimizer->targetReturn($tickers, $targetReturn, [
    'risk_free_rate' => 0.02,
]);
```

**Algorithm:**
1. Calculate expected returns and covariance
2. Run Monte Carlo simulation
3. For each iteration:
   - Generate random weights
   - Calculate portfolio return
   - If return matches target (within 1% tolerance):
     - Calculate volatility
     - Track minimum volatility
4. Return optimal weights for target return

**Fallback**: If target is unachievable, returns error with closest result.

### 4. Efficient Frontier

**Purpose**: Generate series of optimal portfolios across risk spectrum

**When to use:**
- Exploring risk/return tradeoffs
- Visualization
- Understanding portfolio possibilities

**Implementation:**
```php
$points = $optimizer->calculateEfficientFrontier($tickers, 50, [
    'risk_free_rate' => 0.02,
]);

foreach ($points as $point) {
    echo "Risk: {$point->volatility}, ";
    echo "Return: {$point->expectedReturn}, ";
    echo "Sharpe: {$point->sharpeRatio}\n";
}
```

**Algorithm:**
1. Calculate min/max possible returns
2. Generate target returns from min to max (evenly spaced)
3. For each target:
   - Use targetReturn() to find optimal portfolio
4. Sort by volatility
5. Return array of EfficientFrontierPoint objects

**Visualization:**
```
Return (%)
    │
 15 │                    ●  (Aggressive)
    │                  ●
 12 │               ●
    │            ●
 10 │         ●       (Moderate)
    │      ●
  8 │   ●          (Conservative)
    │●
  5 └────────────────────────────> Risk (%)
    5    10   15   20   25   30
```

## Risk Metrics

### 1. Volatility (Standard Deviation)

**Definition**: Annualized standard deviation of returns

**Formula:**
```
σp = sqrt(Var(Rp)) * sqrt(252)
```

**Interpretation:**
- **Low (< 10%)**: Conservative, stable
- **Moderate (10-20%)**: Balanced
- **High (> 20%)**: Aggressive, volatile

**Example:**
```php
$metrics = $riskAnalyzer->analyzePortfolio($weights, $returns);
echo "Volatility: " . ($metrics['volatility'] * 100) . "%\n";
```

### 2. Sharpe Ratio

**Definition**: Risk-adjusted return

**Formula:**
```
Sharpe = (Return - Risk_Free) / Volatility
```

**Interpretation:**
- **< 1.0**: Poor
- **1.0 - 2.0**: Good
- **> 2.0**: Excellent

### 3. Sortino Ratio

**Definition**: Like Sharpe, but only penalizes downside volatility

**Formula:**
```
Sortino = (Return - Risk_Free) / Downside_Deviation
```

**Why use Sortino?**
- Sharpe penalizes ALL volatility (even upside)
- Sortino only penalizes downside (losses)
- Better for asymmetric return distributions

**Example:**
```
Portfolio A: 12% return, 15% volatility (both up and down)
Portfolio B: 12% return, 15% upside, 8% downside

Sharpe: A and B similar
Sortino: B is better (lower downside risk)
```

### 4. Maximum Drawdown

**Definition**: Largest peak-to-trough decline

**Formula:**
```
MaxDD = max((Peak - Trough) / Peak)
```

**Interpretation:**
- **< 10%**: Very stable
- **10-20%**: Moderate
- **20-40%**: High volatility
- **> 40%**: Extreme losses

**Example:**
```
Portfolio value: $100k → $120k → $90k → $110k

Peak: $120k
Trough: $90k
Max Drawdown: ($120k - $90k) / $120k = 25%
```

### 5. Value at Risk (VaR)

**Definition**: Maximum expected loss at confidence level

**VaR 95%**: 95% confident losses won't exceed this amount  
**VaR 99%**: 99% confident losses won't exceed this amount

**Example:**
```
VaR 95% = 5%
VaR 99% = 8%

Interpretation:
- 95% of days: losses < 5%
- 99% of days: losses < 8%
- 5% of days: losses > 5%
- 1% of days: losses > 8%
```

### 6. Beta

**Definition**: Sensitivity to market movements

**Formula:**
```
β = Cov(Portfolio, Market) / Var(Market)
```

**Interpretation:**
- **β = 1.0**: Moves with market
- **β < 1.0**: Less volatile than market
- **β > 1.0**: More volatile than market
- **β < 0**: Moves opposite to market

**Example:**
```
Market up 10%, Portfolio up 12% → β = 1.2
Market down 10%, Portfolio down 12% → β = 1.2

β = 1.2 means 20% more volatile than market
```

### 7. Correlation Matrix

**Definition**: Pairwise correlations between all assets

**Example:**
```
         AAPL   MSFT   GOOGL
AAPL    [ 1.00  0.75   0.70 ]
MSFT    [ 0.75  1.00   0.80 ]
GOOGL   [ 0.70  0.80   1.00 ]

Interpretation:
- AAPL-MSFT: 0.75 (high positive correlation)
- AAPL-GOOGL: 0.70 (high positive correlation)
- All tech stocks move together

Diversification benefit: LOW (all highly correlated)
```

**Better diversification:**
```
         AAPL   BOND   GOLD
AAPL    [ 1.00  0.20  -0.10 ]
BOND    [ 0.20  1.00   0.30 ]
GOLD    [-0.10  0.30   1.00 ]

Diversification benefit: HIGH (low/negative correlations)
```

## API Reference

### PortfolioOptimizerInterface

```php
interface PortfolioOptimizerInterface
{
    /**
     * Maximize Sharpe ratio (best risk-adjusted returns)
     *
     * @param array<string> $tickers Array of ticker symbols
     * @param array<string, mixed> $options Configuration options
     * @return OptimizationResult
     */
    public function maximizeSharpeRatio(
        array $tickers,
        array $options = []
    ): OptimizationResult;

    /**
     * Minimize variance (lowest risk portfolio)
     */
    public function minimizeVariance(
        array $tickers,
        array $options = []
    ): OptimizationResult;

    /**
     * Target specific return with minimum risk
     *
     * @param float $targetReturn Annual return target (e.g., 0.12 for 12%)
     */
    public function targetReturn(
        array $tickers,
        float $targetReturn,
        array $options = []
    ): OptimizationResult;

    /**
     * Calculate efficient frontier
     *
     * @param int $points Number of frontier points (default 50)
     * @return array<EfficientFrontierPoint>
     */
    public function calculateEfficientFrontier(
        array $tickers,
        int $points = 50,
        array $options = []
    ): array;
}
```

### OptimizationResult

```php
readonly class OptimizationResult
{
    public function __construct(
        public array $weights,              // ['AAPL' => 0.3, 'MSFT' => 0.7]
        public float $expectedReturn,        // 0.12 (12% annual)
        public float $volatility,            // 0.15 (15% risk)
        public float $sharpeRatio,           // 0.67
        public string $method,               // 'maximize_sharpe_ratio'
        public array $metrics = [],          // Additional metrics
        public DateTimeImmutable $calculatedAt = new DateTimeImmutable(),
        public ?string $error = null
    );

    public function isValid(): bool;
    public function getWeight(string $ticker): float;
    public function getSortedTickers(): array;
    public function getAllocation(float $portfolioValue): array;
    public function toArray(): array;
    public function toPromptString(): string; // For LLM inclusion
}
```

### PortfolioRiskAnalyzer

```php
class PortfolioRiskAnalyzer
{
    /**
     * Calculate comprehensive risk metrics
     *
     * @param array<string, float> $weights Portfolio weights
     * @param array<string, array<float>> $returns Historical returns
     * @param array<string, mixed> $options Configuration
     * @return array<string, mixed> Risk metrics
     */
    public function analyzePortfolio(
        array $weights,
        array $returns,
        array $options = []
    ): array;

    /**
     * Format risk metrics for display
     */
    public function formatRiskMetrics(array $metrics): string;
}
```

## Usage Examples

### Example 1: Basic Optimization

```php
use WealthSystem\StockAnalysis\Portfolio\ModernPortfolioTheoryOptimizer;

$optimizer = new ModernPortfolioTheoryOptimizer($logger);

$tickers = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA'];

$result = $optimizer->maximizeSharpeRatio($tickers);

if ($result->isValid()) {
    echo "Optimal Portfolio:\n";
    foreach ($result->getSortedTickers() as $ticker) {
        $weight = $result->getWeight($ticker);
        echo "  {$ticker}: " . number_format($weight * 100, 1) . "%\n";
    }
    
    echo "\nMetrics:\n";
    echo "  Expected Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
    echo "  Volatility: " . number_format($result->volatility * 100, 2) . "%\n";
    echo "  Sharpe Ratio: " . number_format($result->sharpeRatio, 2) . "\n";
}
```

### Example 2: Dollar Allocation

```php
$result = $optimizer->maximizeSharpeRatio($tickers);

$portfolioValue = 100000; // $100k
$allocation = $result->getAllocation($portfolioValue);

echo "Invest $100,000 as follows:\n";
foreach ($allocation as $ticker => $dollars) {
    if ($dollars >= 1000) {
        echo "  {$ticker}: $" . number_format($dollars, 2) . "\n";
    }
}
```

### Example 3: Conservative Portfolio

```php
$result = $optimizer->minimizeVariance($tickers);

echo "Conservative (Minimum Risk) Portfolio:\n";
echo "Expected Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
echo "Volatility: " . number_format($result->volatility * 100, 2) . "% (LOWEST)\n";
```

### Example 4: Target Return

```php
$targetReturn = 0.12; // 12% annual
$result = $optimizer->targetReturn($tickers, $targetReturn);

if ($result->isValid()) {
    echo "Portfolio for 12% Target Return:\n";
    echo "Actual Return: " . number_format($result->expectedReturn * 100, 2) . "%\n";
    echo "Volatility: " . number_format($result->volatility * 100, 2) . "% (MINIMIZED)\n";
}
```

### Example 5: Risk Analysis

```php
use WealthSystem\StockAnalysis\Portfolio\PortfolioRiskAnalyzer;

$analyzer = new PortfolioRiskAnalyzer($logger);

$metrics = $analyzer->analyzePortfolio($weights, $returns);

echo $analyzer->formatRiskMetrics($metrics);
```

### Example 6: Rebalancing Recommendations

```php
// Current portfolio
$currentWeights = [
    'AAPL' => 0.50,
    'MSFT' => 0.30,
    'GOOGL' => 0.20,
];

// Optimal portfolio
$result = $optimizer->maximizeSharpeRatio(['AAPL', 'MSFT', 'GOOGL']);

// Compare and recommend trades
echo "Rebalancing Recommendations:\n";
foreach ($result->weights as $ticker => $optimalWeight) {
    $currentWeight = $currentWeights[$ticker] ?? 0.0;
    $difference = $optimalWeight - $currentWeight;
    
    if (abs($difference) >= 0.05) { // 5% threshold
        $action = $difference > 0 ? 'BUY' : 'SELL';
        $amount = abs($difference) * 100;
        echo "  {$action} {$ticker}: " . number_format($amount, 1) . "% of portfolio\n";
    }
}
```

## Integration with AI Assistant

### Setup

```php
use WealthSystem\StockAnalysis\AI\LLMTradingAssistantWithOptimization;
use WealthSystem\StockAnalysis\AI\AnthropicProvider;
use WealthSystem\StockAnalysis\AI\AIClient;
use WealthSystem\StockAnalysis\Portfolio\ModernPortfolioTheoryOptimizer;

$provider = new AnthropicProvider($apiKey, 'claude-sonnet-4-20250514');
$aiClient = new AIClient($provider, $logger);
$optimizer = new ModernPortfolioTheoryOptimizer($logger);

$assistant = new LLMTradingAssistantWithOptimization(
    $aiClient,
    $fundamentalService,    // Optional
    $sentimentService,      // Optional
    $optimizer,             // Portfolio optimizer
    $logger
);
```

### Get Optimized Recommendations

```php
$holdings = [
    ['ticker' => 'AAPL', 'quantity' => 50, 'cost_basis' => 150.00],
    ['ticker' => 'MSFT', 'quantity' => 30, 'cost_basis' => 300.00],
];

$recommendations = $assistant->getRecommendations(
    $holdings,
    50000,  // $50k cash
    100000, // $100k total equity
    [
        'use_fundamentals' => true,
        'use_sentiment' => true,
        'use_optimization' => true,
        'optimization_method' => 'sharpe',     // or 'variance', 'target'
        'target_return' => 0.12,               // If using 'target' method
        'risk_tolerance' => 'moderate',         // or 'conservative', 'aggressive'
        'watchlist' => ['GOOGL', 'AMZN', 'NVDA'],
    ]
);

echo $recommendations->reasoning . "\n\n";

foreach ($recommendations->actions as $action) {
    echo "{$action->action} {$action->ticker}\n";
    echo "Reasoning: {$action->reasoning}\n\n";
}
```

### How AI Uses Optimization

The AI assistant receives:

1. **Optimal Weights**: Target allocation percentages
2. **Risk Metrics**: Expected return, volatility, Sharpe ratio
3. **Dollar Allocations**: Specific dollar amounts for each position
4. **Risk Tolerance Context**: Conservative/moderate/aggressive guidance

The AI then:

1. Compares current holdings to optimal weights
2. Recommends trades to move toward optimal allocation
3. Considers transaction costs and tax implications
4. Adjusts for risk tolerance
5. Cites specific metrics in reasoning

**Example AI Response:**

> "Based on portfolio optimization (Sharpe ratio 1.24), I recommend increasing NVDA from 15% to 25% of portfolio (+$10,000). NVDA shows strong fundamentals (P/E 45, ROE 28%) and positive sentiment (0.72), and increasing allocation improves portfolio Sharpe ratio by 0.15 while adding only 2% volatility."

## Best Practices

### 1. Historical Data Quality

**Recommendation**: Use at least 1 year (252 trading days) of historical data

```php
$result = $optimizer->maximizeSharpeRatio($tickers, [
    'lookback_days' => 252,  // 1 year
]);
```

**Why?**
- More data = more reliable correlations
- Captures full business cycle
- Reduces sampling error

**Exceptions:**
- New IPOs: Use shorter periods
- High-frequency trading: Use more data

### 2. Risk-Free Rate Selection

**Current US T-Bill Rates** (as of 2025):
- 3-month: ~5.0%
- 1-year: ~4.5%

```php
$result = $optimizer->maximizeSharpeRatio($tickers, [
    'risk_free_rate' => 0.045,  // 4.5% for 1-year T-bills
]);
```

**Sources:**
- https://www.treasury.gov/resource-center/data-chart-center/interest-rates/

### 3. Rebalancing Frequency

**Recommendations:**
- **Quarterly**: Most common, good balance
- **Monthly**: For active traders
- **Annually**: For tax efficiency

**Rebalancing Trigger:**
```php
$threshold = 0.05; // 5%

foreach ($optimalWeights as $ticker => $targetWeight) {
    $currentWeight = $currentWeights[$ticker] ?? 0.0;
    if (abs($currentWeight - $targetWeight) > $threshold) {
        // Rebalance
    }
}
```

### 4. Transaction Costs

Consider costs before rebalancing:

```php
$tradeValue = $portfolioValue * abs($weightDifference);
$commission = 0; // Most brokers now free
$bidAskSpread = $tradeValue * 0.001; // ~0.1% for liquid stocks
$slippage = $tradeValue * 0.0005; // ~0.05%

$totalCost = $commission + $bidAskSpread + $slippage;

if ($expectedImprovement > $totalCost) {
    // Execute trade
}
```

### 5. Diversification Guidelines

**Minimum**: 3-5 uncorrelated assets  
**Optimal**: 15-20 assets  
**Diminishing returns**: > 30 assets

**Correlation Targets:**
- Low correlation (< 0.3): Excellent diversification
- Moderate (0.3 - 0.7): Good diversification
- High (> 0.7): Limited diversification benefit

### 6. Risk Tolerance Mapping

```php
$riskProfiles = [
    'conservative' => [
        'method' => 'variance',
        'max_volatility' => 0.10,  // 10%
        'target_return' => 0.06,    // 6%
    ],
    'moderate' => [
        'method' => 'sharpe',
        'max_volatility' => 0.15,  // 15%
        'target_return' => 0.10,    // 10%
    ],
    'aggressive' => [
        'method' => 'sharpe',
        'max_volatility' => 0.25,  // 25%
        'target_return' => 0.15,    // 15%
    ],
];

$profile = $riskProfiles[$userRiskTolerance];
$result = $optimizer->{$profile['method']}($tickers, $profile);
```

### 7. Tax Efficiency

**Long-term holdings** (> 1 year): Lower capital gains tax  
**Short-term holdings** (< 1 year): Ordinary income tax

**Tax-Loss Harvesting:**
```php
// Sell losing positions to offset gains
foreach ($holdings as $holding) {
    $gain = $currentPrice - $holding['cost_basis'];
    if ($gain < 0 && $holding['hold_days'] > 30) {
        // Can harvest loss (watch wash sale rule)
    }
}
```

### 8. Market Conditions

**Bull Market**: Higher equity allocation  
**Bear Market**: Higher bond/cash allocation  
**High Volatility**: Reduce position sizes

**Dynamic Adjustment:**
```php
$vixLevel = 20; // VIX index

if ($vixLevel > 30) {
    // High volatility: reduce equity exposure
    $options['max_weight'] = 0.20; // Max 20% per stock
} elseif ($vixLevel < 15) {
    // Low volatility: normal optimization
    $options['max_weight'] = 0.40; // Max 40% per stock
}
```

## Limitations & Future Enhancements

### Current Limitations

#### 1. Simulated Historical Data

**Current State:**
```php
// In ModernPortfolioTheoryOptimizer.php
private function calculateReturns(array $tickers, array $options): array
{
    // TODO: Replace with actual data fetching
    // For now, simulate returns
    $returns = [];
    foreach ($tickers as $ticker) {
        $returns[$ticker] = array_map(
            fn() => (rand(-300, 300) / 10000), // -3% to +3%
            range(1, $lookbackDays)
        );
    }
    return $returns;
}
```

**Solution:**
Integrate with Alpha Vantage TIME_SERIES_DAILY:

```php
private function calculateReturns(array $tickers, array $options): array
{
    $returns = [];
    foreach ($tickers as $ticker) {
        $prices = $this->alphaVantageClient->getHistoricalPrices(
            $ticker,
            $options['lookback_days'] ?? 252
        );
        
        $returns[$ticker] = [];
        for ($i = 1; $i < count($prices); $i++) {
            $returns[$ticker][] = ($prices[$i] - $prices[$i-1]) / $prices[$i-1];
        }
    }
    return $returns;
}
```

#### 2. Monte Carlo vs. Quadratic Programming

**Current**: Monte Carlo simulation (10,000 iterations)  
**Limitation**: Approximate solution, not guaranteed optimal

**Alternative**: True quadratic programming

**Python (scipy) example:**
```python
from scipy.optimize import minimize

def objective(weights, cov_matrix):
    return np.dot(weights.T, np.dot(cov_matrix, weights))

result = minimize(
    objective,
    x0=initial_weights,
    args=(cov_matrix,),
    method='SLSQP',
    bounds=[(0, 1) for _ in range(n)],
    constraints={'type': 'eq', 'fun': lambda w: np.sum(w) - 1}
)
```

**Future Enhancement**: Add PHP quadratic programming library or call Python optimizer via API.

#### 3. Normal Distribution Assumption

**MPT Assumption**: Returns follow normal distribution  
**Reality**: Markets have "fat tails" (extreme events more common)

**Example:**
```
Normal: 99.7% of returns within ±3σ
Reality: More frequent extreme events (2008 crash, COVID)
```

**Mitigation:**
1. Use VaR for tail risk assessment
2. Add max drawdown constraint
3. Consider alternative models (GARCH, Student's t)

#### 4. Transaction Costs

**Not currently included**: Commissions, bid-ask spread, slippage, taxes

**Future Enhancement:**
```php
public function maximizeSharpeRatio(array $tickers, array $options = []): OptimizationResult
{
    $transactionCost = $options['transaction_cost'] ?? 0.001; // 0.1%
    
    // Adjust returns for transaction costs
    $adjustedReturns = [];
    foreach ($expectedReturns as $ticker => $return) {
        $adjustedReturns[$ticker] = $return - $transactionCost;
    }
    
    // Optimize with adjusted returns...
}
```

### Future Enhancements

#### 1. Black-Litterman Model

Incorporate investor views into optimization:

```php
interface PortfolioOptimizerInterface
{
    public function blackLitterman(
        array $tickers,
        array $views,  // ['AAPL' => 0.15, 'MSFT' => 0.12]
        array $confidence = []
    ): OptimizationResult;
}
```

**Benefits:**
- Combines market equilibrium with investor opinions
- Produces more intuitive allocations
- Reduces estimation error

#### 2. Risk Parity

Equal risk contribution from each asset:

```php
public function riskParity(array $tickers, array $options = []): OptimizationResult;
```

**Benefits:**
- Diversifies risk, not just capital
- Better for multi-asset portfolios (stocks + bonds + commodities)

#### 3. Factor Models

Fama-French multi-factor optimization:

```php
public function factorOptimization(
    array $tickers,
    array $factors = ['market', 'size', 'value', 'momentum']
): OptimizationResult;
```

**Benefits:**
- Accounts for systematic risk factors
- Better for equity portfolios
- Explains returns more accurately

#### 4. Dynamic Rebalancing

Automated rebalancing recommendations:

```php
class RebalancingService
{
    public function shouldRebalance(
        array $currentWeights,
        array $optimalWeights,
        float $threshold = 0.05
    ): bool;
    
    public function getRebalancingTrades(
        array $currentHoldings,
        array $optimalWeights,
        float $portfolioValue
    ): array;
}
```

#### 5. Multi-Period Optimization

Optimize across multiple time horizons:

```php
public function multiPeriodOptimization(
    array $tickers,
    int $periods,
    array $options = []
): array; // Returns array of OptimizationResult per period
```

#### 6. Constraints

Advanced position constraints:

```php
$result = $optimizer->maximizeSharpeRatio($tickers, [
    'min_weight' => 0.05,           // Min 5% per position
    'max_weight' => 0.25,           // Max 25% per position
    'sector_limits' => [
        'Technology' => 0.40,       // Max 40% tech
        'Finance' => 0.20,
    ],
    'asset_class_limits' => [
        'stocks' => 0.70,           // Max 70% stocks
        'bonds' => 0.30,
    ],
]);
```

## Conclusion

Modern Portfolio Theory provides a rigorous, quantitative framework for portfolio construction. By combining MPT optimization with AI-driven analysis (fundamentals + sentiment), you get:

1. **Scientific position sizing** (not gut feel)
2. **Optimal risk/return tradeoff**
3. **Comprehensive risk metrics**
4. **Backtested strategies**
5. **Automated rebalancing**

**Next Steps:**

1. Replace simulated data with Alpha Vantage integration
2. Test optimizer on your portfolio
3. Compare results to current allocation
4. Execute recommended trades
5. Monitor performance over time

**Resources:**

- Harry Markowitz, "Portfolio Selection" (1952)
- William Sharpe, "Capital Asset Pricing Model" (1964)
- Alpha Vantage API: https://www.alphavantage.co/
- Modern Portfolio Theory: https://en.wikipedia.org/wiki/Modern_portfolio_theory

**Support:**

For questions or issues, see:
- GitHub: https://github.com/ksfraser/WealthSystem
- Documentation: `/docs/`
- Examples: `/examples/portfolio_optimization_usage.php`
