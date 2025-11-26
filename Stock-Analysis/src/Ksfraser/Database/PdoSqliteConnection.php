<?php
/**
 * SQLite-based fallback database connection implementation
 * 
 * @package Ksfraser\Database
 */

namespace Ksfraser\Database;

use PDO;
use PDOException;
use Exception;

/**
 * SQLite connection wrapper implementing DatabaseConnectionInterface
 * This is used as a fallback when MySQL is not available
 */
class PdoSqliteConnection implements DatabaseConnectionInterface
{
    /** @var PDO The PDO instance */
    protected $pdo;
    
    /**
     * Constructor - creates in-memory SQLite database
     * 
     * @throws PDOException If connection fails
     */
    public function __construct()
    {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $this->pdo = new PDO('sqlite::memory:', null, null, $options);
        
        // Create basic users table for compatibility
        $this->createUsersTable();
    }
    
    /**
     * Create users table for compatibility with the application
     */
    protected function createUsersTable(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                is_admin INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->pdo->exec($sql);
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
?>
