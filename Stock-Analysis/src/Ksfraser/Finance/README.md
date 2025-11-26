# Finance Package README

## SOLID Finance Architecture

This Finance package implements a clean, SOLID architecture for stock market data management with the following features:

### 🏗️ Architecture Principles

- **Single Responsibility Principle**: Each class has one reason to change
- **Open/Closed Principle**: Easily extensible with new data sources and LLM providers
- **Liskov Substitution Principle**: All implementations are interchangeable
- **Interface Segregation Principle**: Minimal, focused interfaces
- **Dependency Injection**: Full dependency injection for testability

### 📦 Components

#### Interfaces
- `DataSourceInterface` - Contract for stock data sources
- `DataRepositoryInterface` - Contract for data persistence
- `LLMProviderInterface` - Contract for AI analysis providers

#### Data Sources
- `YahooFinanceDataSource` - Free Yahoo Finance API integration
- `AlphaVantageDataSource` - Alpha Vantage API integration (requires API key)

#### Repositories
- `DatabaseRepository` - PDO-based database persistence

#### LLM Providers
- `OpenAIProvider` - ChatGPT integration for financial analysis

#### Services
- `StockDataService` - Core business logic for stock operations

#### Controllers
- `StockController` - HTTP request handling (MVC pattern)

### 🚀 Quick Start

1. **Configure Database and APIs**:
   The Finance package uses two separate configuration files for security:
   
   **Database Configuration (`db_config.yml`)**:
   ```yaml
   # Uses your existing database configuration
   database:
     legacy:
       database: stock_market  # Finance package uses this
   ```
   
   **API Configuration (`api_config.yml`)**:
   ```yaml
   # Copy api_config.example.yml to api_config.yml
   stock_apis:
     alphavantage:
       api_key: "your_alpha_vantage_key"
   
   ai_apis:
     openai:
       api_key: "your_openai_key"
       model: "gpt-4"
   
   finance:
     rate_limiting:
       delay_between_requests: 500000
     general:
       max_retries: 3
       bulk_update_limit: 100
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Basic Usage**:
   ```php
   require_once 'web_ui/FinanceIntegration.php';
   
   $finance = new FinanceIntegration();
   $stockController = $finance->getStockController();
   
   // Update stock price
   $result = $stockController->updateStock('AAPL');
   
   // Get AI analysis
   $analysis = $stockController->getAnalysis('AAPL');
   
   // Bulk update
   $bulkResult = $stockController->bulkUpdate(['AAPL', 'GOOGL', 'MSFT']);
   ```

### 🔌 API Endpoints

The package includes a web API interface:

- `GET /overview` - Market overview
- `GET /stock/{SYMBOL}/analysis` - AI analysis
- `GET /stock/{SYMBOL}/history?days=30` - Historical data
- `POST /update` - Update stock data

### 🛠️ Configuration

The Finance package uses a **secure two-file configuration approach**:

**1. Database Configuration (`db_config.yml`)**
- Uses your existing `DatabaseConfig.php` system
- Contains only database connection settings
- No API keys or sensitive external service data

**2. API Configuration (`api_config.yml`)**  
- Separate file for API keys and external services
- Better security - can be excluded from version control
- Contains stock APIs, AI APIs, and finance package settings

**Configuration Structure:**
```yaml
# db_config.yml (existing)
database:
  legacy:
    database: stock_market

# api_config.yml (new)
stock_apis:
  alphavantage:
    api_key: "your_key"
ai_apis:
  openai:
    api_key: "your_key"
finance:
  rate_limiting:
    delay_between_requests: 500000
```

**Benefits:**
- 🔐 **Security**: API keys separate from database config
- 🎯 **Organization**: Clear separation of concerns  
- 🔄 **Compatibility**: Works with existing DatabaseConfig system
- 🚫 **No Pollution**: Doesn't clutter database configuration

### 🔒 Security Features

- Input validation for stock symbols
- Rate limiting to prevent API abuse
- SQL injection protection via prepared statements
- API key security via environment variables

### 🧪 Testing

Run the examples to test functionality:

```bash
php src/Ksfraser/Finance/examples/basic_usage.php
```

### 📊 Database Tables Required

The package expects these tables in your `stock_market` database:

- `stock_prices` - Current and historical stock prices
- `companies` - Company information
- `financial_statements` - Financial statement data

### 🔄 Integration with Existing System

This package is designed to integrate seamlessly with your existing web_ui system. The dependency injection container makes it easy to wire into your current authentication and navigation systems.

### 🎯 Features

- ✅ Multiple data source support (Yahoo Finance, Alpha Vantage)
- ✅ AI-powered financial analysis via OpenAI
- ✅ Historical data tracking
- ✅ Bulk operations for multiple stocks
- ✅ Market overview and statistics
- ✅ RESTful API interface
- ✅ Comprehensive error handling
- ✅ Rate limiting and retry mechanisms
- ✅ Full SOLID architecture compliance
