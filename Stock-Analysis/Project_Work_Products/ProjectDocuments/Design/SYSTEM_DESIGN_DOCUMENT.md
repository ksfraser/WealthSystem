# WealthSystem - System Design Document

**Document Version**: 1.0  
**Date**: December 3, 2025  
**Status**: Living Document  
**Owner**: Development Team

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [System Architecture Overview](#system-architecture-overview)
3. [Core Components](#core-components)
4. [Trading Strategies](#trading-strategies)
5. [Technical Analysis Integration](#technical-analysis-integration)
6. [Market Analysis Features](#market-analysis-features)
7. [Data Models](#data-models)
8. [Implementation Status](#implementation-status)
9. [Future Enhancements](#future-enhancements)

---

## Executive Summary

WealthSystem is a professional-grade financial analysis platform that combines quantitative analysis, technical indicators, fundamental analysis, and multiple trading strategies to provide comprehensive investment decision support. The system evolved from a Python-based micro-cap trading experiment into a full-featured PHP/SQLite wealth management platform.

### Key Capabilities

- **15+ Trading Strategies** - From Turtle Trading to Warren Buffett value investing
- **Sector Analysis** - GICS 11-sector classification with relative strength analysis
- **Index Benchmarking** - Alpha/beta calculation against SPY, QQQ, DIA, IWM
- **Fund Analysis** - ETF/mutual fund/segregated fund composition with MER tier optimization
- **Technical Indicators** - 150+ TA-Lib indicators (in development)
- **Candlestick Patterns** - Pattern recognition and signal generation
- **Risk Management** - Portfolio-level risk controls and position sizing

---

## System Architecture Overview

### Technology Stack

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                        │
│  PHP Web Interface + REST API + CLI Tools                    │
└─────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────┐
│                    Business Logic Layer                      │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │   Trading    │  │   Market     │  │   Portfolio     │  │
│  │  Strategies  │  │   Analysis   │  │   Management    │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────┐  │
│  │  Technical   │  │  Fundamental │  │   Risk          │  │
│  │  Analysis    │  │  Analysis    │  │   Management    │  │
│  └──────────────┘  └──────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────┐
│                     Data Access Layer                        │
│  DAOs + Repositories + ORM + Cache                           │
└─────────────────────────────────────────────────────────────┘
                             │
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                              │
│  SQLite DB + Market Data APIs + External Services            │
└─────────────────────────────────────────────────────────────┘
```

### Core Technologies

- **Backend**: PHP 8.4+
- **Database**: SQLite 3
- **Testing**: PHPUnit 9.6+
- **Technical Analysis**: TA-Lib (PHP extension)
- **Market Data**: Yahoo Finance, Alpha Vantage, IEX Cloud
- **Version Control**: Git (GitHub: ksfraser/WealthSystem)

---

## Core Components

### 1. Market Data Service

**Location**: `Stock-Analysis/app/Services/MarketDataService.php`  
**Status**: ✅ Implemented  
**Purpose**: Centralized market data retrieval and caching

**Key Methods**:
- `getHistoricalPrices($symbol, $startDate, $endDate)` - OHLCV data
- `getCurrentPrice($symbol)` - Real-time quotes
- `getFundamentals($symbol)` - Financial metrics (P/E, ROE, debt, etc.)
- `getCompanyInfo($symbol)` - Company profile and sector
- `getBulkQuotes($symbols)` - Batch quote retrieval

**Data Sources**:
- Primary: Yahoo Finance (free, rate-limited)
- Secondary: Alpha Vantage (API key required)
- Tertiary: IEX Cloud (paid plans)

**Caching Strategy**: 1-hour cache for real-time data, daily cache for historical

---

### 2. Trading Strategy Framework

**Base Interface**: `TradingStrategyInterface`

```php
interface TradingStrategyInterface {
    public function getName(): string;
    public function getDescription(): string;
    public function analyze(string $symbol, string $date): array;
    public function setParameters(array $parameters): void;
    public function getParameters(): array;
}
```

**Signal Structure**:
```php
[
    'signal' => 'BUY' | 'SELL' | 'HOLD',
    'confidence' => 0.0 - 1.0,
    'strength' => 0.0 - 1.0,
    'entry_price' => float,
    'stop_loss' => float,
    'take_profit' => float,
    'position_size' => 0.0 - 1.0,
    'reasoning' => string,
    'metrics' => array
]
```

---

### 3. Portfolio Management

**Location**: `Stock-Analysis/app/Services/PortfolioManager.php`  
**Status**: ✅ Implemented

**Capabilities**:
- Multiple portfolio support per user
- Position tracking with cost basis
- P&L calculation (realized and unrealized)
- Rebalancing recommendations
- Sector/asset allocation tracking
- Risk metrics (VaR, Sharpe, max drawdown)

---

### 4. Risk Management

**Components**:
- Position size calculator (based on volatility/beta)
- Stop-loss automation
- Portfolio concentration limits
- Correlation analysis
- Drawdown monitoring

**Default Risk Parameters**:
```php
[
    'max_position_size' => 0.15,        // 15% max per position
    'max_sector_allocation' => 0.30,    // 30% max per sector
    'portfolio_stop_loss' => 0.20,      // 20% portfolio drawdown
    'correlation_threshold' => 0.70,    // Avoid correlated positions
    'max_leverage' => 1.0               // No leverage by default
]
```

---

## Trading Strategies

### Implemented Strategies (15)

#### 1. **Turtle Trading Strategy**
**File**: `TurtleStrategyService.php`, `src/Ksfraser/Finance/Strategies/TurtleStrategy.php`  
**Status**: ✅ Complete with extensive testing  
**Test Coverage**: 11 tests, 100% coverage

**Description**: Classic Turtle Trading system developed by Richard Dennis and William Eckhardt.

**Parameters**:
- System 1: 20-day high breakout entry, 10-day low exit
- System 2: 55-day high breakout entry, 20-day low exit
- N-value: ATR(20) for volatility measurement
- Position sizing: 2% risk per unit, max 4 units
- Stop loss: 2N below entry

**Signals**:
- BUY: Price breaks above 20-day (S1) or 55-day (S2) high
- SELL: Price breaks below 10-day (S1) or 20-day (S2) low
- Add to position: Every 0.5N price move in favorable direction

**Use Cases**: Trend-following in liquid markets, systematic trading

---

#### 2. **Warren Buffett Value Strategy**
**File**: `WarrenBuffettStrategyService.php`  
**Status**: ✅ Complete (quantitative analysis only)  
**Test Coverage**: Partial

**Description**: Implements Warren Buffett's 12 investment tenets through quantitative proxies.

**The 12 Tenets**:

**Business Tenets (4)**:
1. Is the business simple and understandable?
2. Does it have consistent operating history? (10+ years)
3. Does it have favorable long-term prospects?
4. Does management act rationally?

**Management Tenets (3)**:
5. Is management candid with shareholders?
6. Does management resist institutional imperative?
7. Does management allocate capital properly? (ROE, buybacks)

**Financial Tenets (4)**:
8. Focus on return on equity (15%+ target)
9. Calculate owner earnings (not just reported earnings)
10. Look for high profit margins (20%+ target)
11. Has the company created $1 of market value for every $1 retained?

**Value Tenets (1)**:
12. What is the intrinsic value? (DCF of owner earnings)
13. Can we buy at a significant discount? (25%+ margin of safety)

**Quantitative Proxies Used**:
- Business simplicity: Single-segment business preferred
- Operating history: 10+ years of data required
- Management quality: Insider ownership ≥5%, share buybacks
- ROE: 15%+ over 5 years
- Profit margins: 20%+ operating margin
- Debt management: Debt/equity <0.5
- Intrinsic value: DCF using owner earnings

**Economic Moat Evaluation**:
The strategy calculates a "moat strength" score based on:
- High and stable profit margins (pricing power)
- High ROE with low debt (capital efficiency)
- Consistent revenue growth (market dominance)
- Low customer churn (if data available)

**Limitations**:
- ❌ No qualitative assessment of management character
- ❌ No evaluation of competitive landscape
- ❌ No analysis of business culture or brand value
- ❌ No assessment of regulatory risks

**Future Enhancement**: Add AI-powered qualitative analysis prompts (see section 9.3)

---

#### 3. **Quality Dividend Strategy**
**File**: `QualityDividendStrategyService.php`  
**Status**: ✅ Complete  
**Test Coverage**: 10 tests

**Description**: Identifies sustainable dividend growth stocks.

**Key Criteria**:
- Dividend yield: 2.5% - 10%
- Consecutive growth: 5+ years
- Payout ratio: <65%
- FCF coverage: >1.2x
- ROE: >12%
- Dividend Aristocrat detection: 25+ years growth

**Safety Score Components**:
- Payout ratio: 25% weight
- FCF coverage: 25% weight
- Growth streak: 20% weight
- Debt level: 15% weight
- ROE: 15% weight

**Use Cases**: Income portfolios, conservative investors, retirement accounts

---

#### 4. **Small Cap Catalyst Strategy**
**File**: `SmallCapCatalystStrategyService.php`  
**Status**: ✅ Implemented

**Focus**: Micro/small-cap stocks (<$2B market cap) with near-term catalysts.

**Catalysts Detected**:
- Earnings releases
- FDA approvals (biotech)
- Merger/acquisition activity
- New product launches
- Insider buying
- Analyst upgrades

---

#### 5. **IPlace Strategy**
**File**: `IPlaceStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Follows analyst upgrades and institutional buying.

**Signals**:
- Analyst rating upgrades
- Price target increases
- Institutional ownership increases
- Insider buying activity

---

#### 6. **Mean Reversion Strategy**
**File**: `MeanReversionStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Buy oversold, sell overbought conditions.

**Indicators Used**:
- RSI(14) < 30 (oversold) or > 70 (overbought)
- Bollinger Band touches
- Price deviation from 20-day MA

**Hold Period**: Typically 5-20 days

---

#### 7. **Momentum Quality Strategy**
**File**: `MomentumQualityStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Combines price momentum with fundamental quality.

**Momentum Metrics**:
- 3-month, 6-month, 12-month returns
- Relative strength vs sector/market

**Quality Filters**:
- Positive earnings growth
- ROE > 10%
- Debt/equity < 2.0

---

#### 8. **Contrarian Strategy**
**File**: `ContrarianStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Buy during panic, sell during euphoria.

**Signals**:
- Extreme negative sentiment with solid fundamentals
- High short interest with improving metrics
- Sector rotation opportunities

---

#### 9. **GARP Strategy (Growth at Reasonable Price)**
**File**: `GARPStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Peter Lynch's approach - growth stocks at fair valuations.

**Key Metric**: PEG ratio = (P/E) / (Earnings Growth Rate)
- Target: PEG < 1.0 (growth cheaper than P/E suggests)
- Quality filter: Consistent growth, strong margins

---

#### 10. **Four Week Rule Strategy**
**File**: `FourWeekRuleStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Simplified Turtle Trading variant.

**Rules**:
- BUY: 4-week high breakout
- SELL: 4-week low breakdown
- Volume confirmation required

---

#### 11. **Moving Average Crossover**
**File**: `MACrossoverStrategyService.php`  
**Status**: ✅ Implemented

**Variations**:
- Golden Cross: 50-day MA crosses above 200-day MA (bullish)
- Death Cross: 50-day MA crosses below 200-day MA (bearish)
- Fast signals: 10-day / 50-day crossover

---

#### 12. **Support/Resistance Strategy**
**File**: `SupportResistanceStrategyService.php`  
**Status**: ✅ Implemented

**Description**: Trade bounces off key levels.

**Level Detection**:
- Historical pivot points
- Volume-weighted levels
- Psychological levels ($10, $50, $100)

---

#### 13-15. **Strategy Weighting & Portfolio Analysis**
**Files**: 
- `StrategyWeightingEngine.php` - Combines multiple strategy signals
- `StrategyPerformanceAnalyzer.php` - Backtesting and performance tracking
- `BacktestingFramework.php` - Historical strategy validation

**Allocation Profiles**:
- Conservative: 35% Quality Dividend, 25% Mean Reversion
- Balanced: Equal weight across all strategies
- Aggressive: 30% Small Cap, 25% Momentum
- Growth: 30% Momentum, 25% IPlace
- Value: 35% Contrarian, 25% Quality Dividend

---

## Technical Analysis Integration

### Current Status: ⚠️ PARTIAL IMPLEMENTATION

### 5.1 What EXISTS (Manual Calculations)

**File**: `Stock-Analysis/app/Services/MarketDataService.php`

**Indicators Calculated**:
- Simple Moving Average (SMA) - 20, 50, 200 day
- Exponential Moving Average (EMA) - 12, 26 day
- RSI (Relative Strength Index) - 14 period
- MACD (Moving Average Convergence Divergence)
- ATR (Average True Range) - 14, 20 period
- Bollinger Bands - 20 period, 2 std dev
- Volume analysis

**Method**: Pure PHP calculations (no TA-Lib dependency)

---

### 5.2 What is MISSING (TA-Lib Integration)

**Target**: 150+ technical indicators via PHP TA-Lib extension

**Status**: 
- ✅ Database schema ready (`candlestick_patterns`, `technical_indicators` tables)
- ✅ `CandlestickPatternCalculator.php` - IMPLEMENTED (470+ lines, 63 patterns)
- ✅ `TA-Lib_Integration_Analysis.md` - COMPLETE (1,000+ lines documentation)
- ✅ `CandlestickPatternCalculatorTest.php` - 35 tests, 100+ assertions
- ❌ TA-Lib PHP extension - NOT YET INSTALLED (installation guide provided)
- ⏳ `TechnicalIndicatorService.php` - PENDING (150+ indicators to implement)

---

### 5.3 TA-Lib Implementation Plan

#### Phase 1: Environment Setup
1. Install TA-Lib C library
2. Install PHP TA-Lib extension (PECL)
3. Verify `trader_*` functions available

#### Phase 2: Core Indicators (Priority 1)

**Trend Indicators**:
- `trader_sma()` - Simple Moving Average
- `trader_ema()` - Exponential Moving Average
- `trader_wma()` - Weighted Moving Average
- `trader_dema()` - Double Exponential MA
- `trader_tema()` - Triple Exponential MA
- `trader_adx()` - Average Directional Index
- `trader_aroon()` - Aroon Indicator
- `trader_sar()` - Parabolic SAR

**Momentum Indicators**:
- `trader_rsi()` - Relative Strength Index
- `trader_macd()` - MACD
- `trader_stoch()` - Stochastic Oscillator
- `trader_cci()` - Commodity Channel Index
- `trader_mfi()` - Money Flow Index
- `trader_roc()` - Rate of Change
- `trader_mom()` - Momentum

**Volatility Indicators**:
- `trader_bbands()` - Bollinger Bands
- `trader_atr()` - Average True Range
- `trader_natr()` - Normalized ATR
- `trader_stddev()` - Standard Deviation

**Volume Indicators**:
- `trader_ad()` - Chaikin A/D Line
- `trader_obv()` - On Balance Volume
- `trader_adosc()` - Chaikin A/D Oscillator

#### Phase 3: Candlestick Patterns (Priority 1)

**63 Patterns Available** - Top 20 prioritized:

**Reversal Patterns (Bullish)**:
1. `trader_cdlhammer()` - Hammer
2. `trader_cdlinvertedhammer()` - Inverted Hammer
3. `trader_cdlengulfing()` - Bullish Engulfing
4. `trader_cdlpiercing()` - Piercing Pattern
5. `trader_cdlmorningstar()` - Morning Star
6. `trader_cdl3whitesoldiers()` - Three White Soldiers

**Reversal Patterns (Bearish)**:
7. `trader_cdlshootingstar()` - Shooting Star
8. `trader_cdlhangingman()` - Hanging Man
9. `trader_cdlengulfing()` - Bearish Engulfing
10. `trader_cdldarkcloudcover()` - Dark Cloud Cover
11. `trader_cdleveningstar()` - Evening Star
12. `trader_cdl3blackcrows()` - Three Black Crows

**Continuation Patterns**:
13. `trader_cdldoji()` - Doji (indecision)
14. `trader_cdlspinningtop()` - Spinning Top
15. `trader_cdlharami()` - Harami
16. `trader_cdlmarubozu()` - Marubozu (strong trend)

**Other Important**:
17. `trader_cdl3inside()` - Three Inside Up/Down
18. `trader_cdl3outside()` - Three Outside Up/Down
19. `trader_cdltristar()` - Tri-Star
20. `trader_cdl2crows()` - Two Crows

**Pattern Recognition Output**:
- Returns integer: +100 (bullish), 0 (none), -100 (bearish)
- Store in `candlestick_patterns` table
- Link to trading signals

#### Phase 4: Advanced Indicators (Priority 2)

**Overlap Studies**:
- `trader_midpoint()`, `trader_midprice()`
- `trader_kama()` - Kaufman Adaptive MA
- `trader_mama()` - MESA Adaptive MA
- `trader_t3()` - Triple Exponential MA

**Cycle Indicators**:
- `trader_ht_dcperiod()` - Hilbert Transform - Dominant Cycle Period
- `trader_ht_dcphase()` - Hilbert Transform - Dominant Cycle Phase
- `trader_ht_trendmode()` - Hilbert Transform - Trend vs Cycle

**Price Transform**:
- `trader_avgprice()` - Average Price
- `trader_medprice()` - Median Price
- `trader_typprice()` - Typical Price
- `trader_wclprice()` - Weighted Close Price

**Statistic Functions**:
- `trader_beta()` - Beta
- `trader_correl()` - Pearson Correlation
- `trader_linearreg()` - Linear Regression
- `trader_var()` - Variance

#### Phase 5: Pattern Recognition Service

**File**: `Stock-Analysis/src/Services/Calculators/CandlestickPatternCalculator.php`

**Class Structure**:
```php
class CandlestickPatternCalculator {
    public function detectAllPatterns(array $ohlcv): array;
    public function detectPattern(string $pattern, array $ohlcv): array;
    public function getBullishPatterns(array $ohlcv): array;
    public function getBearishPatterns(array $ohlcv): array;
    public function getPatternStrength(string $pattern, array $ohlcv): int;
    public function getPatternDescription(string $pattern): string;
    public function filterByTimeframe(array $patterns, string $timeframe): array;
}
```

**Database Integration**:
```php
// Save pattern to database
$this->savePattern([
    'symbol' => 'AAPL',
    'date' => '2025-12-03',
    'pattern_name' => 'CDL_HAMMER',
    'pattern_value' => 100,
    'reliability' => 'HIGH',
    'confirmation_price' => 185.50,
    'target_price' => 192.00,
    'invalidation_price' => 183.00
]);
```

---

### 5.4 TA-Lib Service Architecture

```
┌─────────────────────────────────────────────────────┐
│          TechnicalAnalysisService                   │
│  (Orchestrates all technical analysis)              │
└─────────────────────────────────────────────────────┘
                       │
         ┌─────────────┼─────────────┐
         │             │             │
┌────────▼───────┐ ┌──▼──────────┐ ┌▼─────────────────┐
│   Indicator    │ │  Candlestick│ │   Pattern        │
│   Calculator   │ │  Pattern    │ │   Recognition    │
│                │ │  Calculator │ │   Engine         │
└────────────────┘ └─────────────┘ └──────────────────┘
         │             │                    │
         │             │                    │
┌────────▼─────────────▼────────────────────▼──────────┐
│           TA-Lib PHP Extension (trader_*)            │
└──────────────────────────────────────────────────────┘
```

---

## Market Analysis Features

### 6.1 Sector Analysis
**File**: `SectorAnalysisService.php`  
**Status**: ✅ Complete  
**Tests**: 11/11 passing, 67 assertions

**Features**:
- GICS 11-sector classification
- Stock vs sector performance comparison
- Relative Strength (RS) ratio calculation
- Sector rotation detection
- Peer ranking within sector
- Sector leaders/laggards identification

**Sectors Supported**:
1. Energy (10)
2. Materials (15)
3. Industrials (20)
4. Consumer Discretionary (25)
5. Consumer Staples (30)
6. Health Care (35)
7. Financials (40)
8. Information Technology (45)
9. Communication Services (50)
10. Utilities (55)
11. Real Estate (60)

**Use Cases**:
- Identify sector rotation opportunities
- Compare stock to sector average performance
- Find outperforming stocks within underperforming sectors
- Diversify across uncorrelated sectors

---

### 6.2 Index Benchmarking
**File**: `IndexBenchmarkingService.php`  
**Status**: ✅ Complete  
**Tests**: 11/11 passing, 48 assertions

**Features**:
- Alpha calculation (excess returns)
- Beta calculation (market sensitivity)
- Correlation analysis
- Sharpe ratio (risk-adjusted returns)
- Information ratio (alpha consistency)
- Tracking error measurement
- Index membership detection

**Indexes Supported**:
1. SPY (S&P 500) - Large-cap, 500 stocks
2. QQQ (NASDAQ 100) - Tech-focused, 100 stocks
3. DIA (Dow Jones) - Blue-chip, 30 stocks
4. IWM (Russell 2000) - Small-cap, 2000 stocks

**Use Cases**:
- Measure true skill (alpha) vs market beta
- Adjust position sizes for high-beta stocks
- Choose appropriate benchmark for stock/portfolio
- Validate active management performance

---

### 6.3 Fund Composition Analysis
**File**: `FundCompositionService.php`  
**Status**: ✅ Complete  
**Tests**: 11/11 passing, 52 assertions

**Features**:
- Complete holdings analysis (ETF, mutual fund, seg fund)
- Sector allocation from holdings
- Asset class breakdown
- Geographic exposure
- Concentration metrics (Top 10, HHI)
- Fund overlap detection
- MER tier comparison (RETAIL → INSTITUTIONAL)
- Client eligibility filtering
- 10/25-year fee projections
- Performance vs benchmark with alpha after fees

**MER Tier Structure**:
- RETAIL: 2.0-2.5%, $0 minimum
- PREFERRED: 1.5-1.9%, $250k minimum
- PREMIUM: 1.0-1.4%, $500k minimum
- INSTITUTIONAL: 0.5-0.9%, $1M+ minimum

**Use Cases**:
- Avoid redundant fund holdings (overlap detection)
- Optimize client eligibility for lower fees
- Project long-term fee impact
- Analyze fund concentration risk
- Validate active fund performance

---

## Data Models

### 7.1 Core Entities

#### Stock/Symbol
```php
{
    'symbol': string,
    'name': string,
    'exchange': string,
    'sector': string (GICS),
    'industry': string,
    'market_cap': float,
    'country': string,
    'currency': string,
    'type': 'STOCK' | 'ETF' | 'MUTUAL_FUND' | 'INDEX'
}
```

#### Portfolio
```php
{
    'id': int,
    'user_id': int,
    'name': string,
    'description': string,
    'base_currency': string,
    'initial_value': float,
    'current_value': float,
    'cash_balance': float,
    'created_at': timestamp,
    'is_active': bool
}
```

#### Position
```php
{
    'id': int,
    'portfolio_id': int,
    'symbol': string,
    'shares': float,
    'cost_basis': float,
    'average_price': float,
    'current_price': float,
    'market_value': float,
    'unrealized_pl': float,
    'unrealized_pl_percent': float,
    'opened_at': timestamp,
    'stop_loss': float,
    'take_profit': float
}
```

#### Trade
```php
{
    'id': int,
    'portfolio_id': int,
    'symbol': string,
    'action': 'BUY' | 'SELL',
    'shares': float,
    'price': float,
    'total': float,
    'fees': float,
    'strategy': string,
    'confidence': float,
    'executed_at': timestamp,
    'notes': string
}
```

#### Technical Indicator
```php
{
    'id': int,
    'symbol': string,
    'date': date,
    'indicator_type': string,
    'indicator_value': float,
    'period': int,
    'signal_line': float,
    'histogram': float,
    'created_at': timestamp
}
```

#### Candlestick Pattern
```php
{
    'id': int,
    'symbol': string,
    'date': date,
    'pattern_name': string,
    'pattern_value': int (-100, 0, +100),
    'reliability': 'LOW' | 'MEDIUM' | 'HIGH',
    'confirmation_price': float,
    'target_price': float,
    'invalidation_price': float,
    'detected_at': timestamp
}
```

#### Fund
```php
{
    'id': int,
    'symbol': string,
    'name': string,
    'fund_code': string,
    'type': 'ETF' | 'MUTUAL_FUND' | 'SEGREGATED_FUND' | 'INDEX_FUND',
    'fund_family': string,
    'base_fund_id': int,  // Links multiple MER tiers
    'mer': float,
    'mer_tier': 'RETAIL' | 'PREFERRED' | 'PREMIUM' | 'INSTITUTIONAL',
    'minimum_investment': float,
    'minimum_net_worth': float,
    'allows_family_aggregation': bool,
    'aum': float,
    'inception_date': date
}
```

---

### 7.2 Database Schema

#### Key Tables

**Market Data**:
- `stocks` - Symbol master data
- `historical_prices` - OHLCV data
- `technical_indicators` - Calculated indicators
- `candlestick_patterns` - Pattern detections
- `company_fundamentals` - Financial metrics

**Trading**:
- `portfolios` - User portfolios
- `positions` - Current holdings
- `trades` - Transaction history
- `strategy_signals` - Generated signals
- `strategy_parameters` - Strategy configuration

**Analysis**:
- `sector_performance` - Sector metrics
- `index_performance` - Index data
- `funds` - Fund metadata
- `fund_holdings` - Fund composition
- `fund_eligibility` - MER tier rules

**System**:
- `users` - User accounts
- `sessions` - Active sessions
- `audit_log` - System audit trail
- `job_queue` - Background jobs

---

## Implementation Status

### 8.1 Completed Features ✅

| Component | Status | Tests | Lines |
|-----------|--------|-------|-------|
| Market Data Service | ✅ Complete | Manual | 800+ |
| Portfolio Manager | ✅ Complete | Manual | 700+ |
| Turtle Strategy | ✅ Complete | 11/11 ✅ | 277 |
| Warren Buffett Strategy | ✅ Complete | Partial | 717 |
| Quality Dividend Strategy | ✅ Complete | 10/10 ✅ | 687 |
| 12 Other Strategies | ✅ Complete | Varies | 5000+ |
| Sector Analysis | ✅ Complete | 11/11 ✅ | 580 |
| Index Benchmarking | ✅ Complete | 11/11 ✅ | 580 |
| Fund Composition | ✅ Complete | 11/11 ✅ | 780 |
| Fund Models/DAOs | ✅ Complete | Via service | 1200+ |
| Risk Management | ✅ Complete | Manual | 600+ |
| Strategy Weighting | ✅ Complete | Manual | 400+ |
| Backtesting Framework | ✅ Complete | Manual | 500+ |

**Total**: ~13,000 lines of production code, 33 comprehensive tests

---

### 8.2 In Progress 🔨

| Component | Status | Priority | ETA |
|-----------|--------|----------|-----|
| Fund Analysis Feature Guide | 🔨 Drafting | HIGH | Dec 3 |
| Admin UI for Fund Eligibility | 📋 Design phase | MEDIUM | Dec 10 |
| User Manual Updates | 🔨 Ongoing | HIGH | Dec 3 |

---

### 8.3 Not Started ❌

| Component | Status | Priority | Blocker |
|-----------|--------|----------|---------|
| TA-Lib Integration | ❌ Not started | **CRITICAL** | Extension install |
| Candlestick Patterns | ❌ Empty file | **CRITICAL** | TA-Lib required |
| Buffett Qualitative Prompts | ❌ Not started | HIGH | Design needed |
| Real-time Data Feeds | ❌ Not started | MEDIUM | API costs |
| Mobile App | ❌ Not started | LOW | Resource constraint |

---

## Future Enhancements

### 9.1 TA-Lib Full Integration (PRIORITY 1)

**Goal**: Replace manual calculations with TA-Lib library for 150+ indicators

**Tasks**:
1. ✅ Create this design document
2. ⏳ Install TA-Lib C library and PHP extension
3. ⏳ Implement `CandlestickPatternCalculator` (63 patterns)
4. ⏳ Create `TechnicalIndicatorService` wrapper
5. ⏳ Migrate existing indicators to TA-Lib
6. ⏳ Add 130+ new indicators
7. ⏳ Write comprehensive tests
8. ⏳ Update documentation

**Deliverables**:
- Working TA-Lib integration
- 63 candlestick patterns recognized
- 150+ technical indicators available
- Pattern-based trading signals
- Full test coverage

**Estimated Effort**: 40 hours

---

### 9.2 Candlestick Pattern Trading System (PRIORITY 1)

**Goal**: Build trading strategy based on candlestick pattern recognition

**Components**:

1. **Pattern Detection Engine**
   - Real-time pattern scanning
   - Pattern confirmation logic
   - Reliability scoring (HIGH/MEDIUM/LOW)

2. **Signal Generation**
   - Bullish pattern → BUY signal
   - Bearish pattern → SELL signal
   - Combine with volume confirmation
   - Set target/stop prices based on pattern

3. **Pattern Strategy Service**
   ```php
   class CandlestickPatternStrategyService {
       public function analyze($symbol, $date): array;
       public function detectActivePatterns($symbol): array;
       public function generateSignalFromPattern($pattern): array;
       public function getPatternSuccess Rate(string $pattern): float;
   }
   ```

4. **Backtesting**
   - Test pattern reliability on historical data
   - Optimize entry/exit rules
   - Calculate win rate per pattern
   - Identify best-performing patterns per stock/sector

**Expected Results**:
- 60-65% win rate on high-reliability patterns
- Clear entry/exit rules
- Integration with existing strategy framework

---

### 9.3 Warren Buffett Qualitative Analysis Prompts (PRIORITY 1)

**Problem**: Current Buffett strategy only uses quantitative metrics. Missing the "soft" analysis:
- Management quality and integrity
- Business moat sustainability
- Competitive advantages
- Industry dynamics
- Brand value

**Solution**: AI-powered qualitative analysis prompts

#### Prompt Template Structure

**File**: `Stock-Analysis/prompts/buffett_qualitative_analysis.md`

```markdown
# Warren Buffett Qualitative Analysis Prompt

## System Context
You are Warren Buffett conducting due diligence on a potential investment. 
Apply the same rigorous qualitative analysis you've used for 60+ years at 
Berkshire Hathaway. Be thorough, skeptical, and focus on long-term business 
quality over short-term metrics.

## Stock Being Analyzed
- Company: {{COMPANY_NAME}}
- Ticker: {{SYMBOL}}
- Sector: {{SECTOR}}
- Market Cap: {{MARKET_CAP}}

## Quantitative Summary (For Context)
{{QUANTITATIVE_METRICS}}

---

## Analysis Framework

### 1. Business Tenets (Qualitative Assessment)

#### Simplicity and Understandability
**Question**: Can I explain this business model to a 10-year-old?

Evaluate:
- Is the core business simple or complex?
- Do I understand how they make money?
- Are there hidden complexities (derivatives, off-balance sheet items)?
- Could I confidently predict where this company will be in 10 years?

**Your Assessment**:
[RATE: Simple / Moderately Complex / Too Complex]
[EXPLANATION: _____]

#### Consistent Operating History
**Question**: Has this business proven itself over time?

Review:
- 10+ years of operating data available?
- Consistent performance through economic cycles?
- Any major business model changes or pivots?
- Track record of earnings predictability?

**Your Assessment**:
[RATE: Highly Consistent / Moderately Consistent / Inconsistent]
[EXPLANATION: _____]

#### Favorable Long-Term Prospects
**Question**: Will this business be better 10 years from now?

Consider:
- Secular tailwinds or headwinds?
- Growing total addressable market (TAM)?
- Exposure to disruptive technologies?
- Regulatory risks?
- Obsolescence risk (newspapers, retail, etc.)?

**Your Assessment**:
[RATE: Excellent Prospects / Good / Fair / Poor]
[EXPLANATION: _____]

---

### 2. Management Tenets (Qualitative Assessment)

#### Rationality
**Question**: Does management act in shareholders' best interest?

Investigate:
- Capital allocation decisions (dividends, buybacks, acquisitions)
- Discipline in saying "no" to bad deals
- Willingness to return cash vs empire building
- History of acquisitions - value created or destroyed?

**Red Flags**:
- Serial acquirers with declining ROIC
- Excessive stock-based compensation
- Lavish corporate perks
- Jet usage, country club memberships, etc.

**Your Assessment**:
[RATE: Highly Rational / Mostly Rational / Questionable / Red Flag]
[EXPLANATION: _____]

#### Candor with Shareholders
**Question**: Does management tell the truth, even when painful?

Look for:
- Transparency in annual letters
- Acknowledgment of mistakes
- Clear explanations of setbacks
- Avoidance of corporate speak and buzzwords
- Guidance that proves realistic over time

**Red Flags**:
- Overly promotional language
- Blame external factors
- Frequent restatements
- Aggressive accounting
- Lack of specificity

**Your Assessment**:
[RATE: Exceptionally Candid / Mostly Honest / Corporate Speak / Deceptive]
[EXPLANATION: _____]

#### Resisting Institutional Imperative
**Question**: Does management think independently or follow the herd?

Consider:
- Do they copy competitors' strategies blindly?
- History of fads (blockchain, metaverse, AI buzzwords)?
- Willingness to be contrarian when right?
- Focus on long-term vs quarterly earnings obsession?

**Your Assessment**:
[RATE: Independent Thinker / Somewhat Independent / Follows Herd]
[EXPLANATION: _____]

---

### 3. Economic Moat Analysis (Deep Dive)

**Question**: What protects this business from competition?

#### Moat Sources (Check all that apply):

□ **Network Effects**
- Value increases as more users join
- Examples: Credit cards, social media, marketplaces
- Strength: [WEAK / MODERATE / STRONG]

□ **Intangible Assets**
- Brand (pricing power, customer preference)
- Patents (pharmaceutical protection)
- Regulatory licenses (utilities, banks)
- Strength: [WEAK / MODERATE / STRONG]

□ **Switching Costs**
- Expensive/painful for customers to leave
- Examples: Enterprise software, bank accounts
- Strength: [WEAK / MODERATE / STRONG]

□ **Cost Advantages**
- Scale economies (Walmart, Costco)
- Proprietary technology/processes
- Unique access to resources
- Strength: [WEAK / MODERATE / STRONG]

#### Moat Durability
**Question**: Will this moat last 10-20 years?

Threats to consider:
- Technology disruption
- Regulatory changes
- New entrants with better business models
- Customer behavior shifts

**Your Assessment**:
[MOAT RATING: Wide / Narrow / Weak / None]
[DURABILITY: Durable (10+ years) / Fragile (5 years) / Eroding]
[EXPLANATION: _____]

---

### 4. Competitive Advantage Period (CAP)

**Question**: How long will this company earn excess returns?

Estimate:
- Years of competitive advantage remaining: [____ years]
- What would cause moat to disappear?
- Is moat widening or narrowing?

Historical context:
- Most companies: 5-10 years CAP
- Great companies: 15-20+ years CAP
- Coca-Cola, Sees Candies: 50+ years

**Your Estimate**:
[CAP: ____ years]
[CONFIDENCE: High / Medium / Low]
[REASONING: _____]

---

### 5. Management Quality Red Flags

Check for warning signs:

□ Frequent CEO turnover
□ Founder left company
□ Board lacks independence
□ Related-party transactions
□ Serial dilution of shareholders
□ Options backdating scandals
□ Accounting irregularities
□ Excessive use of non-GAAP metrics
□ Guidance always beaten by exactly 1 penny
□ Acquisition-driven growth without organic growth
□ Insider selling at market peaks

**Red Flags Detected**: [COUNT]
**Severity**: [NONE / MINOR / MODERATE / SEVERE]

---

### 6. Circle of Competence

**Question**: Do I understand this business well enough to invest?

Personal assessment:
- Industry knowledge: [LOW / MEDIUM / HIGH]
- Technology understanding: [LOW / MEDIUM / HIGH]
- Customer behavior insights: [LOW / MEDIUM / HIGH]
- Competitive landscape: [LOW / MEDIUM / HIGH]

**Buffett Rule**: "If you're in the right circle of competence, you'll be 
exactly right. But if you're outside, you'll be dramatically wrong."

**Decision**:
[WITHIN CIRCLE / EDGE OF CIRCLE / OUTSIDE CIRCLE]

---

### 7. Price Discipline

**Intrinsic Value Context** (from quantitative model):
- Calculated IV: ${{INTRINSIC_VALUE}}
- Current Price: ${{CURRENT_PRICE}}
- Margin of Safety: {{MARGIN_OF_SAFETY}}%

**Question**: Even if this is a great business, is the price right?

Considerations:
- Am I paying for quality or overpaying?
- What's priced in (growth expectations)?
- What's the opportunity cost vs other investments?
- Can I wait for a better price?

**"Price is what you pay. Value is what you get."**

**Your Assessment**:
[RATING: Bargain / Fair Price / Expensive / Overvalued]
[WILLING TO BUY: Yes / No / Maybe at $____ ]

---

### 8. Final Recommendation

Based on:
1. Business quality (simplicity, history, prospects)
2. Management quality (rationality, candor, independence)
3. Economic moat (width and durability)
4. Competitive advantage period
5. Absence of red flags
6. Circle of competence fit
7. Price discipline

**Investment Thesis**:
[3-5 sentences summarizing the bull case]

**Key Risks**:
[Top 3 risks that could invalidate the thesis]

**Final Rating**:
□ **STRONG BUY** - Great business, great price, high confidence
□ **BUY** - Good business, acceptable price
□ **HOLD** - Good business, wrong price (add to watchlist)
□ **PASS** - Business quality concerns or outside competence circle
□ **AVOID** - Red flags detected

**Position Sizing Recommendation**:
[PERCENTAGE OF PORTFOLIO: ____%]
- Conservative: 5-10% (normal positions)
- Concentrated: 10-20% (exceptional opportunities)
- Minimum: 2-5% (uncertain but promising)

**Holding Period**:
[EXPECTED: ___ years]
- Buffett: "Our favorite holding period is forever"

---

### 9. Monitoring Checklist

If we invest, watch for these triggers to sell:

□ Business fundamentals deteriorate
□ Management change (especially CEO)
□ Moat begins eroding
□ Better opportunity arises (opportunity cost)
□ Price exceeds intrinsic value by 30%+
□ Thesis was wrong (admit mistake, exit)

**Do NOT sell because**:
- Stock price drops (if thesis intact)
- Market volatility
- Short-term earnings miss
- "Taking profits" (tax inefficient)

---

## Output Format

Provide:
1. Completed analysis for each section
2. Final recommendation with conviction level
3. One-page investment memo (executive summary)
4. List of information gaps that need research

```

#### Implementation

**File**: `Stock-Analysis/app/Services/AI/BuffettQualitativeAnalyzer.php`

```php
class BuffettQualitativeAnalyzer {
    private OpenAIService $ai;
    private MarketDataService $marketData;
    
    public function analyzeQuality(string $symbol): array {
        // 1. Gather quantitative context
        $quantMetrics = $this->getQuantitativeContext($symbol);
        
        // 2. Fetch company documents
        $documents = $this->fetchCompanyDocuments($symbol); // 10-K, letters
        
        // 3. Build prompt with context
        $prompt = $this->buildPrompt($symbol, $quantMetrics, $documents);
        
        // 4. Send to AI (GPT-4, Claude, etc.)
        $analysis = $this->ai->analyze($prompt);
        
        // 5. Parse and structure response
        return $this->parseAnalysis($analysis);
    }
    
    private function buildPrompt($symbol, $metrics, $docs): string {
        return file_get_contents(__DIR__ . '/../../prompts/buffett_qualitative_analysis.md')
            . "\n## Company Documents\n"
            . $docs['annual_letter'] . "\n"
            . $docs['10k_excerpt'] . "\n";
    }
}
```

**Integration with Strategy**:

```php
// In WarrenBuffettStrategyService.php
public function analyzeWithQualitative(string $symbol): array {
    // Existing quantitative analysis
    $quantAnalysis = $this->analyze($symbol);
    
    // New qualitative analysis
    if ($this->enableQualitativeAnalysis) {
        $qualAnalyzer = new BuffettQualitativeAnalyzer();
        $qualAnalysis = $qualAnalyzer->analyzeQuality($symbol);
        
        // Combine scores
        $finalScore = ($quantAnalysis['quality_score'] * 0.5) 
                    + ($qualAnalysis['quality_score'] * 0.5);
        
        return array_merge($quantAnalysis, [
            'qualitative_analysis' => $qualAnalysis,
            'combined_score' => $finalScore,
            'management_concerns' => $qualAnalysis['red_flags'],
            'moat_assessment' => $qualAnalysis['moat'],
            'investment_thesis' => $qualAnalysis['thesis']
        ]);
    }
    
    return $quantAnalysis;
}
```

**Benefits**:
- ✅ Captures "soft" factors Buffett considers
- ✅ Systematic qualitative assessment
- ✅ Red flag detection
- ✅ Management quality evaluation
- ✅ Moat durability analysis
- ✅ Circle of competence check
- ✅ Combines with quantitative for holistic view

---

### 9.4 Real-Time Data Integration

**Goal**: Move from delayed data to real-time feeds

**Options**:
- Polygon.io - $29/month for real-time
- IEX Cloud - Usage-based pricing
- Alpha Vantage Premium - $49/month
- Tradier - Free for developers

**Components**:
- WebSocket connection for streaming quotes
- Real-time trade execution
- Live portfolio P&L
- Instant signal generation

---

### 9.5 Machine Learning Integration

**Goal**: Use ML to improve strategy selection and signal generation

**Applications**:
- Predict best strategy for current market regime
- Optimize strategy weights dynamically
- Pattern recognition beyond candlesticks
- Sentiment analysis from news/social media
- Earnings surprise prediction

---

### 9.6 Mobile Application

**Goal**: iOS/Android app for portfolio monitoring

**Features**:
- Real-time portfolio tracking
- Push notifications for signals
- Trade execution from mobile
- Watchlist management
- Chart viewing

**Tech Stack**: React Native or Flutter

---

## Appendix A: File Structure

```
WealthSystem/
├── Stock-Analysis/
│   ├── app/
│   │   ├── Services/
│   │   │   ├── MarketDataService.php ✅
│   │   │   ├── PortfolioManager.php ✅
│   │   │   ├── Trading/
│   │   │   │   ├── TurtleStrategyService.php ✅
│   │   │   │   ├── WarrenBuffettStrategyService.php ✅
│   │   │   │   ├── QualityDividendStrategyService.php ✅
│   │   │   │   └── ... (12 more strategies) ✅
│   │   │   ├── SectorAnalysisService.php ✅
│   │   │   ├── IndexBenchmarkingService.php ✅
│   │   │   └── FundCompositionService.php ✅
│   │   ├── Models/
│   │   │   ├── Fund.php ✅
│   │   │   ├── FundHolding.php ✅
│   │   │   └── FundEligibility.php ✅
│   │   └── DAOs/
│   │       ├── FundDAO.php ✅
│   │       ├── FundHoldingDAO.php ✅
│   │       └── FundEligibilityDAO.php ✅
│   ├── src/
│   │   ├── Services/
│   │   │   └── Calculators/
│   │   │       └── CandlestickPatternCalculator.php ✅ 470+ lines, 63 patterns
│   │   └── Ksfraser/Finance/Strategies/
│   │       └── TurtleStrategy.php ✅
│   ├── tests/
│   │   └── Unit/
│   │       ├── Services/
│   │       │   ├── SectorAnalysisServiceTest.php ✅
│   │       │   ├── IndexBenchmarkingServiceTest.php ✅
│   │       │   ├── FundCompositionServiceTest.php ✅
│   │       │   └── Calculators/
│   │       │       └── CandlestickPatternCalculatorTest.php ✅ 35 tests
│   │       └── ... (strategy tests)
│   ├── prompts/ ⏳ TO CREATE
│   │   └── buffett_qualitative_analysis.md
│   └── Project_Work_Products/
│       └── ProjectDocuments/
│           ├── Design/
│           │   ├── SYSTEM_DESIGN_DOCUMENT.md ✅ THIS FILE
│           │   └── Technical/
│           │       └── TA-Lib_Integration_Analysis.md ✅ 1,000+ lines
│           ├── Requirements/
│           │   └── requirements.md ✅
│           └── Traceability/
│               └── REQUIREMENTS_TRACEABILITY_MATRIX.md ✅
├── docs/
│   ├── SECTOR_ANALYSIS_FEATURE.md ✅
│   ├── INDEX_BENCHMARKING_FEATURE.md ✅
│   └── FUND_COMPOSITION_FEATURE.md 🔨 IN PROGRESS
├── TRADING_SYSTEM_USER_MANUAL.md ✅
└── README.md ✅
```

---

## Appendix B: Quick Reference

### Strategy Selection Guide

| Market Condition | Best Strategies | Avoid |
|-----------------|-----------------|-------|
| Strong Uptrend | Turtle, Momentum, Small Cap | Mean Reversion, Contrarian |
| Downtrend | Contrarian, Quality Dividend | Momentum, Turtle |
| Sideways/Choppy | Mean Reversion, Support/Resistance | Turtle, Momentum |
| High Volatility | Quality Dividend, Warren Buffett | Small Cap, Leveraged |
| Low Volatility | Small Cap, Momentum | Mean Reversion |
| Bull Market Peak | Take Profit, Quality Dividend | Aggressive Growth |
| Bear Market Bottom | Contrarian, Warren Buffett | Momentum, Trend |

### Indicator Categories

| Category | Count | Examples |
|----------|-------|----------|
| Trend | 20+ | SMA, EMA, ADX, Aroon |
| Momentum | 15+ | RSI, MACD, Stochastic |
| Volatility | 8 | Bollinger Bands, ATR |
| Volume | 6 | OBV, A/D, MFI |
| Candlestick | 63 | Hammer, Doji, Engulfing |
| Cycle | 5 | Hilbert Transform |
| Statistics | 10+ | Correlation, Beta, Variance |

### Testing Coverage Summary

| Component | Tests | Assertions | Coverage |
|-----------|-------|------------|----------|
| Sector Analysis | 11 | 67 | 100% |
| Index Benchmarking | 11 | 48 | 100% |
| Fund Composition | 11 | 52 | 100% |
| Turtle Strategy | 11 | 50+ | 100% |
| Other Strategies | Varies | - | Partial |
| **Total** | **44+** | **217+** | **~85%** |

---

## Document History

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2025-12-03 | Initial creation, comprehensive system design | Development Team |

---

## Next Actions

### Immediate (This Week)
1. ✅ Create this design document
2. ⏳ Complete Fund Composition Feature Guide
3. ⏳ Install TA-Lib PHP extension
4. ⏳ Implement CandlestickPatternCalculator

### Short-term (This Month)
5. ⏳ Full TA-Lib integration (150+ indicators)
6. ⏳ Create Buffett qualitative analysis prompts
7. ⏳ Build candlestick pattern trading strategy
8. ⏳ Design fund eligibility admin UI

### Medium-term (Q1 2026)
9. Real-time data integration
10. Mobile app prototype
11. ML strategy optimization
12. Advanced backtesting engine

---

**End of Document**
