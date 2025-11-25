<?php
/**
 * MicroCapPortfolioDAO: Handles micro-cap portfolio data with DB-first read, CSV fallback, and dual-write.
 * On write: writes CSV first, then DB. On read: tries DB, falls back to CSV. Logs errors and stores failed data in session for retry.
 */
class MicroCapPortfolioDAO {
    private $csvPath;
    private $pdo;
    private $errors = [];
    private $sessionKey = 'microcap_portfolio_retry';

    public function __construct($csvPath) {
        $this->csvPath = $csvPath;
        $this->pdo = null;
        // Use centralized SessionManager instead of direct session_start()
        require_once __DIR__ . '/SessionManager.php';
        SessionManager::getInstance();
        $this->connectDb();
    }

    private function connectDb() {
        try {
            require_once __DIR__ . '/DbConfigClasses.php';
            $this->pdo = MicroCapDatabaseConfig::createConnection();
        } catch (Exception $e) {
            $this->pdo = null;
            $this->logError('DB connection failed: ' . $e->getMessage());
        }
    }

    public function readPortfolio() {
        // Try DB first
        if ($this->pdo) {
            try {
                $stmt = $this->pdo->query("SELECT * FROM portfolio_data WHERE date = (SELECT MAX(date) FROM portfolio_data)");
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
            $_SESSION[$this->sessionKey] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey]);
        }
        return $csvOk && $dbOk;
    }

    private function readPortfolioCsv() {
        if (!file_exists($this->csvPath)) return [];
        $rows = array_map('str_getcsv', file($this->csvPath));
        $header = $rows[0];
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = array_combine($header, $rows[$i]);
            $data[] = $row;
        }
        // Get latest date
        $dates = array_column($data, 'Date');
        $latest = max($dates);
        return array_values(array_filter($data, function($r) use ($latest) { return $r['Date'] === $latest; }));
    }

    private function writePortfolioCsv($rows) {
        try {
            if (empty($rows)) return false;
            $header = array_keys($rows[0]);
            $fp = fopen($this->csvPath, 'w');
            fputcsv($fp, $header, ',', '"', '\\');
            foreach ($rows as $row) fputcsv($fp, $row, ',', '"', '\\');
            fclose($fp);
            return true;
        } catch (Exception $e) {
            $this->logError('CSV write failed: ' . $e->getMessage());
            return false;
        }
    }

    private function writePortfolioDb($rows) {
        if (!$this->pdo) return false;
        try {
            foreach ($rows as $row) {
                $stmt = $this->pdo->prepare("REPLACE INTO portfolio_data (symbol, date, position_size, avg_cost, current_price, market_value, unrealized_pnl) VALUES (?, ?, ?, ?, ?, ?, ?)");
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
    private function logError($msg) {
        $this->errors[] = $msg;
    }
    public function getRetryData() {
        return $_SESSION[$this->sessionKey] ?? null;
    }
    public function clearRetryData() {
        unset($_SESSION[$this->sessionKey]);
    }
}
