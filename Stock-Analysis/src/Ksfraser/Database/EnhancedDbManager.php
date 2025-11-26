<?php
/**
 * Enhanced Database Manager with PDO-first, MySQLi fallback support
 * Extended from ksfraser/Database with additional resilience
 * 
 * @package Ksfraser\Database
 * @author ksfraser
 * @license GPL-3.0
 */

namespace Ksfraser\Database;

use Exception;
use PDO;
use PDOException;
use mysqli;
use mysqli_result;

/**
 * Database connection interface for consistent API across drivers
 */
interface DatabaseConnectionInterface
{
    public function prepare(string $sql): DatabaseStatementInterface;
    public function exec(string $sql): int;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function getAttribute(int $attribute);
    public function setAttribute(int $attribute, $value): bool;
}

/**
 * Database statement interface for consistent API across drivers
 */
interface DatabaseStatementInterface
{
    public function execute(array $params = []): bool;
    public function fetch(?int $fetchStyle = null);
    public function fetchAll(?int $fetchStyle = null): array;
    public function fetchColumn(int $column = 0);
    public function rowCount(): int;
    public function bindParam($parameter, &$variable, ?int $dataType = null): bool;
    public function bindValue($parameter, $value, ?int $dataType = null): bool;
}

/**
 * Enhanced Database Manager with multiple driver support
 */
class EnhancedDbManager
{
    /** @var array Configuration cache */
    protected static $config = null;
    
    /** @var DatabaseConnectionInterface|null Singleton connection */
    protected static $connection = null;
    
    /** @var string Current driver being used */
    protected static $currentDriver = null;
    
    /** @var array Available driver priorities */
    protected static $driverPriority = ['pdo_mysql', 'mysqli', 'pdo_sqlite'];
    
    /**
     * Load database configuration from multiple sources
     * 
     * @param string|null $configFile Optional config file path
     * @return array Configuration array
     */
    public static function getConfig(?string $configFile = null): array
    {
        if (self::$config !== null) {
            return self::$config;
        }
        
        $config = [];
        
        // Try multiple configuration file locations
        $possibleFiles = [
            $configFile,
            __DIR__ . '/../../../../db_config.ini',
            __DIR__ . '/../../../../db_config.yml',
            __DIR__ . '/../../../../db_config.yaml',
            __DIR__ . '/../../../config/db_config.ini',
            __DIR__ . '/../../../config/db_config.yml',
        ];
        
        $configLoaded = false;
        foreach ($possibleFiles as $file) {
            if ($file && file_exists($file)) {
                $config = self::loadConfigFile($file);
                $configLoaded = true;
                break;
            }
        }
        
        if (!$configLoaded) {
            // Fallback to environment variables
            $config = [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'username' => $_ENV['DB_USERNAME'] ?? '',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'database' => $_ENV['DB_DATABASE'] ?? '',
                'charset' => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
            ];
        }
        
        // Ensure required fields have defaults
        $config = array_merge([
            'host' => 'localhost',
            'port' => 3306,
            'username' => '',
            'password' => '',
            'database' => '',
            'charset' => 'utf8mb4',
        ], $config);
        
        self::$config = $config;
        return $config;
    }
    
    /**
     * Load configuration from file (INI or YAML)
     * 
     * @param string $file File path
     * @return array Configuration array
     */
    protected static function loadConfigFile(string $file): array
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'ini':
                $config = parse_ini_file($file, true);
                return self::normalizeConfig($config);
                
            case 'yml':
            case 'yaml':
                if (function_exists('yaml_parse_file')) {
                    $config = yaml_parse_file($file);
                } else {
                    $config = self::parseSimpleYaml($file);
                }
                return self::normalizeConfig($config);
                
            default:
                throw new Exception("Unsupported configuration file format: $extension");
        }
    }
    
    /**
     * Simple YAML parser for basic configurations
     * 
     * @param string $file YAML file path
     * @return array Parsed configuration
     */
    protected static function parseSimpleYaml(string $file): array
    {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        $config = [];
        $currentSection = &$config;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                if (empty($value)) {
                    $currentSection[$key] = [];
                    $currentSection = &$currentSection[$key];
                } else {
                    $currentSection[$key] = $value;
                }
            }
        }
        
        return $config;
    }
    
    /**
     * Normalize configuration from different sources
     * 
     * @param array $config Raw configuration
     * @return array Normalized configuration
     */
    protected static function normalizeConfig(array $config): array
    {
        // Handle nested database configuration
        if (isset($config['database'])) {
            $dbConfig = $config['database'];
            
            return [
                'host' => $dbConfig['host'] ?? 'localhost',
                'port' => (int)($dbConfig['port'] ?? 3306),
                'username' => $dbConfig['username'] ?? '',
                'password' => $dbConfig['password'] ?? '',
                'database' => $dbConfig['legacy']['database'] ?? $dbConfig['database'] ?? '',
                'charset' => $dbConfig['charset'] ?? 'utf8mb4',
            ];
        }
        
        return $config;
    }
    
    /**
     * Get database connection with automatic fallback
     * 
     * @return DatabaseConnectionInterface Database connection
     * @throws Exception If no suitable driver is available
     */
    public static function getConnection(): DatabaseConnectionInterface
    {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        $config = self::getConfig();
        $lastException = null;
        
        foreach (self::$driverPriority as $driver) {
            try {
                switch ($driver) {
                    case 'pdo_mysql':
                        if (self::isPdoMysqlAvailable()) {
                            self::$connection = new PdoConnection($config);
                            self::$currentDriver = 'pdo_mysql';
                            return self::$connection;
                        }
                        break;
                        
                    case 'mysqli':
                        if (self::isMysqliAvailable()) {
                            self::$connection = new MysqliConnection($config);
                            self::$currentDriver = 'mysqli';
                            return self::$connection;
                        }
                        break;
                        
                    case 'pdo_sqlite':
                        if (self::isPdoSqliteAvailable()) {
                            self::$connection = new PdoSqliteConnection();
                            self::$currentDriver = 'pdo_sqlite';
                            return self::$connection;
                        }
                        break;
                }
            } catch (Exception $e) {
                $lastException = $e;
                continue;
            }
        }
        
        throw new Exception(
            "No suitable database driver available. Last error: " . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }
    
    /**
     * Check if PDO MySQL is available
     * 
     * @return bool True if available
     */
    protected static function isPdoMysqlAvailable(): bool
    {
        return extension_loaded('pdo') && 
               extension_loaded('pdo_mysql') && 
               in_array('mysql', PDO::getAvailableDrivers());
    }
    
    /**
     * Check if MySQLi is available
     * 
     * @return bool True if available
     */
    protected static function isMysqliAvailable(): bool
    {
        return extension_loaded('mysqli');
    }
    
    /**
     * Check if PDO SQLite is available
     * 
     * @return bool True if available
     */
    protected static function isPdoSqliteAvailable(): bool
    {
        return extension_loaded('pdo') && 
               extension_loaded('pdo_sqlite') && 
               in_array('sqlite', PDO::getAvailableDrivers());
    }
    
    /**
     * Get current driver name
     * 
     * @return string|null Current driver name
     */
    public static function getCurrentDriver(): ?string
    {
        return self::$currentDriver;
    }
    
    /**
     * Reset connection (for testing)
     */
    public static function resetConnection(): void
    {
        self::$connection = null;
        self::$currentDriver = null;
    }
    
    /**
     * Helper method for running queries
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array Results
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Helper method for running single row queries
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|null Single row result
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Helper method for running scalar queries
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return mixed Single value result
     */
    public static function fetchValue(string $sql, array $params = [])
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Helper method for executing statements
     * 
     * @param string $sql SQL statement
     * @param array $params Query parameters
     * @return int Number of affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $connection = self::getConnection();
        $stmt = $connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Get last insert ID
     * 
     * @return string Last insert ID
     */
    public static function lastInsertId(): string
    {
        $connection = self::getConnection();
        return $connection->lastInsertId();
    }
    
    /**
     * Begin database transaction
     * 
     * @return bool Success status
     */
    public static function beginTransaction(): bool
    {
        $connection = self::getConnection();
        return $connection->beginTransaction();
    }
    
    /**
     * Commit database transaction
     * 
     * @return bool Success status
     */
    public static function commit(): bool
    {
        $connection = self::getConnection();
        return $connection->commit();
    }
    
    /**
     * Rollback database transaction
     * 
     * @return bool Success status
     */
    public static function rollback(): bool
    {
        $connection = self::getConnection();
        return $connection->rollback();
    }
}
?>
