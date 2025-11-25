<?php

require_once __DIR__ . '/CoreInterfaces.php';

/**
 * Improved database connection using dependency injection
 */
class DatabaseConnection implements DatabaseConnectionInterface
{
    private $pdo;
    private $config;
    private $logger;
    private static $instance = null;

    public function __construct(ConfigurationInterface $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function connect()
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
            $host = $this->config->get('database.host', 'localhost');
            $dbname = $this->config->get('database.name');
            $username = $this->config->get('database.username');
            $password = $this->config->get('database.password');
            $port = $this->config->get('database.port', 3306);

            if (empty($dbname) || empty($username)) {
                throw new InvalidArgumentException('Database configuration is incomplete');
            }

            $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            $this->logger->info('Database connection established', [
                'host' => $host,
                'database' => $dbname,
                'port' => $port
            ]);

            return $this->pdo;
            
        } catch (PDOException $e) {
            $this->logger->error('Database connection failed', [
                'error' => $e->getMessage(),
                'host' => $host ?? 'unknown',
                'database' => $dbname ?? 'unknown'
            ]);
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function disconnect()
    {
        if ($this->pdo !== null) {
            $this->pdo = null;
            $this->logger->info('Database connection closed');
        }
    }

    public function getPDO()
    {
        return $this->connect();
    }

    public function getConnection()
    {
        return $this->connect();
    }

    public function isConnected()
    {
        return $this->pdo !== null;
    }

    public function query($sql, array $params = [])
    {
        $pdo = $this->connect();
        
        try {
            if (empty($params)) {
                $stmt = $pdo->query($sql);
            } else {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
            }
            
            $this->logger->debug('Database query executed', [
                'sql' => $sql,
                'params' => $params
            ]);
            
            return $stmt;
            
        } catch (PDOException $e) {
            $this->logger->error('Database query failed', [
                'sql' => $sql,
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            throw new RuntimeException('Database query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function beginTransaction()
    {
        $pdo = $this->connect();
        return $pdo->beginTransaction();
    }

    public function commit()
    {
        if ($this->pdo) {
            return $this->pdo->commit();
        }
        return false;
    }

    public function rollback()
    {
        if ($this->pdo) {
            return $this->pdo->rollback();
        }
        return false;
    }

    public function lastInsertId()
    {
        if ($this->pdo) {
            return $this->pdo->lastInsertId();
        }
        return false;
    }
}

/**
 * Configuration implementation using array or file-based config
 */
class Configuration implements ConfigurationInterface
{
    private $config;
    private $logger;

    public function __construct(array $config = [], LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger ?: new NullLogger();
        
        // Load from environment if config is empty
        if (empty($config)) {
            $this->loadFromEnvironment();
        }
    }

    public function get($key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
        
        $this->logger->debug('Configuration value set', [
            'key' => $key,
            'value' => is_string($value) ? $value : gettype($value)
        ]);
    }

    public function has($key)
    {
        return $this->get($key) !== null;
    }

    public function all()
    {
        return $this->config;
    }

    public function load($configFile = null)
    {
        if ($configFile !== null) {
            if (!file_exists($configFile)) {
                throw new InvalidArgumentException("Configuration file not found: $configFile");
            }
            $this->config = require $configFile;
            $this->logger->info('Configuration loaded from file', ['file' => $configFile]);
        } else {
            $this->loadFromEnvironment();
        }
    }

    private function loadFromEnvironment()
    {
        // Load database configuration from environment or default file
        $configFile = __DIR__ . '/config/database.php';
        
        if (file_exists($configFile)) {
            $this->config = require $configFile;
            $this->logger->info('Configuration loaded from file', ['file' => $configFile]);
        } else {
            // Fallback to environment variables
            $this->config = [
                'database' => [
                    'host' => $_ENV['DB_HOST'] ?? 'localhost',
                    'port' => $_ENV['DB_PORT'] ?? 3306,
                    'name' => $_ENV['DB_NAME'] ?? '',
                    'username' => $_ENV['DB_USERNAME'] ?? '',
                    'password' => $_ENV['DB_PASSWORD'] ?? ''
                ]
            ];
            $this->logger->info('Configuration loaded from environment variables');
        }
    }

    public static function fromFile($filePath)
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException("Configuration file not found: $filePath");
        }

        $config = require $filePath;
        return new self($config);
    }
}
