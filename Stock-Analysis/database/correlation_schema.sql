-- =====================================================
-- CORRELATION AND ACCURACY TRACKING SCHEMA
-- =====================================================
-- This schema enables tracking correlations between market factors and stocks,
-- plus technical analysis accuracy scores for weighted indicator application

USE stock_market_2;

-- =====================================================
-- STOCK-FACTOR CORRELATION TRACKING
-- =====================================================

-- Table to store correlation scores between stocks and market factors
CREATE TABLE IF NOT EXISTS stock_factor_correlations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    factor_id INT NOT NULL,
    factor_type ENUM('index', 'forex', 'economic', 'sector') NOT NULL,
    correlation_coefficient DECIMAL(5,4) NOT NULL, -- -1.0000 to 1.0000
    correlation_strength ENUM('Very Strong', 'Strong', 'Moderate', 'Weak', 'Very Weak') NOT NULL,
    p_value DECIMAL(10,8), -- Statistical significance
    sample_size INT NOT NULL,
    time_period_days INT NOT NULL DEFAULT 30,
    calculation_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (factor_id) REFERENCES market_factors(id) ON DELETE CASCADE,
    
    -- Indexes for performance
    INDEX idx_stock_symbol (stock_symbol),
    INDEX idx_factor_type (factor_type),
    INDEX idx_correlation_strength (correlation_strength),
    INDEX idx_calculation_date (calculation_date),
    INDEX idx_composite_lookup (stock_symbol, factor_type, is_active),
    
    -- Unique constraint to prevent duplicate entries
    UNIQUE KEY unique_correlation (stock_symbol, factor_id, time_period_days, calculation_date)
);

-- Table to store historical correlation data for trend analysis
CREATE TABLE IF NOT EXISTS correlation_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    factor_id INT NOT NULL,
    correlation_coefficient DECIMAL(5,4) NOT NULL,
    calculation_date DATE NOT NULL,
    time_period_days INT NOT NULL,
    volume_traded BIGINT DEFAULT 0,
    market_volatility DECIMAL(8,4) DEFAULT 0.0000,
    
    -- Foreign key constraints
    FOREIGN KEY (factor_id) REFERENCES market_factors(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_stock_date (stock_symbol, calculation_date),
    INDEX idx_factor_date (factor_id, calculation_date),
    
    -- Unique constraint
    UNIQUE KEY unique_history (stock_symbol, factor_id, calculation_date, time_period_days)
);

-- =====================================================
-- TECHNICAL ANALYSIS ACCURACY TRACKING
-- =====================================================

-- Table to store technical indicators and their configurations
CREATE TABLE IF NOT EXISTS technical_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicator_name VARCHAR(50) NOT NULL, -- RSI, MACD, SMA, EMA, Bollinger, etc.
    indicator_type ENUM('momentum', 'trend', 'volume', 'volatility', 'support_resistance') NOT NULL,
    parameters JSON, -- Store indicator-specific parameters (periods, thresholds, etc.)
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_indicator_type (indicator_type),
    UNIQUE KEY unique_indicator (indicator_name, parameters(255))
);

-- Table to store technical indicator predictions and their outcomes
CREATE TABLE IF NOT EXISTS indicator_predictions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    indicator_id INT NOT NULL,
    prediction_date DATETIME NOT NULL,
    prediction_type ENUM('buy', 'sell', 'hold', 'bullish', 'bearish', 'neutral') NOT NULL,
    confidence_level DECIMAL(5,2) NOT NULL, -- 0.00 to 100.00
    indicator_value DECIMAL(15,6), -- The actual indicator value at prediction time
    target_price DECIMAL(10,4),
    target_timeframe_days INT DEFAULT 5, -- How many days to evaluate the prediction
    
    -- Outcome tracking
    actual_outcome ENUM('correct', 'incorrect', 'partially_correct', 'pending') DEFAULT 'pending',
    actual_price_change DECIMAL(10,4) DEFAULT 0.0000,
    actual_price_change_percent DECIMAL(8,4) DEFAULT 0.0000,
    outcome_date DATETIME,
    accuracy_score DECIMAL(5,2) DEFAULT 0.00, -- 0.00 to 100.00
    
    -- Metadata
    market_conditions JSON, -- Store market context at time of prediction
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (indicator_id) REFERENCES technical_indicators(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_stock_symbol (stock_symbol),
    INDEX idx_prediction_date (prediction_date),
    INDEX idx_outcome (actual_outcome),
    INDEX idx_accuracy_score (accuracy_score),
    INDEX idx_composite_analysis (stock_symbol, indicator_id, prediction_date)
);

-- Table to store aggregated accuracy statistics for each indicator
CREATE TABLE IF NOT EXISTS indicator_accuracy_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    indicator_id INT NOT NULL,
    time_period_days INT NOT NULL DEFAULT 30,
    total_predictions INT NOT NULL DEFAULT 0,
    correct_predictions INT NOT NULL DEFAULT 0,
    incorrect_predictions INT NOT NULL DEFAULT 0,
    pending_predictions INT NOT NULL DEFAULT 0,
    
    -- Accuracy metrics
    overall_accuracy_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    buy_signal_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    sell_signal_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    hold_signal_accuracy DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    
    -- Performance metrics
    average_accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    weighted_accuracy_score DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Weighted by confidence
    volatility_adjustment DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    
    -- Trend analysis
    accuracy_trend ENUM('improving', 'declining', 'stable', 'insufficient_data') DEFAULT 'insufficient_data',
    confidence_trend ENUM('increasing', 'decreasing', 'stable', 'insufficient_data') DEFAULT 'insufficient_data',
    
    -- Time tracking
    calculation_date DATE NOT NULL,
    last_prediction_date DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (indicator_id) REFERENCES technical_indicators(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_stock_indicator (stock_symbol, indicator_id),
    INDEX idx_accuracy (overall_accuracy_percent DESC),
    INDEX idx_calculation_date (calculation_date),
    
    -- Unique constraint
    UNIQUE KEY unique_stats (stock_symbol, indicator_id, time_period_days, calculation_date)
);

-- =====================================================
-- CORRELATION MODIFIER CONFIGURATION
-- =====================================================

-- Table to store correlation-based modifier rules and configurations
CREATE TABLE IF NOT EXISTS correlation_modifiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    modifier_name VARCHAR(100) NOT NULL,
    modifier_type ENUM('factor_correlation', 'indicator_accuracy', 'combined') NOT NULL,
    
    -- Configuration
    base_weight DECIMAL(5,2) NOT NULL DEFAULT 1.00, -- Base multiplier
    min_correlation_threshold DECIMAL(5,4) DEFAULT 0.1000, -- Minimum correlation to apply
    max_modifier DECIMAL(5,2) DEFAULT 2.00, -- Maximum modifier value
    min_modifier DECIMAL(5,2) DEFAULT 0.10, -- Minimum modifier value
    
    -- Factor-specific settings
    factor_types JSON, -- Which factor types to include
    indicator_types JSON, -- Which indicator types to include
    time_sensitivity_days INT DEFAULT 30,
    
    -- Accuracy thresholds
    min_accuracy_threshold DECIMAL(5,2) DEFAULT 60.00,
    accuracy_weight DECIMAL(5,2) DEFAULT 1.00,
    correlation_weight DECIMAL(5,2) DEFAULT 1.00,
    
    -- Status and metadata
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_stock_symbol (stock_symbol),
    INDEX idx_modifier_type (modifier_type),
    INDEX idx_active (is_active),
    
    -- Unique constraint
    UNIQUE KEY unique_modifier (stock_symbol, modifier_name)
);

-- =====================================================
-- TRADING ALGORITHM INTEGRATION
-- =====================================================

-- Table to store applied modifiers for backtesting and analysis
CREATE TABLE IF NOT EXISTS applied_modifiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stock_symbol VARCHAR(10) NOT NULL,
    trading_date DATETIME NOT NULL,
    
    -- Original signal data
    original_signal_strength DECIMAL(5,2) NOT NULL,
    original_confidence DECIMAL(5,2) NOT NULL,
    signal_type ENUM('buy', 'sell', 'hold') NOT NULL,
    
    -- Applied modifiers
    factor_correlation_modifier DECIMAL(5,2) DEFAULT 1.00,
    indicator_accuracy_modifier DECIMAL(5,2) DEFAULT 1.00,
    combined_modifier DECIMAL(5,2) NOT NULL,
    
    -- Final modified values
    modified_signal_strength DECIMAL(5,2) NOT NULL,
    modified_confidence DECIMAL(5,2) NOT NULL,
    
    -- Contributing factors
    contributing_factors JSON, -- Details about which factors influenced the modifier
    contributing_indicators JSON, -- Details about which indicators influenced the modifier
    
    -- Performance tracking
    actual_performance DECIMAL(8,4), -- Actual price change after signal
    modifier_effectiveness DECIMAL(5,2), -- How well the modifier performed
    
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_stock_date (stock_symbol, trading_date),
    INDEX idx_signal_type (signal_type),
    INDEX idx_effectiveness (modifier_effectiveness DESC),
    INDEX idx_trading_date (trading_date)
);

-- =====================================================
-- VIEWS FOR ANALYSIS
-- =====================================================

-- View for current correlation matrix
CREATE OR REPLACE VIEW v_current_correlations AS
SELECT 
    sfc.stock_symbol,
    mf.symbol as factor_symbol,
    mf.name as factor_name,
    sfc.factor_type,
    sfc.correlation_coefficient,
    sfc.correlation_strength,
    sfc.p_value,
    sfc.sample_size,
    sfc.time_period_days,
    sfc.calculation_date
FROM stock_factor_correlations sfc
JOIN market_factors mf ON sfc.factor_id = mf.id
WHERE sfc.is_active = TRUE
  AND sfc.calculation_date = (
    SELECT MAX(calculation_date) 
    FROM stock_factor_correlations sfc2 
    WHERE sfc2.stock_symbol = sfc.stock_symbol 
      AND sfc2.factor_id = sfc.factor_id
      AND sfc2.is_active = TRUE
  );

-- View for indicator performance summary
CREATE OR REPLACE VIEW v_indicator_performance AS
SELECT 
    ias.stock_symbol,
    ti.indicator_name,
    ti.indicator_type,
    ias.overall_accuracy_percent,
    ias.average_accuracy_score,
    ias.weighted_accuracy_score,
    ias.total_predictions,
    ias.accuracy_trend,
    ias.confidence_trend,
    ias.calculation_date,
    ias.last_prediction_date
FROM indicator_accuracy_stats ias
JOIN technical_indicators ti ON ias.indicator_id = ti.id
WHERE ias.calculation_date = (
    SELECT MAX(calculation_date)
    FROM indicator_accuracy_stats ias2
    WHERE ias2.stock_symbol = ias.stock_symbol
      AND ias2.indicator_id = ias.indicator_id
);

-- View for correlation-weighted recommendations
CREATE OR REPLACE VIEW v_weighted_recommendations AS
SELECT 
    cc.stock_symbol,
    AVG(cc.correlation_coefficient * ias.weighted_accuracy_score / 100) as composite_score,
    COUNT(DISTINCT cc.factor_id) as factor_count,
    COUNT(DISTINCT ias.indicator_id) as indicator_count,
    AVG(cc.correlation_coefficient) as avg_correlation,
    AVG(ias.weighted_accuracy_score) as avg_accuracy,
    MAX(cc.calculation_date) as last_updated
FROM v_current_correlations cc
LEFT JOIN v_indicator_performance ias ON cc.stock_symbol = ias.stock_symbol
GROUP BY cc.stock_symbol
HAVING composite_score IS NOT NULL;

-- =====================================================
-- INITIAL DATA SETUP
-- =====================================================

-- Insert common technical indicators
INSERT IGNORE INTO technical_indicators (indicator_name, indicator_type, parameters, description) VALUES
('RSI_14', 'momentum', '{"period": 14, "overbought": 70, "oversold": 30}', 'Relative Strength Index with 14-day period'),
('MACD_12_26_9', 'momentum', '{"fast_period": 12, "slow_period": 26, "signal_period": 9}', 'MACD with standard parameters'),
('SMA_20', 'trend', '{"period": 20}', 'Simple Moving Average 20-day'),
('SMA_50', 'trend', '{"period": 50}', 'Simple Moving Average 50-day'),
('EMA_12', 'trend', '{"period": 12}', 'Exponential Moving Average 12-day'),
('EMA_26', 'trend', '{"period": 26}', 'Exponential Moving Average 26-day'),
('Bollinger_20_2', 'volatility', '{"period": 20, "std_dev": 2}', 'Bollinger Bands with 20-day period and 2 standard deviations'),
('Stochastic_14_3', 'momentum', '{"k_period": 14, "d_period": 3, "smooth": 3}', 'Stochastic Oscillator with standard parameters'),
('Volume_SMA_20', 'volume', '{"period": 20}', 'Volume Simple Moving Average 20-day'),
('ATR_14', 'volatility', '{"period": 14}', 'Average True Range with 14-day period');

-- =====================================================
-- STORED PROCEDURES FOR COMMON OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to calculate correlation coefficient between stock and factor
CREATE PROCEDURE IF NOT EXISTS CalculateStockFactorCorrelation(
    IN p_stock_symbol VARCHAR(10),
    IN p_factor_id INT,
    IN p_days INT
)
BEGIN
    DECLARE correlation_value DECIMAL(5,4);
    DECLARE p_val DECIMAL(10,8);
    DECLARE sample_count INT;
    
    -- This is a placeholder - actual correlation calculation would need
    -- historical price data and factor data to compute Pearson correlation
    -- For now, we'll insert a dummy value
    SET correlation_value = 0.0000;
    SET p_val = 0.50000000;
    SET sample_count = p_days;
    
    INSERT INTO stock_factor_correlations (
        stock_symbol, factor_id, factor_type, correlation_coefficient,
        correlation_strength, p_value, sample_size, time_period_days
    )
    SELECT 
        p_stock_symbol,
        p_factor_id,
        mf.type,
        correlation_value,
        CASE 
            WHEN ABS(correlation_value) >= 0.8 THEN 'Very Strong'
            WHEN ABS(correlation_value) >= 0.6 THEN 'Strong'
            WHEN ABS(correlation_value) >= 0.4 THEN 'Moderate'
            WHEN ABS(correlation_value) >= 0.2 THEN 'Weak'
            ELSE 'Very Weak'
        END,
        p_val,
        sample_count,
        p_days
    FROM market_factors mf
    WHERE mf.id = p_factor_id;
END //

-- Procedure to update indicator accuracy statistics
CREATE PROCEDURE IF NOT EXISTS UpdateIndicatorAccuracyStats(
    IN p_stock_symbol VARCHAR(10),
    IN p_indicator_id INT,
    IN p_time_period INT
)
BEGIN
    DECLARE total_preds INT DEFAULT 0;
    DECLARE correct_preds INT DEFAULT 0;
    DECLARE incorrect_preds INT DEFAULT 0;
    DECLARE pending_preds INT DEFAULT 0;
    DECLARE overall_acc DECIMAL(5,2) DEFAULT 0.00;
    DECLARE avg_score DECIMAL(5,2) DEFAULT 0.00;
    DECLARE weighted_score DECIMAL(5,2) DEFAULT 0.00;
    
    -- Count predictions by outcome
    SELECT 
        COUNT(*),
        SUM(CASE WHEN actual_outcome = 'correct' THEN 1 ELSE 0 END),
        SUM(CASE WHEN actual_outcome = 'incorrect' THEN 1 ELSE 0 END),
        SUM(CASE WHEN actual_outcome = 'pending' THEN 1 ELSE 0 END)
    INTO total_preds, correct_preds, incorrect_preds, pending_preds
    FROM indicator_predictions
    WHERE stock_symbol = p_stock_symbol
      AND indicator_id = p_indicator_id
      AND prediction_date >= DATE_SUB(CURRENT_DATE, INTERVAL p_time_period DAY);
    
    -- Calculate accuracy metrics
    IF total_preds > 0 THEN
        SET overall_acc = (correct_preds / total_preds) * 100;
        
        SELECT AVG(accuracy_score), AVG(accuracy_score * confidence_level / 100)
        INTO avg_score, weighted_score
        FROM indicator_predictions
        WHERE stock_symbol = p_stock_symbol
          AND indicator_id = p_indicator_id
          AND prediction_date >= DATE_SUB(CURRENT_DATE, INTERVAL p_time_period DAY)
          AND actual_outcome != 'pending';
    END IF;
    
    -- Insert or update statistics
    INSERT INTO indicator_accuracy_stats (
        stock_symbol, indicator_id, time_period_days, total_predictions,
        correct_predictions, incorrect_predictions, pending_predictions,
        overall_accuracy_percent, average_accuracy_score, weighted_accuracy_score,
        calculation_date
    ) VALUES (
        p_stock_symbol, p_indicator_id, p_time_period, total_preds,
        correct_preds, incorrect_preds, pending_preds,
        overall_acc, COALESCE(avg_score, 0), COALESCE(weighted_score, 0),
        CURRENT_DATE
    )
    ON DUPLICATE KEY UPDATE
        total_predictions = total_preds,
        correct_predictions = correct_preds,
        incorrect_predictions = incorrect_preds,
        pending_predictions = pending_preds,
        overall_accuracy_percent = overall_acc,
        average_accuracy_score = COALESCE(avg_score, 0),
        weighted_accuracy_score = COALESCE(weighted_score, 0),
        updated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- =====================================================
-- INDEXES FOR PERFORMANCE OPTIMIZATION
-- =====================================================

-- Additional composite indexes for complex queries
CREATE INDEX IF NOT EXISTS idx_correlation_strength_stock ON stock_factor_correlations(correlation_strength, stock_symbol);
CREATE INDEX IF NOT EXISTS idx_accuracy_indicator_stock ON indicator_accuracy_stats(overall_accuracy_percent DESC, stock_symbol, indicator_id);
CREATE INDEX IF NOT EXISTS idx_prediction_outcome_date ON indicator_predictions(actual_outcome, prediction_date);
CREATE INDEX IF NOT EXISTS idx_modifier_effectiveness ON applied_modifiers(modifier_effectiveness DESC, trading_date);

-- =====================================================
-- TRIGGERS FOR DATA INTEGRITY
-- =====================================================

DELIMITER //

-- Trigger to automatically update correlation history
CREATE TRIGGER IF NOT EXISTS tr_update_correlation_history
AFTER INSERT ON stock_factor_correlations
FOR EACH ROW
BEGIN
    INSERT INTO correlation_history (
        stock_symbol, factor_id, correlation_coefficient,
        calculation_date, time_period_days
    ) VALUES (
        NEW.stock_symbol, NEW.factor_id, NEW.correlation_coefficient,
        DATE(NEW.calculation_date), NEW.time_period_days
    )
    ON DUPLICATE KEY UPDATE
        correlation_coefficient = NEW.correlation_coefficient;
END //

DELIMITER ;

-- =====================================================
-- SAMPLE DATA FOR TESTING
-- =====================================================

-- This would be populated by the correlation analysis system
-- Sample correlation data will be inserted via the service layer

COMMIT;
