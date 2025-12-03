# TA-Lib Integration Analysis

**Document Version**: 1.0  
**Date**: December 3, 2025  
**Status**: Implementation Guide  

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Available Functions](#available-functions)
4. [Implementation Architecture](#implementation-architecture)
5. [Candlestick Patterns](#candlestick-patterns)
6. [Technical Indicators](#technical-indicators)
7. [Usage Examples](#usage-examples)
8. [Testing Strategy](#testing-strategy)
9. [Performance Considerations](#performance-considerations)

---

## Overview

TA-Lib (Technical Analysis Library) is a widely-used library providing 150+ technical analysis functions. This document outlines the integration of TA-Lib into the WealthSystem platform.

### Why TA-Lib?

- **Comprehensive**: 150+ indicators covering all major technical analysis methods
- **Battle-tested**: Used by professional traders for 20+ years
- **Standardized**: Industry-standard calculations
- **Fast**: Written in C for performance
- **Well-documented**: Clear function signatures and parameters

### Integration Goals

1. Replace manual indicator calculations with TA-Lib
2. Add 63 candlestick pattern recognition functions
3. Provide 150+ technical indicators
4. Build pattern-based trading signals
5. Maintain backwards compatibility with existing code

---

## Installation

### Step 1: Install TA-Lib C Library

#### Windows
```powershell
# Download from: http://prdownloads.sourceforge.net/ta-lib/ta-lib-0.4.0-msvc.zip
# Extract to C:\ta-lib
# Add C:\ta-lib\bin to PATH
```

#### Linux (Ubuntu/Debian)
```bash
wget http://prdownloads.sourceforge.net/ta-lib/ta-lib-0.4.0-src.tar.gz
tar -xzf ta-lib-0.4.0-src.tar.gz
cd ta-lib/
./configure --prefix=/usr
make
sudo make install
```

#### macOS
```bash
brew install ta-lib
```

### Step 2: Install PHP TA-Lib Extension

```bash
# Via PECL
pecl install trader

# Or compile from source
git clone https://github.com/php-trader/trader.git
cd trader
phpize
./configure
make
sudo make install
```

### Step 3: Enable Extension

Add to `php.ini`:
```ini
extension=trader.so  # Linux/Mac
extension=trader.dll # Windows
```

### Step 4: Verify Installation

```php
<?php
if (extension_loaded('trader')) {
    echo "TA-Lib Trader extension loaded!\n";
    $functions = get_extension_funcs('trader');
    echo "Available functions: " . count($functions) . "\n";
    print_r(array_slice($functions, 0, 10)); // Show first 10
} else {
    die("ERROR: Trader extension not loaded\n");
}
?>
```

---

## Available Functions

### Function Categories

| Category | Count | Description |
|----------|-------|-------------|
| **Overlap Studies** | 17 | Moving averages, Bollinger Bands |
| **Momentum Indicators** | 30 | RSI, MACD, Stochastic, CCI |
| **Volume Indicators** | 3 | OBV, AD, ADOSC |
| **Volatility Indicators** | 3 | ATR, NATR, TRANGE |
| **Price Transform** | 4 | AVGPRICE, MEDPRICE, TYPPRICE |
| **Cycle Indicators** | 5 | Hilbert Transform functions |
| **Pattern Recognition** | 63 | Candlestick patterns |
| **Statistic Functions** | 11 | Beta, Correlation, StdDev |
| **Math Transform** | 15 | Trigonometric, Log, Sqrt |
| **Math Operators** | 9 | ADD, SUB, MULT, DIV |

**Total**: 160+ functions

---

## Implementation Architecture

### Service Layer

```
┌─────────────────────────────────────────────────────────┐
│         TechnicalAnalysisService (Orchestrator)         │
│  - Manages all technical analysis operations            │
│  - Caches calculated indicators                         │
│  - Provides unified API                                 │
└─────────────────────────────────────────────────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         │                 │                 │
┌────────▼────────┐ ┌──────▼──────────┐ ┌──▼────────────────┐
│   Indicator     │ │  Candlestick    │ │   Pattern         │
│   Calculator    │ │  Pattern        │ │   Recognition     │
│                 │ │  Calculator     │ │   Engine          │
│ - Trend         │ │ - 63 patterns   │ │ - Signal gen      │
│ - Momentum      │ │ - Reliability   │ │ - Confirmation    │
│ - Volatility    │ │ - Targets       │ │ - Backtesting     │
└─────────────────┘ └─────────────────┘ └───────────────────┘
         │                 │                     │
         └─────────────────┴─────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────┐
│         TA-Lib PHP Extension (trader_*)                 │
│  - C library bindings                                   │
│  - High-performance calculations                        │
└─────────────────────────────────────────────────────────┘
```

### Database Schema

```sql
-- Technical Indicators
CREATE TABLE IF NOT EXISTS technical_indicators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT NOT NULL,
    date TEXT NOT NULL,
    indicator_type TEXT NOT NULL,
    indicator_value REAL,
    period INTEGER,
    signal_line REAL,
    histogram REAL,
    metadata TEXT, -- JSON for additional params
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, date, indicator_type, period)
);

-- Candlestick Patterns
CREATE TABLE IF NOT EXISTS candlestick_patterns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol TEXT NOT NULL,
    date TEXT NOT NULL,
    pattern_name TEXT NOT NULL,
    pattern_value INTEGER, -- -100 (bearish), 0 (none), +100 (bullish)
    reliability TEXT CHECK(reliability IN ('LOW', 'MEDIUM', 'HIGH')),
    confirmation_price REAL,
    target_price REAL,
    invalidation_price REAL,
    notes TEXT,
    detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, date, pattern_name)
);

-- Pattern Performance (for backtesting)
CREATE TABLE IF NOT EXISTS pattern_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    pattern_name TEXT NOT NULL,
    symbol TEXT,
    sector TEXT,
    total_occurrences INTEGER DEFAULT 0,
    successful_occurrences INTEGER DEFAULT 0,
    success_rate REAL,
    avg_gain_percent REAL,
    avg_days_to_target REAL,
    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

---

## Candlestick Patterns

### All 63 Patterns

#### Bullish Reversal Patterns (18)
1. `CDL2CROWS` - Two Crows
2. `CDL3BLACKCROWS` - Three Black Crows (bearish, but listed here)
3. `CDL3INSIDE` - Three Inside Up
4. `CDL3LINESTRIKE` - Three-Line Strike
5. `CDL3OUTSIDE` - Three Outside Up
6. `CDL3STARSINSOUTH` - Three Stars In The South
7. `CDL3WHITESOLDIERS` - Three Advancing White Soldiers
8. `CDLABANDONEDBABY` - Abandoned Baby
9. `CDLBREAKAWAY` - Breakaway
10. `CDLCLOSINGMARUBOZU` - Closing Marubozu
11. `CDLCONCEALBABYSWALL` - Concealing Baby Swallow
12. `CDLCOUNTERATTACK` - Counterattack
13. `CDLENGULFING` - Engulfing Pattern (Bullish)
14. `CDLHAMMER` - Hammer
15. `CDLINVERTEDHAMMER` - Inverted Hammer
16. `CDLKICKING` - Kicking
17. `CDLKICKINGBYLENGTH` - Kicking (by body length)
18. `CDLMORNINGDOJISTAR` - Morning Doji Star
19. `CDLMORNINGSTAR` - Morning Star
20. `CDLPIERCING` - Piercing Pattern

#### Bearish Reversal Patterns (15)
21. `CDLDARKCLOUDCOVER` - Dark Cloud Cover
22. `CDLEVENINGDOJISTAR` - Evening Doji Star
23. `CDLEVENINGSTAR` - Evening Star
24. `CDLGRAVESTONEDOJI` - Gravestone Doji
25. `CDLHANGINGMAN` - Hanging Man
26. `CDLSHOOTINGSTAR` - Shooting Star
27. `CDLSTICKSANDWICH` - Stick Sandwich
28. `CDL3BLACKCROWS` - Three Black Crows
29. `CDLIDENTICAL3CROWS` - Identical Three Crows
30. `CDLUPSIDEGAP2CROWS` - Upside Gap Two Crows

#### Continuation Patterns (10)
31. `CDLADVANCEBLOCK` - Advance Block
32. `CDLBELTHOLD` - Belt-hold
33. `CDLGAPSIDESIDEWHITE` - Up/Down-gap side-by-side white lines
34. `CDLHIGHWAVE` - High-Wave Candle
35. `CDLHOMINGPIGEON` - Homing Pigeon
36. `CDLINNECK` - In-Neck Pattern
37. `CDLONNECK` - On-Neck Pattern
38. `CDLSEPARATINGLINES` - Separating Lines
39. `CDLTHRUSTING` - Thrusting Pattern
40. `CDLTRISTAR` - Tristar Pattern

#### Indecision/Reversal Patterns (10)
41. `CDLDOJI` - Doji
42. `CDLDOJISTAR` - Doji Star
43. `CDLDRAGONFLYDOJI` - Dragonfly Doji
44. `CDLHARAMI` - Harami Pattern
45. `CDLHARAMICROSS` - Harami Cross Pattern
46. `CDLLADDERBOTTOM` - Ladder Bottom
47. `CDLLONGLEGGEDDOJI` - Long Legged Doji
48. `CDLRICKSHAWMAN` - Rickshaw Man
49. `CDLSPINNINGTOP` - Spinning Top
50. `CDLSTALLEDPATTERN` - Stalled Pattern

#### Strong Trend Patterns (13)
51. `CDLMARUBOZU` - Marubozu (strong candle)
52. `CDLMATCHINGLOW` - Matching Low
53. `CDLMATHOLD` - Mat Hold
54. `CDLRISEFALL3METHODS` - Rising/Falling Three Methods
55. `CDLTAKURI` - Takuri (Dragonfly Doji with long shadow)
56. `CDLTASUKIGAP` - Tasuki Gap
57. `CDLUNIQUE3RIVER` - Unique 3 River
58. `CDLXSIDEGAP3METHODS` - Upside/Downside Gap Three Methods
59. `CDLLONGLINE` - Long Line Candle
60. `CDLSHORTLINE` - Short Line Candle
61. `CDLHIKKAKE` - Hikkake Pattern
62. `CDLHIKKAKEMOD` - Modified Hikkake Pattern
63. `CDLSTALLEDPATTERN` - Stalled Pattern

### Pattern Reliability Classification

**HIGH Reliability** (65%+ win rate):
- Engulfing Pattern
- Morning Star / Evening Star
- Three White Soldiers / Three Black Crows
- Hammer (at support)
- Shooting Star (at resistance)
- Piercing Pattern / Dark Cloud Cover

**MEDIUM Reliability** (55-65% win rate):
- Doji (context-dependent)
- Harami
- Inverted Hammer
- Hanging Man
- Belt-hold

**LOW Reliability** (<55% win rate):
- Spinning Top
- High-Wave
- Most single-candle patterns without confirmation

### Pattern Detection Example

```php
<?php
// Detect Hammer pattern
$hammer = trader_cdlhammer($open, $high, $low, $close);

// Returns array of integers:
// +100 = Bullish hammer detected
// 0 = No pattern
// -100 = Not applicable (hammer can only be bullish)

// Example usage:
foreach ($hammer as $i => $value) {
    if ($value == 100) {
        echo "Hammer detected on {$dates[$i]} at price {$close[$i]}\n";
        // Generate BUY signal
    }
}
?>
```

---

## Technical Indicators

### Overlap Studies (Moving Averages)

#### Simple Moving Average (SMA)
```php
trader_sma(array $real, int $timePeriod = 30): array
```

**Use Cases**:
- Trend identification
- Support/resistance levels
- Crossover strategies

**Common Periods**: 20, 50, 100, 200

---

#### Exponential Moving Average (EMA)
```php
trader_ema(array $real, int $timePeriod = 30): array
```

**Advantages over SMA**:
- More responsive to recent price changes
- Reduces lag
- Better for short-term trading

**Common Periods**: 12, 26 (MACD), 9 (signal line)

---

#### Weighted Moving Average (WMA)
```php
trader_wma(array $real, int $timePeriod = 30): array
```

**Characteristics**:
- Linear weighting (recent prices weighted higher)
- Less lag than SMA, more than EMA
- Good balance for medium-term trends

---

#### Bollinger Bands
```php
trader_bbands(
    array $real,
    int $timePeriod = 20,
    float $nbDevUp = 2.0,
    float $nbDevDn = 2.0,
    int $mAType = TRADER_MA_TYPE_SMA
): array
```

**Returns**: `[upper_band, middle_band, lower_band]`

**Interpretation**:
- Price touching upper band → Overbought
- Price touching lower band → Oversold
- Band width → Volatility measure
- Band squeeze → Breakout imminent

---

### Momentum Indicators

#### Relative Strength Index (RSI)
```php
trader_rsi(array $real, int $timePeriod = 14): array
```

**Range**: 0 to 100

**Interpretation**:
- RSI > 70 → Overbought (potential sell)
- RSI < 30 → Oversold (potential buy)
- RSI 50 → Neutral
- Divergence from price → Reversal signal

---

#### MACD (Moving Average Convergence Divergence)
```php
trader_macd(
    array $real,
    int $fastPeriod = 12,
    int $slowPeriod = 26,
    int $signalPeriod = 9
): array
```

**Returns**: `[macd_line, signal_line, histogram]`

**Signals**:
- MACD crosses above signal → Bullish
- MACD crosses below signal → Bearish
- Histogram growing → Trend strengthening
- Histogram shrinking → Trend weakening

---

#### Stochastic Oscillator
```php
trader_stoch(
    array $high,
    array $low,
    array $close,
    int $fastK_Period = 5,
    int $slowK_Period = 3,
    int $slowK_MAType = TRADER_MA_TYPE_SMA,
    int $slowD_Period = 3,
    int $slowD_MAType = TRADER_MA_TYPE_SMA
): array
```

**Returns**: `[slowK, slowD]`

**Interpretation**:
- %K > 80 → Overbought
- %K < 20 → Oversold
- %K crosses above %D → Buy
- %K crosses below %D → Sell

---

#### Commodity Channel Index (CCI)
```php
trader_cci(
    array $high,
    array $low,
    array $close,
    int $timePeriod = 14
): array
```

**Range**: Typically -100 to +100

**Interpretation**:
- CCI > +100 → Overbought
- CCI < -100 → Oversold
- Breaking above +100 → Strong uptrend
- Breaking below -100 → Strong downtrend

---

### Volatility Indicators

#### Average True Range (ATR)
```php
trader_atr(
    array $high,
    array $low,
    array $close,
    int $timePeriod = 14
): array
```

**Use Cases**:
- Volatility measurement
- Stop loss placement (e.g., 2 × ATR)
- Position sizing (Turtle Trading)
- Breakout validation

---

#### Standard Deviation
```php
trader_stddev(
    array $real,
    int $timePeriod = 20,
    float $nbDev = 1.0
): array
```

**Use Cases**:
- Volatility measurement
- Risk assessment
- Bollinger Band calculation
- Options pricing (implied volatility)

---

### Volume Indicators

#### On Balance Volume (OBV)
```php
trader_obv(array $close, array $volume): array
```

**Interpretation**:
- OBV rising with price → Uptrend confirmed
- OBV falling with price → Downtrend confirmed
- OBV diverging from price → Potential reversal

---

#### Chaikin A/D Line
```php
trader_ad(
    array $high,
    array $low,
    array $close,
    array $volume
): array
```

**Interpretation**:
- Accumulation/Distribution line
- Rising → Buying pressure
- Falling → Selling pressure
- Divergence → Reversal warning

---

### Trend Indicators

#### Average Directional Index (ADX)
```php
trader_adx(
    array $high,
    array $low,
    array $close,
    int $timePeriod = 14
): array
```

**Range**: 0 to 100

**Interpretation**:
- ADX < 20 → Weak trend (range-bound)
- ADX 20-25 → Trend emerging
- ADX 25-50 → Strong trend
- ADX > 50 → Very strong trend
- ADX direction irrelevant (measures strength only)

---

#### Parabolic SAR
```php
trader_sar(
    array $high,
    array $low,
    float $acceleration = 0.02,
    float $maximum = 0.2
): array
```

**Interpretation**:
- SAR below price → Uptrend (hold long)
- SAR above price → Downtrend (hold short)
- SAR flips → Trend reversal
- Trailing stop loss indicator

---

## Usage Examples

### Example 1: Detect Hammer with Volume Confirmation

```php
<?php
namespace App\Services\Trading;

use App\Services\MarketDataService;

class CandlestickPatternStrategy
{
    private MarketDataService $marketData;
    
    public function detectHammerWithConfirmation(string $symbol): ?array
    {
        // Fetch 100 days of data
        $data = $this->marketData->getHistoricalPrices($symbol, '-100 days', 'today');
        
        // Extract OHLCV arrays
        $open = array_column($data, 'open');
        $high = array_column($data, 'high');
        $low = array_column($data, 'low');
        $close = array_column($data, 'close');
        $volume = array_column($data, 'volume');
        
        // Detect hammer pattern
        $hammers = trader_cdlhammer($open, $high, $low, $close);
        
        // Calculate average volume (20-day)
        $avgVolume = trader_sma($volume, 20);
        
        // Find most recent hammer
        $lastIndex = count($hammers) - 1;
        
        if ($hammers[$lastIndex] == 100) {
            // Hammer detected - check volume confirmation
            $currentVolume = $volume[$lastIndex];
            $avgVol = $avgVolume[$lastIndex];
            
            if ($currentVolume > $avgVol * 1.2) {
                // High volume confirmation
                $entry = $close[$lastIndex];
                $stop = $low[$lastIndex] * 0.97; // 3% below low
                $target = $entry + (($entry - $stop) * 2); // 2:1 reward/risk
                
                return [
                    'signal' => 'BUY',
                    'pattern' => 'HAMMER',
                    'confidence' => 0.75, // High reliability pattern with volume
                    'entry_price' => $entry,
                    'stop_loss' => $stop,
                    'take_profit' => $target,
                    'volume_ratio' => $currentVolume / $avgVol,
                    'reasoning' => "Hammer pattern with {$currentVolume/$avgVol}x volume"
                ];
            }
        }
        
        return null;
    }
}
?>
```

### Example 2: RSI + MACD Combo Strategy

```php
<?php
public function rsimACDCombo(string $symbol): ?array
{
    $data = $this->marketData->getHistoricalPrices($symbol, '-200 days', 'today');
    $close = array_column($data, 'close');
    
    // Calculate RSI(14)
    $rsi = trader_rsi($close, 14);
    
    // Calculate MACD
    [$macd, $signal, $histogram] = trader_macd($close);
    
    $lastIndex = count($close) - 1;
    $prevIndex = $lastIndex - 1;
    
    // Buy conditions:
    // 1. RSI was oversold (<30) and now recovering
    // 2. MACD crossing above signal line
    
    $rsiOversoldRecovery = $rsi[$prevIndex] < 30 && $rsi[$lastIndex] > 30;
    $macdBullishCross = $macd[$prevIndex] < $signal[$prevIndex] && 
                        $macd[$lastIndex] > $signal[$lastIndex];
    
    if ($rsiOversoldRecovery && $macdBullishCross) {
        return [
            'signal' => 'BUY',
            'confidence' => 0.80,
            'entry_price' => $close[$lastIndex],
            'stop_loss' => $close[$lastIndex] * 0.95,
            'indicators' => [
                'rsi' => $rsi[$lastIndex],
                'macd' => $macd[$lastIndex],
                'signal' => $signal[$lastIndex],
                'histogram' => $histogram[$lastIndex]
            ],
            'reasoning' => 'RSI oversold recovery + MACD bullish crossover'
        ];
    }
    
    return null;
}
?>
```

### Example 3: Multi-Pattern Scan

```php
<?php
public function scanAllPatterns(string $symbol): array
{
    $data = $this->marketData->getHistoricalPrices($symbol, '-60 days', 'today');
    
    $open = array_column($data, 'open');
    $high = array_column($data, 'high');
    $low = array_column($data, 'low');
    $close = array_column($data, 'close');
    
    $patterns = [
        'HAMMER' => trader_cdlhammer($open, $high, $low, $close),
        'SHOOTING_STAR' => trader_cdlshootingstar($open, $high, $low, $close),
        'ENGULFING' => trader_cdlengulfing($open, $high, $low, $close),
        'DOJI' => trader_cdldoji($open, $high, $low, $close),
        'MORNING_STAR' => trader_cdlmorningstar($open, $high, $low, $close),
        'EVENING_STAR' => trader_cdleveningstar($open, $high, $low, $close),
        'THREE_WHITE_SOLDIERS' => trader_cdl3whitesoldiers($open, $high, $low, $close),
        'THREE_BLACK_CROWS' => trader_cdl3blackcrows($open, $high, $low, $close)
    ];
    
    $detected = [];
    $lastIndex = count($close) - 1;
    
    foreach ($patterns as $name => $values) {
        if ($values[$lastIndex] != 0) {
            $detected[] = [
                'pattern' => $name,
                'value' => $values[$lastIndex],
                'direction' => $values[$lastIndex] > 0 ? 'BULLISH' : 'BEARISH',
                'price' => $close[$lastIndex]
            ];
        }
    }
    
    return $detected;
}
?>
```

---

## Testing Strategy

### Unit Tests

```php
<?php
namespace Tests\Unit\Services\Calculators;

use PHPUnit\Framework\TestCase;
use App\Services\Calculators\CandlestickPatternCalculator;

class CandlestickPatternCalculatorTest extends TestCase
{
    public function testHammerDetection()
    {
        $calculator = new CandlestickPatternCalculator();
        
        // Create hammer candle data
        $data = [
            'open' => [10.5],
            'high' => [10.6],
            'low' => [9.0],  // Long lower shadow
            'close' => [10.4] // Close near open
        ];
        
        $result = $calculator->detectPattern('HAMMER', $data);
        
        $this->assertEquals(100, $result[0]); // Should detect bullish hammer
    }
    
    public function testEngulfingPattern()
    {
        $calculator = new CandlestickPatternCalculator();
        
        // Bullish engulfing: small red candle followed by large green candle
        $data = [
            'open' => [10.0, 9.5],
            'high' => [10.1, 11.0],
            'low' => [9.5, 9.0],
            'close' => [9.6, 10.8]
        ];
        
        $result = $calculator->detectPattern('ENGULFING', $data);
        
        $this->assertEquals(100, $result[1]); // Bullish engulfing on second candle
    }
}
?>
```

### Integration Tests

```php
<?php
public function testPatternBasedTrading()
{
    $strategy = new CandlestickPatternStrategyService(
        $this->marketData,
        $this->repository
    );
    
    // Test on known historical pattern
    $signal = $strategy->analyze('AAPL', '2024-01-15');
    
    $this->assertNotNull($signal);
    $this->assertEquals('BUY', $signal['signal']);
    $this->assertArrayHasKey('pattern', $signal);
    $this->assertGreaterThan(0.6, $signal['confidence']);
}
?>
```

---

## Performance Considerations

### Caching Strategy

```php
<?php
class TechnicalAnalysisService
{
    private array $cache = [];
    private int $cacheTTL = 3600; // 1 hour
    
    public function getIndicator(string $symbol, string $indicator, array $params): array
    {
        $cacheKey = $this->buildCacheKey($symbol, $indicator, $params);
        
        if (isset($this->cache[$cacheKey])) {
            $cached = $this->cache[$cacheKey];
            if (time() - $cached['timestamp'] < $this->cacheTTL) {
                return $cached['data'];
            }
        }
        
        // Calculate indicator
        $data = $this->calculateIndicator($symbol, $indicator, $params);
        
        // Store in cache
        $this->cache[$cacheKey] = [
            'data' => $data,
            'timestamp' => time()
        ];
        
        return $data;
    }
}
?>
```

### Batch Processing

```php
<?php
// Instead of calculating indicators one-by-one:
// ❌ BAD:
foreach ($symbols as $symbol) {
    $rsi = $this->calculator->getRSI($symbol);
    $macd = $this->calculator->getMACD($symbol);
}

// ✅ GOOD: Batch fetch data first, then calculate
$allData = $this->marketData->getBulkHistoricalData($symbols);
foreach ($allData as $symbol => $data) {
    $rsi = trader_rsi($data['close'], 14);
    $macd = trader_macd($data['close']);
    // Store results
}
?>
```

### Memory Management

```php
<?php
// For large datasets, process in chunks
function calculateIndicatorsForPortfolio(array $symbols): void
{
    $chunkSize = 50; // Process 50 symbols at a time
    $chunks = array_chunk($symbols, $chunkSize);
    
    foreach ($chunks as $chunk) {
        $this->processChunk($chunk);
        gc_collect_cycles(); // Force garbage collection
    }
}
?>
```

---

## Common Pitfalls

### 1. Insufficient Data

```php
// TA-Lib needs enough data points
// Example: 200-day SMA needs 200+ data points

$close = [/* only 50 prices */];
$sma200 = trader_sma($close, 200); // ❌ Will return array with nulls

// ✅ Always check data length first
if (count($close) >= 200) {
    $sma200 = trader_sma($close, 200);
}
```

### 2. Unstable Period

```php
// Many indicators have an "unstable period" where values are unreliable
$macd = trader_macd($close);

// ❌ BAD: Using first values immediately
$firstSignal = $macd[0];

// ✅ GOOD: Skip unstable period
$unstablePeriod = 33; // For MACD with default params
$stableMACD = array_slice($macd, $unstablePeriod);
```

### 3. Look-Ahead Bias

```php
// ❌ BAD: Using future data in backtest
$future_high = max(array_slice($highs, $i, 10)); // Looks 10 days ahead!

// ✅ GOOD: Only use data available at that point in time
$current_high = $highs[$i];
```

---

## Next Steps

1. ✅ Complete this documentation
2. ⏳ Implement `CandlestickPatternCalculator.php`
3. ⏳ Create `TechnicalIndicatorService.php`
4. ⏳ Build pattern-based strategy
5. ⏳ Write comprehensive tests
6. ⏳ Backtest pattern reliability
7. ⏳ Integrate with existing strategies

---

## References

- [TA-Lib Official Documentation](https://ta-lib.org/function.html)
- [PHP Trader Extension](https://www.php.net/manual/en/book.trader.php)
- [Candlestick Patterns Guide](https://school.stockcharts.com/doku.php?id=chart_analysis:introduction_to_candlesticks)
- [Technical Indicators Encyclopedia](https://www.investopedia.com/terms/t/technicalindicator.asp)

---

**Document Version**: 1.0  
**Last Updated**: December 3, 2025  
**Status**: Ready for Implementation
