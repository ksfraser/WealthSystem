# Technical Requirements Specification
## ChatGPT Micro-Cap Experiment - PHP Native Stock Data Platform

**Document Version:** 2.0  
**Last Updated:** September 27, 2025  
**Platform Version:** PHP Native 2.0

---

## 1. Executive Summary

This document outlines the technical requirements for the ChatGPT Micro-Cap Experiment platform, which has been redesigned as a 100% PHP-native solution. The system eliminates all Python dependencies while maintaining full functionality for stock data retrieval, portfolio management, and financial analysis.

### 1.1 Key Architecture Changes
- **Eliminated Python Dependency:** Replaced Python yfinance with native PHP Yahoo Finance client
- **Simplified Infrastructure:** SQLite database reduces deployment complexity
- **Organized Data Storage:** Structured data/ directory for CSV files and debug outputs
- **Enhanced Performance:** Direct API integration without subprocess overhead

---

## 2. System Requirements

### 2.1 Core Runtime Requirements

#### **PHP Runtime Environment**
| Component | Minimum Version | Recommended Version | Notes |
|-----------|----------------|-------------------|-------|
| PHP | 8.4.0 | 8.4.6+ | Core platform requirement |
| PHP Extensions | pdo_sqlite, json, curl, mbstring | + openssl, fileinfo | Essential for stock data API |
| Composer | 2.0+ | 2.8.8+ | Dependency management |

#### **Database Requirements**
| Component | Minimum Version | Recommended Version | Notes |
|-----------|----------------|-------------------|-------|
| SQLite | 3.0+ | 3.40+ | Built into PHP, no separate install |
| Alternative: PostgreSQL | 12+ | 15+ | For high-concurrency deployments |
| Alternative: MySQL | 5.7+ | 8.0+ | Legacy compatibility option |

#### **HTTP Client Requirements**
| Component | Minimum Version | Recommended Version | Notes |
|-----------|----------------|-------------------|-------|
| Guzzle HTTP | 7.0+ | 7.8+ | Yahoo Finance API client |
| cURL Extension | 7.4+ | 8.0+ | HTTP transport layer |

### 2.2 Infrastructure Requirements

#### **Development Environment**
```
OS: Windows 10+, macOS 10.15+, Linux (Ubuntu 20.04+)
Memory: 256MB RAM minimum, 1GB+ recommended
Storage: 100MB application + 50MB data directory
Network: Internet access for Yahoo Finance API (443/HTTPS)
```

#### **Production Environment**
```
OS: Linux (Ubuntu 22.04 LTS recommended)
Memory: 1GB RAM minimum, 4GB+ for high traffic
Storage: SSD recommended, 1GB+ available space
Network: Reliable internet, CDN for static assets
Web Server: Apache 2.4+ or Nginx 1.18+
```

### 2.3 Performance Requirements

#### **Stock Data Retrieval**
- **Response Time:** < 5 seconds for 1 year of daily data
- **Throughput:** 100+ symbols per hour
- **Reliability:** 99.5% success rate with automatic retry
- **Data Accuracy:** 100% data integrity with validation

#### **Database Operations** 
- **Query Response:** < 100ms for standard portfolio queries
- **Insert Performance:** 1000+ price records per second
- **Concurrent Users:** 50+ simultaneous users (SQLite), 500+ (PostgreSQL)
- **Data Volume:** Support 10M+ price records per database

#### **Web Interface**
- **Page Load:** < 2 seconds initial load, < 500ms subsequent
- **AJAX Responses:** < 300ms for stock search, portfolio updates
- **File Operations:** CSV export < 5 seconds for 10K records
- **Memory Usage:** < 128MB per request

---

## 3. Functional Requirements

### 3.1 Stock Data Service (`StockDataService.php`)

#### **FR-001: Yahoo Finance Integration**
```php
/**
 * REQUIREMENT: Fetch historical stock data from Yahoo Finance API
 * INPUT: Symbol, start date, end date
 * OUTPUT: JSON response with OHLCV data
 * ERROR HANDLING: Retry logic, fallback mechanisms
 * PERFORMANCE: < 5 seconds for 1 year of data
 */
public function fetchHistoricalData($symbol, $startDate, $endDate);
```

#### **FR-002: CSV Data Export**
```php
/**
 * REQUIREMENT: Export stock data to CSV format
 * INPUT: JSON stock data, optional filename
 * OUTPUT: CSV file path in data/csv/ directory
 * FORMAT: Date,Open,High,Low,Close,Adj Close,Volume
 * VALIDATION: Data integrity checks before export
 */
public function saveToCSV($jsonData, $filename = null);
```

#### **FR-003: Data Validation and Error Handling**
- Validate all API responses for completeness
- Handle network timeouts and API rate limits
- Provide detailed error messages for troubleshooting
- Maintain debug logs in data/debug/ directory

### 3.2 Yahoo Finance Client (`YahooFinancePhp.php`)

#### **FR-004: Direct API Communication**
```php
/**
 * REQUIREMENT: Direct HTTP communication with Yahoo Finance
 * PROTOCOL: HTTPS with SSL verification (configurable)
 * RATE LIMITING: Respect API limits with delays
 * TIMEOUT: 30 second default, configurable
 * RETRY: Automatic retry on transient failures
 */
```

#### **FR-005: Data Parsing and Validation**
- Parse Yahoo Finance JSON response format
- Validate data completeness and accuracy
- Handle missing or invalid data points
- Sort data chronologically by date

#### **FR-006: SSL and Security Configuration**
- SSL certificate validation (configurable for development)
- User-Agent spoofing for API compatibility
- Request headers optimization for reliability
- Connection timeout and retry management

### 3.3 Progressive Historical Loader (`ProgressiveHistoricalLoader.php`)

#### **FR-007: Chunked Data Loading**
```php
/**
 * REQUIREMENT: Load historical data in manageable chunks
 * CHUNK SIZE: 6 months default, configurable
 * PROCESSING: Sequential chunk processing with delays
 * PROGRESS: Real-time progress reporting to UI
 * RECOVERY: Resume capability for interrupted loads
 */
```

#### **FR-008: Database Integration**
- Initialize stock tables automatically
- Insert/update price data with conflict resolution
- Maintain data integrity with transaction support
- Provide detailed operation logging

#### **FR-009: CSV Processing Capability**
- Process existing CSV files for data import
- Map CSV columns to database schema
- Validate imported data for consistency
- Support multiple CSV formats and sources

### 3.4 Data Organization Requirements

#### **FR-010: Structured Data Directory**
```
data/
├── csv/          # Historical data exports
│   ├── SYMBOL_YYYY-MM-DD_to_YYYY-MM-DD.csv
│   └── ...
├── debug/        # API response debugging
│   ├── debug_output_SYMBOL_YYYY-MM-DD.json
│   └── ...
└── README.md     # Directory documentation
```

#### **FR-011: File Naming Conventions**
- CSV files: `{SYMBOL}_{START_DATE}_to_{END_DATE}.csv`
- Debug files: `debug_output_{SYMBOL}_{DATE}.json`
- Automatic directory creation if missing
- File permissions management for web access

---

## 4. Non-Functional Requirements

### 4.1 Performance Requirements

#### **NFR-001: Response Time Standards**
| Operation | Target Time | Maximum Time |
|-----------|-------------|--------------|
| Stock data fetch (1 month) | < 2 seconds | < 5 seconds |
| Stock data fetch (1 year) | < 4 seconds | < 10 seconds |
| CSV export (1K records) | < 1 second | < 3 seconds |
| Database query (portfolio) | < 50ms | < 200ms |
| Web page load | < 1 second | < 3 seconds |

#### **NFR-002: Throughput Requirements**
- **API Requests:** 100 requests/hour sustainable
- **Database Operations:** 1000 inserts/second
- **Concurrent Users:** 50+ (SQLite), 200+ (PostgreSQL)
- **File I/O:** 10MB/second CSV processing

#### **NFR-003: Memory Management**
- **Per Request:** < 64MB typical, 128MB maximum
- **Background Processing:** < 256MB per worker
- **File Processing:** Stream large files to avoid memory limits
- **Garbage Collection:** Explicit cleanup for long-running processes

### 4.2 Reliability Requirements

#### **NFR-004: System Availability**
- **Uptime Target:** 99.5% availability
- **Error Recovery:** Automatic retry with exponential backoff
- **Data Integrity:** 100% accuracy with validation checks
- **Failover:** Graceful degradation when APIs unavailable

#### **NFR-005: Error Handling Standards**
```php
/**
 * ERROR HANDLING REQUIREMENTS:
 * - All exceptions must be caught and logged
 * - User-friendly error messages for UI
 * - Detailed technical logs for debugging
 * - Automatic retry for transient failures
 * - Graceful degradation when services unavailable
 */
```

#### **NFR-006: Data Backup and Recovery**
- **Automatic CSV Export:** All data exportable to CSV format
- **Database Backups:** SQLite file-based backup capability
- **Version Control:** Git ignore for sensitive data files
- **Data Migration:** Import/export tools for platform migration

### 4.3 Security Requirements

#### **NFR-007: API Security**
- **HTTPS Only:** All Yahoo Finance API calls over HTTPS
- **Rate Limiting:** Respect API terms of service
- **Input Validation:** Sanitize all user inputs
- **SQL Injection:** Use prepared statements exclusively

#### **NFR-008: File System Security**
- **Directory Permissions:** Proper file system permissions
- **Path Traversal:** Prevent directory traversal attacks
- **File Access:** Restrict access to data directory
- **Temporary Files:** Secure cleanup of temporary files

#### **NFR-009: Configuration Security**
- **Sensitive Data:** No hardcoded credentials or API keys
- **Configuration Files:** Secure configuration file handling
- **Environment Variables:** Support for environment-based config
- **Debug Information:** No sensitive data in debug logs

### 4.4 Scalability Requirements

#### **NFR-010: Horizontal Scaling**
- **Stateless Design:** No server-side session dependencies for API
- **Database Scaling:** Support PostgreSQL for high concurrency
- **Load Balancing:** Compatible with load balancer deployments
- **Caching:** File-based caching for frequently accessed data

#### **NFR-011: Data Volume Scaling**
- **Large Datasets:** Support millions of price records
- **Historical Data:** Efficient handling of multi-year datasets
- **Partitioning:** Database partitioning strategies for large tables
- **Archiving:** Automated archiving of old data

### 4.5 Maintainability Requirements

#### **NFR-012: Code Quality Standards**
- **PSR Compliance:** Follow PSR-4 autoloading, PSR-12 coding standards
- **Documentation:** PHPDoc comments for all public methods
- **Testing:** Unit test coverage > 80%
- **Logging:** Comprehensive logging with configurable levels

#### **NFR-013: Deployment Requirements**
- **Zero Downtime:** Support rolling deployments
- **Configuration Management:** Environment-specific configurations
- **Monitoring:** Health check endpoints for monitoring
- **Updates:** Backward-compatible database schema changes

---

## 5. Integration Requirements

### 5.1 Yahoo Finance API Integration

#### **INT-001: API Endpoint Specifications**
```
Base URL: https://query1.finance.yahoo.com/v8/finance/chart/{symbol}
Parameters:
  - period1: Start timestamp (Unix)
  - period2: End timestamp (Unix)  
  - interval: Data interval (1d for daily)
  - includePrePost: Extended hours data (true)
  - events: Corporate events (div,splits)
```

#### **INT-002: Response Format Handling**
```json
{
  "chart": {
    "result": [{
      "meta": { "symbol": "AAPL", "timezone": "EST" },
      "timestamp": [1640995200, ...],
      "indicators": {
        "quote": [{
          "open": [182.83, ...],
          "high": [183.29, ...],
          "low": [180.46, ...],
          "close": [181.5, ...],
          "volume": [3825000, ...]
        }]
      }
    }]
  }
}
```

#### **INT-003: Error Response Handling**
- HTTP status code validation (200, 404, 429, 500)
- JSON parsing error detection
- Empty dataset handling
- Rate limit response management

### 5.2 Database Integration Requirements

#### **INT-004: SQLite Integration (Default)**
```sql
-- Required table structure for stock price data
CREATE TABLE IF NOT EXISTS {symbol}_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date DATE NOT NULL UNIQUE,
    open DECIMAL(10,4) NOT NULL,
    high DECIMAL(10,4) NOT NULL,
    low DECIMAL(10,4) NOT NULL,
    close DECIMAL(10,4) NOT NULL,
    volume BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### **INT-005: Alternative Database Support**
- PostgreSQL compatibility for production deployments
- MySQL compatibility for legacy systems
- Connection pooling for high-concurrency scenarios
- Database migration utilities for platform changes

### 5.3 File System Integration

#### **INT-006: CSV File Format Standards**
```csv
Date,Open,High,Low,Close,Adj Close,Volume
2024-01-02,162.83,163.29,160.46,161.50,161.50,3825000
2024-01-03,161.00,161.73,160.08,160.10,160.10,4086100
```

#### **INT-007: Debug File Format Standards**
```json
{
  "success": true,
  "symbol": "AAPL",
  "start_date": "2024-01-02", 
  "end_date": "2024-01-31",
  "total_records": 20,
  "data": [...],
  "source": "PHP_YahooFinance"
}
```

---

## 6. Quality Assurance Requirements

### 6.1 Testing Requirements

#### **QA-001: Unit Testing Standards**
- **Coverage Target:** Minimum 80% code coverage
- **Test Framework:** PHPUnit 9.6+
- **Mock Objects:** External API calls must be mocked
- **Data Providers:** Comprehensive test data scenarios

#### **QA-002: Integration Testing Requirements**
- **API Integration:** Test Yahoo Finance API integration
- **Database Integration:** Test all database operations
- **File System:** Test CSV generation and data directory management
- **Error Scenarios:** Test all error conditions and recovery

#### **QA-003: Performance Testing Standards**
- **Load Testing:** Simulate 50+ concurrent users
- **Stress Testing:** Test system limits and failure points
- **Data Volume Testing:** Test with large datasets (100K+ records)
- **Network Latency:** Test under various network conditions

### 6.2 Code Quality Requirements

#### **QA-004: Static Analysis Standards**
```bash
# Required code quality tools
composer require --dev phpstan/phpstan       # Level 8 analysis
composer require --dev squizlabs/php_codesniffer  # PSR-12 compliance
composer require --dev phpmd/phpmd          # Mess detection
```

#### **QA-005: Security Analysis Requirements**
- **SQL Injection:** Verify all queries use prepared statements
- **XSS Prevention:** Validate all output escaping
- **File Upload:** Secure file handling practices
- **Input Validation:** Comprehensive input sanitization

---

## 7. Deployment Requirements

### 7.1 Production Deployment Standards

#### **DEP-001: Environment Configuration**
```php
// Production configuration requirements
return [
    'environment' => 'production',
    'debug' => false,
    'ssl_verify' => true,
    'database_backup' => true,
    'error_logging' => 'syslog',
    'session_security' => 'strict',
];
```

#### **DEP-002: Security Hardening Requirements**
- **File Permissions:** 644 for files, 755 for directories
- **Web Root:** Only web_ui/ directory in document root
- **Hidden Files:** .env, config/ not web accessible
- **Error Display:** Disable error display in production

### 7.2 Monitoring and Logging

#### **DEP-003: Application Monitoring**
- **Health Checks:** /health endpoint for monitoring systems
- **Performance Metrics:** Response time and throughput tracking
- **Error Tracking:** Centralized error logging and alerting
- **Resource Usage:** Memory and CPU usage monitoring

#### **DEP-004: Log Management Requirements**
```php
// Required log levels and destinations
LOG_LEVELS = ['ERROR', 'WARNING', 'INFO', 'DEBUG'];
LOG_DESTINATIONS = ['file', 'syslog', 'database'];
RETENTION_POLICY = '30 days minimum, 1 year recommended';
```

---

## 8. Compliance and Standards

### 8.1 Code Standards Compliance

#### **STD-001: PHP Standards Recommendations (PSR)**
- **PSR-4:** Autoloading standard compliance
- **PSR-12:** Extended coding style guide
- **PSR-3:** Logger interface implementation
- **PSR-7:** HTTP message interface (future enhancement)

#### **STD-002: Documentation Standards**
- **PHPDoc:** Complete documentation for all public methods
- **README:** Comprehensive installation and usage guide
- **API Docs:** Auto-generated API documentation
- **Architecture:** UML diagrams and system design documents

### 8.2 Data Protection Requirements

#### **STD-003: Financial Data Handling**
- **Data Accuracy:** 100% data integrity requirements
- **Audit Trail:** Complete transaction logging
- **Data Export:** Full data portability in CSV format
- **Privacy:** No personal financial data storage

---

## 9. Future Enhancement Requirements

### 9.1 Planned Enhancements

#### **FUT-001: Advanced Analytics Integration**
- Technical analysis indicators (RSI, MACD, etc.)
- Portfolio optimization algorithms
- Risk management calculations
- Performance benchmarking tools

#### **FUT-002: Real-time Data Integration**
- WebSocket connections for live data
- Streaming price updates
- Real-time portfolio valuations
- Live trading signal generation

#### **FUT-003: Multi-Exchange Support**
- Additional data providers (Alpha Vantage, IEX)
- International market support
- Cryptocurrency data integration
- Alternative data sources

### 9.2 Scalability Enhancements

#### **FUT-004: Cloud Platform Integration**
- AWS/Azure/GCP deployment templates
- Container orchestration (Kubernetes)
- Serverless function integration
- CDN integration for static assets

#### **FUT-005: Performance Optimizations**
- Redis caching layer
- Database query optimization
- Background job processing
- API response caching

---

## 10. Acceptance Criteria

### 10.1 System Acceptance Tests

#### **ACC-001: Core Functionality Verification**
✅ **Stock Data Retrieval:** Successfully fetch data for 100 different symbols  
✅ **CSV Export:** Generate valid CSV files for all retrieved data  
✅ **Error Handling:** Graceful handling of network and API errors  
✅ **Data Integrity:** 100% accuracy compared to source data  

#### **ACC-002: Performance Benchmarks**
✅ **Response Time:** < 5 seconds for 1 year of daily data  
✅ **Throughput:** Process 100 symbols in under 1 hour  
✅ **Concurrency:** Support 50 simultaneous users  
✅ **Memory Usage:** < 128MB per request  

#### **ACC-003: Reliability Standards**  
✅ **System Uptime:** 99.5% availability over 30-day period  
✅ **Data Recovery:** Full data recovery from CSV backups  
✅ **Error Recovery:** Automatic retry and recovery mechanisms  
✅ **Monitoring:** Complete system health monitoring  

### 10.2 User Acceptance Criteria

#### **ACC-004: User Experience Standards**
✅ **Ease of Installation:** Complete setup in under 15 minutes  
✅ **Documentation:** Comprehensive user and developer guides  
✅ **Error Messages:** Clear, actionable error messages  
✅ **Data Export:** One-click CSV export functionality  

---

**Document Control:**
- **Version:** 2.0 (PHP Native Release)
- **Status:** Active
- **Next Review:** December 2025
- **Approved By:** Development Team
- **Distribution:** All project stakeholders