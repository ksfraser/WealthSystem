<?php
require_once __DIR__ . '/CommonDAO.php';

class StockSymbolDAO extends CommonDAO {

    public function __construct() {
        parent::__construct('LegacyDatabaseConfig');
    }

    /**
     * Checks if a symbol exists in the master list.
     *
     * @param string $symbol The stock symbol.
     * @return bool True if the symbol exists, false otherwise.
     */
    public function symbolExists($symbol) {
        $stmt = $this->pdo->prepare("SELECT 1 FROM stock_symbols WHERE symbol = ?");
        $stmt->execute([$symbol]);
        return $stmt->fetchColumn() !== false;
    }

    /**
     * Adds a new symbol to the master list.
     *
     * @param string $symbol The stock symbol to add.
     * @return bool True on success, false on failure.
     */
    public function addSymbol($symbol) {
        if ($this->symbolExists($symbol)) {
            return true; // Symbol already exists
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO stock_symbols (symbol, added_date) VALUES (?, NOW())");
            $stmt->execute([$symbol]);
            return true;
        } catch (PDOException $e) {
            // Handle potential race conditions if another process added the symbol
            if ($e->getCode() == '23000') { // Integrity constraint violation
                return true;
            }
            // Log error if needed
            error_log("Failed to add symbol {$symbol}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates the necessary database tables for a new stock symbol.
     *
     * @param string $symbol The stock symbol.
     */
    public function createSymbolTables($symbol) {
        $tableName = 'hist_prices_' . strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', $symbol));

        $sql = "CREATE TABLE IF NOT EXISTS `{$tableName}` (
            `date` DATE NOT NULL,
            `open` DECIMAL(10, 4) NOT NULL,
            `high` DECIMAL(10, 4) NOT NULL,
            `low` DECIMAL(10, 4) NOT NULL,
            `close` DECIMAL(10, 4) NOT NULL,
            `adj_close` DECIMAL(10, 4) NOT NULL,
            `volume` BIGINT NOT NULL,
            PRIMARY KEY (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create table for symbol {$symbol}: " . $e->getMessage());
            throw new Exception("Failed to create table for symbol {$symbol}: " . $e->getMessage());
        }
    }

    /**
     * Triggers a background script to download historical data for a symbol.
     *
     * @param string $symbol The stock symbol.
     */
    public function triggerHistoricalDataDownload($symbol) {
        $scriptPath = __DIR__ . '/download_historical_data.php';
        $php_path = PHP_BINARY; // Fallback: "C:/php/php.exe";
        
        // Execute the script in the background
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = 'start /B ' . $php_path . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($symbol) . ' > NUL 2>&1';
        } else {
            // Linux/Mac
            $command = $php_path . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($symbol) . ' > /dev/null 2>&1 &';
        }
        
        pclose(popen($command, 'r'));
    }

    /**
     * Ensures a symbol exists, creating it and its infrastructure if it doesn't.
     *
     * @param string $symbol The stock symbol.
     */
    public function ensureSymbolExists($symbol) {
        if (empty($symbol)) {
            return;
        }

        if (!$this->symbolExists($symbol)) {
            $this->addSymbol($symbol);
            $this->createSymbolTables($symbol);
            $this->triggerHistoricalDataDownload($symbol);
        }
    }
}
