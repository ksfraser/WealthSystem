# Technical Indicator & Trading Concept Glossary

**Version**: 1.0  
**Purpose**: Quick reference for UI tooltips, help system, and user education  
**Target Audience**: Traders, investors, financial advisors  

---

## Table of Contents

- [Moving Averages](#moving-averages)
- [Momentum Indicators](#momentum-indicators)
- [Volatility Indicators](#volatility-indicators)
- [Volume Indicators](#volume-indicators)
- [Trend Indicators](#trend-indicators)
- [Candlestick Patterns](#candlestick-patterns)
- [Trading Strategies](#trading-strategies)
- [Risk Management](#risk-management)
- [Fund Analysis](#fund-analysis)

---

## Moving Averages

### SMA (Simple Moving Average)
**What it is**: Average price over N periods, all periods weighted equally  
**Use case**: Identify trend direction, support/resistance levels  
**Common periods**: 20, 50, 100, 200 days  
**Bullish signal**: Price crosses above SMA  
**Bearish signal**: Price crosses below SMA  
**Learn more**: [TA-Lib Documentation](../Project_Work_Products/ProjectDocuments/Design/Technical/TA-Lib_Integration_Analysis.md#simple-moving-average-sma)

### EMA (Exponential Moving Average)
**What it is**: Average price giving more weight to recent data  
**Use case**: More responsive to recent price changes than SMA  
**Common periods**: 12, 26 (for MACD), 9 (signal line)  
**Bullish signal**: Faster EMA crosses above slower EMA (golden cross)  
**Bearish signal**: Faster EMA crosses below slower EMA (death cross)  
**Pros**: Less lag than SMA  
**Cons**: More sensitive to noise

### WMA (Weighted Moving Average)
**What it is**: Linear weighting (most recent price weighted highest)  
**Use case**: Balance between SMA responsiveness and EMA sensitivity  
**Calculation**: Recent prices weighted higher, linear decrease  
**Best for**: Medium-term trend analysis

### DEMA (Double Exponential Moving Average)
**What it is**: EMA of EMA, reduces lag significantly  
**Use case**: Fast-moving trend identification  
**Calculation**: 2×EMA - EMA(EMA)  
**Best for**: Short-term trading, quick reversals

### TEMA (Triple Exponential Moving Average)
**What it is**: Even faster than DEMA, minimal lag  
**Use case**: Ultra-responsive trend following  
**Calculation**: 3×EMA - 3×EMA(EMA) + EMA(EMA(EMA))  
**Best for**: Day trading, scalping

### Bollinger Bands®
**What it is**: SMA with upper/lower bands at N standard deviations  
**Use case**: Measure volatility, identify overbought/oversold  
**Default settings**: 20-period SMA, ±2 standard deviations  
**Bullish signals**:
- Price bounces off lower band
- Band squeeze (low volatility) followed by expansion
- Walk along upper band (strong uptrend)

**Bearish signals**:
- Price rejected at upper band
- Walk along lower band (strong downtrend)

**Interpretation**:
- Bands tighten: Low volatility, breakout imminent
- Bands widen: High volatility, trend continuation
- Price above upper band: Overbought (but can continue)
- Price below lower band: Oversold (but can continue)

---

## Momentum Indicators

### RSI (Relative Strength Index)
**What it is**: Momentum oscillator measuring speed/magnitude of price changes  
**Range**: 0 to 100  
**Default period**: 14 days  
**Overbought**: RSI > 70 (potential sell signal)  
**Oversold**: RSI < 30 (potential buy signal)  
**Neutral**: RSI ≈ 50  

**Advanced signals**:
- **Divergence**: Price makes new high/low but RSI doesn't (reversal warning)
- **Failure swing**: RSI fails to exceed previous high/low
- **50-line cross**: RSI crossing 50 indicates momentum shift

**Best practices**:
- Don't sell just because RSI > 70 (can stay elevated in strong trends)
- Use with other indicators for confirmation
- Adjust thresholds in trending markets (80/20)

### MACD (Moving Average Convergence/Divergence)
**What it is**: Difference between two EMAs (12, 26) with signal line (9 EMA)  
**Components**:
- **MACD line**: 12 EMA - 26 EMA
- **Signal line**: 9 EMA of MACD line
- **Histogram**: MACD line - Signal line

**Bullish signals**:
- MACD crosses above signal line
- MACD crosses above zero line
- Histogram growing (momentum increasing)
- Bullish divergence (price lower low, MACD higher low)

**Bearish signals**:
- MACD crosses below signal line
- MACD crosses below zero line
- Histogram shrinking (momentum decreasing)
- Bearish divergence (price higher high, MACD lower high)

**Best for**: Trend following, momentum confirmation

### Stochastic Oscillator
**What it is**: Compares closing price to price range over N periods  
**Range**: 0 to 100  
**Components**: %K (fast line), %D (slow line, 3-period SMA of %K)  
**Default period**: 14 days  

**Overbought**: > 80  
**Oversold**: < 20  

**Bullish signals**:
- %K crosses above %D in oversold zone
- Bullish divergence

**Bearish signals**:
- %K crosses below %D in overbought zone
- Bearish divergence

**Warning**: Can remain overbought/oversold for extended periods in strong trends

### CCI (Commodity Channel Index)
**What it is**: Measures current price level relative to average price  
**Range**: Typically -100 to +100 (but unlimited)  
**Default period**: 14 days  

**Overbought**: > +100  
**Oversold**: < -100  

**Interpretation**:
- Breaking above +100: Strong uptrend beginning
- Breaking below -100: Strong downtrend beginning
- Between ±100: Ranging/weak trend

**Best for**: Identifying cyclical trends, overbought/oversold extremes

### MFI (Money Flow Index)
**What it is**: Volume-weighted RSI  
**Range**: 0 to 100  
**Default period**: 14 days  

**Overbought**: > 80  
**Oversold**: < 20  

**Unique feature**: Incorporates volume (buying/selling pressure)  
**Best for**: Confirming price moves with volume validation  
**Divergence**: More reliable than RSI divergence due to volume component

### Williams %R
**What it is**: Momentum indicator similar to Stochastic  
**Range**: -100 to 0  
**Default period**: 14 days  

**Overbought**: > -20  
**Oversold**: < -80  

**Interpretation**:
- Measures where close is relative to high-low range
- Inverted scale (0 is high, -100 is low)

**Best for**: Short-term trading, identifying reversals

---

## Volatility Indicators

### ATR (Average True Range)
**What it is**: Measures market volatility (not direction)  
**Calculation**: Average of True Range over N periods  
**Default period**: 14 days  
**True Range**: Max of:
1. Current High - Current Low
2. |Current High - Previous Close|
3. |Current Low - Previous Close|

**Use cases**:
- **Stop loss placement**: 2× ATR from entry
- **Position sizing**: Risk ÷ ATR = number of shares
- **Breakout validation**: High ATR confirms strong breakout
- **Volatility comparison**: Compare stocks, identify volatility changes

**Interpretation**:
- High ATR: High volatility, wider stops needed
- Low ATR: Low volatility, tighter stops possible
- Rising ATR: Increasing volatility (often start of trend)
- Falling ATR: Decreasing volatility (often end of trend)

### NATR (Normalized ATR)
**What it is**: ATR expressed as percentage of closing price  
**Use case**: Compare volatility across different price levels  
**Formula**: (ATR ÷ Close) × 100  
**Benefit**: Apples-to-apples comparison between $10 and $100 stocks

### Standard Deviation
**What it is**: Statistical measure of price dispersion  
**Use case**: Quantify volatility, risk assessment  
**Interpretation**:
- High StdDev: High volatility, higher risk/reward
- Low StdDev: Low volatility, lower risk/reward
- Used in Bollinger Bands calculation

### True Range
**What it is**: Single-period volatility measure  
**Use case**: Day-to-day volatility tracking  
**Always positive**: Measures absolute range

---

## Volume Indicators

### OBV (On Balance Volume)
**What it is**: Cumulative volume based on price direction  
**Calculation**:
- If close > previous close: Add volume
- If close < previous close: Subtract volume
- If close = previous close: No change

**Bullish signals**:
- OBV rising with price (confirms uptrend)
- OBV rising while price flat (accumulation, bullish)

**Bearish signals**:
- OBV falling with price (confirms downtrend)
- OBV falling while price flat (distribution, bearish)

**Divergence**: Most powerful signal
- Price up, OBV down: Weak rally, likely reversal
- Price down, OBV up: Weak decline, likely bounce

### Chaikin A/D Line (Accumulation/Distribution)
**What it is**: Volume-weighted cumulative indicator  
**Formula**: Considers close location within high-low range  
**Range**: Unlimited (cumulative)  

**Interpretation**:
- Rising: Accumulation (buying pressure)
- Falling: Distribution (selling pressure)
- Confirms trends when moving with price
- Divergence warns of reversals

**Advantage over OBV**: Considers where price closed in range (not just up/down)

### Chaikin A/D Oscillator
**What it is**: MACD of A/D Line (3-day EMA - 10-day EMA)  
**Use case**: Earlier signals than A/D Line  

**Bullish**: Oscillator > 0  
**Bearish**: Oscillator < 0  

**Best for**: Short-term trading, momentum shifts

---

## Trend Indicators

### ADX (Average Directional Index)
**What it is**: Measures trend strength (NOT direction)  
**Range**: 0 to 100  
**Default period**: 14 days  

**Interpretation**:
- ADX < 20: Weak trend, ranging market
- ADX 20-25: Trend emerging
- ADX 25-50: Strong trend
- ADX > 50: Very strong trend
- ADX > 75: Extremely strong trend (rare)

**Important**: ADX doesn't indicate direction (use +DI/-DI for that)

**Best use**:
- Filter: Only trade when ADX > 25 (trending market)
- Avoid mean-reversion strategies when ADX > 40

### +DI / -DI (Directional Indicators)
**What it is**: Measures directional movement  
**Range**: 0 to 100  

**+DI**: Upward movement strength  
**-DI**: Downward movement strength  

**Bullish**: +DI crosses above -DI  
**Bearish**: -DI crosses above +DI  

**Combined with ADX**:
- ADX > 25 AND +DI > -DI: Strong uptrend, go long
- ADX > 25 AND -DI > +DI: Strong downtrend, go short
- ADX < 20: Don't trend trade, wait for breakout

### Aroon Indicator
**What it is**: Measures time since highest high / lowest low  
**Range**: 0 to 100  
**Default period**: 25 days  

**Components**:
- **Aroon Up**: ((N - Days Since High) ÷ N) × 100
- **Aroon Down**: ((N - Days Since Low) ÷ N) × 100

**Interpretation**:
- Aroon Up > 70: Strong uptrend
- Aroon Down > 70: Strong downtrend
- Both < 50: Consolidation/ranging

**Bullish**: Aroon Up crosses above Aroon Down  
**Bearish**: Aroon Down crosses above Aroon Up

### Aroon Oscillator
**What it is**: Aroon Up - Aroon Down  
**Range**: -100 to +100  

**Bullish**: Oscillator > 0 (especially > +50)  
**Bearish**: Oscillator < 0 (especially < -50)  
**Neutral**: Oscillator near 0

### Parabolic SAR (Stop and Reverse)
**What it is**: Trailing stop indicator showing stop-loss levels  
**Display**: Dots above/below price  

**Interpretation**:
- Dots below price: Uptrend, hold long
- Dots above price: Downtrend, hold short
- Dots flip: Trend reversal, close position and reverse

**Parameters**:
- Acceleration Factor: 0.02 (default start)
- Maximum: 0.2 (default)

**Best for**: Trailing stops, trend-following systems  
**Warning**: Generates many false signals in ranging markets

---

## Candlestick Patterns

### Reversal Patterns (Bullish)

#### Hammer
**Description**: Small body at top, long lower shadow (2×+ body)  
**Context**: Appears at bottom of downtrend  
**Psychology**: Sellers pushed price down, buyers rejected low and drove it back up  
**Confirmation**: Next candle closes above hammer high  
**Reliability**: HIGH (especially with high volume)  
**Target**: Height of preceding downtrend  
**Stop loss**: Below hammer low

#### Inverted Hammer
**Description**: Small body at bottom, long upper shadow  
**Context**: Appears at bottom of downtrend  
**Psychology**: Buyers tried to rally, got rejected, but close held  
**Confirmation**: Next candle closes higher  
**Reliability**: MEDIUM (needs confirmation)

#### Bullish Engulfing
**Description**: Large green candle completely engulfs previous red candle  
**Context**: After downtrend or at support  
**Psychology**: Bulls overwhelm bears, strong reversal signal  
**Confirmation**: Volume above average  
**Reliability**: HIGH  
**Target**: 2× engulfing candle range

#### Morning Star
**Description**: Three candles - large red, small body (any color), large green  
**Context**: Bottom of downtrend  
**Psychology**: Selling exhaustion → indecision → buying surge  
**Confirmation**: Third candle closes above midpoint of first  
**Reliability**: HIGH (one of best patterns)  
**Target**: Height of first candle

#### Piercing Pattern
**Description**: Red candle followed by green that opens below low but closes above midpoint  
**Context**: Downtrend  
**Psychology**: Bears lose control, bulls take over  
**Reliability**: HIGH (needs to pierce 50%+)

#### Three White Soldiers
**Description**: Three consecutive green candles with higher closes  
**Context**: After downtrend or consolidation  
**Psychology**: Strong, sustained buying pressure  
**Reliability**: HIGH (strong continuation signal)  
**Warning**: If appearing after long uptrend at resistance, could be exhaustion

### Reversal Patterns (Bearish)

#### Shooting Star
**Description**: Small body at bottom, long upper shadow (2×+ body)  
**Context**: Appears at top of uptrend  
**Psychology**: Buyers pushed up, sellers rejected high and drove it back down  
**Confirmation**: Next candle closes below shooting star low  
**Reliability**: HIGH (especially with high volume)  
**Stop loss**: Above shooting star high

#### Hanging Man
**Description**: Same as hammer but appears at top of uptrend  
**Context**: Top of uptrend  
**Psychology**: Despite attempted rally, close near low shows weakness  
**Confirmation**: Next candle closes lower  
**Reliability**: MEDIUM

#### Dark Cloud Cover
**Description**: Green candle followed by red that opens above high but closes below midpoint  
**Context**: Uptrend  
**Psychology**: Initial strength, then selling overwhelms  
**Reliability**: HIGH (needs to penetrate 50%+)

#### Evening Star
**Description**: Three candles - large green, small body, large red  
**Context**: Top of uptrend  
**Psychology**: Buying exhaustion → indecision → selling surge  
**Reliability**: HIGH  
**Target**: Height of first candle downward

#### Three Black Crows
**Description**: Three consecutive red candles with lower closes  
**Context**: After uptrend  
**Psychology**: Strong, sustained selling pressure  
**Reliability**: HIGH  
**Warning**: Rare but powerful

### Indecision Patterns

#### Doji
**Description**: Open and close at same price (or very close)  
**Context**: Anywhere  
**Psychology**: Perfect balance between buyers and sellers  
**Interpretation**: Depends on context
- After uptrend: Potential reversal down
- After downtrend: Potential reversal up
- In consolidation: Continued indecision

**Types**:
- **Long-legged**: Long shadows both directions (extreme indecision)
- **Dragonfly**: Long lower shadow only (buyers strong at end)
- **Gravestone**: Long upper shadow only (sellers strong at end)

**Reliability**: MEDIUM (needs confirmation)

#### Spinning Top
**Description**: Small body, long shadows on both sides  
**Context**: Anywhere  
**Psychology**: Buyers and sellers battling, neither winning  
**Reliability**: LOW (indecision, wait for confirmation)

#### Harami
**Description**: Large candle followed by small candle inside it  
**Context**: After strong trend  
**Psychology**: Trend momentum slowing  
**Reliability**: MEDIUM

### Continuation Patterns

#### Marubozu
**Description**: Large body, no shadows (or tiny)  
**Types**:
- **Bullish Marubozu**: Opens at low, closes at high (strong buying)
- **Bearish Marubozu**: Opens at high, closes at low (strong selling)

**Reliability**: HIGH (strong conviction)  
**Use**: Confirms trend continuation

---

## Trading Strategies

### Turtle Trading
**Created by**: Richard Dennis & William Eckhardt (1983)  
**Philosophy**: Trend-following system with strict risk management  

**Entry Rules**:
- **System 1** (short-term): 20-day breakout
- **System 2** (long-term): 55-day breakout

**Position Sizing**:
- Risk 1-2% per trade
- Unit = 1% portfolio risk ÷ N (where N = 20-day ATR)
- Maximum 4 units per position, 12 units total

**Stop Loss**: 2N (2× ATR) from entry  
**Exits**:
- System 1: 10-day low (long) or 10-day high (short)
- System 2: 20-day low (long) or 20-day high (short)

**Learn more**: [Turtle Strategy Documentation](../src/Ksfraser/Finance/Strategies/TurtleStrategy.php)

### Warren Buffett Strategy
**Philosophy**: Value investing - buy wonderful companies at fair prices  

**Quantitative Criteria** (12 Tenets):
1. Business tenets: Simple, consistent, favorable long-term prospects
2. Management tenets: Rational, candid, resists peer pressure
3. Financial tenets: ROE > 15%, high owner earnings, profit margins
4. Market tenets: Intrinsic value, buy at discount

**Qualitative Analysis**:
- Economic moat (switching costs, brand, network effects)
- Management quality (capital allocation, transparency)
- Competitive advantages
- Circle of competence

**Valuation**: 
- Book value growth
- Owner earnings (net income + D&A - capex - working capital)
- Intrinsic value calculation
- Margin of safety: 20-50% discount depending on quality

**Learn more**: [Buffett Qualitative Framework](../prompts/buffett_qualitative_analysis.md)

### Quality Dividend Strategy
**Philosophy**: High-quality dividend-paying stocks with sustainable payouts  

**Criteria**:
- Dividend Aristocrat status (25+ years of increases)
- Payout ratio < 60% (sustainable)
- Dividend safety score > 70
- Consistent earnings growth
- Strong balance sheet

**Dividend Safety Factors**:
- Payout ratio
- Free cash flow coverage
- Debt levels
- Earnings stability
- Business cyclicality

**Best for**: Conservative investors, income generation, lower volatility

---

## Risk Management

### Position Sizing
**What it is**: Determining how many shares/contracts to buy  
**Goal**: Limit risk per trade to acceptable level (typically 1-2%)  

**Formula**: 
```
Shares = (Portfolio Value × Risk%) ÷ (Entry Price - Stop Loss)
```

**Example**:
- Portfolio: $100,000
- Risk per trade: 1% = $1,000
- Entry: $50
- Stop: $48
- Position: $1,000 ÷ ($50 - $48) = 500 shares

### Stop Loss
**What it is**: Pre-determined exit point to limit losses  
**Types**:
- **Fixed**: Specific price level
- **Percentage**: X% below entry
- **ATR-based**: N× ATR below entry (2× ATR common)
- **Technical**: Below support, moving average, pattern
- **Trailing**: Moves up with price, never down

**Best practices**:
- Set before entering trade
- Never move further away
- Wide enough to avoid noise
- Tight enough to limit losses

### Risk/Reward Ratio
**What it is**: Potential profit divided by potential loss  
**Minimum acceptable**: 2:1 (risk $1 to make $2)  
**Calculation**: (Target - Entry) ÷ (Entry - Stop)  

**Example**:
- Entry: $50
- Stop: $48 (risk $2)
- Target: $56 (reward $6)
- R/R: $6 ÷ $2 = 3:1 (good)

### Drawdown
**What it is**: Peak-to-trough decline in portfolio value  
**Formula**: (Trough Value - Peak Value) ÷ Peak Value  
**Acceptable**: < 20% for most investors  

**Recovery math**:
- 10% loss requires 11% gain to recover
- 20% loss requires 25% gain
- 50% loss requires 100% gain

### Sharpe Ratio
**What it is**: Risk-adjusted return measure  
**Formula**: (Return - Risk-Free Rate) ÷ Standard Deviation  
**Interpretation**:
- < 1.0: Bad
- 1.0-2.0: Good
- 2.0-3.0: Very good
- > 3.0: Excellent

---

## Fund Analysis

### MER (Management Expense Ratio)
**What it is**: Annual fee charged by fund (%)  
**Includes**: Management fees, operating costs, taxes  
**Typical ranges**:
- Index ETFs: 0.03% - 0.20%
- Active ETFs: 0.40% - 0.80%
- Mutual Funds: 1.00% - 2.50%
- Seg Funds: 2.00% - 3.00%

**Impact over 25 years on $100,000**:
- 0.20% MER: $128,000 final value
- 1.00% MER: $112,000 (12% less!)
- 2.50% MER: $91,000 (29% less!)

**Rule**: Every 1% MER costs ~20% of returns over 25 years

### Fund Overlap
**What it is**: Percentage of holdings shared between funds  
**Thresholds**:
- < 25%: Low overlap (good diversification)
- 25-50%: Moderate overlap (some redundancy)
- 50-75%: High overlap (excessive redundancy)
- > 75%: Very high overlap (poor diversification)

**Why it matters**: Paying double fees for same exposure

### HHI (Herfindahl-Hirschman Index)
**What it is**: Measures fund concentration  
**Formula**: Sum of squared weights  
**Range**: 0 to 10,000  

**Interpretation**:
- < 1,000: Well diversified
- 1,000-1,800: Moderately concentrated
- > 1,800: Highly concentrated

**Example**:
- 10 equal holdings (10% each): 10 × 10² = 1,000
- Top holding 30%, other 9 at 7.78%: 1,445 (concentrated)

### Segregated Funds (Seg Funds)
**What it is**: Insurance product that invests like mutual fund  
**Unique features**:
- Maturity guarantee (75-100% of deposits)
- Death benefit guarantee
- Creditor protection (in some cases)
- Bypass probate

**Cost**: Higher MER (2-3%) due to insurance features  
**MER Tiers**:
- Retail: 2.50-3.00% (< $100K net worth)
- Preferred: 2.00-2.25% ($100K-$500K)
- Premium: 1.50-1.75% ($500K-$1M)
- Institutional: < 1.50% (> $1M)

**Best for**: Estate planning, creditor protection, risk-averse investors

### Family Aggregation
**What it is**: Combining household net worth for fee qualification  
**Example**: 
- Spouse 1: $75K
- Spouse 2: $60K
- Combined: $135K → Qualifies for Preferred tier

**Benefit**: Lower MER through bulk eligibility

---

## Statistical Concepts

### Beta
**What it is**: Measures stock volatility relative to market  
**Benchmark**: Market beta = 1.0  
**Interpretation**:
- β = 1.0: Moves with market
- β > 1.0: More volatile than market (amplifies moves)
- β < 1.0: Less volatile than market (dampens moves)
- β = 0: No correlation to market
- β < 0: Inverse correlation (rare)

**Example**: β = 1.5 means if market up 10%, stock typically up 15%

### Correlation
**What it is**: Measures relationship between two securities  
**Range**: -1.0 to +1.0  
**Interpretation**:
- +1.0: Perfect positive correlation (move together)
- 0: No correlation
- -1.0: Perfect negative correlation (move opposite)

**Diversification**: Seek low or negative correlations

### Standard Deviation
**What it is**: Measures dispersion of returns  
**Higher value**: More volatility/risk  
**Use**: Risk assessment, comparing investments  

**68-95-99.7 Rule**:
- 68% of returns within ±1 std dev
- 95% within ±2 std dev
- 99.7% within ±3 std dev

---

## Quick Reference Card

### Overbought/Oversold Levels
| Indicator | Overbought | Oversold |
|-----------|------------|----------|
| RSI | > 70 | < 30 |
| Stochastic | > 80 | < 20 |
| MFI | > 80 | < 20 |
| Williams %R | > -20 | < -80 |
| CCI | > +100 | < -100 |

### Moving Average Periods
| Timeframe | Short | Medium | Long |
|-----------|-------|--------|------|
| Day Trading | 9 | 20 | 50 |
| Swing Trading | 20 | 50 | 200 |
| Position Trading | 50 | 100 | 200 |

### Risk Management Rules
- **Risk per trade**: 1-2% of portfolio
- **Max open risk**: 6-10% of portfolio
- **Min Risk/Reward**: 2:1
- **Max drawdown**: 20%
- **Position size**: Account for ATR
- **Stop loss**: Always set before entry

---

**Last Updated**: December 3, 2025  
**For Technical Support**: See [System Design Document](../Project_Work_Products/ProjectDocuments/Design/SYSTEM_DESIGN_DOCUMENT.md)  
**For Implementation**: See [TA-Lib Integration Guide](../Project_Work_Products/ProjectDocuments/Design/Technical/TA-Lib_Integration_Analysis.md)
