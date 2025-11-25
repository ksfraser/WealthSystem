# Python Analysis Module

## Purpose

This directory contains **ONLY** the AI and statistical analysis code that PHP cannot efficiently perform. All business logic, portfolio management, database operations, and trade execution are handled by the PHP MVC application.

## Architecture

```
PHP MVC Application (PRIMARY)
├── Controllers (UI, routing, request handling)
├── Services (Business logic)
│   ├── PortfolioService (portfolio management)
│   ├── MarketDataService (data fetching)
│   └── StockAnalysisService (orchestrates analysis)
│       └── PythonIntegrationService
│           └── Calls → python_analysis/analysis.py (THIS MODULE)
├── Repositories (Data access)
├── Models (Domain entities)
└── Views (UI templates)

Python Analysis Module (HELPER)
└── analysis.py
    ├── StockAnalyzer (AI/statistical analysis)
    ├── calculate_fundamental_score()
    ├── calculate_technical_score()
    ├── calculate_momentum_score()
    └── calculate_sentiment_score()
```

## What Python Does

Python handles ONLY computational analysis that benefits from scientific computing libraries:

- **Fundamental Analysis** - Financial ratio calculations and scoring
- **Technical Analysis** - RSI, MACD, Moving Averages, Bollinger Bands
- **Momentum Analysis** - Multi-period returns, volatility metrics
- **Sentiment Analysis** - Price pattern analysis, volume trends
- **Risk Assessment** - Statistical risk calculations

## What Python Does NOT Do

All of the following are handled by **PHP MVC Application**:

- ❌ Portfolio management
- ❌ Trade execution
- ❌ Database operations
- ❌ User authentication
- ❌ FrontAccounting integration
- ❌ UI rendering
- ❌ Routing/controllers
- ❌ Business rules enforcement
- ❌ Data persistence

## Dependencies

```bash
pip install pandas numpy ta
```

Optional (for better technical indicators):
```bash
pip install ta-lib
```

## Usage

### From PHP

```php
use App\Services\PythonIntegrationService;

$pythonService = new PythonIntegrationService();

$stockData = [
    'symbol' => 'AAPL',
    'price_data' => $priceArray,  // Array of price records
    'fundamentals' => $fundamentalsArray,  // Financial metrics
    'scoring_weights' => [
        'fundamental' => 0.40,
        'technical' => 0.30,
        'momentum' => 0.20,
        'sentiment' => 0.10
    ]
];

$result = $pythonService->analyzeStock($stockData);

// Returns:
// {
//   "symbol": "AAPL",
//   "overall_score": 72.5,
//   "recommendation": "BUY",
//   "fundamental_score": 75.0,
//   "technical_score": 68.0,
//   "momentum_score": 70.0,
//   "sentiment_score": 80.0,
//   "risk_level": "MEDIUM",
//   "target_price": 185.50,
//   "confidence": 85.0,
//   "details": { ... }
// }
```

### From Command Line (for testing)

```bash
python python_analysis/analysis.py analyze '{
  "symbol": "AAPL",
  "price_data": [
    {"date": "2024-01-01", "open": 150, "high": 155, "low": 149, "close": 154, "volume": 1000000}
  ],
  "fundamentals": {
    "pe_ratio": 28.5,
    "price_to_book": 5.2,
    "return_on_equity": 0.25,
    "debt_to_equity": 0.45,
    "profit_margin": 0.22,
    "market_cap": 2500000000000
  }
}'
```

## API

### analyze_stock(stock_data)

**Input:**
```python
{
    'symbol': str,              # Stock ticker
    'price_data': [             # List of price dictionaries
        {
            'date': str,        # ISO date format
            'open': float,
            'high': float,
            'low': float,
            'close': float,
            'volume': int
        }
    ],
    'fundamentals': {           # Financial metrics
        'pe_ratio': float,
        'price_to_book': float,
        'return_on_equity': float,
        'debt_to_equity': float,
        'profit_margin': float,
        'revenue_growth': float,
        'market_cap': float
    },
    'scoring_weights': {        # Optional weights
        'fundamental': 0.40,
        'technical': 0.30,
        'momentum': 0.20,
        'sentiment': 0.10
    }
}
```

**Output:**
```python
{
    'symbol': str,
    'overall_score': float,         # 0-100
    'recommendation': str,          # STRONG_BUY, BUY, HOLD, SELL, STRONG_SELL
    'fundamental_score': float,     # 0-100
    'technical_score': float,       # 0-100
    'momentum_score': float,        # 0-100
    'sentiment_score': float,       # 0-100
    'risk_level': str,              # LOW, MEDIUM, HIGH, VERY_HIGH
    'target_price': float,
    'current_price': float,
    'confidence': float,            # 0-100
    'analysis_date': str,           # ISO timestamp
    'details': {                    # Detailed breakdown
        'fundamental': {...},
        'technical': {...},
        'momentum': {...},
        'sentiment': {...}
    }
}
```

## Files

- `analysis.py` - Main analysis module with StockAnalyzer class
- `README.md` - This file

## Integration Points

The PHP application calls this module via `PythonIntegrationService`:

1. **app/Services/StockAnalysisService.php** - Orchestrates stock analysis
2. **app/Services/PythonIntegrationService.php** - Calls Python scripts
3. **python_analysis/analysis.py** - Performs AI/statistical analysis

## Migration Notes

This module was created to establish the correct architecture:

**OLD (Incorrect):**
- Stock-Analysis-Extension/ had portfolio management, DB ops, trade execution in Python
- PHP was calling Python for everything
- Massive duplication between PHP and Python

**NEW (Correct):**
- Python ONLY does AI/statistical analysis
- PHP handles ALL business logic, portfolio, trades, DB, UI
- Python is a helper module called by PHP when needed
- Clean separation of concerns
