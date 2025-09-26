<?php

/**
 * Dynamic Stock Table Manager
 * Creates and manages per-symbol tables for stock data
 */

require_once 'DatabaseConfig.php';
require_once __DIR__ . '/JobLogger.php';

require_once __DIR__ . '/src/IStockTableManager.php';

class StockTableManager implements IStockTableManager
{
    private $pdo;
    private $logger;
    
    // Table templates for each stock symbol
    private $tableTemplates = [
        'historical_prices' => [
            'suffix' => '_prices',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    open DECIMAL(10,4) NOT NULL,
                    high DECIMAL(10,4) NOT NULL,
                    low DECIMAL(10,4) NOT NULL,
                    close DECIMAL(10,4) NOT NULL,
                    adj_close DECIMAL(10,4),
                    volume BIGINT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_date_symbol (symbol, date),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'technical_indicators' => [
            'suffix' => '_indicators',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    indicator_name VARCHAR(50) NOT NULL,
                    value DECIMAL(15,6),
                    period INT,
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    calculation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_indicator (symbol, date, indicator_name, period, timeframe),
                    INDEX idx_indicator_name (indicator_name),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'candlestick_patterns' => [
            'suffix' => '_patterns',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    pattern_name VARCHAR(100) NOT NULL,
                    strength INT DEFAULT 50,
                    signal ENUM('BUY', 'SELL', 'NEUTRAL') DEFAULT 'NEUTRAL',
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    detection_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_pattern (symbol, date, pattern_name, timeframe),
                    INDEX idx_pattern_name (pattern_name),
                    INDEX idx_signal (signal),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'support_resistance' => [
            'suffix' => '_support_resistance',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    level_type ENUM('SUPPORT', 'RESISTANCE') NOT NULL,
                    price_level DECIMAL(10,4) NOT NULL,
                    strength INT DEFAULT 50,
                    touches INT DEFAULT 1,
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    calculated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_level_type (level_type),
                    INDEX idx_price_level (price_level),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'trading_signals' => [
            'suffix' => '_signals',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    signal_type VARCHAR(50) NOT NULL,
                    signal ENUM('BUY', 'SELL', 'HOLD') NOT NULL,
                    strength INT DEFAULT 50,
                    price DECIMAL(10,4),
                    strategy VARCHAR(100),
                    notes TEXT,
                    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_signal_type (signal_type),
                    INDEX idx_signal (signal),
                    INDEX idx_date (date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'earnings_data' => [
            'suffix' => '_earnings',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    quarter VARCHAR(10) NOT NULL,
                    year INT NOT NULL,
                    earnings_date DATE,
                    estimated_eps DECIMAL(8,4),
                    actual_eps DECIMAL(8,4),
                    revenue BIGINT,
                    estimated_revenue BIGINT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_quarter (symbol, quarter, year),
                    INDEX idx_earnings_date (earnings_date),
                    INDEX idx_year (year),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ],
        'dividends' => [
            'suffix' => '_dividends',
            'schema' => "
                CREATE TABLE IF NOT EXISTS {table_name} (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    ex_date DATE NOT NULL,
                    payment_date DATE,
                    record_date DATE,
                    declared_date DATE,
                    amount DECIMAL(8,4) NOT NULL,
                    frequency VARCHAR(20),
                    dividend_type VARCHAR(50) DEFAULT 'REGULAR',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_ex_date (symbol, ex_date),
                    INDEX idx_ex_date (ex_date),
                    INDEX idx_payment_date (payment_date),
                    INDEX idx_symbol (symbol)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ]
    ];
    
    public function __construct()
    {
        $this->pdo = DatabaseConfig::createLegacyConnection();
        $this->logger = new JobLogger('logs/table_manager.log');
        
        // Ensure we have the stock symbol registry table
        $this->createSymbolRegistryTable();
    }
    
    /**
     * Create the master symbol registry table
     */
    private function createSymbolRegistryTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS stock_symbol_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(10) NOT NULL UNIQUE,
                company_name VARCHAR(255),
                exchange VARCHAR(20),
                sector VARCHAR(100),
                industry VARCHAR(100),
                market_cap BIGINT,
                status ENUM('ACTIVE', 'INACTIVE', 'DELISTED') DEFAULT 'ACTIVE',
                tables_created BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_symbol (symbol),
                INDEX idx_status (status),
                INDEX idx_exchange (exchange)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $this->pdo->exec($sql);
        $this->logger->info("Stock symbol registry table ensured");
    }
    
    /**
     * Register a new stock symbol and create its tables
     */
    public function registerSymbol($symbol, $companyData = [])
    {
        $symbol = strtoupper(trim($symbol));
        
        // Validate symbol
        if (!$this->isValidSymbol($symbol)) {
            throw new InvalidArgumentException("Invalid stock symbol: {$symbol}");
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Insert or update symbol in registry
            $sql = "INSERT INTO stock_symbol_registry 
                    (symbol, company_name, exchange, sector, industry, market_cap, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                    company_name = COALESCE(VALUES(company_name), company_name),
                    exchange = COALESCE(VALUES(exchange), exchange),
                    sector = COALESCE(VALUES(sector), sector),
                    industry = COALESCE(VALUES(industry), industry),
                    market_cap = COALESCE(VALUES(market_cap), market_cap),
                    status = VALUES(status),
                    updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $symbol,
                $companyData['company_name'] ?? null,
                $companyData['exchange'] ?? null,
                $companyData['sector'] ?? null,
                $companyData['industry'] ?? null,
                $companyData['market_cap'] ?? null,
                $companyData['status'] ?? 'ACTIVE'
            ]);
            
            // Create tables for this symbol
            $this->createTablesForSymbol($symbol);
            
            // Mark tables as created
            $sql = "UPDATE stock_symbol_registry SET tables_created = TRUE WHERE symbol = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$symbol]);
            
            $this->pdo->commit();
            
            $this->logger->info("Successfully registered symbol: {$symbol}");
            
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->logger->error("Failed to register symbol {$symbol}: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Create all tables for a specific symbol
     */
    public function createTablesForSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $createdTables = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                $sql = str_replace('{table_name}', $tableName, $template['schema']);
                $this->pdo->exec($sql);
                
                $createdTables[] = $tableName;
                $this->logger->info("Created table: {$tableName}");
                
            } catch (Exception $e) {
                $this->logger->error("Failed to create table {$tableName}: " . $e->getMessage());
                throw $e;
            }
        }
        
        return $createdTables;
    }
    
    /**
     * Get table name for a specific symbol and data type
     */
    public function getTableName($symbol, $tableType)
    {
        if (!isset($this->tableTemplates[$tableType])) {
            throw new InvalidArgumentException("Unknown table type: {$tableType}");
        }
        
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        return $sanitizedSymbol . $this->tableTemplates[$tableType]['suffix'];
    }
    
    /**
     * Check if tables exist for a symbol
     */
    public function tablesExistForSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        
        $sql = "SELECT tables_created FROM stock_symbol_registry WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        
        $result = $stmt->fetchColumn();
        return $result === '1' || $result === 1 || $result === true;
    }
    
    /**
     * Get all registered symbols
     */
    public function getAllSymbols($activeOnly = true)
    {
        $sql = "SELECT symbol, company_name, exchange, status, tables_created 
                FROM stock_symbol_registry";
        
        if ($activeOnly) {
            $sql .= " WHERE status = 'ACTIVE'";
        }
        
        $sql .= " ORDER BY symbol";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Remove tables for a symbol (use with caution!)
     */
    public function removeTablesForSymbol($symbol, $confirm = false)
    {
        if (!$confirm) {
            throw new InvalidArgumentException("Must confirm table removal by passing confirm=true");
        }
        
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $droppedTables = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                $sql = "DROP TABLE IF EXISTS {$tableName}";
                $this->pdo->exec($sql);
                
                $droppedTables[] = $tableName;
                $this->logger->warning("Dropped table: {$tableName}");
                
            } catch (Exception $e) {
                $this->logger->error("Failed to drop table {$tableName}: " . $e->getMessage());
            }
        }
        
        // Update registry
        $sql = "UPDATE stock_symbol_registry SET tables_created = FALSE WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$symbol]);
        
        return $droppedTables;
    }
    
    /**
     * Deactivate a symbol (keeps tables but marks as inactive)
     */
    public function deactivateSymbol($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        
        $sql = "UPDATE stock_symbol_registry SET status = 'INACTIVE' WHERE symbol = ?";
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute([$symbol]);
        
        if ($result) {
            $this->logger->info("Deactivated symbol: {$symbol}");
        }
        
        return $result;
    }
    
    /**
     * Get table statistics for a symbol
     */
    public function getSymbolTableStats($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $stats = [];
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            
            try {
                // Check if table exists
                $sql = "SHOW TABLES LIKE ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$tableName]);
                
                if ($stmt->rowCount() > 0) {
                    // Get row count
                    $sql = "SELECT COUNT(*) as row_count FROM {$tableName}";
                    $stmt = $this->pdo->query($sql);
                    $rowCount = $stmt->fetchColumn();
                    
                    // Get table size
                    $sql = "SELECT 
                                ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                            FROM information_schema.TABLES 
                            WHERE table_schema = DATABASE() 
                            AND table_name = ?";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([$tableName]);
                    $sizeInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stats[$tableType] = [
                        'table_name' => $tableName,
                        'exists' => true,
                        'row_count' => $rowCount,
                        'size_mb' => $sizeInfo['size_mb'] ?? 0
                    ];
                } else {
                    $stats[$tableType] = [
                        'table_name' => $tableName,
                        'exists' => false,
                        'row_count' => 0,
                        'size_mb' => 0
                    ];
                }
            } catch (Exception $e) {
                $stats[$tableType] = [
                    'table_name' => $tableName,
                    'exists' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Sanitize symbol for use in table names
     */
    private function sanitizeSymbolForTableName($symbol)
    {
        // Replace special characters with underscores
        $sanitized = preg_replace('/[^A-Za-z0-9]/', '_', $symbol);
        
        // Ensure it starts with a letter
        if (is_numeric(substr($sanitized, 0, 1))) {
            $sanitized = 'stock_' . $sanitized;
        }
        
        // Convert to lowercase for consistency
        return strtolower($sanitized);
    }
    
    /**
     * Validate stock symbol format
     */
    private function isValidSymbol($symbol)
    {
        // Basic validation - adjust as needed
        return preg_match('/^[A-Z0-9.-]{1,10}$/i', $symbol);
    }
    
    /**
     * Bulk register symbols from array
     */
    public function bulkRegisterSymbols($symbols)
    {
        $results = [
            'success' => [],
            'failed' => []
        ];
        
        foreach ($symbols as $symbolData) {
            $symbol = is_array($symbolData) ? $symbolData['symbol'] : $symbolData;
            $companyData = is_array($symbolData) ? $symbolData : [];
            
            try {
                $this->registerSymbol($symbol, $companyData);
                $results['success'][] = $symbol;
            } catch (Exception $e) {
                $results['failed'][] = [
                    'symbol' => $symbol,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Generate table creation script for a symbol (for backup/migration)
     */
    public function generateTableScript($symbol)
    {
        $symbol = strtoupper(trim($symbol));
        $sanitizedSymbol = $this->sanitizeSymbolForTableName($symbol);
        
        $script = "-- Table creation script for symbol: {$symbol}\n";
        $script .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($this->tableTemplates as $tableType => $template) {
            $tableName = $sanitizedSymbol . $template['suffix'];
            $sql = str_replace('{table_name}', $tableName, $template['schema']);
            
            $script .= "-- {$tableType} table\n";
            $script .= $sql . ";\n\n";
        }
        
        return $script;
    }
}
