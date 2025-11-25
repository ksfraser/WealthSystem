# Stock Analysis Extension

A comprehensive stock analysis and portfolio management system that extends the ChatGPT Micro-Cap Experiment to analyze normal market stocks with MySQL database integration and FrontAccounting support.

## Features

### 🔍 **Comprehensive Stock Analysis**
- **Multi-source data fetching** with Yahoo Finance, Finnhub, and Alpha Vantage APIs
- **Four-dimensional analysis**:
  - Fundamental analysis (40% weight)
  - Technical analysis (30% weight) 
  - Momentum analysis (20% weight)
  - Sentiment analysis (10% weight)
- **Risk assessment** with confidence scoring
- **Target price calculation** based on comprehensive metrics

### 💼 **Portfolio Management**
- **Risk-based position sizing** with sector exposure limits
- **Automated stop-loss and take-profit** level calculation
- **Portfolio rebalancing** recommendations
- **Performance tracking** with real-time P&L
- **Correlation analysis** to avoid over-concentration

### 🗄️ **MySQL Database Integration**
- **Complete data persistence** for all analysis results
- **Historical tracking** of recommendations and performance
- **Portfolio positions** and trade log management
- **Flexible querying** with built-in views for common reports

### 📊 **FrontAccounting Integration**
- **Automated journal entries** for all trades
- **Mark-to-market adjustments** for portfolio valuation
- **Dividend income tracking**
- **Commission expense recording**
- **Balance sheet and P&L reporting**

### 🎯 **Key Capabilities**
- Analyze individual stocks with detailed scoring
- Generate buy/sell/hold recommendations
- Manage existing portfolio positions
- Execute trades with full audit trail
- Daily automated analysis runs
- Risk management and exposure monitoring

## Installation

### 1. Install Dependencies

```bash
cd Stock-Analysis-Extension
pip install -r requirements_extension.txt
```

### 2. Database Setup

1. Install MySQL Server 8.0 or higher
2. Create a database user with appropriate permissions
3. Update database configuration in `config/config.py`

```sql
CREATE DATABASE stock_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'stock_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON stock_analysis.* TO 'stock_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Configuration Setup

1. Copy the template configuration:
```bash
cp config/config_template.py config/config.py
```

2. Update `config/config.py` with your settings:
   - Database credentials
   - API keys (optional but recommended)
   - FrontAccounting settings (if using)
   - Risk management parameters

### 4. API Keys (Optional but Recommended)

Get free API keys from:
- **Finnhub**: https://finnhub.io/ (60 calls/minute free)
- **Alpha Vantage**: https://www.alphavantage.co/ (500 calls/day free)

### 5. FrontAccounting Setup (Optional)

If you want accounting integration:
1. Install FrontAccounting 2.4+
2. Configure the API module
3. Update FrontAccounting settings in config

## Quick Start

### 1. Initialize the System

```python
from main import StockAnalysisApp

# Initialize application
app = StockAnalysisApp('config/config.py')

# Initialize all components
if app.initialize():
    print("System ready!")
```

### 2. Analyze a Stock

```python
# Analyze Apple stock
result = app.analyze_stock('AAPL')
app.print_analysis_report(result)
```

### 3. Get Stock Recommendations

```python
# Get top 10 recommendations
recommendations = app.get_recommendations(10)

for rec in recommendations:
    print(f"{rec['symbol']}: {rec['score']:.1f} - {rec['recommendation']}")
```

### 4. Analyze Your Existing Portfolio

```python
# Analyze your current holdings
my_stocks = ['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA']
portfolio_analysis = app.analyze_existing_portfolio(my_stocks)

print(f"Average Score: {portfolio_analysis['summary']['avg_score']:.1f}")
print(f"Buy Recommendations: {portfolio_analysis['summary']['buy_recommendations']}")
```

### 5. Execute Trades

```python
# Execute a buy order
result = app.execute_trade(
    symbol='AAPL',
    trade_type='BUY',
    quantity=10,
    price=150.00,
    strategy='Analysis Recommendation'
)

print(f"Trade Status: {result['status']}")
```

## Interactive Mode

Run the main application for an interactive experience:

```bash
python main.py
```

This provides a menu-driven interface for:
- Stock analysis
- Getting recommendations  
- Portfolio analysis
- Trade execution
- Portfolio summary
- Daily analysis runs

## Architecture

### Core Components

1. **DatabaseManager** (`modules/database_manager.py`)
   - MySQL database operations
   - Data persistence and retrieval
   - Schema management

2. **StockDataFetcher** (`modules/stock_data_fetcher.py`)
   - Multi-source data collection
   - Robust fallback mechanisms
   - Rate limiting and error handling

3. **StockAnalyzer** (`modules/stock_analyzer.py`)
   - Comprehensive analysis engine
   - Four-dimensional scoring system
   - Risk assessment and confidence calculation

4. **PortfolioManager** (`modules/portfolio_manager.py`)
   - Portfolio operations and management
   - Risk management and position sizing
   - Trade execution and validation

5. **FrontAccountingIntegrator** (`modules/front_accounting.py`)
   - Accounting system integration
   - Automated journal entries
   - Financial reporting

### Database Schema

The system uses a comprehensive MySQL schema with tables for:
- **stock_prices**: Historical price data
- **stock_fundamentals**: Company fundamental data
- **technical_indicators**: Technical analysis indicators
- **analysis_results**: Comprehensive analysis scores
- **portfolios**: Portfolio definitions
- **portfolio_positions**: Current holdings
- **trade_log**: Complete trade history
- **front_accounting_sync**: FA integration tracking

### Analysis Methodology

The analysis engine combines four key dimensions:

1. **Fundamental Analysis (40%)**
   - P/E, P/B, P/S ratios
   - ROE, ROA, profit margins
   - Debt levels and liquidity
   - Growth metrics

2. **Technical Analysis (30%)**
   - Moving averages and trends
   - RSI, MACD indicators
   - Bollinger Bands
   - Volume analysis

3. **Momentum Analysis (20%)**
   - Short, medium, long-term momentum
   - Volatility assessment
   - Relative strength

4. **Sentiment Analysis (10%)**
   - Analyst ratings
   - Market cap considerations
   - Sector sentiment
   - Trading volume patterns

## Risk Management

### Built-in Risk Controls

- **Position Size Limits**: Maximum 5% per position (configurable)
- **Sector Concentration**: Maximum 25% per sector (configurable)
- **Stop Loss**: Automatic 15% stop loss (configurable)
- **Take Profit**: 25% take profit targets (configurable)
- **Correlation Limits**: Maximum 70% correlation between positions
- **Risk Rating System**: LOW/MEDIUM/HIGH/VERY_HIGH classification

### Risk Assessment Factors

- Price volatility (30-day standard deviation)
- Debt-to-equity ratios
- Technical indicator extremes
- Fundamental weakness indicators
- Market cap and liquidity considerations

## Integration with Original System

This extension is designed to **complement** the original micro-cap experiment without interfering:

### Separation of Concerns
- **Original System**: Focuses on micro-cap stocks with existing CSV-based tracking
- **Extension**: Handles normal market stocks with database persistence
- **No Overlap**: Different file structures, databases, and workflows

### Parallel Operation
- Both systems can run simultaneously
- No modifications to original `trading_script.py`
- Separate data storage and logging
- Independent configuration and setup

### Data Compatibility
- Can import existing portfolio data if desired
- FrontAccounting integration tracks both systems separately
- Unified reporting possible through FA system

## Advanced Features

### Daily Automation

```python
# Run daily analysis (can be scheduled)
results = app.run_daily_analysis()
```

This performs:
- Price updates for all positions
- New stock recommendations
- Portfolio rebalancing suggestions
- FrontAccounting sync

### Custom Analysis

```python
# Analyze specific sectors or market caps
analyzer = StockAnalyzer(config)
data_fetcher = StockDataFetcher(config)

# Get tech stocks
tech_stocks = ['AAPL', 'MSFT', 'GOOGL', 'META', 'NVDA']
batch_data = data_fetcher.batch_fetch_data(tech_stocks)

for symbol, data in batch_data.items():
    analysis = analyzer.analyze_stock(data)
    print(f"{symbol}: {analysis['overall_score']:.1f}")
```

### Portfolio Optimization

The system includes position sizing based on:
- Risk assessment
- Correlation analysis
- Sector exposure limits
- Overall portfolio balance

## Troubleshooting

### Common Issues

1. **Database Connection Errors**
   - Verify MySQL is running
   - Check credentials in config
   - Ensure database exists

2. **API Rate Limits**
   - System includes automatic rate limiting
   - Consider upgrading API plans for heavy usage
   - Yahoo Finance is the primary source (no key required)

3. **FrontAccounting Integration**
   - Verify FA API is enabled
   - Check authentication credentials
   - System works without FA if needed

### Logging

Comprehensive logging is available in:
- `logs/stock_analysis_YYYYMMDD.log`
- Console output for interactive sessions
- Database error tracking

## Performance Considerations

### Optimization Tips

1. **Batch Analysis**: Use batch operations for multiple stocks
2. **Database Indexing**: Indexes are automatically created for common queries
3. **API Efficiency**: Primary reliance on Yahoo Finance (free, unlimited)
4. **Caching**: Recent analysis results cached in database
5. **Selective Updates**: Only update positions that have changed

### Scaling

The system is designed to handle:
- **Portfolios**: Up to 100+ positions
- **Analysis**: 500+ stocks per day
- **Data Storage**: Multi-year historical data
- **Concurrent Users**: Multiple portfolio managers

## Contributing

This extension follows the same open-source spirit as the original experiment:

1. Fork the repository
2. Create feature branches
3. Submit pull requests
4. Maintain documentation
5. Follow existing code patterns

## License

This extension inherits the same license as the original ChatGPT Micro-Cap Experiment.

## Support

For issues, questions, or feature requests:
1. Check the troubleshooting section
2. Review logs for error details
3. Create GitHub issues with detailed information
4. Follow the same support channels as the original project

---

**Note**: This extension is designed for educational and experimental purposes. Always consult with financial professionals before making investment decisions and thoroughly test the system before using with real money.
