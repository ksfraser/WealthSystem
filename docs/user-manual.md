# Stock Analysis System - User Manual

## Table of Contents
1. [Getting Started](#getting-started)
2. [Stock Search Interface](#stock-search-interface)
3. [Main Analysis Dashboard](#main-analysis-dashboard)
4. [AI-Powered Analysis](#ai-powered-analysis)
5. [User Interaction Workflow](#user-interaction-workflow)
6. [Understanding Analysis Results](#understanding-analysis-results)
7. [Advanced Features](#advanced-features)
8. [Troubleshooting](#troubleshooting)

---

## Getting Started

The Stock Analysis System provides comprehensive AI-powered stock analysis with individual database architecture for each stock. Each stock symbol (e.g., AAPL, MSFT, TSLA) gets its own set of database tables for prices, fundamentals, technical indicators, news, and AI analysis results.

### System Requirements
- Web browser (Chrome, Firefox, Safari, Edge)
- Active internet connection for real-time data
- User account with appropriate permissions

### First Time Setup
1. Log into the system at: `https://yoursite.com/web_ui/login.php`
2. Navigate to the stock search page
3. Begin by searching for your first stock symbol

---

## Stock Search Interface

### Accessing the Search Page
**URL:** `https://yoursite.com/web_ui/stock_search.php`

### Search Methods

#### 1. **Symbol Search**
```
┌─────────────────────────────────────────────────────┐
│ Search by symbol, company name, sector, or industry │
│ [Enter stock symbol (e.g., AAPL, MSFT, TSLA)    ] │
│ [Search Button]                                     │
└─────────────────────────────────────────────────────┘
```

**How to use:**
- Type any stock symbol (AAPL), company name (Apple), or sector (Technology)
- Real-time suggestions appear as you type
- Click on suggestions or press Enter to search

#### 2. **Category Browsing**
Click category buttons for quick access:
- **Technology** - Tech stocks like AAPL, MSFT, GOOGL
- **Healthcare** - Pharmaceutical and medical companies
- **Financial** - Banks, insurance, investment firms
- **Energy** - Oil, gas, and renewable energy companies
- **Industrial** - Manufacturing and industrial companies
- **Consumer** - Retail and consumer goods companies

#### 3. **Add New Stocks**
```
┌─────────────────────────────────────────┐
│ Add new symbol: [TSLA        ] [Add]    │
└─────────────────────────────────────────┘
```

**Process:**
1. Enter any valid stock symbol
2. Click "Add Stock" button
3. System validates symbol and creates individual database tables
4. Redirects to analysis dashboard

### Search Results Display
```
┌─────────────────────────────────────────────────────────┐
│ Symbol | Company Name    | Sector     | Market Cap | Actions │
│ AAPL   | Apple Inc      | Technology | $2.8T      | [Analyze] │
│ MSFT   | Microsoft Corp | Technology | $2.4T      | [Analyze] │
│ TSLA   | Tesla Inc      | Automotive | $800B      | [Analyze] │
└─────────────────────────────────────────────────────────┘
```

---

## Main Analysis Dashboard

### Accessing Stock Analysis
**URL:** `https://yoursite.com/web_ui/stock_analysis.php?symbol=AAPL`

When you click "Analyze" from search results or enter a symbol directly.

### Dashboard Layout

#### **Header Section - Current Price & Status**
```
┌─────────────────────────────────────────────────────────┐
│ AAPL                                    $150.25         │
│                                        +2.35 (+1.58%)   │
│ Last updated: Sep 27, 2025 2:30 PM    [MARKET OPEN]    │
│                                                         │
│ [Update Data] [AI Analysis] [Search Other]             │
└─────────────────────────────────────────────────────────┘
```

**Elements:**
- **Stock Symbol**: Large display of ticker symbol
- **Current Price**: Real-time price with change indicators
- **Change Amount**: Dollar and percentage change (green=up, red=down)
- **Last Updated**: Timestamp of most recent data
- **Market State**: OPEN, CLOSED, PRE_MARKET, AFTER_HOURS

#### **Left Column - Price & News Analysis**

**Price Analysis Card:**
```
┌─────────────────────────────────────────┐
│ 📊 Price Analysis                       │
├─────────────────────────────────────────┤
│ Current Price: $150.25                  │
│ Volume: 45,678,123                      │
│ Market Cap: $2.8T                       │
│ P/E Ratio: 28.5                         │
│                                         │
│ 30-Day Price Trend:                     │
│ [Interactive Chart]                     │
└─────────────────────────────────────────┘
```

**Recent News & Sentiment:**
```
┌─────────────────────────────────────────┐
│ 📰 Recent News & Sentiment              │
│                            [Analyze]    │
├─────────────────────────────────────────┤
│ ▲ 85% Apple Reports Strong Q3 Results   │
│       Reuters • 2 hours ago             │
│                                         │
│ ● 60% New iPhone Features Announced     │
│       MarketWatch • 5 hours ago         │
│                                         │
│ ▲ 90% Analyst Upgrades Price Target     │
│       Bloomberg • 1 day ago             │
└─────────────────────────────────────────┘
```

**Sentiment Indicators:**
- **▲ Green** = Positive sentiment (bullish news)
- **▼ Red** = Negative sentiment (bearish news)  
- **● Gray** = Neutral sentiment (informational)
- **Percentage** = AI confidence score (0-100%)

#### **Right Column - AI Analysis Results**

```
┌─────────────────────────────────┐
│ 🤖 AI ANALYSIS                  │
├─────────────────────────────────┤
│                                 │
│         [ BUY ]                 │
│      Confidence: High           │
│                                 │
│ Overall Score: 78/100           │
│ ████████████░░░░░ 78%           │
│                                 │
│ Technical: 75 | Fundamental: 82 │
│ Sentiment: 68                   │
│                                 │
│ Target Price: $165.00           │
│ Upside Potential: +9.8%        │
│ Risk Level: MEDIUM              │
│                                 │
│ AI Insight:                     │
│ Based on analysis of provided   │
│ data, this stock shows strong   │
│ fundamentals with solid revenue │
│ growth and improving margins... │
│ [read more]                     │
│                                 │
│ Last updated: Sep 27, 10:15 AM │
└─────────────────────────────────┘
```

---

## AI-Powered Analysis

### How AI Analysis Works

The system uses advanced Large Language Models (LLM) including OpenAI GPT-4 and Anthropic Claude to analyze:

1. **Current & Historical Prices** - Price trends, volatility, support/resistance
2. **Fundamental Data** - P/E ratios, growth rates, financial health
3. **News Sentiment** - Recent news articles analyzed for market impact
4. **Technical Indicators** - Moving averages, RSI, MACD patterns

### AI Analysis Components

#### **Investment Recommendation**
- **STRONG_BUY** 🟢 - High conviction buy recommendation
- **BUY** 🟢 - Positive outlook, recommended purchase
- **HOLD** 🟡 - Maintain current position, wait and see
- **SELL** 🔴 - Negative outlook, consider selling
- **STRONG_SELL** 🔴 - High conviction sell recommendation

#### **Scoring System (0-100)**
- **Overall Score**: Weighted average of all factors
- **Fundamental Score**: Financial health, valuation metrics
- **Technical Score**: Chart patterns, momentum indicators
- **Sentiment Score**: News analysis, market mood

#### **Confidence Levels**
- **Very High (80-100%)**: Strong conviction in analysis
- **High (60-79%)**: Good confidence in recommendation  
- **Medium (40-59%)**: Moderate confidence, watch closely
- **Low (20-39%)**: Limited conviction, high uncertainty
- **Very Low (0-19%)**: Poor data quality or mixed signals

### Target Price Calculation
The AI generates target prices based on:
- **Fundamental Valuation**: P/E ratios, growth projections
- **Technical Analysis**: Support/resistance levels, trends
- **Peer Comparison**: Industry multiples and benchmarks
- **Market Sentiment**: News impact and analyst consensus

---

## User Interaction Workflow

### Step-by-Step Process

#### **Step 1: Search for Stock**
1. Go to stock search page
2. Enter symbol (e.g., "AAPL") or browse categories
3. Click "Analyze" button
4. System creates individual database tables for the stock

#### **Step 2: Initial Data Load**
```
System automatically:
├─ Creates AAPL_prices table
├─ Creates AAPL_fundamentals table  
├─ Creates AAPL_technical table
├─ Creates AAPL_news table
├─ Creates AAPL_analysis table
└─ Fetches basic price data
```

#### **Step 3: Generate AI Analysis**
1. Click "AI Analysis" button
2. Watch real-time status updates:
   - "Updating stock data..." ⏳
   - "Analyzing news sentiment..." 🧠
   - "Generating comprehensive AI analysis..." 🤖
   - "Analysis completed successfully!" ✅

#### **Step 4: View Results**
The dashboard updates with:
- Real-time price information
- AI recommendation and scores
- News sentiment analysis
- Target price and risk assessment

### Real-Time Data Flow
```
User Action → API Call → Python Script → Yahoo Finance
     ↓
LLM Service → OpenAI/Claude → Sentiment Analysis
     ↓
Individual Tables → AAPL_* tables updated
     ↓  
Dashboard Refresh → Live results display
```

### Available Actions

| **Button** | **What It Does** | **Result** |
|---|---|---|
| **Update Data** | Fetches latest price/volume from Yahoo Finance | Current prices, market cap, volume |
| **AI Analysis** | Runs complete LLM analysis of all data | Buy/Sell recommendation, target price |
| **Analyze Sentiment** | AI processes recent news headlines | Sentiment scores on news articles |
| **Search Other** | Navigate to stock search page | Browse/search different stocks |

---

## Understanding Analysis Results

### Sample Complete Analysis Display

```
═══════════════════════════════════════
📊 APPLE INC (AAPL) - AI ANALYSIS  
═══════════════════════════════════════

💰 PRICE INFORMATION
├─ Current Price: $150.25 (+$2.35, +1.58%)
├─ Volume: 45,678,123 shares  
├─ Market Cap: $2.8 Trillion
├─ P/E Ratio: 28.5
└─ Last Updated: Sep 27, 2025 2:30 PM

🎯 AI RECOMMENDATION  
├─ Overall: BUY  
├─ Confidence: HIGH (82%)
├─ Target Price: $165.00 
├─ Upside Potential: +9.8%
└─ Risk Level: MEDIUM

📊 DETAILED SCORES
├─ Overall Score: 78/100
├─ Fundamental Analysis: 82/100  
├─ Technical Analysis: 75/100
└─ Market Sentiment: 68/100

🤖 AI REASONING
"Based on comprehensive analysis of Apple's 
recent performance, the company demonstrates 
strong fundamental metrics with consistent 
revenue growth of 8.2% year-over-year and 
expanding profit margins. The recent product 
launches have received positive market 
reception, with iPhone 15 sales exceeding 
initial projections.

Technical indicators suggest bullish momentum 
with the stock trading above key moving 
averages and RSI indicating room for further 
upside. News sentiment analysis reveals 
predominantly positive coverage focusing on 
innovation pipeline and market expansion."

📰 NEWS SENTIMENT BREAKDOWN  
├─ ▲ "Apple Reports Strong Q3 Results" 
│   └─ Sentiment: 85% Positive | Source: Reuters
├─ ● "New iPhone Features Announced"
│   └─ Sentiment: 60% Neutral | Source: MarketWatch  
├─ ▲ "Analyst Upgrades Price Target to $170"
│   └─ Sentiment: 90% Positive | Source: Bloomberg
└─ Overall News Sentiment: 78% Positive

🎯 INVESTMENT THESIS
Bull Case:
• Strong brand loyalty and ecosystem lock-in
• Expanding services revenue with higher margins  
• Innovation pipeline in AR/VR and automotive
• Consistent capital returns through dividends/buybacks

Bear Case:  
• Smartphone market saturation in key regions
• Increasing competition from Android manufacturers
• Regulatory pressure on App Store policies
• Supply chain dependencies and geopolitical risks

Key Risks to Monitor:
• China market exposure (~20% of revenue)
• Interest rate sensitivity for valuation multiples
• Slower innovation cycle compared to historical pace

📅 ANALYSIS METADATA
├─ Generated: Sep 27, 2025 2:30 PM
├─ AI Model: GPT-4 Turbo  
├─ Confidence Score: 82%
├─ Data Sources: Yahoo Finance, Reuters, Bloomberg
└─ Next Update: Auto-refresh in 24 hours
```

### Key Metrics Explained

#### **Financial Health Score (0-100)**
Calculated based on:
- **Profitability (30%)**: ROE > 15% = excellent, 10-15% = good  
- **Liquidity (20%)**: Current ratio > 2.0 = strong, 1.5-2.0 = adequate
- **Leverage (20%)**: Debt/Equity < 0.3 = low risk, 0.3-0.6 = moderate
- **Growth (20%)**: Revenue growth > 20% = excellent, 10-20% = good
- **Margins (10%)**: Net margin > 15% = excellent, 8-15% = good

#### **Valuation Categories**
- **Undervalued**: P/E < 15, potential bargain
- **Fair Value**: P/E 15-25, reasonably priced  
- **Expensive**: P/E 25-40, premium valuation
- **Overvalued**: P/E > 40, potentially risky

#### **Risk Level Assessment**
- **LOW**: Blue-chip stocks, stable earnings, low volatility
- **MEDIUM**: Growth stocks, moderate volatility, established companies  
- **HIGH**: Small-cap stocks, volatile earnings, competitive pressure
- **VERY HIGH**: Speculative stocks, unproven business models, high debt

---

## Advanced Features

### Auto-Refresh Functionality
- **Price Updates**: Every 5 minutes during market hours
- **News Monitoring**: Continuous scanning for new articles  
- **Analysis Updates**: Daily refresh of AI recommendations
- **Alert System**: Notifications for significant price/news changes

### Individual Stock Database Architecture

Each stock symbol gets its own complete set of tables:

```sql
-- For AAPL stock:
AAPL_prices          -- Historical and real-time price data
AAPL_fundamentals    -- P/E ratios, growth rates, financial metrics  
AAPL_technical       -- RSI, MACD, moving averages, indicators
AAPL_news           -- News articles with AI sentiment analysis
AAPL_analysis       -- Complete LLM-generated investment analysis
AAPL_alerts         -- Price alerts and notification settings

-- For MSFT stock:  
MSFT_prices          -- Separate price history for Microsoft
MSFT_fundamentals    -- Microsoft-specific financial data
... (same structure for each stock)
```

**Benefits of Individual Tables:**
- **Scalability**: Each stock's data is isolated and optimized
- **Performance**: Faster queries on smaller, focused datasets  
- **Flexibility**: Different analysis parameters per stock
- **Data Integrity**: No cross-contamination between stocks

### Batch Analysis Features
- **Portfolio Analysis**: Analyze multiple stocks simultaneously
- **Sector Comparison**: Compare stocks within same industry
- **Bulk Updates**: Refresh data for entire watchlist
- **Export Functions**: Download analysis reports in PDF/Excel

---

## Troubleshooting

### Common Issues and Solutions

#### **"Stock symbol not found"**
**Problem**: Invalid or delisted stock symbol
**Solution**: 
- Verify symbol spelling (use official ticker)
- Check if stock is actively traded
- Try alternative exchanges (e.g., .TO for Toronto)

#### **"Price data not available"**  
**Problem**: Data source connection issues
**Solution**:
- Click "Update Data" to retry
- Check internet connection
- Wait a few minutes and try again
- Contact support if issue persists

#### **"Analysis temporarily unavailable"**
**Problem**: LLM API configuration or quota issues  
**Solution**:
- Try again in a few minutes
- Check if API keys are configured
- Contact administrator for API quota status

#### **Slow loading times**
**Problem**: Large dataset or high server load
**Solution**:
- Use smaller date ranges for historical data
- Clear browser cache and cookies
- Try during off-peak hours
- Check network connection speed

### Error Messages

| **Message** | **Cause** | **Action** |
|---|---|---|
| "Symbol is required" | Empty search field | Enter valid stock symbol |  
| "Authentication required" | Session expired | Log in again |
| "Rate limit exceeded" | Too many API calls | Wait 1 minute and retry |
| "Database connection failed" | Server issues | Contact technical support |
| "Invalid date range" | Incorrect date parameters | Check start/end dates |

### Best Practices

#### **For Optimal Performance:**
- Use specific date ranges rather than "all time"
- Limit bulk operations to 50 stocks or fewer  
- Refresh analysis during off-peak hours
- Clear old cached data periodically

#### **For Accurate Analysis:**
- Ensure latest data before making investment decisions
- Cross-reference AI analysis with other sources
- Monitor risk levels and adjust position sizes
- Set up alerts for significant price movements

#### **Data Reliability:**  
- AI analysis is based on available data quality
- News sentiment reflects headline analysis, not deep research
- Price targets are estimates, not guarantees
- Always consider multiple factors before investing

---

## Support and Contact Information

### Technical Support
- **Email**: support@stockanalysis.com
- **Phone**: 1-800-STOCKS-1  
- **Hours**: Monday-Friday, 9 AM - 6 PM EST

### System Status
- **Status Page**: https://status.stockanalysis.com
- **Maintenance Windows**: Sundays 2-4 AM EST
- **Data Updates**: Market hours + 30 minutes

### Documentation
- **API Documentation**: Available for developers
- **Video Tutorials**: Step-by-step usage guides  
- **FAQ Section**: Common questions and answers
- **Release Notes**: Feature updates and improvements

---

*Last Updated: September 27, 2025*
*Version: 2.0.0*
*© 2025 Stock Analysis System - All Rights Reserved*