# CSV Operations Refactor - Requirements Update

## New Requirements
- All CSV file operations in production code must use `CsvParser` or `CsvHandler`.
- All CSV operations must be covered by unit tests (see `tests/Unit/UserPortfolioManagerTest.php`).
- All public methods in CSV-related classes must have PHPDoc blocks.
- All major classes must have UML diagrams in documentation.
- Logging for CSV operations must be written to a log file for diagnostics.

## Implementation
- `UserPortfolioManager.php` refactored to use `CsvParser` for all CSV read/write.
- Logging for portfolio CSV operations is in `logs/user_portfolio_manager.log`.
- Unit test created: `tests/Unit/UserPortfolioManagerTest.php`.
- UML and PHPDoc blocks added to `UserPortfolioManager.php`.

## See Also
- `ARCHITECTURE_IMPLEMENTATION.md` for architecture and UML.
- `README.md` for project overview.

---
Last updated: 2025-10-08
# Trading System Requirements Documentation

## Overview
This document outlines the comprehensive requirements for the ChatGPT Micro-Cap Trading Experiment system, including technical analysis strategies, risk management, data integration, and user interface capabilities.

## System Architecture

### Core Components
1. **SOLID Finance Package** (`src/Ksfraser/Finance/`)
   - MVC architecture with dependency injection
   - Multiple data source integration
   - LLM-powered financial analysis
   - Clean separation of concerns

2. **Trading Strategies Engine** (`src/Ksfraser/Finance/2000/strategies/`)
   - Rule-based inference engine
   - Multiple trading methodologies
   - Configurable parameters
   - Backtesting capabilities

3. **Data Management**
   - Multiple market data sources
   - Historical data storage
   - Real-time price feeds
   - Results persistence

## Trading Strategies
### 6. Advanced Analytics, Risk, and Indicator Accuracy (MarketFactorsService)
**File**: `src/Ksfraser/Finance/MarketFactors/Services/MarketFactorsService.php`

**Description**: Provides advanced analytics, risk assessment, and indicator accuracy tracking for all market factors. Centralizes logic for factor management, correlation analysis, weighted scoring, and recommendation generation.

**Key Features**:
- **Market Factor Management**: Add, update, retrieve, and filter market factors by type, sector, or index.
- **Correlation Analysis**: Analyze and track correlations between market factors, including correlation matrix generation and management.
- **Technical Indicator Accuracy Tracking**: Track predictions made by technical indicators, update and calculate their accuracy, and maintain detailed performance metrics for each indicator.
- **Risk Level Calculation**: Calculate risk levels for market factors and the overall portfolio using volatility, drawdown, and other risk metrics.
- **Weighted Scoring System**: Compute weighted scores for market factors based on configurable criteria (e.g., performance, accuracy, risk, sentiment).
- **Recommendation Engine**: Generate buy/hold/sell recommendations for market factors using analytics, scoring, and risk assessment.
- **Confidence Calculation**: Quantify the confidence level of recommendations based on historical accuracy and current analytics.
- **Market Sentiment Analysis**: Aggregate and analyze sentiment across all tracked factors.
- **Backtesting Support**: Provide methods for historical performance analysis and indicator validation.
- **Data Import/Export**: Support for importing/exporting factor data and analytics for reproducibility and auditability.

**Required Functions**:
- `trackIndicatorPrediction`, `updateIndicatorAccuracy`, `calculatePredictionAccuracy`, `updateIndicatorPerformance`, `getIndicatorAccuracy`, `getIndicatorPerformanceScore`, `getAllIndicatorPerformance`, `calculateWeightedScore`, `generateRecommendation`, `calculateConfidence`, `calculateRiskLevel`, `getMarketSummary`, `getSectorSummary`, `getIndexSummary`, `getForexSummary`, `getEconomicsSummary`, `trackCorrelation`, `getCorrelationMatrix`, `calculateMarketSentiment`, `exportData`, `importData`.

**Testing Requirements**:
- All analytics, risk, and indicator accuracy functions must have unit tests covering normal, edge, and error cases.
- Recommendation and scoring logic must be validated against historical data and simulated scenarios.

**Documentation Requirements**:
- All advanced analytics, risk, and indicator accuracy features must be documented in the README and technical documentation, with clear mapping to code and requirements.

### 1. Turtle Trading System
**File**: `turtle.php`

**Description**: Implementation of the famous Turtle Trading System with dual breakout strategies.

**Key Features**:
- **System 1**: 20-day breakout entry with 10-day exit
- **System 2**: 55-day breakout entry with 20-day exit
- Position sizing based on account equity and volatility
- Stop-loss management with ATR-based stops

**Entry Signals**:
- `enter_1()`: 20-day price breakout (highest high in 20 days)
- `enter_2()`: 55-day price breakout (highest high in 55 days)

**Exit Signals**:
- `exit_1()`: 10-day price breakdown (lowest low in 10 days)
- `exit_2()`: 20-day price breakdown (lowest low in 20 days)

**Risk Management**:
- Maximum 2% risk per trade
- Unit-based position sizing
- Pyramiding allowed (up to 4 units per position)

### 2. Support and Resistance Trading
**File**: `buyLeadingStocksAtSupport.php`

**Description**: Strategy focused on buying leading stocks at support levels using technical analysis.

**Key Features**:
- Support and resistance level identification
- 50-day moving average analysis
- Trend channel analysis
- Leading stock identification

**Entry Criteria**:
- Stock approaching established support level
- Above 50-day moving average
- Leading stock characteristics
- Volume confirmation

### 3. Technical Analysis Strategies

#### Moving Average Crossover
**File**: `macrossover.php`

**Features**:
- Multiple timeframe analysis
- Golden cross/death cross signals
- Trend confirmation
- Volume validation

#### Four Week Rule
**File**: `fourweekrule.php`

**Features**:
- 4-week high/low breakout system
- Simple trend-following approach
- Clear entry/exit rules

### 4. Money Management
**File**: `moneymanagement.php`

**Features**:
- Position sizing algorithms
- Risk percentage calculations
- Portfolio allocation management
- Stop-loss positioning

### 5. Trading Constants
**File**: `strategiesConstants.php`

**Defined Actions**:
- `BUY (10)`: Long position entry
- `SELL (20)`: Long position exit
- `HOLD (30)`: Maintain current position
- `SHORT (40)`: Short position entry
- `COVER (50)`: Short position exit

## Data Requirements

### Market Data Sources
1. **Yahoo Finance** (Primary - Free)
   - Real-time and historical price data
   - Volume and fundamental data
   - No API key required

2. **Alpha Vantage** (Secondary - API Key Required)
   - Enhanced fundamental data
   - Technical indicators
   - News sentiment data

3. **Future Extensions**
   - Bloomberg API
   - IEX Cloud
   - Quandl/NASDAQ Data Link

### Required Data Types
- **OHLCV Data**: Open, High, Low, Close, Volume
- **Fundamental Data**: P/E ratios, earnings, revenue
- **Technical Indicators**: RSI, MACD, Bollinger Bands
- **News Data**: Sentiment analysis, event data
- **Market Data**: Sector performance, market indices

## Technical Analysis Integration

### TA-Lib Requirements
The system requires TA-Lib (Technical Analysis Library) for:

- **Trend Indicators**: SMA, EMA, MACD, ADX
- **Momentum Indicators**: RSI, Stochastic, Williams %R
- **Volatility Indicators**: Bollinger Bands, ATR
- **Volume Indicators**: OBV, Chaikin Money Flow
- **Pattern Recognition**: Candlestick patterns

### Custom Indicators
- **Turtle N-value**: 20-day Average True Range
- **Breakout Detection**: Price breakout algorithms
- **Support/Resistance**: Dynamic level calculation
- **Trend Analysis**: Multi-timeframe trend detection

## LLM Integration

### OpenAI ChatGPT Integration
**Purpose**: Enhance trading decisions with AI analysis

**Capabilities**:
- Market sentiment analysis
- News interpretation
- Risk assessment
- Strategy optimization suggestions

**Implementation**:
- Real-time market commentary
- Position recommendations
- Risk warnings
- Market regime identification

### Future LLM Integrations
- **Anthropic Claude**: Alternative analysis perspective
- **Google Bard**: Additional market insights
- **Local Models**: Privacy-focused analysis

## Database Schema Requirements

### Core Tables

#### 1. Trading Strategies
```sql
CREATE TABLE trading_strategies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    strategy_type ENUM('trend_following', 'mean_reversion', 'breakout', 'support_resistance'),
    parameters JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### 2. Strategy Executions
```sql
CREATE TABLE strategy_executions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    strategy_id INT,
    symbol VARCHAR(20),
    execution_date DATETIME,
    action ENUM('BUY', 'SELL', 'SHORT', 'COVER', 'HOLD'),
    price DECIMAL(10,4),
    quantity INT,
    reasoning TEXT,
    confidence_score DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (strategy_id) REFERENCES trading_strategies(id)
);
```

#### 3. Backtesting Results
```sql
CREATE TABLE backtesting_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    strategy_id INT,
    symbol VARCHAR(20),
    start_date DATE,
    end_date DATE,
    initial_capital DECIMAL(12,2),
    final_capital DECIMAL(12,2),
    total_return DECIMAL(8,4),
    sharpe_ratio DECIMAL(6,4),
    max_drawdown DECIMAL(6,4),
    win_rate DECIMAL(5,4),
    total_trades INT,
    parameters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (strategy_id) REFERENCES trading_strategies(id)
);
```

#### 4. Market Data
```sql
CREATE TABLE market_data (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20),
    date DATE,
    open_price DECIMAL(10,4),
    high_price DECIMAL(10,4),
    low_price DECIMAL(10,4),
    close_price DECIMAL(10,4),
    volume BIGINT,
    adjusted_close DECIMAL(10,4),
    source VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, date)
);
```

#### 5. Technical Indicators
```sql
CREATE TABLE technical_indicators (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20),
    date DATE,
    indicator_name VARCHAR(50),
    indicator_value DECIMAL(12,6),
    timeframe VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol_date_indicator (symbol, date, indicator_name)
);
```

#### 6. LLM Analysis
```sql
CREATE TABLE llm_analysis (
    id INT PRIMARY KEY AUTO_INCREMENT,
    symbol VARCHAR(20),
    analysis_date DATETIME,
    llm_provider VARCHAR(50),
    prompt TEXT,
    response TEXT,
    sentiment_score DECIMAL(3,2),
    confidence_level DECIMAL(3,2),
    recommendations JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Web UI Requirements

### 1. Dashboard Page
**Route**: `/finance/dashboard`

**Features**:
- Portfolio overview
- Active strategies status
- Recent trade signals
- Performance metrics
- Market overview

### 2. Strategy Management
**Route**: `/finance/strategies`

**Features**:
- List all available strategies
- Configure strategy parameters
- Enable/disable strategies
- View strategy performance
- Create custom strategies

### 3. Strategy Execution
**Route**: `/finance/strategies/{id}/execute`

**Features**:
- Manual strategy execution
- Parameter override
- Real-time results
- Execution history
- Risk warnings

### 4. Backtesting Interface
**Route**: `/finance/backtest`

**Features**:
- Strategy selection
- Date range picker
- Symbol selection
- Parameter configuration
- Results visualization
- Performance comparison

### 5. Market Data Viewer
**Route**: `/finance/market-data`

**Features**:
- Price charts (candlestick, line)
- Technical indicator overlays
- Volume analysis
- Multiple timeframes
- Export capabilities

### 6. LLM Analysis Center
**Route**: `/finance/ai-analysis`

**Features**:
- Request market analysis
- View AI recommendations
- Sentiment tracking
- Historical AI predictions
- Performance validation

### 7. Risk Management
**Route**: `/finance/risk`

**Features**:
- Position sizing calculator
- Risk metrics dashboard
- Drawdown analysis
- Correlation matrix
- Portfolio optimization

## Integration Requirements

### 1. Authentication Integration
- Utilize existing Symfony session management
- Role-based access control
- Trading permissions system

### 2. Configuration Management
- Integrate with existing `DatabaseConfig.php`
- Use `ApiConfig.php` for external APIs
- Environment-specific settings

### 3. Error Handling
- Comprehensive logging system
- User-friendly error messages
- Fallback mechanisms for data sources
- Rate limiting compliance

### 4. Performance Requirements
- Page load times < 2 seconds
- Real-time data updates
- Efficient database queries
- Caching strategies

## Security Requirements

### 1. Data Protection
- API key encryption
- Secure database connections
- Input validation and sanitization
- SQL injection prevention

### 2. Access Control
- User authentication required
- Role-based permissions
- Strategy execution authorization
- Audit trail logging

### 3. Rate Limiting
- API request throttling
- User action limitations
- Resource usage monitoring

## Testing Requirements

### 1. Unit Testing
- Strategy logic validation
- Data source connectivity
- Calculation accuracy
- Error handling

### 2. Integration Testing
- End-to-end workflow testing
- API integration validation
- Database transaction testing
- UI functionality testing

### 3. Performance Testing
- Load testing for concurrent users
- Database performance optimization
- Memory usage monitoring
- Response time validation

## Deployment Requirements

### 1. Environment Setup
- PHP 8.1+ with required extensions
- MySQL 8.0+ database
- Python 3.9+ for analysis scripts
- TA-Lib compilation and installation

### 2. Dependencies
- Composer package management
- Python pip requirements
- Database migrations
- Configuration file setup

### 3. Monitoring
- Application performance monitoring
- Error tracking and alerting
- Resource usage monitoring
- Trade execution monitoring

## Future Enhancements

### 1. Advanced Features
- Machine learning integration
- Options trading strategies
- Cryptocurrency support
- Real-time alerts and notifications

### 2. Mobile Integration
- Responsive web design
- Mobile app development
- Push notifications
- Offline capabilities

### 3. API Development
- RESTful API for external integration
- Webhook support
- Third-party platform integration
- Data export capabilities

## Conclusion

This comprehensive trading system combines proven technical analysis strategies with modern software architecture principles. The modular design allows for easy extension and maintenance while providing robust functionality for both manual and automated trading operations.

The integration of multiple data sources, LLM analysis, and comprehensive backtesting capabilities creates a powerful platform for micro-cap stock trading experimentation and analysis.
