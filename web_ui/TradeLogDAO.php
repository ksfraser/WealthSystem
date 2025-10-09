<?php
/**
     * TradeLogDAO: Handles trade log data for any type (micro, blue-chip, etc.) with DB-first read, CSV fallback, and dual-write.
     * On write: writes CSV first, then DB. On read: tries DB, falls back to CSV. Logs errors and stores failed data in session for retry.
     */

require_once __DIR__ . '/CommonDAO.php';
class TradeLogDAO extends CommonDAO {
    private $csvPath;
    private $sessionKey;
    private $tableName;

    public function __construct($csvPath, $tableName, $dbConfigClass) {
        parent::__construct($dbConfigClass);
        $this->csvPath = $csvPath;
        $this->tableName = $tableName;
        $this->sessionKey = 'tradelog_retry_' . $tableName;
        
        // Use centralized SessionManager instead of direct session_start()
        require_once __DIR__ . '/SessionManager.php';
        SessionManager::getInstance();
    }

    public function readTradeLog($filters = []) {
        // Try DB first
        if ($this->pdo) {
            try {
                $query = "SELECT * FROM {$this->tableName} WHERE 1=1";
                $params = [];
                if (!empty($filters['date_from'])) {
                    $query .= " AND date >= ?";
                    $params[] = $filters['date_from'];
                }
                if (!empty($filters['date_to'])) {
                    $query .= " AND date <= ?";
                    $params[] = $filters['date_to'];
                }
                if (!empty($filters['ticker'])) {
                    $query .= " AND symbol = ?";
                    $params[] = $filters['ticker'];
                }
                if (!empty($filters['cost_min'])) {
                    $query .= " AND amount >= ?";
                    $params[] = $filters['cost_min'];
                }
                if (!empty($filters['cost_max'])) {
                    $query .= " AND amount <= ?";
                    $params[] = $filters['cost_max'];
                }
                $query .= " ORDER BY date DESC, id DESC LIMIT 100";
                $stmt = $this->pdo->prepare($query);
                $stmt->execute($params);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($rows) return $rows;
            } catch (Exception $e) {
                $this->logError('DB read failed: ' . $e->getMessage());
            }
        }
        // Fallback to CSV
        return $this->readTradeLogCsv($filters);
    }

    public function writeTradeLog($rows) {
        $csvOk = $this->writeTradeLogCsv($rows);
        $dbOk = $this->writeTradeLogDb($rows);
        if (!$csvOk || !$dbOk) {
            $_SESSION[$this->sessionKey] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey]);
        }
        return $csvOk && $dbOk;
    }

    private function readTradeLogCsv($filters = []) {
        if (!file_exists($this->csvPath)) return [];
        $rows = array_map('str_getcsv', file($this->csvPath));
        $header = $rows[0];
        $data = [];
        for ($i = 1; $i < count($rows); $i++) {
            $row = array_combine($header, $rows[$i]);
            $data[] = $row;
        }
        // Apply filters
        $data = array_filter($data, function($r) use ($filters) {
            if (!empty($filters['date_from']) && $r['Date'] < $filters['date_from']) return false;
            if (!empty($filters['date_to']) && $r['Date'] > $filters['date_to']) return false;
            if (!empty($filters['ticker']) && strtoupper($r['Ticker']) !== strtoupper($filters['ticker'])) return false;
            if (!empty($filters['cost_min']) && isset($r['Cost Basis']) && $r['Cost Basis'] < $filters['cost_min']) return false;
            if (!empty($filters['cost_max']) && isset($r['Cost Basis']) && $r['Cost Basis'] > $filters['cost_max']) return false;
            return true;
        });
        return array_values($data);
    }

    private function writeTradeLogCsv($rows) {
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

    private function writeTradeLogDb($rows) {
        if (!$this->pdo) return false;
        try {
            foreach ($rows as $row) {
                $stmt = $this->pdo->prepare("REPLACE INTO {$this->tableName} (symbol, date, action, quantity, price, amount, reasoning) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $row['Ticker'] ?? $row['symbol'] ?? '',
                    $row['Date'],
                    $row['Action'] ?? '',
                    $row['Shares Bought'] ?? $row['Shares Sold'] ?? $row['quantity'] ?? 0,
                    $row['Buy Price'] ?? $row['Sell Price'] ?? $row['price'] ?? 0,
                    $row['Cost Basis'] ?? $row['Proceeds'] ?? $row['amount'] ?? 0,
                    $row['Reason'] ?? $row['reasoning'] ?? ''
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
        return $_SESSION[$this->sessionKey] ?? null;
    }
    public function clearRetryData() {
        unset($_SESSION[$this->sessionKey]);
    }
}
