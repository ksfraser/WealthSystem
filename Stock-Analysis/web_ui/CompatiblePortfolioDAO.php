<?php
/**
 * CompatiblePortfolioDAO: Simplified portfolio data access compatible with F30 Apache setup
 * Uses CSV-first approach with optional local database fallback
 */

class CompatiblePortfolioDAO {
    private $csvPath;
    private $errors = [];
    private $sessionKey;

    public function __construct($csvPath, $sessionKeyPrefix = 'portfolio_retry') {
        $this->csvPath = $csvPath;
        $this->sessionKey = $sessionKeyPrefix . '_' . basename($csvPath);
        if (session_status() === PHP_SESSION_NONE) session_start();
    }

    public function readPortfolio() {
        return $this->readPortfolioCsv();
    }

    public function writePortfolio($rows) {
        $csvOk = $this->writePortfolioCsv($rows);
        if (!$csvOk) {
            $_SESSION[$this->sessionKey] = $rows;
        } else {
            unset($_SESSION[$this->sessionKey]);
        }
        return $csvOk;
    }

    private function readPortfolioCsv() {
        $data = $this->readCsv($this->csvPath);
        if (empty($data)) return [];
        
        // Get latest date if Date column exists
        if (isset($data[0]['Date'])) {
            $dates = array_column($data, 'Date');
            $latest = max($dates);
            return array_values(array_filter($data, function($r) use ($latest) { 
                return $r['Date'] === $latest; 
            }));
        }
        
        return $data;
    }

    private function writePortfolioCsv($rows) {
        return $this->writeCsv($this->csvPath, $rows);
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
