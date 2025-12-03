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
- Stop Loss: 10%
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

**Mean Reversion**: Strategy assuming prices return to average

**Momentum**: Rate of price change (accelerating/decelerating)

**Monte Carlo**: Simulation with randomized variables

**Oversold**: Price has fallen too much, too fast (RSI < 30)

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
│  RISK MANAGEMENT                                    │
│  ---------------                                    │
│  ✓ Always use stop losses                          │
│  ✓ Size positions appropriately                    │
│  ✓ Diversify across strategies                     │
│  ✓ Don't trade with low confidence (<60%)          │
│  ✓ Monitor live vs. expected performance           │
└─────────────────────────────────────────────────────┘
```

---

**Document Version**: 1.0  
**Last Updated**: December 2, 2025  
**Next Review**: March 2, 2026

---

**Disclaimer**: This software is for educational purposes. Trading involves risk. Past performance does not guarantee future results. Always do your own research and consider consulting with a licensed financial advisor before making investment decisions.
