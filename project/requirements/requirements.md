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

### **FR-006: Portfolio Sector Analysis & Charting**

#### **Sector Allocation Calculation (FR-006.1)**
```yaml
Priority: High
Description: Calculate and visualize portfolio sector allocation
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Aggregate portfolio holdings by GICS sector
  ✅ Calculate percentage allocation for each sector
  ✅ Display allocation in pie chart format
  ✅ Color-coded sector visualization
  ✅ Real-time updates on portfolio changes
  ✅ Support for 11 GICS sectors

GICS Sectors:
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

Technical Details:
  - Service: SectorAnalysisChartService::calculateSectorAllocation()
  - DAO: SectorAnalysisDAOImpl::getPortfolioSectorData()
  - Chart: Chart.js pie chart with auto color generation
  - API: GET /api/sector-analysis.php?user_id={id}
```

#### **S&P 500 Benchmark Comparison (FR-006.2)**
```yaml
Priority: High
Description: Compare portfolio sector weights vs S&P 500 benchmark
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Retrieve S&P 500 sector weights (Q4 2025)
  ✅ Calculate portfolio vs benchmark deviation
  ✅ Identify overweight sectors (>5% deviation)
  ✅ Identify underweight sectors (<-5% deviation)
  ✅ Display comparison in bar chart format
  ✅ Color-coded bars (portfolio vs benchmark)

Benchmark Data:
  - Technology: 28.5%
  - Financials: 12.8%
  - Health Care: 12.5%
  - Consumer Discretionary: 10.2%
  - Communication Services: 8.7%
  - Industrials: 8.3%
  - Consumer Staples: 6.1%
  - Energy: 4.2%
  - Utilities: 2.5%
  - Real Estate: 2.4%
  - Materials: 2.3%

Technical Details:
  - Service: SectorAnalysisChartService::compareToBenchmark()
  - DAO: SectorAnalysisDAOImpl::getSP500SectorWeights()
  - Chart: Chart.js multi-dataset bar chart
  - Threshold: 5% deviation for overweight/underweight classification
```

#### **Concentration Risk Assessment (FR-006.3)**
```yaml
Priority: High
Description: Calculate portfolio concentration risk metrics
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Calculate Herfindahl-Hirschman Index (HHI)
  ✅ Identify top sector weight percentage
  ✅ Risk level classification (LOW/MEDIUM/HIGH)
  ✅ Display risk metrics in dashboard cards
  ✅ Color-coded risk indicators (green/yellow/red)

HHI Calculation:
  HHI = Σ(sector_weight²) × 10,000
  
Risk Levels:
  - LOW (HHI < 1,500): Diversified portfolio
  - MEDIUM (1,500 ≤ HHI < 2,500): Moderately concentrated
  - HIGH (HHI ≥ 2,500): Highly concentrated

Business Rules:
  - HHI ranges from 0 (infinite diversity) to 10,000 (single sector)
  - Top sector weight should not exceed 40% for LOW risk
  - Any sector >60% automatically triggers HIGH risk

Technical Details:
  - Service: SectorAnalysisChartService::calculateConcentrationRisk()
  - Returns: ['hhi' => float, 'top_sector_weight' => float, 'risk_level' => string]
  - UI: 3 metric cards with border colors based on risk
```

#### **Diversification Scoring (FR-006.4)**
```yaml
Priority: High
Description: 0-100 scoring system for portfolio diversification
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Calculate diversification score (0-100 scale)
  ✅ Consider sector count (40% weight)
  ✅ Consider max sector weight (35% weight)
  ✅ Consider HHI (30% weight)
  ✅ Display score with visual indicator
  ✅ Provide score interpretation

Scoring Algorithm:
  score = (sectorScore × 0.4) + (maxWeightScore × 0.35) + (hhiScore × 0.3)
  
  sectorScore:
    - 100: 9-11 sectors (excellent diversification)
    - 70: 6-8 sectors (good diversification)
    - 40: 3-5 sectors (moderate diversification)
    - 10: 1-2 sectors (poor diversification)
  
  maxWeightScore:
    - 100: ≤15% (excellent)
    - 70: ≤25% (good)
    - 40: ≤40% (moderate)
    - 10: >40% (poor)
  
  hhiScore:
    - 100: HHI ≤1,500 (diversified)
    - 70: HHI ≤2,000 (moderate)
    - 40: HHI ≤2,500 (concentrated)
    - 10: HHI >2,500 (highly concentrated)

Score Interpretation:
  - 80-100: Excellently diversified
  - 60-79: Well diversified
  - 40-59: Moderately diversified
  - 20-39: Poorly diversified
  - 0-19: Highly concentrated

Technical Details:
  - Service: SectorAnalysisChartService::calculateDiversificationScore()
  - Example: 9 sectors, max 15%, HHI 1200 → score = 77.5 (well diversified)
```

#### **Chart Visualization (FR-006.5)**
```yaml
Priority: High
Description: Interactive Chart.js visualizations for sector analysis
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Pie chart for sector allocation
  ✅ Bar chart for portfolio vs benchmark comparison
  ✅ Responsive design for mobile/tablet/desktop
  ✅ Tooltips with percentage values
  ✅ Legend with color mapping
  ✅ Loading states and error handling

Chart Features:
  Pie Chart:
    - Auto color generation (11 distinct colors)
    - Percentage labels on hover
    - Legend with sector names
    - Data labels plugin support
  
  Bar Chart:
    - Grouped bars (portfolio vs S&P 500)
    - Color coding: Blue (portfolio), Gray (benchmark)
    - Y-axis: 0-100% scale
    - X-axis: Sector names
    - Horizontal scrolling for mobile

Technical Details:
  - Library: Chart.js 4.4.0
  - Service: chart_service.js
  - Functions: createSectorPieChart(), createSectorComparisonChart()
  - Page: sector_analysis.php
```

### **FR-007: Index Benchmarking & Performance Comparison**

#### **Performance vs Major Indexes (FR-007.1)**
```yaml
Priority: High
Description: Compare stock/portfolio performance against market indexes
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Support SPX (S&P 500), IXIC (NASDAQ), DJI (Dow Jones), RUT (Russell 2000)
  ✅ Calculate total return for portfolio and index
  ✅ Calculate annualized return (for periods >12 months)
  ✅ Calculate relative performance (outperformance periods)
  ✅ Display cumulative returns in line chart
  ✅ Support multiple time periods (1M, 3M, 6M, 1Y, 3Y, 5Y)

Supported Indexes:
  - SPX: S&P 500 (large-cap US stocks)
  - IXIC: NASDAQ Composite (tech-focused)
  - DJI: Dow Jones Industrial Average (blue-chip)
  - RUT: Russell 2000 (small-cap)

Time Periods:
  - 1M: Last 30 days
  - 3M: Last 90 days
  - 6M: Last 180 days
  - 1Y: Last 365 days
  - 3Y: Last 1,095 days
  - 5Y: Last 1,825 days

Technical Details:
  - Service: IndexBenchmarkService::calculateTotalReturn()
  - Service: IndexBenchmarkService::calculateRelativePerformance()
  - DAO: IndexDataDAOImpl::getIndexData()
  - Chart: Chart.js line chart with dual datasets
  - API: GET /api/index-benchmark.php?symbol={sym}&index={idx}&period={per}
```

#### **Statistical Calculations (FR-007.2)**
```yaml
Priority: High
Description: Calculate beta, alpha, and correlation coefficients
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Calculate beta (systematic risk)
  ✅ Calculate alpha (excess return)
  ✅ Calculate correlation coefficient
  ✅ Handle edge cases (zero variance, missing data)
  ✅ Display in metrics comparison table
  ✅ Provide statistical interpretation

Beta (β):
  Formula: β = Cov(portfolio, index) / Var(index)
  Interpretation:
    - β = 1.0: Same volatility as market
    - β > 1.0: More volatile than market
    - β < 1.0: Less volatile than market
    - β < 0: Inverse correlation (rare)

Alpha (α):
  Formula: α = Rₚ - (Rₓ + β(Rₘ - Rₓ))
  Where:
    - Rₚ: Portfolio return
    - Rₘ: Market (index) return
    - Rₓ: Risk-free rate (0% assumed)
  Interpretation:
    - α > 0: Outperformance beyond risk-adjusted expectations
    - α = 0: Fair compensation for risk taken
    - α < 0: Underperformance relative to risk

Correlation (ρ):
  Formula: ρ = Cov(portfolio, index) / (σₚ × σₘ)
  Range: -1 to +1
  Interpretation:
    - ρ = +1: Perfect positive correlation
    - ρ = 0: No correlation
    - ρ = -1: Perfect negative correlation

Technical Details:
  - Service: IndexBenchmarkService::calculateBeta()
  - Service: IndexBenchmarkService::calculateAlpha()
  - Service: IndexBenchmarkService::calculateCorrelation()
  - Precision: 4 decimal places
```

#### **Risk-Adjusted Returns (FR-007.3)**
```yaml
Priority: High
Description: Calculate Sharpe ratio, Sortino ratio, and maximum drawdown
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Calculate Sharpe ratio (risk-adjusted return)
  ✅ Calculate Sortino ratio (downside risk-adjusted)
  ✅ Calculate maximum drawdown (peak-to-trough)
  ✅ Calculate Calmar ratio (return/drawdown)
  ✅ Display risk metrics in dedicated panel
  ✅ Provide interpretation guidelines

Sharpe Ratio:
  Formula: (Rₚ - Rₓ) / σₚ
  Where:
    - Rₚ: Portfolio return
    - Rₓ: Risk-free rate (0% assumed)
    - σₚ: Portfolio standard deviation
  Interpretation:
    - Sharpe > 3.0: Excellent risk-adjusted returns
    - Sharpe 2.0-3.0: Very good
    - Sharpe 1.0-2.0: Good
    - Sharpe 0.5-1.0: Acceptable
    - Sharpe < 0.5: Poor

Sortino Ratio:
  Formula: (Rₚ - Rₜ) / σ_downside
  Where:
    - Rₜ: Target return (often minimum acceptable return)
    - σ_downside: Standard deviation of negative returns only
  Advantage: Ignores upside volatility, focuses on downside risk
  Interpretation: Similar to Sharpe, but typically higher values

Maximum Drawdown:
  Formula: (Trough - Peak) / Peak
  Calculation:
    1. Track cumulative portfolio value
    2. Identify running maximum (peak)
    3. Calculate percentage decline from peak
    4. Maximum drawdown = largest decline observed
  Interpretation:
    - <10%: Low risk
    - 10-20%: Moderate risk
    - 20-30%: High risk
    - >30%: Very high risk

Technical Details:
  - Service: IndexBenchmarkService::calculateSharpeRatio()
  - Service: IndexBenchmarkService::calculateSortinoRatio()
  - Service: IndexBenchmarkService::calculateMaxDrawdown()
  - Risk-free rate: 0% (assumed for simplicity)
  - Annualization: √252 for daily data (trading days per year)
```

#### **Multiple Time Periods (FR-007.4)**
```yaml
Priority: High
Description: Support 6 time periods for flexible analysis
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ 1M (1 month): Short-term momentum
  ✅ 3M (3 months): Quarterly performance
  ✅ 6M (6 months): Semi-annual trends
  ✅ 1Y (1 year): Annual performance
  ✅ 3Y (3 years): Medium-term trends
  ✅ 5Y (5 years): Long-term analysis
  ✅ Dropdown selector on UI
  ✅ Automatic date range calculation

Period Mapping:
  - 1M: DateTime::modify('-30 days')
  - 3M: DateTime::modify('-90 days')
  - 6M: DateTime::modify('-180 days')
  - 1Y: DateTime::modify('-365 days')
  - 3Y: DateTime::modify('-1095 days')
  - 5Y: DateTime::modify('-1825 days')

Business Rules:
  - Annualized returns calculated for periods >12 months
  - Sharpe/Sortino ratios annualized using √(periods_per_year)
  - Maximum drawdown calculated over entire period
  - Beta/alpha calculated from aligned return series

Technical Details:
  - DAO: IndexDataDAOImpl::validatePeriod()
  - Query: WHERE date >= :start_date AND date <= :end_date
  - UI: Bootstrap select dropdown with 6 options
```

#### **Chart Visualization (FR-007.5)**
```yaml
Priority: High
Description: Interactive performance comparison charts and metrics tables
Status: ✅ IMPLEMENTED (December 4, 2025)

Acceptance Criteria:
  ✅ Line chart showing cumulative returns (portfolio vs index)
  ✅ Dual Y-axis support (if needed)
  ✅ Tooltips with date and return values
  ✅ Legend with color mapping
  ✅ Detailed metrics comparison table
  ✅ Risk metrics panel with interpretation
  ✅ Performance summary cards (4 key metrics)

Chart Features:
  Line Chart:
    - X-axis: Date (time series)
    - Y-axis: Cumulative return percentage
    - Dataset 1: Portfolio (blue line)
    - Dataset 2: Index (gray line)
    - Hover tooltips with date + return
    - Responsive design
    - Loading spinner during data fetch

Metrics Cards:
  1. Total Return: Portfolio return with color coding (green/red)
  2. Beta (β): Market volatility comparison
  3. Alpha (α): Excess return with color coding
  4. Sharpe Ratio: Risk-adjusted return

Metrics Table:
  Columns: Metric | Portfolio | Index | Difference
  Rows:
    - Total Return
    - Annualized Return (if applicable)
    - Beta
    - Alpha
    - Correlation
    - Sharpe Ratio
    - Sortino Ratio
    - Max Drawdown

Technical Details:
  - Library: Chart.js 4.4.0
  - Service: chart_service.js::createIndexPerformanceChart()
  - Service: chart_service.js::formatMetricsTable()
  - Page: index_benchmark.php
  - API: GET /api/index-benchmark.php
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
