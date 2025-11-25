<?php
/**
 * PortfolioDAO: Handles portfolio data for any type (micro, blue-chip, small-cap, etc.)
 * 
 * @startuml PortfolioDAO_Class_Diagram
 * !define RECTANGLE class
 * 
 * class PortfolioDAO extends CommonDAO {
 *   - csvPath : string
 *   - sessionKey : string
 *   - tableName : string
 *   - sessionManager : SessionManager
 *   --
 *   + __construct(csvPath : string, tableName : string, dbConfigClass : class)
 *   + readPortfolio() : array
 *   + writePortfolio(rows : array) : bool
 *   + getRetryData() : array|null
 *   + clearRetryData() : void
 *   - readPortfolioCsv() : array
 *   - writePortfolioCsv(rows : array) : bool
 *   - writePortfolioDb(rows : array) : bool
 * }
 * 
 * abstract class CommonDAO {
 *   # pdo : PDO
 *   # errors : array
 *   # readCsv(csvPath : string) : array
 *   # writeCsv(csvPath : string, rows : array) : bool
 * }
 * 
 * class SessionManager {
 *   + setRetryData(key : string, data : mixed) : bool
 *   + getRetryData(key : string) : mixed
 *   + clearRetryData(key : string) : bool
 * }
 * 
 * PortfolioDAO --> SessionManager : manages retry data
 * CommonDAO <|-- PortfolioDAO
 * 
 * note right of PortfolioDAO : Dual storage strategy:\nDB-first read, CSV fallback\nDual-write with retry mechanism
 * @enduml
 * 
 * @startuml PortfolioDAO_Read_Strategy
 * start
 * :Read Portfolio Request;
 * 
 * if (Database available?) then (yes)
 *   :Query latest portfolio from DB;
 *   if (Data found?) then (yes)
 *     :Return DB data;
 *     stop
 *   else (no)
 *     :Log "no DB data";
 *   endif
 * else (no)
 *   :Log "DB connection failed";
 * endif
 * 
 * :Fallback to CSV;
 * :Read CSV file;
 * :Filter for latest date;
 * :Return CSV data;
 * stop
 * @enduml
 * 
 * @startuml PortfolioDAO_Write_Strategy
 * start
 * :Write Portfolio Request;
 * 
 * fork
 *   :Write to CSV;
 *   if (CSV write successful?) then (yes)
 *     :Set csvOk = true;
 *   else (no)
 *     :Set csvOk = false;
 *     :Log CSV error;
 *   endif
 * fork again
 *   :Write to Database;
 *   if (DB write successful?) then (yes)
 *     :Set dbOk = true;
 *   else (no)
 *     :Set dbOk = false;
 *     :Log DB error;
 *   endif
 * end fork
 * 
 * if (Both operations successful?) then (yes)
 *   :Clear retry data;
 *   :Return true;
 * else (no)
 *   :Store data for retry;
 *   :Return false;
 * endif
 * 
 * stop
 * @enduml
 * 
 * @startuml PortfolioDAO_Data_Flow
 * participant "Client" as C
 * participant "PortfolioDAO" as P
 * participant "Database" as DB
 * participant "CSV File" as CSV
 * participant "SessionManager" as SM
 * 
 * == Read Operation ==
 * C -> P: readPortfolio()
 * P -> DB: SELECT * FROM portfolio WHERE date = MAX(date)
 * 
 * alt DB data available
 *   DB --> P: portfolio rows
 *   P --> C: return DB data
 * else DB fails or empty
 *   P -> CSV: readCsv(csvPath)
 *   CSV --> P: CSV data
 *   P -> P: filter for latest date
 *   P --> C: return filtered CSV data
 * end
 * 
 * == Write Operation ==
 * C -> P: writePortfolio(rows)
 * 
 * par parallel writes
 *   P -> CSV: writePortfolioCsv(rows)
 *   CSV --> P: success/failure
 * and
 *   P -> DB: writePortfolioDb(rows)
 *   DB --> P: success/failure
 * end
 * 
 * alt both succeed
 *   P -> SM: clearRetryData(sessionKey)
 * else any fails
 *   P -> SM: setRetryData(sessionKey, rows)
 * end
 * 
 * P --> C: combined success result
 * @enduml
 * 
 * Handles portfolio data for any type (micro, blue-chip, small-cap, etc.) with 
 * DB-first read, CSV fallback, and dual-write strategy.
 * 
 * Key Features:
 * - Resilient dual-storage architecture (Database + CSV)
 * - Database-first read with automatic CSV fallback
 * - Parallel write operations to both storage systems
 * - Automatic retry mechanism for failed operations
 * - Portfolio type agnostic (configurable table names)
 * - Latest date filtering for historical data
 * 
 * Storage Strategy:
 * Read Priority: Database → CSV fallback
 * Write Strategy: Dual-write (CSV + Database in parallel)
 * Failure Handling: Session-based retry data storage
 * 
 * Design Patterns:
 * - Template Method: Inherits from CommonDAO
 * - Strategy: Dual-storage with fallback strategy
 * - Command: Retry mechanism for failed operations
 * - Facade: Simplifies complex storage operations
 * 
 * Data Consistency:
 * - REPLACE INTO for database upserts
 * - Latest date filtering for current portfolio state
 * - Automatic data format normalization between CSV and DB
 * - Session persistence for retry operations
 */

require_once __DIR__ . '/CommonDAO.php';
require_once __DIR__ . '/SessionManager.php';

class PortfolioDAO extends CommonDAO {
    private $csvPath;
    private $sessionKey;
    private $tableName;
    private $sessionManager;

    public function __construct($csvPath, $tableName, $dbConfigClass) {
        parent::__construct($dbConfigClass);
        $this->csvPath = $csvPath;
        $this->tableName = $tableName;
        $this->sessionKey = 'portfolio_retry_' . $tableName;
        
        // Use centralized SessionManager instead of direct session_start()
        $this->sessionManager = SessionManager::getInstance();
    }

    public function readPortfolio() {
        // Try DB first
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM {$this->tableName} WHERE date = (SELECT MAX(date) FROM {$this->tableName})");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) return $rows;
            } catch (Exception $e) {
                $this->logError('DB read failed: ' . $e->getMessage());
            }
        }
        // Fallback to CSV
        return $this->readPortfolioCsv();
    }

    public function writePortfolio($rows) {
        $csvOk = $this->writePortfolioCsv($rows);
        $dbOk = $this->writePortfolioDb($rows);
        if (!$csvOk || !$dbOk) {
            $this->sessionManager->setRetryData($this->sessionKey, $rows);
        } else {
            $this->sessionManager->clearRetryData($this->sessionKey);
        }
        return $csvOk && $dbOk;
    }


    private function readPortfolioCsv() {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) return [];
        // Get latest date
        $dates = array_column($data, 'Date');
        $latest = max($dates);
        return array_values(array_filter($data, function($r) use ($latest) { return $r['Date'] === $latest; }));
    }

    private function writePortfolioCsv($rows) {
        return $this->writeCsv($this->csvPath, $rows);
    }

    private function writePortfolioDb($rows) {
        if (!$this->pdo) return false;
        try {
            foreach ($rows as $row) {
                $stmt = $this->pdo->prepare("REPLACE INTO {$this->tableName} (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $row['Ticker'] ?? $row['symbol'] ?? '',
                    $row['Date'],
                    $row['Shares'] ?? $row['position_size'] ?? 0,
                    $row['Buy Price'] ?? $row['avg_cost'] ?? 0,
                    $row['Current Price'] ?? $row['current_price'] ?? 0,
                    $row['Total Value'] ?? $row['market_value'] ?? 0,
                    $row['PnL'] ?? $row['unrealized_pnl'] ?? 0
                ]);
            }
            return true;
        } catch (Exception $e) {
            $this->logError('DB write failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrors() {
        return $this->errors;
    }
    protected function logError($msg) {
        $this->errors[] = $msg;
    }
    public function getRetryData() {
        return $this->sessionManager->getRetryData($this->sessionKey);
    }
    
    public function clearRetryData() {
        $this->sessionManager->clearRetryData($this->sessionKey);
    }
}
