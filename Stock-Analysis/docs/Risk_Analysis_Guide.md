# Risk Analysis Guide

Comprehensive portfolio risk assessment with correlation analysis, beta calculations, and VaR metrics.

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Core Components](#core-components)
5. [Correlation Analysis](#correlation-analysis)
6. [Beta & Alpha Calculations](#beta--alpha-calculations)
7. [Portfolio Risk Assessment](#portfolio-risk-assessment)
8. [Interpretation Guides](#interpretation-guides)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)

---

## Overview

The Risk Analysis system provides institutional-grade portfolio risk assessment capabilities including:

- **Correlation Analysis**: Multi-method correlation matrices (Pearson, Spearman, Kendall)
- **CAPM Metrics**: Beta, alpha, R-squared, systematic/unsystematic risk
- **VaR Calculations**: Historical, parametric, Monte Carlo, and CVaR
- **Risk Scoring**: 0-100 composite risk score with component breakdown
- **Stress Testing**: Scenario-based portfolio stress tests
- **Risk Contribution**: Individual asset risk decomposition
- **Actionable Recommendations**: Priority-based risk management suggestions

### Key Features

✅ **Multiple Correlation Methods**: Pearson (linear), Spearman (monotonic), Kendall (rank-based)  
✅ **Time-Varying Analysis**: Rolling correlations and betas  
✅ **Diversification Scoring**: Quantitative diversification assessment (0-1 scale)  
✅ **Beta Interpretation**: Automatic categorization (high/low volatility, market-like, etc.)  
✅ **Comprehensive VaR**: Four different VaR methodologies  
✅ **Risk Decomposition**: Systematic vs unsystematic risk breakdown  
✅ **Market Timing**: Detect timing ability in up vs down markets  
✅ **Production Ready**: Suitable for daily risk monitoring

---

## Installation

### Requirements

- **PHP 8.2+**: Modern PHP features (union types, enums)
- **No External Dependencies**: Pure PHP statistical calculations
- **Composer**: For autoloading

### Setup

```bash
# Clone repository
git clone https://github.com/ksfraser/WealthSystem.git
cd WealthSystem/Stock-Analysis

# Install dependencies
composer install

# Verify installation
php examples/risk_analysis_examples.php
```

### Files Structure

```
Stock-Analysis/
├── app/Risk/
│   ├── CorrelationMatrix.php      # Correlation analysis
│   ├── BetaCalculator.php         # CAPM metrics
│   ├── RiskAnalyzer.php           # Portfolio risk assessment
│   ├── RiskMetrics.php            # VaR calculations (existing)
│   ├── VaRCalculator.php          # VaR calculator (existing)
│   ├── CVaRCalculator.php         # CVaR calculator (existing)
│   └── StressTester.php           # Stress testing (existing)
├── tests/Risk/
│   ├── CorrelationMatrixTest.php  # 22 tests
│   ├── BetaCalculatorTest.php     # 23 tests
│   └── RiskAnalyzerTest.php       # 20+ tests
├── examples/
│   └── risk_analysis_examples.php # 8 comprehensive examples
└── docs/
    └── Risk_Analysis_Guide.md     # This file
```

---

## Quick Start

### Basic Correlation Analysis

```php
use App\Risk\CorrelationMatrix;

$correlationMatrix = new CorrelationMatrix();

// Sample returns
$returns = [
    'AAPL' => [0.02, 0.03, -0.01, 0.04, -0.02],
    'MSFT' => [0.025, 0.035, -0.005, 0.035, -0.015],
    'GOOGL' => [0.03, 0.04, -0.02, 0.05, -0.03],
];

// Calculate correlation matrix
$matrix = $correlationMatrix->calculate($returns, 'pearson');

// Find highly correlated pairs
$pairs = $correlationMatrix->findCorrelatedPairs($returns, 0.8);

// Calculate diversification score
$divScore = $correlationMatrix->diversificationScore($returns);
```

### Basic Beta Calculation

```php
use App\Risk\BetaCalculator;

$calculator = new BetaCalculator();

$assetReturns = [0.03, 0.05, -0.01, 0.06, -0.02];
$marketReturns = [0.02, 0.03, -0.01, 0.04, -0.02];
$riskFreeRate = 0.02;

// Calculate all CAPM metrics
$metrics = $calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);

echo "Beta: " . $metrics['beta'] . "\n";
echo "Alpha: " . $metrics['alpha'] . "\n";
echo "R-squared: " . $metrics['r_squared'] . "\n";
```

### Comprehensive Portfolio Risk Assessment

```php
use App\Risk\RiskAnalyzer;

$analyzer = new RiskAnalyzer();

$portfolio = [
    'AAPL' => [
        'returns' => [0.03, 0.05, -0.01, 0.06, -0.02],
        'weight' => 0.4,
    ],
    'MSFT' => [
        'returns' => [0.025, 0.04, -0.015, 0.05, -0.025],
        'weight' => 0.3,
    ],
    'BND' => [
        'returns' => [0.005, 0.008, 0.003, 0.007, 0.004],
        'weight' => 0.3,
    ],
];

$marketReturns = [0.02, 0.03, -0.01, 0.04, -0.02];
$riskFreeRate = 0.02;
$confidenceLevel = 0.95;

$analysis = $analyzer->analyzePortfolio(
    $portfolio,
    $marketReturns,
    $riskFreeRate,
    $confidenceLevel
);

// Access results
echo "Risk Score: " . $analysis['risk_score']['total'] . "/100\n";
echo "VaR (95%): " . ($analysis['var_analysis']['historical_var_95'] * 100) . "%\n";
echo "Diversification: " . $analysis['correlation_analysis']['diversification']['score'] . "\n";
```

---

## Core Components

### 1. CorrelationMatrix

Multi-method correlation analysis with diversification scoring.

#### Key Methods

```php
// Calculate full correlation matrix
$matrix = $correlationMatrix->calculate($returns, $method);
// Methods: 'pearson', 'spearman', 'kendall'

// Individual correlation methods
$pearson = $correlationMatrix->pearsonCorrelation($x, $y);
$spearman = $correlationMatrix->spearmanCorrelation($x, $y);
$kendall = $correlationMatrix->kendallCorrelation($x, $y);

// Rolling correlation (time-varying)
$rolling = $correlationMatrix->rollingCorrelation($x, $y, $window);

// Full correlation statistics
$stats = $correlationMatrix->correlationStats($x, $y, $rollingWindow);

// Find highly correlated pairs
$pairs = $correlationMatrix->findCorrelatedPairs($returns, $threshold);

// Diversification score (0-1, higher is better)
$divScore = $correlationMatrix->diversificationScore($returns);

// Convert to distance matrix for clustering
$distances = $correlationMatrix->toDistanceMatrix($correlationMatrix);
```

### 2. BetaCalculator

CAPM-based systematic risk analysis.

#### Key Methods

```php
// Individual metrics
$beta = $calculator->beta($assetReturns, $marketReturns);
$alpha = $calculator->alpha($assetReturns, $marketReturns, $riskFreeRate);
$rSquared = $calculator->rSquared($assetReturns, $marketReturns);

// All metrics at once
$metrics = $calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);
// Returns: beta, alpha, r_squared, asset_return, market_return,
//          asset_volatility, market_volatility, systematic_risk,
//          unsystematic_risk, systematic_percent, unsystematic_percent,
//          beta_interpretation, alpha_interpretation

// Rolling metrics (time-varying)
$rollingBeta = $calculator->rollingBeta($assetReturns, $marketReturns, $window);
$rollingAlpha = $calculator->rollingAlpha($assetReturns, $marketReturns, $riskFreeRate, $window);

// Risk-adjusted performance
$treynor = $calculator->treynorRatio($assetReturns, $marketReturns, $riskFreeRate);
$jensensAlpha = $calculator->jensensAlpha($assetReturns, $marketReturns, $riskFreeRate);

// Market timing analysis
$timing = $calculator->marketTiming($assetReturns, $marketReturns);
// Returns: beta_up, beta_down, timing_coefficient, up_periods, down_periods
```

### 3. RiskAnalyzer

Comprehensive portfolio risk assessment.

#### Key Methods

```php
// Main portfolio analysis method
$analysis = $analyzer->analyzePortfolio($portfolio, $marketReturns, $riskFreeRate, $confidenceLevel);
// Returns: risk_score, var_analysis, correlation_analysis,
//          beta_analysis, performance_metrics, recommendations, summary

// Stress testing
$stressResults = $analyzer->stressTest($portfolio, $scenarios);
// Scenarios: array of ['description' => ..., 'asset_shocks' => [...]]

// Risk contribution by asset
$contributions = $analyzer->riskContribution($portfolio);
// Returns: volatility, marginal_var, component_var, contribution_percent per asset
```

---

## Correlation Analysis

### Correlation Methods

#### Pearson Correlation
**Use Case**: Linear relationships  
**Range**: -1 (perfect negative) to +1 (perfect positive)  
**Formula**: Covariance divided by product of standard deviations

```php
$pearson = $correlationMatrix->pearsonCorrelation($assetA, $assetB);
```

**Interpretation**:
- **1.0**: Perfect positive correlation (assets move together)
- **0.0**: No linear correlation
- **-1.0**: Perfect negative correlation (assets move opposite)

#### Spearman Correlation
**Use Case**: Monotonic relationships (not necessarily linear)  
**Range**: -1 to +1  
**Method**: Rank-based correlation

```php
$spearman = $correlationMatrix->spearmanCorrelation($assetA, $assetB);
```

**Advantages**:
- Robust to outliers
- Detects non-linear monotonic relationships
- Better for non-normal distributions

#### Kendall Correlation (Tau)
**Use Case**: Concordant vs discordant pairs  
**Range**: -1 to +1  
**Method**: Pairwise comparisons

```php
$kendall = $correlationMatrix->kendallCorrelation($assetA, $assetB);
```

**Advantages**:
- More robust than Pearson
- Better for small sample sizes
- Clearer interpretation (proportion of concordant pairs)

### Rolling Correlation

Analyze time-varying relationships:

```php
$window = 30; // 30-period rolling window
$rolling = $correlationMatrix->rollingCorrelation($assetA, $assetB, $window);
```

**Use Cases**:
- Detect changing relationships during market regimes
- Identify correlation breakdowns during crises
- Monitor diversification effectiveness over time

### Diversification Score

Quantify portfolio diversification:

```php
$divScore = $correlationMatrix->diversificationScore($returns);
// Returns: ['score' => 0.0-1.0, 'interpretation' => '...', 'description' => '...']
```

**Score Interpretation**:
- **0.9-1.0**: Excellent diversification
- **0.7-0.9**: Good diversification
- **0.5-0.7**: Moderate diversification
- **0.3-0.5**: Poor diversification
- **0.0-0.3**: Very poor diversification

**Calculation**: `1 - (average absolute correlation)`

### Correlation Strength

Automatic strength categorization:

| Correlation | Strength |
|-------------|----------|
| 0.9-1.0 | Very Strong |
| 0.7-0.9 | Strong |
| 0.4-0.7 | Moderate |
| 0.2-0.4 | Weak |
| 0.0-0.2 | Very Weak |

---

## Beta & Alpha Calculations

### Beta (β)

**Definition**: Measure of systematic risk relative to market  
**Formula**: `β = Cov(Asset, Market) / Var(Market)`

```php
$beta = $calculator->beta($assetReturns, $marketReturns);
```

**Interpretation**:

| Beta | Interpretation | Meaning |
|------|----------------|---------|
| β > 1.2 | High Volatility | Asset amplifies market movements |
| β > 1.0 | Above Market | Asset moves more than market |
| β ≈ 1.0 | Market-Like | Asset tracks market closely |
| β < 0.8 | Low Volatility | Defensive asset |
| β < 0.5 | Very Low Volatility | Very defensive |
| β < 0 | Negative Correlation | Moves opposite to market |

**Examples**:
- **β = 1.5**: If market goes up 10%, asset typically goes up 15%
- **β = 0.5**: If market goes down 10%, asset typically goes down 5%
- **β = -0.5**: If market goes up 10%, asset typically goes down 5%

### Alpha (α)

**Definition**: Excess return above CAPM prediction  
**Formula**: `α = Actual Return - [Rf + β * (Market Return - Rf)]`

```php
$alpha = $calculator->alpha($assetReturns, $marketReturns, $riskFreeRate);
```

**Interpretation**:

| Alpha | Interpretation | Meaning |
|-------|----------------|---------|
| α > 0.05 | Strong Outperformance | Significantly beats CAPM prediction |
| α > 0.02 | Outperformance | Beats CAPM prediction |
| -0.02 to 0.02 | Market Performance | Performs as predicted by CAPM |
| α < -0.02 | Underperformance | Below CAPM prediction |
| α < -0.05 | Strong Underperformance | Significantly below CAPM |

**Note**: Alpha represents skill/luck after adjusting for systematic risk.

### R-Squared (R²)

**Definition**: Proportion of variance explained by market  
**Range**: 0.0 (no explanation) to 1.0 (perfect explanation)

```php
$rSquared = $calculator->rSquared($assetReturns, $marketReturns);
```

**Interpretation**:
- **R² = 0.95**: 95% of asset variance explained by market (high systematic risk)
- **R² = 0.50**: 50% explained by market, 50% unsystematic (diversifiable)
- **R² = 0.10**: Mostly unsystematic risk (low market correlation)

### Systematic vs Unsystematic Risk

**Systematic Risk**: Market-related, cannot be diversified away  
**Unsystematic Risk**: Asset-specific, can be diversified away

```php
$metrics = $calculator->calculate($assetReturns, $marketReturns, $riskFreeRate);

echo "Systematic Risk: " . $metrics['systematic_percent'] . "%\n";
echo "Unsystematic Risk: " . $metrics['unsystematic_percent'] . "%\n";
```

**Portfolio Implications**:
- High systematic risk → Add defensive assets (low beta)
- High unsystematic risk → Add more diversification

### Treynor Ratio

**Definition**: Risk-adjusted return per unit of systematic risk  
**Formula**: `(Return - Rf) / Beta`

```php
$treynor = $calculator->treynorRatio($assetReturns, $marketReturns, $riskFreeRate);
```

**Use Case**: Compare assets with different betas  
**Interpretation**: Higher is better (more return per unit of systematic risk)

### Market Timing

Detect if manager has timing ability:

```php
$timing = $calculator->marketTiming($assetReturns, $marketReturns);
```

**Timing Coefficient**: `Beta(up markets) - Beta(down markets)`

- **Positive**: Good timing (higher beta in up markets, lower in down)
- **Negative**: Poor timing (higher beta in down markets)
- **Near Zero**: No timing ability

---

## Portfolio Risk Assessment

### Risk Score (0-100)

Composite score with four components (each 0-25 points):

```php
$analysis = $analyzer->analyzePortfolio($portfolio, $marketReturns, $riskFreeRate, $confidenceLevel);
$riskScore = $analysis['risk_score'];
```

**Components**:

1. **VaR Component** (0-25): Based on Value at Risk
   - Higher VaR → Higher score (worse)
   - Threshold: 15% VaR = 25 points

2. **Diversification Component** (0-25): Based on correlation
   - Lower diversification → Higher score (worse)
   - Threshold: 0.0 diversification = 25 points

3. **Beta Component** (0-25): Based on distance from β=1
   - Further from 1.0 → Higher score (worse)
   - Extreme betas indicate higher risk

4. **Performance Component** (0-25): Based on Sharpe ratio
   - Lower Sharpe → Higher score (worse)
   - Poor risk-adjusted returns

**Risk Ratings**:
- **0-30**: Low Risk
- **30-50**: Moderate Risk
- **50-70**: High Risk
- **70-100**: Very High Risk

### VaR Analysis

Four different VaR methodologies:

```php
$varAnalysis = $analysis['var_analysis'];
```

#### 1. Historical VaR
**Method**: Actual historical percentile  
**Pros**: No distribution assumptions  
**Cons**: Limited by historical data

#### 2. Parametric VaR
**Method**: Assumes normal distribution  
**Pros**: Simple, smooth estimates  
**Cons**: Underestimates tail risk

#### 3. Monte Carlo VaR
**Method**: Random simulation  
**Pros**: Flexible, captures complex scenarios  
**Cons**: Computationally intensive

#### 4. CVaR (Conditional VaR)
**Method**: Average loss beyond VaR  
**Pros**: Captures tail risk  
**Cons**: Requires sufficient tail data

**Typical Values**:
- **VaR (95%)**: 5-15% for diversified portfolios
- **VaR (99%)**: 10-25% for diversified portfolios
- **CVaR**: 1.3-1.5x VaR for normal distributions

### Correlation Analysis

Portfolio correlation structure:

```php
$corrAnalysis = $analysis['correlation_analysis'];
```

**Includes**:
- Full correlation matrix
- Diversification score
- Highly correlated pairs (>threshold)

**Red Flags**:
- Pairs with correlation > 0.9
- Diversification score < 0.4
- All assets positive correlated > 0.7

### Beta Analysis

Per-asset beta metrics:

```php
$betaAnalysis = $analysis['beta_analysis'];
```

**For Each Asset**:
- Beta, alpha, R²
- Systematic/unsystematic risk
- Interpretations

**Portfolio Implications**:
- Portfolio beta = weighted average of asset betas
- High beta portfolio → Higher market risk
- Negative beta assets → Hedging potential

### Performance Metrics

Risk-adjusted performance indicators:

```php
$perfMetrics = $analysis['performance_metrics'];
```

**Metrics**:

1. **Sharpe Ratio**: `(Return - Rf) / Volatility`
   - Measures return per unit of total risk
   - Typical: 0.5-2.0 for good strategies

2. **Sortino Ratio**: `(Return - Rf) / Downside Volatility`
   - Only penalizes downside volatility
   - Higher than Sharpe for asymmetric returns

3. **Treynor Ratio**: `(Return - Rf) / Beta`
   - Return per unit of systematic risk
   - Better for comparing diversified portfolios

4. **Information Ratio**: `(Return - Benchmark) / Tracking Error`
   - Measures active return per unit of active risk
   - Typical: 0.5-1.0 for skilled managers

### Recommendations

Priority-based action items:

```php
$recommendations = $analysis['recommendations'];
```

**Priority Levels**:
- **HIGH**: Immediate action required
- **MEDIUM**: Address soon
- **LOW**: Monitor or consider

**Common Recommendations**:
- Reduce position sizes (high VaR)
- Add uncorrelated assets (poor diversification)
- Reduce correlated pairs (high correlation)
- Add low-beta assets (high beta portfolio)
- Review strategy (negative alpha)
- Improve risk-adjusted returns (low Sharpe)

### Stress Testing

Test portfolio under adverse scenarios:

```php
$scenarios = [
    'market_crash' => [
        'description' => 'Market crashes 30%',
        'asset_shocks' => [
            'AAPL' => -0.30,
            'MSFT' => -0.28,
            'BND' => -0.05,
        ],
    ],
];

$stressResults = $analyzer->stressTest($portfolio, $scenarios);
```

**Results Per Scenario**:
- VaR (95%, 99%)
- Expected return
- Maximum loss
- Severity rating

**Severity Levels**:
- **Mild**: < 5% expected loss
- **Moderate**: 5-10% expected loss
- **Severe**: 10-20% expected loss
- **Extreme**: > 20% expected loss

### Risk Contribution

Decompose risk by asset:

```php
$contributions = $analyzer->riskContribution($portfolio);
```

**For Each Asset**:
- **Volatility**: Individual asset volatility
- **Marginal VaR**: Impact of small position change
- **Component VaR**: Contribution to portfolio VaR
- **Contribution %**: Percentage of total risk

**Interpretation**:
- High contribution relative to weight → Consider reducing
- Low contribution → Good diversifier
- Total contributions sum to 100%

---

## Interpretation Guides

### Correlation Interpretation

**Very Strong (0.9-1.0)**:
- Assets move almost identically
- Minimal diversification benefit
- Consider reducing overlap

**Strong (0.7-0.9)**:
- Assets move together most of the time
- Some diversification benefit
- Monitor for increasing correlation

**Moderate (0.4-0.7)**:
- Assets have some relationship
- Good diversification potential
- Acceptable for portfolio

**Weak (0.2-0.4)**:
- Assets move somewhat independently
- Good diversification
- Ideal for portfolio construction

**Very Weak (0.0-0.2)**:
- Assets move independently
- Excellent diversification
- Optimal for risk reduction

### Beta Interpretation

**High Volatility (β > 1.2)**:
- Asset amplifies market movements
- Higher potential returns and losses
- Consider for growth portfolios
- Monitor closely during downturns

**Above Market (β > 1.0)**:
- Asset moves more than market
- Moderate risk amplification
- Common for growth stocks

**Market-Like (β ≈ 1.0)**:
- Asset tracks market
- Average systematic risk
- Index funds typically have β ≈ 1.0

**Low Volatility (β < 0.8)**:
- Defensive asset
- Lower market sensitivity
- Good for conservative portfolios
- Utilities, consumer staples

**Negative Correlation (β < 0)**:
- Asset moves opposite to market
- Hedging potential
- Gold, inverse ETFs
- Rare but valuable for diversification

### Alpha Interpretation

**Strong Outperformance (α > 0.05)**:
- Significantly beats market expectations
- Skill or favorable market conditions
- Monitor sustainability

**Outperformance (α > 0.02)**:
- Beats market expectations
- Positive active management
- Continue strategy

**Market Performance (-0.02 to 0.02)**:
- Performs as expected by CAPM
- No significant alpha generation
- Consider passive alternatives

**Underperformance (α < -0.02)**:
- Below market expectations
- Review strategy effectiveness
- May indicate poor management

**Strong Underperformance (α < -0.05)**:
- Significantly below expectations
- Serious concern
- Consider exit or strategy change

### Risk Score Interpretation

**Low Risk (0-30)**:
- Well-diversified portfolio
- Moderate VaR
- Balanced beta exposure
- Good risk-adjusted returns
- **Action**: Maintain current strategy

**Moderate Risk (30-50)**:
- Acceptable risk levels
- Some areas for improvement
- Monitor key metrics
- **Action**: Address medium-priority recommendations

**High Risk (50-70)**:
- Elevated risk levels
- Multiple concerns
- Diversification or VaR issues
- **Action**: Address high-priority recommendations soon

**Very High Risk (70-100)**:
- Unacceptable risk levels
- Immediate attention required
- Multiple critical issues
- **Action**: Rebalance portfolio immediately

### Diversification Interpretation

**Excellent (0.9-1.0)**:
- Nearly uncorrelated assets
- Maximum diversification benefit
- Optimal portfolio construction

**Good (0.7-0.9)**:
- Well-diversified portfolio
- Good risk reduction
- Acceptable for most portfolios

**Moderate (0.5-0.7)**:
- Some diversification benefit
- Room for improvement
- Consider adding uncorrelated assets

**Poor (0.3-0.5)**:
- Limited diversification
- High correlation among assets
- Significant improvement needed

**Very Poor (0.0-0.3)**:
- Minimal diversification
- Assets move together
- Urgent: Add uncorrelated assets

---

## Best Practices

### 1. Data Requirements

**Minimum Data Points**:
- Correlation analysis: 20-30 observations
- Beta calculation: 30-60 observations
- VaR calculation: 100+ observations recommended
- Rolling metrics: Window size + 30 extra points

**Data Quality**:
- Use consistent time intervals (daily, weekly, monthly)
- Remove data gaps and outliers carefully
- Align dates across all assets
- Handle dividends and splits appropriately

**Historical Period**:
- Short-term (1-2 years): Reflects recent regime
- Medium-term (3-5 years): Balanced view
- Long-term (5-10 years): Includes multiple cycles

### 2. Risk Monitoring Schedule

**Real-Time** (Intraday):
- Monitor for VaR breaches
- Alert on extreme price moves (>3 std dev)
- Check position limits

**Daily**:
- Calculate portfolio VaR
- Update risk score
- Review diversification
- Check beta exposure

**Weekly**:
- Full risk analysis
- Stress testing
- Risk contribution review
- Update recommendations

**Monthly**:
- Historical performance review
- Rolling metrics analysis
- Correlation regime changes
- Strategic rebalancing

### 3. Risk Thresholds

**Recommended Alerts**:

| Metric | Alert Threshold | Action |
|--------|----------------|--------|
| VaR (95%) | > 15% | Reduce positions |
| Risk Score | > 70 | Immediate rebalancing |
| Diversification | < 0.4 | Add uncorrelated assets |
| Beta | > 1.5 or < 0.5 | Adjust market exposure |
| Correlation Pair | > 0.9 | Reduce overlap |
| Sharpe Ratio | < 0.5 | Review strategy |

### 4. Portfolio Construction

**Correlation-Based**:
- Target average correlation < 0.5
- Include negative correlation assets (gold, inverse ETFs)
- Monitor correlation stability over time

**Beta-Based**:
- Target portfolio beta based on risk tolerance
  - Conservative: β = 0.5-0.8
  - Moderate: β = 0.8-1.2
  - Aggressive: β = 1.2-1.5+
- Balance high-beta growth with low-beta defensive

**Diversification-Based**:
- Aim for diversification score > 0.7
- Include multiple asset classes
- Geographic diversification
- Sector diversification

### 5. Stress Testing

**Scenario Selection**:
- Historical crises (2008, 2020 COVID, etc.)
- Sector-specific shocks
- Interest rate changes
- Geopolitical events
- Custom scenarios based on portfolio

**Frequency**:
- Standard scenarios: Weekly
- Custom scenarios: As needed
- Regulatory scenarios: Quarterly

**Interpretation**:
- Identify vulnerable positions
- Estimate maximum drawdown
- Plan risk mitigation strategies
- Set position limits

### 6. Rebalancing Triggers

**Risk-Based**:
- Risk score exceeds threshold (>70)
- VaR breaches limit
- Diversification drops below target
- Beta drifts outside target range

**Time-Based**:
- Monthly: Minor adjustments
- Quarterly: Full rebalancing review
- Annual: Strategic allocation review

**Threshold-Based**:
- Asset weight deviates > 5% from target
- Correlation pair exceeds 0.9
- Individual asset contribution > 40% of risk

### 7. Interpretation Guidelines

**Context Matters**:
- Compare metrics to benchmarks
- Consider market regime (bull/bear)
- Account for asset class characteristics
- Adjust for investment horizon

**Multiple Perspectives**:
- Don't rely on single metric
- Use VaR + diversification + beta together
- Combine absolute and relative measures
- Historical + forward-looking

**Limitations**:
- Past performance ≠ future results
- Correlations change over time
- Normal distribution assumptions may fail
- Extreme events underrepresented

### 8. Documentation

**Record Keeping**:
- Save risk analysis results
- Document assumptions
- Track recommendation follow-up
- Log rebalancing decisions

**Reporting**:
- Executive summary for stakeholders
- Detailed metrics for risk managers
- Visual dashboards for quick assessment
- Historical trend charts

---

## Troubleshooting

### Common Issues

#### Issue: "Invalid argument exception - mismatched array lengths"

**Cause**: Asset returns and market returns have different lengths

**Solution**:
```php
// Ensure equal lengths
$assetReturns = array_slice($assetReturns, 0, count($marketReturns));
$marketReturns = array_slice($marketReturns, 0, count($assetReturns));
```

#### Issue: "Beta calculation returns NaN"

**Cause**: Zero market variance (constant market returns)

**Solution**:
```php
// Check for sufficient variance
$marketStd = sqrt(array_sum(array_map(fn($x) => $x ** 2, $marketReturns)) / count($marketReturns));
if ($marketStd < 0.0001) {
    echo "Warning: Insufficient market variance\n";
}
```

#### Issue: "Correlation returns 0 or NaN for valid data"

**Cause**: Constant returns (zero variance)

**Solution**:
```php
// Verify variance
$variance = array_sum(array_map(fn($x) => ($x - $mean) ** 2, $returns)) / count($returns);
if ($variance < 0.00001) {
    echo "Warning: Asset has near-zero variance\n";
}
```

#### Issue: "Portfolio weights don't sum to 1.0"

**Cause**: Weight normalization needed

**Solution**:
```php
// Normalize weights
$totalWeight = array_sum(array_column($portfolio, 'weight'));
foreach ($portfolio as $symbol => &$asset) {
    $asset['weight'] /= $totalWeight;
}
```

#### Issue: "VaR calculation seems too high/low"

**Possible Causes**:
1. Insufficient historical data
2. Outliers in data
3. Wrong confidence level
4. Data frequency mismatch

**Solution**:
```php
// Verify data quality
echo "Data points: " . count($returns) . "\n";
echo "Mean return: " . (array_sum($returns) / count($returns)) . "\n";
echo "Std dev: " . sqrt(array_sum(array_map(fn($x) => $x ** 2, $returns)) / count($returns)) . "\n";
echo "Min return: " . min($returns) . "\n";
echo "Max return: " . max($returns) . "\n";
```

#### Issue: "Risk score always very high"

**Cause**: Unrealistic thresholds or volatile portfolio

**Solution**:
```php
// Check individual components
$riskScore = $analysis['risk_score'];
echo "VaR component: " . $riskScore['var_component'] . "/25\n";
echo "Diversification component: " . $riskScore['diversification_component'] . "/25\n";
echo "Beta component: " . $riskScore['beta_component'] . "/25\n";
echo "Performance component: " . $riskScore['performance_component'] . "/25\n";

// Identify main contributor
$maxComponent = max([
    $riskScore['var_component'],
    $riskScore['diversification_component'],
    $riskScore['beta_component'],
    $riskScore['performance_component']
]);
echo "Main risk contributor: " . $maxComponent . "\n";
```

### Performance Optimization

#### Large Portfolios (>100 assets)

**Issue**: Slow correlation matrix calculation

**Solution**:
```php
// Calculate only needed pairs
$pairs = [['AAPL', 'MSFT'], ['GOOGL', 'AMZN']];
foreach ($pairs as [$asset1, $asset2]) {
    $corr = $correlationMatrix->pearsonCorrelation(
        $returns[$asset1],
        $returns[$asset2]
    );
}
```

#### Long Time Series (>1000 points)

**Issue**: Slow rolling calculations

**Solution**:
```php
// Use larger windows
$window = 60; // Instead of 30
$rolling = $calculator->rollingBeta($assetReturns, $marketReturns, $window);

// Or sample data
$sampledReturns = array_filter($returns, fn($k) => $k % 2 === 0, ARRAY_FILTER_USE_KEY);
```

### Data Quality Checks

```php
function validateReturns(array $returns): array
{
    $issues = [];
    
    // Check for NaN/Inf
    if (array_filter($returns, fn($x) => !is_finite($x))) {
        $issues[] = "Contains NaN or Inf values";
    }
    
    // Check for extreme outliers (>10 std dev)
    $mean = array_sum($returns) / count($returns);
    $std = sqrt(array_sum(array_map(fn($x) => ($x - $mean) ** 2, $returns)) / count($returns));
    $outliers = array_filter($returns, fn($x) => abs($x - $mean) > 10 * $std);
    if (count($outliers) > 0) {
        $issues[] = "Contains extreme outliers: " . count($outliers);
    }
    
    // Check variance
    if ($std < 0.0001) {
        $issues[] = "Near-zero variance detected";
    }
    
    // Check length
    if (count($returns) < 20) {
        $issues[] = "Insufficient data points: " . count($returns);
    }
    
    return $issues;
}
```

### Getting Help

**Resources**:
- GitHub Issues: https://github.com/ksfraser/WealthSystem/issues
- Documentation: `/docs/Risk_Analysis_Guide.md`
- Examples: `/examples/risk_analysis_examples.php`
- Tests: `/tests/Risk/*Test.php`

**When Reporting Issues**:
1. PHP version and environment
2. Sample data causing issue (anonymized)
3. Full error message and stack trace
4. Expected vs actual behavior
5. Steps to reproduce

---

## Advanced Topics

### Custom Risk Metrics

Extend RiskAnalyzer with custom metrics:

```php
class CustomRiskAnalyzer extends RiskAnalyzer
{
    public function calculateDrawdown(array $returns): float
    {
        $cumReturns = [];
        $cumReturn = 1.0;
        foreach ($returns as $return) {
            $cumReturn *= (1 + $return);
            $cumReturns[] = $cumReturn;
        }
        
        $maxDrawdown = 0;
        $peak = $cumReturns[0];
        foreach ($cumReturns as $value) {
            if ($value > $peak) {
                $peak = $value;
            }
            $drawdown = ($peak - $value) / $peak;
            $maxDrawdown = max($maxDrawdown, $drawdown);
        }
        
        return $maxDrawdown;
    }
}
```

### Multi-Factor Models

Extend beyond single-factor CAPM:

```php
// Fama-French 3-factor model
function calculateFamaFrench(
    array $assetReturns,
    array $marketReturns,
    array $smbReturns,  // Small minus big
    array $hmlReturns,  // High minus low
    float $riskFreeRate
): array {
    // Multi-factor regression
    // Returns: alpha, beta_market, beta_smb, beta_hml, r_squared
}
```

### Machine Learning Integration

Use risk metrics for ML models:

```php
$features = [
    'beta' => $metrics['beta'],
    'alpha' => $metrics['alpha'],
    'correlation' => $matrix[$asset1][$asset2],
    'diversification' => $divScore['score'],
    'var_95' => $varAnalysis['historical_var_95'],
    'sharpe' => $perfMetrics['sharpe_ratio'],
];

// Feed to ML model for predictions
```

---

## Appendix

### Statistical Formulas

**Pearson Correlation**:
```
r = Σ((x - x̄)(y - ȳ)) / sqrt(Σ(x - x̄)² * Σ(y - ȳ)²)
```

**Beta**:
```
β = Cov(Asset, Market) / Var(Market)
  = Σ((Ra - R̄a)(Rm - R̄m)) / Σ(Rm - R̄m)²
```

**Alpha**:
```
α = Ra - [Rf + β(Rm - Rf)]
```

**R-Squared**:
```
R² = (Correlation(Asset, Market))²
```

**Sharpe Ratio**:
```
Sharpe = (Ra - Rf) / σa
```

**Treynor Ratio**:
```
Treynor = (Ra - Rf) / β
```

**VaR (Parametric)**:
```
VaR = μ + σ * z(α)
where z(0.95) = 1.645, z(0.99) = 2.326
```

### Glossary

- **Alpha (α)**: Excess return beyond CAPM prediction
- **Beta (β)**: Systematic risk measure
- **CAPM**: Capital Asset Pricing Model
- **Correlation**: Statistical relationship between two variables
- **CVaR**: Conditional Value at Risk (average loss beyond VaR)
- **Diversification**: Risk reduction through uncorrelated assets
- **Kurtosis**: Measure of tail thickness
- **Monotonic**: Consistently increasing or decreasing relationship
- **R-Squared (R²)**: Proportion of variance explained
- **Rank Correlation**: Correlation based on ranks, not values
- **Risk-Free Rate**: Return on zero-risk investment (e.g., T-bills)
- **Sharpe Ratio**: Risk-adjusted return metric
- **Skewness**: Measure of asymmetry in distribution
- **Sortino Ratio**: Like Sharpe, but only penalizes downside
- **Systematic Risk**: Market-related risk (cannot diversify away)
- **Treynor Ratio**: Return per unit of systematic risk
- **Unsystematic Risk**: Asset-specific risk (can diversify away)
- **VaR**: Value at Risk (loss threshold at confidence level)
- **Volatility**: Standard deviation of returns

---

**Version**: 1.0  
**Last Updated**: December 7, 2025  
**Author**: WealthSystem Development Team  
**License**: MIT
