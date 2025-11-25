<?php

namespace Ksfraser\StockInfo;

use PDO;

/**
 * Database Connection Factory
 * 
 * Handles database connections and configuration for the StockInfo library
 */
class DatabaseFactory
{
    private static $instance = null;
    private $pdo;

    private function __construct($config)
    {
        $this->connect($config);
    }

    /**
     * Get singleton instance
     */
    public static function getInstance($config = null)
    {
        if (self::$instance === null) {
            if ($config === null) {
                throw new \InvalidArgumentException("Database configuration required for first initialization");
            }
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * Get PDO connection
     */
    public function getConnection()
    {
        return $this->pdo;
    }

    /**
     * Create database connection
     */
    private function connect($config)
    {
        $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $this->pdo = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $options
            );
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Test database connection
     */
    public function testConnection()
    {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /**
     * Get database info
     */
    public function getDatabaseInfo()
    {
        try {
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            return [
                'version' => $result->version,
                'driver' => $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME),
                'connection_status' => 'Connected'
            ];
        } catch (\PDOException $e) {
            return [
                'error' => $e->getMessage(),
                'connection_status' => 'Failed'
            ];
        }
    }
}
