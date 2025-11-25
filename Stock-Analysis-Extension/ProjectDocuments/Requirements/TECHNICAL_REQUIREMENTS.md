# Technical Requirements Document
## Stock Analysis Extension

**Document Version:** 1.0  
**Date:** November 25, 2025  
**Status:** Approved  
**Parent:** FUNCTIONAL_REQUIREMENTS.md

---

## 1. System Architecture

### 1.1 High-Level Architecture

| Req ID | Parent | Requirement | Implementation |
|--------|--------|-------------|----------------|
| TR-100 | FR-100-1900 | System SHALL use modular Python architecture | Implemented: modules/ directory |
| TR-101 | FR-100-1900 | System SHALL implement MVC pattern | Implemented: main.py (controller), modules (model), reports (view) |
| TR-102 | FR-1200-1207 | System SHALL use MySQL for persistence layer | Implemented: database_manager.py |
| TR-103 | FR-100-107 | System SHALL use dependency injection pattern | Implemented: config-based initialization |

**Component Mapping:**
- `main.py` → Application Controller & Entry Point
- `modules/stock_data_fetcher.py` → Data Acquisition Layer
- `modules/stock_analyzer.py` → Analysis Engine
- `modules/portfolio_manager.py` → Portfolio Management Layer
- `modules/database_manager.py` → Data Persistence Layer
- `modules/front_accounting.py` → External Integration Layer
- `config/config_template.py` → Configuration Management

---

## 2. Technology Stack

### 2.1 Core Technologies

| Component | Technology | Version | Purpose |
|-----------|-----------|---------|---------|
| Runtime | Python | 3.8+ | Application execution |
| Database | MySQL | 8.0+ | Data persistence |
| Data Fetching | yfinance | Latest | Yahoo Finance API |
| Data Fetching | finnhub-python | Latest | Finnhub API |
| Data Fetching | alpha_vantage | Latest | Alpha Vantage API |
| Data Analysis | pandas | Latest | Data manipulation |
| Data Analysis | numpy | Latest | Numerical computations |
| HTTP | requests | Latest | API calls |
| Database | mysql-connector-python | Latest | MySQL connectivity |

**Requirement:** TR-104  
**Implements:** DEP-100, DEP-101, DEP-102, DEP-103, DEP-104

---

## 3. Module Specifications

### 3.1 StockDataFetcher Module

**File:** `modules/stock_data_fetcher.py`

| Req ID | Class/Method | Functional Req | Description |
|--------|--------------|----------------|-------------|
| TR-200 | StockDataFetcher | FR-100-107 | Main data fetching class |
| TR-201 | \_\_init\_\_(config) | FR-104 | Initialize with API configuration |
| TR-202 | fetch_stock_data(symbol) | FR-100-103 | Fetch all data for symbol |
| TR-203 | fetch_price_data(symbol) | FR-100 | Fetch current and historical prices |
| TR-204 | fetch_fundamentals(symbol) | FR-102 | Fetch fundamental data |
| TR-205 | fetch_technical_indicators(symbol) | FR-103 | Calculate technical indicators |
| TR-206 | \_fetch_from_yahoo(symbol) | FR-104 | Primary data source |
| TR-207 | \_fetch_from_finnhub(symbol) | FR-105 | Fallback data source |
| TR-208 | \_fetch_from_alphavantage(symbol) | FR-105 | Final fallback source |
| TR-209 | \_handle_rate_limit() | FR-106 | Rate limit management |
| TR-210 | batch_fetch_data(symbols) | FR-1503 | Batch fetch multiple symbols |

**Data Structures:**
```python
stock_data = {
    'symbol': str,
    'current_price': float,
    'volume': int,
    'market_cap': float,
    'prices': pd.DataFrame,  # Historical prices
    'fundamentals': {
        'pe_ratio': float,
        'pb_ratio': float,
        'roe': float,
        'roa': float,
        'debt_to_equity': float,
        'current_ratio': float,
        'quick_ratio': float,
        'profit_margin': float,
        'revenue_growth': float,
        'earnings_growth': float
    },
    'technical': {
        'rsi': float,
        'macd': float,
        'macd_signal': float,
        'ma_20': float,
        'ma_50': float,
        'ma_200': float,
        'bollinger_upper': float,
        'bollinger_lower': float
    }
}
```

---

### 3.2 StockAnalyzer Module

**File:** `modules/stock_analyzer.py`

| Req ID | Class/Method | Functional Req | Description |
|--------|--------------|----------------|-------------|
| TR-300 | StockAnalyzer | FR-200-706 | Main analysis engine |
| TR-301 | analyze_stock(data) | FR-700-706 | Complete stock analysis |
| TR-302 | calculate_fundamental_score(data) | FR-200-209 | Fundamental analysis (40%) |
| TR-303 | calculate_technical_score(data) | FR-300-308 | Technical analysis (30%) |
| TR-304 | calculate_momentum_score(data) | FR-400-406 | Momentum analysis (20%) |
| TR-305 | calculate_sentiment_score(data) | FR-500-505 | Sentiment analysis (10%) |
| TR-306 | assess_risk(data, scores) | FR-600-606 | Risk assessment |
| TR-307 | generate_recommendation(score, risk) | FR-700-706 | Generate BUY/SELL/HOLD |
| TR-308 | calculate_target_price(data, score) | FR-704 | Calculate target price |

**Scoring Algorithm:**
```python
overall_score = (
    fundamental_score * 0.40 +
    technical_score * 0.30 +
    momentum_score * 0.20 +
    sentiment_score * 0.10
)

recommendation = {
    score >= 70: 'BUY',
    score <= 40: 'SELL',
    else: 'HOLD'
}

risk_level = {
    risk_score < 30: 'LOW',
    risk_score < 50: 'MEDIUM',
    risk_score < 70: 'HIGH',
    else: 'VERY_HIGH'
}
```

**Analysis Result Structure:**
```python
analysis_result = {
    'symbol': str,
    'timestamp': datetime,
    'current_price': float,
    'scores': {
        'fundamental': float,
        'technical': float,
        'momentum': float,
        'sentiment': float,
        'overall': float
    },
    'recommendation': str,  # BUY/SELL/HOLD
    'target_price': float,
    'expected_return': float,
    'confidence': float,
    'risk_level': str,  # LOW/MEDIUM/HIGH/VERY_HIGH
    'risk_factors': List[str],
    'reasoning': Dict[str, str]
}
```

---

### 3.3 PortfolioManager Module

**File:** `modules/portfolio_manager.py`

| Req ID | Class/Method | Functional Req | Description |
|--------|--------------|----------------|-------------|
| TR-400 | PortfolioManager | FR-800-1106 | Portfolio management |
| TR-401 | create_portfolio(name, initial_cash) | FR-800 | Create new portfolio |
| TR-402 | get_portfolio(portfolio_id) | FR-800 | Retrieve portfolio |
| TR-403 | get_positions(portfolio_id) | FR-801 | Get current positions |
| TR-404 | execute_trade(portfolio_id, trade_data) | FR-900-908 | Execute BUY/SELL trade |
| TR-405 | validate_trade(portfolio_id, trade_data) | FR-1000-1007 | Validate against risk rules |
| TR-406 | calculate_position_size(risk_level, total_value) | FR-1003 | Risk-based sizing |
| TR-407 | check_sector_limits(portfolio_id, sector) | FR-1001 | Sector concentration check |
| TR-408 | calculate_correlation(portfolio_id, new_symbol) | FR-1002 | Correlation analysis |
| TR-409 | get_portfolio_summary(portfolio_id) | FR-1100-1106 | Portfolio analytics |
| TR-410 | update_position_prices(portfolio_id) | FR-802 | Update current values |
| TR-411 | check_stop_loss_triggers(portfolio_id) | FR-1004 | Stop-loss monitoring |
| TR-412 | check_take_profit_triggers(portfolio_id) | FR-1005 | Take-profit monitoring |

**Trade Validation Logic:**
```python
# Position size validation
max_position_size = total_portfolio_value * MAX_POSITION_PCT
if trade_value > max_position_size:
    return False, "Exceeds maximum position size"

# Sector concentration validation
sector_exposure = calculate_sector_exposure(portfolio_id, sector)
if sector_exposure + trade_value > total_value * MAX_SECTOR_PCT:
    return False, "Exceeds sector concentration limit"

# Correlation validation
correlations = calculate_correlations(portfolio_id, new_symbol)
if any(corr > MAX_CORRELATION for corr in correlations):
    return False, "High correlation with existing position"

# Cash validation (for buys)
if trade_type == 'BUY' and trade_value > available_cash:
    return False, "Insufficient cash"

# Position validation (for sells)
if trade_type == 'SELL' and quantity > current_position:
    return False, "Insufficient shares"
```

---

### 3.4 DatabaseManager Module

**File:** `modules/database_manager.py`

| Req ID | Class/Method | Functional Req | Description |
|--------|--------------|----------------|-------------|
| TR-500 | DatabaseManager | FR-1200-1305 | Database operations |
| TR-501 | \_\_init\_\_(config) | FR-1207 | Initialize connection |
| TR-502 | create_schema() | FR-1206 | Create database schema |
| TR-503 | save_stock_price(data) | FR-1200 | Store price data |
| TR-504 | save_fundamentals(data) | FR-1201 | Store fundamental data |
| TR-505 | save_technical_indicators(data) | FR-1202 | Store technical data |
| TR-506 | save_analysis_result(data) | FR-1203 | Store analysis results |
| TR-507 | save_trade(data) | FR-1204 | Store trade record |
| TR-508 | save_position(data) | FR-1205 | Store position |
| TR-509 | get_price_history(symbol, start, end) | FR-1301 | Retrieve price history |
| TR-510 | get_analysis_history(symbol, limit) | FR-1300 | Retrieve analysis history |
| TR-511 | get_trade_history(portfolio_id, start, end) | FR-1302 | Retrieve trade history |
| TR-512 | get_portfolio_positions(portfolio_id) | FR-1303 | Retrieve positions |

**Database Schema:**
```sql
-- Stock Prices Table
CREATE TABLE stock_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open DECIMAL(10,2),
    high DECIMAL(10,2),
    low DECIMAL(10,2),
    close DECIMAL(10,2),
    volume BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(symbol, date),
    INDEX idx_symbol (symbol),
    INDEX idx_date (date)
);

-- Stock Fundamentals Table
CREATE TABLE stock_fundamentals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    pe_ratio DECIMAL(10,2),
    pb_ratio DECIMAL(10,2),
    market_cap BIGINT,
    roe DECIMAL(10,4),
    roa DECIMAL(10,4),
    debt_to_equity DECIMAL(10,4),
    current_ratio DECIMAL(10,4),
    quick_ratio DECIMAL(10,4),
    profit_margin DECIMAL(10,4),
    revenue_growth DECIMAL(10,4),
    earnings_growth DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(symbol, date),
    INDEX idx_symbol (symbol)
);

-- Technical Indicators Table
CREATE TABLE technical_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    rsi DECIMAL(10,4),
    macd DECIMAL(10,4),
    macd_signal DECIMAL(10,4),
    ma_20 DECIMAL(10,2),
    ma_50 DECIMAL(10,2),
    ma_200 DECIMAL(10,2),
    bollinger_upper DECIMAL(10,2),
    bollinger_lower DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY(symbol, date),
    INDEX idx_symbol (symbol)
);

-- Analysis Results Table
CREATE TABLE analysis_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    current_price DECIMAL(10,2),
    fundamental_score DECIMAL(5,2),
    technical_score DECIMAL(5,2),
    momentum_score DECIMAL(5,2),
    sentiment_score DECIMAL(5,2),
    overall_score DECIMAL(5,2),
    recommendation VARCHAR(10),
    target_price DECIMAL(10,2),
    expected_return DECIMAL(10,4),
    confidence DECIMAL(5,2),
    risk_level VARCHAR(10),
    risk_factors TEXT,
    reasoning TEXT,
    INDEX idx_symbol (symbol),
    INDEX idx_date (analysis_date),
    INDEX idx_score (overall_score)
);

-- Portfolios Table
CREATE TABLE portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    initial_cash DECIMAL(15,2),
    current_cash DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Portfolio Positions Table
CREATE TABLE portfolio_positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    quantity DECIMAL(15,4),
    entry_price DECIMAL(10,2),
    current_price DECIMAL(10,2),
    stop_loss DECIMAL(10,2),
    take_profit DECIMAL(10,2),
    sector VARCHAR(50),
    entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id),
    UNIQUE KEY(portfolio_id, symbol),
    INDEX idx_portfolio (portfolio_id)
);

-- Trade Log Table
CREATE TABLE trade_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    trade_type VARCHAR(10) NOT NULL,
    quantity DECIMAL(15,4),
    price DECIMAL(10,2),
    total_value DECIMAL(15,2),
    commission DECIMAL(10,2) DEFAULT 0,
    strategy VARCHAR(100),
    trade_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id),
    INDEX idx_portfolio (portfolio_id),
    INDEX idx_symbol (symbol),
    INDEX idx_date (trade_date)
);

-- FrontAccounting Sync Table
CREATE TABLE front_accounting_sync (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trade_id INT NOT NULL,
    fa_transaction_id INT,
    fa_transaction_type VARCHAR(50),
    sync_status VARCHAR(20),
    sync_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    FOREIGN KEY (trade_id) REFERENCES trade_log(id),
    INDEX idx_trade (trade_id)
);
```

---

### 3.5 FrontAccountingIntegrator Module

**File:** `modules/front_accounting.py`

| Req ID | Class/Method | Functional Req | Description |
|--------|--------------|----------------|-------------|
| TR-600 | FrontAccountingIntegrator | FR-1400-1406 | FA integration |
| TR-601 | sync_trade(trade_data) | FR-1400-1401 | Sync trade to FA |
| TR-602 | create_buy_journal_entry(trade) | FR-1400 | Create buy journal |
| TR-603 | create_sell_journal_entry(trade) | FR-1401 | Create sell journal |
| TR-604 | create_mtm_adjustment(positions) | FR-1402 | Mark-to-market adjustment |
| TR-605 | update_sync_status(trade_id, status) | FR-1403 | Update sync status |
| TR-606 | handle_api_error(error) | FR-1404 | Error handling |

**GL Account Mapping:**
```python
ACCOUNT_MAPPING = {
    'cash': 1060,              # Cash in Bank
    'investments': 1500,       # Investment Securities
    'realized_gain_loss': 8200,  # Investment Gain/Loss
    'unrealized_gain_loss': 8210,  # Unrealized Gain/Loss
    'commission': 5800,        # Commission Expense
    'dividend_income': 8100    # Dividend Income
}
```

**Journal Entry Format (Buy):**
```
DR Investment Securities    $10,000
DR Commission Expense       $10
    CR Cash in Bank                 $10,010
```

**Journal Entry Format (Sell):**
```
DR Cash in Bank            $15,000
DR Commission Expense       $10
    CR Investment Securities        $10,000
    CR Realized Gain/Loss           $5,010
```

---

## 4. API Specifications

### 4.1 Yahoo Finance API

| Req ID | Endpoint | Purpose | Rate Limit |
|--------|----------|---------|------------|
| TR-700 | yfinance.Ticker(symbol).info | Get company info | Unlimited (best effort) |
| TR-701 | yfinance.Ticker(symbol).history() | Get price history | Unlimited (best effort) |
| TR-702 | yfinance.download() | Batch download | Unlimited (best effort) |

**Implements:** FR-104, DEP-100

---

### 4.2 Finnhub API

| Req ID | Endpoint | Purpose | Rate Limit |
|--------|----------|---------|------------|
| TR-710 | /quote | Real-time quote | 60 calls/minute (free) |
| TR-711 | /stock/profile2 | Company profile | 60 calls/minute (free) |
| TR-712 | /stock/metric | Company metrics | 60 calls/minute (free) |

**Implements:** FR-105, DEP-103

---

### 4.3 Alpha Vantage API

| Req ID | Endpoint | Purpose | Rate Limit |
|--------|----------|---------|------------|
| TR-720 | TIME_SERIES_DAILY | Daily prices | 500 calls/day (free) |
| TR-721 | OVERVIEW | Company overview | 500 calls/day (free) |
| TR-722 | RSI, MACD | Technical indicators | 500 calls/day (free) |

**Implements:** FR-105, DEP-104

---

## 5. Performance Requirements

| Req ID | Requirement | Target | Priority |
|--------|-------------|--------|----------|
| TR-800 | Stock analysis completion time | < 30 seconds | MUST |
| TR-801 | Database query response time | < 1 second | MUST |
| TR-802 | Portfolio summary generation | < 5 seconds | MUST |
| TR-803 | Batch analysis (10 stocks) | < 5 minutes | SHOULD |
| TR-804 | FrontAccounting sync | < 10 seconds per trade | SHOULD |
| TR-805 | Memory usage | < 500 MB | SHOULD |
| TR-806 | Database size growth | < 100 MB per month | SHOULD |

**Implements:** SC-101

---

## 6. Security Requirements

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| TR-900 | API keys stored in config file only | config/config.py (not in repo) |
| TR-901 | Database credentials in config file | config/config.py (not in repo) |
| TR-902 | Config template provided without secrets | config/config_template.py |
| TR-903 | Input validation on all user inputs | Implemented in all modules |
| TR-904 | SQL injection prevention | Parameterized queries |
| TR-905 | Error messages do not expose secrets | Sanitized error messages |

**Implements:** CON-202, CON-203

---

## 7. Deployment Requirements

| Req ID | Requirement | Implementation |
|--------|-------------|----------------|
| TR-1000 | Python 3.8+ required | requirements_extension.txt |
| TR-1001 | MySQL 8.0+ required | Database schema |
| TR-1002 | All dependencies in requirements file | requirements_extension.txt |
| TR-1003 | Setup script for initialization | setup.py |
| TR-1004 | Configuration template provided | config/config_template.py |
| TR-1005 | README with installation instructions | README.md |

**Implements:** DEP-100, DEP-101, DEP-102

---

## 8. Testing Requirements

| Req ID | Requirement | Coverage Target |
|--------|-------------|----------------|
| TR-1100 | Unit tests for all modules | 80% code coverage |
| TR-1101 | Integration tests for database | All CRUD operations |
| TR-1102 | Integration tests for APIs | All API calls |
| TR-1103 | End-to-end test for full workflow | Complete user journey |
| TR-1104 | Performance tests | All performance requirements |

**Test Files Location:** `tests/` (to be created)

---

## 9. Code Quality Requirements

| Req ID | Requirement | Standard |
|--------|-------------|----------|
| TR-1200 | Python code style | PEP 8 |
| TR-1201 | Docstrings for all public methods | Google style |
| TR-1202 | Type hints for function signatures | Python 3.8+ annotations |
| TR-1203 | Logging for all operations | Python logging module |
| TR-1204 | Error handling for all external calls | try/except with logging |

---

## 10. Requirements-to-Code Mapping

### 10.1 Module-to-Requirements Matrix

| Module | Requirements Implemented | Line Count |
|--------|-------------------------|------------|
| main.py | FR-1500-1505, FR-1700-1703, FR-1800-1804, FR-1900-1903 | ~575 |
| stock_data_fetcher.py | FR-100-107 | ~400 |
| stock_analyzer.py | FR-200-706 | ~600 |
| portfolio_manager.py | FR-800-1106 | ~700 |
| database_manager.py | FR-1200-1305 | ~800 |
| front_accounting.py | FR-1400-1406 | ~300 |
| **Total** | **129 functional requirements** | **~3,375 lines** |

---

**End of Document**
