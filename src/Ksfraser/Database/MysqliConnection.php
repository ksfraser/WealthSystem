<?php
/**
 * MySQLi-based database connection implementation
 * 
 * @package Ksfraser\Database
 */

namespace Ksfraser\Database;

use mysqli;
use mysqli_stmt;
use mysqli_result;
use Exception;

/**
 * MySQLi connection wrapper implementing DatabaseConnectionInterface
 */
class MysqliConnection implements DatabaseConnectionInterface
{
    /** @var mysqli The MySQLi instance */
    protected $mysqli;
    
    /** @var bool Transaction status */
    protected $inTransaction = false;
    
    /**
     * Constructor
     * 
     * @param array $config Database configuration
     * @throws Exception If connection fails
     */
    public function __construct(array $config)
    {
        $this->mysqli = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database'],
            $config['port']
        );
        
        if ($this->mysqli->connect_error) {
            throw new Exception('MySQLi connection failed: ' . $this->mysqli->connect_error);
        }
        
        $this->mysqli->set_charset($config['charset']);
    }
    
    /**
     * Prepare a statement
     * 
     * @param string $sql SQL query
     * @return DatabaseStatementInterface Prepared statement
     * @throws Exception If prepare fails
     */
    public function prepare(string $sql): DatabaseStatementInterface
    {
        $stmt = $this->mysqli->prepare($sql);
        
        if (!$stmt) {
            throw new Exception('MySQLi prepare failed: ' . $this->mysqli->error);
        }
        
        return new MysqliStatementWrapper($stmt);
    }
    
    /**
     * Execute a statement directly
     * 
     * @param string $sql SQL statement
     * @return int Number of affected rows
     * @throws Exception If query fails
     */
    public function exec(string $sql): int
    {
        $result = $this->mysqli->query($sql);
        
        if ($result === false) {
            throw new Exception('MySQLi query failed: ' . $this->mysqli->error);
        }
        
        return $this->mysqli->affected_rows;
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last insert ID
     */
    public function lastInsertId(): string
    {
        return (string)$this->mysqli->insert_id;
    }
    
    /**
     * Begin transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction(): bool
    {
        $this->inTransaction = $this->mysqli->begin_transaction();
        return $this->inTransaction;
    }
    
    /**
     * Commit transaction
     * 
     * @return bool Success status
     */
    public function commit(): bool
    {
        if (!$this->inTransaction) {
            return false;
        }
        
        $result = $this->mysqli->commit();
        $this->inTransaction = false;
        return $result;
    }
    
    /**
     * Rollback transaction
     * 
     * @return bool Success status
     */
    public function rollback(): bool
    {
        if (!$this->inTransaction) {
            return false;
        }
        
        $result = $this->mysqli->rollback();
        $this->inTransaction = false;
        return $result;
    }
    
    /**
     * Get attribute (limited MySQLi support)
     * 
     * @param int $attribute Attribute constant
     * @return mixed Attribute value
     */
    public function getAttribute(int $attribute)
    {
        // MySQLi has limited attribute support
        switch ($attribute) {
            case 3: // PDO::ATTR_ERRMODE equivalent
                return 2; // ERRMODE_EXCEPTION equivalent
            default:
                return null;
        }
    }
    
    /**
     * Set attribute (limited MySQLi support)
     * 
     * @param int $attribute Attribute constant
     * @param mixed $value Attribute value
     * @return bool Success status
     */
    public function setAttribute(int $attribute, $value): bool
    {
        // MySQLi has limited attribute support
        return true; // Always return true for compatibility
    }
}

/**
 * MySQLi statement wrapper implementing DatabaseStatementInterface
 */
class MysqliStatementWrapper implements DatabaseStatementInterface
{
    /** @var mysqli_stmt The MySQLi statement */
    protected $stmt;
    
    /** @var mysqli_result|null Current result set */
    protected $result = null;
    
    /** @var array Bound parameters */
    protected $params = [];
    
    /** @var string Parameter types */
    protected $types = '';
    
    /**
     * Constructor
     * 
     * @param mysqli_stmt $stmt MySQLi statement
     */
    public function __construct(mysqli_stmt $stmt)
    {
        $this->stmt = $stmt;
    }
    
    /**
     * Execute the statement
     * 
     * @param array $params Parameters
     * @return bool Success status
     * @throws Exception If execution fails
     */
    public function execute(array $params = []): bool
    {
        if (!empty($params)) {
            $this->bindParams($params);
        }
        
        $result = $this->stmt->execute();
        
        if (!$result) {
            throw new Exception('MySQLi execute failed: ' . $this->stmt->error);
        }
        
        // Get result set for SELECT queries
        $this->result = $this->stmt->get_result();
        
        return true;
    }
    
    /**
     * Bind parameters for execution
     * 
     * @param array $params Parameters to bind
     */
    protected function bindParams(array $params): void
    {
        if (empty($params)) {
            return;
        }
        
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
        
        $this->stmt->bind_param($types, ...$params);
    }
    
    /**
     * Fetch single row
     * 
     * @param int|null $fetchStyle Fetch style (ignored for MySQLi)
     * @return array|false Row data or false
     */
    public function fetch(?int $fetchStyle = null)
    {
        if ($this->result === null || $this->result === false) {
            return false;
        }
        
        return $this->result->fetch_assoc();
    }
    
    /**
     * Fetch all rows
     * 
     * @param int|null $fetchStyle Fetch style (ignored for MySQLi)
     * @return array All rows
     */
    public function fetchAll(?int $fetchStyle = null): array
    {
        if ($this->result === null || $this->result === false) {
            return [];
        }
        
        $rows = [];
        while ($row = $this->result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Fetch single column
     * 
     * @param int $column Column index
     * @return mixed Column value
     */
    public function fetchColumn(int $column = 0)
    {
        if ($this->result === null || $this->result === false) {
            return false;
        }
        
        $row = $this->result->fetch_array();
        return $row ? $row[$column] : false;
    }
    
    /**
     * Get row count
     * 
     * @return int Number of rows
     */
    public function rowCount(): int
    {
        return $this->stmt->affected_rows;
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
        // MySQLi doesn't support named parameters or individual binding
        // This is a simplified implementation for compatibility
        return true;
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
        // MySQLi doesn't support named parameters or individual binding
        // This is a simplified implementation for compatibility
        return true;
    }
}
?>
