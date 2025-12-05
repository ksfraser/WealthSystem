# Strategy Tooltips & Help System

This document provides tooltip text and help content for all trading strategies in the WealthSystem. Use this content for UI tooltips, help dialogs, and user guidance.

---

## Strategy Quick Reference (For Tooltips)

### Moving Average Crossover
**Tooltip**: "Trend-following strategy using fast/slow MA crossovers. Golden cross (BUY) when fast crosses above slow, death cross (SELL) when below."

**Help Text**: "Identifies trend changes by comparing two moving averages. The fast MA responds quickly to price changes while the slow MA smooths out noise. When the fast MA crosses above the slow MA (golden cross), it signals an uptrend. When it crosses below (death cross), it signals a downtrend. Best for trending markets."

---

### RSI (Relative Strength Index)
**Tooltip**: "Momentum oscillator identifying overbought (>70) and oversold (<30) conditions. BUY below 30, SELL above 70."

**Help Text**: "Measures the speed and magnitude of price changes from 0-100. Values below 30 indicate oversold conditions (potential bounce), while values above 70 indicate overbought conditions (potential pullback). Extreme readings (<20 or >80) provide higher confidence signals. Best for range-bound markets and reversals."

---

### Bollinger Bands
**Tooltip**: "Volatility bands around price. BUY when price touches lower band, SELL when price touches upper band. Also detects squeezes and breakouts."

**Help Text**: "Creates upper and lower bands at 2 standard deviations from a 20-period moving average. When price touches the lower band, it's often oversold. When it touches the upper band, it's often overbought. Band width indicates volatility - narrow bands (squeeze) often precede large moves. Best for mean reversion and volatility trading."

---

### MACD (Moving Average Convergence Divergence)
**Tooltip**: "Trend-following momentum indicator. BUY when MACD crosses above signal line with positive histogram, SELL when crosses below with negative histogram."

**Help Text**: "Shows the relationship between two exponential moving averages (12 and 26 periods). The MACD line is the difference between these EMAs. The signal line is a 9-period EMA of the MACD line. Crossovers indicate momentum shifts. The histogram shows the distance between MACD and signal lines. Best for identifying trend changes and momentum."

---

### Stochastic Oscillator
**Tooltip**: "Momentum indicator comparing closing price to price range. BUY when %K crosses above %D below 20 (oversold), SELL when crosses below above 80 (overbought)."

**Help Text**: "Compares the current closing price to the price range over a period (typically 14). %K is the fast line, %D is a smoothed version. Values below 20 indicate oversold conditions, above 80 indicate overbought. Crossovers in these extreme zones provide high-probability reversal signals. Best for short-term reversals."

---

### Ichimoku Cloud
**Tooltip**: "Advanced 5-component Japanese analysis system. BUY when price above bullish cloud, SELL when below bearish cloud. Considers Tenkan/Kijun crossovers."

**Help Text**: "Comprehensive trend identification system using five components: Tenkan-sen (9-period conversion line), Kijun-sen (26-period base line), Senkou Span A & B (leading spans creating the cloud), and Chikou Span (lagging span). Cloud color indicates trend - green/light is bullish, red/dark is bearish. Price above cloud confirms uptrend, below confirms downtrend. Cloud thickness shows support/resistance strength. Best for comprehensive trend analysis."

**Components**:
- **Tenkan-sen (Conversion Line)**: (9-high + 9-low) / 2
- **Kijun-sen (Base Line)**: (26-high + 26-low) / 2
- **Senkou Span A**: (Tenkan + Kijun) / 2, plotted 26 ahead
- **Senkou Span B**: (52-high + 52-low) / 2, plotted 26 ahead
- **Chikou Span**: Close, plotted 26 behind

---

### Fibonacci Retracement
**Tooltip**: "Uses golden ratio (61.8%) and other Fibonacci levels to identify support/resistance. BUY at 0.618 support bounce, SELL at 0.618 resistance rejection."

**Help Text**: "Identifies potential support and resistance levels based on Fibonacci ratios (23.6%, 38.2%, 50%, 61.8%, 78.6%). The 61.8% level (golden ratio) is the most significant. In an uptrend, price often retraces to 61.8% before continuing up. In a downtrend, it often retraces to 61.8% before continuing down. Detects bounces (price reverses at level), rejections (price fails at level), and breakouts (price breaks through level). Best for retracement trading in trending markets."

**Key Levels**:
- **23.6%**: Shallow retracement
- **38.2%**: Moderate retracement
- **50.0%**: Midpoint
- **61.8%**: Golden ratio (strongest level)
- **78.6%**: Deep retracement

---

### Volume Profile
**Tooltip**: "Analyzes price distribution by volume. BUY at Value Area Low (support), SELL at Value Area High (resistance). POC shows equilibrium."

**Help Text**: "Maps trading volume across price levels to identify key support and resistance zones. Point of Control (POC) is the price with highest volume - the market's agreed 'fair value'. Value Area contains 70% of volume and represents the fair price range. Value Area Low (VAL) acts as support, Value Area High (VAH) acts as resistance. High Volume Nodes (HVN) are strong levels where lots of trading occurred. Low Volume Nodes (LVN) are weak levels price may pass through easily. Best for day trading and identifying high-probability levels."

**Key Terms**:
- **POC**: Price with highest volume (strongest level)
- **Value Area**: Range containing 70% of volume
- **VAH**: Value Area High (resistance)
- **VAL**: Value Area Low (support)
- **HVN**: High Volume Nodes (strong levels)
- **LVN**: Low Volume Nodes (weak levels)

---

### Support/Resistance
**Tooltip**: "Identifies horizontal price levels where buying (support) or selling (resistance) pressure is strong. BUY at support, SELL at resistance. Detects breakouts with volume."

**Help Text**: "Finds price levels where the market has historically reversed direction. Support is where buying pressure exceeds selling (floor), resistance is where selling exceeds buying (ceiling). The more times a level is tested without breaking, the stronger it becomes. Calculates pivot points as mathematical support/resistance levels. Detects breakouts (price breaks above resistance with 20%+ volume) and breakdowns (price breaks below support with 20%+ volume). Level strength increases with touch count. Best for swing trading and key entry/exit points."

**Key Concepts**:
- **Support**: Buying pressure floor
- **Resistance**: Selling pressure ceiling
- **Pivot Point**: (H + L + C) / 3
- **Breakout**: Break above resistance with volume
- **Breakdown**: Break below support with volume
- **Touch Count**: Number of tests (more = stronger)

---

## Confidence Score Guidance (For UI Display)

### Confidence Meter Colors
- **0.8-1.0** (Green): Very High - Strong signal with multiple confirmations
- **0.65-0.79** (Light Green): High - Reliable signal, good setup
- **0.5-0.64** (Yellow): Moderate - Valid signal, less conviction
- **0.3-0.49** (Orange): Low - Weak signal, wait for better setup
- **Below 0.3** (Red): Very Low - Insufficient data or no signal

### Recommended Actions by Confidence
```
Confidence >= 0.80: "Strong signal - consider full position"
Confidence >= 0.65: "Good setup - consider 75% position"
Confidence >= 0.50: "Valid signal - consider 50% position"
Confidence >= 0.30: "Weak signal - consider 25% position or wait"
Confidence <  0.30: "Insufficient data - wait for better setup"
```

---

## Strategy Selection Helper (For UI Dropdown/Help)

### By Market Condition
```
Strong Uptrend → Use: MA Crossover, MACD, Ichimoku
Strong Downtrend → Use: MA Crossover, MACD, Ichimoku
Sideways/Range → Use: RSI, Stochastic, Bollinger Bands, Support/Resistance
High Volatility → Use: Bollinger Bands, Volume Profile
Trending with Pullbacks → Use: Fibonacci, Ichimoku, Support/Resistance
```

### By Trading Style
```
Day Trading → Use: Stochastic, Volume Profile, Support/Resistance
Swing Trading → Use: MACD, Ichimoku, Fibonacci, Support/Resistance
Position Trading → Use: MA Crossover, Ichimoku
Retracement Trading → Use: Fibonacci, Support/Resistance
Breakout Trading → Use: Bollinger Bands, Volume Profile, Support/Resistance
```

---

## Parameter Help Text

### Moving Average Crossover
- **fast_period** (default: 10): "Number of periods for fast moving average. Lower values respond quicker to price changes."
- **slow_period** (default: 20): "Number of periods for slow moving average. Higher values smooth out noise."

### RSI
- **period** (default: 14): "Number of periods for RSI calculation. 14 is standard, lower values are more sensitive."
- **overbought_threshold** (default: 70): "RSI level above which market is considered overbought. Standard is 70."
- **oversold_threshold** (default: 30): "RSI level below which market is considered oversold. Standard is 30."

### Bollinger Bands
- **period** (default: 20): "Number of periods for moving average calculation. 20 is standard."
- **std_dev** (default: 2): "Standard deviation multiplier. 2 captures ~95% of price action."

### MACD
- **fast_period** (default: 12): "Fast EMA period. Standard is 12."
- **slow_period** (default: 26): "Slow EMA period. Standard is 26."
- **signal_period** (default: 9): "Signal line EMA period. Standard is 9."

### Stochastic Oscillator
- **k_period** (default: 14): "%K calculation period. Standard is 14."
- **d_period** (default: 3): "%D smoothing period. Standard is 3."
- **overbought** (default: 80): "Overbought threshold. Standard is 80."
- **oversold** (default: 20): "Oversold threshold. Standard is 20."

### Ichimoku Cloud
- **tenkan_period** (default: 9): "Conversion line period. Standard is 9."
- **kijun_period** (default: 26): "Base line period. Standard is 26."
- **senkou_b_period** (default: 52): "Leading Span B period. Standard is 52."

### Fibonacci Retracement
- **lookback_period** (default: 50): "Number of periods to find swing high/low. Higher values find larger swings."
- **proximity_threshold** (default: 0.02): "Distance threshold for 'near' a level (2% = 0.02)."

### Volume Profile
- **price_levels** (default: 30): "Number of price bins for volume distribution. More levels = finer granularity."
- **value_area_percentage** (default: 0.70): "Percentage of volume in Value Area. Standard is 70% (0.70)."
- **proximity_threshold** (default: 0.01): "Distance threshold for 'at' a level (1% = 0.01)."

### Support/Resistance
- **proximity_threshold** (default: 0.02): "Distance threshold for 'at' a level (2% = 0.02)."
- **min_touches** (default: 2): "Minimum touches for a valid level. Higher values = stronger levels only."
- **lookback_period** (default: 30): "Number of periods to find support/resistance. Higher values find older levels."

---

## Signal Explanations (For Result Display)

### Common Signal Reasons
```
"Golden cross - fast MA crossed above slow MA" → Bullish trend beginning
"Death cross - fast MA crossed below slow MA" → Bearish trend beginning
"RSI oversold (< 30) - potential bounce" → Mean reversion opportunity
"RSI overbought (> 70) - potential pullback" → Take profit opportunity
"Price at lower Bollinger Band - oversold" → Potential reversal
"Price at upper Bollinger Band - overbought" → Potential reversal
"MACD bullish crossover with positive histogram" → Strong upward momentum
"MACD bearish crossover with negative histogram" → Strong downward momentum
"Stochastic bullish crossover in oversold zone" → Short-term reversal up
"Stochastic bearish crossover in overbought zone" → Short-term reversal down
"Price above bullish Ichimoku cloud" → Confirmed uptrend
"Price below bearish Ichimoku cloud" → Confirmed downtrend
"Bounce at 0.618 Fibonacci support" → Golden ratio reversal
"Rejection at 0.618 Fibonacci resistance" → Golden ratio reversal
"Price at Value Area Low (support)" → Volume-based support
"Price at Value Area High (resistance)" → Volume-based resistance
"Price at support level (3 touches)" → Strong support test
"Breakout above resistance with strong volume" → Momentum continuation
```

---

## Usage in Code

### Tooltip Implementation
```php
// In your view/template
<div class="strategy-selector">
    <select name="strategy" data-tooltip="tooltip-text">
        <option value="ma_crossover" 
                data-tooltip="Trend-following strategy using fast/slow MA crossovers">
            Moving Average Crossover
        </option>
        <!-- More options... -->
    </select>
</div>
```

### Help Dialog Implementation
```php
// In your JavaScript
function showStrategyHelp(strategyName) {
    const helpText = {
        'ma_crossover': 'Identifies trend changes by comparing two moving averages...',
        'rsi': 'Measures the speed and magnitude of price changes from 0-100...',
        // ... etc
    };
    
    displayModal(helpText[strategyName]);
}
```

### Confidence Display
```php
// In your result display
function getConfidenceClass(confidence) {
    if (confidence >= 0.80) return 'confidence-very-high';
    if (confidence >= 0.65) return 'confidence-high';
    if (confidence >= 0.50) return 'confidence-moderate';
    if (confidence >= 0.30) return 'confidence-low';
    return 'confidence-very-low';
}

function getConfidenceText(confidence) {
    if (confidence >= 0.80) return 'Very High - Strong signal';
    if (confidence >= 0.65) return 'High - Reliable signal';
    if (confidence >= 0.50) return 'Moderate - Valid signal';
    if (confidence >= 0.30) return 'Low - Weak signal';
    return 'Very Low - Insufficient data';
}
```

---

**For complete strategy details, see `docs/Strategy_Documentation.md`**
