-- Per-symbol technical indicator table template
-- For each symbol, create a table named techvals_{SYMBOL} (e.g., techvals_AAPL)
-- Each row stores all indicator values for a given day

-- Example for AAPL:
--
-- CREATE TABLE IF NOT EXISTS techvals_AAPL (
--     id INTEGER PRIMARY KEY AUTOINCREMENT,
--     date DATE NOT NULL,
--     rsi_14 DECIMAL(10,6),
--     sma_20 DECIMAL(10,6),
--     ema_20 DECIMAL(10,6),
--     macd DECIMAL(10,6),
--     macd_signal DECIMAL(10,6),
--     macd_hist DECIMAL(10,6),
--     bbands_upper DECIMAL(10,6),
--     bbands_middle DECIMAL(10,6),
--     bbands_lower DECIMAL(10,6),
--     -- add more indicators as needed
--     timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
--     UNIQUE(date)
-- );

-- To automate, generate CREATE TABLE statements for each symbol as needed.
-- Technical Indicators Storage Schema
-- Stores calculated TA-Lib indicator values for all stocks and dates

CREATE TABLE IF NOT EXISTS technical_indicator_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    symbol VARCHAR(10) NOT NULL,
    indicator_name VARCHAR(50) NOT NULL, -- e.g. RSI, SMA, EMA, MACD, BBANDS
    indicator_params JSON, -- e.g. {"period":14}
    value DECIMAL(20,8),
    value2 DECIMAL(20,8), -- for indicators with multiple outputs (e.g. MACD signal)
    value3 DECIMAL(20,8), -- for indicators with multiple outputs (e.g. MACD hist, BBANDS upper/middle/lower)
    date DATE NOT NULL,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(symbol, indicator_name, indicator_params, date)
);

-- Example: For MACD, value=macd, value2=signal, value3=hist
-- For BBANDS, value=upper, value2=middle, value3=lower

CREATE INDEX idx_indicator_symbol_date ON technical_indicator_values(symbol, indicator_name, date);