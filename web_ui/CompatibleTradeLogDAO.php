<?php
/**
 * CompatibleTradeLogDAO: Simplified trade log data access compatible with F30 Apache setup
 * Uses CSV-first approach with optional filtering
 */

class CompatibleTradeLogDAO {
    private $csvPath;
    private $errors = [];
    private $sessionKey;

    public function __construct($csvPath, $sessionKeyPrefix = 'tradelog_retry') {
        $this->csvPath = $csvPath;
        $this->sessionKey = $sessionKeyPrefix . '_' . basename($csvPath);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function readTradeLog($filters = []) {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) return [];
        
        // Apply filters
        return $this->applyFilters($data, $filters);
    }

    public function writeTradeLog($rows) {
        $csvOk = $this->writeCsv($this->csvPath, $rows);
        if (!$csvOk) {
            $_SESSION[$this->sessionKey] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey]);
        }
        return $csvOk;
    }

    private function applyFilters($data, $filters) {
        if (empty($filters)) return $data;
        
        return array_filter($data, function($row) use ($filters) {
            // Date from filter
            if (!empty($filters['date_from']) && isset($row['Date'])) {
                if ($row['Date'] < $filters['date_from']) return false;
            }
            
            // Date to filter
            if (!empty($filters['date_to']) && isset($row['Date'])) {
                if ($row['Date'] > $filters['date_to']) return false;
            }
            
            // Ticker filter
            if (!empty($filters['ticker'])) {
                $ticker = $row['Ticker'] ?? $row['Symbol'] ?? '';
                if (stripos($ticker, $filters['ticker']) === false) return false;
            }
            
            // Cost min filter
            if (!empty($filters['cost_min'])) {
                $cost = $row['Cost'] ?? $row['Price'] ?? $row['avg_cost'] ?? 0;
                if ((float)$cost < (float)$filters['cost_min']) return false;
            }
            
            // Cost max filter
            if (!empty($filters['cost_max'])) {
                $cost = $row['Cost'] ?? $row['Price'] ?? $row['avg_cost'] ?? 0;
                if ((float)$cost > (float)$filters['cost_max']) return false;
            }
            
            return true;
        });
    }

    // Generic CSV read
    private function readCsv($csvPath) {
        if (!file_exists($csvPath)) {
            $this->logError("CSV file not found: $csvPath");
            return [];
        }
        
        try {
            $rows = array_map('str_getcsv', file($csvPath));
            if (count($rows) < 2) return [];
            
            $header = $rows[0];
            $data = [];
            for ($i = 1; $i < count($rows); $i++) {
                if (count($rows[$i]) === count($header)) {
                    $data[] = array_combine($header, $rows[$i]);
                }
            }
            return $data;
        } catch (Exception $e) {
            $this->logError('CSV read failed: ' . $e->getMessage());
            return [];
        }
    }

    // Generic CSV write
    private function writeCsv($csvPath, $rows) {
        try {
            if (empty($rows)) return false;
            
            // Ensure directory exists
            $dir = dirname($csvPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            $header = array_keys($rows[0]);
            $fp = fopen($csvPath, 'w');
            if (!$fp) {
                $this->logError("Cannot open CSV file for writing: $csvPath");
                return false;
            }
            
            fputcsv($fp, $header, ',', '"', '\\');
            foreach ($rows as $row) {
                fputcsv($fp, $row, ',', '"', '\\');
            }
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

    public function getRetryData() {
        return $_SESSION[$this->sessionKey] ?? null;
    }

    public function clearRetryData() {
        unset($_SESSION[$this->sessionKey]);
    }

    private function logError($msg) {
        $this->errors[] = $msg;
    }
}
