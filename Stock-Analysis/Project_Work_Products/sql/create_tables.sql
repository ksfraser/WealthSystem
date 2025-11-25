-- Stock Analysis Extension Database Schema
-- MySQL database schema for storing stock analysis data and portfolio information

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS stock_analysis CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE stock_analysis;

-- Stock price data table
CREATE TABLE IF NOT EXISTS stock_prices (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    open_price DECIMAL(10,4) NOT NULL,
    high_price DECIMAL(10,4) NOT NULL,
    low_price DECIMAL(10,4) NOT NULL,
    close_price DECIMAL(10,4) NOT NULL,
    adj_close_price DECIMAL(10,4) NOT NULL,
    volume BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, date),
    INDEX idx_symbol (symbol),
    INDEX idx_date (date)
);

-- Stock fundamental data
CREATE TABLE IF NOT EXISTS stock_fundamentals (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL UNIQUE,
    company_name VARCHAR(255) NOT NULL,
    sector VARCHAR(100),
    industry VARCHAR(100),
    market_cap BIGINT,
    enterprise_value BIGINT,
    pe_ratio DECIMAL(8,2),
    forward_pe DECIMAL(8,2),
    peg_ratio DECIMAL(8,2),
    price_to_book DECIMAL(8,2),
    price_to_sales DECIMAL(8,2),
    debt_to_equity DECIMAL(8,2),
    return_on_equity DECIMAL(8,2),
    return_on_assets DECIMAL(8,2),
    profit_margin DECIMAL(8,2),
    operating_margin DECIMAL(8,2),
    gross_margin DECIMAL(8,2),
    dividend_yield DECIMAL(8,4),
    payout_ratio DECIMAL(8,2),
    beta DECIMAL(8,4),
    revenue_growth DECIMAL(8,2),
    earnings_growth DECIMAL(8,2),
    current_ratio DECIMAL(8,2),
    quick_ratio DECIMAL(8,2),
    cash_per_share DECIMAL(8,2),
    book_value_per_share DECIMAL(8,2),
    analyst_rating VARCHAR(20),
    target_price DECIMAL(10,4),
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sector (sector),
    INDEX idx_market_cap (market_cap),
    INDEX idx_pe_ratio (pe_ratio)
);

-- Technical indicators
CREATE TABLE IF NOT EXISTS technical_indicators (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    date DATE NOT NULL,
    sma_20 DECIMAL(10,4),
    sma_50 DECIMAL(10,4),
    sma_200 DECIMAL(10,4),
    ema_12 DECIMAL(10,4),
    ema_26 DECIMAL(10,4),
    macd DECIMAL(10,4),
    macd_signal DECIMAL(10,4),
    macd_histogram DECIMAL(10,4),
    rsi DECIMAL(8,2),
    bollinger_upper DECIMAL(10,4),
    bollinger_middle DECIMAL(10,4),
    bollinger_lower DECIMAL(10,4),
    stochastic_k DECIMAL(8,2),
    stochastic_d DECIMAL(8,2),
    williams_r DECIMAL(8,2),
    atr DECIMAL(10,4),
    obv BIGINT,
    vwap DECIMAL(10,4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, date),
    INDEX idx_symbol (symbol),
    INDEX idx_date (date)
);

-- Analysis results
CREATE TABLE IF NOT EXISTS analysis_results (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    analysis_date DATE NOT NULL,
    fundamental_score DECIMAL(5,2),
    technical_score DECIMAL(5,2),
    momentum_score DECIMAL(5,2),
    sentiment_score DECIMAL(5,2),
    overall_score DECIMAL(5,2),
    recommendation ENUM('STRONG_BUY', 'BUY', 'HOLD', 'SELL', 'STRONG_SELL'),
    target_price DECIMAL(10,4),
    risk_rating ENUM('LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH'),
    confidence_level DECIMAL(5,2),
    analysis_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_symbol_date (symbol, analysis_date),
    INDEX idx_overall_score (overall_score),
    INDEX idx_recommendation (recommendation),
    INDEX idx_analysis_date (analysis_date)
);

-- Portfolio management
CREATE TABLE IF NOT EXISTS portfolios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    initial_value DECIMAL(15,2) NOT NULL,
    current_value DECIMAL(15,2),
    cash_balance DECIMAL(15,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Portfolio positions
CREATE TABLE IF NOT EXISTS portfolio_positions (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    quantity DECIMAL(12,4) NOT NULL,
    avg_cost DECIMAL(10,4) NOT NULL,
    current_price DECIMAL(10,4),
    position_value DECIMAL(15,2),
    unrealized_pnl DECIMAL(15,2),
    realized_pnl DECIMAL(15,2),
    stop_loss DECIMAL(10,4),
    take_profit DECIMAL(10,4),
    entry_date DATE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_portfolio_symbol (portfolio_id, symbol),
    INDEX idx_portfolio_id (portfolio_id),
    INDEX idx_symbol (symbol)
);

-- Trade log
CREATE TABLE IF NOT EXISTS trade_log (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    portfolio_id INT NOT NULL,
    symbol VARCHAR(10) NOT NULL,
    trade_type ENUM('BUY', 'SELL') NOT NULL,
    quantity DECIMAL(12,4) NOT NULL,
    price DECIMAL(10,4) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    commission DECIMAL(8,2) DEFAULT 0,
    trade_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    strategy VARCHAR(100),
    notes TEXT,
    front_accounting_ref VARCHAR(50),
    FOREIGN KEY (portfolio_id) REFERENCES portfolios(id) ON DELETE CASCADE,
    INDEX idx_portfolio_id (portfolio_id),
    INDEX idx_symbol (symbol),
    INDEX idx_trade_date (trade_date)
);

-- Watchlist
CREATE TABLE IF NOT EXISTS watchlist (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10) NOT NULL,
    reason TEXT,
    target_price DECIMAL(10,4),
    added_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('ACTIVE', 'TRIGGERED', 'REMOVED') DEFAULT 'ACTIVE',
    INDEX idx_symbol (symbol),
    INDEX idx_status (status)
);

-- FrontAccounting integration tracking
CREATE TABLE IF NOT EXISTS front_accounting_sync (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    trade_id BIGINT NOT NULL,
    fa_transaction_id VARCHAR(50),
    fa_reference VARCHAR(100),
    sync_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('PENDING', 'SYNCED', 'FAILED') DEFAULT 'PENDING',
    error_message TEXT,
    FOREIGN KEY (trade_id) REFERENCES trade_log(id) ON DELETE CASCADE,
    INDEX idx_trade_id (trade_id),
    INDEX idx_status (status)
);

-- Market data sources tracking
CREATE TABLE IF NOT EXISTS data_sources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_name VARCHAR(50) NOT NULL UNIQUE,
    api_endpoint VARCHAR(255),
    last_update TIMESTAMP,
    status ENUM('ACTIVE', 'INACTIVE', 'ERROR') DEFAULT 'ACTIVE',
    error_count INT DEFAULT 0,
    rate_limit_per_day INT,
    requests_today INT DEFAULT 0,
    reset_date DATE DEFAULT (CURRENT_DATE)
);

-- Insert default portfolio
INSERT IGNORE INTO portfolios (id, name, description, initial_value, current_value, cash_balance) 
VALUES (1, 'Main Portfolio', 'Primary stock analysis portfolio', 100000.00, 100000.00, 100000.00);

-- Insert default data sources
INSERT IGNORE INTO data_sources (source_name, api_endpoint, rate_limit_per_day) VALUES
('Yahoo Finance', 'https://query1.finance.yahoo.com/v7/finance/download/', 2000),
('Finnhub', 'https://finnhub.io/api/v1/', 60),
('Alpha Vantage', 'https://www.alphavantage.co/query', 500),
('Stooq', 'https://stooq.com/q/d/l/', 1000);

-- Views for common queries
CREATE OR REPLACE VIEW portfolio_summary AS
SELECT 
    p.id,
    p.name,
    p.cash_balance,
    COALESCE(SUM(pp.position_value), 0) as total_positions_value,
    p.cash_balance + COALESCE(SUM(pp.position_value), 0) as total_portfolio_value,
    COALESCE(SUM(pp.unrealized_pnl), 0) as total_unrealized_pnl,
    COALESCE(SUM(pp.realized_pnl), 0) as total_realized_pnl,
    COUNT(pp.id) as total_positions
FROM portfolios p
LEFT JOIN portfolio_positions pp ON p.id = pp.portfolio_id AND pp.quantity > 0
GROUP BY p.id, p.name, p.cash_balance;

CREATE OR REPLACE VIEW top_performers AS
SELECT 
    pp.symbol,
    sf.company_name,
    sf.sector,
    pp.quantity,
    pp.avg_cost,
    pp.current_price,
    pp.position_value,
    pp.unrealized_pnl,
    (pp.unrealized_pnl / (pp.avg_cost * pp.quantity)) * 100 as return_pct,
    ar.overall_score,
    ar.recommendation
FROM portfolio_positions pp
LEFT JOIN stock_fundamentals sf ON pp.symbol = sf.symbol
LEFT JOIN analysis_results ar ON pp.symbol = ar.symbol 
    AND ar.analysis_date = (SELECT MAX(analysis_date) FROM analysis_results WHERE symbol = pp.symbol)
WHERE pp.quantity > 0
ORDER BY return_pct DESC;

CREATE OR REPLACE VIEW recent_analysis AS
SELECT 
    ar.*,
    sf.company_name,
    sf.sector,
    sf.market_cap
FROM analysis_results ar
LEFT JOIN stock_fundamentals sf ON ar.symbol = sf.symbol
WHERE ar.analysis_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
ORDER BY ar.overall_score DESC, ar.analysis_date DESC;
