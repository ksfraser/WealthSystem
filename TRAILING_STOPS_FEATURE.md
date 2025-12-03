# Trailing Stops & Partial Profit-Taking Feature

## Overview

**Status**: ✅ IMPLEMENTED & TESTED  
**Commit**: 08b63e0d  
**Tests**: 5/5 passing with 21 assertions  
**Documentation**: Complete with examples

This feature addresses your critical requirement: **"Does our strategies and back testing consider stop losses, as well as resets? I.e. we buy at 100$, with a 10% stop loss (i.e. 90). Price goes up to 120 in a week, so we adjust our stop loss to be 90% of 120, etc."**

---

## What Was Built

### 1. Trailing Stop Loss

Dynamic stop loss that **locks in profits** by adjusting upward as price rises.

**Key Behaviors**:
- ✅ Activates after configurable gain threshold (default 5%)
- ✅ Trails below highest price by configurable distance (default 10%)
- ✅ **Never moves downward** - only upward or stays same
- ✅ Automatically exits position when price falls to stop level
- ✅ Falls back to fixed stop loss before trailing activates

**Example**:
```
Buy: $100
Fixed Stop: $90 (10% below entry)
Trailing Stop: 10% distance, activates at 5% gain

Day 1: $100 → Stop at $90 (fixed)
Day 2: $105 → Stop at $90 (not activated yet)
Day 3: $106 → Trailing activates, stop moves to $95.40
Day 4: $120 → Stop moves to $108 (10% below $120)
Day 5: $115 → Stop stays at $108 (doesn't move down)
Day 6: $108 → EXIT with $8 profit locked in

WITHOUT TRAILING: Would risk all $20 gain on reversal
WITH TRAILING: Locked in $8 profit, protected against full reversal
```

### 2. Partial Profit-Taking

Sell portions of your position at predetermined profit milestones.

**Key Behaviors**:
- ✅ Configurable profit levels with sell percentages
- ✅ Scales out of position to lock in gains
- ✅ Keeps some exposure for further upside
- ✅ Each partial exit recorded in trade history
- ✅ Position sizing adjusts automatically

**Example**:
```
Buy: 1000 shares at $10 = $10,000
Config: 
  - 10% gain → Sell 25%
  - 20% gain → Sell 50% (of original)
  - 30% gain → Sell remaining

Price $11 (10% gain):
  - Sell 250 shares at $11 = $2,750
  - Remaining: 750 shares
  - Profit locked: $250

Price $12 (20% gain):
  - Sell 500 shares (but only 750 remain)
  - Sell all 750 at $12 = $9,000
  - Total proceeds: $11,750
  - Total profit: $1,750 (17.5%)
  
WITHOUT PARTIAL PROFITS: All-or-nothing exit
WITH PARTIAL PROFITS: Guaranteed profit + upside participation
```

### 3. Combined Strategy

Use both features together for maximum protection and profit.

**Example**:
```
Buy: 1000 shares at $100

Config:
  - Partial profits: 30% at 10% gain, 50% at 20% gain
  - Trailing stop: 10% distance, activates at 5% gain

Price $110 (10% gain):
  - Sell 300 shares at $110 = $33,000
  - Profit locked: $3,000
  - Remaining: 700 shares
  - Trailing activates, stop at $99

Price $120 (20% gain):
  - Sell 500 shares (but only 700 remain) at $120 = $84,000
  - Remaining: 200 shares
  - Trailing moves to $108

Price drops to $108:
  - Exit 200 shares at $108 = $21,600
  - Total proceeds: $138,600 vs $100,000 cost
  - Total profit: $38,600 (38.6%)

Result: Locked in profits along the way while maximizing upside
```

---

## Configuration Options

### Backtesting Framework Options

```php
$options = [
    // Position sizing
    'position_size' => 0.10,  // 10% of capital per trade
    
    // Fixed risk management
    'stop_loss' => 0.10,      // 10% fixed stop (fallback)
    'take_profit' => 0.20,    // 20% take profit
    'max_holding_days' => 30, // Maximum holding period
    
    // Trailing stop (NEW)
    'trailing_stop' => true,              // Enable trailing stop
    'trailing_stop_activation' => 0.05,   // Activate after 5% gain
    'trailing_stop_distance' => 0.10,     // Trail 10% below highest
    
    // Partial profit-taking (NEW)
    'partial_profit_taking' => true,      // Enable scaling out
    'profit_levels' => [                  // Profit targets
        ['profit' => 0.10, 'sell_pct' => 0.25],  // 10% gain → sell 25%
        ['profit' => 0.20, 'sell_pct' => 0.50],  // 20% gain → sell 50%
        ['profit' => 0.30, 'sell_pct' => 1.00]   // 30% gain → sell rest
    ]
];
```

### Risk Profile Presets

**Conservative (Capital Preservation)**:
```php
'stop_loss' => 0.08,
'trailing_stop' => true,
'trailing_stop_activation' => 0.03,
'trailing_stop_distance' => 0.08,
'profit_levels' => [
    ['profit' => 0.05, 'sell_pct' => 0.20],
    ['profit' => 0.10, 'sell_pct' => 0.50],
    ['profit' => 0.15, 'sell_pct' => 1.00]
]
```

**Balanced (Growth + Protection)**:
```php
'stop_loss' => 0.10,
'trailing_stop' => true,
'trailing_stop_activation' => 0.05,
'trailing_stop_distance' => 0.10,
'profit_levels' => [
    ['profit' => 0.10, 'sell_pct' => 0.25],
    ['profit' => 0.20, 'sell_pct' => 0.50],
    ['profit' => 0.30, 'sell_pct' => 1.00]
]
```

**Aggressive (Let Winners Run)**:
```php
'stop_loss' => 0.15,
'trailing_stop' => true,
'trailing_stop_activation' => 0.10,
'trailing_stop_distance' => 0.15,
'profit_levels' => [
    ['profit' => 0.15, 'sell_pct' => 0.30],
    ['profit' => 0.40, 'sell_pct' => 1.00]
]
```

---

## Implementation Details

### Position Tracking Structure

**Before** (missing trailing stop fields):
```php
$position = [
    'symbol' => 'AAPL',
    'entry_date' => '2024-01-01',
    'entry_price' => 100.00,
    'shares' => 100,
    'cost' => 10000,
    'confidence' => 0.85
];
```

**After** (with trailing stop support):
```php
$position = [
    'symbol' => 'AAPL',
    'entry_date' => '2024-01-01',
    'entry_price' => 100.00,
    'shares' => 100,
    'cost' => 10000,
    'confidence' => 0.85,
    // NEW FIELDS
    'highest_price' => 120.00,           // Tracks peak price
    'trailing_stop_price' => 108.00,     // Current stop level
    'trailing_stop_active' => true,      // Whether trailing is active
    'original_shares' => 100,            // Original position size
    'profit_levels_taken' => ['0.1' => true]  // Track which levels hit
];
```

### Exit Reason Tracking

New exit reasons in trade history:
- `'trailing_stop'` - Exited via trailing stop
- `'partial_profit_10%'` - Partial exit at 10% gain
- `'partial_profit_20%'` - Partial exit at 20% gain
- `'partial_profit_30%'` - Partial exit at 30% gain
- `'stop_loss'` - Fixed stop (before trailing activates)
- `'take_profit'` - Fixed take profit
- `'strategy_signal'` - Strategy said SELL
- `'max_holding_days'` - Time limit reached

### Exit Logic Flow

```
For each position in portfolio:
  1. Update highest_price if current price is new high
  
  2. If highest_price > entry_price * (1 + activation_threshold):
     - Activate trailing stop
     - Set trailing_stop_price = highest_price * (1 - trail_distance)
     - Only move stop upward, never down
  
  3. Check partial profit levels:
     - For each level not yet taken:
       - If profit >= level['profit']:
         - Sell level['sell_pct'] of original shares
         - Record partial exit in trades
         - Mark level as taken
  
  4. Check exit conditions (in priority order):
     - If trailing_stop_active AND price <= trailing_stop_price:
       → EXIT via trailing_stop
     - Else if price <= entry_price * (1 - stop_loss):
       → EXIT via stop_loss
     - Else if price >= entry_price * (1 + take_profit):
       → EXIT via take_profit
     - Else if holding_days >= max_holding_days:
       → EXIT via max_holding_days
     - Else if strategy says SELL:
       → EXIT via strategy_signal
```

---

## Test Coverage

### Test Suite: TrailingStopTest.php

**Status**: ✅ All 5 tests passing with 21 assertions

#### Test 1: Trailing Stop Activates and Adjusts Upward
- Buy at $100, price rises to $120
- Trailing activates at $105 (5% gain)
- Stop adjusts from $90 → $95.40 → $99 → $103.50 → $108
- Price falls to $107.50
- ✅ Exits at ~$108 with ~8% gain via trailing_stop

#### Test 2: Trailing Stop Never Moves Downward
- Buy at $100, price rises to $120 (stop at $108)
- Price drops to $110 (stop stays at $108, doesn't drop to $99)
- ✅ Stop maintains highest level, never decreases

#### Test 3: Partial Profit Taking at Multiple Levels
- Buy 1000 shares at $10
- Price $11: Sells 25% (250 shares)
- Price $12: Sells 50% of original (500 shares, but 750 remain)
- Price $13: Sells remaining
- ✅ 3 trades recorded with correct exit reasons and prices

#### Test 4: Combined Trailing Stop and Partial Profits
- Uses both trailing stops AND partial profit-taking
- Verifies both features work together
- ✅ Multiple partial exits + trailing stop exit all execute correctly

#### Test 5: Fixed Stop Loss Works Before Trailing Activates
- Buy at $100, price drops to $89 before reaching 5% gain
- Trailing never activates (needs 5% gain first)
- ✅ Exits via fixed stop_loss at $90, not trailing_stop

### Running Tests

```bash
cd Stock-Analysis
php vendor/bin/phpunit tests/Unit/Services/Trading/TrailingStopTest.php --testdox
```

**Output**:
```
PHPUnit 9.6.25 by Sebastian Bergmann and contributors.

Trailing Stop (Tests\Unit\Services\Trading\TrailingStop)
 ✔ Trailing stop activates and adjusts upward
 ✔ Trailing stop never moves downward
 ✔ Partial profit taking at multiple levels
 ✔ Combined trailing stop and partial profits
 ✔ Fixed stop loss works before trailing activates

Time: 00:00.022, Memory: 8.00 MB

OK (5 tests, 21 assertions)
```

---

## Documentation

### Updated Files

1. **BacktestingFramework.php** (Stock-Analysis/app/Services/Trading/)
   - 120+ lines added for trailing stop and partial profit logic
   - Enhanced PHPDoc with examples
   - Position structure expanded with tracking fields
   - Exit logic prioritizes trailing stop when active

2. **TrailingStopTest.php** (Stock-Analysis/tests/Unit/Services/Trading/)
   - 360+ lines of comprehensive test coverage
   - 5 test scenarios covering all edge cases
   - Mock strategy for controlled testing
   - Real-world price action simulation

3. **TRADING_SYSTEM_USER_MANUAL.md** (Project Root)
   - New "Risk Management" section (80+ lines)
   - Detailed examples with numbers
   - Configuration guide for different risk profiles
   - Best practices and DO/DON'T lists
   - Strategy combination recommendations

---

## Impact Analysis

### Before This Feature

**Problem**: All profits at risk on reversal
```
Buy: $100
Price rises to: $150 (50% gain)
Price reverses to: $90
Result: 10% LOSS (risked $50 gain + $10 original capital)
```

**User's Concern**: "Price goes up to 120 in a week, so we adjust our stop loss to be 90% of 120, etc."

### After This Feature

**Solution**: Automatic profit protection
```
Buy: $100
Trailing: 10% distance, activates at 5%
Price rises to: $150
Stop adjusts to: $135 (10% below $150)
Price reverses to: $135
Result: 35% GAIN (locked in $35 profit, protected $15 gain)
```

### Real-World Scenarios

#### Scenario 1: Momentum Trade
```
Strategy: Momentum Quality on NVDA
Entry: $400
Trailing: 12% distance, activates at 8%

Day 1-5: $400 → $450 (12.5% gain, trailing activates)
Stop: $396 (12% below $450)

Day 6-10: $450 → $550 (37.5% gain)
Stop: $484 (12% below $550)

Day 11-15: $550 → $520 (pullback)
Stop: Still $484

Day 16: Price $484
EXIT: +21% gain ($84/share profit)

Without Trailing: Might hold through entire reversal, exit at $400 for 0%
With Trailing: Locked in 21% gain
```

#### Scenario 2: Small Cap Catalyst
```
Strategy: SmallCapCatalyst on biotech stock
Entry: $8.00
Partial Profits: 25% at 15%, 50% at 30%, 100% at 50%
Trailing: 15% distance, activates at 10%

FDA approval news → $10 (25% gain)
- Sell 25% at $10 = $2/share profit locked
- Remaining 75% of position

Further momentum → $12 (50% gain)
- Sell 50% of original (but 75% remains)
- Sell all 75% at $12 = $4/share profit locked
- Position closed

Total Profit: 
- 25% × $2 = $0.50/share
- 75% × $4 = $3.00/share
- Average: $3.50/share (43.75% gain)

Without Partial Profits: Might have held too long, given back gains
With Partial Profits: Locked in 43.75% average, reduced risk
```

---

## Usage Recommendations

### By Strategy Type

**Momentum Strategies** (IPlace, Momentum Quality):
- Use wider trailing stops (12-15%)
- Activate earlier (3-5% gain)
- Fewer partial profit levels (let trends run)

**Mean Reversion Strategies**:
- Use tighter trailing stops (8-10%)
- Activate quickly (2-3% gain)
- More frequent partial profits (capture quick reversals)

**Quality/Dividend Strategies**:
- Use moderate trailing stops (10-12%)
- Activate after modest gain (5-7%)
- Conservative partial profits (lock in value)

**Small Cap/Catalyst Strategies**:
- Use wide trailing stops (15-20%, high volatility)
- Aggressive partial profits (capture spikes)
- Take 50%+ off table quickly on big moves

### Market Conditions

**Bull Market**:
- Wider trailing stops (let trends run)
- Less aggressive partial profits
- Higher activation thresholds

**Bear Market**:
- Tighter trailing stops (protect capital)
- More aggressive partial profits
- Lower activation thresholds

**Range-Bound**:
- Consider disabling trailing stops
- Use fixed take-profit instead
- Aggressive partial profits at resistance

---

## Future Enhancements

Potential additions (not yet implemented):

1. **ATR-Based Trailing Distance**
   - Adjust trail distance based on volatility
   - Wider stops in volatile stocks, tighter in stable

2. **Time-Based Trailing Adjustments**
   - Tighten stops after holding X days
   - "Lock in gains after 2 weeks"

3. **Trend-Following Trailing**
   - Widen stops when uptrend strong
   - Tighten stops when trend weakening
   - Integration with technical indicators

4. **Smart Partial Profit Levels**
   - Dynamic levels based on support/resistance
   - Fibonacci retracement targets
   - Volume profile exits

5. **Portfolio-Level Trailing**
   - Trail entire portfolio value
   - Exit all positions if portfolio falls X%
   - Risk management across positions

---

## Summary

✅ **Implemented**: Trailing stops with configurable activation and distance  
✅ **Implemented**: Partial profit-taking at multiple levels  
✅ **Implemented**: Combined strategy support  
✅ **Tested**: 5 comprehensive test cases, 21 assertions, 100% passing  
✅ **Documented**: User manual updated with examples and best practices  
✅ **Committed**: Pushed to GitHub (commit 08b63e0d)

**Answers Your Question**: 
> "Does our strategies and back testing consider stop losses, as well as resets? I.e. we buy at 100$, with a 10% stop loss (i.e. 90). Price goes up to 120 in a week, so we adjust our stop loss to be 90% of 120, etc."

**Answer**: YES - The system now automatically adjusts stops upward as price rises, locks in profits, and supports partial profit-taking exactly as you described.

**Ready for**: Production backtesting and live trading validation
