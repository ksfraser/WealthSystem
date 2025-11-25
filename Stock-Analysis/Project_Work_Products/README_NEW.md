# Stock Analysis Project Documentation

## ⚠️ IMPORTANT: Architecture Changed (November 25, 2025)

**This directory previously contained a complete Python application. The architecture has been corrected.**

### What Changed

**Old (Incorrect) Architecture:**
- This directory contained a full Python application with portfolio management, database operations, and business logic
- Duplicated functionality that should have been in PHP

**New (Correct) Architecture:**
- **PHP MVC Application** (`app/`) = Primary application
  - Handles ALL business logic, portfolio, trades, database, UI
- **Python AI Module** (`python_analysis/`) = Helper for AI/statistical analysis
  - Handles ONLY calculations that require numpy/pandas/scikit-learn

**See**: [MIGRATION_NOTES.md](MIGRATION_NOTES.md) for complete migration details

---

## Current Project Structure

### PHP Application (Primary) - `/app`

**Purpose**: Complete stock analysis web application

**Components:**
- `Controllers/` - Request handling, routing
- `Services/` - Business logic
  - `PortfolioService.php` - Portfolio management
  - `MarketDataService.php` - Data fetching/caching
  - `StockAnalysisService.php` - Analysis orchestration
  - `PythonIntegrationService.php` - Calls Python AI module
- `Repositories/` - Database operations
- `Models/` - Domain entities
- `Views/` - User interface templates

**Technologies:**
- PHP 8.x
- Symfony HTTP Foundation
- MySQL
- PDO

### Python AI Module (Helper) - `/python_analysis`

**Purpose**: AI and statistical analysis calculations ONLY

**Files:**
- `analysis.py` - Stock analysis calculations
  - `calculate_fundamental_score()` - Financial metrics
  - `calculate_technical_score()` - RSI, MACD, indicators
  - `calculate_momentum_score()` - Returns, volatility
  - `calculate_sentiment_score()` - Price patterns
  - `assess_risk()` - Risk classification

**Technologies:**
- Python 3.8+
- pandas, numpy (data manipulation)
- ta (technical analysis)
- scikit-learn (optional, for ML)

**Interface:**
```bash
python python_analysis/analysis.py analyze '{json_data}'
```

### Documentation (This Directory) - `/Stock-Analysis-Extension`

**Purpose**: Requirements, design, and project documentation

**Structure:**
```
Stock-Analysis-Extension/
├── README.md (this file)
├── MIGRATION_NOTES.md (architecture change explanation)
├── archived_python_code/ (old Python code, preserved)
└── ProjectDocuments/
    ├── Requirements/
    │   ├── BUSINESS_REQUIREMENTS.md
    │   ├── FUNCTIONAL_REQUIREMENTS.md
    │   └── TECHNICAL_REQUIREMENTS.md
    ├── Architecture/
    │   └── MVC_ARCHITECTURE_DOCUMENTATION.md
    ├── Traceability/
    │   ├── REQUIREMENTS_TRACEABILITY_MATRIX.md
    │   └── CODE_TO_REQUIREMENTS_XREF.md
    └── README.md (documentation navigation)
```

---

## System Overview

### What the System Does

The Stock Analysis System provides:

1. **Multi-Dimensional Stock Analysis**
   - Fundamental analysis (40% weight): P/E, ROE, margins, debt
   - Technical analysis (30% weight): RSI, MACD, moving averages
   - Momentum analysis (20% weight): Returns, volatility
   - Sentiment analysis (10% weight): Price patterns, volume

2. **AI-Powered Recommendations**
   - BUY/SELL/HOLD recommendations
   - Target price calculations
   - Risk assessment (LOW/MEDIUM/HIGH/VERY_HIGH)
   - Confidence scoring

3. **Portfolio Management**
   - Track positions and holdings
   - Calculate performance
   - Monitor risk exposure
   - Generate reports

4. **Market Data Management**
   - Fetch from multiple sources (Yahoo Finance, APIs)
   - Cache data for performance
   - Historical data storage
   - Real-time price updates

### How It Works

```
User Request
    ↓
PHP Controller (app/Controllers/)
    ↓
StockAnalysisService (app/Services/)
    │
    ├─→ Fetch data: MarketDataService
    │   └─→ Get prices, fundamentals
    │
    ├─→ AI Analysis: PythonIntegrationService
    │   └─→ Call python_analysis/analysis.py
    │       └─→ Calculate scores & recommendation
    │
    ├─→ Apply business rules (PHP)
    │   └─→ Format, validate, enhance
    │
    └─→ Store results: Repository
        └─→ Cache in database
    ↓
Return to Controller
    ↓
Render View or JSON Response
    ↓
User sees results
```

---

## Analysis Features

### Four-Dimensional Analysis

#### 1. Fundamental Analysis (40%)

Evaluates company financial health:
- **Valuation**: P/E ratio, P/B ratio, P/S ratio
- **Profitability**: ROE, ROA, profit margins
- **Financial Health**: Debt-to-equity, current ratio
- **Growth**: Revenue growth, earnings growth

**Scoring**: 0-100 based on financial strength

#### 2. Technical Analysis (30%)

Analyzes price patterns and indicators:
- **Moving Averages**: 20, 50, 200-day MAs
- **Momentum**: RSI (Relative Strength Index)
- **Trend**: MACD, Bollinger Bands
- **Volume**: Trading volume patterns

**Scoring**: 0-100 based on technical signals

#### 3. Momentum Analysis (20%)

Measures price momentum:
- **Short-term**: 1-month returns
- **Medium-term**: 3-month returns
- **Long-term**: 6-12 month returns
- **Volatility**: Risk-adjusted returns

**Scoring**: 0-100 based on momentum strength

#### 4. Sentiment Analysis (10%)

Gauges market sentiment:
- **Price Patterns**: Recent performance
- **Volume Trends**: Interest levels
- **Market Cap**: Size-based risk assessment

**Scoring**: 0-100 based on sentiment indicators

### Recommendation System

**Overall Score Calculation:**
```
Overall = (Fundamental × 0.40) + 
          (Technical × 0.30) + 
          (Momentum × 0.20) + 
          (Sentiment × 0.10)
```

**Recommendations:**
- **STRONG_BUY**: Score ≥ 80
- **BUY**: Score 65-79
- **HOLD**: Score 35-64
- **SELL**: Score 20-34
- **STRONG_SELL**: Score < 20

**Risk Levels:**
- **LOW**: Safe, stable investment
- **MEDIUM**: Moderate risk
- **HIGH**: Significant risk factors
- **VERY_HIGH**: High volatility/risk

---

## Requirements Documentation

This project has comprehensive requirements documentation in `ProjectDocuments/`:

### Requirements Hierarchy

1. **Business Requirements** (17 requirements)
   - High-level business objectives
   - Stakeholder needs
   - Success criteria

2. **Functional Requirements** (129 requirements)
   - Detailed feature specifications
   - User-facing capabilities
   - System behaviors

3. **Technical Requirements** (112 requirements)
   - Implementation specifications
   - Database schema
   - API integrations
   - Performance requirements

**Total**: 290 requirements (285 implemented, 5 planned)

### Traceability

The project maintains bidirectional traceability:

- **Forward**: Business Req → Functional Req → Technical Req → Code
- **Backward**: Code → Technical Req → Functional Req → Business Req

**Documents:**
- `REQUIREMENTS_TRACEABILITY_MATRIX.md` - Forward tracing
- `CODE_TO_REQUIREMENTS_XREF.md` - Backward tracing

---

## Technology Stack

### PHP Stack (Primary Application)

- **PHP**: 8.x
- **Framework**: Custom MVC with Symfony HTTP Foundation
- **Database**: MySQL 8.0+
- **Data Access**: PDO
- **Dependencies**: Composer

### Python Stack (AI Module)

- **Python**: 3.8+
- **Data Libraries**: pandas, numpy
- **Analysis**: ta (technical analysis)
- **ML**: scikit-learn (optional)
- **Dependencies**: pip

### Integration

- **Method**: Shell execution (`exec()`)
- **Format**: JSON input/output
- **Direction**: PHP calls Python, Python returns results

---

## Getting Started

### Prerequisites

1. **PHP 8.x** with extensions: pdo_mysql, json, mbstring
2. **Python 3.8+**
3. **MySQL 8.0+**
4. **Composer** (PHP dependencies)
5. **pip** (Python dependencies)

### Installation

#### 1. Install PHP Dependencies
```bash
cd /path/to/project
composer install
```

#### 2. Install Python Dependencies
```bash
pip install pandas numpy ta
```

Optional ML libraries:
```bash
pip install scikit-learn
```

#### 3. Configure Database
```sql
CREATE DATABASE stock_analysis CHARACTER SET utf8mb4;
CREATE USER 'stock_user'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON stock_analysis.* TO 'stock_user'@'localhost';
```

#### 4. Configure Application
Edit `app/config/database.php` with your credentials.

#### 5. Verify Python Integration
```bash
cd python_analysis
python analysis.py analyze '{"symbol":"TEST","price_data":[],"fundamentals":{}}'
```

Should return JSON with analysis results.

### Usage

#### Analyze a Stock (PHP)

```php
use App\Services\StockAnalysisService;

$analysisService = new StockAnalysisService($marketDataService);

$result = $analysisService->analyzeStock('AAPL');

if ($result['success']) {
    $analysis = $result['data'];
    echo "Recommendation: " . $analysis['recommendation'];
    echo "Score: " . $analysis['overall_score'];
    echo "Risk: " . $analysis['risk_level'];
}
```

#### Call Python Directly (Testing)

```bash
python python_analysis/analysis.py analyze '{
  "symbol": "AAPL",
  "price_data": [...],
  "fundamentals": {...}
}'
```

---

## Project Status

### Completed ✅

- ✅ PHP MVC application structure
- ✅ Portfolio management (PortfolioService)
- ✅ Market data fetching (MarketDataService)
- ✅ Python AI analysis module
- ✅ PHP-Python integration
- ✅ Requirements documentation (290 requirements)
- ✅ Bidirectional traceability
- ✅ Architecture documentation

### In Progress 🔄

- 🔄 Requirements docs need updating for new architecture
- 🔄 Integration testing (PHP ↔ Python)
- 🔄 Unit tests for analysis module
- 🔄 Dashboard UI enhancements

### Planned 📋

- 📋 Trade execution service
- 📋 FrontAccounting integration enhancement
- 📋 Real-time analysis updates
- 📋 API endpoints for external access
- 📋 Machine learning model integration

---

## Documentation

### For Developers

- **ARCHITECTURE.md** (root) - Complete system architecture
- **python_analysis/README.md** - Python module documentation
- **MIGRATION_NOTES.md** - Architecture change details

### For Business/Product

- **ProjectDocuments/README.md** - Documentation navigation
- **Requirements/BUSINESS_REQUIREMENTS.md** - Business objectives
- **Requirements/FUNCTIONAL_REQUIREMENTS.md** - Feature specifications

### For QA/Testing

- **Traceability/REQUIREMENTS_TRACEABILITY_MATRIX.md** - Test coverage
- **Traceability/CODE_TO_REQUIREMENTS_XREF.md** - Code-to-req mapping

---

## Contributing

When adding features:

1. **Business Logic** → Add to PHP Services
2. **Database Operations** → Add to PHP Repositories
3. **UI Components** → Add to PHP Controllers/Views
4. **AI/Statistical Calculations** → Add to Python analysis.py
5. **Update Requirements** → Update documentation in ProjectDocuments/

### Guidelines

- **Use PHP for**: Application logic, database, UI, portfolio, trades
- **Use Python for**: AI/ML, complex statistics, scientific computing
- **Keep Python stateless**: Input → Calculate → Output (no side effects)
- **Maintain traceability**: Link code to requirements

---

## Support

### Questions About:

- **Architecture**: See `ARCHITECTURE.md`
- **Python Module**: See `python_analysis/README.md`
- **Migration**: See `MIGRATION_NOTES.md`
- **Requirements**: See `ProjectDocuments/README.md`
- **PHP MVC**: See `app/Services/README.md` (if exists)

### Common Issues

**Q: Python module not found**  
A: Check Python path in PHP config, verify `python_analysis/analysis.py` exists

**Q: Analysis returns errors**  
A: Check Python dependencies installed: `pip install pandas numpy ta`

**Q: Where to add new analysis metrics?**  
A: Add to `python_analysis/analysis.py` (if calculation) or `StockAnalysisService.php` (if business rule)

---

## License

See LICENSE file in project root.

---

## Version History

- **2.0** (Nov 25, 2025) - Architecture refactoring: PHP primary, Python helper
- **1.0** - Initial Python-based implementation (deprecated)

---

*For the complete, up-to-date architecture, see [ARCHITECTURE.md](../ARCHITECTURE.md) in the project root.*
