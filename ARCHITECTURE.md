# Stock Analysis Project - Correct Architecture

## Overview

This document defines the **correct architecture** for the Stock Analysis project after refactoring to properly separate concerns between PHP (primary application) and Python (AI helper).

**Date**: November 25, 2025  
**Version**: 2.0 (Corrected Architecture)

## Architecture Principles

### 1. PHP is the Primary Application

PHP handles **ALL** business logic, portfolio management, data persistence, user interface, and request handling.

### 2. Python is an AI Helper Module

Python handles **ONLY** AI/statistical analysis that requires specialized libraries (numpy, pandas, scikit-learn, etc.).

### 3. Single Source of Truth

Each piece of functionality exists in exactly **ONE** place:
- Portfolio management: PHP PortfolioService
- Trade execution: PHP (future TradeService)
- Database operations: PHP Repositories
- User interface: PHP Controllers/Views
- Data fetching: PHP MarketDataService
- Analysis calculations: Python analysis.py (called by PHP)

## System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Stock Analysis System                     │
└─────────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────────────┐
│                     PHP MVC Application                          │
│                         (PRIMARY)                                │
├──────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐            │
│  │ Controllers │  │    Views    │  │ Middleware  │            │
│  └──────┬──────┘  └─────────────┘  └─────────────┘            │
│         │                                                       │
│  ┌──────▼────────────────────────────────────────┐            │
│  │              Services Layer                    │            │
│  │  ┌──────────────────────────────────────┐    │            │
│  │  │ PortfolioService                     │    │            │
│  │  │  - Portfolio management              │    │            │
│  │  │  - Position tracking                 │    │            │
│  │  │  - Performance calculations          │    │            │
│  │  └──────────────────────────────────────┘    │            │
│  │  ┌──────────────────────────────────────┐    │            │
│  │  │ MarketDataService                    │    │            │
│  │  │  - Data fetching (APIs, DB)          │    │            │
│  │  │  - Data caching                      │    │            │
│  │  │  - Historical data management        │    │            │
│  │  └──────────────────────────────────────┘    │            │
│  │  ┌──────────────────────────────────────┐    │            │
│  │  │ StockAnalysisService                 │    │            │
│  │  │  - Orchestrates analysis             │    │            │
│  │  │  - Fetches data from MarketData      │    │            │
│  │  │  - Calls Python for AI analysis ─────┼────┼───────┐   │
│  │  │  - Applies business rules            │    │       │   │
│  │  │  - Formats results                   │    │       │   │
│  │  └──────────────────────────────────────┘    │       │   │
│  │  ┌──────────────────────────────────────┐    │       │   │
│  │  │ PythonIntegrationService             │    │       │   │
│  │  │  - Executes Python scripts           │◄───┼───────┘   │
│  │  │  - Handles JSON serialization        │    │           │
│  │  │  - Error handling and logging        │    │           │
│  │  └──────────────────────────────────────┘    │           │
│  └───────────────────────────────────────────────┘           │
│  ┌──────────────────────────────────────────────┐            │
│  │           Repositories Layer                 │            │
│  │  - PortfolioRepository                       │            │
│  │  - MarketDataRepository                      │            │
│  │  - AnalysisRepository                        │            │
│  └──────────────────────────────────────────────┘            │
│  ┌──────────────────────────────────────────────┐            │
│  │              Models Layer                    │            │
│  │  - Portfolio, Position, Stock, Analysis     │            │
│  └──────────────────────────────────────────────┘            │
└──────────────────────┬───────────────────────────────────────┘
                       │
                       │ JSON via exec()
                       │
┌──────────────────────▼───────────────────────────────────────┐
│              Python Analysis Module                          │
│                    (AI HELPER)                               │
├──────────────────────────────────────────────────────────────┤
│  python_analysis/analysis.py                                 │
│  ┌────────────────────────────────────────────┐             │
│  │ StockAnalyzer                              │             │
│  │  - analyze_stock()                         │             │
│  │  - calculate_fundamental_score()           │             │
│  │  - calculate_technical_score()             │             │
│  │  - calculate_momentum_score()              │             │
│  │  - calculate_sentiment_score()             │             │
│  │  - assess_risk()                           │             │
│  │  - calculate_target_price()                │             │
│  └────────────────────────────────────────────┘             │
│  Uses: numpy, pandas, ta, scikit-learn (optional)           │
└──────────────────────────────────────────────────────────────┘
```

## Component Responsibilities

### PHP Components

#### Controllers (app/Controllers/)
- Handle HTTP requests
- Route to appropriate services
- Return views or JSON responses
- Session/authentication management

**Examples:**
- `DashboardController.php` - Dashboard display
- `BankImportController.php` - Bank transaction imports
- Future: `AnalysisController.php`, `TradeController.php`

#### Services (app/Services/)

**PortfolioService.php**
- Manages user portfolios
- Tracks positions and holdings
- Calculates portfolio performance
- Handles portfolio analytics
- **Does NOT**: Analysis calculations (delegates to StockAnalysisService)

**MarketDataService.php**
- Fetches stock prices from APIs/database
- Manages historical data
- Caches market data
- Provides data to other services
- **Does NOT**: Analyze data (just provides it)

**StockAnalysisService.php** (NEW)
- **Orchestrates** stock analysis workflow
- Fetches data from MarketDataService
- Calls Python via PythonIntegrationService
- Applies business rules to analysis results
- Formats results for display/storage
- Caches analysis results
- **Does NOT**: Perform calculations (delegates to Python)

**PythonIntegrationService.php** (ENHANCED)
- Executes Python scripts via shell
- Handles JSON serialization/deserialization
- Manages error handling
- Provides clean API for calling Python
- **Does NOT**: Contain business logic

**AuthenticationService.php**
- User authentication
- Session management
- Authorization

**DataSynchronizationService.php**
- Syncs data between systems
- FrontAccounting integration

#### Repositories (app/Repositories/)
- Database access layer
- CRUD operations
- Query builders
- **Does NOT**: Business logic

#### Models (app/Models/)
- Domain entities
- Data validation
- Relationships
- **Does NOT**: Business logic or calculations

#### Views (app/Views/)
- HTML templates
- Display formatting
- User interface
- **Does NOT**: Business logic

### Python Components

#### python_analysis/analysis.py

**Purpose**: ONLY AI/statistical analysis

**What it does:**
- ✅ Advanced statistical calculations (numpy)
- ✅ Technical indicator computations (RSI, MACD, Bollinger Bands)
- ✅ Multi-dimensional scoring (fundamental, technical, momentum, sentiment)
- ✅ Risk assessment using volatility metrics
- ✅ Target price calculations
- ✅ Machine learning predictions (optional, using scikit-learn)

**What it does NOT do:**
- ❌ Portfolio management
- ❌ Trade execution
- ❌ Database operations
- ❌ Data fetching from APIs
- ❌ User authentication
- ❌ Business rules enforcement
- ❌ UI/presentation logic

**Interface:**
```bash
python python_analysis/analysis.py analyze '{
  "symbol": "AAPL",
  "price_data": [...],
  "fundamentals": {...},
  "scoring_weights": {...}
}'
```

**Output:**
```json
{
  "symbol": "AAPL",
  "overall_score": 72.5,
  "recommendation": "BUY",
  "fundamental_score": 75.0,
  "technical_score": 68.0,
  "momentum_score": 70.0,
  "sentiment_score": 80.0,
  "risk_level": "MEDIUM",
  "target_price": 185.50,
  "confidence": 85.0,
  "details": {...}
}
```

## Data Flow

### Stock Analysis Workflow

```
1. User Request
   ↓
2. PHP Controller receives request
   ↓
3. PHP StockAnalysisService.analyzeStock(symbol)
   │
   ├─→ 4. Fetch data from MarketDataService
   │   ├─→ Check cache
   │   ├─→ Query database
   │   └─→ Call external APIs if needed
   │
   ├─→ 5. Prepare data for Python
   │   └─→ Format: {symbol, price_data, fundamentals, weights}
   │
   ├─→ 6. Call Python via PythonIntegrationService
   │   ├─→ Execute: python analysis.py analyze '{...}'
   │   ├─→ Python performs AI/statistical analysis
   │   │   ├─→ Calculate fundamental score
   │   │   ├─→ Calculate technical score
   │   │   ├─→ Calculate momentum score
   │   │   ├─→ Calculate sentiment score
   │   │   ├─→ Assess risk
   │   │   └─→ Generate recommendation
   │   └─→ Return JSON result
   │
   ├─→ 7. Enhance results in PHP
   │   ├─→ Apply business rules
   │   ├─→ Format for display
   │   └─→ Add metadata
   │
   └─→ 8. Cache/persist results
       └─→ Store in database
   
9. Return to Controller
   ↓
10. Render View or return JSON
    ↓
11. Response to User
```

### Portfolio Dashboard Workflow

```
1. User accesses dashboard
   ↓
2. DashboardController.index()
   ↓
3. PortfolioService.getDashboardData(userId)
   │
   ├─→ Get portfolio holdings
   ├─→ Get current prices (MarketDataService)
   ├─→ Calculate performance
   └─→ Get recent activity
   ↓
4. Render dashboard view
   ↓
5. Display to user
```

## Directory Structure

```
ChatGPT-Micro-Cap-Experiment/
│
├── app/                              # PHP MVC Application
│   ├── Controllers/
│   │   ├── Web/
│   │   │   ├── DashboardController.php
│   │   │   └── BankImportController.php
│   │   ├── Api/ (future)
│   │   └── BaseController.php
│   │
│   ├── Services/
│   │   ├── PortfolioService.php           # Portfolio management
│   │   ├── MarketDataService.php          # Data fetching
│   │   ├── StockAnalysisService.php       # Analysis orchestration (NEW)
│   │   ├── PythonIntegrationService.php   # Python bridge (ENHANCED)
│   │   ├── AuthenticationService.php
│   │   ├── DataSynchronizationService.php
│   │   └── Interfaces/
│   │
│   ├── Repositories/
│   │   ├── PortfolioRepository.php
│   │   ├── MarketDataRepository.php
│   │   └── Interfaces/
│   │
│   ├── Models/
│   │   ├── Portfolio.php
│   │   ├── Position.php
│   │   ├── Stock.php
│   │   └── Analysis.php
│   │
│   ├── Views/
│   │   ├── dashboard.php
│   │   ├── portfolio.php
│   │   └── layouts/
│   │
│   ├── Middleware/
│   └── Core/
│       ├── Application.php
│       ├── Router.php
│       ├── Request.php
│       └── Response.php
│
├── python_analysis/                  # Python AI Module
│   ├── analysis.py                   # AI/statistical analysis ONLY
│   └── README.md                     # Python module documentation
│
├── Stock-Analysis-Extension/         # DEPRECATED (archived)
│   ├── MIGRATION_NOTES.md            # Explains what happened
│   ├── archived_python_code/         # Old Python code (archived)
│   │   ├── main.py
│   │   └── modules/
│   └── ProjectDocuments/             # Requirements docs (to be updated)
│
├── web_ui/                           # Legacy code (being refactored)
│   ├── includes/
│   ├── views/
│   └── *.php
│
├── Scripts and CSV Files/            # Data files
│   ├── chatgpt_portfolio_update.csv
│   └── chatgpt_trade_log.csv
│
├── database/                         # Database scripts
│   └── schema.sql
│
└── public/                           # Web root
    └── index.php                     # Entry point
```

## Integration Points

### PHP → Python

**Method**: Shell execution via `exec()`

**Format**: JSON input/output

**Example**:
```php
// In StockAnalysisService.php
$pythonService = new PythonIntegrationService();

$input = [
    'symbol' => 'AAPL',
    'price_data' => $priceArray,
    'fundamentals' => $fundamentalsArray
];

$result = $pythonService->analyzeStock($input);

if ($result['success']) {
    $analysis = $result['data'];
    // Use $analysis['overall_score'], etc.
}
```

### Python → PHP

Python returns JSON to stdout, which PHP captures and parses.

### Database

PHP handles ALL database operations via Repositories. Python never touches the database.

## Requirements Mapping

### Business Requirements → Implementation

| Requirement | PHP Implementation | Python Implementation |
|------------|-------------------|----------------------|
| BR-001: Automated stock analysis | StockAnalysisService orchestrates | analysis.py calculates scores |
| BR-002: Data-driven recommendations | StockAnalysisService formats | analysis.py generates recommendation |
| BR-003: Portfolio management | PortfolioService | None |
| BR-010: Professional analysis | StockAnalysisService + analysis.py | calculate_*_score() methods |
| BR-020: Multi-stock analysis | StockAnalysisService.analyzeMultiple() | analysis.py (called per stock) |

### Functional Requirements → Implementation

| Category | PHP | Python |
|----------|-----|--------|
| FR-100-107: Data Acquisition | MarketDataService | None |
| FR-200-706: Analysis Calculations | None | analysis.py |
| FR-800-1106: Portfolio Management | PortfolioService | None |
| FR-1200-1306: Database Operations | Repositories | None |
| FR-1400-1406: FrontAccounting | DataSynchronizationService | None |
| FR-1500-1505: User Interface | Controllers/Views | None |

## Technologies

### PHP Stack
- **PHP 8.x**: Primary application language
- **Symfony HTTP Foundation**: Request/Response handling
- **MySQL**: Database
- **PDO**: Database access
- **Composer**: Dependency management

### Python Stack
- **Python 3.8+**: Analysis calculations
- **pandas**: Data manipulation
- **numpy**: Numerical calculations
- **ta**: Technical analysis indicators
- **scikit-learn**: Machine learning (optional)

## Development Guidelines

### When to Use PHP
Use PHP for:
- ✅ Web requests and routing
- ✅ Business logic and rules
- ✅ Database operations
- ✅ Portfolio management
- ✅ Trade execution
- ✅ User authentication
- ✅ Data fetching and caching
- ✅ UI rendering
- ✅ API endpoints

### When to Use Python
Use Python for:
- ✅ AI/Machine learning models
- ✅ Complex statistical analysis
- ✅ Technical indicator calculations
- ✅ Data science workflows
- ✅ Anything requiring numpy/pandas/scipy/scikit-learn

### Integration Best Practices

1. **PHP orchestrates, Python calculates**
   - PHP decides what to analyze and when
   - Python performs the calculations
   - PHP uses the results for business decisions

2. **Keep Python stateless**
   - Python receives all needed data in input
   - Python returns all results in output
   - No session, no database, no side effects

3. **Error handling**
   - Python catches exceptions and returns error in JSON
   - PHP checks for errors and handles gracefully
   - Log errors on both sides

4. **Performance**
   - Cache analysis results in PHP/database
   - Don't call Python unnecessarily
   - Consider async/queue for batch analysis

## Migration from Old Architecture

### What Changed

**Before (WRONG)**:
- Stock-Analysis-Extension/ was a complete Python application
- Duplicated portfolio management, DB operations, business logic
- PHP and Python both doing the same things differently

**After (CORRECT)**:
- python_analysis/ is a small AI helper module
- PHP is the primary application
- Clear separation: PHP = app logic, Python = AI calculations

### Migration Steps Completed

1. ✅ Created python_analysis/analysis.py with ONLY AI analysis
2. ✅ Created StockAnalysisService.php to orchestrate analysis
3. ✅ Enhanced PythonIntegrationService.php
4. ✅ Archived old Stock-Analysis-Extension Python code
5. ✅ Created architecture documentation

### Remaining Work

1. ⏳ Update requirements documentation
2. ⏳ Test PHP-to-Python integration
3. ⏳ Create unit tests
4. ⏳ Update development setup instructions
5. ⏳ Document API endpoints

## Testing Strategy

### PHP Tests
- Unit tests for Services
- Integration tests for Controllers
- Repository tests with test database
- Mock Python responses for testing

### Python Tests
- Unit tests for each analysis method
- Test with sample stock data
- Verify JSON input/output format

### Integration Tests
- Test PHP calling Python end-to-end
- Test error handling
- Test performance with real data

## Deployment

### Requirements
- PHP 8.x with required extensions
- Python 3.8+ with pandas, numpy, ta
- MySQL 8.0+
- Web server (Apache/Nginx)

### Setup Steps
1. Install PHP dependencies: `composer install`
2. Install Python dependencies: `pip install pandas numpy ta`
3. Configure database in app/config
4. Set Python path in PHP config
5. Verify Python integration: `php test_python.php`

## Conclusion

This architecture provides:
- ✅ Clear separation of concerns
- ✅ Single source of truth for each feature
- ✅ Maintainable codebase
- ✅ Scalable design
- ✅ Proper use of each technology's strengths

PHP handles the application, Python handles the AI. Simple and correct.
