# Stock Analysis System

This directory contains the **Stock Analysis System** - a comprehensive stock analysis platform with AI-powered multi-dimensional analysis.

## Overview

The Stock Analysis System is a PHP-based web application with Python AI integration for analyzing stocks across four dimensions (fundamental, technical, momentum, sentiment) and providing automated buy/sell/hold recommendations.

## Directory Structure

```
Stock-Analysis/
├── app/                      # PHP MVC Application (Primary)
│   ├── Controllers/          # Request handling, routing
│   ├── Services/             # Business logic
│   ├── Repositories/         # Database access
│   ├── Models/               # Domain entities
│   ├── Views/                # UI templates
│   ├── Middleware/           # Request middleware
│   └── Core/                 # MVC framework core
│
├── api/                      # REST API Endpoints
│   └── market-factors.php    # Market factors API
│
├── python_analysis/          # Python AI Module (Helper)
│   ├── analysis.py           # AI/statistical analysis
│   └── README.md             # Python module documentation
│
├── database/                 # Database Scripts & Schema
│   └── schema.sql            # Database schema
│
├── web_ui/                   # Legacy Web UI (being refactored → app/)
│   └── [legacy PHP files]    # Original web interface
│
└── Project_Work_Products/    # Documentation & Requirements
    ├── Requirements/         # Business, Functional, Technical requirements
    ├── Architecture/         # Architecture documentation
    ├── Traceability/         # Requirements traceability
    └── archived_python_code/ # Old Python implementation (deprecated)
```

## Architecture

### PHP MVC Application (Primary) - `app/`

**Purpose**: Complete web application for stock analysis and portfolio management

**Key Services:**
- `PortfolioService` - Portfolio management and tracking
- `MarketDataService` - Data fetching from APIs and database
- `StockAnalysisService` - Orchestrates stock analysis workflow
- `PythonIntegrationService` - Integrates with Python AI module
- `AuthenticationService` - User authentication
- `DataSynchronizationService` - FrontAccounting integration

**Technologies:**
- PHP 8.x
- Symfony HTTP Foundation
- MySQL 8.0+
- PDO for database access

### Python AI Module (Helper) - `python_analysis/`

**Purpose**: AI and statistical analysis calculations

**Capabilities:**
- Fundamental analysis (P/E, ROE, margins, debt ratios)
- Technical analysis (RSI, MACD, moving averages)
- Momentum analysis (returns, volatility)
- Sentiment analysis (price patterns, volume)
- Risk assessment and target price calculation

**Technologies:**
- Python 3.8+
- pandas, numpy (data manipulation)
- ta (technical analysis library)

**Integration**: PHP calls Python via shell execution with JSON data exchange

### REST API - `api/`

**Purpose**: RESTful API endpoints for external access

**Endpoints:**
- `market-factors.php` - Market factors and indicators

### Database - `database/`

**Purpose**: Database schema and migration scripts

**Database**: MySQL 8.0+ with complete schema for:
- Stock prices and fundamentals
- Portfolio positions and trades
- Analysis results and recommendations
- User accounts and permissions

### Legacy Web UI - `web_ui/`

**Status**: Being refactored into `app/` MVC structure

Contains original procedural PHP code that is gradually being migrated to the MVC architecture in `app/`.

### Project Work Products - `Project_Work_Products/`

**Purpose**: Business analysis, requirements, and project documentation

**Contents:**
- Business Requirements (17 BR)
- Functional Requirements (129 FR)
- Technical Requirements (112 TR)
- Architecture documentation
- Requirements traceability matrices
- Archived deprecated code

**Audience**: Business Analysts, Product Managers, QA, Architects

## System Features

### Multi-Dimensional Stock Analysis

Analyzes stocks across four dimensions with weighted scoring:

1. **Fundamental Analysis (40%)** - Financial health and valuation
2. **Technical Analysis (30%)** - Price patterns and indicators
3. **Momentum Analysis (20%)** - Performance and volatility
4. **Sentiment Analysis (10%)** - Market sentiment

**Output:**
- Overall score (0-100)
- Buy/Sell/Hold recommendation
- Risk level (Low/Medium/High/Very High)
- Target price calculation
- Confidence score

### Portfolio Management

- Track multiple portfolios
- Position tracking with real-time P&L
- Risk management and exposure limits
- Performance analytics
- Sector diversification monitoring

### Data Integration

- Multi-source data fetching (Yahoo Finance, Finnhub, Alpha Vantage)
- Data caching and optimization
- Historical data storage
- Real-time price updates

## Getting Started

### Prerequisites

- PHP 8.x with extensions: pdo_mysql, json, mbstring
- Python 3.8+ with packages: pandas, numpy, ta
- MySQL 8.0+
- Composer (PHP dependencies)
- Web server (Apache/Nginx)

### Installation

1. **Install PHP dependencies:**
   ```bash
   cd Stock-Analysis
   composer install
   ```

2. **Install Python dependencies:**
   ```bash
   pip install pandas numpy ta
   ```

3. **Configure database:**
   ```sql
   CREATE DATABASE stock_analysis CHARACTER SET utf8mb4;
   CREATE USER 'stock_user'@'localhost' IDENTIFIED BY 'password';
   GRANT ALL PRIVILEGES ON stock_analysis.* TO 'stock_user'@'localhost';
   ```

4. **Import schema:**
   ```bash
   mysql -u stock_user -p stock_analysis < database/schema.sql
   ```

5. **Configure application:**
   - Edit database configuration in `app/config/`
   - Set Python path in PHP config
   - Configure API keys if using external data sources

### Testing

**Test PHP-Python integration:**
```bash
php ../test_php_python_integration.php
```

**Run PHP unit tests:**
```bash
cd Stock-Analysis
vendor/bin/phpunit
```

## Usage Examples

### Analyze a Stock (PHP)

```php
use App\Services\StockAnalysisService;

$analysisService = new StockAnalysisService($marketDataService);

$result = $analysisService->analyzeStock('AAPL');

if ($result['success']) {
    $analysis = $result['data'];
    echo "Recommendation: {$analysis['recommendation']}";
    echo "Score: {$analysis['overall_score']}/100";
    echo "Risk: {$analysis['risk_level']}";
}
```

### Call Python Analysis Directly

```bash
python python_analysis/analysis.py analyze-file data.json
```

## Documentation

### For Developers
- **Technical Architecture**: See `../ARCHITECTURE.md`
- **Python Module API**: See `python_analysis/README.md`
- **MVC Structure**: See `Project_Work_Products/Architecture/`

### For Business/Product
- **Requirements**: See `Project_Work_Products/Requirements/`
- **Business Cases**: See `Project_Work_Products/BusinessAnalysis/`

### For QA/Testing
- **Test Plans**: See `Project_Work_Products/QualityAssurance/`
- **Traceability**: See `Project_Work_Products/Traceability/`

## Development Guidelines

### Adding New Features

**Business Logic** → Add to `app/Services/`  
**Database Operations** → Add to `app/Repositories/`  
**UI Components** → Add to `app/Controllers/` and `app/Views/`  
**AI/Statistical Analysis** → Add to `python_analysis/analysis.py`  
**API Endpoints** → Add to `api/`

### Code Organization

- Use PHP for all application logic, database, UI, portfolio, trades
- Use Python only for AI/ML and complex statistical analysis
- Keep Python stateless (input → calculate → output)
- Maintain requirements traceability in Project_Work_Products/

## Project Status

✅ **Completed:**
- PHP MVC architecture
- Python AI integration
- Multi-dimensional analysis
- Portfolio management services
- Requirements documentation (290 requirements)
- Bidirectional traceability

🔄 **In Progress:**
- Migrating web_ui/ to app/ structure
- Enhanced dashboard UI
- Real-time analysis updates

📋 **Planned:**
- Trade execution service
- Advanced ML models
- Mobile responsive UI
- API authentication

## Support & Documentation

- **Main Documentation**: `../ARCHITECTURE.md`
- **Migration Notes**: `Project_Work_Products/MIGRATION_NOTES.md`
- **Requirements**: `Project_Work_Products/Requirements/`
- **API Docs**: `api/README.md` (if exists)

## License

See LICENSE file in project root.

---

*This is the Stock Analysis System - separate from the original ChatGPT-Micro-Cap-Experiment project that was forked.*
