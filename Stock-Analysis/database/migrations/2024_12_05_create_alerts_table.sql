-- Alert Persistence Migration
-- Creates alerts table for persistent storage of user alerts

CREATE TABLE IF NOT EXISTS alerts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    symbol TEXT,
    condition_type TEXT NOT NULL,
    threshold REAL NOT NULL,
    email TEXT,
    throttle_minutes INTEGER DEFAULT 0,
    active INTEGER DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT,
    
    -- Indexes for performance
    INDEX idx_alerts_user_id (user_id),
    INDEX idx_alerts_symbol (symbol),
    INDEX idx_alerts_active (active),
    INDEX idx_alerts_created_at (created_at)
);

-- Alert condition types:
-- - price_above: Trigger when price goes above threshold
-- - price_below: Trigger when price goes below threshold
-- - percent_change: Trigger when price changes by threshold percent
-- - volume_above: Trigger when volume goes above threshold
-- - volume_below: Trigger when volume goes below threshold
-- - rsi_above: Trigger when RSI goes above threshold
-- - rsi_below: Trigger when RSI goes below threshold
-- - macd_bullish: Trigger on MACD bullish crossover
-- - macd_bearish: Trigger on MACD bearish crossover
