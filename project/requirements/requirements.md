# 📋 **Requirements Documentation**

## 🎯 **System Requirements Overview**

This document outlines the complete requirements for the ChatGPT-Micro-Cap-Experiment trading platform, including functional requirements, technical specifications, and compliance standards.

---

## 🖥️ **Technical Requirements**

### **Server Requirements**

#### **Minimum System Requirements**
```yaml
Operating System:
  - Linux (Ubuntu 20.04+ / CentOS 8+)
  - Windows Server 2019+
  - macOS 11+ (Development only)

Hardware:
  Processor: 2+ CPU cores, 2.0GHz+
  Memory: 4GB RAM minimum
  Storage: 10GB available disk space
  Network: Broadband internet connection

Database:
  - MySQL 8.0+ / MariaDB 10.5+
  - PostgreSQL 12+ (Alternative)
  - 1GB storage minimum
```

#### **Recommended Production Requirements**
```yaml
Hardware:
  Processor: 4+ CPU cores, 3.0GHz+
  Memory: 16GB+ RAM
  Storage: 100GB+ SSD storage
  Network: Dedicated server with 1Gbps+ connection
  
Performance:
  - Load balancing for multiple instances
  - Database replication for high availability
  - CDN integration for static assets
  - Automated backup systems
```

### **Software Dependencies**

#### **PHP Environment**
```yaml
PHP Version: 8.0+ (Recommended: 8.2+)

Required Extensions:
  - pdo_mysql: Database connectivity
  - curl: External API communication
  - json: JSON data processing
  - mbstring: Multi-byte string handling
  - xml: XML parsing for financial data
  - zip: Archive handling
  - openssl: Cryptographic operations
  - session: User session management

Optional Extensions:
  - redis: Session storage and caching
  - memcached: Performance optimization
  - xdebug: Development debugging
```

#### **Python Environment**
```yaml
Python Version: 3.8+ (Recommended: 3.11+)

Core Dependencies:
  pandas==2.0.3: Data manipulation and analysis
  numpy==1.24.3: Numerical computations
  requests==2.31.0: HTTP library for API calls
  python-dateutil==2.8.2: Date/time parsing
  
Financial Libraries:
  yfinance==0.2.18: Yahoo Finance data
  alpha_vantage==2.3.1: Alpha Vantage API
  ta-lib==0.4.26: Technical analysis indicators
  
Visualization:
  matplotlib==3.7.1: Chart generation
  plotly==5.15.0: Interactive charts
  seaborn==0.12.2: Statistical visualizations
```

---

## ⚙️ **Functional Requirements**

### **FR-001: User Authentication & Authorization**

#### **User Registration (FR-001.1)**
```yaml
Priority: Critical
Description: Users must be able to create accounts securely

Acceptance Criteria:
  - Username/email validation with format checking
  - Password complexity requirements (8+ chars, mixed case, numbers)
  - Email verification for new accounts
  - CAPTCHA protection against automated registration
  - Duplicate email/username prevention

Security Requirements:
  - Password hashing using PHP password_hash()
  - CSRF token validation for registration forms
  - Rate limiting for registration attempts
  - Input sanitization for all user data
```

#### **User Login (FR-001.2)**
```yaml
Priority: Critical
Description: Secure user authentication system

Acceptance Criteria:
  - Username/email and password authentication
  - Session management with secure cookies
  - "Remember Me" functionality with secure tokens
  - Account lockout after failed attempts (5 attempts = 15min lockout)
  - Password reset functionality via email

Security Requirements:
  - Session regeneration on login
  - Secure session storage
  - Login attempt logging
  - Brute force protection
```

#### **Exception Handling & Redirects (FR-001.3)**
```yaml
Priority: Critical
Description: Graceful handling of authentication failures with user-friendly redirects
Status: ✅ IMPLEMENTED (Commits: 25159906, 72b95eaa)

Acceptance Criteria:
  - All protected pages catch LoginRequiredException
  - Users redirected to login page with return URL
  - After successful login, redirect to originally requested page
  - Return URL sanitized to prevent open redirect attacks
  - Admin-only pages show appropriate error messages
  - No uncaught exceptions causing 500 errors

Implementation Details:
  - 14 protected pages with try/catch blocks
  - Return URL validation regex: ^[a-zA-Z0-9_\-\.\?\/=&]+$
  - Blocks external redirects (no // in URL)
  - Hidden form field preserves return URL through login
  - Separate handling for LoginRequiredException vs admin access

Test Cases:
  ✅ Access protected page while logged out → redirect to login
  ✅ Login with return URL → redirect to original page
  ✅ Access admin page as normal user → redirect to dashboard with error
  ✅ Malformed return URL → default to dashboard.php
  ✅ All pages tested: dashboard, profile, job_manager, strategy-config,
     system_status, database, invitations, admin pages (4)
```

### **FR-002: Portfolio Management**

#### **Portfolio Creation (FR-002.1)**
```yaml
Priority: Critical
Description: Users can create and manage investment portfolios

Acceptance Criteria:
  - Create multiple named portfolios per user
  - Set initial portfolio value and currency
  - Define investment objectives and risk tolerance
  - Portfolio duplication and templating
  - Portfolio archival and restoration

Validation Rules:
  - Portfolio name: 3-50 characters, alphanumeric + spaces
  - Initial value: Positive number, max 10 digits
  - Currency: ISO 4217 currency codes only
```

#### **Asset Management (FR-002.2)**
```yaml
Priority: Critical
Description: Add, modify, and remove assets from portfolios

Acceptance Criteria:
  - Add stocks, ETFs, mutual funds, crypto currencies
  - Real-time price updates via API integration
  - Position sizing and allocation tracking
  - Historical transaction logging
  - Cost basis and P&L calculations

Data Requirements:
  - Symbol validation against market data providers
  - Share quantity: Up to 6 decimal places
  - Purchase price: Currency-specific precision
  - Transaction fees and commission tracking
```

### **FR-003: Trading Operations**

#### **Manual Trading (FR-003.1)**
```yaml
Priority: High
Description: Execute buy/sell orders manually

Acceptance Criteria:
  - Market and limit order types
  - Order validation and confirmation
  - Transaction cost calculations
  - Portfolio impact preview
  - Order history and audit trail

Business Rules:
  - Sufficient cash balance validation
  - Position size limits (max 20% single asset)
  - Trading hours validation
  - Settlement period handling (T+2)
```

### **FR-004: Financial Analytics**

#### **Technical Analysis (FR-004.1)**
```yaml
Priority: High
Description: Comprehensive technical analysis tools

Indicators Required:
  - Moving Averages: SMA, EMA, WMA
  - Oscillators: RSI, MACD, Stochastic
  - Volatility: Bollinger Bands, ATR
  - Volume: OBV, VWAP, Volume Profile
  - Trend: ADX, Parabolic SAR, Ichimoku

Chart Features:
  - Multiple timeframes (1m, 5m, 1h, 1d, 1w, 1M)
  - Candlestick and OHLC charts
  - Volume overlays
  - Custom indicator combinations
```

#### **Risk Analytics (FR-004.2)**
```yaml
Priority: High
Description: Portfolio risk assessment and monitoring

Risk Metrics:
  - Value at Risk (VaR) calculations
  - Sharpe and Sortino ratios
  - Beta correlation analysis
  - Maximum drawdown tracking
  - Sector and geographic diversification

Alert Thresholds:
  - Portfolio volatility exceeding targets
  - Concentration risk warnings
  - Correlation risk alerts
  - Liquidity risk monitoring
```

#### **Sector Analysis (FR-004.3)**
```yaml
Priority: High
Description: GICS sector classification and comparative analysis
Status: ✅ Implemented (December 2025)

Acceptance Criteria:
  - Classify stocks into 11 GICS sectors
  - Compare stock performance vs sector average
  - Calculate Relative Strength (RS) ratio
  - Identify sector leaders and laggards
  - Detect sector rotation patterns
  - Track sector index performance

GICS Sectors Supported:
  - Energy (10)
  - Materials (15)
  - Industrials (20)
  - Consumer Discretionary (25)
  - Consumer Staples (30)
  - Health Care (35)
  - Financials (40)
  - Information Technology (45)
  - Communication Services (50)
  - Utilities (55)
  - Real Estate (60)

Business Rules:
  - RS Ratio > 1.5: Significant outperformance
  - RS Ratio < 0.5: Significant underperformance
  - Sector rotation detected with 3+ consecutive period trends
  - Peer comparison within same sector only
```

#### **Index Benchmarking (FR-004.4)**
```yaml
Priority: High
Description: Compare stocks/portfolios against major market indexes
Status: ✅ Implemented (December 2025)

Acceptance Criteria:
  - Calculate alpha (excess returns beyond market)
  - Calculate beta (volatility relative to market)
  - Measure correlation with indexes
  - Calculate Sharpe ratio (risk-adjusted returns)
  - Calculate Information ratio (alpha consistency)
  - Measure tracking error
  - Detect index membership heuristically

Supported Indexes:
  - SPY (S&P 500) - Large-cap US stocks, 500 constituents
  - QQQ (NASDAQ 100) - Tech-focused, 100 constituents
  - DIA (Dow Jones) - Blue-chip stocks, 30 constituents
  - IWM (Russell 2000) - Small-cap stocks, 2000 constituents

Performance Metrics:
  - Alpha: Annualized excess return (positive = outperformance)
  - Beta: Market sensitivity (1.0 = market volatility)
  - Correlation: -1 to +1 scale (movement relationship)
  - Tracking Error: Standard deviation of excess returns
  - Information Ratio: Alpha / Tracking Error
  - Sharpe Ratio: (Return - Risk-free rate) / Std Dev

Business Rules:
  - Choose benchmark based on market cap:
    * Large-cap → SPY
    * Small-cap → IWM
    * Tech-heavy → QQQ
    * Blue-chip → DIA
  - Adjust position size for high beta (beta > 1.5 = reduce position)
  - Positive alpha required to justify active management fees
```

#### **Fund Composition Analysis (FR-004.5)**
```yaml
Priority: High
Description: Analyze ETF, mutual fund, and segregated fund holdings and fees
Status: ✅ Implemented (December 2025)

Acceptance Criteria:
  - Retrieve complete fund holdings with weights
  - Calculate sector allocation from holdings
  - Calculate asset class breakdown
  - Calculate geographic exposure
  - Measure concentration risk (Top 10, HHI)
  - Compare fund overlap (redundancy detection)
  - Compare MER tiers for same base fund
  - Filter funds by client eligibility
  - Project long-term fee impact (10/25 years)
  - Analyze fund performance vs benchmark with alpha after fees

Fund Types Supported:
  - ETF (Exchange-Traded Fund)
  - Mutual Fund
  - Segregated Fund (insurance product)
  - Index Fund

MER Tier Structure:
  - RETAIL: 2.0-2.5% MER, $0 minimum net worth
  - PREFERRED: 1.5-1.9% MER, $250k minimum net worth
  - PREMIUM: 1.0-1.4% MER, $500k minimum net worth
  - INSTITUTIONAL: 0.5-0.9% MER, $1M+ minimum net worth

Eligibility Rules:
  - Personal net worth threshold
  - Family net worth aggregation (when allowed by fund)
  - Minimum investment amount
  - Advisor approval requirements
  - Effective date and expiry date validation

Overlap Interpretation:
  - <20%: Minimal overlap (good diversification)
  - 20-50%: Moderate overlap (acceptable)
  - 50-80%: High overlap (consider alternatives)
  - >80%: Very high redundancy (avoid)

Concentration Risk Metrics:
  - Top 10 holdings weight percentage
  - HHI (Herfindahl-Hirschman Index):
    * <1,000: Highly diversified
    * 1,000-1,800: Moderately concentrated
    * >1,800: Highly concentrated (risky)

Business Rules:
  - Multiple fund codes may share same base fund ID (different MER tiers)
  - Family aggregation increases eligibility for lower MER tiers
  - Upgrade opportunities detected when client net worth increases
  - Fee projections assume 6% annual return
  - Fund performance must be compared to appropriate benchmark
  - Segregated funds combine underlying fund + insurance wrapper fees
```

### **FR-005: Navigation Architecture**

#### **SRP-Based Navigation System (FR-005.1)**
```yaml
Priority: High
Description: Single Responsibility Principle navigation architecture with provider pattern
Status: ✅ IMPLEMENTED (Commits: cc037fe4, 21ae3d51)

Design Principles:
  - SRP: Each component has single responsibility
  - SOLID: Interface-based design with dependency injection
  - DRY: Single source of truth for navigation items
  - Factory Pattern: Centralized object creation
  - Provider Pattern: Pluggable navigation sources

Architecture Components:
  Models (4 classes):
    - NavigationItem: Base class with access control
    - MenuItem: Dropdown menu items with children
    - DashboardCard: Card-based dashboard items with actions
    - BreadcrumbItem: Breadcrumb trail navigation
  
  Providers (8 implementations):
    - NavigationItemProvider: Interface for all providers
    - PortfolioItemsProvider: Portfolio-related navigation
    - StockAnalysisItemsProvider: Stock analysis features
    - DataManagementItemsProvider: Data management tools
    - ReportsItemsProvider: Report generation
    - AdminItemsProvider: Admin-only features
    - ProfileItemsProvider: User profile items
    - TradingStrategiesItemsProvider: Trading strategy tools
    - DatabaseNavigationProvider: Database-driven items (optional)
  
  Services (3 classes):
    - NavigationBuilder: Build navigation menus
    - DashboardCardBuilder: Build dashboard cards
    - BreadcrumbBuilder: Build breadcrumb trails
  
  Factory (1 class):
    - NavigationFactory: Create configured instances

Acceptance Criteria:
  ✅ Single source of truth for navigation definitions
  ✅ Role-based access control (admin, user, guest)
  ✅ Two display modes: hidden (invisible) or greyed_out (visible but disabled)
  ✅ Consistent icons, titles, URLs across all pages
  ✅ Active state detection for current page
  ✅ Dropdown menu support with parent-child hierarchy
  ✅ Dashboard cards with multiple action buttons
  ✅ Configurable via config/navigation.php

Implementation Stats:
  - Files created: 24 (17 code + 5 docs + 1 config + 1 schema)
  - Lines of code: ~1,936 lines
  - Test coverage: 28 unit tests all passing
  - Performance: 80-90% faster with caching enabled

Configuration Options:
  - restricted_items_mode: 'hidden' | 'greyed_out'
  - cache_enabled: true | false
  - cache_duration: seconds (default 3600)
  - show_icons: true | false
  - show_restriction_tooltip: true | false
  - admin_roles: array of role names
```

#### **Navigation Caching (FR-005.2)**
```yaml
Priority: Medium
Description: File-based caching for improved navigation performance
Status: ✅ IMPLEMENTED (Commit: 21ae3d51)

Acceptance Criteria:
  ✅ Cache navigation items per user role
  ✅ Separate cache for admin vs normal users
  ✅ Configurable cache duration
  ✅ Automatic cache expiration
  ✅ Manual cache clearing capability
  ✅ Cache stored in cache/ directory

Performance Metrics:
  - Without cache: 15-25ms build time
  - With cache: 1-3ms build time
  - Improvement: 80-90% faster

Cache Key Format:
  - Navigation: nav_menu_{role}_{admin/user}.cache
  - Dashboard: dashboard_cards_{role}_{admin/user}.cache

Implementation:
  - File-based serialization (no Redis/Memcached needed)
  - getCachedItems() - read from cache
  - cacheItems() - write to cache
  - clearCache() - invalidate cache
```

#### **Breadcrumb Navigation (FR-005.3)**
```yaml
Priority: Low
Description: Breadcrumb trails for improved navigation UX
Status: ✅ IMPLEMENTED (Commit: 21ae3d51)

Acceptance Criteria:
  ✅ Automatic breadcrumb generation for pages
  ✅ Predefined trails for common pages
  ✅ Custom trail support via addBreadcrumbTrail()
  ✅ Bootstrap-compatible HTML output
  ✅ Last item marked with aria-current="page"
  ✅ Array format for JSON APIs

Predefined Trails:
  - Dashboard pages
  - Portfolio pages
  - Admin pages (hierarchical)
  - Profile pages
  - Trading strategy pages

Usage:
  $breadcrumbs = NavigationFactory::createBreadcrumbBuilder($user);
  echo $breadcrumbs->renderBreadcrumbs('current_page.php');
```

#### **Database-Driven Navigation (FR-005.4)**
```yaml
Priority: Low
Description: Optional database storage for dynamic navigation management
Status: ✅ IMPLEMENTED (Commit: 21ae3d51)

Database Schema:
  Tables:
    - navigation_items: Main items table
    - navigation_item_actions: Card action buttons
  
  Indexes: 5 indexes for performance
  Sample Data: Pre-populated with existing items

Acceptance Criteria:
  ✅ Store navigation items in database
  ✅ Support hierarchical menus (parent_id)
  ✅ CRUD operations (add, update, delete)
  ✅ Action button management
  ✅ Enable/disable items dynamically
  ✅ Sort order control
  ✅ Item type: menu | card | both

Future Enhancement:
  - Admin UI for managing items (not yet implemented)
  - Drag-and-drop reordering
  - Item versioning and rollback
  - A/B testing support
```

---

## 🔒 **Non-Functional Requirements**

### **Performance Requirements (NFR-001)**

#### **Response Time**
```yaml
Page Load Times:
  - Dashboard: < 2 seconds
  - Portfolio views: < 3 seconds
  - Reports generation: < 10 seconds
  - Chart rendering: < 5 seconds

API Response Times:
  - Authentication: < 500ms
  - Data retrieval: < 1 second
  - Trade execution: < 2 seconds
  - Batch operations: < 30 seconds

Throughput:
  - Concurrent users: 100+ simultaneous
  - API requests: 1000+ per minute
  - Database queries: 10,000+ per hour
```

#### **Scalability**
```yaml
Horizontal Scaling:
  - Load balancer compatible
  - Session sharing across instances
  - Database connection pooling
  - CDN integration ready

Vertical Scaling:
  - Memory efficient code
  - CPU optimization
  - Database query optimization
  - Caching strategies
```

### **Security Requirements (NFR-002)**

#### **Data Protection**
```yaml
Encryption:
  - HTTPS/TLS 1.3 for all connections
  - Database encryption at rest
  - API key encryption in storage
  - Password hashing (bcrypt/argon2)

Authentication:
  - Multi-factor authentication support
  - JWT token management
  - Session timeout controls
  - Password policy enforcement

Authorization:
  - Role-based access control
  - API endpoint protection
  - Resource-level permissions
  - Audit logging for all actions
```

### **Reliability Requirements (NFR-003)**

#### **Availability**
```yaml
Uptime Targets:
  - System availability: 99.5% (4.38 hours/month downtime)
  - Database availability: 99.9%
  - API availability: 99.7%
  - Trading system: 99.9% during market hours

Maintenance Windows:
  - Planned maintenance: Weekends only
  - Emergency patches: Outside market hours
  - Database maintenance: Off-peak hours
  - Notification: 24 hours advance notice
```

---

## 🔄 **Integration Requirements**

### **External APIs (INT-001)**

#### **Market Data Providers**
```yaml
Primary Sources:
  - Yahoo Finance: Free tier, rate limited
  - Alpha Vantage: Premium features, 500 calls/day free
  - IEX Cloud: Professional grade, paid plans

Data Requirements:
  - Real-time quotes during market hours
  - Historical OHLCV data (5 years minimum)
  - Corporate actions (splits, dividends)
  - Fundamental data (P/E ratios, market cap)

Failover Strategy:
  - Primary source failure detection
  - Automatic secondary source activation
  - Data quality validation
  - Manual source override capability
```

---

## 📊 **Data Requirements**

### **Data Models (DATA-001)**

#### **User Data Model**
```yaml
User Entity:
  - user_id: Primary key, auto-increment
  - username: Unique, 3-30 characters
  - email: Unique, valid email format
  - password_hash: bcrypt hashed
  - role: enum(guest, user, admin)
  - created_at: timestamp
  - last_login: timestamp
  - is_active: boolean

Portfolio Entity:
  - portfolio_id: Primary key, auto-increment
  - user_id: Foreign key to users
  - name: 3-50 characters
  - description: 500 characters max
  - base_currency: ISO 4217 currency code
  - created_at: timestamp
  - is_active: boolean
```

### **Data Validation (DATA-002)**

#### **Input Validation Rules**
```yaml
Financial Data:
  - Stock symbols: 1-10 alphanumeric characters
  - Share quantities: Positive numbers, max 6 decimal places
  - Prices: Positive numbers, max 4 decimal places
  - Percentages: -100% to +1000% range

User Data:
  - Usernames: Alphanumeric + underscore, no spaces
  - Passwords: 8+ chars, mixed case, numbers, symbols
  - Email addresses: RFC 5322 compliant
  - Names: Unicode letters, spaces, hyphens, apostrophes
```

---

## 🧪 **Testing Requirements**

### **Test Coverage Standards (TEST-001)**

#### **Unit Testing**
```yaml
Coverage Targets:
  - PHP code: 85% minimum coverage
  - Python code: 80% minimum coverage
  - Critical functions: 95% coverage
  - Database operations: 90% coverage

Test Framework:
  - PHPUnit for PHP testing
  - pytest for Python testing
  - Mock objects for external dependencies
  - Test database isolation
```

---

## 📈 **Business Requirements**

### **Market Analysis (BIZ-001)**

#### **Target Market**
```yaml
Primary Users:
  - Individual retail investors
  - Small investment clubs
  - Financial advisors with <100 clients
  - Educational institutions

Market Size:
  - US retail investors: 58 million
  - Global DIY investors: 200+ million
  - Market growth: 8-12% annually
  - Addressable market: $50B+ globally
```

---

## 📋 **Acceptance Criteria**

### **Definition of Done (DOD-001)**

#### **Feature Completion Checklist**
```yaml
Development Complete:
  ✅ Code implementation finished
  ✅ Unit tests written and passing
  ✅ Integration tests passing
  ✅ Code review approved
  ✅ Documentation updated

Quality Assurance:
  ✅ Functional testing complete
  ✅ Performance testing passed
  ✅ Security testing passed
  ✅ User acceptance testing approved
  ✅ Accessibility testing passed

Deployment Ready:
  ✅ Production deployment tested
  ✅ Rollback plan documented
  ✅ Monitoring configured
  ✅ User documentation updated
  ✅ Support team trained
```

---

**📝 Document Version:** 1.0  
**📅 Last Updated:** December 2024  
**👤 Document Owner:** Development Team  
**📧 Contact:** nathanbsmith.business@gmail.com
