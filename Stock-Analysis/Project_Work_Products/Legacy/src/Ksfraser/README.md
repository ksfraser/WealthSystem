# Investment Methodology Analyzers

This directory contains specialized analysis modules for different investment methodologies based on your legacy stock market database.

## Available Methodologies

### 1. Motley Fool Analyzer (`MotleyFool/`)
Analyzes stocks based on Motley Fool's 10-point investment criteria:
- **Business Model**: Simple and understandable business
- **Valuation**: Reasonable valuation relative to earnings
- **Focus**: Core business focus
- **Growth**: Double-digit sales growth
- **Cash Flow**: Rising cash flow trends
- **Book Value**: Strong book value fundamentals
- **Margins**: Healthy profit margins
- **Returns**: Strong return on equity
- **Ownership**: Significant insider ownership
- **Dividends**: Regular dividend payment history

### 2. Warren Buffett Analyzer (`WarrenBuffett/`)
Implements Warren Buffett's investment tenets and valuation methodology:
- **12 Investment Tenets**: Business, management, financial, and value criteria
- **Owner Earnings**: Calculation of true economic earnings
- **Intrinsic Value**: DCF-based valuation using owner earnings
- **Margin of Safety**: Risk assessment through value vs. price comparison
- **Detailed Evaluation**: Comprehensive financial analysis

### 3. IPlace Analyzer (`IPlace/`)
Technical and fundamental analysis based on IPlace calculation methodology:
- **Growth Metrics**: Earnings growth, acceleration, sales trends
- **Valuation Metrics**: PE ratios, PEG ratios, industry comparisons
- **Momentum Indicators**: Volume surge, institutional interest, order flow
- **Quality Metrics**: Dividend analysis, debt ratios, financial health
- **Risk Assessment**: Volatility, beta, short interest analysis

### 4. Evaluation Analyzer (`Evaluation/`)
Comprehensive evaluation across four key areas:
- **Business Evaluation**: Industry characteristics and competitive position
- **Financial Evaluation**: Financial strength and profitability metrics
- **Management Evaluation**: Leadership quality and strategic decisions
- **Market Evaluation**: Valuation and market-based metrics

### 5. Unified Analyzer (`Analysis/`)
Combines all methodologies for comprehensive stock analysis:
- **Composite Scoring**: Weighted average across all methodologies
- **Consensus Recommendations**: Aggregated buy/sell/hold signals
- **Risk Assessment**: Multi-methodology risk evaluation
- **Confidence Levels**: Data availability and reliability metrics

## Usage Examples

### Basic Analysis
```php
use Ksfraser\MotleyFool\MotleyFoolAnalyzer;

$analyzer = new MotleyFoolAnalyzer();
$recommendation = $analyzer->getRecommendation($stockId);

echo "Score: {$recommendation['score']}/10\n";
echo "Recommendation: {$recommendation['recommendation']}\n";
```

### Comprehensive Analysis
```php
use Ksfraser\Analysis\UnifiedAnalyzer;

$unified = new UnifiedAnalyzer();
$analysis = $unified->getComprehensiveAnalysis($stockId);

echo "Unified Score: {$analysis['unified_score']['unified_score']}/100\n";
echo "Consensus: {$analysis['consensus_recommendation']['consensus']}\n";
```

### Stock Screening
```php
use Ksfraser\IPlace\IPlaceAnalyzer;

$iPlace = new IPlaceAnalyzer();
$topStocks = $iPlace->screenByIPlaceCriteria([
    'min_earnings_growth' => 15,
    'max_pe' => 25,
    'max_volatility' => 0.3
]);
```

## Database Tables Used

### Motley Fool
- `motleyfool`: 10 boolean criteria for investment analysis

### Warren Buffett
- `tenets`: 12 investment tenets evaluation
- `teneteval`: Detailed valuation calculations (owner earnings, margin of safety)

### IPlace
- `iplace_calc`: Comprehensive technical and fundamental metrics

### Evaluation
- `evalbusiness`: Business quality assessment
- `evalfinancial`: Financial strength analysis
- `evalmanagement`: Management quality evaluation
- `evalmarket`: Market valuation and intrinsic value

## Key Features

### Scoring Systems
- **Motley Fool**: 0-10 point scale based on criteria compliance
- **Buffett**: 0-12 tenets score + margin of safety analysis
- **IPlace**: 0-100 composite score across multiple dimensions
- **Evaluation**: 0-100 weighted score across four evaluation areas
- **Unified**: 0-100 methodology-weighted composite score

### Risk Assessment
- Multi-dimensional risk evaluation
- Volatility and beta analysis
- Fundamental risk factors
- Consensus risk ratings

### Recommendations
- Methodology-specific recommendations
- Consensus recommendations across all methods
- Confidence levels based on data availability
- Risk-adjusted recommendations

### Screening & Filtering
- Top stocks by methodology
- Custom screening criteria
- Sector analysis
- Historical trend analysis

## Data Quality & Availability

The analyzers automatically handle missing data and provide confidence levels based on:
- Number of methodologies with available data
- Recency of analysis data
- Completeness of fundamental metrics
- Historical data depth

## Installation & Setup

1. Ensure your database connection is configured in `DatabaseFactory`
2. Run `composer dump-autoload` to refresh autoloading
3. Include the appropriate analyzer classes in your code
4. See `examples/methodology_examples.php` for comprehensive usage examples

## Performance Considerations

- Database queries are optimized for large datasets
- Caching recommended for frequently accessed stocks
- Batch analysis available for portfolio-level evaluation
- Lazy loading of complex calculations

## Integration with Existing System

These analyzers extend the existing `StockInfo` library and can be used alongside:
- Legacy database models
- Existing portfolio management
- Historical analysis tools
- Reporting systems

The unified analyzer provides a single interface for accessing all methodologies while maintaining compatibility with existing code.
