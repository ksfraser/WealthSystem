# Advanced Backtesting Guide

**Sprint 20 Documentation**  
**Last Updated**: December 7, 2025

## Table of Contents

1. [Overview](#overview)
2. [Position Sizing](#position-sizing)
3. [Short Selling](#short-selling)
4. [Multi-Symbol Backtesting](#multi-symbol-backtesting)
5. [Best Practices](#best-practices)
6. [Troubleshooting](#troubleshooting)

---

## Overview

Sprint 20 adds advanced backtesting capabilities:

- **Position Sizing**: 6 methods for optimal capital allocation
- **Short Selling**: Full support with margin, interest, and liquidation
- **Multi-Symbol**: Portfolio-level backtesting with constraints
- **Risk Management**: Margin calls, correlation limits, sector exposure

### Key Features

✅ **Sophisticated Position Sizing**
- Fixed Dollar/Percent allocation
- Kelly Criterion (optimal growth)
- Volatility-based (ATR)
- Risk Parity
- Margin-aware calculations

✅ **Short Selling Support**
- Enter/exit short positions
- Margin requirement tracking (default 150%)
- Short interest calculation (daily borrow fees)
- Margin call detection
- Forced liquidation

✅ **Portfolio-Level Backtesting**
- Multi-symbol coordination
- Position/sector limits
- Correlation-based diversification
- Automatic rebalancing
- Portfolio metrics (Sharpe, Sortino, drawdown)

---

## Position Sizing

### 1. Fixed Dollar

Allocate a fixed dollar amount to each position.

**Use Case**: Simple allocation, consistent position sizes

```php
$sizer = new PositionSizer();

$result = $sizer->fixedDollar(
    portfolioValue: 100000,  // $100k portfolio
    fixedAmount: 10000,      // $10k per position
    currentPrice: 50         // Stock at $50
);

// Result: 200 shares, $10,000 value, 10% of portfolio
```

**Pros**: Simple, predictable  
**Cons**: Doesn't scale with portfolio or adjust for risk

---

### 2. Fixed Percent

Allocate a fixed percentage of portfolio to each position.

**Use Case**: Consistent portfolio allocation, scales with portfolio size

```php
$result = $sizer->fixedPercent(
    portfolioValue: 100000,
    percent: 0.15,    // 15% of portfolio
    currentPrice: 50
);

// Result: 300 shares, $15,000 value, 15% of portfolio
```

**Pros**: Scales with portfolio, consistent risk exposure  
**Cons**: Doesn't adjust for individual asset risk

**Recommended Range**: 5-20% per position

---

### 3. Kelly Criterion

Optimal position size based on win probability and win/loss ratio.

**Use Case**: Maximize long-term growth with known edge

**Formula**: `f* = (p * b - q) / b`
- `p` = win probability
- `q` = loss probability (1-p)
- `b` = win/loss ratio

```php
$result = $sizer->kellyCriterion(
    portfolioValue: 100000,
    winProbability: 0.6,   // 60% win rate
    avgWin: 1.2,           // 20% average win
    avgLoss: 0.9,          // 10% average loss
    currentPrice: 50,
    fraction: 0.5          // Half-Kelly (recommended)
);

// Result: Optimal shares based on edge
// kelly_percent: Full Kelly %
// adjusted_percent: Fractional Kelly (50% of full)
```

**Important Notes**:
- Full Kelly can be aggressive (use fractional Kelly)
- Sensitive to parameter estimation
- Automatically capped at 25% of portfolio
- Returns 0 shares if negative edge detected

**Recommended**: Use 25-50% of full Kelly (fraction = 0.25-0.5)

---

### 4. Volatility-Based (ATR)

Size positions inversely to volatility for consistent risk per trade.

**Use Case**: Equal risk across different volatility assets

```php
$result = $sizer->volatilityBased(
    portfolioValue: 100000,
    riskPercent: 0.01,     // Risk 1% of portfolio
    atr: 2.0,              // $2 Average True Range
    currentPrice: 50,
    atrMultiplier: 2.0     // Stop loss at 2x ATR
);

// Result: Position sized so stop loss = 1% of portfolio
// stop_loss_price: Entry price - (ATR * multiplier)
// stop_loss_distance: Dollar distance to stop
```

**Formula**: `Shares = (Portfolio * Risk%) / (ATR * Multiplier)`

**Pros**: Consistent risk per trade, adjusts for volatility  
**Cons**: Requires ATR calculation, may undersize trending markets

**Recommended**: 0.5-2% risk per trade, 2-3x ATR multiplier

---

### 5. Risk Parity

Allocate so each position contributes equal risk to portfolio.

**Use Case**: Balanced risk exposure, diversification-focused

```php
$assets = [
    ['symbol' => 'AAPL', 'volatility' => 0.02, 'price' => 150],
    ['symbol' => 'MSFT', 'volatility' => 0.015, 'price' => 300],
    ['symbol' => 'BND', 'volatility' => 0.005, 'price' => 80]  // Low vol bond
];

$result = $sizer->riskParity(portfolioValue: 100000, assets: $assets);

// Result: positions array with weight inversely proportional to volatility
// Lower volatility = higher weight
```

**Formula**: `Weight_i = (1/Vol_i) / Sum(1/Vol_j)`

**Pros**: Equal risk contribution, diversification-focused  
**Cons**: May underweight high-return assets, requires volatility estimates

---

### 6. Margin-Aware

Calculate maximum position size given leverage and margin requirements.

**Use Case**: Trading on margin, leverage limits

```php
$result = $sizer->maxPositionWithMargin(
    portfolioValue: 100000,
    availableCash: 50000,
    marginRequirement: 0.5,  // 50% margin
    maxLeverage: 2.0,        // 2x leverage limit
    currentPrice: 100
);

// Result: Max shares respecting both margin and leverage limits
// margin_used: Cash held as margin
// leverage: Actual leverage used
```

**Notes**:
- Respects both margin requirement and leverage limit
- Returns lower of two constraints
- Buying power = Cash / Margin Requirement

---

## Short Selling

### Overview

Short selling allows profiting from declining prices but introduces additional risks and costs.

**Mechanics**:
1. Borrow shares from broker
2. Sell borrowed shares at current price
3. Hold short position (pay daily interest)
4. Buy back shares to return (cover)
5. Profit if price declined, loss if increased

### Entering Short Positions

```php
$engine = new ShortSellingBacktestEngine([
    'initial_capital' => 100000,
    'margin_requirement' => 1.5,      // 150% of position value
    'short_interest_rate' => 0.03,    // 3% annual borrow rate
    'margin_call_threshold' => 1.3,   // Margin call at 130%
    'liquidation_penalty' => 0.02     // 2% penalty on forced liquidation
]);

// Enter short position
$result = $engine->enterShortPosition(
    symbol: 'AAPL',
    shares: 100,
    price: 150.0,
    date: '2025-01-01'
);

// Result includes:
// - margin_required: Cash held as margin (150% of position)
// - proceeds: Cash from short sale (held as part of margin)
```

### Margin Requirements

**Initial Margin**: Typically 150% of short position value
- Example: Short $10,000 → Need $15,000 margin
- Proceeds ($10,000) + additional cash ($5,000) = $15,000

**Maintenance Margin**: Threshold for margin calls (default 130%)

### Exiting Short Positions

```php
// Cover short position
$result = $engine->exitShortPosition(
    symbol: 'AAPL',
    shares: null,    // null = cover all shares
    price: 140.0,
    date: '2025-01-30'
);

// Result includes:
// - profit: Positive if price declined, negative if increased
// - profit_percent: Return %
// - short_interest: Interest paid for borrowing shares
// - days_held: Days position was open
```

### Short Interest Calculation

**Formula**: `Interest = Position Value * (Annual Rate / 365) * Days`

Example:
- Position: $15,000
- Rate: 3% annual
- Days: 30
- Interest: $15,000 * (0.03/365) * 30 = $37

### Margin Calls and Liquidation

```php
// Check for margin calls
$marginCalls = $engine->checkMarginRequirements(
    currentPrices: ['AAPL' => 165.0],
    date: '2025-01-10'
);

if (!empty($marginCalls)) {
    // Margin call triggered!
    // - current_margin_ratio: Current margin coverage
    // - unrealized_loss: Loss on position
    // - action_required: 'add_margin_or_liquidate'
    
    // Force liquidation if needed
    $result = $engine->forceLiquidate('AAPL', 165.0, '2025-01-10');
    // Liquidation includes 2% penalty on top of losses
}
```

**Margin Call Example**:
- Short 100 shares at $150 (need $22,500 margin)
- Price rises to $200
- Position value now $20,000
- Margin ratio: $22,500 / $20,000 = 1.125 (below 1.3 threshold)
- **Margin call triggered!**

### Risk Management

**Unlimited Loss Potential**: Unlike long positions (max loss = 100%), short positions have unlimited loss potential

**Best Practices**:
1. Use stop losses (cover at predetermined price)
2. Limit position size (5-10% of portfolio max)
3. Monitor margin daily
4. Avoid shorting in strong uptrends
5. Factor in borrow costs

---

## Multi-Symbol Backtesting

### Overview

Portfolio-level backtesting across multiple symbols with realistic constraints.

### Setup

```php
$engine = new MultiSymbolBacktestEngine([
    'initial_capital' => 100000,
    'max_position_size' => 0.15,      // 15% max per position
    'max_sector_exposure' => 0.40,    // 40% max per sector
    'max_positions' => 10,            // Max 10 concurrent positions
    'rebalance_threshold' => 0.05,    // Rebalance if drift > 5%
    'correlation_limit' => 0.7,       // Max 0.7 correlation
    'risk_free_rate' => 0.02          // 2% risk-free rate
]);
```

### Registering Strategies

```php
// Define strategy function
$momentumStrategy = function($symbol, $historicalData, $currentPrice) {
    // Strategy logic here
    return [
        'action' => 'BUY',  // BUY, SELL, SHORT, COVER, HOLD
        'confidence' => 0.7  // 0-1 confidence score
    ];
};

// Register with metadata
$engine->registerStrategy('AAPL', $momentumStrategy, [
    'sector' => 'Technology',
    'industry' => 'Consumer Electronics'
]);
```

### Running Backtests

```php
// Prepare market data
$marketData = [
    'AAPL' => [
        ['date' => '2025-01-01', 'open' => 150, 'high' => 152, 'low' => 149, 'close' => 151, 'volume' => 1000000],
        // ... more bars
    ],
    'MSFT' => [
        ['date' => '2025-01-01', 'open' => 300, 'high' => 302, 'low' => 299, 'close' => 301, 'volume' => 800000],
        // ... more bars
    ]
];

// Run backtest
$result = $engine->runBacktest(
    marketData: $marketData,
    startDate: '2025-01-01',
    endDate: '2025-12-31'
);
```

### Results Structure

```php
$result = [
    'period' => [
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'trading_days' => 252
    ],
    'initial_capital' => 100000,
    'final_value' => 125000,
    'metrics' => [
        'total_return' => 0.25,           // 25% return
        'annualized_return' => 0.25,
        'sharpe_ratio' => 1.5,
        'sortino_ratio' => 2.0,
        'max_drawdown' => -0.15,          // -15% max drawdown
        'win_rate' => 0.65,               // 65% win rate
        'volatility' => 0.18              // 18% volatility
    ],
    'trades' => [...],                    // All executed trades
    'signals_stats' => [
        'generated' => 150,               // Signals generated
        'executed' => 100,                // Signals executed
        'rejected' => 50,                 // Signals rejected
        'rejection_reasons' => [
            'Max positions limit reached' => 20,
            'Sector exposure limit reached' => 15,
            'High correlation with existing positions' => 10,
            'Insufficient cash' => 5
        ]
    ],
    'sector_exposures' => [...],          // Daily sector exposures
    'rebalances' => [...]                 // Rebalancing events
];
```

### Portfolio Constraints

#### Position Limits

Maximum number of concurrent positions (default: 10)

**Purpose**: Prevent over-diversification, maintain focus

#### Sector Exposure

Maximum allocation to any sector (default: 40%)

**Purpose**: Reduce concentration risk

**Example**:
- Technology: 35%
- Healthcare: 25%
- Finance: 20%
- Other: 20%

#### Correlation Limit

Maximum average correlation with existing positions (default: 0.7)

**Purpose**: Enforce diversification

**Calculation**: Average absolute correlation between new position and all existing positions

#### Position Size Limit

Maximum % of portfolio per position (default: 15%)

**Purpose**: Limit single-position risk

**Combined with confidence**: `Actual Size = Max Size * Confidence`

### Signal Rejection Reasons

1. **Max positions limit reached**: Already at max concurrent positions
2. **Sector exposure limit reached**: Adding position would exceed sector limit
3. **High correlation**: Average correlation > limit
4. **Insufficient cash**: Not enough cash for position
5. **Insufficient shares calculated**: Position too small (< 1 share)

### Rebalancing

**Automatic rebalancing** triggers when position drift exceeds threshold

**Example**:
- Target: Equal weight (20% each for 5 positions)
- Current: AAPL drifted to 27% (7% drift)
- Threshold: 5%
- **Rebalance triggered**

---

## Best Practices

### Position Sizing

1. **Diversify methods**: Use different sizing for different strategies
2. **Start conservative**: Use fractional Kelly (25-50%), moderate % allocations
3. **Risk 1-2% per trade**: For volatility-based sizing
4. **Monitor concentration**: No single position > 20%

### Short Selling

1. **Limit exposure**: Max 20-30% of portfolio in shorts
2. **Use stop losses**: Cover at predetermined price
3. **Monitor borrow costs**: High interest = avoid or close quickly
4. **Avoid earnings**: Don't short before earnings (gap risk)
5. **Check margin daily**: Prevent surprise margin calls

### Multi-Symbol Backtesting

1. **Use realistic data**: Include gaps, low volume days
2. **Test constraints**: Verify position/sector limits work as expected
3. **Analyze rejections**: Understand why signals were rejected
4. **Correlations matter**: Track how correlations change over time
5. **Rebalance carefully**: Balance drift control vs. transaction costs

### Risk Management

1. **Diversify**: Multiple positions, sectors, strategies
2. **Position sizing**: Adjust for risk (volatility, correlation)
3. **Stop losses**: Always use protective stops
4. **Margin prudently**: Keep leverage < 2x
5. **Monitor daily**: Portfolio value, margins, exposures

---

## Troubleshooting

### Common Issues

#### Issue: Kelly Criterion returning 0 shares

**Cause**: Negative edge detected (expected value < 0)

**Solution**: Check win rate and win/loss ratio. Ensure positive edge:
```
(p * avgWin) > ((1-p) * avgLoss)
```

#### Issue: All signals rejected

**Causes**:
1. Insufficient cash
2. Position limits reached
3. Sector exposure limits
4. High correlation

**Solution**: Review constraints, increase capital, or adjust limits

#### Issue: Margin calls on every short position

**Cause**: Margin requirement too high or positions too large

**Solution**:
- Reduce position size
- Increase initial capital
- Lower margin_requirement config
- Use wider stops

#### Issue: No trades executed in multi-symbol backtest

**Causes**:
1. Insufficient historical data (need 20+ bars)
2. Strategy never generates signals
3. All signals rejected by constraints

**Solution**: 
- Check historical data length
- Verify strategy logic
- Review rejection reasons in results

#### Issue: Negative returns on profitable short

**Cause**: Short interest costs exceed profit

**Solution**: 
- Hold shorts for shorter periods
- Lower short_interest_rate config for backtesting
- Factor borrow costs into strategy

### Performance Optimization

#### Slow multi-symbol backtests

**Solutions**:
1. Reduce date range
2. Fewer symbols
3. Pre-filter market data by date
4. Simplify strategy logic

#### Memory issues with large datasets

**Solutions**:
1. Process in batches by date range
2. Reduce data granularity (daily vs. intraday)
3. Store results incrementally
4. Use database for large datasets

### Validation

#### Verify position sizing

```php
$result = $sizer->fixedPercent(100000, 0.1, 50);
assert($result['value'] / 100000 <= 0.1001);  // Allow rounding
assert($result['shares'] * $result['price'] == $result['value']);
```

#### Verify short selling

```php
// Check margin calculation
$result = $engine->enterShortPosition('AAPL', 100, 150, '2025-01-01');
$expectedMargin = 100 * 150 * 1.5;  // shares * price * margin_requirement
assert(abs($result['margin_required'] - $expectedMargin) < 500);  // Allow slippage
```

#### Verify portfolio constraints

```php
// Check position limits
$result = $engine->runBacktest($marketData, $startDate, $endDate);
$rejections = $result['signals_stats']['rejection_reasons'];
assert(isset($rejections['Max positions limit reached']));  // Should trigger at some point
```

---

## Appendix

### Configuration Defaults

```php
// PositionSizer - no configuration needed

// ShortSellingBacktestEngine
[
    'initial_capital' => 100000,
    'commission_rate' => 0.001,           // 0.1%
    'slippage_rate' => 0.0005,            // 0.05%
    'margin_requirement' => 1.5,          // 150%
    'short_interest_rate' => 0.03,        // 3% annual
    'margin_call_threshold' => 1.3,       // 130%
    'liquidation_penalty' => 0.02         // 2%
]

// MultiSymbolBacktestEngine
[
    'initial_capital' => 100000,
    'max_position_size' => 0.15,          // 15%
    'max_sector_exposure' => 0.40,        // 40%
    'max_positions' => 10,
    'rebalance_threshold' => 0.05,        // 5%
    'correlation_limit' => 0.7,
    'risk_free_rate' => 0.02              // 2%
]
```

### Glossary

- **ATR**: Average True Range - volatility measure
- **Correlation**: Statistical relationship between two assets (-1 to +1)
- **Drawdown**: Peak-to-trough decline in portfolio value
- **Kelly Criterion**: Optimal position sizing formula for maximum growth
- **Leverage**: Using borrowed capital to increase position size
- **Liquidation**: Forced closing of position (e.g., on margin call)
- **Margin**: Collateral required for short positions or leverage
- **Margin Call**: Demand for additional margin when requirements not met
- **Risk Parity**: Allocation method for equal risk contribution
- **Sharpe Ratio**: Risk-adjusted return measure (higher = better)
- **Short Interest**: Cost to borrow shares for shorting
- **Slippage**: Difference between expected and actual execution price
- **Sortino Ratio**: Like Sharpe but only penalizes downside volatility
- **Stop Loss**: Predetermined exit price to limit losses

---

**End of Guide**

For examples, see `examples/advanced_backtesting_examples.php`  
For tests, see `tests/Backtesting/`
