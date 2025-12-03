-- User Risk Management Preferences Table
-- Stores per-user risk management configuration for trading and backtesting

CREATE TABLE IF NOT EXISTS user_risk_preferences (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    
    -- Position Sizing
    default_position_size DECIMAL(5,4) DEFAULT 0.10, -- 10% of capital per position
    max_positions INTEGER DEFAULT 5,                  -- Maximum concurrent positions
    max_portfolio_exposure DECIMAL(5,4) DEFAULT 1.00, -- Max 100% of capital deployed
    
    -- Fixed Stop Loss & Take Profit
    default_stop_loss DECIMAL(5,4),                   -- e.g., 0.10 = 10% stop loss
    default_take_profit DECIMAL(5,4),                 -- e.g., 0.20 = 20% take profit
    default_max_holding_days INTEGER,                 -- Maximum days to hold position
    
    -- Trailing Stop Loss
    enable_trailing_stop BOOLEAN DEFAULT 0,
    trailing_stop_activation DECIMAL(5,4) DEFAULT 0.05, -- Activate after 5% gain
    trailing_stop_distance DECIMAL(5,4) DEFAULT 0.10,   -- Trail 10% below highest price
    
    -- Partial Profit Taking
    enable_partial_profits BOOLEAN DEFAULT 0,
    partial_profit_levels TEXT,                       -- JSON array: [{"profit": 0.10, "sell_pct": 0.25}, ...]
    
    -- Risk Profile
    risk_profile VARCHAR(20) DEFAULT 'balanced',      -- conservative, balanced, aggressive
    
    -- Backtesting Options
    commission_rate DECIMAL(6,5) DEFAULT 0.001,       -- 0.1% commission
    slippage_rate DECIMAL(6,5) DEFAULT 0.0005,        -- 0.05% slippage
    initial_capital DECIMAL(15,2) DEFAULT 100000.00,  -- $100,000 starting capital
    
    -- Strategy-Specific Overrides (JSON)
    strategy_overrides TEXT,                          -- Per-strategy risk settings
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_user_risk_prefs_user ON user_risk_preferences(user_id);
CREATE INDEX idx_user_risk_prefs_profile ON user_risk_preferences(risk_profile);

-- Insert default risk profiles as templates
INSERT INTO user_risk_preferences (user_id, risk_profile, default_position_size, max_positions, default_stop_loss, default_take_profit, 
                                   enable_trailing_stop, trailing_stop_activation, trailing_stop_distance,
                                   enable_partial_profits, partial_profit_levels) VALUES
-- Conservative Profile (user_id = 0 is template)
(0, 'conservative', 0.05, 3, 0.08, 0.12, 1, 0.03, 0.08, 1, 
 '[{"profit": 0.05, "sell_pct": 0.20}, {"profit": 0.10, "sell_pct": 0.50}, {"profit": 0.15, "sell_pct": 1.00}]'),

-- Balanced Profile (template)
(0, 'balanced', 0.10, 5, 0.10, 0.20, 1, 0.05, 0.10, 1,
 '[{"profit": 0.10, "sell_pct": 0.25}, {"profit": 0.20, "sell_pct": 0.50}, {"profit": 0.30, "sell_pct": 1.00}]'),

-- Aggressive Profile (template)
(0, 'aggressive', 0.15, 7, 0.15, 0.30, 1, 0.10, 0.15, 1,
 '[{"profit": 0.15, "sell_pct": 0.30}, {"profit": 0.40, "sell_pct": 1.00}]');

-- Example: Populate default preferences for existing users
-- INSERT INTO user_risk_preferences (user_id, risk_profile)
-- SELECT id, 'balanced' FROM users WHERE id NOT IN (SELECT user_id FROM user_risk_preferences);
