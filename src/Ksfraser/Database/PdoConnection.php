<?php
/**
 * PDO-based database connection implementation
 * 
 * @package Ksfraser\Database
 */

namespace Ksfraser\Database;

use PDO;
use PDOException;
use PDOStatement;

/**
 * PDO connection wrapper implementing DatabaseConnectionInterface
 */
class PdoConnection implements DatabaseConnectionInterface
{
    /** @var PDO The PDO instance */
    protected $pdo;
    
    /**
     * Constructor
     * 
     * @param array $config Database configuration
     * @throws PDOException If connection fails
     */
    public function __construct(array $config)
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
    }
    
    /**
     * Prepare a statement
     * 
     * @param string $sql SQL query
     * @return DatabaseStatementInterface Prepared statement
     */
    public function prepare(string $sql): DatabaseStatementInterface
    {
        $stmt = $this->pdo->prepare($sql);
        return new PdoStatementWrapper($stmt);
    }
    
    /**
     * Execute a statement directly
     * 
     * @param string $sql SQL statement
     * @return int Number of affected rows
     */
    public function exec(string $sql): int
    {
        return $this->pdo->exec($sql);
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last insert ID
     */
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool
    {
        return $this->pdo->rollback();
    }
    
    /**
     * Get attribute
     * 
     * @param int $attribute Attribute constant
     * @return mixed Attribute value
     */
    public function getAttribute(int $attribute)
    {
        return $this->pdo->getAttribute($attribute);
    }
    
    /**
     * Set attribute
     * 
     * @param int $attribute Attribute constant
     * @param mixed $value Attribute value
     * @return bool Success status
     */
    public function setAttribute(int $attribute, $value): bool
    {
        return $this->pdo->setAttribute($attribute, $value);
    }
}

/**
 * PDO statement wrapper implementing DatabaseStatementInterface
 */
class PdoStatementWrapper implements DatabaseStatementInterface
{
    /** @var PDOStatement The PDO statement */
    protected $stmt;
    
    /**
     * Constructor
     * 
     * @param PDOStatement $stmt PDO statement
     */
    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }
    
    /**
     * Execute the statement
     * 
     * @param array $params Parameters
     * @return bool Success status
     */
    public function execute(array $params = []): bool
    {
        return $this->stmt->execute($params);
    }
    
    /**
     * Fetch single row
     * 
     * @param int|null $fetchStyle Fetch style
     * @return mixed Row data or false
     */
    public function fetch(?int $fetchStyle = null)
    {
        return $this->stmt->fetch($fetchStyle ?? PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch all rows
     * 
     * @param int|null $fetchStyle Fetch style
     * @return array All rows
     */
    public function fetchAll(?int $fetchStyle = null): array
    {
        return $this->stmt->fetchAll($fetchStyle ?? PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch single column
     * 
     * @param int $column Column index
     * @return mixed Column value
     */
    public function fetchColumn(int $column = 0)
    {
        return $this->stmt->fetchColumn($column);
    }
    
    /**
     * Get row count
     * 
     * @return int Number of rows
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }
    
    /**
     * Bind parameter by reference
     * 
     * @param mixed $parameter Parameter name or index
     * @param mixed $variable Variable reference
     * @param int|null $dataType Data type
     * @return bool Success status
     */
    public function bindParam($parameter, &$variable, ?int $dataType = null): bool
    {
        return $this->stmt->bindParam($parameter, $variable, $dataType ?? PDO::PARAM_STR);
    }
    
    /**
     * Bind parameter by value
     * 
     * @param mixed $parameter Parameter name or index
     * @param mixed $value Parameter value
     * @param int|null $dataType Data type
     * @return bool Success status
     */
    public function bindValue($parameter, $value, ?int $dataType = null): bool
    {
        return $this->stmt->bindValue($parameter, $value, $dataType ?? PDO::PARAM_STR);
    }
}
?>
