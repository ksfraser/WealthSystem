<?php
/**
 * CommonDAO: Base class for all DAOs, handles DB connection, error logging, and config.
 * 
 * @startuml CommonDAO_Class_Diagram
 * !define RECTANGLE class
 * 
 * abstract class CommonDAO {
 *   # pdo : PDO
 *   # errors : array
 *   # dbConfigClass : class
 *   --
 *   + __construct(dbConfigClass : class)
 *   # connectDb() : void
 *   # readCsv(csvPath : string) : array
 *   # writeCsv(csvPath : string, rows : array) : bool
 *   + getErrors() : array
 *   + getPdo() : PDO
 *   # logError(msg : string) : void
 * }
 * 
 * class DbConfig {
 *   + {static} createConnection() : PDO
 * }
 * 
 * CommonDAO --> DbConfig : uses for connection
 * 
 * note right of CommonDAO : Abstract base for all\nDAO implementations\nProvides DB connectivity\nand CSV operations
 * note right of DbConfig : Static factory for\nPDO connections
 * @enduml
 * 
 * @startuml CommonDAO_Inheritance
 * abstract class CommonDAO {
 *   # pdo : PDO
 *   # errors : array
 *   # connectDb()
 *   # readCsv()
 *   # writeCsv()
 *   + getErrors()
 *   + getPdo()
 * }
 * 
 * class UserAuthDAO extends CommonDAO {
 *   + authenticateUser()
 *   + createUser()
 *   + updateUser()
 * }
 * 
 * class PortfolioDAO extends CommonDAO {
 *   + getPortfolios()
 *   + savePortfolio()
 *   + deletePortfolio()
 * }
 * 
 * class TradeLogDAO extends CommonDAO {
 *   + logTrade()
 *   + getTradeHistory()
 *   + updateTrade()
 * }
 * 
 * CommonDAO <|-- UserAuthDAO
 * CommonDAO <|-- PortfolioDAO
 * CommonDAO <|-- TradeLogDAO
 * 
 * note right of CommonDAO : Template Method pattern\nfor DAO operations
 * @enduml
 * 
 * @startuml CommonDAO_Connection_Sequence
 * participant "ConcreteDAO" as DAO
 * participant "CommonDAO" as COMMON
 * participant "DbConfig" as CONFIG
 * participant "PDO" as PDO
 * 
 * DAO -> COMMON: __construct(dbConfigClass)
 * activate COMMON
 * COMMON -> COMMON: connectDb()
 * COMMON -> COMMON: set timeout (5 sec)
 * COMMON -> CONFIG: createConnection()
 * activate CONFIG
 * CONFIG -> PDO: new PDO(dsn, user, pass)
 * activate PDO
 * PDO --> CONFIG: connection
 * deactivate PDO
 * CONFIG --> COMMON: PDO instance
 * deactivate CONFIG
 * COMMON -> COMMON: restore timeout
 * COMMON --> DAO: initialized DAO
 * deactivate COMMON
 * 
 * alt connection fails
 *   COMMON -> COMMON: logError(message)
 *   COMMON -> COMMON: set pdo = null
 * end
 * @enduml
 * 
 * Base class for all DAOs, handles DB connection, error logging, and config.
 * Implements the Template Method pattern for common DAO operations.
 * 
 * Key Features:
 * - Centralized database connection management
 * - Connection timeout handling to prevent hanging
 * - Generic CSV read/write operations
 * - Error collection and logging
 * - Public PDO access for advanced queries
 * - Configurable database connection via dependency injection
 * 
 * Design Patterns:
 * - Template Method: Defines structure for DAO operations
 * - Abstract Factory: Base for concrete DAO implementations
 * - Dependency Injection: Database configuration class injection
 * - Error Collector: Accumulates errors for centralized handling
 * 
 * Connection Management:
 * - Automatic timeout configuration (5 seconds)
 * - Graceful failure handling
 * - Connection restoration after exceptions
 * - Support for different database configurations
 * 
 * CSV Integration:
 * - Built-in CSV operations for data import/export
 * - Header-based associative array conversion
 * - Error handling for file operations
 */
abstract class CommonDAO {
    protected $pdo;
    protected $errors = [];
    protected $dbConfigClass;

    public function __construct($dbConfigClass) {
        $this->dbConfigClass = $dbConfigClass;
        $this->connectDb();
    }

    protected function connectDb() {
        try {
            require_once __DIR__ . '/DbConfigClasses.php';
            
            // Set a connection timeout to prevent hanging
            $originalTimeout = ini_get('default_socket_timeout');
            ini_set('default_socket_timeout', 5); // 5 second timeout
            
            $this->pdo = $this->dbConfigClass::createConnection();
            
            // Restore original timeout
            ini_set('default_socket_timeout', $originalTimeout);
            
        } catch (Exception $e) {
            $this->pdo = null;
            $this->logError('DB connection failed: ' . $e->getMessage());
            
            // Restore original timeout in case of exception
            if (isset($originalTimeout)) {
                ini_set('default_socket_timeout', $originalTimeout);
            }
        }
    }

    // Generic CSV read
    protected function readCsv($csvPath) {
        if (!file_exists($csvPath)) return [];
        $rows = array_map('str_getcsv', file($csvPath));
        if (count($rows) < 2) return [];
        $header = $rows[0];
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $data[] = array_combine($header, $rows[$i]);
        }
        return $data;
    }

    // Generic CSV write
    protected function writeCsv($csvPath, $rows) {
        try {
            if (empty($rows)) return false;
            $header = array_keys($rows[0]);
            $fp = fopen($csvPath, 'w');
            fputcsv($fp, $header, ',', '"', '\\');
            foreach ($rows as $row) fputcsv($fp, $row, ',', '"', '\\');
            fclose($fp);
            return true;
        } catch (Exception $e) {
            $this->logError('CSV write failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }

    // Public accessor for PDO connection
    public function getPdo() {
        return $this->pdo;
    }

    protected function logError($msg) {
        $this->errors[] = $msg;
    }
}
