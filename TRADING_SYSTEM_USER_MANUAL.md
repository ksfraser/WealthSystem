# Trading Strategy System - User Manual
**Version 1.0**  
**December 2, 2025**

---

## Table of Contents

1. [Introduction](#introduction)
2. [Getting Started](#getting-started)
3. [Understanding Trading Strategies](#understanding-trading-strategies)
4. [Running Strategy Analysis](#running-strategy-analysis)
5. [Portfolio Analysis with Multiple Strategies](#portfolio-analysis)
6. [Backtesting Strategies](#backtesting)
7. [Performance Analysis](#performance-analysis)
8. [Advanced Features](#advanced-features)
9. [Configuration & Customization](#configuration)
10. [Troubleshooting](#troubleshooting)
11. [FAQ](#faq)
12. [Glossary](#glossary)

---

## Introduction

### What is the Trading Strategy System?

The Trading Strategy System is a comprehensive platform for analyzing stocks using multiple professional trading strategies. It helps you:

- **Analyze stocks** using 6 distinct trading strategies
- **Get actionable recommendations** (BUY, SELL, or HOLD)
- **Backtest strategies** on historical data
- **Compare performance** across different approaches
- **Optimize portfolio allocation** using multiple strategies

### Who Should Use This System?

- **Individual Traders**: Looking for data-driven trading decisions
- **Portfolio Managers**: Managing multi-strategy portfolios
- **Quantitative Analysts**: Backtesting and optimizing strategies
- **Financial Advisors**: Researching investment opportunities
- **Students**: Learning about systematic trading approaches

### System Requirements

- **PHP**: 8.4 or higher
- **Database**: SQLite (included) or MySQL
- **Web Browser**: Chrome, Firefox, Safari, or Edge (latest versions)
- **Internet**: Required for market data fetching
- **Disk Space**: Minimum 500MB for data storage

---

## Getting Started

### Installation

#### Quick Start (5 Minutes)

```bash
# 1. Clone the repository
git clone https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment.git
cd ChatGPT-Micro-Cap-Experiment/Stock-Analysis

# 2. Install dependencies
composer install

# 3. Initialize database
php scripts/initialize_database.php

# 4. Load sample data (optional)
php scripts/load_sample_data.php

# 5. Start the system
php -S localhost:8000
```

Now open your browser to `http://localhost:8000`

#### Detailed Installation

See the [Installation Guide](INSTALLATION.md) for:
- Custom database configuration
- Production deployment
- Advanced setup options

### First-Time Configuration

1. **Set Up Market Data Source**
   - The system uses Yahoo Finance API by default
   - No API key required
   - Data is cached locally for performance

2. **Configure Strategies**
   - Default parameters are optimized for most use cases
   - Advanced users can customize in `Settings > Strategy Parameters`

3. **Create Your First Analysis**
   - Navigate to `Analysis > Single Strategy`
   - Enter a stock symbol (e.g., "AAPL")
   - Click "Analyze"

---

## Understanding Trading Strategies

The system includes 6 professional trading strategies:

### 1. **Small Cap Catalyst Strategy** 🚀

**What it does**: Identifies small-cap stocks with upcoming catalysts (events that could move the price).

**Best for**:
- High-growth opportunities
- Event-driven trading
- Higher risk tolerance

**Key Indicators**:
- Market cap: $50M - $2B
- Catalyst events (earnings, FDA approval, product launches)
- Risk/reward ratio minimum 3:1
- Liquidity requirements

**When to use**:
- Looking for high-potential opportunities
- Willing to accept higher volatility
- Have time to monitor positions

**Example**: A biotech company with FDA approval pending for a new drug.

---

### 2. **IPlace Strategy** 📊

**What it does**: Follows analyst upgrades and institutional interest.

**Best for**:
- Following the "smart money"
- Lower risk than small-cap catalyst
- Medium-term holdings

**Key Indicators**:
- Analyst upgrades
- Price target increases
- Institutional ownership changes
- Momentum following upgrades

**When to use**:
- Looking for institutional validation
- Want data-backed confidence
- Prefer established companies

**Example**: A stock just upgraded to "Buy" by 3 major analysts with price targets 20% above current price.

---

### 3. **Mean Reversion Strategy** ↩️

**What it does**: Buys oversold stocks expecting them to return to average prices.

**Best for**:
- Short-term trading (days to weeks)
- Stocks with established trading ranges
- Lower risk, frequent trades

**Key Indicators**:
- Bollinger Bands (20-day, 2 standard deviations)
- RSI < 30 (oversold)
- Volume confirmation (1.5x average)
- Support level bounces

**When to use**:
- Market is range-bound (not trending)
- Stock has temporary weakness
- Looking for quick profits

**Example**: A quality stock drops 15% on market panic but fundamentals unchanged.

---

### 4. **Quality Dividend Strategy** 💰

**What it does**: Finds high-quality dividend stocks with sustainable payouts.

**Best for**:
- Income generation
- Long-term holdings
- Conservative investors
- Retirement portfolios

**Key Indicators**:
- Dividend yield: 2.5% - 10%
- 5+ years of dividend growth
- Payout ratio < 65%
- Free cash flow coverage > 1.2x
- ROE > 12%

**When to use**:
- Building income stream
- Seeking stability
- Long-term investment horizon
- Want to reduce volatility

**Example**: A utility company with 25 years of consecutive dividend increases and strong cash flow.

---

### 5. **Momentum Quality Strategy** 📈

**What it does**: Combines strong price momentum with quality fundamentals.

**Best for**:
- Riding trends
- Growth stocks
- Medium-term holdings (weeks to months)

**Key Indicators**:
- 50/200 MA golden cross
- 3-month return > 10%
- 6-month return > 15%
- ROE > 15%
- Profit margin > 10%
- Revenue growth > 10%

**When to use**:
- Market is trending upward
- Looking for growth stocks
- Want quality plus momentum
- Medium risk tolerance

**Example**: A tech company showing accelerating revenue growth with strong price momentum.

---

### 6. **Contrarian Strategy** 🎯

**What it does**: Buys quality stocks during market panic.

**Best for**:
- Buying fear, selling greed
- Value investing
- Patience required
- Long-term perspective

**Key Indicators**:
- 20%+ drawdown from highs
- RSI < 30 (oversold)
- Volume surge (1.8x average)
- Strong fundamentals (quality score > 65%)
- Insider buying

**When to use**:
- Market panic or selloff
- Stock fundamentals remain strong
- Have patience for recovery
- Want to buy "on sale"

**Example**: A strong company drops 30% due to market-wide panic, but earnings and cash flow remain solid.

---

## Running Strategy Analysis

### Single Strategy Analysis

#### Step-by-Step Guide

**1. Navigate to Analysis**
```
Dashboard > Analysis > Single Strategy
```

**2. Select Strategy**
- Choose from dropdown: Mean Reversion, Momentum Quality, etc.
- Hover over strategy name for quick description

**3. Enter Stock Symbol**
```
Symbol: AAPL
Date: Today (or select specific date)
```

**4. Click "Analyze"**
- Analysis completes in 2-5 seconds
- Results display automatically

#### Understanding the Results

**Action**: BUY, SELL, or HOLD
- **BUY**: Strategy signals entry point
- **SELL**: Strategy signals exit point
- **HOLD**: No strong signal, maintain current position

**Confidence**: 0-100%
- **80-100%**: High confidence, strong signal
- **60-79%**: Moderate confidence, reasonable signal
- **40-59%**: Low confidence, weak signal
- **0-39%**: Very low confidence, avoid acting

**Reasoning**: Why the action was recommended
- Plain English explanation
- Key factors that influenced decision
- Warnings or caveats

**Metrics**: Technical indicators used
- Strategy-specific calculations
- Current values vs. thresholds
- Historical context

#### Example Result

```
Symbol: AAPL
Strategy: Mean Reversion
Date: 2024-12-02

ACTION: BUY
Confidence: 85%

Reasoning:
Stock is oversold with RSI at 28 (below 30 threshold). Price touched 
lower Bollinger Band at $170.50, 8% below 20-day moving average. 
Volume confirmation present with 1.7x average volume. Bullish RSI 
divergence detected - price making lower lows while RSI making higher 
lows, suggesting reversal likely.

Metrics:
- Current Price: $170.50
- RSI: 28 (Oversold < 30)
- Bollinger Band Position: -2.1 (below lower band)
- Distance from Mean: -8.2%
- Volume Multiple: 1.7x
- Mean Reversion Score: 82%

Recommendation: Strong BUY signal for mean reversion trade. 
Target: $185 (middle band). Stop Loss: $162 (5% below entry).
```

### Quick Analysis Tips

✅ **DO**:
- Analyze during market hours for real-time data
- Consider multiple strategies for same stock
- Read the reasoning carefully
- Check confidence level before acting
- Use stop losses

❌ **DON'T**:
- Act on low confidence signals (<60%)
- Ignore risk management warnings
- Trade without understanding the strategy
- Use only one data point
- Forget about market conditions

---

## Portfolio Analysis

### Multi-Strategy Consensus Analysis

Use all 6 strategies simultaneously to get a consensus recommendation.

#### How It Works

1. **Select Allocation Profile**
   - **Conservative**: Favors dividend and mean reversion (lower risk)
   - **Balanced**: Equal weight across all strategies
   - **Aggressive**: Favors small-cap and momentum (higher risk)
   - **Growth**: Emphasizes momentum and upgrades
   - **Value**: Focuses on contrarian and dividend

2. **Enter Stock Symbol**

3. **Review Consensus**
   - Weighted average of all strategy recommendations
   - Individual strategy results available
   - Confidence calculated based on agreement

#### Example: Portfolio Analysis

```
Symbol: TSLA
Profile: Balanced
Date: 2024-12-02

CONSENSUS: BUY
Weighted Confidence: 72%

Individual Results:
┌────────────────────┬────────┬────────────┬────────────┐
│ Strategy           │ Action │ Confidence │ Weight     │
├────────────────────┼────────┼────────────┼────────────┤
│ SmallCapCatalyst   │ HOLD   │ 45%        │ 15% (6.8%) │
│ IPlace             │ BUY    │ 78%        │ 15% (11.7%)│
│ MeanReversion      │ HOLD   │ 52%        │ 15% (7.8%) │
│ QualityDividend    │ SELL   │ 35%        │ 20% (7.0%) │
│ MomentumQuality    │ BUY    │ 88%        │ 20% (17.6%)│
│ Contrarian         │ BUY    │ 82%        │ 15% (12.3%)│
└────────────────────┴────────┴────────────┴────────────┘

Consensus Reasoning:
Majority of strategies (3 of 6) recommend BUY with strong confidence. 
Momentum Quality and Contrarian strategies show highest confidence. 
Quality Dividend signals caution due to low yield. Overall signal 
strength is MODERATE due to some disagreement.

Recommendation: BUY with moderate confidence. Consider position sizing 
at 10-15% of portfolio rather than full 20% due to mixed signals.
```

### Comparing Allocation Profiles

See how different risk profiles affect recommendations:

```
Dashboard > Analysis > Profile Comparison
```

**Use case**: Understand how your risk tolerance affects recommendations.

---

## Backtesting

### What is Backtesting?

Backtesting runs a strategy on historical data to see how it would have performed. It answers: "If I had used this strategy in the past, would I have made money?"

### Running a Backtest

#### Basic Backtest (Single Strategy)

**1. Navigate to Backtesting**
```
Dashboard > Backtesting > Single Strategy
```

**2. Configure Backtest**

```
Strategy: Momentum Quality
Symbol: NVDA
Start Date: 2023-01-01
End Date: 2023-12-31

Parameters:
- Initial Capital: $100,000
- Position Size: 10% per trade
- Fixed Stop Loss: 10%
- Trailing Stop: Enabled (10% trailing, activates at 5% gain)
- Partial Profit Taking: Enabled (25% at 10%, 50% at 20%, 100% at 30%)
- Take Profit: 20%
- Max Holding Days: 30

Commission: 0.1% per trade
Slippage: 0.05% per trade
```

**3. Run Backtest**
- Click "Run Backtest"
- Wait 10-30 seconds for completion
- Review results

#### Understanding Backtest Results

**Key Metrics Explained**:

**Total Return**: Overall profit/loss
```
Example: 45.2% means $100,000 → $145,200
```

**Win Rate**: Percentage of profitable trades
```
Example: 65% means 13 winners out of 20 trades
```

**Profit Factor**: Gross profits ÷ gross losses
```
> 2.0 = Excellent
1.5 - 2.0 = Good
1.0 - 1.5 = Acceptable
< 1.0 = Losing strategy
```

**Sharpe Ratio**: Risk-adjusted returns
```
> 2.0 = Excellent
1.0 - 2.0 = Good
0.5 - 1.0 = Acceptable
< 0.5 = Poor
```

**Max Drawdown**: Largest peak-to-trough decline
```
Example: -15% means worst drop from peak to bottom was 15%
```

**Average Win/Loss**: Mean profit per winning/losing trade
```
Want: Avg Win > Avg Loss (ideally 2x or more)
```

#### Example Backtest Result

```
=== BACKTEST RESULTS ===
Symbol: NVDA
Strategy: Momentum Quality
Period: 2023-01-01 to 2023-12-31
Initial Capital: $100,000

Performance Summary:
- Total Return: +58.3% ($58,300 profit)
- Final Equity: $158,300
- Number of Trades: 28
- Win Rate: 67.9% (19 wins, 9 losses)
- Profit Factor: 2.4 (Excellent)
- Sharpe Ratio: 1.8 (Good)
- Max Drawdown: -12.5%

Trade Statistics:
- Average Win: +8.2% ($4,920)
- Average Loss: -4.1% ($2,460)
- Largest Win: +24.5% ($14,700)
- Largest Loss: -9.8% ($5,880)
- Average Holding Period: 18 days
- Best Month: March 2023 (+18.2%)
- Worst Month: August 2023 (-5.3%)

Risk Metrics:
- Volatility (Annual): 28.5%
- Risk-Adjusted Return: 2.04x
- Recovery Time (Max DD): 42 days
- Consecutive Losses (Max): 3

Assessment: STRONG PERFORMANCE
Strategy shows good risk-adjusted returns with acceptable drawdown. 
Win rate above 65% with favorable win/loss ratio. Profit factor 
above 2.0 indicates robust edge. Recommended for live trading with 
suggested position sizing: 10-12% per trade.
```

### Portfolio Backtesting

Test multiple strategies simultaneously.

```
Dashboard > Backtesting > Portfolio
```

**Configuration**:
```
Allocation Profile: Balanced
Symbols: AAPL, MSFT, GOOGL, NVDA, AMD
Period: 2023-01-01 to 2024-12-31
Max Positions: 5
Position Size: 15% per position
Rebalance: Every 30 days
```

**Results Include**:
- Portfolio-level performance
- Strategy breakdown (which strategies contributed most)
- Diversification benefit
- Comparison to buy-and-hold

### Walk-Forward Analysis

**What it does**: Tests strategy robustness by training on one period and testing on another.

**Why it matters**: Prevents overfitting. If strategy works well on out-of-sample data, it's more reliable.

```
Dashboard > Backtesting > Walk-Forward
```

**Configuration**:
```
Training Period: 60 days
Testing Period: 30 days
Step Size: 30 days (moving window)
```

**Interpretation**:
- **Good**: Testing performance close to training performance
- **Bad**: Large gap between training and testing (overfitting)

---

## Performance Analysis

### Comparing Strategies

See which strategy performs best.

```
Dashboard > Performance > Strategy Comparison
```

**Features**:
- Side-by-side metrics
- Equity curve comparison
- Statistical significance tests
- Best/worst case scenarios

### Strategy Correlation

Understand which strategies behave similarly.

```
Dashboard > Performance > Correlation Analysis
```

**Use case**: Build diversified portfolio by choosing strategies with low correlation.

**Correlation Guide**:
```
0.8 - 1.0: Highly correlated (move together)
0.5 - 0.8: Moderately correlated
0.0 - 0.5: Low correlation (good for diversification)
< 0.0: Negative correlation (excellent for diversification)
```

### Optimal Allocation Finder

Let the system recommend strategy allocations.

```
Dashboard > Performance > Optimal Allocation
```

**Input Your Goals**:
```
Return Weight: 30% (how much you care about returns)
Sharpe Ratio Weight: 50% (how much you care about risk-adjusted returns)
Drawdown Weight: 20% (how much you want to avoid losses)
```

**Output**:
```
Recommended Allocations:
- Momentum Quality: 30%
- Mean Reversion: 25%
- Quality Dividend: 20%
- Contrarian: 15%
- IPlace: 10%
- SmallCapCatalyst: 0%

Expected Performance:
- Annual Return: 28.5%
- Sharpe Ratio: 1.9
- Max Drawdown: -14.2%
```

---

## Advanced Features

### Sector & Industry Analysis

**NEW FEATURE**: Compare stock performance against sector benchmarks and peer companies.

#### What is Sector Analysis?

Sector analysis helps you understand how a stock performs relative to its industry peers and sector average. This context is critical for determining if a stock's gains are due to company-specific factors or broader sector trends.

**Why it matters**:
- A stock up 20% in a sector up 25% is actually **underperforming**
- A stock down 5% in a sector down 15% is **outperforming**
- Sector rotation can signal market regime changes

#### GICS Sector Classification

Stocks are categorized using the **Global Industry Classification Standard (GICS)**:

| Sector Code | Sector Name | Examples |
|------------|-------------|----------|
| 10 | Energy | XOM, CVX, SLB |
| 15 | Materials | DOW, LYB, NEM |
| 20 | Industrials | BA, CAT, UNP |
| 25 | Consumer Discretionary | AMZN, TSLA, HD |
| 30 | Consumer Staples | PG, KO, WMT |
| 35 | Health Care | JNJ, UNH, PFE |
| 40 | Financials | JPM, BAC, GS |
| 45 | Information Technology | AAPL, MSFT, NVDA |
| 50 | Communication Services | GOOGL, META, NFLX |
| 55 | Utilities | NEE, DUK, SO |
| 60 | Real Estate | AMT, PLD, SPG |

#### Classifying Stocks by Sector

**In the System**:
```
Dashboard > Analysis > Sector Classification

Enter Symbol: NVDA
```

**Example Output**:
```
Symbol: NVDA
Sector: Information Technology (Code: 45)
Industry: Semiconductors
Market Cap: $2.1T
Classification: GICS
```

#### Comparing Stock vs Sector Performance

**What it does**: Shows if your stock is outperforming or underperforming its sector.

**How to use**:
```
Dashboard > Analysis > Sector Comparison

Symbol: NVDA
Period: Q1 2024 (Jan 1 - Mar 31)
```

**Example Results**:
```
Stock Performance (NVDA):
- Total Return: +82.5%
- Volatility: 35.2%
- Max Drawdown: -12.3%

Sector Performance (Information Technology):
- Sector Return: +18.4%
- Constituents: 75 stocks
- Market Cap Weight: 28.5%

Relative Performance:
- Outperformance: +64.1%
- Percentile Rank: 98th
- Status: Significantly outperforming sector

Interpretation:
✅ NVDA is in the top 2% of tech stocks
✅ Company-specific drivers (AI boom)
✅ Not just riding sector trend
```

**Another Example (Underperformance)**:
```
Stock: XOM (Energy)
- Stock Return: +3.2%
- Sector Return: +12.8%
- Relative Performance: -9.6%
- Status: Underperforming sector

Interpretation:
⚠️ Energy sector is strong, but XOM lagging
⚠️ Consider investigating company-specific issues
⚠️ May indicate operational problems
```

#### Sector Peer Ranking

**What it does**: Ranks all stocks in a sector by performance.

**How to use**:
```
Dashboard > Analysis > Sector Rankings

Sector: Information Technology
Period: 90 days
```

**Example Output**:
```
Rank | Symbol | Return | Volatility | Sharpe
-----|--------|--------|------------|-------
  1  | NVDA   | +82.5% |   35.2%   |  2.34
  2  | AMD    | +54.3% |   42.1%   |  1.29
  3  | AVGO   | +38.7% |   28.5%   |  1.36
  4  | MSFT   | +22.1% |   18.9%   |  1.17
  5  | AAPL   | +15.3% |   16.2%   |  0.94
 ...
 73  | HPQ    | -3.2%  |   22.5%   | -0.14
 74  | CSCO   | -5.8%  |   19.8%   | -0.29
 75  | IBM    | -8.4%  |   24.1%   | -0.35

Your Holdings:
- NVDA: Rank #1 (Top 1.3%)
- MSFT: Rank #4 (Top 5.3%)
```

#### Sector Rotation Detection

**What it is**: Identifying which sectors are gaining or losing momentum.

**Why it matters**:
- Early sign of market regime changes
- Helps rebalance portfolio allocation
- Indicates economic cycle shifts

**How to use**:
```
Dashboard > Analysis > Sector Rotation

Lookback Period: 30 days
```

**Example Output**:
```
Sector Leaders (Past 30 Days):
1. Information Technology: +12.5% (strong uptrend)
2. Communication Services: +8.3% (uptrend)
3. Consumer Discretionary: +5.1% (uptrend)

Sector Laggards (Past 30 Days):
1. Energy: -7.8% (strong downtrend)
2. Utilities: -5.2% (downtrend)
3. Consumer Staples: -3.5% (downtrend)

Rotation Status: ✅ ROTATION DETECTED
- Spread: 20.3% (Tech to Energy)
- Interpretation: Strong rotation into growth sectors

Investment Implications:
✅ Growth > Value rotation
✅ Favor tech, communication, discretionary
⚠️ Reduce defensive positions (utilities, staples)
⚠️ Energy weakness may indicate economic slowdown concerns
```

#### Relative Strength Analysis

**What it is**: Measures how stock performs relative to its sector over time.

**Relative Strength Ratio = Stock Return / Sector Return**

**How to interpret**:
- **RS Ratio > 1.5**: Significantly outperforming
- **RS Ratio 1.1-1.5**: Outperforming  
- **RS Ratio 0.9-1.1**: In line with sector
- **RS Ratio 0.5-0.9**: Underperforming
- **RS Ratio < 0.5**: Significantly underperforming

**Example**:
```
Symbol: JPM (Financials)
Period: 90 days

Stock Return: +24.0%
Sector Return: +12.0%
RS Ratio: 2.0 (24% / 12%)

Interpretation: Significantly outperforming sector
- Company-specific catalysts strong
- Consider maintaining/increasing position
```

#### Practical Usage Examples

**Scenario 1: Evaluating a Buy Signal**
```
Strategy says: BUY NVDA (80% confidence)

Check Sector Analysis:
- NVDA Return: +82% (90 days)
- Tech Sector: +18%
- RS Ratio: 4.56 (exceptional outperformance)
- Sector Trend: Strong uptrend
- Sector Rank: #1 out of 75

Decision: 
✅ Strong BUY - Both stock AND sector showing strength
✅ Momentum likely to continue
✅ Sector tailwinds supporting individual performance
```

**Scenario 2: Warning Sign**
```
Strategy says: BUY XYZ (75% confidence)

Check Sector Analysis:
- XYZ Return: +5%
- Sector Return: +22%
- RS Ratio: 0.23 (significantly underperforming)
- Sector Rank: #68 out of 70

Decision:
⚠️ CAUTION - Stock lagging its strong sector
⚠️ Investigate why stock is weak when peers are strong
⚠️ May indicate company-specific problems
→ Reduce confidence or skip trade
```

**Scenario 3: Sector Rotation Trade**
```
Sector Rotation shows:
- Energy dropping from leader to laggard
- Tech rising from laggard to leader

Action:
1. Reduce energy positions
2. Increase tech exposure
3. Update portfolio allocations:
   - Tech: 25% → 35%
   - Energy: 15% → 5%
```

#### Best Practices

**✅ DO**:
- Always check sector context before trading
- Use sector rotation to time entries/exits
- Compare multiple stocks in same sector
- Track relative strength trends over time
- Rebalance when rotation is detected

**❌ DON'T**:
- Ignore sector trends when evaluating stocks
- Buy a "strong" stock in a collapsing sector
- Overlook outperformers in weak sectors (they're rare gems)
- Trade without understanding sector dynamics
- Assume sector trends continue forever

#### Integration with Trading Strategies

Each strategy now includes sector analysis:

**Momentum Quality Strategy + Sector Analysis**:
```
Before: Buy stock with strong momentum
After: Buy stock with strong momentum in strong sector
→ Higher win rate, better risk-adjusted returns
```

**Mean Reversion Strategy + Sector Analysis**:
```
Before: Buy oversold stock
After: Buy oversold stock in neutral/strong sector
→ Avoid catching falling knives in collapsing sectors
```

**Contrarian Strategy + Sector Analysis**:
```
Before: Buy panic-sold stock
After: Buy panic-sold stock if sector fundamentals intact
→ Distinguish temporary panic from permanent decline
```

---

### Index Benchmarking & Market Comparison

**Compare Your Stocks Against Major Market Indexes**

The system tracks and compares performance against major market benchmarks to measure alpha (excess returns), beta (market sensitivity), and relative strength.

#### Supported Market Indexes

| Index Symbol | Index Name | Description | Constituents |
|-------------|------------|-------------|--------------|
| **SPY** | S&P 500 | Large-cap US stocks | 500 companies |
| **QQQ** | NASDAQ 100 | Technology-focused index | 100 companies |
| **DIA** | Dow Jones Industrial Average | Blue-chip US stocks | 30 companies |
| **IWM** | Russell 2000 | Small-cap US stocks | 2000 companies |

#### Stock vs Index Comparison

Compare individual stock performance to relevant market benchmarks:

```php
$comparison = $indexService->compareToIndex('NVDA', 'SPY', '2024-01-01', '2024-12-01');
```

**Example Output**:
```
Stock: NVDA
Index: S&P 500 (SPY)
Period: 90 days

Stock Performance:
- Total Return: +82.5%
- Volatility: 38.2% (annualized)
- Sharpe Ratio: 2.16

Index Performance:
- Total Return: +18.3%
- Volatility: 14.5%
- Sharpe Ratio: 1.26

Comparative Metrics:
- Alpha (Annualized): +64.2% ✅ Exceptional excess return
- Beta: 1.85 (85% more volatile than market)
- Correlation: 0.72 (strong positive relationship)
- Tracking Error: 22.1% (significant divergence)
- Excess Return: +64.2%
- Information Ratio: 2.91 (excellent risk-adjusted outperformance)

Interpretation: NVDA significantly outperformed S&P 500
→ Strong alpha generation
→ Higher volatility than market (beta 1.85)
→ Consider appropriate position sizing
```

#### Understanding Alpha & Beta

**Alpha (α)**: Excess return beyond what the market delivered
- **Positive Alpha**: Outperformance (e.g., +15% alpha = beat market by 15%)
- **Negative Alpha**: Underperformance (e.g., -8% alpha = lagged market by 8%)
- **Zero Alpha**: Matched market returns

**Beta (β)**: Sensitivity to market movements
- **β = 1.0**: Moves in line with market
- **β > 1.0**: More volatile than market (e.g., β=1.5 = 50% more volatile)
- **β < 1.0**: Less volatile than market (e.g., β=0.7 = 30% less volatile)
- **β ≈ 0**: Uncorrelated with market

**Example Interpretations**:
```
Stock A: Alpha = +12%, Beta = 1.5
→ Great returns BUT higher risk
→ Good for aggressive portfolios
→ Expect larger swings

Stock B: Alpha = +5%, Beta = 0.8
→ Modest outperformance with lower risk
→ Good for conservative portfolios
→ Smoother ride

Stock C: Alpha = -3%, Beta = 1.2
→ Underperformance with higher risk
→ ⚠️ Worst of both worlds
→ Likely candidate for replacement
```

#### Index Membership Detection

The system can estimate if a stock is likely included in major indexes:

```php
$membership = $indexService->isLikelyInIndex('AAPL', 'SPY');
```

**Example Output**:
```
Symbol: AAPL
Index: S&P 500

Likely Member: ✅ YES
Confidence: 95%
Reason: Large-cap stock with $3.0T market cap exceeds S&P 500 threshold ($10B+)

Market Cap: $3,000,000,000,000
Sector: Information Technology
```

**Detection Criteria**:
- **S&P 500**: Market cap > $10B, US domiciled, high liquidity
- **NASDAQ 100**: Market cap > $10B, tech/growth focus, NASDAQ-listed
- **Dow Jones**: Market cap > $100B, blue-chip reputation
- **Russell 2000**: Market cap $300M - $5B (small-cap range)

#### Correlation Analysis

Understand how closely your stock moves with the market:

**Correlation Scale**:
- **+1.0**: Perfect positive correlation (moves identically)
- **+0.7 to +1.0**: Strong positive relationship
- **+0.3 to +0.7**: Moderate positive relationship
- **-0.3 to +0.3**: Weak/no relationship
- **-0.7 to -0.3**: Moderate negative relationship
- **-1.0 to -0.7**: Strong negative relationship

**Example**:
```
NVDA vs S&P 500:
- Correlation: 0.72
- Interpretation: Strong positive correlation
- Meaning: When S&P 500 rises, NVDA usually rises (but more volatile)
- Use case: NVDA captures market uptrends with amplification

GLD (Gold ETF) vs S&P 500:
- Correlation: -0.15
- Interpretation: Weak negative correlation
- Meaning: Gold moves independently of stocks
- Use case: Portfolio diversification benefit
```

#### Information Ratio & Sharpe Ratio

**Information Ratio**: Measures consistency of alpha generation
```
Information Ratio = Excess Return / Tracking Error

High IR (>1.0): Consistent outperformance with controlled risk
Low IR (<0.5): Inconsistent or risky outperformance
Negative IR: Consistent underperformance
```

**Sharpe Ratio**: Risk-adjusted returns
```
Sharpe Ratio = (Return - Risk-free Rate) / Volatility

Excellent: >2.0
Very Good: 1.5-2.0
Good: 1.0-1.5
Fair: 0.5-1.0
Poor: <0.5
```

#### Practical Usage Examples

**Scenario 1: Portfolio Benchmark Selection**
```
Analyzing portfolio of 10 stocks

Step 1: Compare to multiple indexes
- vs S&P 500: Beta 1.2, Alpha +5%
- vs Russell 2000: Beta 0.9, Alpha +12%
- vs NASDAQ 100: Beta 1.1, Alpha +3%

Step 2: Choose best benchmark
→ Russell 2000 is best fit (lowest beta, highest alpha)
→ Portfolio is small-cap focused
```

**Scenario 2: Risk Assessment**
```
Stock XYZ Analysis:
- Return: +45% (90 days)
- vs S&P 500: Beta 2.1, Alpha +25%

Interpretation:
⚠️ Stock is 2.1x more volatile than market
→ Position sizing: Reduce by 50% vs normal
→ Set tighter stop losses
→ Expect large daily swings
✅ But generating strong alpha (+25%)
```

**Scenario 3: Performance Attribution**
```
Stock returned +30% while S&P 500 returned +10%

Question: Was this skill or just market beta?

Answer:
- Beta: 1.5
- Expected return from market: 10% × 1.5 = 15%
- Actual return: 30%
- Alpha: 30% - 15% = +15%

Conclusion: 
- 15% from market exposure (beta)
- 15% from stock-specific alpha ✅
→ Manager added real value
```

**Scenario 4: Index Fund Selection**
```
Want to add index exposure to portfolio

Current holdings:
- All large-cap tech stocks
- High correlation with NASDAQ 100

Decision:
❌ Don't add QQQ (would increase concentration)
✅ Add IWM (Russell 2000) for small-cap diversification
✅ Or add DIA (Dow) for blue-chip value exposure
```

#### Best Practices

**✅ DO**:
- Compare every stock to relevant benchmark
- Use beta for position sizing (lower beta = larger position)
- Track alpha consistency over time
- Choose benchmark that matches portfolio style
- Rebalance when correlations shift dramatically

**❌ DON'T**:
- Compare small-cap stocks to S&P 500 (use Russell 2000)
- Ignore beta when calculating position sizes
- Chase high returns without considering risk-adjusted metrics
- Use single benchmark for diversified portfolio
- Assume past correlations persist indefinitely

#### Integration with Strategies

**Momentum Quality + Index Benchmarking**:
```
Before: Buy stock with strong momentum
After: Buy stock with positive alpha AND momentum
→ Avoid stocks riding market beta without skill
```

**Mean Reversion + Index Benchmarking**:
```
Before: Buy oversold stock
After: Buy stock with positive alpha that's temporarily oversold
→ Focus on quality stocks having bad days
```

**Risk Management + Index Benchmarking**:
```
Position size based on beta:
- Beta 0.8: Standard position
- Beta 1.5: Reduce position by 40%
- Beta 2.0: Reduce position by 50%
→ Maintain consistent portfolio volatility
```

---

### Risk Management: Trailing Stops & Profit Taking

**NEW FEATURE**: Lock in profits automatically as your positions gain value.

#### Trailing Stop Loss

**What it is**: A dynamic stop loss that adjusts upward as the price rises, but never moves downward.

**How it works**:
```
1. Buy stock at $100
2. Set 10% trailing stop (trail distance)
3. Set 5% activation threshold
4. Price rises to $106 → Trailing activates
5. Stop is set at $95.40 (10% below $106)
6. Price rises to $120 → Stop moves to $108 (10% below $120)
7. Price falls to $115 → Stop stays at $108
8. Price falls to $108 → Position exits, locking in $8 profit
```

**Benefit**: If price had fallen from $120 back to $90, you'd still exit at $108 instead of losing everything.

#### Partial Profit Taking

**What it is**: Selling portions of your position at predetermined profit levels.

**How it works**:
```
Buy 1000 shares at $10 = $10,000 invested

Price hits $11 (10% gain):
- Sell 25% (250 shares) = $2,750
- Keep 750 shares, locked in $250 profit

Price hits $12 (20% gain):
- Sell 50% of original (500 shares total, but only 750 remain)
- All 750 remaining shares sold = $9,000
- Total proceeds: $2,750 + $9,000 = $11,750
- Total profit: $1,750 (17.5% on original capital)
```

**Benefit**: Lock in guaranteed profits while keeping upside exposure.

#### Configuring Risk Management

**In Backtest Setup**:
```
Dashboard > Backtesting > Advanced Options

[✓] Enable Trailing Stop
    Activation Threshold: 5%  (start trailing after 5% gain)
    Trail Distance: 10%       (stay 10% below highest price)

[✓] Enable Partial Profit Taking
    Level 1: 10% gain → Sell 25%
    Level 2: 20% gain → Sell 50%
    Level 3: 30% gain → Sell remaining
```

**Strategy Combinations**:

**Conservative (Capital Preservation)**:
```
Fixed Stop Loss: 8%
Trailing Stop: 8% distance, activate at 3% gain
Partial Profits: 20% at 5%, 50% at 10%, 100% at 15%
```

**Balanced (Growth + Protection)**:
```
Fixed Stop Loss: 10%
Trailing Stop: 10% distance, activate at 5% gain  
Partial Profits: 25% at 10%, 50% at 20%, 100% at 30%
```

**Aggressive (Let Winners Run)**:
```
Fixed Stop Loss: 15%
Trailing Stop: 15% distance, activate at 10% gain
Partial Profits: 30% at 15%, 100% at 40%
```

**Best Practices**:

✅ **DO**:
- Use trailing stops on momentum strategies (let trends run)
- Use tighter stops (5-8%) on volatile small caps
- Use partial profits to lock in gains on large moves
- Adjust trail distance based on stock volatility
- Test different configurations in backtests

❌ **DON'T**:
- Move stops downward manually (defeats the purpose)
- Use same parameters for all strategies (customize per strategy)
- Ignore trailing stops in range-bound markets (consider fixed stops)
- Take all profit too early (keep some skin in the game)

### Custom Strategy Parameters

Adjust strategy behavior to match your preferences.

```
Dashboard > Settings > Strategy Parameters
```

**Example: Mean Reversion Strategy**

**Default Parameters**:
```
BB Period: 20 days
BB Std Dev: 2.0
RSI Threshold: 30
Volume Multiple: 1.5
Min Volatility: 0.5%
```

**More Aggressive**:
```
RSI Threshold: 35 (earlier entry)
Volume Multiple: 1.3 (less confirmation required)
```

**More Conservative**:
```
RSI Threshold: 25 (wait for deeper oversold)
Volume Multiple: 2.0 (require stronger confirmation)
BB Std Dev: 2.5 (wider bands)
```

⚠️ **Warning**: Changing parameters affects strategy behavior. Test with backtesting before live trading.

### Batch Analysis

Analyze many stocks at once.

```
Dashboard > Analysis > Batch Analysis
```

**Upload Symbol List**:
```
File format: CSV or TXT
Example:
AAPL
MSFT
GOOGL
NVDA
TSLA
```

**Output**: Ranked list of opportunities

### Monte Carlo Simulation

Test strategy under randomized conditions.

```
Dashboard > Backtesting > Monte Carlo
```

**What it does**: Runs 1,000+ simulations with randomized trade sequences.

**Why it matters**: Shows range of possible outcomes and confidence intervals.

**Output**:
```
Expected Return: 35% (median)
90% Confidence Interval: 18% to 52%
Probability of Profit: 85%
Worst 5% Scenario: -8%
Best 5% Scenario: +68%
```

---

## Configuration

### Database Settings

**SQLite (Default)**:
```php
// No configuration needed
// Database file: data/trading.db
```

**MySQL (Production)**:
```php
// config/database.php
return [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'trading_system',
    'username' => 'your_username',
    'password' => 'your_password'
];
```

### Market Data Settings

**Yahoo Finance API (Default)**:
```php
// config/market_data.php
return [
    'provider' => 'yahoo',
    'cache_duration' => 3600, // 1 hour
    'retry_attempts' => 3
];
```

**Data Refresh Schedule**:
```
Real-time: During market hours (9:30 AM - 4:00 PM ET)
End-of-day: After market close (5:00 PM ET)
Pre-market: Before market open (8:00 AM ET)
```

### Performance Settings

**For Better Performance**:
```php
// config/app.php
return [
    'cache_enabled' => true,
    'cache_ttl' => 3600,
    'parallel_requests' => 5, // API calls
    'max_symbols_batch' => 50
];
```

---

## Troubleshooting

### Common Issues

#### "Insufficient Historical Data"

**Problem**: Strategy requires more data than available.

**Solution**:
1. Check minimum required days:
   - Mean Reversion: 100 days
   - Momentum Quality: 250 days
   - Contrarian: 150 days
2. Use a different strategy
3. Choose a stock with more history

#### "Market Data Not Available"

**Problem**: Cannot fetch data for symbol.

**Solutions**:
1. **Check Symbol**:
   - Verify correct ticker
   - Some symbols require exchange suffix (e.g., "BRK-B" not "BRKB")

2. **Check Market Hours**:
   - Real-time data only during market hours
   - Use delayed data setting for after-hours

3. **API Rate Limit**:
   - Wait 60 seconds
   - Reduce batch size

#### "Analysis Taking Too Long"

**Problem**: Strategy analysis not completing.

**Solutions**:
1. **Reduce Date Range**: Use shorter backtest period
2. **Clear Cache**: Settings > Clear Cache
3. **Check System Resources**: Close other applications
4. **Use Batch Mode**: For multiple symbols

#### "Strategies Showing Conflicting Signals"

**This is NORMAL!**

Different strategies have different goals:
- Mean Reversion: "Buy low, sell high" (short-term)
- Momentum: "Buy high, sell higher" (trend-following)
- Contrarian: "Buy fear" (value-oriented)

**What to do**:
1. Use **Portfolio Analysis** for consensus
2. Consider your **investment timeframe**
3. Check **confidence levels**
4. Review **individual reasoning**

#### "Backtest Results Don't Match Live Trading"

**Common Causes**:
1. **Look-Ahead Bias**: Fixed in system (not your issue)
2. **Slippage**: Real execution differs from backtest
3. **Commission**: Ensure commission settings match broker
4. **Market Conditions**: Past ≠ future
5. **Position Sizing**: Use same size as backtest

**Recommendations**:
- Add 0.1-0.2% extra slippage
- Paper trade first (simulated)
- Start with smaller positions
- Monitor real vs. expected performance

---

## FAQ

### General Questions

**Q: Do I need to pay for market data?**  
A: No. The system uses free Yahoo Finance API.

**Q: Can I use this for real trading?**  
A: Yes, but:
- Start with paper trading
- Validate strategies with backtesting
- Use proper risk management
- This is educational software (see disclaimer)

**Q: How often is data updated?**  
A: Real-time during market hours, cached for 1 hour

**Q: Can I add my own strategies?**  
A: Yes, see Developer Guide for instructions

**Q: What markets are supported?**  
A: US stocks, ETFs. International stocks with Yahoo Finance data.

### Strategy Questions

**Q: Which strategy is best?**  
A: Depends on:
- Market conditions (trending vs. range-bound)
- Your risk tolerance
- Investment timeframe
- Portfolio goals

Recommendation: Use **Portfolio Analysis** with multiple strategies

**Q: Why do strategies disagree?**  
A: Different strategies have different goals:
- Some are short-term, some long-term
- Some favor growth, some favor value
- This diversity is GOOD for portfolio robustness

**Q: Can I combine strategies?**  
A: Yes! That's the point of Portfolio Analysis. Combining strategies usually improves risk-adjusted returns.

**Q: How often should I rebalance?**  
A: 
- Active trading: Weekly
- Moderate: Monthly
- Long-term: Quarterly

### Sector Analysis Questions

**Q: What is sector analysis and why does it matter?**  
A: Sector analysis compares your stock's performance to its industry peers. It matters because:
- Stock up 20% in sector up 30% = Actually underperforming
- Stock down 5% in sector down 20% = Actually outperforming
- Reveals if gains are company-specific or sector-wide

**Q: What is a good Relative Strength (RS) Ratio?**  
A:
- > 1.5: Significantly outperforming sector (excellent)
- 1.1-1.5: Outperforming sector (good)
- 0.9-1.1: In line with sector (acceptable)
- 0.5-0.9: Underperforming sector (concerning)
- < 0.5: Significantly underperforming (investigate)

**Q: Should I buy a stock that's up 50% this year?**  
A: Check sector context first:
- If sector is up 60%, stock is actually lagging
- If sector is up 10%, stock is a strong outperformer
- Always compare to sector before judging absolute returns

**Q: What is sector rotation?**  
A: When money moves from one sector to another:
- Example: Money rotating from defensive (utilities, staples) to growth (tech, discretionary)
- Signals potential market regime changes
- Use it to rebalance portfolio allocations

**Q: How do I use sector analysis with strategies?**  
A: Combine them:
- Strategy says BUY + Stock outperforming sector = Strong BUY
- Strategy says BUY + Stock underperforming sector = Caution
- Strategy says SELL + Sector rotating down = Strong SELL

### Index Benchmarking Questions

**Q: What is alpha and why does it matter?**  
A: Alpha is excess return beyond what the market delivered:
- Positive alpha = You beat the market
- Negative alpha = You lagged the market
- Zero alpha = You matched the market
Example: Stock up 25%, market up 10%, alpha = +15%

**Q: What is a good beta for my portfolio?**  
A: Depends on risk tolerance:
- Beta 0.8-1.0: Conservative (lower volatility)
- Beta 1.0-1.3: Moderate (market-like volatility)
- Beta 1.3-1.5: Aggressive (higher volatility)
- Beta > 1.5: Very aggressive (expect large swings)

**Q: Which index should I compare my stocks to?**  
A:
- Large-cap stocks → S&P 500 (SPY)
- Small-cap stocks → Russell 2000 (IWM)
- Tech stocks → NASDAQ 100 (QQQ)
- Blue-chip stocks → Dow Jones (DIA)
- Diversified portfolio → Use multiple benchmarks

**Q: What's the difference between alpha and excess return?**  
A: They're related but different:
- Excess Return = Total return - Index return (simple difference)
- Alpha = Return beyond what beta predicted (risk-adjusted)
- Alpha accounts for stock's volatility relative to market

**Q: How do I use correlation in portfolio construction?**  
A:
- High correlation (>0.7): Stocks move together (limited diversification)
- Low correlation (<0.3): Stocks move independently (good diversification)
- Negative correlation (<-0.3): Stocks move opposite (excellent hedge)

**Q: What is tracking error and why does it matter?**  
A: Tracking error measures how much returns deviate from benchmark:
- Low tracking error (<5%): Closely follows index
- Moderate (5-10%): Some independent movement
- High (>10%): Significantly different from index
Use it to gauge if active management is worth the risk.

### Performance Questions

**Q: What is a good Sharpe Ratio?**  
A:
- > 2.0: Excellent
- 1.0-2.0: Good
- 0.5-1.0: Acceptable
- < 0.5: Poor

**Q: What is a good win rate?**  
A:
- > 65%: Excellent
- 55-65%: Good
- 45-55%: Acceptable (if win/loss ratio good)
- < 45%: Needs improvement

**Q: How much drawdown is acceptable?**  
A:
- < 10%: Very conservative
- 10-20%: Moderate
- 20-30%: Aggressive
- > 30%: Very risky

**Q: Past performance doesn't guarantee future results?**  
A: Correct! Backtesting shows historical edge, not future certainty. Always:
- Use risk management
- Diversify
- Size positions appropriately
- Monitor live performance

---

## Glossary

**Action**: Recommended trade direction (BUY, SELL, HOLD)

**Allocation Profile**: Preset strategy weights (Conservative, Balanced, Aggressive, Growth, Value)

**Backtest**: Historical simulation of strategy performance

**Bollinger Bands**: Price volatility indicator (upper/middle/lower bands)

**Catalyst**: Event that could move stock price (earnings, FDA approval, etc.)

**Confidence**: Strategy certainty level (0-100%)

**Consensus**: Agreement among multiple strategies

**Contrarian**: Strategy that buys during panic/fear

**Correlation**: How similarly two strategies behave (-1 to +1)

**Drawdown**: Peak-to-trough decline in account value

**Equity Curve**: Graph showing account value over time

**GICS**: Global Industry Classification Standard (11 sector classification system)

**Mean Reversion**: Strategy assuming prices return to average

**Momentum**: Rate of price change (accelerating/decelerating)

**Monte Carlo**: Simulation with randomized variables

**Outperformance**: Stock return exceeding sector/benchmark return

**Oversold**: Price has fallen too much, too fast (RSI < 30)

**Relative Performance**: Stock return minus sector return (can be positive or negative)

**Relative Strength (RS) Ratio**: Stock return divided by sector return

**Sector**: Industry group classification (e.g., Technology, Energy, Healthcare)

**Sector Leaders**: Sectors with strongest recent performance (top 3)

**Sector Laggards**: Sectors with weakest recent performance (bottom 3)

**Sector Rotation**: Money flowing from one sector to another (indicates market regime change)

**Alpha**: Excess return beyond what the market (benchmark) delivered, annualized. Positive alpha indicates outperformance.

**Beta**: Measure of stock's volatility relative to market. Beta > 1.0 means more volatile than market, beta < 1.0 means less volatile.

**Correlation**: Statistical measure of how two assets move together, ranging from -1 (perfect negative) to +1 (perfect positive). 0 means uncorrelated.

**Excess Return**: Simple difference between stock return and index return (Total Return - Index Return).

**Information Ratio**: Measures consistency of alpha generation (Excess Return / Tracking Error). Higher is better.

**Tracking Error**: Standard deviation of excess returns, measuring how closely a stock follows its benchmark. Lower means closer tracking.

**Position Size**: Percentage of portfolio allocated to one trade

**Profit Factor**: Gross profits ÷ gross losses

**Rebalancing**: Adjusting portfolio back to target allocations

**RSI**: Relative Strength Index (momentum indicator 0-100)

**Sharpe Ratio**: Risk-adjusted return measure

**Slippage**: Difference between expected and actual execution price

**Stop Loss**: Automatic sell order to limit losses

**Take Profit**: Automatic sell order to lock in gains

**Walk-Forward**: Testing strategy on out-of-sample data

**Weighting Engine**: System that combines multiple strategies

**Win Rate**: Percentage of profitable trades

---

## Getting Help

### Support Resources

**Documentation**:
- [API Reference](API_REFERENCE.md)
- [Developer Guide](DEVELOPER_GUIDE.md)
- [Installation Guide](INSTALLATION.md)

**Community**:
- GitHub Issues: [Report bugs or request features](https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment/issues)
- Discussions: [Ask questions](https://github.com/ksfraser/ChatGPT-Micro-Cap-Experiment/discussions)

**Contact**:
- Email: support@example.com (replace with actual)
- Website: [Project Website](https://example.com)

### Reporting Issues

When reporting issues, include:
1. **Error message** (exact text)
2. **Steps to reproduce**
3. **Expected vs. actual behavior**
4. **System info** (PHP version, OS)
5. **Screenshots** (if applicable)

---

## Appendix A: Quick Reference Card

```
┌─────────────────────────────────────────────────────┐
│           TRADING STRATEGY QUICK REFERENCE          │
├─────────────────────────────────────────────────────┤
│                                                     │
│  STRATEGIES                                         │
│  -----------                                        │
│  1. SmallCapCatalyst  → High risk, high reward     │
│  2. IPlace            → Follow analyst upgrades     │
│  3. MeanReversion     → Buy oversold, short-term    │
│  4. QualityDividend   → Income + stability          │
│  5. MomentumQuality   → Ride trends with quality    │
│  6. Contrarian        → Buy panic, sell euphoria    │
│                                                     │
│  CONFIDENCE LEVELS                                  │
│  -----------------                                  │
│  80-100% → High confidence, strong signal          │
│  60-79%  → Moderate confidence                      │
│  40-59%  → Low confidence                           │
│  0-39%   → Very low, avoid                          │
│                                                     │
│  ALLOCATION PROFILES                                │
│  -------------------                                │
│  Conservative → 35% QualityDiv, 25% MeanRev        │
│  Balanced     → Equal weights all strategies        │
│  Aggressive   → 30% SmallCap, 25% Momentum         │
│  Growth       → 30% Momentum, 25% IPlace           │
│  Value        → 35% Contrarian, 25% QualityDiv     │
│                                                     │
│  KEY METRICS                                        │
│  -----------                                        │
│  Sharpe > 1.0   → Good risk-adjusted returns       │
│  Win Rate > 55% → Acceptable performance           │
│  Profit Factor > 1.5 → Good edge                   │
│  Max DD < 20%   → Acceptable risk                  │
│                                                     │
│  POSITION SIZING                                    │
│  ---------------                                    │
│  Conservative: 5-8% per position                   │
│  Moderate: 10-15% per position                     │
│  Aggressive: 15-20% per position                   │
│                                                     │
│  SECTOR ANALYSIS                                    │
│  ---------------                                    │
│  ✓ Check sector context before trading             │
│  ✓ Compare stock vs sector performance             │
│  ✓ Use sector rotation to time trades              │
│  ✓ RS Ratio > 1.5 = Strong outperformance          │
│  ✓ Watch for sector leaders/laggards               │
│                                                     │
│  GICS SECTORS                                       │
│  ------------                                       │
│  10-Energy, 15-Materials, 20-Industrials           │
│  25-ConsumerDisc, 30-ConsumerStaples               │
│  35-HealthCare, 40-Financials, 45-InfoTech         │
│  50-Communications, 55-Utilities, 60-RealEstate    │
│                                                     │
│  INDEX BENCHMARKING                                 │
│  ------------------                                 │
│  ✓ Compare to relevant benchmark (SPY/QQQ/IWM)     │
│  ✓ Alpha > 0 = Beating the market                  │
│  ✓ Beta > 1.0 = More volatile than market          │
│  ✓ Reduce position size for high beta stocks       │
│  ✓ Information Ratio > 1.0 = Consistent alpha      │
│  ✓ Choose benchmark matching stock's market cap    │
│                                                     │
│  MAJOR INDEXES                                      │
│  -------------                                      │
│  SPY  - S&P 500 (Large-cap, 500 stocks)            │
│  QQQ  - NASDAQ 100 (Tech-focused, 100 stocks)      │
│  DIA  - Dow Jones (Blue-chip, 30 stocks)           │
│  IWM  - Russell 2000 (Small-cap, 2000 stocks)      │
│                                                     │
│  RISK MANAGEMENT                                    │
│  ---------------                                    │
│  ✓ Always use stop losses                          │
│  ✓ Size positions by beta (high beta = smaller)    │
│  ✓ Diversify across strategies and sectors         │
│  ✓ Don't trade with low confidence (<60%)          │
│  ✓ Monitor alpha consistency over time             │
└─────────────────────────────────────────────────────┘
```

---

**Document Version**: 1.1  
**Last Updated**: December 2, 2025  
**Next Review**: March 2, 2026

---

**Disclaimer**: This software is for educational purposes. Trading involves risk. Past performance does not guarantee future results. Always do your own research and consider consulting with a licensed financial advisor before making investment decisions.
