<?php
/**
 * Alternative Database Connection using MySQLi
 * Fallback for when PDO MySQL driver is not available
 */

class MySQLiDatabaseConfig {
    private static $connection = null;
    
    public static function createConnection() {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        // Load config from INI file
        $configFile = __DIR__ . '/../db_config.ini';
        if (!file_exists($configFile)) {
            throw new Exception('Database configuration file not found: ' . $configFile);
        }
        
        $config = parse_ini_file($configFile, true);
        
        $host = $config['database']['host'] ?? 'localhost';
        $port = $config['database']['port'] ?? 3306;
        $username = $config['database']['username'] ?? '';
        $password = $config['database']['password'] ?? '';
        $database = $config['database']['legacy']['database'] ?? 'stock_market_2';
        
        try {
            $mysqli = new mysqli($host, $username, $password, $database, $port);
            
            if ($mysqli->connect_error) {
                throw new Exception('Connection failed: ' . $mysqli->connect_error);
            }
            
            $mysqli->set_charset('utf8mb4');
            
            self::$connection = $mysqli;
            return $mysqli;
            
        } catch (Exception $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function query($sql, $params = []) {
        $mysqli = self::createConnection();
        
        if (empty($params)) {
            $result = $mysqli->query($sql);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            return $result;
        }
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $mysqli->error);
        }
        
        if (!empty($params)) {
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
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result;
    }
}
?>
