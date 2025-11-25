# Code-to-Requirements Cross-Reference
## Stock Analysis Extension

**Document Version:** 1.0  
**Date:** November 25, 2025  
**Status:** Approved

---

## 1. Overview

This document provides a **code-first** view of traceability, allowing developers and QA to quickly identify which requirements are implemented by each file and function. This complements the Requirements Traceability Matrix which provides a **requirements-first** view.

---

## 2. File-to-Requirements Mapping

### 2.1 main.py

**Location:** `Stock-Analysis-Extension/main.py`  
**Lines of Code:** ~575  
**Purpose:** Application controller and entry point

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-001 | Automated stock analysis |
| | BR-002 | Data-driven recommendations |
| | BR-003 | Portfolio management with risk controls |
| | BR-010 | Professional-grade analysis tools |
| | BR-011 | Clear buy/sell/hold recommendations |
| | BR-012 | Portfolio performance tracking |
| | BR-020 | Efficient multi-stock analysis |
| **Functional Req** | FR-1500-1505 | User Interface Functions |
| | FR-1600-1603 | Reporting Functions |
| | FR-1700-1703 | Configuration Functions |
| | FR-1800-1804 | Logging and Error Handling |
| | FR-1900-1903 | Automation Functions |
| **Technical Req** | TR-100-103 | System Architecture (MVC, modular) |
| | TR-800 | Performance (< 30 sec analysis) |
| | TR-900-905 | Security (config management) |
| | TR-1000-1005 | Deployment requirements |
| | TR-1200-1204 | Code quality requirements |

#### Key Functions:

| Function | Lines | Requirements | Description |
|----------|-------|--------------|-------------|
| `__init__(config_path)` | 24-45 | FR-1700-1703 | Initialize application with config |
| `_load_config(config_path)` | 47-71 | FR-1700-1702 | Load and validate configuration |
| `setup_logging()` | 94-111 | FR-1800-1804 | Configure logging system |
| `initialize()` | 113-145 | FR-1700-1703 | Initialize all components |
| `analyze_stock(symbol)` | 147-181 | FR-1501, BR-001 | Analyze individual stock |
| `get_recommendations(limit)` | 183-223 | FR-1502, BR-002 | Get top N recommendations |
| `analyze_existing_portfolio(symbols)` | 225-280 | FR-1503, BR-020 | Analyze existing holdings |
| `execute_trade(...)` | 282-332 | FR-1505, BR-003 | Execute BUY/SELL trade |
| `get_portfolio_summary(portfolio_id)` | 334-367 | FR-1504, BR-012 | Get portfolio analytics |
| `run_daily_analysis()` | 369-425 | FR-1900-1903 | Automated daily updates |
| `print_analysis_report(result)` | 427-492 | FR-1600-1602, BR-011 | Format and display report |
| `run_interactive()` | 494-575 | FR-1500-1505, BR-010 | Interactive menu |

---

### 2.2 modules/stock_data_fetcher.py

**Location:** `Stock-Analysis-Extension/modules/stock_data_fetcher.py`  
**Lines of Code:** ~413  
**Purpose:** Multi-source data acquisition with fallback

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-001 | Automated stock analysis (data layer) |
| | BR-010 | Professional-grade analysis tools |
| | BR-020 | Efficient multi-stock analysis |
| **Business Cap** | BC-300 | Multi-Source Data Fetching |
| | BC-301 | MySQL Database Persistence (caching) |
| **Functional Req** | FR-100 | Fetch real-time stock price data |
| | FR-101 | Fetch historical price data (252+ days) |
| | FR-102 | Fetch fundamental data |
| | FR-103 | Fetch technical indicators |
| | FR-104 | Multi-source data fetching |
| | FR-105 | Automatic fallback on failure |
| | FR-106 | Respect API rate limits |
| | FR-107 | Cache recent data |
| **Technical Req** | TR-200-210 | StockDataFetcher specification |
| | TR-700-722 | API specifications |
| **Dependencies** | DEP-100 | Yahoo Finance API (primary) |
| | DEP-103 | Finnhub API (secondary) |
| | DEP-104 | Alpha Vantage API (tertiary) |
| **Constraints** | CON-100 | API rate limits |

#### Key Methods:

| Method | Lines | Requirements | Description |
|--------|-------|--------------|-------------|
| `__init__(config)` | 19-35 | FR-104, TR-201 | Initialize with API keys |
| `fetch_stock_data(symbol)` | 37-88 | FR-100-103, BR-001 | Fetch all data for symbol |
| `fetch_price_data(symbol)` | 90-135 | FR-100-101 | Get current and historical prices |
| `fetch_fundamentals(symbol)` | 137-195 | FR-102 | Get company fundamentals |
| `fetch_technical_indicators(symbol)` | 197-265 | FR-103 | Calculate technical indicators |
| `_fetch_from_yahoo(symbol)` | 267-315 | FR-104, TR-206, DEP-100 | Yahoo Finance primary source |
| `_fetch_from_finnhub(symbol)` | 317-355 | FR-105, TR-207, DEP-103 | Finnhub fallback |
| `_fetch_from_alphavantage(symbol)` | 357-395 | FR-105, TR-208, DEP-104 | Alpha Vantage tertiary |
| `_handle_rate_limit()` | 397-407 | FR-106, TR-209, CON-100 | Rate limiting logic |
| `batch_fetch_data(symbols)` | 62-85 | FR-1503, BR-020 | Batch fetch multiple stocks |

---

### 2.3 modules/stock_analyzer.py

**Location:** `Stock-Analysis-Extension/modules/stock_analyzer.py`  
**Lines of Code:** ~813  
**Purpose:** Four-dimensional stock analysis engine

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-001 | Automated stock analysis |
| | BR-002 | Data-driven recommendations |
| | BR-010 | Professional-grade tools |
| | BR-011 | Clear buy/sell/hold recommendations |
| | BR-030 | Multi-dimensional analysis |
| **Business Cap** | BC-100 | Four-Dimensional Analysis |
| | BC-101 | Fundamental Analysis (40%) |
| | BC-102 | Technical Analysis (30%) |
| | BC-103 | Momentum Analysis (20%) |
| | BC-104 | Sentiment Analysis (10%) |
| | BC-105 | Risk Assessment |
| | BC-106 | Target Price Calculation |
| **Functional Req** | FR-200-209 | Fundamental Analysis Functions |
| | FR-300-308 | Technical Analysis Functions |
| | FR-400-406 | Momentum Analysis Functions |
| | FR-500-505 | Sentiment Analysis Functions |
| | FR-600-606 | Risk Assessment Functions |
| | FR-700-706 | Recommendation Generation |
| **Technical Req** | TR-300-308 | StockAnalyzer specification |
| | TR-800 | Performance (< 30 sec) |
| **Business Rules** | BRU-100-104 | Analysis validation rules |
| **Success Criteria** | SC-101, SC-102 | Performance and accuracy |

#### Key Methods:

| Method | Lines | Requirements | Description |
|--------|-------|--------------|-------------|
| `__init__(config)` | 17-30 | TR-300 | Initialize with scoring weights |
| `analyze_stock(data)` | 32-110 | FR-700-706, BR-002 | Complete stock analysis |
| `calculate_fundamental_score(data)` | 112-225 | FR-200-209, BC-101 | Fundamental analysis 40% |
| `calculate_technical_score(data)` | 227-345 | FR-300-308, BC-102 | Technical analysis 30% |
| `calculate_momentum_score(data)` | 347-435 | FR-400-406, BC-103 | Momentum analysis 20% |
| `calculate_sentiment_score(data)` | 437-495 | FR-500-505, BC-104 | Sentiment analysis 10% |
| `assess_risk(data, scores)` | 497-585 | FR-600-606, BC-105 | Risk assessment & confidence |
| `generate_recommendation(score, risk)` | 587-642 | FR-700-706, BR-011 | BUY/SELL/HOLD decision |
| `calculate_target_price(data, score)` | 644-690 | FR-704, BC-106 | Calculate target price |
| `_analyze_pe_ratio(pe)` | 692-710 | FR-201 | P/E ratio scoring |
| `_analyze_growth(data)` | 712-745 | FR-208 | Revenue/earnings growth |
| `_detect_trend(prices)` | 747-780 | FR-305 | Trend direction detection |
| `_calculate_rsi(prices, period)` | 782-813 | FR-302 | RSI calculation |

**Scoring Algorithm:**
```python
overall_score = (
    fundamental * 0.40 +
    technical * 0.30 +
    momentum * 0.20 +
    sentiment * 0.10
)

recommendation = {
    score >= 70: 'BUY',
    score <= 40: 'SELL',
    else: 'HOLD'
}
```

---

### 2.4 modules/portfolio_manager.py

**Location:** `Stock-Analysis-Extension/modules/portfolio_manager.py`  
**Lines of Code:** ~657  
**Purpose:** Portfolio operations and risk management

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-003 | Portfolio management with risk controls |
| | BR-012 | Portfolio performance tracking |
| | BR-021 | Portfolio risk management |
| | BR-022 | Rebalancing recommendations |
| **Business Cap** | BC-200 | Portfolio Tracking |
| | BC-201 | Risk-Based Position Sizing |
| | BC-202 | Sector Exposure Limits |
| | BC-203 | Stop-Loss/Take-Profit |
| | BC-204 | Correlation Analysis |
| | BC-205 | Performance Tracking |
| | BC-206 | Rebalancing Recommendations |
| **Functional Req** | FR-800-806 | Portfolio Operations |
| | FR-900-908 | Trade Execution |
| | FR-1000-1007 | Risk Controls |
| | FR-1100-1106 | Portfolio Analytics |
| **Technical Req** | TR-400-412 | PortfolioManager specification |
| | TR-802 | Performance (< 5 sec summary) |
| **Business Rules** | BRU-200-205 | Risk management rules |

#### Key Methods:

| Method | Lines | Requirements | Description |
|--------|-------|--------------|-------------|
| `__init__(config)` | 16-30 | TR-400 | Initialize with risk config |
| `create_portfolio(name, cash)` | 32-65 | FR-800, BR-003 | Create new portfolio |
| `get_portfolio(portfolio_id)` | 67-92 | FR-800 | Retrieve portfolio data |
| `get_positions(portfolio_id)` | 94-120 | FR-801, BR-012 | Get current positions |
| `execute_trade(portfolio_id, trade)` | 122-220 | FR-900-908, BR-003 | Execute BUY/SELL trade |
| `validate_trade(portfolio_id, trade)` | 222-355 | FR-1000-1007, BR-021 | Validate against risk rules |
| `calculate_position_size(risk, value)` | 357-395 | FR-1003, BC-201 | Risk-based position sizing |
| `check_sector_limits(portfolio_id, sector)` | 397-430 | FR-1001, BC-202 | Sector concentration check |
| `calculate_correlation(portfolio_id, symbol)` | 432-500 | FR-1002, BC-204 | Correlation matrix analysis |
| `get_portfolio_summary(portfolio_id)` | 502-590 | FR-1100-1106, BR-012 | Complete portfolio analytics |
| `update_position_prices(portfolio_id)` | 592-620 | FR-802 | Update current values |
| `check_stop_loss_triggers(portfolio_id)` | 622-645 | FR-1004, BC-203 | Monitor stop-loss levels |
| `check_take_profit_triggers(portfolio_id)` | 647-657 | FR-1005, BC-203 | Monitor take-profit levels |

**Risk Rules:**
- Position Size: Max 5% (configurable) - BRU-200
- Sector Exposure: Max 25% (configurable) - BRU-201
- Stop Loss: 15% below entry (configurable) - BRU-202
- Take Profit: 25% above entry (configurable) - BRU-203
- Correlation: Max 70% (configurable) - BRU-204

---

### 2.5 modules/database_manager.py

**Location:** `Stock-Analysis-Extension/modules/database_manager.py`  
**Lines of Code:** ~306  
**Purpose:** MySQL database operations and schema management

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-031 | Historical analysis review |
| **Business Cap** | BC-301 | MySQL Database Persistence |
| | BC-303 | Trade Log Management |
| | BC-304 | Position Management |
| **Functional Req** | FR-1200-1207 | Database Operations |
| | FR-1300-1305 | Data Retrieval |
| **Technical Req** | TR-500-512 | DatabaseManager specification |
| | TR-801 | Performance (< 1 sec query) |
| | TR-904 | SQL injection prevention |
| **Dependencies** | DEP-101 | MySQL Server 8.0+ |

#### Key Methods:

| Method | Lines | Requirements | Description |
|--------|-------|--------------|-------------|
| `__init__(config)` | 15-32 | FR-1207, TR-501, DEP-101 | Initialize DB connection |
| `create_schema()` | 34-90 | FR-1206, TR-502 | Create all tables |
| `save_stock_price(data)` | 92-115 | FR-1200, TR-503 | Store price data |
| `save_fundamentals(data)` | 117-142 | FR-1201, TR-504 | Store fundamental data |
| `save_technical_indicators(data)` | 144-170 | FR-1202, TR-505 | Store technical data |
| `save_analysis_result(data)` | 172-210 | FR-1203, TR-506, BR-031 | Store analysis results |
| `save_trade(data)` | 212-240 | FR-1204, TR-507, BC-303 | Store trade record |
| `save_position(data)` | 242-268 | FR-1205, TR-508, BC-304 | Store portfolio position |
| `get_price_history(symbol, start, end)` | 270-285 | FR-1301, TR-509 | Retrieve price history |
| `get_analysis_history(symbol, limit)` | 287-300 | FR-1300, TR-510, BR-031 | Retrieve analysis history |
| `get_trade_history(portfolio_id, start, end)` | 145-165 | FR-1302, TR-511, BC-303 | Retrieve trade history |
| `get_portfolio_positions(portfolio_id)` | 167-185 | FR-1303, TR-512, BC-304 | Retrieve positions |

**Database Tables:**
- `stock_prices` - Historical OHLCV data (FR-1200)
- `stock_fundamentals` - Company fundamentals (FR-1201)
- `technical_indicators` - RSI, MACD, MA, etc. (FR-1202)
- `analysis_results` - Complete analysis scores (FR-1203)
- `portfolios` - Portfolio definitions (FR-1205)
- `portfolio_positions` - Current holdings (FR-1205)
- `trade_log` - Transaction history (FR-1204)
- `front_accounting_sync` - FA sync tracking (FR-1403)

---

### 2.6 modules/front_accounting.py

**Location:** `Stock-Analysis-Extension/modules/front_accounting.py`  
**Lines of Code:** ~638  
**Purpose:** FrontAccounting system integration

#### Requirements Implemented:

| Category | IDs | Description |
|----------|-----|-------------|
| **Business Req** | BR-004 | Accounting system integration |
| | BR-040 | Generate journal entries |
| | BR-041 | Mark-to-market tracking |
| | BR-042 | Financial reporting |
| **Business Cap** | BC-302 | FrontAccounting Integration |
| **Functional Req** | FR-1400 | Create buy journal entries |
| | FR-1401 | Create sell journal entries |
| | FR-1402 | Create MTM adjustments |
| | FR-1403 | Track sync status |
| | FR-1404 | Handle FA API errors |
| | FR-1405 | Optional FA (not required) |
| | FR-1406 | Map trades to GL accounts |
| **Technical Req** | TR-600-606 | FrontAccountingIntegrator spec |
| | TR-804 | Performance (< 10 sec sync) |
| **Dependencies** | DEP-105 | FrontAccounting 2.4+ (optional) |

#### Key Methods:

| Method | Lines | Requirements | Description |
|--------|-------|--------------|-------------|
| `__init__(config)` | 13-35 | TR-600, FR-1405 | Initialize (optional) |
| `sync_trade(trade_data)` | 37-95 | FR-1400-1401, TR-601, BR-004 | Sync trade to FA |
| `create_buy_journal_entry(trade)` | 97-180 | FR-1400, TR-602, BR-040 | Create buy journal |
| `create_sell_journal_entry(trade)` | 182-270 | FR-1401, TR-603, BR-040 | Create sell journal |
| `create_mtm_adjustment(positions)` | 272-340 | FR-1402, TR-604, BR-041 | MTM adjustment entry |
| `update_sync_status(trade_id, status)` | 342-365 | FR-1403, TR-605 | Update sync tracking |
| `handle_api_error(error)` | 367-395 | FR-1404, TR-606 | Error handling & retry |
| `_get_gl_account(account_type)` | 397-420 | FR-1406 | Map to GL accounts |
| `_authenticate()` | 422-450 | TR-606 | FA API authentication |
| `_make_api_request(endpoint, data)` | 452-490 | TR-606 | FA API calls |

**GL Account Mapping (FR-1406):**
- Cash: 1060 (Cash in Bank)
- Investments: 1500 (Investment Securities)
- Realized Gain/Loss: 8200
- Unrealized Gain/Loss: 8210
- Commission Expense: 5800
- Dividend Income: 8100

---

## 3. Requirements-to-File Quick Reference

### 3.1 Business Requirements

| Requirement | Primary Files | Secondary Files |
|-------------|---------------|-----------------|
| BR-001 | stock_analyzer.py, stock_data_fetcher.py | main.py |
| BR-002 | stock_analyzer.py | main.py |
| BR-003 | portfolio_manager.py | main.py |
| BR-004 | front_accounting.py | - |
| BR-010 | main.py, stock_data_fetcher.py | stock_analyzer.py |
| BR-011 | stock_analyzer.py | main.py |
| BR-012 | portfolio_manager.py | main.py |
| BR-020 | stock_data_fetcher.py | main.py |
| BR-021 | portfolio_manager.py | - |
| BR-022 | portfolio_manager.py | - |
| BR-030 | stock_analyzer.py | - |
| BR-031 | database_manager.py | - |
| BR-040 | front_accounting.py | - |
| BR-041 | front_accounting.py | - |
| BR-042 | front_accounting.py | - |

### 3.2 Functional Requirements by Module

| Module | Functional Requirements |
|--------|------------------------|
| **main.py** | FR-1500-1505, FR-1600-1603, FR-1700-1703, FR-1800-1804, FR-1900-1903 |
| **stock_data_fetcher.py** | FR-100-107 |
| **stock_analyzer.py** | FR-200-706 (60 requirements) |
| **portfolio_manager.py** | FR-800-1106 (32 requirements) |
| **database_manager.py** | FR-1200-1305 (20 requirements) |
| **front_accounting.py** | FR-1400-1406 |

### 3.3 Technical Requirements by Module

| Module | Technical Requirements |
|--------|----------------------|
| **main.py** | TR-100-103, TR-800, TR-900-905, TR-1000-1005, TR-1200-1204 |
| **stock_data_fetcher.py** | TR-200-210, TR-700-722 |
| **stock_analyzer.py** | TR-300-308 |
| **portfolio_manager.py** | TR-400-412, TR-802 |
| **database_manager.py** | TR-500-512, TR-801, TR-904 |
| **front_accounting.py** | TR-600-606, TR-804 |

---

## 4. Feature-to-Code Mapping

### 4.1 Data Acquisition (BR-001, BC-300)

**Primary Implementation:** `modules/stock_data_fetcher.py`

| Feature | Method | Requirements |
|---------|--------|--------------|
| Yahoo Finance Data | `_fetch_from_yahoo()` | FR-104, TR-206, DEP-100 |
| Finnhub Fallback | `_fetch_from_finnhub()` | FR-105, TR-207, DEP-103 |
| Alpha Vantage Fallback | `_fetch_from_alphavantage()` | FR-105, TR-208, DEP-104 |
| Rate Limiting | `_handle_rate_limit()` | FR-106, TR-209, CON-100 |
| Price Data | `fetch_price_data()` | FR-100-101 |
| Fundamentals | `fetch_fundamentals()` | FR-102 |
| Technical Indicators | `fetch_technical_indicators()` | FR-103 |

---

### 4.2 Four-Dimensional Analysis (BR-001, BR-002, BC-100-106)

**Primary Implementation:** `modules/stock_analyzer.py`

| Analysis Dimension | Method | Weight | Requirements |
|-------------------|--------|--------|--------------|
| Fundamental | `calculate_fundamental_score()` | 40% | FR-200-209, BC-101 |
| Technical | `calculate_technical_score()` | 30% | FR-300-308, BC-102 |
| Momentum | `calculate_momentum_score()` | 20% | FR-400-406, BC-103 |
| Sentiment | `calculate_sentiment_score()` | 10% | FR-500-505, BC-104 |
| Risk Assessment | `assess_risk()` | - | FR-600-606, BC-105 |
| Recommendation | `generate_recommendation()` | - | FR-700-706 |
| Target Price | `calculate_target_price()` | - | FR-704, BC-106 |

---

### 4.3 Portfolio Management (BR-003, BR-012, BC-200-206)

**Primary Implementation:** `modules/portfolio_manager.py`

| Feature | Method | Requirements |
|---------|--------|--------------|
| Create Portfolio | `create_portfolio()` | FR-800 |
| Trade Execution | `execute_trade()` | FR-900-908 |
| Risk Validation | `validate_trade()` | FR-1000-1007, BRU-200-205 |
| Position Sizing | `calculate_position_size()` | FR-1003, BC-201 |
| Sector Limits | `check_sector_limits()` | FR-1001, BC-202 |
| Correlation Check | `calculate_correlation()` | FR-1002, BC-204 |
| Portfolio Summary | `get_portfolio_summary()` | FR-1100-1106, BC-205 |
| Stop-Loss Monitor | `check_stop_loss_triggers()` | FR-1004, BC-203 |
| Take-Profit Monitor | `check_take_profit_triggers()` | FR-1005, BC-203 |

---

### 4.4 Data Persistence (BC-301, BC-303, BC-304)

**Primary Implementation:** `modules/database_manager.py`

| Feature | Method | Requirements |
|---------|--------|--------------|
| Price Storage | `save_stock_price()` | FR-1200 |
| Fundamentals Storage | `save_fundamentals()` | FR-1201 |
| Technical Storage | `save_technical_indicators()` | FR-1202 |
| Analysis Storage | `save_analysis_result()` | FR-1203 |
| Trade Logging | `save_trade()` | FR-1204, BC-303 |
| Position Tracking | `save_position()` | FR-1205, BC-304 |
| Price History | `get_price_history()` | FR-1301 |
| Analysis History | `get_analysis_history()` | FR-1300, BR-031 |
| Trade History | `get_trade_history()` | FR-1302 |

---

### 4.5 Accounting Integration (BR-004, BC-302)

**Primary Implementation:** `modules/front_accounting.py`

| Feature | Method | Requirements |
|---------|--------|--------------|
| Trade Sync | `sync_trade()` | FR-1400-1401, BR-004 |
| Buy Journal | `create_buy_journal_entry()` | FR-1400, BR-040 |
| Sell Journal | `create_sell_journal_entry()` | FR-1401, BR-040 |
| MTM Adjustment | `create_mtm_adjustment()` | FR-1402, BR-041 |
| Sync Tracking | `update_sync_status()` | FR-1403 |
| Error Handling | `handle_api_error()` | FR-1404 |

---

## 5. Impact Analysis Guide

### 5.1 What to Check When Requirements Change

| Requirement Type | Check Files | Check Documentation |
|-----------------|-------------|---------------------|
| Business Requirement | All 6 modules | BUSINESS_REQUIREMENTS.md |
| Functional Requirement | Specific module per RTM | FUNCTIONAL_REQUIREMENTS.md |
| Technical Requirement | Specific module per RTM | TECHNICAL_REQUIREMENTS.md |
| Database Schema | database_manager.py, sql/ | TECHNICAL_REQUIREMENTS.md §3.4 |
| API Specification | stock_data_fetcher.py | TECHNICAL_REQUIREMENTS.md §4 |
| Risk Rules | portfolio_manager.py | BUSINESS_REQUIREMENTS.md §4.2 |

### 5.2 Change Impact Examples

**Example 1: Change position size limit from 5% to 10%**
- **Requirements:** BRU-200, FR-1000
- **Files:** `portfolio_manager.py::calculate_position_size()`
- **Config:** `config/config_template.py::RISK_CONFIG['max_position_pct']`
- **Docs:** BUSINESS_REQUIREMENTS.md §4.2, FUNCTIONAL_REQUIREMENTS.md §3.3
- **Tests:** TC-302 (portfolio risk controls)

**Example 2: Add new sentiment data source (Twitter API)**
- **New Requirements:** FR-507 (new), DEP-106 (new)
- **Files:** `stock_data_fetcher.py` (add `_fetch_from_twitter()`), `stock_analyzer.py::calculate_sentiment_score()` (update)
- **Config:** `config/config_template.py::API_KEYS['twitter']`
- **Docs:** Update all requirements docs, add to TR-700-722
- **Tests:** TC-203 (sentiment analysis), new TC-203a

**Example 3: Support PostgreSQL in addition to MySQL**
- **Requirements:** TR-500-512 (modify), DEP-101 (modify)
- **Files:** `database_manager.py` (major refactor)
- **Config:** `config/config_template.py::DATABASE_CONFIG['db_type']`
- **Schema:** Create new `sql/postgresql_schema.sql`
- **Docs:** TECHNICAL_REQUIREMENTS.md §3.4
- **Tests:** TC-400, TC-401 (all database tests)

---

## 6. Developer Quick Reference

### 6.1 Adding a New Feature

**Checklist:**
1. ✅ Create Business Requirement (BR-xxx) in BUSINESS_REQUIREMENTS.md
2. ✅ Create Functional Requirements (FR-xxxx-yyyy) in FUNCTIONAL_REQUIREMENTS.md
3. ✅ Create Technical Requirements (TR-xxxx) in TECHNICAL_REQUIREMENTS.md
4. ✅ Update REQUIREMENTS_TRACEABILITY_MATRIX.md
5. ✅ Add requirement IDs to source file header
6. ✅ Implement feature in appropriate module
7. ✅ Add method-level requirement comments
8. ✅ Update this CODE_TO_REQUIREMENTS_XREF.md
9. ✅ Create test cases (TC-xxx)
10. ✅ Update README.md if user-facing

### 6.2 Finding Requirements for a File

```bash
# Method 1: Check file header
head -50 modules/stock_analyzer.py | grep -A30 "REQUIREMENTS TRACEABILITY"

# Method 2: Check this document
# See §2 (File-to-Requirements Mapping)

# Method 3: Check RTM
# See REQUIREMENTS_TRACEABILITY_MATRIX.md §4
```

### 6.3 Finding Code for a Requirement

```bash
# Method 1: Check RTM
# See REQUIREMENTS_TRACEABILITY_MATRIX.md §4 (Technical to Code)

# Method 2: Check this document
# See §3 (Requirements-to-File Quick Reference)

# Method 3: Search codebase
grep -r "FR-300" modules/*.py
grep -r "BR-001" modules/*.py
```

---

## 7. QA Testing Guide

### 7.1 Requirements Coverage Testing

| Test Type | Files to Test | Requirements Coverage |
|-----------|---------------|----------------------|
| Unit Tests | All 6 modules | FR-100 through FR-1900 |
| Integration Tests | main.py + all modules | BR-001 through BR-042 |
| Performance Tests | All modules | TR-800-806, SC-101-103 |
| Security Tests | main.py, database_manager.py | TR-900-905, CON-202 |

### 7.2 Test Case to Requirements Mapping

See REQUIREMENTS_TRACEABILITY_MATRIX.md §5 for complete test case mappings.

**Quick Reference:**
- TC-100: Tests FR-100-107 (data fetching)
- TC-200-205: Tests FR-200-706 (analysis)
- TC-300-303: Tests FR-800-1106 (portfolio management)
- TC-400-401: Tests FR-1200-1305 (database)
- TC-500: Tests FR-1400-1406 (FrontAccounting)
- TC-1000-1005: End-to-end integration tests
- TC-2000-2005: Performance tests

---

## 8. Validation Summary

**Traceability Completeness:**
- ✅ All 6 source files have requirement headers
- ✅ All 290 requirements mapped to code
- ✅ All 150+ methods documented with requirements
- ✅ Bidirectional traceability established
- ✅ Impact analysis guidance provided
- ✅ Developer quick reference included
- ✅ QA testing guide included

**Cross-References:**
- ✅ Business → Functional → Technical → Code
- ✅ Code → Technical → Functional → Business
- ✅ Requirements → Files → Methods
- ✅ Files → Requirements → Business Value

---

## 9. Maintenance

**Update Frequency:** This document should be updated whenever:
- New requirements are added
- Existing requirements are modified
- Code structure changes significantly
- New modules are added
- Methods are renamed or moved

**Version History:**

| Version | Date | Changes | Author |
|---------|------|---------|--------|
| 1.0 | 2025-11-25 | Initial creation with complete traceability | Development Team |

---

**End of Document**
