# 🚀 **ChatGPT Micro-Cap Experiment API Documentation**

## 📊 **API Overview**

The ChatGPT Micro-Cap Experiment provides a comprehensive RESTful API for financial portfolio management, trading operations, and market analysis. This documentation covers all available endpoints, authentication, data models, and integration examples.

---

## 🔐 **Authentication**

### **API Authentication Methods**

#### **Session-Based Authentication**
```php
// Session authentication for web interface
POST /api/auth/login
{
    "username": "user@example.com",
    "password": "secure_password",
    "remember_me": true
}

Response:
{
    "success": true,
    "session_id": "sess_abc123...",
    "user_id": 1,
    "expires_at": "2025-09-27T10:30:00Z"
}
```

#### **API Key Authentication**
```http
GET /api/portfolios
Authorization: Bearer your_api_key_here
Content-Type: application/json
```

#### **JWT Token Authentication**
```php
// JWT token for mobile and external integrations
POST /api/auth/token
{
    "username": "user@example.com",
    "password": "secure_password"
}

Response:
{
    "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "token_type": "Bearer",
    "expires_in": 3600,
    "refresh_token": "refresh_token_here"
}
```

---

## 📁 **Core API Endpoints**

### 👤 **User Management API**

#### **User Registration**
```http
POST /api/users/register
Content-Type: application/json

{
    "username": "newuser",
    "email": "user@example.com",
    "password": "SecurePass123!",
    "first_name": "John",
    "last_name": "Doe",
    "timezone": "America/New_York"
}
```

**Response:**
```json
{
    "success": true,
    "user_id": 123,
    "message": "User registered successfully",
    "activation_required": true,
    "activation_email_sent": true
}
```

#### **User Profile Management**
```http
GET /api/users/profile
Authorization: Bearer {token}

Response:
{
    "user_id": 123,
    "username": "johnDoe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "timezone": "America/New_York",
    "created_at": "2025-01-15T10:30:00Z",
    "last_login": "2025-09-26T14:25:30Z",
    "is_active": true,
    "preferences": {
        "default_currency": "USD",
        "dashboard_layout": "grid",
        "notifications": {
            "email": true,
            "push": false
        }
    }
}
```

#### **Update User Profile**
```http
PUT /api/users/profile
Authorization: Bearer {token}
Content-Type: application/json

{
    "first_name": "John",
    "last_name": "Smith",
    "timezone": "Europe/London",
    "preferences": {
        "default_currency": "GBP",
        "dashboard_layout": "list"
    }
}
```

### 📊 **Portfolio Management API**

#### **Create Portfolio**
```http
POST /api/portfolios
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Growth Portfolio",
    "description": "Long-term growth focused investments",
    "base_currency": "USD",
    "initial_cash": 10000.00,
    "investment_objectives": ["growth", "dividend_income"],
    "risk_tolerance": "moderate"
}
```

**Response:**
```json
{
    "success": true,
    "portfolio_id": 456,
    "name": "Growth Portfolio",
    "created_at": "2025-09-26T15:30:00Z",
    "initial_cash": 10000.00,
    "current_value": 10000.00,
    "base_currency": "USD"
}
```

#### **Get Portfolio Details**
```http
GET /api/portfolios/{portfolio_id}
Authorization: Bearer {token}

Response:
{
    "portfolio_id": 456,
    "name": "Growth Portfolio",
    "description": "Long-term growth focused investments",
    "base_currency": "USD",
    "created_at": "2025-09-26T15:30:00Z",
    "updated_at": "2025-09-26T16:45:00Z",
    "performance": {
        "current_value": 12500.00,
        "total_return": 2500.00,
        "total_return_percent": 25.00,
        "daily_change": 150.00,
        "daily_change_percent": 1.22
    },
    "allocation": {
        "cash": 1500.00,
        "stocks": 9800.00,
        "etfs": 1200.00
    },
    "positions": [
        {
            "symbol": "AAPL",
            "name": "Apple Inc.",
            "shares": 50,
            "avg_cost": 145.00,
            "current_price": 152.30,
            "market_value": 7615.00,
            "unrealized_gain_loss": 365.00,
            "unrealized_gain_loss_percent": 5.03,
            "allocation_percent": 60.92
        }
    ]
}
```

#### **List User Portfolios**
```http
GET /api/portfolios
Authorization: Bearer {token}

Query Parameters:
- page: int (default: 1)
- limit: int (default: 10, max: 100)
- sort: string (name|created_at|value|performance)
- order: string (asc|desc)

Response:
{
    "portfolios": [
        {
            "portfolio_id": 456,
            "name": "Growth Portfolio",
            "current_value": 12500.00,
            "total_return_percent": 25.00,
            "daily_change_percent": 1.22,
            "created_at": "2025-09-26T15:30:00Z"
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 1,
        "total_items": 1,
        "items_per_page": 10
    }
}
```

### 💼 **Trading Operations API**

#### **Add Position (Buy Order)**
```http
POST /api/portfolios/{portfolio_id}/positions
Authorization: Bearer {token}
Content-Type: application/json

{
    "symbol": "TSLA",
    "action": "buy",
    "shares": 25,
    "order_type": "market",
    "price": null,
    "commission": 0.00,
    "notes": "Adding Tesla to growth portfolio"
}
```

**Response:**
```json
{
    "success": true,
    "transaction_id": "txn_789abc",
    "symbol": "TSLA",
    "action": "buy",
    "shares": 25,
    "executed_price": 245.67,
    "total_cost": 6141.75,
    "commission": 0.00,
    "executed_at": "2025-09-26T16:45:00Z",
    "portfolio_impact": {
        "new_cash_balance": 3358.25,
        "new_position_value": 6141.75
    }
}
```

#### **Sell Position**
```http
POST /api/portfolios/{portfolio_id}/positions/{symbol}/sell
Authorization: Bearer {token}
Content-Type: application/json

{
    "shares": 10,
    "order_type": "limit",
    "limit_price": 250.00,
    "time_in_force": "day",
    "notes": "Taking partial profits"
}
```

#### **Get Trading History**
```http
GET /api/portfolios/{portfolio_id}/transactions
Authorization: Bearer {token}

Query Parameters:
- start_date: string (YYYY-MM-DD)
- end_date: string (YYYY-MM-DD)
- symbol: string (filter by symbol)
- action: string (buy|sell|dividend)
- page: int
- limit: int

Response:
{
    "transactions": [
        {
            "transaction_id": "txn_789abc",
            "symbol": "TSLA",
            "action": "buy",
            "shares": 25,
            "price": 245.67,
            "total_amount": 6141.75,
            "commission": 0.00,
            "executed_at": "2025-09-26T16:45:00Z",
            "notes": "Adding Tesla to growth portfolio"
        }
    ],
    "summary": {
        "total_transactions": 15,
        "total_buy_value": 25000.00,
        "total_sell_value": 3500.00,
        "total_commissions": 0.00
    }
}
```

### 📈 **Market Data API**

#### **Get Real-Time Quote**
```http
GET /api/market/quote/{symbol}
Authorization: Bearer {token}

Response:
{
    "symbol": "AAPL",
    "name": "Apple Inc.",
    "exchange": "NASDAQ",
    "currency": "USD",
    "price": 152.30,
    "change": 2.15,
    "change_percent": 1.43,
    "volume": 45670123,
    "market_cap": 2456789000000,
    "pe_ratio": 28.45,
    "dividend_yield": 0.52,
    "52_week_high": 198.23,
    "52_week_low": 124.17,
    "last_updated": "2025-09-26T20:00:00Z",
    "market_status": "closed"
}
```

#### **Get Historical Data**
```http
GET /api/market/history/{symbol}
Authorization: Bearer {token}

Query Parameters:
- period: string (1d|5d|1mo|3mo|6mo|1y|2y|5y|10y|ytd|max)
- interval: string (1m|2m|5m|15m|30m|60m|90m|1h|1d|5d|1wk|1mo|3mo)

Response:
{
    "symbol": "AAPL",
    "period": "1mo",
    "interval": "1d",
    "data": [
        {
            "date": "2025-08-26",
            "open": 148.50,
            "high": 152.80,
            "low": 147.20,
            "close": 151.45,
            "volume": 52341567,
            "adjusted_close": 151.45
        }
    ],
    "meta": {
        "currency": "USD",
        "symbol": "AAPL",
        "exchange_name": "NasdaqGS",
        "instrument_type": "EQUITY",
        "first_trade_date": "1980-12-12",
        "regular_market_time": 1727385600,
        "gmtoffset": -14400,
        "timezone": "EDT"
    }
}
```

#### **Get Technical Indicators**
```http
GET /api/market/indicators/{symbol}
Authorization: Bearer {token}

Query Parameters:
- indicators: string (comma-separated: sma,ema,rsi,macd,bb)
- period: string (1d|5d|1mo|3mo|6mo|1y)

Response:
{
    "symbol": "AAPL",
    "indicators": {
        "sma": {
            "sma_20": 148.75,
            "sma_50": 145.30,
            "sma_200": 152.80
        },
        "rsi": {
            "rsi_14": 68.45,
            "signal": "overbought_warning"
        },
        "macd": {
            "macd_line": 2.45,
            "signal_line": 1.87,
            "histogram": 0.58,
            "signal": "bullish"
        },
        "bollinger_bands": {
            "upper_band": 156.80,
            "middle_band": 151.45,
            "lower_band": 146.10,
            "bandwidth": 7.07,
            "signal": "neutral"
        }
    },
    "last_updated": "2025-09-26T20:00:00Z"
}
```

### 📊 **Analytics & Reports API**

#### **Portfolio Performance Analytics**
```http
GET /api/portfolios/{portfolio_id}/analytics
Authorization: Bearer {token}

Query Parameters:
- period: string (1d|1w|1m|3m|6m|1y|ytd|all)
- benchmark: string (SPY|QQQ|IWM|VTI)

Response:
{
    "portfolio_id": 456,
    "period": "1y",
    "performance": {
        "total_return": 2500.00,
        "total_return_percent": 25.00,
        "annualized_return": 24.85,
        "volatility": 18.45,
        "sharpe_ratio": 1.35,
        "sortino_ratio": 1.67,
        "max_drawdown": -8.75,
        "max_drawdown_percent": -12.34,
        "beta": 1.15,
        "alpha": 0.045,
        "r_squared": 0.87
    },
    "benchmark_comparison": {
        "benchmark": "SPY",
        "portfolio_return": 25.00,
        "benchmark_return": 18.50,
        "excess_return": 6.50,
        "tracking_error": 4.23,
        "information_ratio": 1.54
    },
    "risk_metrics": {
        "value_at_risk_95": -450.00,
        "conditional_var_95": -678.00,
        "downside_deviation": 12.34,
        "upside_capture": 105.67,
        "downside_capture": 89.23
    }
}
```

#### **Generate Portfolio Report**
```http
POST /api/portfolios/{portfolio_id}/reports
Authorization: Bearer {token}
Content-Type: application/json

{
    "report_type": "performance_summary",
    "period": "1y",
    "format": "pdf",
    "include_sections": [
        "performance_summary",
        "holdings_breakdown",
        "sector_allocation",
        "risk_analysis",
        "transaction_history"
    ],
    "email_delivery": true
}

Response:
{
    "success": true,
    "report_id": "rpt_xyz789",
    "download_url": "/api/reports/download/rpt_xyz789",
    "expires_at": "2025-10-03T16:45:00Z",
    "email_sent": true,
    "generated_at": "2025-09-26T16:45:00Z"
}
```

---

## 📋 **Data Models**

### **User Model**
```typescript
interface User {
    user_id: number;
    username: string;
    email: string;
    first_name?: string;
    last_name?: string;
    timezone: string;
    created_at: string;
    last_login?: string;
    is_active: boolean;
    is_admin: boolean;
    preferences: UserPreferences;
}

interface UserPreferences {
    default_currency: string;
    dashboard_layout: 'grid' | 'list';
    notifications: {
        email: boolean;
        push: boolean;
        sms: boolean;
    };
    privacy: {
        profile_public: boolean;
        portfolio_public: boolean;
    };
}
```

### **Portfolio Model**
```typescript
interface Portfolio {
    portfolio_id: number;
    user_id: number;
    name: string;
    description?: string;
    base_currency: string;
    created_at: string;
    updated_at: string;
    is_active: boolean;
    performance: PortfolioPerformance;
    allocation: AssetAllocation;
    positions: Position[];
}

interface PortfolioPerformance {
    current_value: number;
    total_return: number;
    total_return_percent: number;
    daily_change: number;
    daily_change_percent: number;
    inception_date: string;
}

interface Position {
    symbol: string;
    name: string;
    shares: number;
    avg_cost: number;
    current_price: number;
    market_value: number;
    unrealized_gain_loss: number;
    unrealized_gain_loss_percent: number;
    allocation_percent: number;
    sector?: string;
    industry?: string;
}
```

### **Transaction Model**
```typescript
interface Transaction {
    transaction_id: string;
    portfolio_id: number;
    symbol: string;
    action: 'buy' | 'sell' | 'dividend' | 'split';
    shares: number;
    price: number;
    total_amount: number;
    commission: number;
    executed_at: string;
    order_type: 'market' | 'limit' | 'stop' | 'stop_limit';
    notes?: string;
}
```

---

## ⚠️ **Error Handling**

### **Standard Error Response**
```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "Invalid input data provided",
        "details": {
            "field": "email",
            "issue": "Email format is invalid"
        },
        "request_id": "req_abc123"
    },
    "timestamp": "2025-09-26T16:45:00Z"
}
```

### **HTTP Status Codes**
- **200** - OK: Request successful
- **201** - Created: Resource created successfully
- **400** - Bad Request: Invalid request data
- **401** - Unauthorized: Authentication required
- **403** - Forbidden: Insufficient permissions
- **404** - Not Found: Resource not found
- **409** - Conflict: Resource already exists
- **422** - Unprocessable Entity: Validation failed
- **429** - Too Many Requests: Rate limit exceeded
- **500** - Internal Server Error: Server error

### **Error Code Reference**

#### **Authentication Errors**
- `AUTH_REQUIRED` - Authentication token required
- `AUTH_INVALID` - Invalid authentication credentials
- `AUTH_EXPIRED` - Authentication token expired
- `AUTH_INSUFFICIENT` - Insufficient permissions

#### **Validation Errors**
- `VALIDATION_ERROR` - General validation failure
- `FIELD_REQUIRED` - Required field missing
- `FIELD_INVALID` - Field format invalid
- `VALUE_OUT_OF_RANGE` - Numeric value outside allowed range

#### **Business Logic Errors**
- `INSUFFICIENT_FUNDS` - Not enough cash for transaction
- `POSITION_NOT_FOUND` - Investment position not found
- `MARKET_CLOSED` - Trading not allowed when market closed
- `ORDER_REJECTED` - Trading order rejected by system

---

## 🔧 **Rate Limiting**

### **Rate Limit Headers**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 995
X-RateLimit-Reset: 1727391600
X-RateLimit-Window: 3600
```

### **Rate Limit Tiers**

#### **Free Tier**
- **API Calls:** 1,000 per hour
- **Market Data:** 500 requests per hour
- **Real-time Quotes:** 100 per hour

#### **Premium Tier**
- **API Calls:** 10,000 per hour
- **Market Data:** 5,000 requests per hour
- **Real-time Quotes:** 1,000 per hour

#### **Professional Tier**
- **API Calls:** 100,000 per hour
- **Market Data:** Unlimited
- **Real-time Quotes:** Unlimited

---

## 📱 **SDK & Integration Examples**

### **JavaScript/TypeScript SDK**
```typescript
import { ChatGPTMicroCapAPI } from 'chatgpt-microcap-sdk';

const api = new ChatGPTMicroCapAPI({
    apiKey: 'your_api_key_here',
    environment: 'production' // or 'sandbox'
});

// Get portfolio performance
const portfolio = await api.portfolios.get(456);
console.log(`Portfolio Value: $${portfolio.performance.current_value}`);

// Execute trade
const trade = await api.trading.buyStock({
    portfolioId: 456,
    symbol: 'AAPL',
    shares: 10,
    orderType: 'market'
});
```

### **Python SDK**
```python
from chatgpt_microcap import ChatGPTMicroCapClient

client = ChatGPTMicroCapClient(
    api_key='your_api_key_here',
    environment='production'
)

# Get real-time quote
quote = client.market.get_quote('AAPL')
print(f"AAPL Price: ${quote.price}")

# Create new portfolio
portfolio = client.portfolios.create(
    name='Tech Growth',
    initial_cash=10000.00,
    base_currency='USD'
)
```

### **curl Examples**
```bash
# Get portfolio list
curl -X GET "https://api.chatgpt-microcap.com/v1/portfolios" \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Content-Type: application/json"

# Create buy order
curl -X POST "https://api.chatgpt-microcap.com/v1/portfolios/456/positions" \
  -H "Authorization: Bearer your_api_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "symbol": "TSLA",
    "action": "buy",
    "shares": 25,
    "order_type": "market"
  }'
```

---

## 🔐 **Security & Compliance**

### **Data Encryption**
- **In Transit:** TLS 1.3 encryption for all API communications
- **At Rest:** AES-256 encryption for sensitive data storage
- **API Keys:** Secure key generation with rotation capabilities

### **Compliance Standards**
- **SOX Compliance:** Complete audit trails for all financial transactions
- **GDPR Compliance:** Data privacy and user rights management
- **PCI DSS:** Secure handling of payment card information
- **SOC 2 Type II:** Security, availability, and confidentiality controls

### **Security Headers**
```http
Strict-Transport-Security: max-age=31536000; includeSubDomains
Content-Security-Policy: default-src 'self'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

---

## 📊 **Webhooks**

### **Portfolio Event Webhooks**
```json
{
    "event": "portfolio.position.updated",
    "portfolio_id": 456,
    "timestamp": "2025-09-26T16:45:00Z",
    "data": {
        "symbol": "AAPL",
        "old_shares": 45,
        "new_shares": 50,
        "transaction_id": "txn_789abc",
        "trigger": "buy_order_executed"
    }
}
```

### **Market Alert Webhooks**
```json
{
    "event": "market.price_alert",
    "symbol": "TSLA",
    "timestamp": "2025-09-26T16:45:00Z",
    "data": {
        "alert_type": "price_target",
        "current_price": 250.00,
        "target_price": 250.00,
        "condition": "above",
        "user_id": 123,
        "portfolio_ids": [456, 789]
    }
}
```

---

## 📞 **Support & Resources**

### **API Support**
- **Documentation:** [https://docs.chatgpt-microcap.com](https://docs.chatgpt-microcap.com)
- **Status Page:** [https://status.chatgpt-microcap.com](https://status.chatgpt-microcap.com)
- **Support Email:** api-support@chatgpt-microcap.com
- **Developer Forum:** [https://community.chatgpt-microcap.com](https://community.chatgpt-microcap.com)

### **Additional Resources**
- **Postman Collection:** [Download API Collection](https://docs.chatgpt-microcap.com/postman)
- **OpenAPI Specification:** [Download OpenAPI JSON](https://api.chatgpt-microcap.com/v1/openapi.json)
- **Code Examples:** [GitHub Repository](https://github.com/chatgpt-microcap/api-examples)
- **Changelog:** [API Version History](https://docs.chatgpt-microcap.com/changelog)

---

**📝 Document Version:** 1.0  
**📅 Last Updated:** September 26, 2025  
**🔗 API Version:** v1  
**👤 Document Owner:** API Development Team  
**📧 Contact:** api-team@chatgpt-microcap.com