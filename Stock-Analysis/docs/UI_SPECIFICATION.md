# WealthSystem UI Specification

**Version**: 1.0  
**Last Updated**: December 3, 2025  
**Status**: Design Phase  
**Target Completion**: Q1 2026  

---

## Table of Contents

1. [Executive Summary](#executive-summary)
2. [Design Principles](#design-principles)
3. [Component Architecture](#component-architecture)
4. [Dashboard Layout](#dashboard-layout)
5. [Tooltip System](#tooltip-system)
6. [Help & Documentation](#help--documentation)
7. [Indicator Panel](#indicator-panel)
8. [Strategy Selector](#strategy-selector)
9. [Pattern Recognition Interface](#pattern-recognition-interface)
10. [Fund Analysis Interface](#fund-analysis-interface)
11. [Responsive Design](#responsive-design)
12. [Accessibility](#accessibility)
13. [Implementation Plan](#implementation-plan)

---

## Executive Summary

### Purpose
This document specifies the user interface for the WealthSystem trading platform, focusing on contextual help, tooltips, and intuitive navigation for technical indicators, trading strategies, and fund analysis.

### Target Users
- **Primary**: Financial advisors, active traders
- **Secondary**: Long-term investors, portfolio managers
- **Skill Levels**: Beginner to advanced

### Key Features
1. **Contextual help system** with tooltips, modal dialogs, and inline documentation
2. **Trading dashboard** with real-time indicators and pattern detection
3. **Strategy builder** with visual configuration
4. **Fund analysis tools** with overlap detection and MER comparison
5. **Responsive design** for desktop, tablet, and mobile

### Technology Stack (Recommended)
- **Frontend**: React 18+ with TypeScript
- **State Management**: Redux Toolkit or Zustand
- **UI Framework**: Material-UI or Ant Design
- **Charts**: TradingView Lightweight Charts or Recharts
- **Documentation**: React-Markdown for embedded docs
- **API**: REST + WebSocket for real-time data

---

## Design Principles

### 1. Progressive Disclosure
- Show essential information first
- Reveal complexity gradually
- Collapsible sections for advanced features
- Tooltips for quick reference, modals for deep dives

### 2. Contextual Help
- Tooltip on hover (desktop) or tap (mobile)
- Help icon (?) next to every indicator/strategy
- "Learn More" links to full documentation
- Inline examples with real data

### 3. Visual Hierarchy
- Primary actions: Bold, colored buttons
- Secondary actions: Text links
- Critical alerts: Red/yellow warnings
- Success states: Green confirmations

### 4. Consistency
- Uniform tooltip styling
- Standardized icon set (Material Icons or Font Awesome)
- Consistent color scheme across components
- Predictable interaction patterns

### 5. Performance
- Lazy load documentation
- Cache indicator calculations
- Virtualized lists for large datasets
- WebSocket for real-time updates (not polling)

---

## Component Architecture

### Component Hierarchy
```
App
├── Navigation
│   ├── TopBar (logo, user, settings)
│   ├── SideBar (main navigation)
│   └── Breadcrumbs
├── Dashboard
│   ├── PortfolioSummary
│   ├── WatchList
│   └── QuickActions
├── TradingWorkspace
│   ├── ChartContainer
│   │   ├── CandlestickChart
│   │   ├── IndicatorOverlays
│   │   └── PatternAnnotations
│   ├── IndicatorPanel
│   │   ├── IndicatorList
│   │   ├── IndicatorConfig
│   │   └── IndicatorResults
│   ├── StrategyPanel
│   │   ├── StrategySelector
│   │   ├── StrategyConfig
│   │   └── BacktestResults
│   └── OrderPanel
│       ├── OrderEntry
│       ├── PositionSizing
│       └── RiskCalculator
├── FundAnalysis
│   ├── FundSelector
│   ├── CompositionView
│   ├── OverlapAnalysis
│   └── MERComparison
├── Documentation
│   ├── SearchBar
│   ├── CategoryNav
│   ├── ArticleViewer
│   └── RelatedTopics
└── SharedComponents
    ├── Tooltip
    ├── HelpModal
    ├── LoadingSpinner
    └── ErrorBoundary
```

---

## Dashboard Layout

### Main Dashboard (Default View)

```
┌─────────────────────────────────────────────────────────────┐
│ [WealthSystem Logo]    Dashboard    [Notifications] [User▾] │
├──────┬──────────────────────────────────────────────────────┤
│      │  Portfolio Value: $125,430.50  (+2.3%) ────────────  │
│ NAV  │  ┌──────────────────────────────────────────┐        │
│      │  │  [Pie Chart: Asset Allocation]            │        │
│ ◉ Dash│  │  • Stocks: 60%  • Funds: 30%  • Cash: 10%│        │
│ ○ Trade│  └──────────────────────────────────────────┘        │
│ ○ Funds│                                                      │
│ ○ Strat│  WatchList (?)                       [+ Add]        │
│ ○ Docs │  ┌─────┬────────┬────────┬────────┬────────────┐  │
│      │  │Ticker│ Price  │ Change │ RSI(?) │ Signal (?) │  │
│      │  ├─────┼────────┼────────┼────────┼────────────┤  │
│      │  │AAPL │ 185.50 │ +1.2%  │ 68 🟢  │ Buy        │  │
│      │  │GOOGL│ 140.20 │ -0.5%  │ 45 🟡  │ Hold       │  │
│      │  │MSFT │ 378.00 │ +0.8%  │ 72 🔴  │ Overbought │  │
│      │  └─────┴────────┴────────┴────────┴────────────┘  │
│      │                                                      │
│      │  Active Strategies                    [Manage]      │
│      │  ┌──────────────────────────────────────────┐      │
│      │  │ ✓ Turtle Trading    (5 positions)  (?)   │      │
│      │  │ ✓ Quality Dividend  (12 positions) (?)   │      │
│      │  │ ○ Buffett Value     (0 positions)  (?)   │      │
│      │  └──────────────────────────────────────────┘      │
└──────┴──────────────────────────────────────────────────────┘

Legend:
(?) = Help icon with tooltip
[Button] = Clickable button
🟢🟡🔴 = Traffic light indicators
```

### Tooltip Example (RSI Column)

**On Hover/Tap**:
```
┌───────────────────────────────────────────┐
│ RSI (Relative Strength Index)            │
│                                           │
│ Current: 68 (Neutral to Overbought)      │
│ Range: 0-100                              │
│ Overbought: > 70                          │
│ Oversold: < 30                            │
│                                           │
│ AAPL's RSI is approaching overbought.    │
│ Consider taking profits or waiting for   │
│ pullback.                                 │
│                                           │
│ [Learn More] [Configure Alert]           │
└───────────────────────────────────────────┘
```

---

## Tooltip System

### Tooltip Types

#### 1. Quick Info Tooltip (Default)
**Trigger**: Hover (desktop) or tap help icon (mobile)  
**Display**: Small popup near cursor/icon  
**Duration**: Visible while hovering, dismissed on mouse-out  
**Content**:
- Indicator name
- Current value
- Interpretation (1-2 sentences)
- Color-coded status

**Example**:
```jsx
<Tooltip
  title="RSI: 68"
  content="Relative Strength Index. Approaching overbought territory (>70). Consider taking profits."
  status="warning"
  position="top"
/>
```

#### 2. Detailed Tooltip (Shift+Hover or Click Help Icon)
**Trigger**: Shift+hover or click (?)  
**Display**: Larger popup with richer content  
**Duration**: Sticky (must click outside to dismiss)  
**Content**:
- Full indicator name
- Current value with context
- Calculation formula (simplified)
- Interpretation guide
- Historical range/chart
- Related indicators
- Action buttons ("Learn More", "Configure")

**Example**:
```jsx
<DetailedTooltip
  indicator="RSI"
  value={68}
  range={[0, 100]}
  interpretation="Approaching overbought. Watch for divergence."
  relatedIndicators={["Stochastic", "MFI", "CCI"]}
  onLearnMore={() => navigate('/docs/indicators/rsi')}
  onConfigure={() => openModal('RSI_CONFIG')}
/>
```

#### 3. Inline Documentation
**Trigger**: Expandable section within panel  
**Display**: Accordion-style expansion  
**Content**:
- Full explanation
- Code examples
- Visual examples
- Best practices

**Example**:
```jsx
<Accordion>
  <AccordionSummary icon={<HelpIcon />}>
    About Bollinger Bands
  </AccordionSummary>
  <AccordionDetails>
    <IndicatorDocs indicator="bollingerBands" />
  </AccordionDetails>
</Accordion>
```

### Tooltip Styling Guidelines

**Color Scheme**:
- Background: `rgba(0, 0, 0, 0.95)` (dark) or `#ffffff` (light mode)
- Text: White (dark mode) or `#333333` (light mode)
- Border: 1px solid `rgba(255, 255, 255, 0.2)`
- Shadow: `0 4px 12px rgba(0, 0, 0, 0.3)`

**Typography**:
- Title: 14px bold, sans-serif
- Body: 12px regular, sans-serif
- Code: 11px monospace, `#00ff00` (matrix green)

**Status Colors**:
- Bullish: `#4caf50` (green)
- Bearish: `#f44336` (red)
- Neutral: `#ff9800` (orange)
- Warning: `#ffc107` (amber)
- Info: `#2196f3` (blue)

**Animation**:
- Fade in: 150ms ease-out
- Fade out: 100ms ease-in
- Slide direction: Depends on available space

---

## Indicator Panel

### Layout

```
┌─────────────────────────────────────────────────┐
│ Indicators                  [+ Add] [⚙ Config]  │
├─────────────────────────────────────────────────┤
│ 📊 Momentum (?)                                  │
│   ├─ RSI (14) ────────────────── 68 🟡 [?][x]  │
│   │   └─ [▼ Show Details]                       │
│   ├─ MACD (12,26,9) ───────────── ▲ Bullish    │
│   │   └─ [▼ Show Details]                       │
│   └─ Stochastic ──────────────── 75 🔴         │
│                                                  │
│ 📈 Trend (?)                                     │
│   ├─ EMA (20) ────────────────── $185.20 ▲     │
│   ├─ ADX (14) ────────────────── 32 Strong     │
│   └─ Parabolic SAR ──────────── $183.50        │
│                                                  │
│ 💥 Volatility (?)                                │
│   └─ ATR (14) ────────────────── $3.45         │
│                                                  │
│ 📊 Volume (?)                                    │
│   └─ OBV ─────────────────────── ▲ Bullish     │
└─────────────────────────────────────────────────┘
```

### Indicator Card (Expanded)

```
┌─────────────────────────────────────────────────┐
│ RSI (Relative Strength Index) [?] [⚙] [x]      │
├─────────────────────────────────────────────────┤
│ Current Value: 68                               │
│ ┌───────────────────────────────────────────┐  │
│ │ 0 ──────────|──────────|──────────── 100   │  │
│ │       30 (Oversold)    70 (Overbought)     │  │
│ │              ◉ 68                          │  │
│ └───────────────────────────────────────────┘  │
│                                                  │
│ Status: ⚠ Approaching Overbought                │
│ Signal: Consider taking profits or tightening   │
│         stop loss.                              │
│                                                  │
│ History (14 days):                              │
│ ┌───────────────────────────────────────────┐  │
│ │ [Mini line chart showing RSI trend]        │  │
│ └───────────────────────────────────────────┘  │
│                                                  │
│ [Learn More] [Set Alert] [Configure]           │
└─────────────────────────────────────────────────┘
```

### Help Modal (When "?" Clicked)

```
┌──────────────────────────────────────────────────────┐
│ RSI (Relative Strength Index)              [Close X] │
├──────────────────────────────────────────────────────┤
│ [Overview] [How to Use] [Examples] [Related]         │
├──────────────────────────────────────────────────────┤
│                                                       │
│ What is RSI?                                          │
│ ═══════════                                           │
│ RSI is a momentum oscillator that measures the speed │
│ and magnitude of recent price changes to evaluate    │
│ overbought or oversold conditions.                    │
│                                                       │
│ Interpretation:                                       │
│ • RSI > 70: Overbought (potential sell signal)       │
│ • RSI < 30: Oversold (potential buy signal)          │
│ • RSI ≈ 50: Neutral momentum                          │
│                                                       │
│ Formula:                                              │
│ ┌──────────────────────────────────────────┐        │
│ │ RSI = 100 - (100 / (1 + RS))              │        │
│ │ RS = Avg Gain / Avg Loss (14 periods)     │        │
│ └──────────────────────────────────────────┘        │
│                                                       │
│ Best Practices:                                       │
│ 1. Don't rely on RSI alone—use with other indicators │
│ 2. In strong trends, RSI can stay overbought/oversold│
│ 3. Look for divergences (price vs RSI)               │
│ 4. Adjust thresholds in trending markets (80/20)     │
│                                                       │
│ Example:                                              │
│ [Interactive chart showing RSI + price action]       │
│                                                       │
│ [Back to Trading] [View Full Documentation]          │
└──────────────────────────────────────────────────────┘
```

---

## Strategy Selector

### Strategy Library View

```
┌───────────────────────────────────────────────────────┐
│ Trading Strategies                         [+ Custom]  │
├───────────────────────────────────────────────────────┤
│ Search: [                          ] 🔍               │
│ Filter: [All ▾] [Active Only ☐]                      │
├───────────────────────────────────────────────────────┤
│                                                        │
│ ┌────────────────────────────────────────────────┐   │
│ │ 🐢 Turtle Trading                      [Active] │   │
│ │ Trend-following system with breakout entries   │   │
│ │ Risk Level: High  Timeframe: Long-term         │   │
│ │ Win Rate: 40%  Avg Return: +45% annually       │   │
│ │ [View Details] [Configure (⚙)] [Help (?)]     │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ ┌────────────────────────────────────────────────┐   │
│ │ 💰 Warren Buffett Value                [Setup] │   │
│ │ Long-term value investing with quality focus   │   │
│ │ Risk Level: Low  Timeframe: Years              │   │
│ │ Win Rate: 70%  Avg Return: +15% annually       │   │
│ │ [View Details] [Configure (⚙)] [Help (?)]     │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ ┌────────────────────────────────────────────────┐   │
│ │ 📈 Quality Dividend Growth         [Active]    │   │
│ │ Income + growth with dividend aristocrats      │   │
│ │ Risk Level: Low-Med  Timeframe: Medium-Long    │   │
│ │ Win Rate: 65%  Avg Return: +12% annually       │   │
│ │ [View Details] [Configure (⚙)] [Help (?)]     │   │
│ └────────────────────────────────────────────────┘   │
│                                                        │
│ [Load More...]                                         │
└───────────────────────────────────────────────────────┘
```

### Strategy Details Modal

```
┌────────────────────────────────────────────────────────────┐
│ 🐢 Turtle Trading Strategy                       [Close X] │
├────────────────────────────────────────────────────────────┤
│ [Overview] [Rules] [Backtest] [Configure] [Help]          │
├────────────────────────────────────────────────────────────┤
│                                                             │
│ Created by Richard Dennis & William Eckhardt (1983)        │
│ ═══════════════════════════════════════════════════════     │
│                                                             │
│ Philosophy:                                                 │
│ Trend-following system that buys breakouts and uses strict │
│ position sizing and risk management.                        │
│                                                             │
│ Entry Rules (?)                                             │
│ • System 1: 20-day price breakout (short-term)             │
│ • System 2: 55-day price breakout (long-term)              │
│                                                             │
│ Position Sizing (?)                                         │
│ • Risk 1-2% per trade                                       │
│ • Unit = 1% portfolio risk ÷ N (N = 20-day ATR)            │
│ • Max 4 units per position, 12 units total portfolio       │
│                                                             │
│ Stop Loss (?)                                               │
│ • Exit at 2N (2× ATR) loss from entry                      │
│                                                             │
│ Exit Rules (?)                                              │
│ • System 1: 10-day low (long) or high (short)              │
│ • System 2: 20-day low (long) or high (short)              │
│                                                             │
│ Performance (Backtest 2010-2025):                           │
│ ┌──────────────────────────────────────────────┐          │
│ │ Total Return: +450%                           │          │
│ │ Annual Return: +11.8%                         │          │
│ │ Win Rate: 38%                                 │          │
│ │ Sharpe Ratio: 0.85                            │          │
│ │ Max Drawdown: -32%                            │          │
│ └──────────────────────────────────────────────┘          │
│                                                             │
│ Pros:                                 Cons:                 │
│ • Captures big trends                 • Many small losses   │
│ • Defined risk                        • Requires discipline │
│ • Simple rules                        • High drawdowns     │
│                                                             │
│ [Activate Strategy] [Run Backtest] [Full Documentation]    │
└────────────────────────────────────────────────────────────┘
```

---

## Pattern Recognition Interface

### Pattern Detection Panel

```
┌─────────────────────────────────────────────────────┐
│ Candlestick Patterns (?)            [Scan All]      │
├─────────────────────────────────────────────────────┤
│ Detected Patterns (Last 10 Days):                   │
│                                                      │
│ Dec 3, 2025 ─────────────────────────────────────── │
│ ┌───────────────────────────────────────────────┐  │
│ │ 🟢 Bullish Engulfing (?) [HIGH RELIABILITY]   │  │
│ │ AAPL @ $185.50                                 │  │
│ │                                                 │  │
│ │ Signal: Strong reversal                        │  │
│ │ Strength: +85/100                              │  │
│ │ Target: $192.50 (+3.8%)                        │  │
│ │ Stop Loss: $182.00 (-1.9%)                     │  │
│ │ Risk/Reward: 2.0:1                             │  │
│ │                                                 │  │
│ │ [View Chart] [Add to WatchList] [Learn More]  │  │
│ └───────────────────────────────────────────────┘  │
│                                                      │
│ Dec 2, 2025 ─────────────────────────────────────── │
│ ┌───────────────────────────────────────────────┐  │
│ │ 🔴 Evening Star (?) [HIGH RELIABILITY]        │  │
│ │ GOOGL @ $140.20                                │  │
│ │                                                 │  │
│ │ Signal: Bearish reversal                       │  │
│ │ Strength: -80/100                              │  │
│ │ Target: $134.50 (-4.1%)                        │  │
│ │ Stop Loss: $143.00 (+2.0%)                     │  │
│ │                                                 │  │
│ │ [View Chart] [Add to WatchList] [Learn More]  │  │
│ └───────────────────────────────────────────────┘  │
│                                                      │
│ [View All Patterns] [Configure Filters]             │
└─────────────────────────────────────────────────────┘
```

### Pattern Detail View (Modal or Sidebar)

```
┌──────────────────────────────────────────────────────┐
│ Bullish Engulfing Pattern                  [Close X] │
├──────────────────────────────────────────────────────┤
│                                                       │
│ [Chart showing pattern on AAPL]                      │
│ ┌───────────────────────────────────────────────┐  │
│ │        ┌─┐                                     │  │
│ │        │░│                                     │  │
│ │    ┌─┐ │░│                                     │  │
│ │    │▓│ │░│ ← Bullish Engulfing                │  │
│ │    └─┘ └─┘                                     │  │
│ │    Day1 Day2                                   │  │
│ └───────────────────────────────────────────────┘  │
│                                                       │
│ What happened:                                        │
│ • Day 1: Red candle (bearish)                        │
│ • Day 2: Large green candle completely engulfs Day 1 │
│ • Volume 2.5× average (strong conviction)            │
│                                                       │
│ Psychology:                                           │
│ Bears were in control on Day 1, but bulls took over  │
│ and completely reversed the prior day's action.       │
│ Strong buying pressure indicates potential reversal.  │
│                                                       │
│ Trading Plan:                                         │
│ Entry: $185.50 (current price)                       │
│ Target: $192.50 (+3.8%, resistance level)            │
│ Stop: $182.00 (-1.9%, below pattern low)             │
│ Position Size: 500 shares (risk $1,750 = 1.4%)       │
│                                                       │
│ Confirmation Needed:                                  │
│ ☑ Volume above average                                │
│ ☑ RSI showing divergence                              │
│ ☐ Break above resistance at $187                     │
│                                                       │
│ [Create Order] [Add Alert] [View Similar Patterns]   │
└──────────────────────────────────────────────────────┘
```

---

## Fund Analysis Interface

### Fund Composition View

```
┌───────────────────────────────────────────────────────────┐
│ Fund Analysis                          [Compare Funds]     │
├───────────────────────────────────────────────────────────┤
│ Selected Fund: XYZ Balanced Growth Fund                   │
│ MER: 2.25% (Preferred Tier) (?)                           │
│                                                            │
│ Holdings (Top 10 of 85) (?)                [View All]     │
│ ┌────────┬──────────────────┬────────┬──────────────┐   │
│ │ Ticker │ Name             │ Weight │ Sector (?)   │   │
│ ├────────┼──────────────────┼────────┼──────────────┤   │
│ │ AAPL   │ Apple Inc.       │ 6.5%   │ Technology   │   │
│ │ MSFT   │ Microsoft Corp.  │ 5.8%   │ Technology   │   │
│ │ GOOGL  │ Alphabet Inc.    │ 4.2%   │ Technology   │   │
│ │ JNJ    │ Johnson & Johnson│ 3.9%   │ Healthcare   │   │
│ │ JPM    │ JPMorgan Chase   │ 3.5%   │ Financials   │   │
│ └────────┴──────────────────┴────────┴──────────────┘   │
│                                                            │
│ Sector Allocation (?)                                      │
│ ┌────────────────────────────────────────────────────┐   │
│ │ Technology    ████████████░░░░░░░░ 30.5%           │   │
│ │ Financials    ██████░░░░░░░░░░░░░░ 18.2%           │   │
│ │ Healthcare    █████░░░░░░░░░░░░░░░ 15.8%           │   │
│ │ Industrials   ███░░░░░░░░░░░░░░░░░ 10.1%           │   │
│ │ Consumer      ███░░░░░░░░░░░░░░░░░  9.4%           │   │
│ │ Other         ████░░░░░░░░░░░░░░░░ 16.0%           │   │
│ └────────────────────────────────────────────────────┘   │
│                                                            │
│ Concentration (?)                                          │
│ • HHI Score: 1,245 (Moderately Concentrated)              │
│ • Top 10 Holdings: 42.3%                                   │
│                                                            │
│ [Download Holdings] [View Fact Sheet] [Compare MERs]      │
└───────────────────────────────────────────────────────────┘
```

### Fund Overlap Analysis

```
┌──────────────────────────────────────────────────────────┐
│ Fund Overlap Analysis (?)                       [Close X] │
├──────────────────────────────────────────────────────────┤
│ Comparing:                                                │
│ • XYZ Balanced Growth Fund                                │
│ • ABC Equity Growth Fund                                  │
│                                                            │
│ Overlap Summary:                                           │
│ ┌────────────────────────────────────────────────────┐   │
│ │ Weighted Overlap: 58.3% ⚠ HIGH OVERLAP             │   │
│ │                                                     │   │
│ │ ████████████░░░░░░░░░ 58%                          │   │
│ │ └─────────────────────┴──────────┘                 │   │
│ │     0%            50%           100%                │   │
│ └────────────────────────────────────────────────────┘   │
│                                                            │
│ Interpretation:                                            │
│ ⚠ Warning: You have significant redundancy between these  │
│   funds. Consider consolidating to reduce fees.           │
│                                                            │
│ Shared Holdings (23 stocks):                               │
│ ┌────────┬─────────────────┬──────────┬──────────┐       │
│ │ Ticker │ Name            │ Fund 1   │ Fund 2   │       │
│ ├────────┼─────────────────┼──────────┼──────────┤       │
│ │ AAPL   │ Apple Inc.      │ 6.5%     │ 7.2%     │       │
│ │ MSFT   │ Microsoft       │ 5.8%     │ 6.1%     │       │
│ │ GOOGL  │ Alphabet        │ 4.2%     │ 5.0%     │       │
│ └────────┴─────────────────┴──────────┴──────────┘       │
│                                                            │
│ Recommendations:                                           │
│ 1. Consider selling one fund to reduce overlap            │
│ 2. If keeping both, reduce allocation to avoid            │
│    overconcentration                                       │
│ 3. Explore complementary funds with <25% overlap          │
│                                                            │
│ [View Full Report] [Find Alternatives] [Dismiss]          │
└──────────────────────────────────────────────────────────┘
```

### MER Comparison Tool

```
┌──────────────────────────────────────────────────────────┐
│ MER Comparison & Fee Impact (?)                 [Close X] │
├──────────────────────────────────────────────────────────┤
│ Your Fund: XYZ Balanced Growth (Retail)                  │
│ Current MER: 2.50%                                         │
│ Your Net Worth: $85,000                                   │
│                                                            │
│ Available Tiers for Same Fund:                            │
│ ┌────────────┬──────┬────────────┬─────────────────┐    │
│ │ Tier       │ MER  │ Minimum NW │ Eligible? (?)   │    │
│ ├────────────┼──────┼────────────┼─────────────────┤    │
│ │ Retail     │ 2.50%│ $0         │ ✓ Current       │    │
│ │ Preferred  │ 2.00%│ $100,000   │ ⚠ Close! ($15K) │    │
│ │ Premium    │ 1.50%│ $500,000   │ ✗ Not eligible  │    │
│ │ Institutional│1.25%│ $1,000,000 │ ✗ Not eligible  │    │
│ └────────────┴──────┴────────────┴─────────────────┘    │
│                                                            │
│ Fee Impact Over Time ($100,000 Investment @ 6% Return):   │
│ ┌────────────────────────────────────────────────────┐   │
│ │ Period   │ 2.50% MER │ 2.00% MER │ Savings (?)    │   │
│ ├──────────┼───────────┼───────────┼────────────────┤   │
│ │ 10 years │ $130,482  │ $134,832  │ $4,350 (3.3%)  │   │
│ │ 25 years │ $184,202  │ $198,669  │ $14,467 (7.9%) │   │
│ └────────────────────────────────────────────────────┘   │
│                                                            │
│ Tip: Family Aggregation (?)                                │
│ Combine household net worth to qualify for lower tiers.   │
│ Your household net worth: $85K + spouse's assets          │
│                                                            │
│ [Calculate Family NW] [Find Lower-Cost Alternatives]      │
└──────────────────────────────────────────────────────────┘
```

---

## Responsive Design

### Breakpoints
- **Mobile**: < 768px
- **Tablet**: 768px - 1024px
- **Desktop**: > 1024px

### Mobile Adaptations

#### Dashboard (Mobile)
```
┌────────────────────────────┐
│ ☰  WealthSystem    🔔  👤 │
├────────────────────────────┤
│ Portfolio: $125,430.50     │
│ +2.3% Today                │
│                             │
│ [WatchList ▾]               │
│ ┌────────────────────────┐ │
│ │ AAPL  $185.50  +1.2%   │ │
│ │ RSI: 68  🟡  [ℹ]      │ │
│ ├────────────────────────┤ │
│ │ GOOGL $140.20  -0.5%   │ │
│ │ RSI: 45  🟢  [ℹ]      │ │
│ └────────────────────────┘ │
│                             │
│ [Strategies ▾]              │
│ [Funds ▾]                   │
│ [Documentation ▾]           │
└────────────────────────────┘
```

#### Help Icon Behavior (Mobile)
- Tap help icon (?) to open modal (not tooltip)
- Swipe down to dismiss modal
- "Learn More" button navigates to full docs page

---

## Accessibility

### WCAG 2.1 Level AA Compliance

#### Keyboard Navigation
- All interactive elements accessible via Tab
- Tooltips open on Space/Enter when focused
- Escape closes modals/tooltips
- Arrow keys navigate between indicators

#### Screen Readers
- ARIA labels for all indicators
- Role="tooltip" for tooltip elements
- Live regions for price updates
- Alt text for all icons

#### Color Contrast
- Minimum 4.5:1 for normal text
- Minimum 3:1 for large text
- Don't rely on color alone (use icons + text)

#### Responsive Text
- Base font size: 16px (1rem)
- Scalable with browser zoom
- No fixed pixel sizes for critical content

---

## Implementation Plan

### Phase 1: Core UI Framework (Weeks 1-2)
- [ ] Set up React + TypeScript project
- [ ] Choose UI component library (Material-UI recommended)
- [ ] Create base layout components (Nav, SideBar, Dashboard)
- [ ] Implement responsive breakpoints
- [ ] Set up state management (Redux Toolkit)

### Phase 2: Tooltip System (Weeks 3-4)
- [ ] Create Tooltip component with variants (quick, detailed, inline)
- [ ] Build HelpModal component for long-form docs
- [ ] Implement tooltip positioning logic (auto-flip)
- [ ] Add keyboard navigation support
- [ ] Create TooltipProvider context for global config

### Phase 3: Indicator Panel (Weeks 5-6)
- [ ] Create IndicatorList component with categories
- [ ] Build IndicatorCard with expand/collapse
- [ ] Connect to TechnicalIndicatorService API
- [ ] Implement caching for indicator results
- [ ] Add "Add Indicator" modal with search

### Phase 4: Strategy Selector (Week 7)
- [ ] Create StrategyLibrary component
- [ ] Build StrategyCard with details modal
- [ ] Implement strategy filtering and search
- [ ] Add strategy configuration UI
- [ ] Connect to backend strategy services

### Phase 5: Pattern Recognition (Week 8)
- [ ] Create PatternDetectionPanel component
- [ ] Build PatternCard with chart integration
- [ ] Integrate with CandlestickPatternCalculator API
- [ ] Add pattern filtering (bullish/bearish, reliability)
- [ ] Implement pattern alerts

### Phase 6: Fund Analysis (Weeks 9-10)
- [ ] Create FundAnalysis components (composition, overlap, MER)
- [ ] Build FundSelector with autocomplete
- [ ] Implement overlap visualization (Venn diagram or bar chart)
- [ ] Add MER comparison calculator
- [ ] Create fund recommendation engine

### Phase 7: Documentation Viewer (Weeks 11-12)
- [ ] Create Documentation component with markdown rendering
- [ ] Build search functionality (Algolia or local search)
- [ ] Implement category navigation
- [ ] Add related topics/cross-references
- [ ] Create printable documentation format

### Phase 8: Testing & Refinement (Weeks 13-14)
- [ ] Unit tests for all components (Jest + React Testing Library)
- [ ] E2E tests for critical flows (Cypress or Playwright)
- [ ] Accessibility audit (axe-core)
- [ ] Performance optimization (React.memo, lazy loading)
- [ ] User acceptance testing

### Phase 9: Deployment (Week 15)
- [ ] Set up CI/CD pipeline (GitHub Actions)
- [ ] Configure production build (optimize bundle size)
- [ ] Deploy to hosting (Vercel, Netlify, or AWS)
- [ ] Set up monitoring (Sentry for errors, Analytics)
- [ ] Create user onboarding tutorial

---

## Technical Requirements

### API Endpoints Needed

```typescript
// Indicators
GET /api/indicators/list                    // All available indicators
GET /api/indicators/calculate               // Calculate indicator values
POST /api/indicators/batch                  // Batch calculation

// Strategies
GET /api/strategies/list                    // All strategies
GET /api/strategies/{id}                    // Strategy details
POST /api/strategies/{id}/backtest          // Run backtest
POST /api/strategies/{id}/activate          // Activate strategy

// Patterns
GET /api/patterns/detect                    // Detect patterns in data
GET /api/patterns/list                      // All pattern definitions
GET /api/patterns/{id}                      // Pattern details

// Funds
GET /api/funds/{id}/composition             // Holdings, sectors
GET /api/funds/overlap                      // Compare two funds
GET /api/funds/mer-comparison               // MER tiers
GET /api/funds/eligibility                  // Check eligibility

// Documentation
GET /api/docs/search                        // Full-text search
GET /api/docs/article/{id}                  // Get article content
GET /api/docs/related/{id}                  // Related articles

// Real-time (WebSocket)
WS /ws/prices                               // Real-time price updates
WS /ws/indicators                           // Real-time indicator updates
```

### Data Models (TypeScript)

```typescript
interface Indicator {
  id: string;
  name: string;
  category: 'momentum' | 'trend' | 'volatility' | 'volume';
  description: string;
  formula?: string;
  parameters: IndicatorParameter[];
  interpretation: string;
  relatedIndicators: string[];
}

interface IndicatorParameter {
  name: string;
  type: 'number' | 'string' | 'enum';
  default: any;
  min?: number;
  max?: number;
  options?: string[];
  description: string;
}

interface IndicatorResult {
  indicatorId: string;
  values: number[];
  timestamps: Date[];
  signal?: 'buy' | 'sell' | 'hold';
  strength?: number; // -100 to 100
  interpretation: string;
}

interface Strategy {
  id: string;
  name: string;
  description: string;
  riskLevel: 'low' | 'medium' | 'high';
  timeframe: 'short' | 'medium' | 'long';
  rules: StrategyRule[];
  performance: StrategyPerformance;
}

interface Pattern {
  id: string;
  name: string;
  type: 'bullish' | 'bearish' | 'continuation' | 'indecision';
  reliability: 'high' | 'medium' | 'low';
  strength: number; // -100 to 100
  interpretation: string;
  target?: number;
  stopLoss?: number;
}
```

---

## Next Steps

1. **User Feedback**: Share mockups with target users for validation
2. **API Design**: Finalize API contracts with backend team
3. **Prototype**: Build interactive prototype (Figma or React)
4. **Iterate**: Refine based on user testing
5. **Implement**: Follow phased implementation plan

---

**Document Owner**: Frontend Team  
**Approved By**: [Pending]  
**Last Review**: December 3, 2025  
**Next Review**: January 15, 2026
