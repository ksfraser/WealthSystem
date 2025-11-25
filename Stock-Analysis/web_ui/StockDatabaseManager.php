<?php
/**
 * Stock Database Manager
 * 
 * Manages individual stock-specific database tables for optimal performance
 * Each stock gets its own set of tables for prices, news, analysis, and technical indicators
 */

class StockDatabaseManager {
    private $pdo;
    private $config;
    
    public function __construct(PDO $pdo, array $config = []) {
        $this->pdo = $pdo;
        $this->config = array_merge([
            'table_prefix' => '',
            'price_retention_days' => 365 * 5, // 5 years
            'news_retention_days' => 365,      // 1 year
            'analysis_retention_days' => 180,   // 6 months
        ], $config);
    }
    
    /**
     * Create all tables for a specific stock symbol
     */
    public function createStockTables(string $symbol): bool {
        try {
            $this->pdo->beginTransaction();
            
            $sanitizedSymbol = $this->sanitizeSymbol($symbol);
            
            // Create price data table
            $this->createPriceTable($sanitizedSymbol);
            
            // Create fundamentals table
            $this->createFundamentalsTable($sanitizedSymbol);
            
            // Create technical indicators table
            $this->createTechnicalTable($sanitizedSymbol);
            
            // Create news table
            $this->createNewsTable($sanitizedSymbol);
            
            // Create analysis results table
            $this->createAnalysisTable($sanitizedSymbol);
            
            // Create alerts table
            $this->createAlertsTable($sanitizedSymbol);
            
            // Register the stock in the master stocks table
            $this->registerStock($symbol, $sanitizedSymbol);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Failed to create tables for stock {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create price data table for a stock
     */
    private function createPriceTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_prices';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL UNIQUE,
                open_price DECIMAL(12,6) NOT NULL,
                high_price DECIMAL(12,6) NOT NULL,
                low_price DECIMAL(12,6) NOT NULL,
                close_price DECIMAL(12,6) NOT NULL,
                adj_close_price DECIMAL(12,6) NOT NULL,
                volume BIGINT DEFAULT 0,
                split_coefficient DECIMAL(8,4) DEFAULT 1.0000,
                dividend_amount DECIMAL(8,4) DEFAULT 0.0000,
                data_source ENUM('yahoo', 'alpha_vantage', 'finnhub', 'manual') DEFAULT 'yahoo',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_date (date),
                INDEX idx_close_price (close_price),
                INDEX idx_volume (volume),
                INDEX idx_created_at (created_at)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Historical price data for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create fundamentals table for a stock
     */
    private function createFundamentalsTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_fundamentals';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                report_date DATE NOT NULL,
                
                -- Valuation Metrics
                market_cap BIGINT,
                enterprise_value BIGINT,
                pe_ratio DECIMAL(8,2),
                forward_pe DECIMAL(8,2),
                peg_ratio DECIMAL(8,2),
                price_to_book DECIMAL(8,2),
                price_to_sales DECIMAL(8,2),
                ev_to_revenue DECIMAL(8,2),
                ev_to_ebitda DECIMAL(8,2),
                
                -- Profitability Metrics
                gross_margin DECIMAL(8,4),
                operating_margin DECIMAL(8,4),
                net_margin DECIMAL(8,4),
                return_on_equity DECIMAL(8,4),
                return_on_assets DECIMAL(8,4),
                return_on_invested_capital DECIMAL(8,4),
                
                -- Financial Health
                debt_to_equity DECIMAL(8,2),
                current_ratio DECIMAL(8,2),
                quick_ratio DECIMAL(8,2),
                cash_ratio DECIMAL(8,2),
                
                -- Growth Metrics
                revenue_growth_yoy DECIMAL(8,4),
                earnings_growth_yoy DECIMAL(8,4),
                free_cash_flow_growth DECIMAL(8,4),
                
                -- Per Share Data
                earnings_per_share DECIMAL(8,2),
                book_value_per_share DECIMAL(8,2),
                cash_per_share DECIMAL(8,2),
                revenue_per_share DECIMAL(8,2),
                
                -- Dividend Information
                dividend_yield DECIMAL(8,4),
                dividend_per_share DECIMAL(8,4),
                payout_ratio DECIMAL(8,4),
                
                -- Risk Metrics
                beta DECIMAL(8,4),
                volatility_30d DECIMAL(8,4),
                
                -- Analyst Data
                analyst_rating ENUM('STRONG_BUY', 'BUY', 'HOLD', 'SELL', 'STRONG_SELL'),
                target_price DECIMAL(12,6),
                analyst_count INT,
                
                data_source VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                UNIQUE KEY unique_report_date (report_date),
                INDEX idx_pe_ratio (pe_ratio),
                INDEX idx_market_cap (market_cap),
                INDEX idx_updated_at (updated_at)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Fundamental data for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create technical indicators table for a stock
     */
    private function createTechnicalTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_technical';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                date DATE NOT NULL UNIQUE,
                
                -- Moving Averages
                sma_5 DECIMAL(12,6),
                sma_10 DECIMAL(12,6),
                sma_20 DECIMAL(12,6),
                sma_50 DECIMAL(12,6),
                sma_200 DECIMAL(12,6),
                ema_5 DECIMAL(12,6),
                ema_10 DECIMAL(12,6),
                ema_12 DECIMAL(12,6),
                ema_20 DECIMAL(12,6),
                ema_26 DECIMAL(12,6),
                ema_50 DECIMAL(12,6),
                
                -- MACD Indicators
                macd DECIMAL(12,6),
                macd_signal DECIMAL(12,6),
                macd_histogram DECIMAL(12,6),
                
                -- Oscillators
                rsi_14 DECIMAL(8,2),
                rsi_30 DECIMAL(8,2),
                stoch_k DECIMAL(8,2),
                stoch_d DECIMAL(8,2),
                williams_r DECIMAL(8,2),
                cci DECIMAL(8,2),
                
                -- Bollinger Bands
                bb_upper DECIMAL(12,6),
                bb_middle DECIMAL(12,6),
                bb_lower DECIMAL(12,6),
                bb_width DECIMAL(8,4),
                bb_percent DECIMAL(8,4),
                
                -- Volume Indicators
                obv BIGINT,
                ad_line DECIMAL(15,2),
                vwap DECIMAL(12,6),
                volume_sma_20 DECIMAL(15,2),
                
                -- Volatility Indicators
                atr_14 DECIMAL(12,6),
                true_range DECIMAL(12,6),
                
                -- Trend Indicators
                adx_14 DECIMAL(8,2),
                plus_di DECIMAL(8,2),
                minus_di DECIMAL(8,2),
                parabolic_sar DECIMAL(12,6),
                
                -- Support/Resistance
                pivot_point DECIMAL(12,6),
                resistance_1 DECIMAL(12,6),
                resistance_2 DECIMAL(12,6),
                resistance_3 DECIMAL(12,6),
                support_1 DECIMAL(12,6),
                support_2 DECIMAL(12,6),
                support_3 DECIMAL(12,6),
                
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_date (date),
                INDEX idx_rsi_14 (rsi_14),
                INDEX idx_macd (macd),
                INDEX idx_sma_20 (sma_20),
                INDEX idx_updated_at (updated_at)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Technical indicators for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create news table for a stock
     */
    private function createNewsTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_news';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                news_id VARCHAR(255) UNIQUE, -- External news ID to prevent duplicates
                headline VARCHAR(500) NOT NULL,
                summary TEXT,
                content LONGTEXT,
                author VARCHAR(255),
                source VARCHAR(100) NOT NULL,
                source_url VARCHAR(1000),
                published_at TIMESTAMP NOT NULL,
                
                -- Sentiment Analysis
                sentiment_score DECIMAL(4,3), -- -1.000 to 1.000
                sentiment_label ENUM('VERY_NEGATIVE', 'NEGATIVE', 'NEUTRAL', 'POSITIVE', 'VERY_POSITIVE'),
                confidence_score DECIMAL(4,3), -- 0.000 to 1.000
                
                -- LLM Analysis
                llm_summary TEXT,
                llm_impact_assessment TEXT,
                llm_price_impact_score DECIMAL(4,3), -- -1.000 to 1.000
                llm_analyzed_at TIMESTAMP NULL,
                llm_model VARCHAR(50),
                
                -- Classification
                category ENUM('EARNINGS', 'MERGER', 'DIVIDEND', 'ANALYST', 'REGULATORY', 'GENERAL', 'OTHER'),
                importance ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL'),
                
                -- Processing Status
                processed BOOLEAN DEFAULT FALSE,
                processing_errors TEXT,
                
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_published_at (published_at),
                INDEX idx_sentiment_score (sentiment_score),
                INDEX idx_category (category),
                INDEX idx_importance (importance),
                INDEX idx_processed (processed),
                FULLTEXT idx_content (headline, summary, content)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='News articles for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create analysis results table for a stock
     */
    private function createAnalysisTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_analysis';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                analysis_date DATE NOT NULL,
                
                -- Component Scores (0-100)
                fundamental_score DECIMAL(5,2),
                technical_score DECIMAL(5,2),
                momentum_score DECIMAL(5,2),
                sentiment_score DECIMAL(5,2),
                news_score DECIMAL(5,2),
                
                -- Overall Analysis
                overall_score DECIMAL(5,2), -- Weighted average
                confidence_level DECIMAL(4,3), -- 0.000 to 1.000
                
                -- Recommendations
                recommendation ENUM('STRONG_BUY', 'BUY', 'HOLD', 'SELL', 'STRONG_SELL'),
                target_price DECIMAL(12,6),
                stop_loss DECIMAL(12,6),
                take_profit DECIMAL(12,6),
                
                -- Risk Assessment
                risk_level ENUM('LOW', 'MEDIUM', 'HIGH', 'VERY_HIGH'),
                volatility_assessment DECIMAL(8,4),
                risk_factors TEXT,
                
                -- LLM Analysis
                llm_analysis LONGTEXT,
                llm_reasoning TEXT,
                llm_model VARCHAR(50),
                llm_tokens_used INT,
                
                -- Analysis Metadata
                analysis_version VARCHAR(20),
                data_freshness_score DECIMAL(4,3),
                analyst_override BOOLEAN DEFAULT FALSE,
                override_reason TEXT,
                
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_analysis_date (analysis_date),
                INDEX idx_overall_score (overall_score),
                INDEX idx_recommendation (recommendation),
                INDEX idx_risk_level (risk_level),
                INDEX idx_confidence_level (confidence_level)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Analysis results for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Create alerts table for a stock
     */
    private function createAlertsTable(string $sanitizedSymbol): void {
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_alerts';
        
        $sql = "
            CREATE TABLE IF NOT EXISTS `{$tableName}` (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                user_id INT, -- NULL for system-wide alerts
                alert_type ENUM('PRICE', 'VOLUME', 'TECHNICAL', 'NEWS', 'RECOMMENDATION') NOT NULL,
                
                -- Price Alerts
                target_price DECIMAL(12,6),
                price_direction ENUM('ABOVE', 'BELOW'),
                
                -- Volume Alerts
                volume_threshold BIGINT,
                volume_direction ENUM('ABOVE', 'BELOW'),
                
                -- Technical Alerts
                indicator_name VARCHAR(50),
                indicator_condition VARCHAR(100),
                indicator_value DECIMAL(12,6),
                
                -- Alert Configuration
                is_active BOOLEAN DEFAULT TRUE,
                trigger_once BOOLEAN DEFAULT FALSE,
                
                -- Notification Settings
                email_notification BOOLEAN DEFAULT TRUE,
                sms_notification BOOLEAN DEFAULT FALSE,
                push_notification BOOLEAN DEFAULT TRUE,
                
                -- Alert Status
                triggered_at TIMESTAMP NULL,
                trigger_count INT DEFAULT 0,
                last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_user_id (user_id),
                INDEX idx_alert_type (alert_type),
                INDEX idx_is_active (is_active),
                INDEX idx_target_price (target_price),
                INDEX idx_triggered_at (triggered_at)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Price and indicator alerts for {$sanitizedSymbol}'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Register stock in the master stocks table
     */
    private function registerStock(string $originalSymbol, string $sanitizedSymbol): void {
        // Create master stocks table if it doesn't exist
        $this->createMasterStocksTable();
        
        $sql = "
            INSERT INTO stocks (symbol, sanitized_symbol, tables_created_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                sanitized_symbol = VALUES(sanitized_symbol),
                tables_updated_at = NOW(),
                is_active = TRUE
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$originalSymbol, $sanitizedSymbol]);
    }
    
    /**
     * Create master stocks table for tracking all registered stocks
     */
    private function createMasterStocksTable(): void {
        $sql = "
            CREATE TABLE IF NOT EXISTS stocks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(20) NOT NULL UNIQUE,
                sanitized_symbol VARCHAR(50) NOT NULL,
                company_name VARCHAR(255),
                sector VARCHAR(100),
                industry VARCHAR(100),
                exchange VARCHAR(50),
                currency VARCHAR(3) DEFAULT 'USD',
                is_active BOOLEAN DEFAULT TRUE,
                tables_created_at TIMESTAMP NULL,
                tables_updated_at TIMESTAMP NULL,
                last_price_update TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                
                INDEX idx_symbol (symbol),
                INDEX idx_sector (sector),
                INDEX idx_is_active (is_active)
            ) ENGINE=InnoDB CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Master registry of all stocks with individual tables'
        ";
        
        $this->pdo->exec($sql);
    }
    
    /**
     * Sanitize symbol for use as table name
     */
    private function sanitizeSymbol(string $symbol): string {
        // Remove any non-alphanumeric characters and convert to uppercase
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($symbol));
        
        // Ensure it doesn't start with a number
        if (is_numeric(substr($sanitized, 0, 1))) {
            $sanitized = 'STOCK_' . $sanitized;
        }
        
        // Limit length to avoid MySQL table name limits
        return substr($sanitized, 0, 50);
    }
    
    /**
     * Get table name for a specific stock and data type
     */
    public function getTableName(string $symbol, string $type): string {
        $sanitizedSymbol = $this->sanitizeSymbol($symbol);
        return $this->config['table_prefix'] . $sanitizedSymbol . '_' . $type;
    }
    
    /**
     * Check if tables exist for a stock
     */
    public function stockTablesExist(string $symbol): bool {
        $sanitizedSymbol = $this->sanitizeSymbol($symbol);
        $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_prices';
        
        $sql = "SHOW TABLES LIKE " . $this->pdo->quote($tableName);
        $stmt = $this->pdo->query($sql);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Drop all tables for a stock (use with caution)
     */
    public function dropStockTables(string $symbol): bool {
        try {
            $sanitizedSymbol = $this->sanitizeSymbol($symbol);
            $tables = ['prices', 'fundamentals', 'technical', 'news', 'analysis', 'alerts'];
            
            foreach ($tables as $table) {
                $tableName = $this->config['table_prefix'] . $sanitizedSymbol . '_' . $table;
                $this->pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");
            }
            
            // Update master stocks table
            $sql = "UPDATE stocks SET is_active = FALSE WHERE symbol = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$symbol]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Failed to drop tables for stock {$symbol}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of all registered stocks
     */
    public function getRegisteredStocks(bool $activeOnly = true): array {
        $whereClause = $activeOnly ? 'WHERE is_active = TRUE' : '';
        
        $sql = "
            SELECT symbol, sanitized_symbol, company_name, sector, industry,
                   tables_created_at, last_price_update
            FROM stocks 
            {$whereClause}
            ORDER BY symbol
        ";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}