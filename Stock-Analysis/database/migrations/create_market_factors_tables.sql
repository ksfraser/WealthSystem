-- Market Factors Database Schema
-- This schema supports comprehensive market factor analysis including
-- sectors, indices, forex, economic indicators, earnings, and correlations

-- Main market factors table
CREATE TABLE IF NOT EXISTS market_factors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(20) NOT NULL,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL, -- sector, index, forex, economic, earnings, commodity
    value DECIMAL(15,4) NOT NULL,
    change_value DECIMAL(15,4) DEFAULT 0,
    change_percent DECIMAL(8,4) DEFAULT 0,
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, timestamp)
);

-- Sector performance tracking
CREATE TABLE IF NOT EXISTS sector_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sector_code VARCHAR(10) NOT NULL,
    sector_name VARCHAR(100) NOT NULL,
    classification VARCHAR(20) DEFAULT 'GICS', -- GICS, ICB, etc.
    performance_value DECIMAL(8,4) NOT NULL,
    change_percent DECIMAL(8,4) DEFAULT 0,
    market_cap_weight DECIMAL(8,4) DEFAULT 0,
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(sector_code, timestamp)
);

-- Index performance tracking
CREATE TABLE IF NOT EXISTS index_performance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    index_symbol VARCHAR(10) NOT NULL,
    index_name VARCHAR(100) NOT NULL,
    region VARCHAR(50) NOT NULL,
    asset_class VARCHAR(50) DEFAULT 'equity',
    value DECIMAL(12,4) NOT NULL,
    change_percent DECIMAL(8,4) DEFAULT 0,
    constituents INTEGER DEFAULT 0,
    market_cap DECIMAL(15,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'USD',
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(index_symbol, timestamp)
);

-- Forex rates tracking
CREATE TABLE IF NOT EXISTS forex_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    base_currency VARCHAR(3) NOT NULL,
    quote_currency VARCHAR(3) NOT NULL,
    pair VARCHAR(6) NOT NULL,
    rate DECIMAL(12,6) NOT NULL,
    bid DECIMAL(12,6) DEFAULT 0,
    ask DECIMAL(12,6) DEFAULT 0,
    spread DECIMAL(8,6) DEFAULT 0,
    change_percent DECIMAL(8,4) DEFAULT 0,
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(pair, timestamp)
);

-- Economic indicators tracking
CREATE TABLE IF NOT EXISTS economic_indicators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    indicator_code VARCHAR(20) NOT NULL,
    indicator_name VARCHAR(255) NOT NULL,
    country VARCHAR(2) NOT NULL,
    frequency VARCHAR(20) DEFAULT 'monthly', -- daily, weekly, monthly, quarterly, annual
    unit VARCHAR(50),
    current_value DECIMAL(15,4) NOT NULL,
    previous_value DECIMAL(15,4) DEFAULT 0,
    forecast_value DECIMAL(15,4) DEFAULT 0,
    change_percent DECIMAL(8,4) DEFAULT 0,
    importance VARCHAR(10) DEFAULT 'medium', -- high, medium, low
    release_date DATETIME,
    source VARCHAR(100),
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(indicator_code, release_date)
);

-- Earnings data tracking
CREATE TABLE IF NOT EXISTS earnings_data (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    company_name VARCHAR(255),
    report_date DATE NOT NULL,
    period_end_date DATE NOT NULL,
    fiscal_quarter INTEGER,
    fiscal_year INTEGER,
    eps_actual DECIMAL(8,4),
    eps_estimate DECIMAL(8,4),
    eps_surprise DECIMAL(8,4),
    revenue_actual DECIMAL(15,2),
    revenue_estimate DECIMAL(15,2),
    revenue_surprise_percent DECIMAL(8,4),
    guidance_raised BOOLEAN DEFAULT FALSE,
    guidance_lowered BOOLEAN DEFAULT FALSE,
    beat_expectations BOOLEAN DEFAULT FALSE,
    sector VARCHAR(50),
    market_cap DECIMAL(15,2),
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, report_date)
);

-- Commodity prices tracking
CREATE TABLE IF NOT EXISTS commodity_prices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    commodity_symbol VARCHAR(10) NOT NULL,
    commodity_name VARCHAR(100) NOT NULL,
    category VARCHAR(50), -- energy, metals, agriculture, livestock
    price DECIMAL(12,4) NOT NULL,
    change_percent DECIMAL(8,4) DEFAULT 0,
    unit VARCHAR(20),
    exchange VARCHAR(20),
    currency VARCHAR(3) DEFAULT 'USD',
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(commodity_symbol, timestamp)
);

-- Factor correlations tracking
CREATE TABLE IF NOT EXISTS factor_correlations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    factor1_symbol VARCHAR(20) NOT NULL,
    factor2_symbol VARCHAR(20) NOT NULL,
    correlation_coefficient DECIMAL(6,4) NOT NULL, -- -1 to 1
    period_days INTEGER NOT NULL,
    calculation_date DATE NOT NULL,
    significance_level DECIMAL(4,3), -- p-value
    sample_size INTEGER,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(factor1_symbol, factor2_symbol, calculation_date, period_days)
);

-- Market sentiment tracking
CREATE TABLE IF NOT EXISTS market_sentiment (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sentiment_date DATE NOT NULL,
    overall_sentiment VARCHAR(20), -- bullish, bearish, neutral
    confidence_score DECIMAL(4,3), -- 0 to 1
    bullish_factors INTEGER DEFAULT 0,
    bearish_factors INTEGER DEFAULT 0,
    neutral_factors INTEGER DEFAULT 0,
    total_factors INTEGER DEFAULT 0,
    volatility_index DECIMAL(8,4),
    fear_greed_index DECIMAL(8,4),
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(sentiment_date)
);

-- Tax and regulatory factors
CREATE TABLE IF NOT EXISTS tax_regulatory_factors (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    factor_code VARCHAR(20) NOT NULL,
    factor_name VARCHAR(255) NOT NULL,
    country VARCHAR(2) NOT NULL,
    category VARCHAR(50), -- tax_rate, regulation, policy, tariff
    current_value DECIMAL(8,4),
    previous_value DECIMAL(8,4),
    effective_date DATE,
    impact_assessment VARCHAR(20), -- positive, negative, neutral
    affected_sectors JSON,
    description TEXT,
    source VARCHAR(100),
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(factor_code, effective_date)
);

-- Interest rates and yield curves
CREATE TABLE IF NOT EXISTS interest_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    rate_type VARCHAR(50) NOT NULL, -- fed_funds, treasury_10y, libor, etc.
    country VARCHAR(2) NOT NULL,
    maturity VARCHAR(20), -- overnight, 1m, 3m, 6m, 1y, 2y, 5y, 10y, 30y
    rate DECIMAL(8,4) NOT NULL,
    change_bps INTEGER DEFAULT 0, -- basis points
    timestamp DATETIME NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(rate_type, country, maturity, timestamp)
);

-- Portfolio factor exposure tracking
CREATE TABLE IF NOT EXISTS portfolio_factor_exposure (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    portfolio_id VARCHAR(50) NOT NULL,
    factor_symbol VARCHAR(20) NOT NULL,
    exposure_value DECIMAL(12,4) NOT NULL,
    exposure_percent DECIMAL(8,4) NOT NULL,
    exposure_type VARCHAR(20), -- direct, indirect, correlated
    calculation_date DATE NOT NULL,
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(portfolio_id, factor_symbol, calculation_date)
);

-- Indexes for performance
CREATE INDEX idx_market_factors_symbol_timestamp ON market_factors(symbol, timestamp);
CREATE INDEX idx_market_factors_type_timestamp ON market_factors(type, timestamp);
CREATE INDEX idx_sector_performance_timestamp ON sector_performance(timestamp);
CREATE INDEX idx_index_performance_timestamp ON index_performance(timestamp);
CREATE INDEX idx_forex_rates_timestamp ON forex_rates(timestamp);
CREATE INDEX idx_economic_indicators_country ON economic_indicators(country, timestamp);
CREATE INDEX idx_earnings_data_symbol_date ON earnings_data(symbol, report_date);
CREATE INDEX idx_commodity_prices_timestamp ON commodity_prices(timestamp);
CREATE INDEX idx_factor_correlations_symbols ON factor_correlations(factor1_symbol, factor2_symbol);
CREATE INDEX idx_market_sentiment_date ON market_sentiment(sentiment_date);
CREATE INDEX idx_tax_regulatory_country ON tax_regulatory_factors(country, effective_date);
CREATE INDEX idx_interest_rates_country_type ON interest_rates(country, rate_type, timestamp);
CREATE INDEX idx_portfolio_exposure_portfolio ON portfolio_factor_exposure(portfolio_id, calculation_date);

-- Views for commonly used queries

-- Current market factors view
CREATE VIEW current_market_factors AS
SELECT 
    symbol,
    name,
    type,
    value,
    change_value,
    change_percent,
    timestamp,
    metadata,
    ROW_NUMBER() OVER (PARTITION BY symbol ORDER BY timestamp DESC) as rn
FROM market_factors
WHERE rn = 1;

-- Latest sector performance view
CREATE VIEW latest_sector_performance AS
SELECT 
    sector_code,
    sector_name,
    classification,
    performance_value,
    change_percent,
    market_cap_weight,
    timestamp,
    ROW_NUMBER() OVER (PARTITION BY sector_code ORDER BY timestamp DESC) as rn
FROM sector_performance
WHERE rn = 1;

-- Current forex rates view
CREATE VIEW current_forex_rates AS
SELECT 
    base_currency,
    quote_currency,
    pair,
    rate,
    bid,
    ask,
    spread,
    change_percent,
    timestamp,
    ROW_NUMBER() OVER (PARTITION BY pair ORDER BY timestamp DESC) as rn
FROM forex_rates
WHERE rn = 1;

-- Latest economic indicators view
CREATE VIEW latest_economic_indicators AS
SELECT 
    indicator_code,
    indicator_name,
    country,
    frequency,
    unit,
    current_value,
    previous_value,
    forecast_value,
    change_percent,
    importance,
    release_date,
    source,
    timestamp,
    ROW_NUMBER() OVER (PARTITION BY indicator_code ORDER BY timestamp DESC) as rn
FROM economic_indicators
WHERE rn = 1;

-- Factor performance summary view
CREATE VIEW factor_performance_summary AS
SELECT 
    type,
    COUNT(*) as factor_count,
    AVG(change_percent) as avg_change_percent,
    MAX(change_percent) as max_change_percent,
    MIN(change_percent) as min_change_percent,
    SUM(CASE WHEN change_percent > 0 THEN 1 ELSE 0 END) as positive_count,
    SUM(CASE WHEN change_percent < 0 THEN 1 ELSE 0 END) as negative_count,
    timestamp
FROM market_factors 
GROUP BY type, DATE(timestamp)
ORDER BY timestamp DESC;
