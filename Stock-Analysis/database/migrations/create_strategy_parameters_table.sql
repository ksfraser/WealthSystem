-- Strategy Parameters Configuration Table
-- Stores configurable parameters for all trading strategies

CREATE TABLE IF NOT EXISTS strategy_parameters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    strategy_name VARCHAR(100) NOT NULL,
    parameter_key VARCHAR(100) NOT NULL,
    parameter_value TEXT NOT NULL,
    parameter_type VARCHAR(20) NOT NULL DEFAULT 'float', -- float, int, bool, string
    display_name VARCHAR(200) NOT NULL,
    description TEXT,
    category VARCHAR(50), -- e.g., 'Business Tenets', 'Risk Management', etc.
    min_value DECIMAL(20,6),
    max_value DECIMAL(20,6),
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(strategy_name, parameter_key)
);

CREATE INDEX idx_strategy_parameters_strategy ON strategy_parameters(strategy_name);
CREATE INDEX idx_strategy_parameters_active ON strategy_parameters(is_active);
CREATE INDEX idx_strategy_parameters_category ON strategy_parameters(category);

-- Insert default parameters for Warren Buffett Strategy
INSERT INTO strategy_parameters (strategy_name, parameter_key, parameter_value, parameter_type, display_name, description, category, min_value, max_value, display_order) VALUES
-- Business Tenets
('Warren Buffett Value Strategy', 'min_operating_history_years', '10', 'int', 'Minimum Operating History (Years)', 'Minimum years of consistent operating history required', 'Business Tenets', 1, 30, 1),
('Warren Buffett Value Strategy', 'require_simple_business', '1', 'bool', 'Require Simple Business', 'Only invest in businesses that are simple and understandable', 'Business Tenets', 0, 1, 2),
('Warren Buffett Value Strategy', 'require_favorable_prospects', '1', 'bool', 'Require Favorable Long-term Prospects', 'Business must have favorable long-term prospects', 'Business Tenets', 0, 1, 3),

-- Management Tenets
('Warren Buffett Value Strategy', 'min_insider_ownership_percent', '5.0', 'float', 'Minimum Insider Ownership (%)', 'Minimum percentage of insider ownership (management skin in the game)', 'Management Tenets', 0, 100, 4),
('Warren Buffett Value Strategy', 'require_share_buybacks', '0', 'bool', 'Require Share Buybacks', 'Company should have history of rational share buybacks', 'Management Tenets', 0, 1, 5),

-- Financial Tenets
('Warren Buffett Value Strategy', 'min_roe_percent', '15.0', 'float', 'Minimum ROE (%)', 'Minimum return on equity percentage', 'Financial Tenets', 0, 100, 6),
('Warren Buffett Value Strategy', 'min_profit_margin_percent', '20.0', 'float', 'Minimum Profit Margin (%)', 'Minimum net profit margin percentage', 'Financial Tenets', 0, 100, 7),
('Warren Buffett Value Strategy', 'max_debt_to_equity', '0.5', 'float', 'Maximum Debt-to-Equity', 'Maximum acceptable debt-to-equity ratio', 'Financial Tenets', 0, 5, 8),
('Warren Buffett Value Strategy', 'min_free_cash_flow_growth', '0.0', 'float', 'Minimum FCF Growth', 'Minimum free cash flow growth rate', 'Financial Tenets', -1, 5, 9),

-- Value Tenets
('Warren Buffett Value Strategy', 'margin_of_safety_percent', '25.0', 'float', 'Margin of Safety (%)', 'Required discount to intrinsic value (buy at 75% of intrinsic value)', 'Value Tenets', 0, 100, 10),
('Warren Buffett Value Strategy', 'discount_rate_percent', '10.0', 'float', 'Discount Rate (%)', 'Discount rate for DCF valuation (typically 10-year Treasury + 2-3%)', 'Value Tenets', 0, 50, 11),
('Warren Buffett Value Strategy', 'perpetual_growth_rate', '3.0', 'float', 'Perpetual Growth Rate (%)', 'Terminal growth rate for DCF calculation', 'Value Tenets', 0, 10, 12),

-- Moat Scoring
('Warren Buffett Value Strategy', 'moat_strength_weight', '0.3', 'float', 'Economic Moat Weight', 'Weight given to economic moat strength in scoring', 'Moat Scoring', 0, 1, 13),

-- Risk Management
('Warren Buffett Value Strategy', 'stop_loss_percent', '0.15', 'float', 'Stop Loss (%)', 'Stop loss percentage below purchase price', 'Risk Management', 0, 1, 14),
('Warren Buffett Value Strategy', 'take_profit_percent', '0.50', 'float', 'Take Profit (%)', 'Take profit target percentage gain', 'Risk Management', 0, 10, 15),
('Warren Buffett Value Strategy', 'max_position_size', '0.15', 'float', 'Maximum Position Size', 'Maximum position size as fraction of portfolio', 'Risk Management', 0, 1, 16);

-- Insert default parameters for GARP Strategy
INSERT INTO strategy_parameters (strategy_name, parameter_key, parameter_value, parameter_type, display_name, description, category, min_value, max_value, display_order) VALUES
-- Growth Criteria
('GARP (Growth at Reasonable Price) Strategy', 'min_revenue_growth', '0.20', 'float', 'Minimum Revenue Growth', 'Minimum annual revenue growth rate (20%)', 'Growth Criteria', 0, 5, 1),
('GARP (Growth at Reasonable Price) Strategy', 'min_earnings_growth', '0.20', 'float', 'Minimum Earnings Growth', 'Minimum annual earnings growth rate (20%)', 'Growth Criteria', 0, 5, 2),
('GARP (Growth at Reasonable Price) Strategy', 'max_peg_ratio', '1.0', 'float', 'Maximum PEG Ratio', 'Maximum P/E to Growth ratio (must be < 1.0 for GARP)', 'Valuation', 0, 5, 3),

-- Quality Filters
('GARP (Growth at Reasonable Price) Strategy', 'min_gross_margin', '0.40', 'float', 'Minimum Gross Margin', 'Minimum gross profit margin (40%)', 'Quality Filters', 0, 1, 4),
('GARP (Growth at Reasonable Price) Strategy', 'min_market_cap', '500000000', 'float', 'Minimum Market Cap ($)', 'Minimum market capitalization ($500M)', 'Quality Filters', 0, 100000000000, 5),

-- Financial Strength
('GARP (Growth at Reasonable Price) Strategy', 'max_debt_to_equity', '1.0', 'float', 'Maximum Debt-to-Equity', 'Maximum debt-to-equity ratio (1.0)', 'Financial Strength', 0, 10, 6),
('GARP (Growth at Reasonable Price) Strategy', 'min_current_ratio', '1.5', 'float', 'Minimum Current Ratio', 'Minimum current ratio for liquidity (1.5:1)', 'Financial Strength', 0, 10, 7),

-- Institutional Interest
('GARP (Growth at Reasonable Price) Strategy', 'min_institutional_ownership', '0.10', 'float', 'Minimum Institutional Ownership', 'Minimum institutional ownership (10%)', 'Institutional Interest', 0, 1, 8),
('GARP (Growth at Reasonable Price) Strategy', 'max_institutional_ownership', '0.70', 'float', 'Maximum Institutional Ownership', 'Maximum institutional ownership to avoid overcrowding (70%)', 'Institutional Interest', 0, 1, 9),

-- Momentum
('GARP (Growth at Reasonable Price) Strategy', 'min_price_momentum_3m', '0.05', 'float', 'Minimum 3-Month Momentum', 'Minimum 3-month price momentum (5%)', 'Momentum', -1, 5, 10),
('GARP (Growth at Reasonable Price) Strategy', 'lookback_periods', '4', 'int', 'Lookback Periods (Quarters)', 'Number of quarters to analyze for trends', 'Analysis', 1, 20, 11),

-- Risk Management
('GARP (Growth at Reasonable Price) Strategy', 'stop_loss_percent', '0.20', 'float', 'Stop Loss (%)', 'Stop loss percentage below purchase price (20%)', 'Risk Management', 0, 1, 12),
('GARP (Growth at Reasonable Price) Strategy', 'take_profit_percent', '1.00', 'float', 'Take Profit (%)', 'Take profit target percentage gain (100%)', 'Risk Management', 0, 10, 13),
('GARP (Growth at Reasonable Price) Strategy', 'max_position_size', '0.10', 'float', 'Maximum Position Size', 'Maximum position size as fraction of portfolio (10%)', 'Risk Management', 0, 1, 14);

-- Insert default parameters for Small-Cap Catalyst Strategy
INSERT INTO strategy_parameters (strategy_name, parameter_key, parameter_value, parameter_type, display_name, description, category, min_value, max_value, display_order) VALUES
-- Market Cap & Liquidity
('SmallCapCatalyst', 'min_market_cap', '50000000', 'float', 'Minimum Market Cap ($)', 'Minimum market capitalization for true small-cap ($50M)', 'Market Cap & Liquidity', 0, 10000000000, 1),
('SmallCapCatalyst', 'max_market_cap', '2000000000', 'float', 'Maximum Market Cap ($)', 'Maximum market capitalization ($2B)', 'Market Cap & Liquidity', 0, 10000000000, 2),
('SmallCapCatalyst', 'min_avg_volume', '100000', 'int', 'Minimum Avg Volume', 'Minimum average daily volume for liquidity (100K shares)', 'Market Cap & Liquidity', 0, 100000000, 3),

-- Risk/Reward
('SmallCapCatalyst', 'min_risk_reward_ratio', '3.0', 'float', 'Minimum Risk/Reward Ratio', 'Minimum risk-to-reward ratio required (3:1)', 'Risk/Reward', 0, 10, 4),
('SmallCapCatalyst', 'stop_loss_percent', '0.15', 'float', 'Stop Loss (%)', 'Stop loss percentage below entry (15%)', 'Risk/Reward', 0, 1, 5),

-- Catalyst Criteria
('SmallCapCatalyst', 'min_catalyst_confidence', '0.60', 'float', 'Minimum Catalyst Confidence', 'Minimum confidence score for catalyst (60%)', 'Catalyst Criteria', 0, 1, 6),
('SmallCapCatalyst', 'max_days_to_catalyst', '90', 'int', 'Maximum Days to Catalyst', 'Maximum days until catalyst event (90 days)', 'Catalyst Criteria', 0, 365, 7),
('SmallCapCatalyst', 'min_days_to_catalyst', '7', 'int', 'Minimum Days to Catalyst', 'Minimum days to avoid front-running (7 days)', 'Catalyst Criteria', 0, 365, 8),
('SmallCapCatalyst', 'catalyst_window_days', '30', 'int', 'Primary Catalyst Window', 'Primary catalyst window in days', 'Catalyst Criteria', 0, 180, 9),

-- Short Interest
('SmallCapCatalyst', 'max_short_interest', '0.30', 'float', 'Maximum Short Interest', 'Maximum short interest for stability (30%)', 'Short Interest', 0, 1, 10),
('SmallCapCatalyst', 'min_short_interest_squeeze', '0.15', 'float', 'Minimum Short Interest for Squeeze', 'Minimum short interest for squeeze plays (15%)', 'Short Interest', 0, 1, 11),

-- Ownership
('SmallCapCatalyst', 'min_insider_ownership', '0.10', 'float', 'Minimum Insider Ownership', 'Minimum insider ownership percentage (10%)', 'Ownership', 0, 1, 12),
('SmallCapCatalyst', 'max_institutional_ownership', '0.50', 'float', 'Maximum Institutional Ownership', 'Maximum institutional ownership (50% - want undiscovered)', 'Ownership', 0, 1, 13),

-- Analyst Coverage
('SmallCapCatalyst', 'min_analyst_coverage_gap', '0', 'int', 'Minimum Analyst Count', 'Minimum analyst count (0 = no coverage/undiscovered)', 'Analyst Coverage', 0, 50, 14),
('SmallCapCatalyst', 'max_analyst_coverage_gap', '3', 'int', 'Maximum Analyst Count', 'Maximum analyst count to remain under-followed', 'Analyst Coverage', 0, 50, 15),

-- Technical & Position Sizing
('SmallCapCatalyst', 'min_technical_score', '0.60', 'float', 'Minimum Technical Score', 'Minimum technical strength score (60%)', 'Technical & Position Sizing', 0, 1, 16),
('SmallCapCatalyst', 'min_price_consolidation_days', '20', 'int', 'Minimum Consolidation Days', 'Minimum days in consolidation for breakout setup', 'Technical & Position Sizing', 0, 365, 17),
('SmallCapCatalyst', 'earnings_surprise_threshold', '0.10', 'float', 'Earnings Surprise Threshold', 'Earnings surprise threshold percentage (10%)', 'Technical & Position Sizing', 0, 1, 18),
('SmallCapCatalyst', 'max_position_size', '0.05', 'float', 'Maximum Position Size', 'Maximum position size as fraction of portfolio (5% - higher risk)', 'Technical & Position Sizing', 0, 1, 19);

-- Insert default parameters for other strategies (Turtle, MA Crossover, etc.)
-- These can be added as needed for full configurability
