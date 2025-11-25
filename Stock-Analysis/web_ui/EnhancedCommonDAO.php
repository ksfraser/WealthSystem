<?php
/**
 * Enhanced CommonDAO - Builds upon existing CommonDAO with improved SOLID principles
 * This extends the existing centralized database system rather than replacing it
 */

require_once __DIR__ . '/CommonDAO.php';

/**
 * Enhanced base DAO that extends the existing CommonDAO with modern practices
 * while maintaining backward compatibility
 */
abstract class EnhancedCommonDAO extends CommonDAO
{
    protected $validator;
    protected $logger;

    public function __construct($dbConfigClass, $validator = null, $logger = null)
    {
        // Call parent constructor to maintain existing behavior
        parent::__construct($dbConfigClass);
        
        // Add enhanced features
        $this->validator = $validator;
        $this->logger = $logger ?: new SimpleLogger();
        
        // Log connection status
        if ($this->pdo) {
            $this->logger->info('Database connection established', [
                'config_class' => $dbConfigClass
            ]);
        } else {
            $this->logger->error('Database connection failed', [
                'config_class' => $dbConfigClass,
                'errors' => $this->errors
            ]);
        }
    }

    /**
     * Enhanced query execution with validation and logging
     */
    protected function executeQuery($sql, array $params = [])
    {
        if (!$this->pdo) {
            throw new RuntimeException('No database connection available');
        }

        try {
            if (empty($params)) {
                $stmt = $this->pdo->query($sql);
            } else {
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $this->logger->debug('Query executed successfully', [
                'sql' => $sql,
                'params' => $params
            ]);
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logError('Query execution failed: ' . $e->getMessage());
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Database operation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate data before database operations
     */
    protected function validateData($data)
    {
        if ($this->validator === null) {
            return ['valid' => true, 'errors' => [], 'warnings' => []];
        }

        return $this->validator->validate($data);
    }

    /**
     * Execute multiple operations in a transaction
     */
    protected function executeTransaction(callable $operations)
    {
        if (!$this->pdo) {
            throw new RuntimeException('No database connection available for transaction');
        }

        $this->pdo->beginTransaction();
        
        try {
            $result = $operations();
            $this->pdo->commit();
            $this->logger->info('Transaction completed successfully');
            return $result;
        } catch (Exception $e) {
            $this->pdo->rollback();
            $this->logError('Transaction failed and was rolled back: ' . $e->getMessage());
            $this->logger->error('Transaction failed and rolled back', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Build WHERE clause from conditions array with parameter binding
     */
    protected function buildWhereClause(array $conditions)
    {
        if (empty($conditions)) {
            return ['', []];
        }

        $whereParts = [];
        $params = [];

        foreach ($conditions as $column => $value) {
            // Sanitize column name to prevent SQL injection
            $sanitizedColumn = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
            
            if ($value === null) {
                $whereParts[] = "$sanitizedColumn IS NULL";
            } else {
                $whereParts[] = "$sanitizedColumn = :$sanitizedColumn";
                $params[$sanitizedColumn] = $value;
            }
        }

        $whereClause = 'WHERE ' . implode(' AND ', $whereParts);
        return [$whereClause, $params];
    }

    /**
     * Enhanced error logging that uses both parent system and new logger
     */
    protected function logError($msg)
    {
        // Call parent method to maintain existing behavior
        parent::logError($msg);
        
        // Also log using enhanced logger if available
        if ($this->logger) {
            $this->logger->error($msg, [
                'class' => get_class($this),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Get last insert ID with error handling
     */
    protected function getLastInsertId()
    {
        if (!$this->pdo) {
            throw new RuntimeException('No database connection available');
        }
        
        return $this->pdo->lastInsertId();
    }

    /**
     * Check if database connection is available
     */
    public function hasValidConnection()
    {
        return $this->pdo !== null;
    }

    /**
     * Get database status information
     */
    public function getConnectionInfo()
    {
        if (!$this->pdo) {
            return [
                'connected' => false,
                'errors' => $this->errors,
                'config_class' => $this->dbConfigClass
            ];
        }

        try {
            $version = $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            return [
                'connected' => true,
                'server_version' => $version,
                'config_class' => $this->dbConfigClass,
                'errors' => $this->errors
            ];
        } catch (Exception $e) {
            return [
                'connected' => false,
                'error' => $e->getMessage(),
                'config_class' => $this->dbConfigClass
            ];
        }
    }
}

/**
 * Simple logger implementation that works with existing system
 */
class SimpleLogger
{
    private $logFile;
    private $logLevel;

    public function __construct($logFile = null, $logLevel = 'INFO')
    {
        $this->logFile = $logFile ?: __DIR__ . '/logs/enhanced_dao.log';
        $this->logLevel = $logLevel;
        
        // Ensure log directory exists
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
    }

    public function debug($message, array $context = [])
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info($message, array $context = [])
    {
        $this->log('INFO', $message, $context);
    }

    public function warning($message, array $context = [])
    {
        $this->log('WARNING', $message, $context);
    }

    public function error($message, array $context = [])
    {
        $this->log('ERROR', $message, $context);
    }

    private function log($level, $message, array $context = [])
    {
        // Simple level filtering
        $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
        if ($levels[$level] < $levels[$this->logLevel]) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        @file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}

/**
 * Enhanced Transaction DAO that extends existing system
 */
class EnhancedTransactionDAO extends EnhancedCommonDAO
{
    public function __construct($dbConfigClass = 'LegacyDatabaseConfig', $validator = null, $logger = null)
    {
        parent::__construct($dbConfigClass, $validator, $logger);
    }

    public function insertTransaction(array $transactionData)
    {
        // Validate transaction data if validator is available
        $validation = $this->validateData($transactionData);
        if (!$validation['valid']) {
            throw new InvalidArgumentException('Invalid transaction data: ' . implode(', ', $validation['errors']));
        }

        $sql = "INSERT INTO transactions (symbol, shares, price, txn_date, txn_type) 
                VALUES (:symbol, :shares, :price, :txn_date, :txn_type)";

        $params = [
            'symbol' => $transactionData['symbol'],
            'shares' => $transactionData['shares'],
            'price' => $transactionData['price'],
            'txn_date' => $transactionData['txn_date'],
            'txn_type' => $transactionData['txn_type'] ?? 'BUY'
        ];

        $this->executeQuery($sql, $params);
        $insertId = $this->getLastInsertId();

        $this->logger->info('Transaction inserted', [
            'id' => $insertId,
            'symbol' => $transactionData['symbol'],
            'shares' => $transactionData['shares']
        ]);

        return $insertId;
    }

    public function getTransactionsBySymbol($symbol)
    {
        $sql = "SELECT * FROM transactions WHERE symbol = :symbol ORDER BY txn_date DESC";
        $stmt = $this->executeQuery($sql, ['symbol' => $symbol]);
        return $stmt->fetchAll();
    }

    public function getAllTransactions()
    {
        $sql = "SELECT * FROM transactions ORDER BY txn_date DESC";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }

    public function getPortfolioSummary()
    {
        $sql = "SELECT 
                    symbol,
                    SUM(CASE WHEN txn_type = 'BUY' THEN shares ELSE -shares END) as total_shares,
                    AVG(CASE WHEN txn_type = 'BUY' THEN price END) as avg_buy_price,
                    COUNT(*) as transaction_count
                FROM transactions 
                GROUP BY symbol
                HAVING total_shares > 0
                ORDER BY symbol";
        
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll();
    }
}
