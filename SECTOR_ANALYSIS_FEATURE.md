# Sector Analysis & Performance Comparison Feature

## Overview

**Status**: ✅ IMPLEMENTED & TESTED  
**Core Feature**: Commit f9734851 (11/11 tests passing)  
**Documentation**: Complete with examples in User Manual  
**Last Updated**: December 2, 2025

This feature addresses critical questions about **sector classification, performance comparison, and peer analysis** to provide essential context for trading decisions.

### Key Questions Answered

1. ✅ **Do we categorize companies by sectors?** - Yes, using GICS classification
2. ✅ **Do we compare performance against sector indexes?** - Yes, with relative performance metrics
3. ✅ **Do we compare against peer companies?** - Yes, with sector peer ranking
4. ⚠️ **Do we compare against major indexes (S&P 500)?** - Database infrastructure exists, implementation pending
5. ⚠️ **Do we download and chart index values?** - Infrastructure exists, charting pending  
6. ⚠️ **ETF/mutual fund composition analysis?** - Not yet implemented

---

## What Was Built

### 1. GICS Sector Classification

Automatically classify stocks using the Global Industry Classification Standard.

**11 GICS Sectors**:
- **10** - Energy (XOM, CVX, SLB)
- **15** - Materials (DOW, LYB, NEM)
- **20** - Industrials (BA, CAT, UNP)
- **25** - Consumer Discretionary (AMZN, TSLA, HD)
- **30** - Consumer Staples (PG, KO, WMT)
- **35** - Health Care (JNJ, UNH, PFE)
- **40** - Financials (JPM, BAC, GS)
- **45** - Information Technology (AAPL, MSFT, NVDA)
- **50** - Communication Services (GOOGL, META, NFLX)
- **55** - Utilities (NEE, DUK, SO)
- **60** - Real Estate (AMT, PLD, SPG)

**Key Behaviors**:
- ✅ Reads sector from stock fundamentals
- ✅ Maps sector name to GICS code
- ✅ Includes industry subdivision
- ✅ Tracks market cap and country

**Example**:
```php
$sectorService = new SectorAnalysisService();
$classification = $sectorService->classifyStock('NVDA');

/*
Returns:
[
    'symbol' => 'NVDA',
    'sector' => 'Information Technology',
    'industry' => 'Semiconductors',
    'sector_code' => '45',
    'classification' => 'GICS',
    'market_cap' => 2100000000000,
    'country' => 'US'
]
*/
```

### 2. Stock vs Sector Performance Comparison

Compare individual stock performance against sector average to identify outperformers and underperformers.

**Key Metrics Calculated**:
- Stock total return (%)
- Sector average return (%)
- Relative performance (stock return - sector return)
- Outperformance/underperformance flag
- Volatility metrics (standard deviation)
- Maximum drawdown
- Percentile rank within sector

**Example - Outperformance**:
```php
$comparison = $sectorService->compareToSector('NVDA', '2024-01-01', '2024-03-31');

/*
Returns:
[
    'symbol' => 'NVDA',
    'sector' => 'Information Technology',
    'industry' => 'Semiconductors',
    'period' => ['start' => '2024-01-01', 'end' => '2024-03-31'],
    'stock_performance' => [
        'total_return' => 82.5,        // Stock up 82.5%
        'volatility' => 35.2,
        'max_drawdown' => 12.3,
        'start_price' => 100.00,
        'end_price' => 182.50
    ],
    'sector_performance' => [
        'name' => 'Information Technology',
        'return' => 18.4,              // Sector up 18.4%
        'constituents' => 75
    ],
    'relative_performance' => 64.1,    // 82.5% - 18.4% = +64.1%
    'outperformance' => true,          // Stock beating sector
    'percentile_rank' => 98            // Top 2% of tech stocks
]
*/
```

**Example - Underperformance**:
```php
$comparison = $sectorService->compareToSector('XOM', '2024-01-01', '2024-03-31');

/*
Returns:
[
    'stock_performance' => ['total_return' => 3.2],   // Stock up 3.2%
    'sector_performance' => ['return' => 12.8],       // Sector up 12.8%
    'relative_performance' => -9.6,                   // 3.2% - 12.8% = -9.6%
    'outperformance' => false                         // Stock lagging sector
]

Interpretation: Stock underperforming its sector by nearly 10%
Action: Investigate company-specific issues
*/
```

### 3. Sector Peer Ranking

Rank all stocks within a sector by performance over a specified period.

**Key Features**:
- ✅ Sorts stocks by return (highest to lowest)
- ✅ Includes volatility and Sharpe ratio
- ✅ Assigns percentile ranks
- ✅ Identifies top/bottom performers

**Example**:
```php
$symbols = ['MSFT', 'GOOGL', 'META', 'NVDA', 'AAPL', 'ORCL', 'IBM'];

$rankings = $sectorService->rankSectorPerformance(
    'Information Technology',
    $symbols,
    '2024-01-01',
    '2024-03-31'
);

/*
Returns:
[
    'sector' => 'Information Technology',
    'period' => ['start' => '2024-01-01', 'end' => '2024-03-31'],
    'total_stocks' => 7,
    'rankings' => [
        ['symbol' => 'NVDA',  'rank' => 1, 'return' => 82.5, 'volatility' => 35.2],
        ['symbol' => 'META',  'rank' => 2, 'return' => 54.3, 'volatility' => 42.1],
        ['symbol' => 'GOOGL', 'rank' => 3, 'return' => 38.7, 'volatility' => 28.5],
        ['symbol' => 'MSFT',  'rank' => 4, 'return' => 22.1, 'volatility' => 18.9],
        ['symbol' => 'AAPL',  'rank' => 5, 'return' => 15.3, 'volatility' => 16.2],
        ['symbol' => 'ORCL',  'rank' => 6, 'return' => 8.7,  'volatility' => 22.3],
        ['symbol' => 'IBM',   'rank' => 7, 'return' => -5.2, 'volatility' => 24.1]
    ]
]
*/
```

### 4. Sector Rotation Detection

Identify which sectors are gaining or losing momentum to spot market regime changes.

**Key Behaviors**:
- ✅ Analyzes all 11 GICS sectors
- ✅ Identifies top 3 leaders and bottom 3 laggards
- ✅ Calculates spread between best and worst
- ✅ Detects rotation when spread > 10%
- ✅ Determines sector trends (uptrend, downtrend, neutral)

**Example**:
```php
$rotation = $sectorService->detectSectorRotation(30); // 30-day lookback

/*
Returns:
[
    'period' => ['start' => '2024-02-01', 'end' => '2024-03-01', 'days' => 30],
    'all_sectors' => [
        ['sector' => 'Information Technology', 'code' => '45', 'return' => 12.5, 'trend' => 'strong_uptrend'],
        ['sector' => 'Communication Services', 'code' => '50', 'return' => 8.3,  'trend' => 'uptrend'],
        ['sector' => 'Consumer Discretionary', 'code' => '25', 'return' => 5.1,  'trend' => 'uptrend'],
        ['sector' => 'Health Care',            'code' => '35', 'return' => 3.2,  'trend' => 'uptrend'],
        ['sector' => 'Financials',             'code' => '40', 'return' => 1.5,  'trend' => 'uptrend'],
        ['sector' => 'Industrials',            'code' => '20', 'return' => 0.8,  'trend' => 'neutral'],
        ['sector' => 'Materials',              'code' => '15', 'return' => -0.5, 'trend' => 'neutral'],
        ['sector' => 'Real Estate',            'code' => '60', 'return' => -2.1, 'trend' => 'downtrend'],
        ['sector' => 'Consumer Staples',       'code' => '30', 'return' => -3.5, 'trend' => 'downtrend'],
        ['sector' => 'Utilities',              'code' => '55', 'return' => -5.2, 'trend' => 'strong_downtrend'],
        ['sector' => 'Energy',                 'code' => '10', 'return' => -7.8, 'trend' => 'strong_downtrend']
    ],
    'leaders' => [
        ['sector' => 'Information Technology', 'return' => 12.5],
        ['sector' => 'Communication Services', 'return' => 8.3],
        ['sector' => 'Consumer Discretionary', 'return' => 5.1]
    ],
    'laggards' => [
        ['sector' => 'Real Estate',       'return' => -2.1],
        ['sector' => 'Consumer Staples',  'return' => -3.5],
        ['sector' => 'Utilities',         'return' => -5.2],
        ['sector' => 'Energy',            'return' => -7.8]
    ],
    'rotation_detected' => true  // Spread: 20.3% (Tech to Energy)
]

Interpretation:
✅ Strong rotation into growth sectors (Tech, Communications)
⚠️ Money flowing out of defensive sectors (Utilities, Staples)
💡 Suggests risk-on sentiment, economic growth expectations
*/
```

### 5. Relative Strength Analysis

Calculate how a stock performs relative to its sector over time.

**Relative Strength Ratio = Stock Return / Sector Return**

**Interpretation Scale**:
- **RS > 1.5**: Significantly outperforming sector
- **RS 1.1-1.5**: Outperforming sector
- **RS 0.9-1.1**: In line with sector
- **RS 0.5-0.9**: Underperforming sector
- **RS < 0.5**: Significantly underperforming sector

**Example**:
```php
$rs = $sectorService->calculateRelativeStrength('JPM', 90); // 90-day period

/*
Returns:
[
    'symbol' => 'JPM',
    'sector' => 'Financials',
    'period_days' => 90,
    'stock_return' => 24.0,
    'sector_return' => 12.0,
    'relative_strength_ratio' => 2.0,  // 24% / 12% = 2.0
    'interpretation' => 'Significantly outperforming sector',
    'outperforming' => true
]

Meaning: JPM gained 2x what the financial sector gained
Action: Strong performance, consider maintaining/increasing position
*/
```

---

## Database Schema

### Table: sector_performance

Stores historical sector-level performance data.

```sql
CREATE TABLE IF NOT EXISTS sector_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sector_code VARCHAR(10) NOT NULL,           -- GICS code (10, 15, 20, etc.)
    sector_name VARCHAR(100) NOT NULL,          -- Full name
    classification VARCHAR(20) DEFAULT 'GICS',  -- Classification system
    performance_value DECIMAL(8,4) NOT NULL,    -- Current performance index
    change_percent DECIMAL(8,4) DEFAULT 0,      -- % change
    market_cap_weight DECIMAL(8,4) DEFAULT 0,   -- Sector weight in market
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(sector_code, timestamp)
);
```

**Usage Example**:
```php
// Store sector performance
$sectorPerf = new SectorPerformance([
    'sector_code' => '45',
    'sector_name' => 'Information Technology',
    'classification' => 'GICS',
    'performance_value' => 105.5,
    'change_percent' => 5.5,
    'market_cap_weight' => 0.285,  // 28.5% of market
    'timestamp' => '2024-03-31 16:00:00'
]);

$dao->save($sectorPerf);
```

---

## Implementation Details

### SectorAnalysisService Class

**Location**: `Stock-Analysis/app/Services/SectorAnalysisService.php`

**Key Methods**:

#### classifyStock(string $symbol): array
```php
// Classify stock by GICS sector
$classification = $service->classifyStock('AAPL');
// Returns: sector, industry, sector_code, market_cap, country
```

#### compareToSector(string $symbol, string $startDate, string $endDate): array
```php
// Compare stock performance vs sector
$comparison = $service->compareToSector('NVDA', '2024-01-01', '2024-03-31');
// Returns: stock_performance, sector_performance, relative_performance, outperformance
```

#### rankSectorPerformance(string $sector, array $symbols, string $startDate, string $endDate): array
```php
// Rank stocks within sector
$rankings = $service->rankSectorPerformance('Information Technology', $techStocks, '2024-01-01', '2024-03-31');
// Returns: ranked list with returns, volatility, Sharpe ratios
```

#### detectSectorRotation(int $lookbackDays = 30): array
```php
// Detect sector rotation
$rotation = $service->detectSectorRotation(30);
// Returns: leaders, laggards, rotation_detected flag, all sector performances
```

#### calculateRelativeStrength(string $symbol, int $period = 90): array
```php
// Calculate relative strength ratio
$rs = $service->calculateRelativeStrength('JPM', 90);
// Returns: RS ratio, interpretation, outperformance flag
```

#### updateSectorPerformance(string $sector, float $value, float $change, float $weight): bool
```php
// Update sector performance data
$success = $service->updateSectorPerformance('Information Technology', 105.5, 5.5, 0.285);
// Stores performance data in database
```

---

## Test Coverage

### Test Suite: SectorAnalysisServiceTest.php

**Status**: ✅ All 11 tests passing with 67 assertions

#### Test 1: Stock Classification
- Classifies AAPL as Information Technology
- Returns sector code '45', industry, market cap
- ✅ Passes with correct GICS mapping

#### Test 2: Unknown Symbol Handling
- Handles stocks with no fundamentals gracefully
- Returns 'Unknown' sector/industry
- ✅ No errors, safe fallback behavior

#### Test 3: Relative Performance Calculation
- NVDA up 30%, sector up 15%
- Calculates +15% relative performance
- ✅ Correctly identifies outperformance

#### Test 4: Underperformance Detection
- Stock down 10%, sector up 5%
- Calculates -15% relative performance
- ✅ Correctly identifies underperformance

#### Test 5: Sector Rotation Detection
- Analyzes all 11 GICS sectors
- Identifies top 3 leaders and bottom 3 laggards
- ✅ Detects rotation when spread > 10%

#### Test 6: Relative Strength Ratio
- Stock up 20%, sector up 10%
- RS Ratio = 2.0
- ✅ Interprets as "Significantly outperforming"

#### Test 7: RS In-Line with Sector
- Stock and sector both up 8%
- RS Ratio = 1.0
- ✅ Interprets as "In line with sector"

#### Test 8: Sector Peer Ranking
- Ranks 4 tech stocks by return
- Sorts correctly: META (40%), MSFT (25%), GOOGL (15%), NFLX (5%)
- ✅ Assigns proper ranks 1-4

#### Test 9: Update Sector Performance
- Saves sector performance to database
- Verifies correct sector code and values
- ✅ Database write successful

#### Test 10: Get All Sectors
- Returns complete list of 11 GICS sectors
- ✅ All sector codes and names present

#### Test 11: Volatility Metrics
- Calculates standard deviation of returns
- Calculates maximum drawdown
- ✅ Metrics included in performance output

### Running Tests

```bash
cd Stock-Analysis
php vendor/bin/phpunit tests/Unit/Services/SectorAnalysisServiceTest.php --testdox
```

**Output**:
```
PHPUnit 9.6.25 by Sebastian Bergmann and contributors.

Sector Analysis Service (Tests\Unit\Services\SectorAnalysisService)
 ✔ Classify stock returns correct sector information
 ✔ Classify stock handles unknown symbol
 ✔ Compare to sector calculates relative performance
 ✔ Compare to sector detects underperformance
 ✔ Detect sector rotation identifies leaders and laggards
 ✔ Calculate relative strength ratio
 ✔ Calculate relative strength in line with sector
 ✔ Rank sector performance orders by return
 ✔ Update sector performance saves data
 ✔ Get all sectors returns complete list
 ✔ Compare to sector calculates volatility metrics

Time: 00:00.114, Memory: 8.00 MB

OK (11 tests, 67 assertions)
```

---

## Usage Examples

### Example 1: Evaluating a Buy Signal with Sector Context

**Scenario**: Strategy recommends buying NVDA

```php
// 1. Get strategy recommendation
$strategy = new MomentumQualityStrategyService();
$recommendation = $strategy->analyze('NVDA', $historicalData);
// Result: BUY (confidence 85%)

// 2. Check sector context
$sectorService = new SectorAnalysisService();
$comparison = $sectorService->compareToSector('NVDA', '2024-01-01', '2024-03-31');

// 3. Make informed decision
if ($comparison['outperformance'] && $comparison['relative_performance'] > 50) {
    echo "STRONG BUY: Stock significantly outperforming sector\n";
    echo "NVDA +{$comparison['stock_performance']['total_return']}% vs ";
    echo "Sector +{$comparison['sector_performance']['return']}%\n";
    echo "Relative: +{$comparison['relative_performance']}%\n";
    // Action: Increase position size or confidence
} else {
    echo "CAUTION: Stock not outperforming sector\n";
    // Action: Reduce position size or skip trade
}
```

### Example 2: Portfolio Rebalancing Based on Sector Rotation

```php
// Detect sector rotation
$rotation = $sectorService->detectSectorRotation(30);

// Identify shifts
$leadingSectors = array_column($rotation['leaders'], 'sector');
$laggingSectors = array_column($rotation['laggards'], 'sector');

// Adjust allocations
$portfolio = new PortfolioManager();

foreach ($leadingSectors as $sector) {
    // Increase exposure to leading sectors
    $portfolio->increaseSectorAllocation($sector, 0.05); // +5%
}

foreach ($laggingSectors as $sector) {
    // Decrease exposure to lagging sectors
    $portfolio->decreaseSectorAllocation($sector, 0.05); // -5%
}

// Example output:
// Information Technology: 25% → 30% (leading)
// Communication Services: 15% → 20% (leading)
// Energy: 15% → 10% (lagging)
// Utilities: 10% → 5% (lagging)
```

### Example 3: Stock Screening with Sector Filters

```php
// Find outperformers in specific sector
$techStocks = ['AAPL', 'MSFT', 'GOOGL', 'NVDA', 'META', 'ORCL', 'IBM', 'CSCO'];
$rankings = $sectorService->rankSectorPerformance(
    'Information Technology',
    $techStocks,
    '2024-01-01',
    '2024-03-31'
);

// Filter top 25% performers
$topQuartile = array_slice($rankings['rankings'], 0, ceil(count($techStocks) * 0.25));

foreach ($topQuartile as $stock) {
    if ($stock['return'] > 20 && $stock['sharpe_ratio'] > 1.0) {
        echo "Consider {$stock['symbol']}: +{$stock['return']}% return, ";
        echo "Sharpe {$stock['sharpe_ratio']}\n";
    }
}

// Output:
// Consider NVDA: +82.5% return, Sharpe 2.34
// Consider META: +54.3% return, Sharpe 1.29
```

---

## Real-World Trading Scenarios

### Scenario 1: AI Boom (Information Technology)

**Market Context** (Q1 2024):
- AI revolution driving tech stocks
- NVIDIA benefiting from GPU demand
- Sector showing strong momentum

**Analysis**:
```
NVDA Performance: +82.5%
Tech Sector: +18.4%
Relative Performance: +64.1%
RS Ratio: 4.48 (exceptional)

Decision: STRONG BUY
Rationale:
✅ Stock significantly outperforming already-strong sector
✅ Company-specific AI catalyst on top of sector tailwinds
✅ Top 2% performer in sector (98th percentile)
✅ Both micro (NVDA) and macro (Tech) trends aligned
```

### Scenario 2: Energy Sector Weakness

**Market Context** (Q2 2024):
- Oil prices declining
- Renewables gaining market share
- Energy sector rotating out

**Analysis**:
```
XOM Performance: +3.2%
Energy Sector: +12.8%
Relative Performance: -9.6%
RS Ratio: 0.25 (significant underperformance)

Decision: AVOID/SELL
Rationale:
⚠️ Stock underperforming in already-weak sector
⚠️ Company-specific issues compounding sector headwinds
⚠️ Bottom quartile performer in sector
⚠️ Both micro (XOM) and macro (Energy) trends negative
```

### Scenario 3: Sector Rotation Trade

**Detection**:
```
Sector Rotation (Past 30 days):
Leaders: Tech +12.5%, Communications +8.3%, Consumer Disc +5.1%
Laggards: Energy -7.8%, Utilities -5.2%, Staples -3.5%

Rotation Detected: YES (20.3% spread)
```

**Action Plan**:
1. **Exit Lagging Sectors**:
   - Sell 50% of energy positions
   - Reduce utility exposure
   
2. **Enter Leading Sectors**:
   - Add tech positions (NVDA, MSFT)
   - Increase communication services (META, GOOGL)

3. **Portfolio Rebalance**:
   ```
   Before:
   - Tech: 25%
   - Energy: 15%
   - Utilities: 10%
   
   After:
   - Tech: 35% (+10%)
   - Energy: 5% (-10%)
   - Utilities: 5% (-5%)
   ```

---

## Integration with Trading Strategies

### Enhanced Strategy Logic

**Before Sector Analysis**:
```php
if ($strategy->getAction() === 'BUY' && $confidence > 0.75) {
    executeTrade($symbol, 'BUY');
}
```

**After Sector Analysis**:
```php
$action = $strategy->getAction();
$confidence = $strategy->getConfidence();

// Add sector context
$comparison = $sectorService->compareToSector($symbol, $startDate, $endDate);
$rs = $sectorService->calculateRelativeStrength($symbol);

// Adjust confidence based on sector performance
if ($action === 'BUY') {
    if ($comparison['outperformance'] && $rs['relative_strength_ratio'] > 1.5) {
        $confidence += 0.10; // Boost confidence by 10%
        $reason = "Stock significantly outperforming sector";
    } elseif (!$comparison['outperformance'] && $rs['relative_strength_ratio'] < 0.5) {
        $confidence -= 0.15; // Reduce confidence by 15%
        $reason = "Stock significantly underperforming sector";
    }
}

if ($confidence > 0.70) {
    executeTrade($symbol, $action);
}
```

---

## What's Next

### Implemented ✅
- GICS sector classification
- Stock vs sector performance comparison
- Sector peer ranking
- Sector rotation detection
- Relative strength analysis
- Database schema and persistence
- Comprehensive test coverage (11/11 passing)
- User manual documentation

### In Progress ⚠️
- Index benchmarking (S&P 500, NASDAQ, Russell 2000)
- Index constituent tracking
- Performance vs major index comparison

### Future Enhancements 📋
- ETF/Mutual fund composition analysis
- Fund holdings and weightings
- Fund overlap detection
- Expense ratio tracking
- Sector heat maps (visual)
- Performance charts (stock vs sector overlay)
- Historical sector correlation analysis
- Sector momentum indicators
- Sub-industry classification (GICS Level 2, 3, 4)

---

## Summary

✅ **Implemented**: Comprehensive sector analysis with GICS classification  
✅ **Implemented**: Stock vs sector performance comparison  
✅ **Implemented**: Sector peer ranking and relative strength analysis  
✅ **Implemented**: Sector rotation detection  
✅ **Tested**: 11 comprehensive tests, 67 assertions, 100% passing  
✅ **Documented**: User manual updated with 200+ lines, examples, FAQs  
✅ **Committed**: All code pushed to GitHub (commits f9734851, 06b8d486)

**Answers Your Questions**: 

> "Do we categorize companies for their sectors?"  
**Answer**: YES - GICS classification with 11 sectors, automatic classification from fundamentals

> "Do we compare their performance against their sector indexes and other companies in that sector?"  
**Answer**: YES - Relative performance calculation, percentile ranking, RS ratios

> "Do we compare their performance against any Indexes they are a part of (e.g. S&P 500)?"  
**Answer**: DATABASE INFRASTRUCTURE EXISTS - Implementation in progress

> "Do we download the index values and chart them against our strategies?"  
**Answer**: DATABASE SCHEMA EXISTS - Charting implementation pending

> "How about Index Funds/ETFs/Mutual funds/Seg Funds? Do we dive into the list of companies (symbols) that compose the index/ETF/MF/SF and their weightings?"  
**Answer**: NOT YET IMPLEMENTED - Future enhancement planned

**Ready for**: Production use of sector analysis features
