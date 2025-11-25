-- Technical Analysis Tables for TA-Lib Integration
-- Created: September 4, 2025

-- --------------------------------------------------------

--
-- Table structure for table `technical_indicators`
--
-- Stores calculated technical indicators (RSI, MACD, etc.)
--

DROP TABLE IF EXISTS `technical_indicators`;
CREATE TABLE IF NOT EXISTS `technical_indicators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL,
  `symbol` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `indicator_type` varchar(50) NOT NULL COMMENT 'RSI, MACD, SMA, EMA, etc.',
  `indicator_value` decimal(15,6) DEFAULT NULL,
  `period` int(11) DEFAULT NULL COMMENT 'Period used for calculation (e.g., 14 for RSI)',
  `signal_line` decimal(15,6) DEFAULT NULL COMMENT 'For MACD signal line',
  `histogram` decimal(15,6) DEFAULT NULL COMMENT 'For MACD histogram',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_indicator` (`idstockinfo`, `symbol`, `date`, `indicator_type`, `period`),
  KEY `idx_symbol_date` (`symbol`, `date`),
  KEY `idx_indicator_type` (`indicator_type`),
  KEY `idx_stockinfo` (`idstockinfo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `candlestick_patterns`
--
-- Stores identified candlestick patterns with TA-Lib
--

DROP TABLE IF EXISTS `candlestick_patterns`;
CREATE TABLE IF NOT EXISTS `candlestick_patterns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL,
  `symbol` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `pattern_name` varchar(50) NOT NULL,
  `pattern_value` int(11) NOT NULL COMMENT 'TA-Lib pattern strength (-100 to 100)',
  `action_recommendation` varchar(20) DEFAULT NULL COMMENT 'BUY, SELL, HOLD',
  `confidence` decimal(5,2) DEFAULT NULL COMMENT 'Confidence level 0-100',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pattern` (`idstockinfo`, `symbol`, `date`, `pattern_name`),
  KEY `idx_symbol_date` (`symbol`, `date`),
  KEY `idx_pattern_name` (`pattern_name`),
  KEY `idx_stockinfo` (`idstockinfo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `support_resistance`
--
-- Stores calculated support and resistance levels
--

DROP TABLE IF EXISTS `support_resistance`;
CREATE TABLE IF NOT EXISTS `support_resistance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL,
  `symbol` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `level_type` enum('SUPPORT','RESISTANCE') NOT NULL,
  `price_level` decimal(15,6) NOT NULL,
  `strength` decimal(5,2) DEFAULT NULL COMMENT 'Strength of the level 0-100',
  `touches` int(11) DEFAULT 1 COMMENT 'Number of times price touched this level',
  `last_touched` date DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1 COMMENT 'Whether level is still active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_symbol_date` (`symbol`, `date`),
  KEY `idx_level_type` (`level_type`),
  KEY `idx_stockinfo` (`idstockinfo`),
  KEY `idx_active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `ta_analysis_jobs`
--
-- Tracks technical analysis calculation jobs
--

DROP TABLE IF EXISTS `ta_analysis_jobs`;
CREATE TABLE IF NOT EXISTS `ta_analysis_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) DEFAULT NULL,
  `symbol` varchar(12) DEFAULT NULL,
  `analysis_type` varchar(50) NOT NULL COMMENT 'indicators, patterns, support_resistance, all',
  `status` enum('PENDING','RUNNING','COMPLETED','FAILED') DEFAULT 'PENDING',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `progress` int(11) DEFAULT 0 COMMENT 'Progress percentage 0-100',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_symbol` (`symbol`),
  KEY `idx_stockinfo` (`idstockinfo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `volume_analysis`
--
-- Stores volume-based technical analysis
--

DROP TABLE IF EXISTS `volume_analysis`;
CREATE TABLE IF NOT EXISTS `volume_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL,
  `symbol` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `obv` bigint(20) DEFAULT NULL COMMENT 'On Balance Volume',
  `volume_sma_20` bigint(20) DEFAULT NULL COMMENT '20-day volume moving average',
  `volume_ratio` decimal(10,4) DEFAULT NULL COMMENT 'Current volume / Average volume',
  `accumulation_distribution` decimal(20,6) DEFAULT NULL COMMENT 'A/D Line',
  `money_flow_index` decimal(8,4) DEFAULT NULL COMMENT 'MFI indicator',
  `chaikin_money_flow` decimal(10,6) DEFAULT NULL COMMENT 'CMF indicator',
  `volume_trend` enum('INCREASING','DECREASING','STABLE') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_volume_analysis` (`idstockinfo`, `symbol`, `date`),
  KEY `idx_symbol_date` (`symbol`, `date`),
  KEY `idx_stockinfo` (`idstockinfo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `trend_analysis`
--
-- Stores trend analysis data
--

DROP TABLE IF EXISTS `trend_analysis`;
CREATE TABLE IF NOT EXISTS `trend_analysis` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idstockinfo` int(11) NOT NULL,
  `symbol` varchar(12) NOT NULL,
  `date` date NOT NULL,
  `short_trend` enum('BULLISH','BEARISH','SIDEWAYS') DEFAULT NULL COMMENT '5-20 day trend',
  `medium_trend` enum('BULLISH','BEARISH','SIDEWAYS') DEFAULT NULL COMMENT '20-50 day trend',
  `long_trend` enum('BULLISH','BEARISH','SIDEWAYS') DEFAULT NULL COMMENT '50-200 day trend',
  `trend_strength` decimal(5,2) DEFAULT NULL COMMENT 'Trend strength 0-100',
  `breakout_probability` decimal(5,2) DEFAULT NULL COMMENT 'Probability of breakout 0-100',
  `next_resistance` decimal(15,6) DEFAULT NULL,
  `next_support` decimal(15,6) DEFAULT NULL,
  `volatility_percentile` decimal(5,2) DEFAULT NULL COMMENT 'Current volatility vs historical',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_trend_analysis` (`idstockinfo`, `symbol`, `date`),
  KEY `idx_symbol_date` (`symbol`, `date`),
  KEY `idx_stockinfo` (`idstockinfo`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Insert initial technical analysis job types
--

INSERT IGNORE INTO `ta_analysis_jobs` (`analysis_type`, `status`, `progress`) VALUES
('indicators', 'PENDING', 0),
('patterns', 'PENDING', 0),
('support_resistance', 'PENDING', 0),
('volume_analysis', 'PENDING', 0),
('trend_analysis', 'PENDING', 0);

-- --------------------------------------------------------

--
-- Common technical indicators that will be calculated
-- This is reference data for the UI
--

CREATE TABLE IF NOT EXISTS `ta_indicator_definitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `default_period` int(11) DEFAULT NULL,
  `min_period` int(11) DEFAULT NULL,
  `max_period` int(11) DEFAULT NULL,
  `category` varchar(50) NOT NULL COMMENT 'trend, momentum, volatility, volume',
  `ta_lib_function` varchar(50) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_name` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT IGNORE INTO `ta_indicator_definitions` 
(`name`, `display_name`, `description`, `default_period`, `min_period`, `max_period`, `category`, `ta_lib_function`) VALUES
('RSI', 'Relative Strength Index', 'Momentum oscillator measuring speed and magnitude of price changes', 14, 2, 100, 'momentum', 'RSI'),
('MACD', 'Moving Average Convergence Divergence', 'Trend-following momentum indicator', 12, 5, 50, 'momentum', 'MACD'),
('SMA', 'Simple Moving Average', 'Average price over specified number of periods', 20, 2, 200, 'trend', 'SMA'),
('EMA', 'Exponential Moving Average', 'Exponentially weighted moving average giving more weight to recent prices', 20, 2, 200, 'trend', 'EMA'),
('BB_UPPER', 'Bollinger Bands Upper', 'Upper Bollinger Band (SMA + 2*StdDev)', 20, 5, 50, 'volatility', 'BBANDS'),
('BB_LOWER', 'Bollinger Bands Lower', 'Lower Bollinger Band (SMA - 2*StdDev)', 20, 5, 50, 'volatility', 'BBANDS'),
('STOCH_K', 'Stochastic %K', 'Stochastic oscillator %K line', 14, 5, 50, 'momentum', 'STOCH'),
('STOCH_D', 'Stochastic %D', 'Stochastic oscillator %D line (smoothed %K)', 3, 1, 10, 'momentum', 'STOCH'),
('ATR', 'Average True Range', 'Volatility indicator measuring average range of price movement', 14, 1, 50, 'volatility', 'ATR'),
('ADX', 'Average Directional Index', 'Trend strength indicator', 14, 5, 50, 'trend', 'ADX'),
('CCI', 'Commodity Channel Index', 'Momentum-based oscillator', 20, 5, 50, 'momentum', 'CCI'),
('MFI', 'Money Flow Index', 'Volume-weighted RSI', 14, 2, 50, 'volume', 'MFI'),
('OBV', 'On Balance Volume', 'Volume-based indicator showing buying/selling pressure', NULL, NULL, NULL, 'volume', 'OBV'),
('WILLR', 'Williams %R', 'Momentum indicator similar to stochastic', 14, 5, 50, 'momentum', 'WILLR'),
('ROC', 'Rate of Change', 'Momentum indicator measuring percentage change', 10, 1, 50, 'momentum', 'ROC');

COMMIT;
