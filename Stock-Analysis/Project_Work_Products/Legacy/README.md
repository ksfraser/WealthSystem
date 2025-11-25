# KsFraser StockInfo Legacy Library

A PHP library for accessing and analyzing legacy stock market data from historical MySQL database tables.

## Overview

This library provides PHP models and analysis tools for historical stock data including:

- **Stock Information**: Basic stock details, prices, financial metrics
- **Price History**: Historical OHLC data and trading volumes  
- **Portfolio Management**: User portfolio holdings and performance tracking
- **Transaction Records**: Buy/sell transaction history and analysis
- **Stock Evaluations**: Investment analysis scores and ratings
- **Technical Analysis**: Moving averages, RSI, MACD, and other indicators
- **Exchange Data**: Stock exchange information and listings

## Installation

### Via Composer

```bash
composer require ksfraser/stock-info
```

### Manual Installation

1. Clone or download this repository
2. Run `composer install` in the Legacy directory
3. Include the autoloader in your project:

```php
require_once 'vendor/autoload.php';
```

## Configuration

Create a database configuration array:

```php
$databaseConfig = [
    'host' => 'your-mysql-host',
    'database' => 'your-database-name',
    'username' => 'your-username',
    'password' => 'your-password'
];
```

## Basic Usage

### Initialize the Library

```php
use Ksfraser\StockInfo\StockInfoManager;

$stockManager = new StockInfoManager($databaseConfig);
```

### Get Stock Information

```php
// Find a stock by symbol
$stock = $stockManager->stockInfo()->findBySymbol('AAPL');

// Get comprehensive stock analysis
$analysis = $stockManager->getStockAnalysis('AAPL');

// Search stocks by company name
$stocks = $stockManager->stockInfo()->searchByCompanyName('Apple');
```

### Portfolio Analysis

```php
// Get user portfolio
$portfolio = $stockManager->getPortfolioAnalysis('username');

// Get portfolio summary
$summary = $stockManager->portfolio()->getUserPortfolioSummary('username');

// Get dividend summary
$dividends = $stockManager->portfolio()->getDividendSummary('username');
```

### Price History and Technical Analysis

```php
// Get price history
$prices = $stockManager->stockPrices()->getPriceHistory('AAPL', '2023-01-01', '2023-12-31');

// Get latest technical analysis
$technical = $stockManager->technicalAnalysis()->getLatestAnalysis('AAPL');

// Get stocks with golden cross signal
$goldenCross = $stockManager->technicalAnalysis()->getGoldenCrossStocks();
```

### Transaction History

```php
// Get user transactions
$transactions = $stockManager->transaction()->getUserTransactions('username');

// Get transaction summary
$summary = $stockManager->transaction()->getUserTransactionSummary('username');

// Get cost basis for a position
$costBasis = $stockManager->transaction()->getCostBasis('username', 'AAPL');
```

### Stock Evaluations

```php
// Get top rated stocks
$topStocks = $stockManager->evalSummary()->getTopRatedStocks(20);

// Get evaluation for a specific stock
$evaluation = $stockManager->evalSummary()->getEvaluationWithStockInfo($stockId);

// Search by evaluation criteria
$criteria = [
    'min_total_score' => 10,
    'min_margin_safety' => 100000,
    'max_price' => 50
];
$results = $stockManager->evalSummary()->searchByEvaluationCriteria($criteria);
```

### Market Overview

```php
// Get comprehensive market overview
$overview = $stockManager->getMarketOverview();

// Get market statistics
$stats = $stockManager->stockInfo()->getStatistics();
```

## Advanced Features

### Custom Searches

```php
$searchCriteria = [
    'search_term' => 'technology',
    'market_cap_min' => 1000000000,
    'min_dividend_yield' => 3.0,
    'max_pe_ratio' => 15.0,
    'evaluation' => [
        'min_total_score' => 12,
        'min_margin_safety' => 500000
    ]
];

$results = $stockManager->searchStocks($searchCriteria);
```

### Technical Indicators

```php
// Get oversold stocks (RSI < 30)
$oversold = $stockManager->technicalAnalysis()->getOversoldStocks(30);

// Get stocks above 20-day moving average
$aboveSMA = $stockManager->technicalAnalysis()->getStocksAboveMovingAverages('sma_20');

// Get MACD buy signals
$macdBuys = $stockManager->technicalAnalysis()->getMACDBuySignals();
```

### Performance Analysis

```php
// Get portfolio performance
$performance = $stockManager->portfolio()->getPerformanceAnalysis('username');

// Get realized gains/losses
$realizedGains = $stockManager->transaction()->getRealizedGainsLosses('username');

// Get dividend history
$dividendHistory = $stockManager->transaction()->getDividendHistory('username');
```

## Model Classes

### Core Models

- **StockInfo**: Basic stock information and metrics
- **StockPrices**: Historical price data and OHLC information
- **Portfolio**: User portfolio holdings and valuations
- **Transaction**: Trading transaction records
- **EvalSummary**: Stock evaluation scores and analysis
- **TechnicalAnalysis**: Technical indicators and signals
- **StockExchange**: Exchange information and listings

### Base Model

All models extend the `BaseModel` class which provides:

- Standard CRUD operations (Create, Read, Update, Delete)
- Query building and execution
- Database connection management
- Common utility methods

## Database Schema

The library expects the following database tables:

- `stockinfo`: Main stock information
- `stockprices`: Historical price data
- `portfolio`: User portfolio holdings
- `transaction`: Transaction records
- `evalsummary`: Stock evaluation scores
- `technicalanalysis`: Technical analysis data
- `stockexchange`: Exchange information

## Error Handling

The library includes comprehensive error handling:

```php
try {
    $analysis = $stockManager->getStockAnalysis('INVALID');
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

## Health Check

Test database connectivity and model functionality:

```php
$healthCheck = $stockManager->healthCheck();
print_r($healthCheck);
```

## Examples

See the `examples/` directory for detailed usage examples including:

- Basic stock lookups
- Portfolio analysis reports
- Technical analysis screening
- Transaction reporting
- Evaluation-based stock screening

## Requirements

- PHP >= 7.4
- PDO MySQL extension
- MySQL/MariaDB database

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests for new functionality
5. Submit a pull request

## License

This library is released under the MIT License. See LICENSE file for details.

## Support

For questions or issues, please create an issue in the GitHub repository or contact the maintainer.

## Legacy Data Notice

This library is designed specifically for accessing legacy stock market data structures. It maintains compatibility with historical database schemas while providing modern PHP interfaces for data access and analysis.
