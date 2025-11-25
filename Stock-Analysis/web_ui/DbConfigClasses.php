<?php
// Utility function to ensure api_keys table exists in legacy DB
function ensureApiKeysTable() {
    try {
        $pdo = LegacyDatabaseConfig::createConnection();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE TABLE IF NOT EXISTS api_keys (
            name VARCHAR(64) PRIMARY KEY,
            value VARCHAR(256)
        )");
    } catch (Exception $e) {
        // Ignore for now
    }
}
/**
 * BaseDatabaseConfig: Abstract base for all DB config classes
 */
abstract class BaseDatabaseConfig {
    protected static $config = null;
    protected static $configFile = null;

    public static function load($configFile = null) {
        if (static::$config !== null && $configFile === static::$configFile) {
            return static::$config;
        }
        // Default config file locations
        if ($configFile === null) {
            $possibleFiles = [
                __DIR__ . '/db_config.yml',
                __DIR__ . '/db_config.yaml',
                __DIR__ . '/db_config.ini',
                __DIR__ . '/../db_config.yml',
                __DIR__ . '/../db_config.yaml',
                __DIR__ . '/../db_config.ini'
            ];
            foreach ($possibleFiles as $file) {
                if (file_exists($file)) {
                    $configFile = $file;
                    break;
                }
            }
        }
        if (!$configFile || !file_exists($configFile)) {
            throw new Exception('Database configuration file not found.');
        }
        $extension = pathinfo($configFile, PATHINFO_EXTENSION);
        switch (strtolower($extension)) {
            case 'yml':
            case 'yaml':
                static::$config = static::loadYaml($configFile);
                break;
            case 'ini':
                static::$config = parse_ini_file($configFile, true);
                break;
            default:
                throw new Exception('Unsupported configuration file format.');
        }
        static::$configFile = $configFile;
        return static::$config;
    }
    protected static function loadYaml($file) {
        if (function_exists('yaml_parse_file')) {
            return yaml_parse_file($file);
        } else {
            // Simple YAML parser for basic configs
            $content = file_get_contents($file);
            $lines = explode("\n", $content);
            $config = [];
            $currentSection = &$config;
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || $line[0] === '#') continue;
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
    }
}

/**
 * MicroCapDatabaseConfig: Handles micro-cap DB only
 */
class MicroCapDatabaseConfig extends BaseDatabaseConfig {
    public static function getConfig() {
        $config = static::load();
        return [
            'host' => $config['database']['host'] ?? 'localhost',
            'port' => $config['database']['port'] ?? 3306,
            'dbname' => $config['database']['micro_cap']['database'] ?? 'micro_cap_trading',
            'username' => $config['database']['username'] ?? '',
            'password' => $config['database']['password'] ?? '',
            'charset' => $config['database']['charset'] ?? 'utf8mb4'
        ];
    }
    public static function createConnection() {
        $c = static::getConfig();
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['dbname'], $c['charset']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, $c['username'], $c['password'], $options);
    }
}

/**
 * LegacyDatabaseConfig: Handles legacy DB only
 */
class LegacyDatabaseConfig extends BaseDatabaseConfig {
    public static function getConfig() {
        $config = static::load();
        return [
            'host' => $config['database']['host'] ?? 'localhost',
            'port' => $config['database']['port'] ?? 3306,
            'dbname' => $config['database']['legacy']['database'] ?? 'stock_market_2',
            'username' => $config['database']['username'] ?? '',
            'password' => $config['database']['password'] ?? '',
            'charset' => $config['database']['charset'] ?? 'utf8mb4'
        ];
    }
    public static function createConnection() {
        $c = static::getConfig();
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['dbname'], $c['charset']);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5, // 5 second connection timeout
        ];
        
        // Set connection timeout at system level to prevent hanging
        ini_set('default_socket_timeout', 5);
        ini_set('mysql.connect_timeout', 5);
        
        return new PDO($dsn, $c['username'], $c['password'], $options);
    }
}
