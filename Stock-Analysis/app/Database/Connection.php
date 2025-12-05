<?php

declare(strict_types=1);

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Database Connection Manager
 * 
 * Manages PDO database connections with configuration support,
 * connection pooling, and error handling.
 * 
 * Features:
 * - Lazy connection initialization
 * - Configuration-based setup
 * - Connection testing
 * - Transaction support
 * - Error handling with meaningful messages
 * 
 * @package App\Database
 */
class Connection
{
    private ?PDO $pdo = null;
    private array $config;
    
    /**
     * Create new database connection manager
     *
     * @param array<string, mixed> $config Database configuration
     *                                      - driver: 'mysql', 'sqlite', 'pgsql'
     *                                      - host: Database host
     *                                      - port: Database port
     *                                      - database: Database name
     *                                      - username: Database username
     *                                      - password: Database password
     *                                      - charset: Character set (default: utf8mb4)
     *                                      - options: PDO options array
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    /**
     * Get PDO instance (creates connection if needed)
     *
     * @return PDO Database connection
     * @throws RuntimeException If connection fails
     */
    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }
        
        return $this->pdo;
    }
    
    /**
     * Establish database connection
     *
     * @return void
     * @throws RuntimeException If connection fails
     */
    private function connect(): void
    {
        try {
            $dsn = $this->buildDsn();
            $username = $this->config['username'] ?? null;
            $password = $this->config['password'] ?? null;
            $options = $this->getDefaultOptions();
            
            if (isset($this->config['options']) && is_array($this->config['options'])) {
                $options = array_replace($options, $this->config['options']);
            }
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
        } catch (PDOException $e) {
            throw new RuntimeException(
                "Failed to connect to database: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }
    
    /**
     * Build DSN string from configuration
     *
     * @return string DSN string
     * @throws RuntimeException If driver is not supported
     */
    private function buildDsn(): string
    {
        $driver = $this->config['driver'] ?? 'mysql';
        
        return match ($driver) {
            'mysql' => $this->buildMysqlDsn(),
            'sqlite' => $this->buildSqliteDsn(),
            'pgsql' => $this->buildPgsqlDsn(),
            default => throw new RuntimeException("Unsupported database driver: {$driver}")
        };
    }
    
    /**
     * Build MySQL DSN string
     *
     * @return string MySQL DSN
     */
    private function buildMysqlDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 3306;
        $database = $this->config['database'] ?? '';
        $charset = $this->config['charset'] ?? 'utf8mb4';
        
        return "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";
    }
    
    /**
     * Build SQLite DSN string
     *
     * @return string SQLite DSN
     */
    private function buildSqliteDsn(): string
    {
        $database = $this->config['database'] ?? ':memory:';
        
        return "sqlite:{$database}";
    }
    
    /**
     * Build PostgreSQL DSN string
     *
     * @return string PostgreSQL DSN
     */
    private function buildPgsqlDsn(): string
    {
        $host = $this->config['host'] ?? 'localhost';
        $port = $this->config['port'] ?? 5432;
        $database = $this->config['database'] ?? '';
        
        return "pgsql:host={$host};port={$port};dbname={$database}";
    }
    
    /**
     * Get default PDO options
     *
     * @return array<int, mixed> PDO options
     */
    private function getDefaultOptions(): array
    {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
    }
    
    /**
     * Test database connection
     *
     * @return bool True if connection successful
     */
    public function testConnection(): bool
    {
        try {
            $pdo = $this->getPDO();
            $pdo->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Close database connection
     *
     * @return void
     */
    public function close(): void
    {
        $this->pdo = null;
    }
    
    /**
     * Begin transaction
     *
     * @return bool True if successful
     */
    public function beginTransaction(): bool
    {
        return $this->getPDO()->beginTransaction();
    }
    
    /**
     * Commit transaction
     *
     * @return bool True if successful
     */
    public function commit(): bool
    {
        return $this->getPDO()->commit();
    }
    
    /**
     * Rollback transaction
     *
     * @return bool True if successful
     */
    public function rollBack(): bool
    {
        return $this->getPDO()->rollBack();
    }
    
    /**
     * Check if in transaction
     *
     * @return bool True if in transaction
     */
    public function inTransaction(): bool
    {
        return $this->getPDO()->inTransaction();
    }
    
    /**
     * Get last insert ID
     *
     * @param string|null $name Sequence name (for PostgreSQL)
     * @return string Last insert ID
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->getPDO()->lastInsertId($name);
    }
}
