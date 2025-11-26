#!/usr/bin/env php
<?php
/**
 * Database Setup Script
 * 
 * Creates all necessary database tables for:
 * 1. Legacy tables (for migration from Python data)
 * 2. Modern per-symbol tables (target of migration)
 * 3. Metadata and configuration tables
 */

require_once __DIR__ . '/../DatabaseConfig.php';
require_once __DIR__ . '/../StockTableManager.php';
require_once __DIR__ . '/../src/IStockTableManager.php';
require_once __DIR__ . '/../src/TableTypeRegistry.php';

class DatabaseSetup
{
    private $legacyPdo;
    private $microCapPdo;
    private $tableManager;

    public function __construct()
    {
        try {
            DatabaseConfig::load();
            $this->legacyPdo = DatabaseConfig::createLegacyConnection();
            $this->microCapPdo = DatabaseConfig::createMicroCapConnection();
            $this->tableManager = new StockTableManager();
        } catch (Exception $e) {
            echo "Error connecting to database: " . $e->getMessage() . "\n";
            echo "Please ensure your database configuration is correct in db_config.yml\n";
            exit(1);
        }
    }

    /**
     * Run the complete database setup
     */
    public function run($options = [])
    {
        $createLegacy = $options['legacy'] ?? true;
        $createModern = $options['modern'] ?? true;
        $createSample = $options['sample'] ?? false;
        
        echo "=== Database Setup Starting ===\n";
        
        if ($createLegacy) {
            echo "\n--- Creating Legacy Tables ---\n";
            $this->createLegacyTables();
        }
        
        if ($createModern) {
            echo "\n--- Creating Modern Schema ---\n";
            $this->createModernSchema();
        }
        
        if ($createSample) {
            echo "\n--- Creating Sample Data ---\n";
            $this->createSampleData();
        }
        
        echo "\n=== Database Setup Complete ===\n";
        $this->showStatus();
    }

    /**
     * Create legacy tables that Python scripts will populate
     */
    private function createLegacyTables()
    {
        $tables = [
            'historical_prices' => "
                CREATE TABLE IF NOT EXISTS historical_prices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    open DECIMAL(10,4) NOT NULL,
                    high DECIMAL(10,4) NOT NULL,
                    low DECIMAL(10,4) NOT NULL,
                    close DECIMAL(10,4) NOT NULL,
                    adj_close DECIMAL(10,4) NOT NULL,
                    volume BIGINT NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'technical_indicators' => "
                CREATE TABLE IF NOT EXISTS technical_indicators (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    indicator_name VARCHAR(50) NOT NULL,
                    value DECIMAL(15,6) NOT NULL,
                    timeframe VARCHAR(10) DEFAULT 'daily',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date_indicator (symbol, date, indicator_name),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date),
                    INDEX idx_indicator (indicator_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'candlestick_patterns' => "
                CREATE TABLE IF NOT EXISTS candlestick_patterns (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    pattern_name VARCHAR(100) NOT NULL,
                    confidence DECIMAL(3,2) DEFAULT 1.00,
                    direction ENUM('bullish', 'bearish', 'neutral') DEFAULT 'neutral',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date_pattern (symbol, date, pattern_name),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date),
                    INDEX idx_pattern (pattern_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'portfolio_data' => "
                CREATE TABLE IF NOT EXISTS portfolio_data (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    position_size DECIMAL(10,4) NOT NULL,
                    avg_cost DECIMAL(10,4) NOT NULL,
                    current_price DECIMAL(10,4) NOT NULL,
                    market_value DECIMAL(12,2) NOT NULL,
                    unrealized_pnl DECIMAL(12,2) NOT NULL,
                    realized_pnl DECIMAL(12,2) DEFAULT 0.00,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_symbol_date (symbol, date),
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'trade_log' => "
                CREATE TABLE IF NOT EXISTS trade_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    symbol VARCHAR(10) NOT NULL,
                    date DATE NOT NULL,
                    action ENUM('BUY', 'SELL', 'HOLD') NOT NULL,
                    quantity DECIMAL(10,4) NOT NULL,
                    price DECIMAL(10,4) NOT NULL,
                    amount DECIMAL(12,2) NOT NULL,
                    fees DECIMAL(8,2) DEFAULT 0.00,
                    reasoning TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_symbol (symbol),
                    INDEX idx_date (date),
                    INDEX idx_action (action)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];

        foreach ($tables as $tableName => $sql) {
            try {
                echo "Creating table: {$tableName}...";
                $this->legacyPdo->exec($sql);
                echo " ✓\n";
            } catch (PDOException $e) {
                echo " ✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }

    /**
     * Create modern per-symbol table schema and metadata tables
     */
    private function createModernSchema()
    {
        // Create symbol registry table
        $symbolRegistrySQL = "
            CREATE TABLE IF NOT EXISTS symbol_registry (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(10) NOT NULL UNIQUE,
                company_name VARCHAR(255) DEFAULT '',
                sector VARCHAR(100) DEFAULT '',
                industry VARCHAR(100) DEFAULT '',
                market_cap ENUM('nano', 'micro', 'small', 'mid', 'large') DEFAULT 'micro',
                active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_symbol (symbol),
                INDEX idx_active (active),
                INDEX idx_market_cap (market_cap)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        try {
            echo "Creating symbol_registry table...";
            $this->microCapPdo->exec($symbolRegistrySQL);
            echo " ✓\n";
        } catch (PDOException $e) {
            echo " ✗ Error: " . $e->getMessage() . "\n";
        }

        // Create migration tracking table
        $migrationTrackingSQL = "
            CREATE TABLE IF NOT EXISTS migration_tracking (
                id INT AUTO_INCREMENT PRIMARY KEY,
                symbol VARCHAR(10) NOT NULL,
                legacy_table VARCHAR(50) NOT NULL,
                target_table VARCHAR(50) NOT NULL,
                records_migrated INT DEFAULT 0,
                migration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
                error_message TEXT,
                UNIQUE KEY unique_symbol_legacy_target (symbol, legacy_table, target_table),
                INDEX idx_symbol (symbol),
                INDEX idx_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";

        try {
            echo "Creating migration_tracking table...";
            $this->microCapPdo->exec($migrationTrackingSQL);
            echo " ✓\n";
        } catch (PDOException $e) {
            echo " ✗ Error: " . $e->getMessage() . "\n";
        }

        echo "Modern schema tables created.\n";
        echo "Note: Per-symbol tables will be created automatically when symbols are added.\n";
    }

    /**
     * Create sample data for testing
     */
    private function createSampleData()
    {
        $sampleSymbols = ['IBM', 'AAPL', 'GOOGL', 'TSLA', 'MSFT'];
        
        echo "Adding sample symbols to registry...\n";
        
        foreach ($sampleSymbols as $symbol) {
            try {
                $stmt = $this->microCapPdo->prepare("
                    INSERT IGNORE INTO symbol_registry (symbol, company_name, sector, market_cap, active) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $companyData = $this->getSampleCompanyData($symbol);
                $stmt->execute([
                    $symbol,
                    $companyData['name'],
                    $companyData['sector'],
                    $companyData['market_cap'],
                    true
                ]);
                
                echo "  ✓ {$symbol}: {$companyData['name']}\n";
                
                // Create per-symbol tables
                $this->tableManager->createTablesForSymbol($symbol);
                
            } catch (Exception $e) {
                echo "  ✗ {$symbol}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "Adding sample legacy data...\n";
        $this->createSampleLegacyData($sampleSymbols);
    }

    /**
     * Get sample company data
     */
    private function getSampleCompanyData($symbol)
    {
        $data = [
            'IBM' => ['name' => 'International Business Machines', 'sector' => 'Technology', 'market_cap' => 'large'],
            'AAPL' => ['name' => 'Apple Inc.', 'sector' => 'Technology', 'market_cap' => 'large'],
            'GOOGL' => ['name' => 'Alphabet Inc.', 'sector' => 'Technology', 'market_cap' => 'large'],
            'TSLA' => ['name' => 'Tesla Inc.', 'sector' => 'Automotive', 'market_cap' => 'large'],
            'MSFT' => ['name' => 'Microsoft Corporation', 'sector' => 'Technology', 'market_cap' => 'large']
        ];
        
        return $data[$symbol] ?? ['name' => $symbol, 'sector' => 'Unknown', 'market_cap' => 'micro'];
    }

    /**
     * Create sample legacy data
     */
    private function createSampleLegacyData($symbols)
    {
        $startDate = new DateTime('-30 days');
        $endDate = new DateTime();
        
        foreach ($symbols as $symbol) {
            $currentDate = clone $startDate;
            $basePrice = rand(50, 200);
            
            while ($currentDate <= $endDate) {
                // Skip weekends
                if ($currentDate->format('w') != 0 && $currentDate->format('w') != 6) {
                    $this->insertSamplePriceData($symbol, $currentDate, $basePrice);
                    $this->insertSampleIndicatorData($symbol, $currentDate, $basePrice);
                    
                    // Simulate price movement
                    $basePrice += rand(-5, 5) * 0.1;
                    $basePrice = max(10, $basePrice); // Don't go below $10
                }
                
                $currentDate->add(new DateInterval('P1D'));
            }
            
            echo "  ✓ {$symbol}: Created 30 days of sample data\n";
        }
    }

    /**
     * Insert sample price data
     */
    private function insertSamplePriceData($symbol, $date, $basePrice)
    {
        $variation = rand(95, 105) / 100; // ±5% variation
        $open = round($basePrice * $variation, 2);
        $high = round($open * rand(100, 110) / 100, 2);
        $low = round($open * rand(90, 100) / 100, 2);
        $close = round($low + rand(0, 100) / 100 * ($high - $low), 2);
        $volume = rand(100000, 1000000);

        $stmt = $this->legacyPdo->prepare("
            INSERT IGNORE INTO historical_prices 
            (symbol, date, open, high, low, close, adj_close, volume) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $symbol,
            $date->format('Y-m-d'),
            $open,
            $high,
            $low,
            $close,
            $close, // adj_close = close for simplicity
            $volume
        ]);
    }

    /**
     * Insert sample indicator data
     */
    private function insertSampleIndicatorData($symbol, $date, $basePrice)
    {
        $indicators = [
            'SMA_20' => $basePrice + rand(-10, 10),
            'RSI' => rand(30, 70),
            'MACD' => rand(-5, 5) * 0.1,
            'BOLLINGER_UPPER' => $basePrice + rand(5, 15),
            'BOLLINGER_LOWER' => $basePrice - rand(5, 15)
        ];

        $stmt = $this->legacyPdo->prepare("
            INSERT IGNORE INTO technical_indicators 
            (symbol, date, indicator_name, value) 
            VALUES (?, ?, ?, ?)
        ");

        foreach ($indicators as $name => $value) {
            $stmt->execute([
                $symbol,
                $date->format('Y-m-d'),
                $name,
                $value
            ]);
        }
    }

    /**
     * Show database status
     */
    private function showStatus()
    {
        echo "\n=== Database Status ===\n";
        
        // Legacy database tables
        echo "Legacy Database Tables:\n";
        $legacyTables = ['historical_prices', 'technical_indicators', 'candlestick_patterns', 'portfolio_data', 'trade_log'];
        foreach ($legacyTables as $table) {
            $count = $this->getTableRowCount($this->legacyPdo, $table);
            echo "  {$table}: {$count} rows\n";
        }
        
        // Modern database tables
        echo "\nMicro-Cap Database Tables:\n";
        $microCapTables = ['symbol_registry', 'migration_tracking'];
        foreach ($microCapTables as $table) {
            $count = $this->getTableRowCount($this->microCapPdo, $table);
            echo "  {$table}: {$count} rows\n";
        }
        
        // Per-symbol tables
        try {
            $stmt = $this->microCapPdo->query("SELECT symbol FROM symbol_registry WHERE active = 1");
            $symbols = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($symbols)) {
                echo "\nPer-Symbol Tables:\n";
                foreach ($symbols as $symbol) {
                    echo "  {$symbol}:\n";
                    foreach (TableTypeRegistry::TABLE_TYPES as $tableType) {
                        $tableName = strtolower($symbol) . '_' . $tableType;
                        $count = $this->getTableRowCount($this->microCapPdo, $tableName);
                        echo "    {$tableName}: {$count} rows\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "  Error checking per-symbol tables: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Get row count for a table
     */
    private function getTableRowCount($pdo, $tableName)
    {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM `{$tableName}`");
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            return "N/A (table doesn't exist)";
        }
    }
}

// Command line interface
function showUsage()
{
    echo "Database Setup Tool\n\n";
    echo "Usage:\n";
    echo "  php setup-database.php [OPTIONS]\n\n";
    echo "Options:\n";
    echo "  --legacy-only     Create only legacy tables\n";
    echo "  --modern-only     Create only modern schema\n";
    echo "  --with-sample     Include sample data\n";
    echo "  --help            Show this help message\n\n";
    echo "Examples:\n";
    echo "  php setup-database.php\n";
    echo "  php setup-database.php --with-sample\n";
    echo "  php setup-database.php --legacy-only\n";
}

// Main execution
if (php_sapi_name() === 'cli') {
    $options = [
        'legacy' => true,
        'modern' => true,
        'sample' => false
    ];

    foreach ($argv as $arg) {
        switch ($arg) {
            case '--help':
                showUsage();
                exit(0);
            case '--legacy-only':
                $options['modern'] = false;
                break;
            case '--modern-only':
                $options['legacy'] = false;
                break;
            case '--with-sample':
                $options['sample'] = true;
                break;
        }
    }

    try {
        $setup = new DatabaseSetup();
        $setup->run($options);
    } catch (Exception $e) {
        echo "Setup failed: " . $e->getMessage() . "\n";
        exit(1);
    }
}
